<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'role',
        'tipe',
        'nip',
        'id_sobat',
        'jabatan',
        'no_hp',
        'kapasitas_maks',
        'tim_kerja_id',
        'kecamatan_id',
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
            'kapasitas_maks' => 'integer',
        ];
    }

    public function timKerja(): BelongsTo
    {
        return $this->belongsTo(TimKerja::class, 'tim_kerja_id');
    }

    public function kecamatan(): BelongsTo
    {
        return $this->belongsTo(Kecamatan::class, 'kecamatan_id');
    }

    public function penugasans(): HasMany
    {
        return $this->hasMany(Penugasan::class, 'user_id');
    }

    public function alokasiTransports(): HasMany
    {
        return $this->hasMany(AlokasiTransport::class, 'user_id');
    }

    public function roleLabel(): string
    {
        return match ($this->role) {
            'admin' => 'Admin (IPDS)',
            'pimpinan' => 'Pimpinan (Kepala BPS)',
            'katim' => 'Ketua Tim',
            'keuangan' => 'Subbagian Umum / Keuangan',
            'organik' => 'Pegawai Organik',
            'mitra' => 'Mitra Statistik',
            default => ucfirst($this->role),
        };
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function hasAnyRole(array $roles): bool
    {
        return in_array($this->role, $roles, true);
    }
}
