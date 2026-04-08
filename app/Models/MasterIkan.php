<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MasterIkan extends Model
{
    protected $table = 'master_ikan';

    protected $primaryKey = 'id_ikan';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $fillable = [
        'nama_ikan',
        'id_ikan_tangkapan',
    ];

    public function ikanTangkapan(): BelongsTo
    {
        return $this->belongsTo(MasterIkanTangkapan::class, 'id_ikan_tangkapan', 'id_ikan_tangkapan');
    }
}
