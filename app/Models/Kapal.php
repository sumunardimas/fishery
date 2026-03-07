<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Kapal extends Model
{
    protected $table = 'kapal';

    protected $primaryKey = 'id_kapal';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $fillable = [
        'nama_kapal',
        'tahun_dibangun',
        'gross_tonnage',
        'deadweight_tonnage',
        'panjang_meter',
        'lebar_meter',
    ];
}
