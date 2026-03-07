<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MasterCustomer extends Model
{
    protected $table = 'master_customer';

    protected $primaryKey = 'id_customer';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $fillable = [
        'nama_customer',
        'alamat',
        'telepon',
    ];
}
