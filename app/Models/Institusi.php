<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Institusi extends Model
{
    protected $fillable = [
        'nama',
        'alamat',
        'telepon',
        'email',
        'website',
    ];

    public function kasir()
    {
        return $this->hasMany(Kasir::class);
    }

    public function staff()
    {
        return $this->hasMany(Staff::class);
    }

    public function panitia()
    {
        return $this->hasMany(Panitia::class);
    }

    public function penguji()
    {
        return $this->hasMany(Penguji::class);
    }
}
