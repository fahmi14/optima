<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Kecamatan extends Model
{
    use HasFactory;

    protected $fillable = [
        'kode',
        'nama',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'kecamatan_id');
    }

    public function penugasans(): HasMany
    {
        return $this->hasMany(Penugasan::class, 'kecamatan_id');
    }

    public function alokasiTransports(): HasMany
    {
        return $this->hasMany(AlokasiTransport::class, 'kecamatan_id');
    }
}
