<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ItemPembelianStock extends Model
{
    protected $table = 'item_pembelian_stock';

    protected $primaryKey = 'id_stok_item_pembelian';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $fillable = [
        'id_item_pembelian',
        'stok_aktual',
    ];
}
