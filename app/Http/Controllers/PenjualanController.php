<?php

namespace App\Http\Controllers;

use App\Models\KasHarian;
use App\Models\MasterCustomer;
use App\Models\Penjualan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PenjualanController extends Controller
{
    public function index(Request $request): View
    {
        $today = now()->toDateString();

        $ikanStock = $this->getIkanStockMap();
        $customers = MasterCustomer::query()->orderBy('nama_customer')->get();
        $kasHarian = KasHarian::query()->whereDate('tanggal', $today)->first();

        $todaySales = Penjualan::query()
            ->leftJoin('master_ikan as mi', 'mi.id_ikan', '=', 'penjualan.id_ikan')
            ->leftJoin('master_customer as mc', 'mc.id_customer', '=', 'penjualan.id_customer')
            ->whereDate('tanggal_penjualan', $today)
            ->orderByDesc('penjualan.created_at')
            ->select(
                'penjualan.*',
                'mi.nama_ikan',
                DB::raw('COALESCE(mc.nama_customer, penjualan.pembeli) as nama_customer_display')
            )
            ->get();

        $summaryToday = [
            'total_transaksi' => $todaySales->count(),
            'total_berat' => (float) $todaySales->sum('berat'),
            'total_pendapatan' => (float) $todaySales->sum('total_harga'),
        ];

        return view('penjualan.index', compact('ikanStock', 'customers', 'kasHarian', 'todaySales', 'summaryToday', 'today'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'id_ikan' => ['required', 'integer', 'exists:master_ikan,id_ikan'],
            'berat' => ['required', 'numeric', 'gt:0'],
            'harga_per_kg' => ['required', 'numeric', 'gt:0'],
            'keterangan' => ['nullable', 'string'],
            'id_customer' => ['nullable', 'integer', 'exists:master_customer,id_customer'],
            'create_new_customer' => ['nullable', 'boolean'],
            'nama_customer_baru' => ['nullable', 'string', 'max:255'],
            'alamat_customer_baru' => ['nullable', 'string', 'max:255'],
            'telepon_customer_baru' => ['nullable', 'string', 'max:30'],
        ]);

        $today = now()->toDateString();
        $kasHarian = KasHarian::query()->whereDate('tanggal', $today)->first();

        if (!$kasHarian) {
            return back()->withInput()->withErrors([
                'message' => 'Saldo awal hari ini belum dibuka. Silakan isi saldo awal terlebih dahulu.',
            ]);
        }

        if ($kasHarian->status_tutup) {
            return back()->withInput()->withErrors([
                'message' => 'Kas hari ini sudah ditutup. Tidak bisa menambah transaksi.',
            ]);
        }

        [$customer, $customerError] = $this->resolveCustomer($validated);
        if ($customerError !== null) {
            return back()->withInput()->withErrors([
                'message' => $customerError,
            ]);
        }
        $idIkan = (int) $validated['id_ikan'];
        $berat = (float) $validated['berat'];

        $availableStock = $this->getAvailableStockByIkan($idIkan);
        if ($berat > $availableStock) {
            return back()->withInput()->withErrors([
                'berat' => 'Berat penjualan melebihi stok tersisa. Stok saat ini: '.number_format($availableStock, 2).' kg.',
            ]);
        }

        $hargaPerKg = (float) $validated['harga_per_kg'];
        $totalHarga = $berat * $hargaPerKg;

        DB::transaction(function () use ($today, $idIkan, $customer, $berat, $hargaPerKg, $totalHarga, $validated) {
            Penjualan::create([
                'tanggal_penjualan' => $today,
                'id_ikan' => $idIkan,
                'id_customer' => $customer?->id_customer,
                'berat' => $berat,
                'harga_per_kg' => $hargaPerKg,
                'total_harga' => $totalHarga,
                'pembeli' => $customer?->nama_customer ?? '-',
                'keterangan' => $validated['keterangan'] ?? 'Transaksi POS penjualan ikan',
            ]);

            $lastSaldoKas = (float) (DB::table('arus_kas')->orderByDesc('id_kas')->value('saldo') ?? 0);
            DB::table('arus_kas')->insert([
                'tanggal' => $today,
                'jenis_transaksi' => 'Masuk',
                'kategori' => 'Penjualan Ikan',
                'deskripsi' => 'Transaksi POS penjualan ikan',
                'uang_masuk' => $totalHarga,
                'uang_keluar' => 0,
                'saldo' => $lastSaldoKas + $totalHarga,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $kasHarian = KasHarian::query()->whereDate('tanggal', $today)->lockForUpdate()->firstOrFail();
            $kasHarian->total_masuk = (float) $kasHarian->total_masuk + $totalHarga;
            $kasHarian->saldo_akhir = (float) $kasHarian->saldo_awal + (float) $kasHarian->total_masuk - (float) $kasHarian->total_keluar;
            $kasHarian->save();

            $this->recalculateStokIkan(now()->format('Y-m'), [$idIkan]);
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

        if (!$kasHarian) {
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

        $sales = Penjualan::query()
            ->leftJoin('master_ikan as mi', 'mi.id_ikan', '=', 'penjualan.id_ikan')
            ->leftJoin('master_customer as mc', 'mc.id_customer', '=', 'penjualan.id_customer')
            ->whereBetween('tanggal_penjualan', [$startDate, $endDate])
            ->orderByDesc('tanggal_penjualan')
            ->select(
                'penjualan.*',
                'mi.nama_ikan',
                DB::raw('COALESCE(mc.nama_customer, penjualan.pembeli) as nama_customer_display')
            )
            ->get();

        $summary = [
            'total_transaksi' => $sales->count(),
            'total_berat' => (float) $sales->sum('berat'),
            'total_pendapatan' => (float) $sales->sum('total_harga'),
        ];

        $groupByIkan = $sales
            ->groupBy('nama_ikan')
            ->map(function ($rows, $namaIkan) {
                return [
                    'nama_ikan' => $namaIkan,
                    'jumlah_transaksi' => $rows->count(),
                    'total_berat' => (float) $rows->sum('berat'),
                    'total_pendapatan' => (float) $rows->sum('total_harga'),
                ];
            })->values();

        return view('penjualan.report', compact('sales', 'summary', 'startDate', 'endDate', 'groupByIkan'));
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

        if (!empty($validated['id_customer'])) {
            return [MasterCustomer::query()->find((int) $validated['id_customer']), null];
        }

        return [null, 'Pilih customer atau buat customer baru terlebih dahulu.'];
    }

    private function getIkanStockMap()
    {
        $masterIkan = DB::table('master_ikan')->orderBy('nama_ikan')->get();

        $catchByIkan = DB::table('ikan_hasil_pelayaran')
            ->groupBy('id_ikan')
            ->selectRaw('id_ikan, SUM(berat_hasil) as total_tangkapan')
            ->pluck('total_tangkapan', 'id_ikan');

        $salesByIkan = DB::table('penjualan')
            ->groupBy('id_ikan')
            ->selectRaw('id_ikan, SUM(berat) as total_penjualan')
            ->pluck('total_penjualan', 'id_ikan');

        return $masterIkan->map(function ($ikan) use ($catchByIkan, $salesByIkan) {
            $totalTangkapan = (float) ($catchByIkan[$ikan->id_ikan] ?? 0);
            $totalPenjualan = (float) ($salesByIkan[$ikan->id_ikan] ?? 0);

            return (object) [
                'id_ikan' => $ikan->id_ikan,
                'nama_ikan' => $ikan->nama_ikan,
                'jenis_ikan' => $ikan->jenis_ikan,
                'harga_default' => (float) $ikan->harga_default,
                'stok_tersedia' => max(0, $totalTangkapan - $totalPenjualan),
            ];
        });
    }

    private function getAvailableStockByIkan(int $idIkan): float
    {
        $totalTangkapan = (float) (DB::table('ikan_hasil_pelayaran')
            ->where('id_ikan', $idIkan)
            ->sum('berat_hasil'));

        $totalPenjualan = (float) (DB::table('penjualan')
            ->where('id_ikan', $idIkan)
            ->sum('berat'));

        return max(0, $totalTangkapan - $totalPenjualan);
    }

    private function recalculateStokIkan(string $periode, array $affectedIkanIds): void
    {
        if (empty($affectedIkanIds)) {
            return;
        }

        $salesByIkan = DB::table('penjualan')
            ->whereIn('id_ikan', $affectedIkanIds)
            ->whereRaw("DATE_FORMAT(tanggal_penjualan, '%Y-%m') = ?", [$periode])
            ->groupBy('id_ikan')
            ->selectRaw('id_ikan, SUM(berat) as total_penjualan')
            ->pluck('total_penjualan', 'id_ikan');

        $catchByIkan = DB::table('ikan_hasil_pelayaran as ihp')
            ->join('pelayaran as p', 'p.id_pelayaran', '=', 'ihp.id_pelayaran')
            ->whereIn('ihp.id_ikan', $affectedIkanIds)
            ->whereRaw("DATE_FORMAT(COALESCE(p.tanggal_selesai, p.tanggal_tiba), '%Y-%m') = ?", [$periode])
            ->groupBy('ihp.id_ikan')
            ->selectRaw('ihp.id_ikan, SUM(ihp.berat_hasil) as total_tangkapan')
            ->pluck('total_tangkapan', 'id_ikan');

        $now = now();
        $rows = collect($affectedIkanIds)->map(function (int $idIkan) use ($catchByIkan, $salesByIkan, $periode, $now) {
            $totalTangkapan = (float) ($catchByIkan[$idIkan] ?? 0);
            $totalPenjualan = (float) ($salesByIkan[$idIkan] ?? 0);

            return [
                'id_ikan' => $idIkan,
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
