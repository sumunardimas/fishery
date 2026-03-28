<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $sales = DB::table('penjualan')
            ->orderBy('id_penjualan')
            ->get(['id_penjualan', 'bayar_tunai', 'bayar_transfer', 'piutang']);

        $cashRows = DB::table('arus_kas')
            ->where('kategori', 'Penjualan Ikan')
            ->where('deskripsi', 'like', 'Transaksi POS penjualan ikan%')
            ->orderBy('id_kas')
            ->get(['id_kas']);

        $limit = min($sales->count(), $cashRows->count());

        for ($index = 0; $index < $limit; $index++) {
            $sale = $sales[$index];
            $cashRow = $cashRows[$index];
            $receipt = (float) ($sale->bayar_tunai ?? 0) + (float) ($sale->bayar_transfer ?? 0);
            $piutang = (float) ($sale->piutang ?? 0);

            DB::table('arus_kas')
                ->where('id_kas', $cashRow->id_kas)
                ->update([
                    'jenis_transaksi' => 'Masuk',
                    'uang_masuk' => $receipt,
                    'uang_keluar' => 0,
                    'deskripsi' => 'Transaksi POS penjualan ikan. Kas/Bank diterima Rp '.number_format($receipt, 2, ',', '.').'; Piutang Rp '.number_format($piutang, 2, ',', '.'),
                    'updated_at' => now(),
                ]);
        }

        $this->recalculateRunningBalance();
    }

    public function down(): void
    {
        $sales = DB::table('penjualan')
            ->orderBy('id_penjualan')
            ->get(['id_penjualan', 'total_harga']);

        $cashRows = DB::table('arus_kas')
            ->where('kategori', 'Penjualan Ikan')
            ->where('deskripsi', 'like', 'Transaksi POS penjualan ikan%')
            ->orderBy('id_kas')
            ->get(['id_kas']);

        $limit = min($sales->count(), $cashRows->count());

        for ($index = 0; $index < $limit; $index++) {
            $sale = $sales[$index];
            $cashRow = $cashRows[$index];

            DB::table('arus_kas')
                ->where('id_kas', $cashRow->id_kas)
                ->update([
                    'jenis_transaksi' => 'Masuk',
                    'uang_masuk' => (float) ($sale->total_harga ?? 0),
                    'uang_keluar' => 0,
                    'deskripsi' => 'Transaksi POS penjualan ikan',
                    'updated_at' => now(),
                ]);
        }

        $this->recalculateRunningBalance();
    }

    private function recalculateRunningBalance(): void
    {
        $balance = 0.0;
        $rows = DB::table('arus_kas')
            ->orderBy('id_kas')
            ->get(['id_kas', 'uang_masuk', 'uang_keluar']);

        foreach ($rows as $row) {
            $balance += (float) $row->uang_masuk;
            $balance -= (float) $row->uang_keluar;

            DB::table('arus_kas')
                ->where('id_kas', $row->id_kas)
                ->update([
                    'saldo' => $balance,
                    'updated_at' => now(),
                ]);
        }
    }
};
