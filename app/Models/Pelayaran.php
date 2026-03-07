<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pelayaran extends Model
{
    protected $table = 'pelayaran';

    protected $primaryKey = 'id_pelayaran';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $fillable = [
        'id_kapal',
        'tanggal_berangkat',
        'tanggal_tiba',
        'pelabuhan_asal',
        'pelabuhan_tujuan',
        'jumlah_trip',
        'keterangan',
        'status_pelayaran',
        'tanggal_selesai',
    ];

    protected $casts = [
        'tanggal_berangkat' => 'date',
        'tanggal_tiba' => 'date',
        'tanggal_selesai' => 'date',
    ];

    public function kapal(): BelongsTo
    {
        return $this->belongsTo(Kapal::class, 'id_kapal', 'id_kapal');
    }
}
