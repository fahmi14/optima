<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'level',
        'tipe',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function organik()
    {
        return $this->hasOne(Organik::class, 'user_id');
    }

    public function mitra()
    {
        return $this->hasOne(Mitra::class, 'user_id');
    }

    public function matrikTugas()
    {
        return $this->hasMany(MatrikTugas::class, 'id_user');
    }
}
