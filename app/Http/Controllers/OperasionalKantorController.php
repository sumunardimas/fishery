<?php

namespace App\Http\Controllers;

use App\Models\MasterOperasionalKantor;
use App\Models\OperasionalKantor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class OperasionalKantorController extends Controller
{
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
            'detail_date' => ['nullable', 'string'],
        ]);

        $today = Carbon::today();

        $start = ! empty($validated['start_date'])
            ? Carbon::parse($validated['start_date'])
            : $today->copy()->subDays(4);

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

        return view('operasional-kantor.index', compact(
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
                'created_at' => now(),
                'updated_at' => now(),
            ];

            $arusKasRows[] = [
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

        DB::transaction(function () use ($operasionalRows, $arusKasRows) {
            OperasionalKantor::query()->insert($operasionalRows);

            $lastSaldoKas = (float) (DB::table('arus_kas')->orderByDesc('id_kas')->value('saldo') ?? 0);

            foreach ($arusKasRows as $row) {
                $lastSaldoKas -= (float) $row['uang_keluar'];
                $row['saldo'] = $lastSaldoKas;
                DB::table('arus_kas')->insert($row);
            }
        });

        return redirect()->route('operasional-kantor.index')->with('success', 'Biaya operasional kantor berhasil disimpan. Grand total: Rp '.number_format($grandTotal, 2, ',', '.'));
    }
}
