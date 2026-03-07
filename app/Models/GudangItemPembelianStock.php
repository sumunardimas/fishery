<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GudangItemPembelianStock extends Model
{
    protected $table = 'gudang_item_pembelian_stock';

    protected $primaryKey = 'id_stok_gudang_item';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $fillable = [
        'id_gudang',
        'id_item_pembelian',
        'stok_aktual',
    ];
}
