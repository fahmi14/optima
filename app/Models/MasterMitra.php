<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MasterMitra extends Model
{
    use HasFactory;

    protected $table = 'master_mitras';

    protected $fillable = [
        'user_id',
        'sobat_id',
        'nama_lengkap',
        'posisi',
        'status_seleksi',
        'alamat_detail',
        'alamat_provinsi',
        'alamat_kab',
        'alamat_kecamatan',
        'alamat_desa',
        'ttl',
        'umur',
        'jenis_kelamin',
        'status_kawin',
        'pendidikan',
        'pekerjaan',
        'deskripsi_pekerjaan_lain',
        'terikat_kldi',
        'nama_kldi',
        'keterangan_kldi',
        'no_telp',
        'email',
    ];

    protected function casts(): array
    {
        return [
            'umur' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function alokasiKepkas(): HasMany
    {
        return $this->hasMany(AlokasiKepka::class, 'id_mitra');
    }
}
