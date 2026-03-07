<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Kasir extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'name', 'whatsapp', 'gender', 'institusi_id', 'document'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function institusi()
    {
        return $this->belongsTo(Institusi::class);
    }
}
