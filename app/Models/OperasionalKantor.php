<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OperasionalKantor extends Model
{
    protected $table = 'operasional_kantor';

    protected $primaryKey = 'id_operasional_kantor';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $fillable = [
        'id_master_operasional_kantor',
        'jenis_biaya',
        'kategori',
        'item',
        'deskripsi',
        'harga_satuan',
        'qty',
        'jumlah',
        'total_biaya',
        'tanggal',
        'keterangan',
    ];

    protected $casts = [
        'harga_satuan' => 'decimal:2',
        'qty' => 'decimal:2',
        'jumlah' => 'decimal:2',
        'total_biaya' => 'decimal:2',
        'tanggal' => 'date',
    ];

    public function master(): BelongsTo
    {
        return $this->belongsTo(MasterOperasionalKantor::class, 'id_master_operasional_kantor', 'id_master_operasional_kantor');
    }
}
