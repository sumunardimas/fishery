<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MasterIkanTangkapan extends Model
{
    protected $table = 'master_ikan_tangkapan';

    protected $primaryKey = 'id_ikan_tangkapan';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $fillable = [
        'nama_ikan_tangkapan',
    ];

    public function masterIkan(): HasMany
    {
        return $this->hasMany(MasterIkan::class, 'id_ikan_tangkapan', 'id_ikan_tangkapan');
    }
}
