<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MasterIkanTangkapan extends Model
{
    protected $table = 'master_ikan_tangkapan';

    protected $primaryKey = 'id_ikan_tangkapan';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $fillable = [
        'nama_ikan_tangkapan',
    ];
}
