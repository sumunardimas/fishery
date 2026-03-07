<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Penjualan extends Model
{
    protected $table = 'penjualan';

    protected $primaryKey = 'id_penjualan';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $fillable = [
        'tanggal_penjualan',
        'id_ikan',
        'id_customer',
        'berat',
        'harga_per_kg',
        'total_harga',
        'pembeli',
        'keterangan',
    ];

    protected $casts = [
        'tanggal_penjualan' => 'date',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(MasterCustomer::class, 'id_customer', 'id_customer');
    }
}
