<?php

namespace App\Http\Controllers;

use App\Models\MasterOperasional;
use App\Models\Operasional;
use App\Models\Pelayaran;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class OperasionalController extends Controller
{
    public function index(): View
    {
        $pelayaran = DB::table('pelayaran as p')
            ->join('kapal as k', 'k.id_kapal', '=', 'p.id_kapal')
            ->orderByDesc('p.tanggal_berangkat')
            ->select('p.*', 'k.nama_kapal')
            ->get();

        $masterOperasional = MasterOperasional::query()
            ->orderBy('nama_operasional')
            ->get();

        $rekapSail = DB::table('operasional as o')
            ->join('pelayaran as p', 'p.id_pelayaran', '=', 'o.id_pelayaran')
            ->join('kapal as k', 'k.id_kapal', '=', 'p.id_kapal')
            ->select(
                'p.id_pelayaran',
                'k.nama_kapal',
                'p.tanggal_berangkat',
                'p.tanggal_tiba',
                'p.pelabuhan_asal',
                'p.pelabuhan_tujuan',
                DB::raw('COUNT(o.id_operasional) as total_item_biaya'),
                DB::raw('SUM(o.jumlah) as total_biaya')
            )
            ->groupBy(
                'p.id_pelayaran',
                'k.nama_kapal',
                'p.tanggal_berangkat',
                'p.tanggal_tiba',
                'p.pelabuhan_asal',
                'p.pelabuhan_tujuan'
            )
            ->orderByDesc('p.tanggal_berangkat')
            ->get();

        $detailBiaya = DB::table('operasional as o')
            ->leftJoin('master_operasional as mo', 'mo.id_master_operasional', '=', 'o.id_master_operasional')
            ->join('pelayaran as p', 'p.id_pelayaran', '=', 'o.id_pelayaran')
            ->select(
                'o.id_operasional',
                'o.id_pelayaran',
                'o.tanggal',
                'o.jumlah',
                'o.jenis_biaya',
                'o.deskripsi',
                'mo.nama_operasional'
            )
            ->orderByDesc('o.tanggal')
            ->orderByDesc('o.id_operasional')
            ->get()
            ->groupBy('id_pelayaran');

        return view('operasional.index', compact('pelayaran', 'masterOperasional', 'rekapSail', 'detailBiaya'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'id_pelayaran' => ['required', 'integer', 'exists:pelayaran,id_pelayaran'],
            'tanggal' => ['required', 'array'],
            'tanggal.*' => ['nullable', 'date'],
            'jumlah' => ['required', 'array'],
            'jumlah.*' => ['nullable', 'numeric', 'min:0'],
            'deskripsi' => ['nullable', 'array'],
            'deskripsi.*' => ['nullable', 'string'],
        ]);

        $masterIds = MasterOperasional::query()
            ->pluck('id_master_operasional')
            ->map(fn ($id) => (int) $id)
            ->all();

        $tanggalMap = $data['tanggal'] ?? [];
        $jumlahMap = $data['jumlah'] ?? [];
        $deskripsiMap = $data['deskripsi'] ?? [];

        $records = [];
        $defaultTanggal = now()->toDateString();

        foreach ($masterIds as $masterId) {
            $jumlahRaw = $jumlahMap[$masterId] ?? null;
            $jumlah = $jumlahRaw === null || $jumlahRaw === '' ? 0 : (float) $jumlahRaw;

            if ($jumlah <= 0) {
                continue;
            }

            $master = MasterOperasional::query()->find($masterId);
            if (!$master) {
                continue;
            }

            $tanggal = $tanggalMap[$masterId] ?? null;
            $tanggal = $tanggal ?: $defaultTanggal;

            $deskripsi = $deskripsiMap[$masterId] ?? null;

            $records[] = [
                'id_pelayaran' => (int) $data['id_pelayaran'],
                'id_master_operasional' => $masterId,
                'jenis_biaya' => $master->nama_operasional,
                'deskripsi' => $deskripsi ?: ($master->deskripsi ?? '-'),
                'jumlah' => $jumlah,
                'tanggal' => $tanggal,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if ($records === []) {
            throw ValidationException::withMessages([
                'jumlah' => 'Isi minimal satu biaya operasional dengan jumlah > 0.',
            ]);
        }

        DB::transaction(function () use ($records) {
            Operasional::query()->insert($records);
        });

        return redirect()->route('operasional.index')->with('success', 'Data biaya operasional berhasil disimpan.');
    }
}
