<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
        'bayar_tunai',
        'bayar_transfer',
        'piutang',
        'status_pembayaran',
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

    public function items(): HasMany
    {
        return $this->hasMany(PenjualanItem::class, 'id_penjualan', 'id_penjualan');
    }
}
