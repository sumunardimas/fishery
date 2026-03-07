<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Operasional extends Model
{
    protected $table = 'operasional';

    protected $primaryKey = 'id_operasional';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $fillable = [
        'id_pelayaran',
        'id_master_operasional',
        'jenis_biaya',
        'deskripsi',
        'jumlah',
        'tanggal',
    ];

    protected $casts = [
        'jumlah' => 'decimal:2',
        'tanggal' => 'date',
    ];
}
