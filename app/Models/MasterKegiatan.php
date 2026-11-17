<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MasterKegiatan extends Model
{
    use HasFactory;

    protected $fillable = [
        'tim_kerja_id',
        'nama',
        'tahun',
        'deskripsi',
    ];

    protected function casts(): array
    {
        return [
            'tahun' => 'integer',
        ];
    }

    public function timKerja(): BelongsTo
    {
        return $this->belongsTo(TimKerja::class, 'tim_kerja_id');
    }

    public function periodes(): HasMany
    {
        return $this->hasMany(PeriodeKegiatan::class, 'master_kegiatan_id');
    }
}
