<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MasterPerbekalan extends Model
{
    protected $table = 'master_perbekalan';

    protected $primaryKey = 'id_barang';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $fillable = [
        'nama_barang',
        'satuan',
        'limit_minimal',
    ];

    protected $casts = [
        'limit_minimal' => 'decimal:2',
    ];
}
