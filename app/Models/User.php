<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, HasRoles, Notifiable;

    protected $fillable = ['username', 'email', 'password'];

    protected $hidden = ['password', 'remember_token'];

    // Eager-load what display_name/profile need
    protected $with = ['roles', 'admin'];

    protected $appends = ['display_name', 'role_name'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // ---- Relations
    public function admin()
    {
        return $this->hasOne(Admin::class, 'user_id');
    }

    public function staff()
    {
        return $this->hasOne(Staff::class, 'user_id');
    }

    public function kasir()
    {
        return $this->hasOne(Kasir::class, 'user_id');
    }

    public function panitia()
    {
        return $this->hasOne(Panitia::class, 'user_id');
    }

    public function penguji()
    {
        return $this->hasOne(Penguji::class, 'user_id');
    }

    // ---- Helpers
    public function getRole()
    {
        return $this->relationLoaded('roles') ? $this->roles->first() : $this->roles()->first();
    }

    public function getProfile()
    {
        return match ($this->getRole()?->name) {
            'admin' => $this->admin,
            'staff' => $this->staff,
            'kasir' => $this->kasir,
            'panitia' => $this->panitia,
            'penguji' => $this->penguji,
            default => null,
        };
    }

    // ---- Accessors
    protected function displayName(): Attribute
    {
        return Attribute::get(function () {
            $profile = $this->getProfile();

            return $profile->name
                ?? ($this->attributes['name'] ?? null);
        });
    }

    protected function roleName(): Attribute
    {
        return Attribute::get(fn () => $this->getRole()?->name);
    }

    // Bring back a SAFE computed 'profile' so existing Blade keeps working
    protected function profile(): Attribute
    {
        // May return null if the user has no role/profile row yet
        return Attribute::get(fn () => $this->getProfile());
    }

    // DO NOT add a 'name()' accessor (it shadows the DB column)
}
