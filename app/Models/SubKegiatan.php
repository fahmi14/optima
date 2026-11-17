<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubKegiatan extends Model
{
    use HasFactory;

    protected $fillable = [
        'periode_kegiatan_id',
        'nama',
        'jenis_tahapan',
        'deadline_tahapan',
        'urutan',
        'estimasi_jam_per_satuan',
        'honor_per_satuan',
    ];

    protected function casts(): array
    {
        return [
            'deadline_tahapan' => 'date',
            'urutan' => 'integer',
            'estimasi_jam_per_satuan' => 'decimal:2',
            'honor_per_satuan' => 'decimal:2',
        ];
    }

    public function periodeKegiatan(): BelongsTo
    {
        return $this->belongsTo(PeriodeKegiatan::class, 'periode_kegiatan_id');
    }

    public function penugasans(): HasMany
    {
        return $this->hasMany(Penugasan::class, 'sub_kegiatan_id');
    }
}
