<?php

namespace App\Http\Controllers;

use App\Models\MasterOperasionalKantor;
use App\Models\OperasionalKantor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class OperasionalKantorController extends Controller
{
    public function index(): View
    {
        $items = MasterOperasionalKantor::query()
            ->orderBy('kategori')
            ->orderBy('item')
            ->get();

        return view('operasional-kantor.index', compact('items'));
    }

    public function storeMaster(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'item' => ['required', 'string', 'max:255', 'unique:master_operasional_kantor,item'],
            'kategori' => ['required', Rule::in(['Operasional', 'Gaji', 'Retribusi', 'Transportasi'])],
        ]);

        MasterOperasionalKantor::create($data);

        return redirect()->route('operasional-kantor.index')->with('success', 'Master operasional kantor berhasil ditambahkan.');
    }

    public function updateMaster(Request $request, MasterOperasionalKantor $masterItem): RedirectResponse
    {
        $data = $request->validate([
            'item' => [
                'required',
                'string',
                'max:255',
                Rule::unique('master_operasional_kantor', 'item')->ignore($masterItem->id_master_operasional_kantor, 'id_master_operasional_kantor'),
            ],
            'kategori' => ['required', Rule::in(['Operasional', 'Gaji', 'Retribusi', 'Transportasi'])],
        ]);

        $masterItem->update($data);

        return redirect()->route('operasional-kantor.index')->with('success', 'Master operasional kantor berhasil diperbarui.');
    }

    public function destroyMaster(MasterOperasionalKantor $masterItem): RedirectResponse
    {
        $used = OperasionalKantor::query()
            ->where('id_master_operasional_kantor', $masterItem->id_master_operasional_kantor)
            ->exists();

        if ($used) {
            return redirect()->route('operasional-kantor.index')->withErrors([
                'message' => 'Data tidak bisa dihapus karena sudah digunakan pada transaksi operasional kantor.',
            ]);
        }

        $masterItem->delete();

        return redirect()->route('operasional-kantor.index')->with('success', 'Master operasional kantor berhasil dihapus.');
    }

    public function transaksi(): View
    {
        $masterItems = MasterOperasionalKantor::query()
            ->orderBy('kategori')
            ->orderBy('item')
            ->get();

        return view('operasional-kantor.transaksi', [
            'masterItems' => $masterItems,
            'transaksiOnly' => true,
        ]);
    }

    public function history(Request $request): View
    {
        $validated = $request->validate([
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
            'detail_date' => ['nullable', 'string'],
        ]);

        $today = Carbon::today();

        $start = ! empty($validated['start_date'])
            ? Carbon::parse($validated['start_date'])
            : $today->copy()->subDays(29);

        $end = ! empty($validated['end_date'])
            ? Carbon::parse($validated['end_date'])
            : $today->copy();

        if ($start->gt($end)) {
            [$start, $end] = [$end, $start];
        }

        $startDate = $start->toDateString();
        $endDate = $end->toDateString();

        $masterItems = MasterOperasionalKantor::query()
            ->orderBy('kategori')
            ->orderBy('item')
            ->get();

        $dailySummary = OperasionalKantor::query()
            ->whereBetween('tanggal', [$startDate, $endDate])
            ->selectRaw('DATE(tanggal) as tanggal, COUNT(id_operasional_kantor) as total_item, SUM(COALESCE(total_biaya, jumlah, 0)) as grand_total')
            ->groupBy('tanggal')
            ->orderByDesc('tanggal')
            ->get();

        $summaryGrandTotal = (float) $dailySummary->sum('grand_total');

        $detailDate = null;
        if (! empty($validated['detail_date'])) {
            try {
                $detailDate = Carbon::parse($validated['detail_date'])->toDateString();
            } catch (\Throwable $th) {
                $detailDate = null;
            }
        }

        $detailRows = collect();
        $detailGrandTotal = 0.0;

        if ($detailDate !== null) {
            $detailRows = OperasionalKantor::query()
                ->whereDate('tanggal', $detailDate)
                ->orderByDesc('id_operasional_kantor')
                ->get();

            $detailGrandTotal = (float) $detailRows->sum(function ($row) {
                return (float) ($row->total_biaya ?? $row->jumlah ?? 0);
            });
        }

        return view('operasional-kantor.history', compact(
            'masterItems',
            'startDate',
            'endDate',
            'dailySummary',
            'summaryGrandTotal',
            'detailDate',
            'detailRows',
            'detailGrandTotal'
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'tanggal' => ['required', 'date'],
            'akun_pembayaran' => ['required', 'in:kas,bank'],
            'rows' => ['required', 'array', 'min:1'],
            'rows.*.id_master_operasional_kantor' => ['required', 'integer', 'exists:master_operasional_kantor,id_master_operasional_kantor'],
            'rows.*.harga_satuan' => ['required', 'numeric', 'min:0'],
            'rows.*.qty' => ['required', 'numeric', 'min:0.01'],
            'rows.*.keterangan' => ['nullable', 'string'],
        ]);

        $masterIds = collect($data['rows'])
            ->pluck('id_master_operasional_kantor')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $masterMap = MasterOperasionalKantor::query()
            ->whereIn('id_master_operasional_kantor', $masterIds)
            ->get()
            ->keyBy('id_master_operasional_kantor');

        $tanggal = Carbon::parse($data['tanggal'])->toDateString();
        $operasionalRows = [];
        $arusKasRows = [];
        $grandTotal = 0.0;

        foreach ($data['rows'] as $row) {
            $masterId = (int) $row['id_master_operasional_kantor'];
            $master = $masterMap->get($masterId);

            if (! $master) {
                continue;
            }

            $hargaSatuan = (float) $row['harga_satuan'];
            $qty = (float) $row['qty'];
            $totalBiaya = round($hargaSatuan * $qty, 2);

            if ($totalBiaya <= 0) {
                continue;
            }

            $keterangan = isset($row['keterangan']) && trim((string) $row['keterangan']) !== ''
                ? trim((string) $row['keterangan'])
                : '-';

            $operasionalRows[] = [
                'id_master_operasional_kantor' => $masterId,
                'jenis_biaya' => $master->kategori,
                'kategori' => $master->kategori,
                'item' => $master->item,
                'deskripsi' => $master->item,
                'harga_satuan' => $hargaSatuan,
                'qty' => $qty,
                'jumlah' => $totalBiaya,
                'total_biaya' => $totalBiaya,
                'tanggal' => $tanggal,
                'keterangan' => $keterangan,
                'akun_pembayaran' => $data['akun_pembayaran'],
                'created_at' => now(),
                'updated_at' => now(),
            ];

            $arusKasRows[] = [
                'akun' => $data['akun_pembayaran'],
                'tanggal' => $tanggal,
                'jenis_transaksi' => 'Keluar',
                'kategori' => 'Operasional Kantor - '.$master->kategori,
                'deskripsi' => $master->item.($keterangan !== '-' ? ' | '.$keterangan : ''),
                'uang_masuk' => 0,
                'uang_keluar' => $totalBiaya,
                'saldo' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            $grandTotal += $totalBiaya;
        }

        if ($operasionalRows === []) {
            throw ValidationException::withMessages([
                'rows' => 'Isi minimal satu item dengan total biaya lebih dari 0.',
            ]);
        }

        DB::transaction(function () use ($operasionalRows, $arusKasRows, $data) {
            OperasionalKantor::query()->insert($operasionalRows);

            $lastSaldoKas = (float) (DB::table('arus_kas')->where('akun', $data['akun_pembayaran'])->orderByDesc('id_kas')->value('saldo') ?? 0);

            foreach ($arusKasRows as $row) {
                $lastSaldoKas -= (float) $row['uang_keluar'];
                $row['saldo'] = $lastSaldoKas;
                DB::table('arus_kas')->insert($row);
            }
        });

        return redirect()->route('operasional-kantor.transaksi')->with('success', 'Biaya operasional kantor berhasil disimpan. Grand total: Rp '.number_format($grandTotal, 2, ',', '.'));
    }

    public function destroyTransaction(Request $request, OperasionalKantor $transaction): RedirectResponse
    {
        $validated = $request->validate([
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
            'detail_date' => ['nullable', 'date'],
        ]);

        DB::transaction(function () use ($transaction) {
            $akun = in_array((string) $transaction->akun_pembayaran, ['kas', 'bank'], true)
                ? (string) $transaction->akun_pembayaran
                : 'kas';

            $totalBiaya = (float) ($transaction->total_biaya ?? $transaction->jumlah ?? 0);

            if ($totalBiaya > 0) {
                $this->postArusKas(
                    akun: $akun,
                    tanggal: now()->toDateString(),
                    kategori: 'Pembatalan Operasional Kantor - '.($transaction->kategori ?? $transaction->jenis_biaya ?? '-'),
                    deskripsi: 'Pembatalan transaksi operasional kantor #'.$transaction->id_operasional_kantor,
                    debit: $totalBiaya,
                    kredit: 0
                );
            }

            $transaction->delete();
        });

        $params = array_filter([
            'start_date' => $validated['start_date'] ?? null,
            'end_date' => $validated['end_date'] ?? null,
            'detail_date' => $validated['detail_date'] ?? null,
        ]);

        return redirect()
            ->route('operasional-kantor.history', $params)
            ->with('success', 'Transaksi operasional kantor berhasil dihapus dan saldo akun dikembalikan.');
    }

    private function getLastSaldoByAkun(string $akun): float
    {
        return (float) (DB::table('arus_kas')
            ->where('akun', $akun)
            ->orderByDesc('id_kas')
            ->value('saldo') ?? 0);
    }

    private function postArusKas(string $akun, string $tanggal, string $kategori, string $deskripsi, float $debit, float $kredit): void
    {
        $lastSaldo = $this->getLastSaldoByAkun($akun);
        $saldoBaru = $lastSaldo + $debit - $kredit;

        DB::table('arus_kas')->insert([
            'akun' => $akun,
            'tanggal' => $tanggal,
            'jenis_transaksi' => $debit > 0 ? 'Masuk' : 'Keluar',
            'kategori' => $kategori,
            'deskripsi' => $deskripsi,
            'uang_masuk' => $debit,
            'uang_keluar' => $kredit,
            'saldo' => $saldoBaru,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
