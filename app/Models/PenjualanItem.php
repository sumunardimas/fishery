<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PenjualanItem extends Model
{
    protected $table = 'penjualan_items';

    protected $primaryKey = 'id_item';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $fillable = [
        'id_penjualan',
        'id_ikan',
        'berat',
        'harga_per_kg',
        'subtotal',
    ];

    public function ikan(): BelongsTo
    {
        return $this->belongsTo(MasterIkan::class, 'id_ikan', 'id_ikan');
    }

    public function penjualan(): BelongsTo
    {
        return $this->belongsTo(Penjualan::class, 'id_penjualan', 'id_penjualan');
    }
}
