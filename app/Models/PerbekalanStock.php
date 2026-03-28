<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PerbekalanStock extends Model
{
    protected $table = 'perbekalan_stock';

    protected $primaryKey = 'id_stok_perbekalan';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $fillable = [
        'id_barang',
        'stok_aktual',
    ];
}
