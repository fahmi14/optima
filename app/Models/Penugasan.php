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
        'pengawas_id',
        'kecamatan_id',
        'jenis_perjalanan',
        'asal_perjalanan',
        'tujuan_luar_kab',
        'rute_transport',
        'jumlah_hari_transport',
        'level_wilayah',
        'wilayah_tugas',
        'target_volume',
        'satuan_volume',
        'realisasi_volume',
        'progres_persen',
        'bobot_poin',
        'estimasi_jam_total',
        'honor_satuan',
        'honor_total',
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
            'estimasi_jam_total' => 'decimal:2',
            'honor_satuan' => 'decimal:2',
            'honor_total' => 'decimal:2',
            'deadline_tugas' => 'date',
            'verified_at' => 'datetime',
            'wilayah_tugas' => 'array',
            'rute_transport' => 'array',
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

    public function pengawas(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pengawas_id');
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

    public function durasiJam(): float
    {
        if ($this->estimasi_jam_total !== null && (float) $this->estimasi_jam_total > 0) {
            return (float) $this->estimasi_jam_total;
        }

        $jamPerSatuan = (float) ($this->subKegiatan?->estimasi_jam_per_satuan ?: 1);
        return round($jamPerSatuan * (int) $this->target_volume, 2);
    }

    public function bebanPoin(): int
    {
        return (int) round($this->durasiJam());
    }

    public function bobotLabel(): string
    {
        $jam = $this->durasiJam();
        return "{$jam} Jam";
    }
}
