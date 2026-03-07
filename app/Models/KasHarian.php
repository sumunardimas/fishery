<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KasHarian extends Model
{
    protected $table = 'kas_harian';

    protected $primaryKey = 'id_kas_harian';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $fillable = [
        'tanggal',
        'saldo_awal',
        'total_masuk',
        'total_keluar',
        'saldo_akhir',
        'status_tutup',
        'waktu_tutup',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'status_tutup' => 'boolean',
        'waktu_tutup' => 'datetime',
    ];
}
