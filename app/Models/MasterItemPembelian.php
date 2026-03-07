<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MasterItemPembelian extends Model
{
    protected $table = 'master_item_pembelian';

    protected $primaryKey = 'id_item_pembelian';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $fillable = [
        'nama_item',
        'kategori',
        'satuan',
        'keterangan',
    ];
}
