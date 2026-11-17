<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TimKerja extends Model
{
    use HasFactory;

    protected $fillable = [
        'kode',
        'nama',
        'ketua_tim',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'tim_kerja_id');
    }

    public function masterKegiatans(): HasMany
    {
        return $this->hasMany(MasterKegiatan::class, 'tim_kerja_id');
    }
}
