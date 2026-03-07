<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PembelianTransaction extends Model
{
    protected $table = 'pembelian_transaction';

    protected $primaryKey = 'id_transaction';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $fillable = [
        'tanggal_transaksi',
        'id_item_pembelian',
        'id_gudang',
        'jenis_transaksi',
        'jumlah',
        'harga_satuan',
        'total_harga',
        'sumber_tujuan',
        'keterangan',
    ];

    protected $casts = [
        'tanggal_transaksi' => 'date',
        'jumlah' => 'decimal:2',
        'harga_satuan' => 'decimal:2',
        'total_harga' => 'decimal:2',
    ];
}
