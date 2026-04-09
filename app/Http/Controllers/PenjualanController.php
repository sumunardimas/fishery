<?php

namespace App\Http\Controllers;

use App\Models\KasHarian;
use App\Models\MasterCustomer;
use App\Models\Penjualan;
use App\Models\PenjualanItem;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\CarbonPeriod;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PenjualanController extends Controller
{
    public function index(Request $request): View
    {
        $today = now()->toDateString();

        $ikanStock = $this->getIkanStockMap();
        $customers = MasterCustomer::query()->orderBy('nama_customer')->get();

        return view('penjualan.index', compact('ikanStock', 'customers'));
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

        // Stock check per unique ikan
        foreach ($requestedByIkan as $idIkan => $totalRequested) {
            $available = $this->getAvailableStockByIkan($idIkan);
            if ($totalRequested > $available) {
                $namaIkan = DB::table('master_ikan')->where('id_ikan', $idIkan)->value('nama_ikan');

                return back()->withInput()->withErrors([
                    'items' => "Berat {$namaIkan} melebihi stok tersisa. Stok: ".number_format($available, 2).' kg.',
                ]);
            }
        }

        $totalHarga = collect($validated['items'])->sum(fn ($i) => (float) $i['berat'] * (float) $i['harga_per_kg']);
        $bayarTunai = (float) ($validated['bayar_tunai'] ?? 0);
        $bayarTransfer = (float) ($validated['bayar_transfer'] ?? 0);
        $totalPenerimaan = $bayarTunai + $bayarTransfer;
        $piutang = max(0, $totalHarga - $totalPenerimaan);
        $statusPembayaran = $piutang <= 0 ? 'lunas' : 'piutang';

        DB::transaction(function () use ($today, $customer, $totalHarga, $bayarTunai, $bayarTransfer, $totalPenerimaan, $piutang, $statusPembayaran, $validated, $requestedByIkan) {
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

            foreach ($validated['items'] as $item) {
                PenjualanItem::create([
                    'id_penjualan' => $penjualan->id_penjualan,
                    'id_ikan' => (int) $item['id_ikan'],
                    'berat' => (float) $item['berat'],
                    'harga_per_kg' => (float) $item['harga_per_kg'],
                    'subtotal' => (float) $item['berat'] * (float) $item['harga_per_kg'],
                ]);
            }

            $this->deductFromFishStorages($requestedByIkan);

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

        return redirect()->route('penjualan.index')->with('success', 'Transaksi penjualan berhasil disimpan.');
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
            ->filter(fn ($row) => !empty($row->id_ikan_tangkapan))
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

    private function deductFromFishStorages(array $requestedByIkan): void
    {
        foreach ($requestedByIkan as $idIkan => $requestedBerat) {
            $remaining = (float) $requestedBerat;
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
                        'updated_at' => now(),
                    ]);
            }

            if ($remaining > 0.00001) {
                $namaIkan = DB::table('master_ikan')->where('id_ikan', (int) $idIkan)->value('nama_ikan');

                throw ValidationException::withMessages([
                    'items' => 'Stok storage untuk ' . $namaIkan . ' tidak mencukupi.',
                ]);
            }
        }
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
            ->get(['sis.id_stok_storage', 'sis.stok_aktual']);
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

        $now = now();
        $rows = $targetIkan->map(function ($ikan) use ($catchByIkan, $catchByRelation, $salesByIkan, $salesByRelation, $periode, $now) {
            $relationId = $ikan->id_ikan_tangkapan ? (int) $ikan->id_ikan_tangkapan : null;
            $totalTangkapan = $relationId
                ? (float) ($catchByRelation[$relationId] ?? 0)
                : (float) ($catchByIkan[$ikan->id_ikan] ?? 0);
            $totalPenjualan = $relationId
                ? (float) ($salesByRelation[$relationId] ?? 0)
                : (float) ($salesByIkan[$ikan->id_ikan] ?? 0);

            return [
                'id_ikan' => (int) $ikan->id_ikan,
                'periode' => $periode,
                'total_tangkapan' => $totalTangkapan,
                'total_penjualan' => $totalPenjualan,
                'stok_akhir' => $totalTangkapan - $totalPenjualan,
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
