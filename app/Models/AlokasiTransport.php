<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AlokasiTransport extends Model
{
    use HasFactory;

    protected $fillable = [
        'penugasan_id',
        'periode_kegiatan_id',
        'user_id',
        'kecamatan_id',
        'tgl_tugas',
        'nominal',
        'status_spj',
        'catatan_spj',
    ];

    protected function casts(): array
    {
        return [
            'tgl_tugas' => 'date',
            'nominal' => 'decimal:2',
        ];
    }

    public function penugasan(): BelongsTo
    {
        return $this->belongsTo(Penugasan::class, 'penugasan_id');
    }

    public function periodeKegiatan(): BelongsTo
    {
        return $this->belongsTo(PeriodeKegiatan::class, 'periode_kegiatan_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function kecamatan(): BelongsTo
    {
        return $this->belongsTo(Kecamatan::class, 'kecamatan_id');
    }
}
