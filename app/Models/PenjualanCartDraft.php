<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PenjualanCartDraft extends Model
{
    protected $table = 'penjualan_cart_drafts';

    protected $primaryKey = 'id_penjualan_cart_draft';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $fillable = [
        'id_user',
        'id_customer',
        'payload',
    ];

    protected $casts = [
        'payload' => 'array',
    ];
}
