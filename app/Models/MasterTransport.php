<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MasterTransport extends Model
{
    use HasFactory;

    protected $table = 'master_transports';

    protected $fillable = [
        'kecamatan_asal_id',
        'kecamatan_tujuan_id',
        'nominal',
    ];

    protected function casts(): array
    {
        return [
            'nominal' => 'decimal:2',
        ];
    }

    public function kecamatanAsal(): BelongsTo
    {
        return $this->belongsTo(Kecamatan::class, 'kecamatan_asal_id');
    }

    public function kecamatanTujuan(): BelongsTo
    {
        return $this->belongsTo(Kecamatan::class, 'kecamatan_tujuan_id');
    }
}
