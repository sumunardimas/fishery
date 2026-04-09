<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

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

    protected static function booted(): void
    {
        static::created(function (Kapal $kapal) {
            StorageIkan::query()->updateOrCreate(
                ['id_kapal' => (int) $kapal->id_kapal],
                ['nama_storage' => 'Storage ' . $kapal->nama_kapal]
            );
        });

        static::updated(function (Kapal $kapal) {
            StorageIkan::query()->updateOrCreate(
                ['id_kapal' => (int) $kapal->id_kapal],
                ['nama_storage' => 'Storage ' . $kapal->nama_kapal]
            );
        });
    }

    public function pelayaran(): HasMany
    {
        return $this->hasMany(Pelayaran::class, 'id_kapal', 'id_kapal');
    }

    public function storageIkan(): HasOne
    {
        return $this->hasOne(StorageIkan::class, 'id_kapal', 'id_kapal');
    }
}
