<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PeriodeKegiatan extends Model
{
    use HasFactory;

    protected $fillable = [
        'master_kegiatan_id',
        'nama_periode',
        'tgl_mulai',
        'tgl_selesai',
        'pagu_transport',
        'pagu_honor',
    ];

    protected function casts(): array
    {
        return [
            'tgl_mulai' => 'date',
            'tgl_selesai' => 'date',
            'pagu_transport' => 'decimal:2',
            'pagu_honor' => 'decimal:2',
        ];
    }

    public function masterKegiatan(): BelongsTo
    {
        return $this->belongsTo(MasterKegiatan::class, 'master_kegiatan_id');
    }

    public function subKegiatans(): HasMany
    {
        return $this->hasMany(SubKegiatan::class, 'periode_kegiatan_id')->orderBy('urutan')->orderBy('id');
    }

    public function alokasiTransports(): HasMany
    {
        return $this->hasMany(AlokasiTransport::class, 'periode_kegiatan_id');
    }

    public function totalTerpakaiTransport(): float
    {
        return (float) $this->alokasiTransports()->where('status_spj', '!=', 'Ditolak')->sum('nominal');
    }

    public function sisaPaguTransport(): float
    {
        return max(0, (float) $this->pagu_transport - $this->totalTerpakaiTransport());
    }
}
