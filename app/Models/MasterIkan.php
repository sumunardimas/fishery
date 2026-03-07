<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MasterIkan extends Model
{
    protected $table = 'master_ikan';

    protected $primaryKey = 'id_ikan';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $fillable = [
        'nama_ikan',
        'jenis_ikan',
        'harga_default',
        'keterangan',
    ];
}
