<?php

namespace App\Http\Controllers;

use App\Models\KasHarian;
use App\Models\MasterCustomer;
use App\Models\Penjualan;
use App\Models\PenjualanCartDraft;
use App\Models\PenjualanItem;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\CarbonPeriod;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class PenjualanController extends Controller
{
    public function index(Request $request): View
    {
        $ikanStock = $this->getIkanStockMap();
        $customers = MasterCustomer::query()->orderBy('nama_customer')->get();
        $pendingDiscrepancyCount = (int) DB::table('penjualan_selisih_stok')
            ->where('status', 'pending')
            ->count();

        $cartDrafts = collect();
        $activeDraftPayload = null;
        $activeDraftCustomerId = (int) $request->query('draft_customer_id', 0);
        $activeDraftCustomerName = null;
        $userId = Auth::id();

        if ($userId !== null) {
            $cartDrafts = PenjualanCartDraft::query()
                ->join('master_customer as mc', 'mc.id_customer', '=', 'penjualan_cart_drafts.id_customer')
                ->where('penjualan_cart_drafts.id_user', $userId)
                ->orderBy('mc.nama_customer')
                ->get([
                    'penjualan_cart_drafts.id_customer',
                    'mc.nama_customer',
                    'penjualan_cart_drafts.updated_at',
                ]);

            if ($activeDraftCustomerId > 0) {
                $activeDraft = PenjualanCartDraft::query()
                    ->where('id_user', $userId)
                    ->where('id_customer', $activeDraftCustomerId)
                    ->first();

                if ($activeDraft === null) {
                    return redirect()->route('penjualan.index')->withErrors([
                        'message' => 'Keranjang customer yang dipilih tidak ditemukan.',
                    ]);
                }

                $activeDraftPayload = $activeDraft->payload;
                $activeDraftCustomerName = (string) ($customers->firstWhere('id_customer', $activeDraftCustomerId)?->nama_customer ?? 'Customer');
            }
        }

        return view('penjualan.index', compact(
            'ikanStock',
            'customers',
            'pendingDiscrepancyCount',
            'cartDrafts',
            'activeDraftPayload',
            'activeDraftCustomerId',
            'activeDraftCustomerName'
        ));
    }

    public function saveCartDraft(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'id_customer' => ['required', 'integer', 'exists:master_customer,id_customer'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.id_ikan' => ['required', 'integer', 'exists:master_ikan,id_ikan'],
            'items.*.berat' => ['required', 'numeric', 'gt:0'],
            'items.*.harga_per_kg' => ['required', 'numeric', 'gt:0'],
            'bayar_tunai' => ['nullable', 'numeric', 'min:0'],
            'bayar_transfer' => ['nullable', 'numeric', 'min:0'],
            'keterangan' => ['nullable', 'string'],
            'allow_pending_discrepancy' => ['nullable', 'boolean'],
            'catatan_selisih' => ['nullable', 'string', 'max:255'],
        ]);

        $userId = Auth::id();
        if ($userId === null) {
            return back()->withInput()->withErrors([
                'message' => 'Sesi pengguna tidak ditemukan. Silakan login ulang lalu simpan keranjang lagi.',
            ]);
        }

        PenjualanCartDraft::query()->updateOrCreate(
            [
                'id_user' => $userId,
                'id_customer' => (int) $validated['id_customer'],
            ],
            [
                'payload' => [
                    'create_new_customer' => false,
                    'id_customer' => (int) $validated['id_customer'],
                    'items' => $validated['items'],
                    'bayar_tunai' => $validated['bayar_tunai'] ?? 0,
                    'bayar_transfer' => $validated['bayar_transfer'] ?? 0,
                    'allow_pending_discrepancy' => (bool) ($validated['allow_pending_discrepancy'] ?? false),
                    'catatan_selisih' => $validated['catatan_selisih'] ?? null,
                    'keterangan' => $validated['keterangan'] ?? null,
                ],
            ]
        );

        return redirect()->route('penjualan.index', [
            'draft_customer_id' => (int) $validated['id_customer'],
        ])->with('success', 'Keranjang customer berhasil disimpan. Anda bisa lanjut lagi kapan saja.');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.id_ikan' => ['required', 'integer', 'exists:master_ikan,id_ikan'],
            'items.*.berat' => ['required', 'numeric', 'gt:0'],
            'items.*.harga_per_kg' => ['required', 'numeric', 'gt:0'],
            'bayar_tunai' => ['nullable', 'numeric', 'min:0'],
            'bayar_transfer' => ['nullable', 'numeric', 'min:0'],
            'keterangan' => ['nullable', 'string'],
            'allow_pending_discrepancy' => ['nullable', 'boolean'],
            'catatan_selisih' => ['nullable', 'string', 'max:255'],
            'id_customer' => ['nullable', 'integer', 'exists:master_customer,id_customer'],
            'create_new_customer' => ['nullable', 'boolean'],
            'nama_customer_baru' => ['nullable', 'string', 'max:255'],
            'alamat_customer_baru' => ['nullable', 'string', 'max:255'],
            'telepon_customer_baru' => ['nullable', 'string', 'max:30'],
        ]);

        $today = now()->toDateString();

        [$customer, $customerError] = $this->resolveCustomer($validated);
        if ($customerError !== null) {
            return back()->withInput()->withErrors(['message' => $customerError]);
        }

        // Aggregate requested berat per ikan (same fish may appear in multiple rows)
        $requestedByIkan = [];
        foreach ($validated['items'] as $item) {
            $id = (int) $item['id_ikan'];
            $requestedByIkan[$id] = ($requestedByIkan[$id] ?? 0.0) + (float) $item['berat'];
        }

        $allowPendingDiscrepancy = (bool) ($validated['allow_pending_discrepancy'] ?? false);
        $stockShortages = [];

        // Stock check per unique ikan
        foreach ($requestedByIkan as $idIkan => $totalRequested) {
            $available = $this->getAvailableStockByIkan($idIkan);
            if ($totalRequested > $available) {
                $stockShortages[$idIkan] = [
                    'stok_tersedia' => $available,
                    'berat_diminta' => (float) $totalRequested,
                    'berat_selisih' => (float) ($totalRequested - $available),
                ];

                if ($allowPendingDiscrepancy) {
                    continue;
                }

                $namaIkan = DB::table('master_ikan')->where('id_ikan', $idIkan)->value('nama_ikan');

                return back()->withInput()->withErrors([
                    'items' => "Berat {$namaIkan} melebihi stok tersisa. Stok: ".number_format($available, 2).' kg. Centang simpan sebagai selisih sementara jika transaksi harus tetap diproses.',
                ]);
            }
        }

        $totalHarga = collect($validated['items'])->sum(fn ($i) => (float) $i['berat'] * (float) $i['harga_per_kg']);
        $bayarTunai = (float) ($validated['bayar_tunai'] ?? 0);
        $bayarTransfer = (float) ($validated['bayar_transfer'] ?? 0);
        $totalPenerimaan = $bayarTunai + $bayarTransfer;
        $piutang = max(0, $totalHarga - $totalPenerimaan);
        $statusPembayaran = $piutang <= 0 ? 'lunas' : 'piutang';
        $hasPendingDiscrepancy = false;

        try {
            DB::transaction(function () use ($today, $customer, $totalHarga, $bayarTunai, $bayarTransfer, $totalPenerimaan, $piutang, $statusPembayaran, $validated, $requestedByIkan, $allowPendingDiscrepancy, &$hasPendingDiscrepancy) {
                $penjualan = Penjualan::create([
                    'tanggal_penjualan' => $today,
                    'id_customer' => $customer?->id_customer,
                    'total_harga' => $totalHarga,
                    'bayar_tunai' => $bayarTunai,
                    'bayar_transfer' => $bayarTransfer,
                    'piutang' => $piutang,
                    'status_pembayaran' => $statusPembayaran,
                    'pembeli' => $customer?->nama_customer ?? '-',
                    'keterangan' => $validated['keterangan'] ?? 'Transaksi POS penjualan ikan',
                ]);

                $saleItems = [];
                foreach ($validated['items'] as $item) {
                    $createdItem = PenjualanItem::create([
                        'id_penjualan' => $penjualan->id_penjualan,
                        'id_ikan' => (int) $item['id_ikan'],
                        'berat' => (float) $item['berat'],
                        'harga_per_kg' => (float) $item['harga_per_kg'],
                        'subtotal' => (float) $item['berat'] * (float) $item['harga_per_kg'],
                    ]);

                    $saleItems[] = [
                        'id_item' => (int) $createdItem->id_item,
                        'id_ikan' => (int) $item['id_ikan'],
                        'berat' => (float) $item['berat'],
                    ];
                }

                $pendingShortages = $this->deductAndAllocateFromFishStorages($saleItems, $allowPendingDiscrepancy);

                if ($allowPendingDiscrepancy && $pendingShortages !== []) {
                    $hasPendingDiscrepancy = true;
                    $this->createStockDiscrepancy(
                        (int) $penjualan->id_penjualan,
                        $pendingShortages,
                        (string) ($validated['catatan_selisih'] ?? '')
                    );
                }

                if ($totalPenerimaan > 0) {
                    $namaCustomer = $customer?->nama_customer ?? '-';

                    if ($bayarTunai > 0) {
                        $lastSaldoKas = (float) (DB::table('arus_kas')->where('akun', 'kas')->orderByDesc('id_kas')->value('saldo') ?? 0);
                        DB::table('arus_kas')->insert([
                            'akun' => 'kas',
                            'tanggal' => $today,
                            'jenis_transaksi' => 'Masuk',
                            'kategori' => 'Penjualan Ikan',
                            'deskripsi' => 'Penjualan kepada '.$namaCustomer.'. Diterima kas Rp '.number_format($bayarTunai, 2, ',', '.').'; Piutang Rp '.number_format($piutang, 2, ',', '.'),
                            'uang_masuk' => $bayarTunai,
                            'uang_keluar' => 0,
                            'saldo' => $lastSaldoKas + $bayarTunai,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }

                    if ($bayarTransfer > 0) {
                        $lastSaldoBank = (float) (DB::table('arus_kas')->where('akun', 'bank')->orderByDesc('id_kas')->value('saldo') ?? 0);
                        DB::table('arus_kas')->insert([
                            'akun' => 'bank',
                            'tanggal' => $today,
                            'jenis_transaksi' => 'Masuk',
                            'kategori' => 'Penjualan Ikan',
                            'deskripsi' => 'Penjualan kepada '.$namaCustomer.'. Diterima transfer Rp '.number_format($bayarTransfer, 2, ',', '.').'; Piutang Rp '.number_format($piutang, 2, ',', '.'),
                            'uang_masuk' => $bayarTransfer,
                            'uang_keluar' => 0,
                            'saldo' => $lastSaldoBank + $bayarTransfer,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }

                $this->recalculateStokIkan(now()->format('Y-m'), array_keys($requestedByIkan));
            });
        } catch (Throwable $e) {
            report($e);

            return back()->withInput()->withErrors([
                'message' => 'Transaksi gagal disimpan. Data form tetap dipertahankan, silakan cek lalu coba simpan kembali.',
            ]);
        }

        $successMessage = $hasPendingDiscrepancy
            ? 'Transaksi penjualan berhasil disimpan sebagai selisih sementara dan menunggu rekonsiliasi stok.'
            : 'Transaksi penjualan berhasil disimpan.';

        $userId = Auth::id();
        if ($userId !== null && $customer?->id_customer !== null) {
            PenjualanCartDraft::query()
                ->where('id_user', $userId)
                ->where('id_customer', (int) $customer->id_customer)
                ->delete();
        }

        return redirect()->route('penjualan.index')->with('success', $successMessage);
    }

    public function openKas(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'saldo_awal' => ['required', 'numeric', 'min:0'],
        ]);

        $today = now()->toDateString();
        $exists = KasHarian::query()->whereDate('tanggal', $today)->exists();
        if ($exists) {
            return back()->withErrors(['message' => 'Saldo awal hari ini sudah pernah dibuka.']);
        }

        KasHarian::create([
            'tanggal' => $today,
            'saldo_awal' => (float) $validated['saldo_awal'],
            'total_masuk' => 0,
            'total_keluar' => 0,
            'saldo_akhir' => (float) $validated['saldo_awal'],
            'status_tutup' => false,
        ]);

        return redirect()->route('penjualan.index')->with('success', 'Saldo awal hari ini berhasil dibuka.');
    }

    public function closeKas(): RedirectResponse
    {
        $today = now()->toDateString();
        $kasHarian = KasHarian::query()->whereDate('tanggal', $today)->first();

        if (! $kasHarian) {
            return back()->withErrors(['message' => 'Belum ada pembukaan saldo hari ini.']);
        }

        if ($kasHarian->status_tutup) {
            return back()->withErrors(['message' => 'Kas hari ini sudah ditutup sebelumnya.']);
        }

        $kasHarian->update([
            'status_tutup' => true,
            'waktu_tutup' => now(),
        ]);

        return redirect()->route('penjualan.index')->with('success', 'Kas hari ini berhasil ditutup.');
    }

    public function report(Request $request): View
    {
        $today = now()->toDateString();
        $startDate = $request->input('start_date', $today);
        $endDate = $request->input('end_date', $today);

        // Item-level rows so berat/harga per item is accurate for multi-item transactions
        $sales = DB::table('penjualan_items as pi')
            ->join('penjualan as p', 'p.id_penjualan', '=', 'pi.id_penjualan')
            ->leftJoin('master_ikan as mi', 'mi.id_ikan', '=', 'pi.id_ikan')
            ->leftJoin('master_customer as mc', 'mc.id_customer', '=', 'p.id_customer')
            ->whereBetween('p.tanggal_penjualan', [$startDate, $endDate])
            ->orderByDesc('p.tanggal_penjualan')
            ->select(
                'p.id_penjualan',
                'p.tanggal_penjualan',
                'p.pembeli',
                'pi.berat',
                'pi.harga_per_kg',
                'pi.subtotal as total_harga',
                'mi.nama_ikan',
                DB::raw('COALESCE(mc.nama_customer, p.pembeli) as nama_customer_display')
            )
            ->get();

        $summary = [
            'total_transaksi' => $sales->pluck('id_penjualan')->unique()->count(),
            'total_berat' => (float) $sales->sum('berat'),
            'total_pendapatan' => (float) $sales->sum('total_harga'),
        ];

        $groupByIkan = $sales
            ->groupBy('nama_ikan')
            ->map(function ($rows, $namaIkan) {
                return [
                    'nama_ikan' => $namaIkan,
                    'jumlah_transaksi' => $rows->pluck('id_penjualan')->unique()->count(),
                    'total_berat' => (float) $rows->sum('berat'),
                    'total_pendapatan' => (float) $rows->sum('total_harga'),
                ];
            })->values();

        return view('penjualan.report', compact('sales', 'summary', 'startDate', 'endDate', 'groupByIkan'));
    }

    public function riwayat(Request $request): View
    {
        $validated = $request->validate([
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
        ]);

        $today = now()->toDateString();
        $defaultStart = now()->subDays(6)->toDateString();

        $startDate = $validated['start_date'] ?? $defaultStart;
        $endDate = $validated['end_date'] ?? $today;

        $startCarbon = Carbon::parse($startDate);
        $endCarbon = Carbon::parse($endDate);

        if ($startCarbon->gt($endCarbon)) {
            [$startCarbon, $endCarbon] = [$endCarbon, $startCarbon];
        }

        $startDate = $startCarbon->toDateString();
        $endDate = $endCarbon->toDateString();

        $sales = Penjualan::query()
            ->leftJoin('master_customer as mc', 'mc.id_customer', '=', 'penjualan.id_customer')
            ->whereBetween('tanggal_penjualan', [$startDate, $endDate])
            ->orderByDesc('tanggal_penjualan')
            ->orderByDesc('penjualan.created_at')
            ->select(
                'penjualan.*',
                DB::raw("EXISTS(SELECT 1 FROM penjualan_selisih_stok pss WHERE pss.id_penjualan = penjualan.id_penjualan AND pss.status = 'pending') as has_pending_discrepancy"),
                DB::raw('COALESCE(mc.nama_customer, penjualan.pembeli) as nama_customer_display')
            )
            ->get()
            ->load(['items.ikan']);

        $totalBerat = $sales->flatMap->items->sum('berat');

        $summary = [
            'total_transaksi' => $sales->count(),
            'total_berat' => (float) $totalBerat,
            'total_pendapatan' => (float) $sales->sum('total_harga'),
            'total_piutang' => (float) $sales->sum('piutang'),
        ];

        return view('penjualan.riwayat', compact('sales', 'summary', 'startDate', 'endDate'));
    }

    public function discrepancies(): View
    {
        $pendingDiscrepancies = DB::table('penjualan_selisih_stok as pss')
            ->join('penjualan as p', 'p.id_penjualan', '=', 'pss.id_penjualan')
            ->leftJoin('master_customer as mc', 'mc.id_customer', '=', 'p.id_customer')
            ->where('pss.status', 'pending')
            ->orderByDesc('pss.created_at')
            ->select(
                'pss.id_penjualan_selisih',
                'pss.id_penjualan',
                'pss.catatan_kasir',
                'pss.created_at as waktu_selisih',
                'p.tanggal_penjualan',
                'p.total_harga',
                'p.created_at as waktu_penjualan',
                DB::raw('COALESCE(mc.nama_customer, p.pembeli) as nama_customer_display')
            )
            ->get();

        $shortageItems = DB::table('penjualan_selisih_stok_items as pssi')
            ->join('master_ikan as mi', 'mi.id_ikan', '=', 'pssi.id_ikan')
            ->whereIn('pssi.id_penjualan_selisih', $pendingDiscrepancies->pluck('id_penjualan_selisih')->all())
            ->orderBy('mi.nama_ikan')
            ->get([
                'pssi.id_penjualan_selisih',
                'pssi.stok_tersedia',
                'pssi.berat_diminta',
                'pssi.berat_selisih',
                'mi.nama_ikan',
            ])
            ->groupBy('id_penjualan_selisih');

        $recentAdjustments = DB::table('penyesuaian_stok_ikan as psi')
            ->leftJoin('penjualan_selisih_stok as pss', 'pss.id_penjualan_selisih', '=', 'psi.id_penjualan_selisih')
            ->leftJoin('penjualan as p', 'p.id_penjualan', '=', 'pss.id_penjualan')
            ->leftJoin('master_customer as mc', 'mc.id_customer', '=', 'p.id_customer')
            ->orderByDesc('psi.created_at')
            ->limit(10)
            ->select(
                'psi.id_penyesuaian_stok',
                'psi.tipe_sumber',
                'psi.catatan',
                'psi.created_at',
                'pss.id_penjualan_selisih',
                'p.id_penjualan',
                DB::raw('COALESCE(mc.nama_customer, p.pembeli) as nama_customer_display')
            )
            ->get();

        $storages = $this->getFishStorages();
        $fishOptions = DB::table('master_ikan')->orderBy('nama_ikan')->get(['id_ikan', 'nama_ikan']);

        return view('penjualan.selisih.index', compact('pendingDiscrepancies', 'shortageItems', 'recentAdjustments', 'storages', 'fishOptions'));
    }

    public function showDiscrepancy(int $id): View
    {
        $discrepancy = DB::table('penjualan_selisih_stok as pss')
            ->join('penjualan as p', 'p.id_penjualan', '=', 'pss.id_penjualan')
            ->leftJoin('master_customer as mc', 'mc.id_customer', '=', 'p.id_customer')
            ->where('pss.id_penjualan_selisih', $id)
            ->select(
                'pss.*',
                'p.id_penjualan',
                'p.tanggal_penjualan',
                'p.total_harga',
                'p.created_at as waktu_penjualan',
                DB::raw('COALESCE(mc.nama_customer, p.pembeli) as nama_customer_display')
            )
            ->firstOrFail();

        $sale = $this->findTrxWithItems((int) $discrepancy->id_penjualan);

        $shortageItems = DB::table('penjualan_selisih_stok_items as pssi')
            ->join('master_ikan as mi', 'mi.id_ikan', '=', 'pssi.id_ikan')
            ->where('pssi.id_penjualan_selisih', $id)
            ->orderBy('mi.nama_ikan')
            ->get([
                'pssi.id_ikan',
                'pssi.stok_tersedia',
                'pssi.berat_diminta',
                'pssi.berat_selisih',
                'mi.nama_ikan',
            ]);

        $storages = $this->getFishStorages();
        $fishOptions = DB::table('master_ikan')->orderBy('nama_ikan')->get(['id_ikan', 'nama_ikan']);

        return view('penjualan.selisih.show', compact('discrepancy', 'sale', 'shortageItems', 'storages', 'fishOptions'));
    }

    public function resolveDiscrepancy(Request $request, int $id): RedirectResponse
    {
        $validated = $request->validate([
            'catatan_admin' => ['nullable', 'string', 'max:255'],
            'adjustments' => ['required', 'array', 'min:1'],
            'adjustments.*.id_storage' => ['required', 'integer', 'exists:storage_ikan,id_storage'],
            'adjustments.*.id_ikan' => ['required', 'integer', 'exists:master_ikan,id_ikan'],
            'adjustments.*.delta_berat' => ['required', 'numeric', 'not_in:0'],
            'adjustments.*.keterangan' => ['nullable', 'string', 'max:255'],
        ]);

        DB::transaction(function () use ($id, $validated) {
            $discrepancy = DB::table('penjualan_selisih_stok')
                ->where('id_penjualan_selisih', $id)
                ->lockForUpdate()
                ->first();

            if (! $discrepancy) {
                throw ValidationException::withMessages([
                    'message' => 'Data selisih stok tidak ditemukan.',
                ]);
            }

            if ($discrepancy->status !== 'pending') {
                throw ValidationException::withMessages([
                    'message' => 'Selisih stok ini sudah direkonsiliasi sebelumnya.',
                ]);
            }

            $idPenyesuaian = DB::table('penyesuaian_stok_ikan')->insertGetId([
                'id_penjualan_selisih' => $id,
                'tipe_sumber' => 'rekonsiliasi_penjualan',
                'catatan' => trim((string) ($validated['catatan_admin'] ?? '')) ?: null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $affectedIkanIds = $this->applyStockAdjustmentLines($idPenyesuaian, $validated['adjustments']);

            DB::table('penjualan_selisih_stok')
                ->where('id_penjualan_selisih', $id)
                ->update([
                    'status' => 'resolved',
                    'catatan_admin' => trim((string) ($validated['catatan_admin'] ?? '')) ?: null,
                    'resolved_at' => now(),
                    'updated_at' => now(),
                ]);

            $this->recalculateStokIkan(now()->format('Y-m'), $affectedIkanIds);
        });

        return redirect()
            ->route('penjualan.selisih.index')
            ->with('success', 'Selisih stok berhasil direkonsiliasi.');
    }

    public function storeManualAdjustment(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'catatan' => ['nullable', 'string', 'max:255'],
            'adjustments' => ['required', 'array', 'min:1'],
            'adjustments.*.id_storage' => ['required', 'integer', 'exists:storage_ikan,id_storage'],
            'adjustments.*.id_ikan' => ['required', 'integer', 'exists:master_ikan,id_ikan'],
            'adjustments.*.delta_berat' => ['required', 'numeric', 'not_in:0'],
            'adjustments.*.keterangan' => ['nullable', 'string', 'max:255'],
        ]);

        DB::transaction(function () use ($validated) {
            $idPenyesuaian = DB::table('penyesuaian_stok_ikan')->insertGetId([
                'id_penjualan_selisih' => null,
                'tipe_sumber' => 'manual',
                'catatan' => trim((string) ($validated['catatan'] ?? '')) ?: null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $affectedIkanIds = $this->applyStockAdjustmentLines($idPenyesuaian, $validated['adjustments']);

            $this->recalculateStokIkan(now()->format('Y-m'), $affectedIkanIds);
        });

        return redirect()
            ->route('penjualan.selisih.index')
            ->with('success', 'Penyesuaian stok manual berhasil disimpan.');
    }

    public function downloadInvoice(int $id): Response
    {
        $trx = $this->findTrxWithItems($id);
        $appSettings = \App\Http\Controllers\PengaturanController::getAll();

        $pdf = Pdf::loadView('penjualan.invoice', ['trx' => $trx, 'appSettings' => $appSettings])
            ->setPaper('a5', 'portrait');

        $filename = 'invoice-'.$trx->id_penjualan.'-'.$trx->tanggal_penjualan.'.pdf';

        return $pdf->download($filename);
    }

    public function previewInvoice(int $id): View
    {
        $trx = $this->findTrxWithItems($id);
        $appSettings = \App\Http\Controllers\PengaturanController::getAll();

        return view('penjualan.invoice', ['trx' => $trx, 'appSettings' => $appSettings]);
    }

    private function findTrxWithItems(int $id): Penjualan
    {
        return Penjualan::query()
            ->leftJoin('master_customer as mc', 'mc.id_customer', '=', 'penjualan.id_customer')
            ->where('penjualan.id_penjualan', $id)
            ->select(
                'penjualan.*',
                DB::raw('COALESCE(mc.nama_customer, penjualan.pembeli) as nama_customer_display')
            )
            ->with('items.ikan')
            ->firstOrFail();
    }

    public function keuanganPenjualanSummary(Request $request): View
    {
        $validated = $request->validate([
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
        ]);

        $today = now()->toDateString();
        $hasCustomRange = ! empty($validated['start_date']) || ! empty($validated['end_date']);

        $startDate = $validated['start_date'] ?? $today;
        $endDate = $validated['end_date'] ?? $today;

        $startCarbon = Carbon::parse($startDate);
        $endCarbon = Carbon::parse($endDate);

        if ($startCarbon->gt($endCarbon)) {
            [$startCarbon, $endCarbon] = [$endCarbon, $startCarbon];
        }

        $startDate = $startCarbon->toDateString();
        $endDate = $endCarbon->toDateString();

        $filteredSales = Penjualan::query()
            ->whereBetween('tanggal_penjualan', [$startDate, $endDate])
            ->get();

        $filteredBeratTotal = (float) DB::table('penjualan_items as pi')
            ->join('penjualan as p', 'p.id_penjualan', '=', 'pi.id_penjualan')
            ->whereBetween('p.tanggal_penjualan', [$startDate, $endDate])
            ->sum('pi.berat');

        $filteredSummary = [
            'total_transaksi' => $filteredSales->count(),
            'total_berat' => $filteredBeratTotal,
            'total_pendapatan' => (float) $filteredSales->sum('total_harga'),
        ];

        $todaySales = Penjualan::query()
            ->whereDate('tanggal_penjualan', $today)
            ->get();

        $todayBeratTotal = (float) DB::table('penjualan_items as pi')
            ->join('penjualan as p', 'p.id_penjualan', '=', 'pi.id_penjualan')
            ->whereDate('p.tanggal_penjualan', $today)
            ->sum('pi.berat');

        $summaryToday = [
            'total_transaksi' => $todaySales->count(),
            'total_berat' => $todayBeratTotal,
            'total_pendapatan' => (float) $todaySales->sum('total_harga'),
        ];

        $chartStart = $hasCustomRange
            ? $startCarbon->copy()
            : Carbon::today()->subDays(29);
        $chartEnd = $hasCustomRange
            ? $endCarbon->copy()
            : Carbon::today();

        $trendRows = Penjualan::query()
            ->whereBetween('tanggal_penjualan', [$chartStart->toDateString(), $chartEnd->toDateString()])
            ->selectRaw('DATE(tanggal_penjualan) as tanggal, COUNT(*) as total_transaksi, SUM(total_harga) as total_pendapatan')
            ->groupBy('tanggal')
            ->orderBy('tanggal')
            ->get()
            ->keyBy('tanggal');

        $chartLabels = [];
        $chartValues = [];

        foreach (CarbonPeriod::create($chartStart, $chartEnd) as $date) {
            $key = $date->toDateString();
            $chartLabels[] = $date->format('d M');
            $chartValues[] = (float) ($trendRows[$key]->total_pendapatan ?? 0);
        }

        $chartMeta = [
            'start' => $chartStart->toDateString(),
            'end' => $chartEnd->toDateString(),
            'custom' => $hasCustomRange,
        ];

        return view('keuangan.penjualan.index', compact(
            'today',
            'startDate',
            'endDate',
            'filteredSummary',
            'summaryToday',
            'chartLabels',
            'chartValues',
            'chartMeta'
        ));
    }

    private function resolveCustomer(array $validated): array
    {
        $createNew = (bool) ($validated['create_new_customer'] ?? false);

        if ($createNew) {
            $nama = trim((string) ($validated['nama_customer_baru'] ?? ''));
            if ($nama === '') {
                return [null, 'Nama customer baru wajib diisi.'];
            }

            return [MasterCustomer::create([
                'nama_customer' => $nama,
                'alamat' => $validated['alamat_customer_baru'] ?? null,
                'telepon' => $validated['telepon_customer_baru'] ?? null,
            ]), null];
        }

        if (! empty($validated['id_customer'])) {
            return [MasterCustomer::query()->find((int) $validated['id_customer']), null];
        }

        return [null, 'Pilih customer atau buat customer baru terlebih dahulu.'];
    }

    private function getIkanStockMap()
    {
        $masterIkan = DB::table('master_ikan')
            ->orderBy('nama_ikan')
            ->get(['id_ikan', 'nama_ikan', 'id_ikan_tangkapan']);

        $storageStocks = DB::table('stok_ikan_storage as sis')
            ->join('master_ikan as mi', 'mi.id_ikan', '=', 'sis.id_ikan')
            ->selectRaw('sis.id_ikan, mi.id_ikan_tangkapan, SUM(sis.stok_aktual) as stok_aktual')
            ->groupBy('sis.id_ikan', 'mi.id_ikan_tangkapan')
            ->get();

        $stockByRelation = $storageStocks
            ->filter(fn ($row) => ! empty($row->id_ikan_tangkapan))
            ->groupBy('id_ikan_tangkapan')
            ->map(fn ($rows) => (float) $rows->sum('stok_aktual'));

        $stockByIkan = $storageStocks
            ->keyBy(fn ($row) => (int) $row->id_ikan)
            ->map(fn ($row) => (float) $row->stok_aktual);

        return $masterIkan->map(function ($ikan) use ($stockByRelation, $stockByIkan) {
            $stokTersedia = $ikan->id_ikan_tangkapan
                ? (float) ($stockByRelation[(int) $ikan->id_ikan_tangkapan] ?? 0)
                : (float) ($stockByIkan[(int) $ikan->id_ikan] ?? 0);

            return (object) [
                'id_ikan' => $ikan->id_ikan,
                'nama_ikan' => $ikan->nama_ikan,
                'stok_tersedia' => max(0, $stokTersedia),
            ];
        });
    }

    private function getAvailableStockByIkan(int $idIkan): float
    {
        $idIkanTangkapan = DB::table('master_ikan')
            ->where('id_ikan', $idIkan)
            ->value('id_ikan_tangkapan');

        $query = DB::table('stok_ikan_storage as sis');

        if ($idIkanTangkapan) {
            $query
                ->join('master_ikan as mi', 'mi.id_ikan', '=', 'sis.id_ikan')
                ->where('mi.id_ikan_tangkapan', (int) $idIkanTangkapan);
        } else {
            $query->where('sis.id_ikan', $idIkan);
        }

        return max(0, (float) $query->sum('sis.stok_aktual'));
    }

    private function deductAndAllocateFromFishStorages(array $saleItems, bool $allowPendingDiscrepancy = false): array
    {
        $pendingShortages = [];
        $allocationRows = [];
        $now = now();

        foreach ($saleItems as $saleItem) {
            $idIkan = (int) $saleItem['id_ikan'];
            $requestedBerat = (float) $saleItem['berat'];
            $remaining = $requestedBerat;
            $stockRows = $this->getStorageRowsForIkan((int) $idIkan);

            foreach ($stockRows as $stockRow) {
                if ($remaining <= 0) {
                    break;
                }

                $stokSaatIni = (float) $stockRow->stok_aktual;
                if ($stokSaatIni <= 0) {
                    continue;
                }

                $stokTerpakai = min($remaining, $stokSaatIni);
                $remaining -= $stokTerpakai;

                DB::table('stok_ikan_storage')
                    ->where('id_stok_storage', (int) $stockRow->id_stok_storage)
                    ->update([
                        'stok_aktual' => max(0, $stokSaatIni - $stokTerpakai),
                        'updated_at' => $now,
                    ]);

                $allocationRows[] = [
                    'id_item' => (int) $saleItem['id_item'],
                    'id_storage' => (int) $stockRow->id_storage,
                    'id_ikan' => (int) $stockRow->id_ikan,
                    'berat_alokasi' => (float) $stokTerpakai,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                $this->allocateFromTripLots(
                    idItem: (int) $saleItem['id_item'],
                    idStorage: (int) $stockRow->id_storage,
                    idIkan: (int) $stockRow->id_ikan,
                    beratAlokasi: (float) $stokTerpakai,
                    nowTs: $now
                );
            }

            if ($remaining > 0.00001) {
                if ($allowPendingDiscrepancy) {
                    if (! isset($pendingShortages[$idIkan])) {
                        $pendingShortages[$idIkan] = [
                            'stok_tersedia' => 0.0,
                            'berat_diminta' => 0.0,
                            'berat_selisih' => 0.0,
                        ];
                    }

                    $pendingShortages[$idIkan]['stok_tersedia'] += max(0, (float) $requestedBerat - $remaining);
                    $pendingShortages[$idIkan]['berat_diminta'] += (float) $requestedBerat;
                    $pendingShortages[$idIkan]['berat_selisih'] += (float) $remaining;

                    continue;
                }

                $namaIkan = DB::table('master_ikan')->where('id_ikan', (int) $idIkan)->value('nama_ikan');

                throw ValidationException::withMessages([
                    'items' => 'Stok storage untuk '.$namaIkan.' tidak mencukupi.',
                ]);
            }
        }

        if ($allocationRows !== []) {
            DB::table('penjualan_item_storage_allocations')->insert($allocationRows);
        }

        return $pendingShortages;
    }

    private function allocateFromTripLots(int $idItem, int $idStorage, int $idIkan, float $beratAlokasi, $nowTs): void
    {
        if ($beratAlokasi <= 0) {
            return;
        }

        $this->ensureLegacyOpeningLotCoverage($idStorage, $idIkan, $nowTs);

        $remaining = $beratAlokasi;
        $lotRows = DB::table('stok_ikan_lots')
            ->where('id_storage', $idStorage)
            ->where('id_ikan', $idIkan)
            ->where('berat_sisa', '>', 0)
            ->orderBy('tanggal_lot')
            ->orderBy('id_stok_ikan_lot')
            ->lockForUpdate()
            ->get([
                'id_stok_ikan_lot',
                'id_pelayaran',
                'berat_sisa',
                'harga_per_kg',
            ]);

        foreach ($lotRows as $lotRow) {
            if ($remaining <= 0) {
                break;
            }

            $lotSisa = (float) $lotRow->berat_sisa;
            if ($lotSisa <= 0) {
                continue;
            }

            $consumed = min($remaining, $lotSisa);
            $remaining -= $consumed;

            DB::table('stok_ikan_lots')
                ->where('id_stok_ikan_lot', (int) $lotRow->id_stok_ikan_lot)
                ->update([
                    'berat_sisa' => max(0, $lotSisa - $consumed),
                    'updated_at' => $nowTs,
                ]);

            DB::table('penjualan_item_lot_allocations')->insert([
                'id_item' => $idItem,
                'id_stok_ikan_lot' => (int) $lotRow->id_stok_ikan_lot,
                'id_storage' => $idStorage,
                'id_ikan' => $idIkan,
                'id_pelayaran' => $lotRow->id_pelayaran ? (int) $lotRow->id_pelayaran : null,
                'berat_alokasi' => $consumed,
                'harga_per_kg_lot' => (float) ($lotRow->harga_per_kg ?? 0),
                'created_at' => $nowTs,
                'updated_at' => $nowTs,
            ]);
        }

        if ($remaining > 0.00001) {
            $namaIkan = DB::table('master_ikan')->where('id_ikan', $idIkan)->value('nama_ikan');

            throw ValidationException::withMessages([
                'items' => 'Lot stok tidak mencukupi untuk alokasi penjualan '.$namaIkan.'.',
            ]);
        }
    }

    private function ensureLegacyOpeningLotCoverage(int $idStorage, int $idIkan, $nowTs): void
    {
        $stokAktual = (float) (DB::table('stok_ikan_storage')
            ->where('id_storage', $idStorage)
            ->where('id_ikan', $idIkan)
            ->lockForUpdate()
            ->value('stok_aktual') ?? 0);

        if ($stokAktual <= 0) {
            return;
        }

        $lotCoverage = (float) (DB::table('stok_ikan_lots')
            ->where('id_storage', $idStorage)
            ->where('id_ikan', $idIkan)
            ->lockForUpdate()
            ->sum('berat_sisa') ?? 0);

        if ($lotCoverage + 0.00001 >= $stokAktual) {
            return;
        }

        $delta = $stokAktual - $lotCoverage;
        DB::table('stok_ikan_lots')->insert([
            'id_storage' => $idStorage,
            'id_ikan' => $idIkan,
            'id_pelayaran' => null,
            'source_type' => 'legacy_opening',
            'tanggal_lot' => '2000-01-01',
            'berat_awal' => $delta,
            'berat_sisa' => $delta,
            'harga_per_kg' => 0,
            'created_at' => $nowTs,
            'updated_at' => $nowTs,
        ]);
    }

    private function createStockDiscrepancy(int $idPenjualan, array $pendingShortages, string $catatanKasir = ''): void
    {
        $catatanKasir = trim($catatanKasir);

        $idDiscrepancy = DB::table('penjualan_selisih_stok')->insertGetId([
            'id_penjualan' => $idPenjualan,
            'status' => 'pending',
            'catatan_kasir' => $catatanKasir !== '' ? $catatanKasir : null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $rows = collect($pendingShortages)
            ->map(function (array $row, int $idIkan) use ($idDiscrepancy) {
                return [
                    'id_penjualan_selisih' => $idDiscrepancy,
                    'id_ikan' => $idIkan,
                    'stok_tersedia' => (float) ($row['stok_tersedia'] ?? 0),
                    'berat_diminta' => (float) ($row['berat_diminta'] ?? 0),
                    'berat_selisih' => (float) ($row['berat_selisih'] ?? 0),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            })
            ->values()
            ->all();

        if ($rows !== []) {
            DB::table('penjualan_selisih_stok_items')->insert($rows);
        }
    }

    private function applyStockAdjustmentLines(int $idPenyesuaian, array $adjustments): array
    {
        $now = now();
        $rows = [];
        $affectedIkanIds = [];

        foreach ($adjustments as $line) {
            $idStorage = (int) $line['id_storage'];
            $idIkan = (int) $line['id_ikan'];
            $deltaBerat = (float) $line['delta_berat'];
            $keterangan = trim((string) ($line['keterangan'] ?? ''));

            $stockRow = DB::table('stok_ikan_storage')
                ->where('id_storage', $idStorage)
                ->where('id_ikan', $idIkan)
                ->lockForUpdate()
                ->first();

            $stokSaatIni = (float) ($stockRow->stok_aktual ?? 0);
            $stokBaru = $stokSaatIni + $deltaBerat;

            if ($stokBaru < -0.00001) {
                $namaIkan = DB::table('master_ikan')->where('id_ikan', $idIkan)->value('nama_ikan');

                throw ValidationException::withMessages([
                    'adjustments' => 'Penyesuaian membuat stok '.$namaIkan.' di storage terpilih menjadi negatif.',
                ]);
            }

            if ($stockRow) {
                DB::table('stok_ikan_storage')
                    ->where('id_stok_storage', (int) $stockRow->id_stok_storage)
                    ->update([
                        'stok_aktual' => max(0, $stokBaru),
                        'updated_at' => $now,
                    ]);
            } else {
                if ($deltaBerat < 0) {
                    $namaIkan = DB::table('master_ikan')->where('id_ikan', $idIkan)->value('nama_ikan');

                    throw ValidationException::withMessages([
                        'adjustments' => 'Tidak bisa mengurangi stok '.$namaIkan.' pada storage yang belum memiliki saldo.',
                    ]);
                }

                DB::table('stok_ikan_storage')->insert([
                    'id_storage' => $idStorage,
                    'id_ikan' => $idIkan,
                    'stok_aktual' => $deltaBerat,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            $rows[] = [
                'id_penyesuaian_stok' => $idPenyesuaian,
                'id_storage' => $idStorage,
                'id_ikan' => $idIkan,
                'delta_berat' => $deltaBerat,
                'keterangan' => $keterangan !== '' ? $keterangan : null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
            $affectedIkanIds[] = $idIkan;
        }

        if ($rows !== []) {
            DB::table('penyesuaian_stok_ikan_items')->insert($rows);
        }

        return array_values(array_unique($affectedIkanIds));
    }

    private function getFishStorages()
    {
        return DB::table('storage_ikan as si')
            ->join('kapal as k', 'k.id_kapal', '=', 'si.id_kapal')
            ->orderBy('k.nama_kapal')
            ->get([
                'si.id_storage',
                'si.nama_storage',
                'k.nama_kapal',
            ]);
    }

    private function getStorageRowsForIkan(int $idIkan)
    {
        $idIkanTangkapan = DB::table('master_ikan')
            ->where('id_ikan', $idIkan)
            ->value('id_ikan_tangkapan');

        $query = DB::table('stok_ikan_storage as sis')
            ->where('sis.stok_aktual', '>', 0);

        if ($idIkanTangkapan) {
            $query
                ->join('master_ikan as mi', 'mi.id_ikan', '=', 'sis.id_ikan')
                ->where('mi.id_ikan_tangkapan', (int) $idIkanTangkapan);
        } else {
            $query->where('sis.id_ikan', $idIkan);
        }

        return $query
            ->orderBy('sis.id_stok_storage')
            ->lockForUpdate()
            ->get(['sis.id_stok_storage', 'sis.id_storage', 'sis.id_ikan', 'sis.stok_aktual']);
    }

    private function recalculateStokIkan(string $periode, array $affectedIkanIds): void
    {
        $affectedIkanIds = collect($affectedIkanIds)
            ->map(fn ($id) => (int) $id)
            ->filter(fn (int $id) => $id > 0)
            ->unique()
            ->values();

        if ($affectedIkanIds->isEmpty()) {
            return;
        }

        $periodeStart = now()->createFromFormat('Y-m', $periode)->startOfMonth()->toDateString();
        $periodeEnd = now()->createFromFormat('Y-m', $periode)->endOfMonth()->toDateString();

        $seedIkan = DB::table('master_ikan')
            ->whereIn('id_ikan', $affectedIkanIds->all())
            ->get(['id_ikan', 'id_ikan_tangkapan']);

        $relationIds = $seedIkan->pluck('id_ikan_tangkapan')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $targetIkan = DB::table('master_ikan')
            ->when($relationIds->isNotEmpty(), function ($query) use ($relationIds, $affectedIkanIds) {
                $query->whereIn('id_ikan', $affectedIkanIds->all())
                    ->orWhereIn('id_ikan_tangkapan', $relationIds->all());
            }, function ($query) use ($affectedIkanIds) {
                $query->whereIn('id_ikan', $affectedIkanIds->all());
            })
            ->get(['id_ikan', 'id_ikan_tangkapan']);

        $directIkanIds = $targetIkan
            ->filter(fn ($ikan) => empty($ikan->id_ikan_tangkapan))
            ->pluck('id_ikan')
            ->map(fn ($id) => (int) $id)
            ->all();

        $salesByRelation = $relationIds->isEmpty()
            ? collect()
            : DB::table('penjualan_items as pi')
                ->join('penjualan as p', 'p.id_penjualan', '=', 'pi.id_penjualan')
                ->join('master_ikan as mi', 'mi.id_ikan', '=', 'pi.id_ikan')
                ->whereIn('mi.id_ikan_tangkapan', $relationIds->all())
                ->whereBetween('p.tanggal_penjualan', [$periodeStart, $periodeEnd])
                ->groupBy('mi.id_ikan_tangkapan')
                ->selectRaw('mi.id_ikan_tangkapan as group_key, SUM(pi.berat) as total_penjualan')
                ->pluck('total_penjualan', 'group_key');

        $catchByRelation = $relationIds->isEmpty()
            ? collect()
            : DB::table('ikan_hasil_pelayaran as ihp')
                ->join('pelayaran as p', 'p.id_pelayaran', '=', 'ihp.id_pelayaran')
                ->join('master_ikan as mi', 'mi.id_ikan', '=', 'ihp.id_ikan')
                ->whereIn('mi.id_ikan_tangkapan', $relationIds->all())
                ->whereRaw('DATE(COALESCE(p.tanggal_selesai, p.tanggal_tiba)) BETWEEN ? AND ?', [$periodeStart, $periodeEnd])
                ->groupBy('mi.id_ikan_tangkapan')
                ->selectRaw('mi.id_ikan_tangkapan as group_key, SUM(ihp.berat_hasil) as total_tangkapan')
                ->pluck('total_tangkapan', 'group_key');

        $salesByIkan = empty($directIkanIds)
            ? collect()
            : DB::table('penjualan_items as pi')
                ->join('penjualan as p', 'p.id_penjualan', '=', 'pi.id_penjualan')
                ->whereIn('pi.id_ikan', $directIkanIds)
                ->whereBetween('p.tanggal_penjualan', [$periodeStart, $periodeEnd])
                ->groupBy('pi.id_ikan')
                ->selectRaw('pi.id_ikan as ikan_key, SUM(pi.berat) as total_penjualan')
                ->pluck('total_penjualan', 'ikan_key');

        $catchByIkan = empty($directIkanIds)
            ? collect()
            : DB::table('ikan_hasil_pelayaran as ihp')
                ->join('pelayaran as p', 'p.id_pelayaran', '=', 'ihp.id_pelayaran')
                ->whereIn('ihp.id_ikan', $directIkanIds)
                ->whereRaw('DATE(COALESCE(p.tanggal_selesai, p.tanggal_tiba)) BETWEEN ? AND ?', [$periodeStart, $periodeEnd])
                ->groupBy('ihp.id_ikan')
                ->selectRaw('ihp.id_ikan as ikan_key, SUM(ihp.berat_hasil) as total_tangkapan')
                ->pluck('total_tangkapan', 'ikan_key');

        $adjustmentByRelation = $relationIds->isEmpty()
            ? collect()
            : DB::table('penyesuaian_stok_ikan_items as psii')
                ->join('penyesuaian_stok_ikan as psi', 'psi.id_penyesuaian_stok', '=', 'psii.id_penyesuaian_stok')
                ->join('master_ikan as mi', 'mi.id_ikan', '=', 'psii.id_ikan')
                ->whereIn('mi.id_ikan_tangkapan', $relationIds->all())
                ->whereBetween(DB::raw('DATE(psi.created_at)'), [$periodeStart, $periodeEnd])
                ->groupBy('mi.id_ikan_tangkapan')
                ->selectRaw('mi.id_ikan_tangkapan as group_key, SUM(psii.delta_berat) as total_penyesuaian')
                ->pluck('total_penyesuaian', 'group_key');

        $adjustmentByIkan = empty($directIkanIds)
            ? collect()
            : DB::table('penyesuaian_stok_ikan_items as psii')
                ->join('penyesuaian_stok_ikan as psi', 'psi.id_penyesuaian_stok', '=', 'psii.id_penyesuaian_stok')
                ->whereIn('psii.id_ikan', $directIkanIds)
                ->whereBetween(DB::raw('DATE(psi.created_at)'), [$periodeStart, $periodeEnd])
                ->groupBy('psii.id_ikan')
                ->selectRaw('psii.id_ikan as ikan_key, SUM(psii.delta_berat) as total_penyesuaian')
                ->pluck('total_penyesuaian', 'ikan_key');

        $now = now();
        $rows = $targetIkan->map(function ($ikan) use ($adjustmentByIkan, $adjustmentByRelation, $catchByIkan, $catchByRelation, $salesByIkan, $salesByRelation, $periode, $now) {
            $relationId = $ikan->id_ikan_tangkapan ? (int) $ikan->id_ikan_tangkapan : null;
            $totalTangkapan = $relationId
                ? (float) ($catchByRelation[$relationId] ?? 0)
                : (float) ($catchByIkan[$ikan->id_ikan] ?? 0);
            $totalPenjualan = $relationId
                ? (float) ($salesByRelation[$relationId] ?? 0)
                : (float) ($salesByIkan[$ikan->id_ikan] ?? 0);
            $totalPenyesuaian = $relationId
                ? (float) ($adjustmentByRelation[$relationId] ?? 0)
                : (float) ($adjustmentByIkan[$ikan->id_ikan] ?? 0);

            return [
                'id_ikan' => (int) $ikan->id_ikan,
                'periode' => $periode,
                'total_tangkapan' => $totalTangkapan,
                'total_penjualan' => $totalPenjualan,
                'stok_akhir' => $totalTangkapan + $totalPenyesuaian - $totalPenjualan,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        })->values()->all();

        DB::table('stok_ikan')->upsert(
            $rows,
            ['id_ikan', 'periode'],
            ['total_tangkapan', 'total_penjualan', 'stok_akhir', 'updated_at']
        );
    }
}
