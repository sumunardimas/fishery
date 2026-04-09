<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StorageIkan extends Model
{
    protected $table = 'storage_ikan';

    protected $primaryKey = 'id_storage';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $fillable = [
        'id_kapal',
        'nama_storage',
    ];

    public function kapal(): BelongsTo
    {
        return $this->belongsTo(Kapal::class, 'id_kapal', 'id_kapal');
    }
}
