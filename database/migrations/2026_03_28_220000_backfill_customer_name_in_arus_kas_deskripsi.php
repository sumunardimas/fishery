<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Match each Penjualan Ikan arus_kas row to the penjualan row via tanggal
        // and the cash actually received (bayar_tunai + bayar_transfer = uang_masuk).
        // Then rebuild the deskripsi to include the customer name.
        $kasRows = DB::table('arus_kas')
            ->where('kategori', 'Penjualan Ikan')
            ->get();

        foreach ($kasRows as $kas) {
            $penjualan = DB::table('penjualan as p')
                ->leftJoin('master_customer as mc', 'p.id_customer', '=', 'mc.id_customer')
                ->whereDate('p.tanggal_penjualan', $kas->tanggal)
                ->whereRaw(
                    'COALESCE(p.bayar_tunai, 0) + COALESCE(p.bayar_transfer, 0) = ?',
                    [(float) $kas->uang_masuk]
                )
                ->select([
                    'p.piutang',
                    DB::raw('COALESCE(mc.nama_customer, p.pembeli) as nama_customer_display'),
                ])
                ->first();

            if (! $penjualan) {
                continue;
            }

            $deskripsi = 'Penjualan kepada ' . $penjualan->nama_customer_display
                . '. Kas/Bank diterima Rp ' . number_format((float) $kas->uang_masuk, 2, ',', '.')
                . '; Piutang Rp ' . number_format((float) ($penjualan->piutang ?? 0), 2, ',', '.');

            DB::table('arus_kas')
                ->where('id_kas', $kas->id_kas)
                ->update(['deskripsi' => $deskripsi]);
        }
    }

    public function down(): void
    {
        DB::table('arus_kas')
            ->where('kategori', 'Penjualan Ikan')
            ->update(['deskripsi' => 'Transaksi POS penjualan ikan.']);
    }
};
