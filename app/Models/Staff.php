<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Staff extends Model
{
    use HasFactory;

    // default pluralization for "staff" is "staff"; explicitly set table
    protected $table = 'staffs';

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
