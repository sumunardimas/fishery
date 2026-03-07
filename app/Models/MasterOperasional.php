<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MasterOperasional extends Model
{
    protected $table = 'master_operasional';

    protected $primaryKey = 'id_master_operasional';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $fillable = [
        'nama_operasional',
        'deskripsi',
    ];
}
