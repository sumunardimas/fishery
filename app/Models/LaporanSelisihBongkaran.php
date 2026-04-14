<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LaporanSelisihBongkaran extends Model
{
    protected $table = 'laporan_selisih_bongkaran';

    protected $primaryKey = 'id_laporan';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $fillable = [
        'id_pelayaran',
        'total_berat_timbangan',
        'total_berat_catatan',
        'total_selisih',
        'tanggal_laporan',
    ];

    protected $casts = [
        'tanggal_laporan' => 'date',
        'total_berat_timbangan' => 'float',
        'total_berat_catatan' => 'float',
        'total_selisih' => 'float',
    ];

    public function pelayaran(): BelongsTo
    {
        return $this->belongsTo(Pelayaran::class, 'id_pelayaran', 'id_pelayaran');
    }
}
