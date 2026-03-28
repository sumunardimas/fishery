<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $rows = DB::table('arus_kas')
            ->where('kategori', 'Penjualan Ikan')
            ->where('deskripsi', 'like', '%Kas/Bank diterima%')
            ->orderBy('id_kas')
            ->get();

        foreach ($rows as $row) {
            $namaCustomer = $this->extractCustomerName((string) $row->deskripsi);
            if ($namaCustomer === null) {
                continue;
            }

            $penjualan = DB::table('penjualan as p')
                ->leftJoin('master_customer as mc', 'p.id_customer', '=', 'mc.id_customer')
                ->whereDate('p.tanggal_penjualan', $row->tanggal)
                ->whereRaw('COALESCE(mc.nama_customer, p.pembeli) = ?', [$namaCustomer])
                ->select([
                    'p.id_penjualan',
                    'p.bayar_tunai',
                    'p.bayar_transfer',
                    'p.piutang',
                    DB::raw('COALESCE(mc.nama_customer, p.pembeli) as nama_customer_display'),
                ])
                ->orderByDesc('p.id_penjualan')
                ->first();

            if (! $penjualan) {
                continue;
            }

            $amount = (float) $row->uang_masuk;
            $tunai = (float) ($penjualan->bayar_tunai ?? 0);
            $transfer = (float) ($penjualan->bayar_transfer ?? 0);
            $total = $tunai + $transfer;
            $piutang = (float) ($penjualan->piutang ?? 0);
            $nama = (string) $penjualan->nama_customer_display;

            // Case 1: old single row actually equals transfer amount -> move to bank.
            if ($transfer > 0 && abs($amount - $transfer) < 0.01) {
                DB::table('arus_kas')->where('id_kas', $row->id_kas)->update([
                    'akun' => 'bank',
                    'deskripsi' => 'Penjualan kepada '.$nama.'. Diterima transfer Rp '.number_format($transfer, 2, ',', '.').'; Piutang Rp '.number_format($piutang, 2, ',', '.'),
                    'updated_at' => now(),
                ]);
                continue;
            }

            // Case 2: old single row equals full receipt and contains both tunai+transfer -> split it.
            if ($tunai > 0 && $transfer > 0 && abs($amount - $total) < 0.01) {
                DB::table('arus_kas')->where('id_kas', $row->id_kas)->update([
                    'akun' => 'kas',
                    'uang_masuk' => $tunai,
                    'deskripsi' => 'Penjualan kepada '.$nama.'. Diterima kas Rp '.number_format($tunai, 2, ',', '.').'; Piutang Rp '.number_format($piutang, 2, ',', '.'),
                    'updated_at' => now(),
                ]);

                DB::table('arus_kas')->insert([
                    'akun' => 'bank',
                    'tanggal' => $row->tanggal,
                    'jenis_transaksi' => 'Masuk',
                    'kategori' => 'Penjualan Ikan',
                    'deskripsi' => 'Penjualan kepada '.$nama.'. Diterima transfer Rp '.number_format($transfer, 2, ',', '.').'; Piutang Rp '.number_format($piutang, 2, ',', '.'),
                    'uang_masuk' => $transfer,
                    'uang_keluar' => 0,
                    'saldo' => 0,
                    'created_at' => $row->created_at,
                    'updated_at' => now(),
                ]);
                continue;
            }

            // Fallback when only one channel exists.
            if ($tunai > 0 && abs($amount - $tunai) < 0.01) {
                DB::table('arus_kas')->where('id_kas', $row->id_kas)->update([
                    'akun' => 'kas',
                    'deskripsi' => 'Penjualan kepada '.$nama.'. Diterima kas Rp '.number_format($tunai, 2, ',', '.').'; Piutang Rp '.number_format($piutang, 2, ',', '.'),
                    'updated_at' => now(),
                ]);
            }
        }

        $this->recomputeSaldoByAkun('kas');
        $this->recomputeSaldoByAkun('bank');
    }

    public function down(): void
    {
        // No-op: this is a corrective backfill for legacy rows.
    }

    private function extractCustomerName(string $deskripsi): ?string
    {
        if (preg_match('/^Penjualan kepada (.+?)\. Kas\/Bank diterima/u', $deskripsi, $matches) !== 1) {
            return null;
        }

        return trim($matches[1]);
    }

    private function recomputeSaldoByAkun(string $akun): void
    {
        $running = 0.0;

        $rows = DB::table('arus_kas')
            ->where('akun', $akun)
            ->orderBy('tanggal')
            ->orderBy('id_kas')
            ->get(['id_kas', 'uang_masuk', 'uang_keluar']);

        foreach ($rows as $row) {
            $running += (float) $row->uang_masuk - (float) $row->uang_keluar;
            DB::table('arus_kas')->where('id_kas', $row->id_kas)->update([
                'saldo' => $running,
            ]);
        }
    }
};
