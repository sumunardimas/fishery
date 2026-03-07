<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MasterOperasionalKantor extends Model
{
    protected $table = 'master_operasional_kantor';

    protected $primaryKey = 'id_master_operasional_kantor';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $fillable = [
        'item',
        'kategori',
    ];

    public function operasionalKantor(): HasMany
    {
        return $this->hasMany(OperasionalKantor::class, 'id_master_operasional_kantor', 'id_master_operasional_kantor');
    }
}
