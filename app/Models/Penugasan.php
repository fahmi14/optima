<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Penugasan extends Model
{
    use HasFactory;

    protected $fillable = [
        'sub_kegiatan_id',
        'user_id',
        'kecamatan_id',
        'target_volume',
        'satuan_volume',
        'realisasi_volume',
        'progres_persen',
        'bobot_poin',
        'deadline_tugas',
        'status',
        'catatan',
        'file_bukti',
        'verified_by',
        'verified_at',
    ];

    protected function casts(): array
    {
        return [
            'target_volume' => 'integer',
            'realisasi_volume' => 'integer',
            'progres_persen' => 'integer',
            'bobot_poin' => 'integer',
            'deadline_tugas' => 'date',
            'verified_at' => 'datetime',
        ];
    }

    public function subKegiatan(): BelongsTo
    {
        return $this->belongsTo(SubKegiatan::class, 'sub_kegiatan_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function kecamatan(): BelongsTo
    {
        return $this->belongsTo(Kecamatan::class, 'kecamatan_id');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function alokasiTransports(): HasMany
    {
        return $this->hasMany(AlokasiTransport::class, 'penugasan_id');
    }

    public function bebanPoin(): int
    {
        return (int) $this->bobot_poin * (int) $this->target_volume;
    }

    public function bobotLabel(): string
    {
        return match ((int) $this->bobot_poin) {
            1 => 'Ringan (1)',
            3 => 'Sedang (3)',
            5 => 'Berat (5)',
            default => "Bobot ({$this->bobot_poin})",
        };
    }
}
