<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AlokasiKepka extends Model
{
    use HasFactory;

    protected $table = 'alokasi_kepkas';

    protected $fillable = [
        'id_mitra',
        'tahun',
    ];

    protected function casts(): array
    {
        return [
            'tahun' => 'integer',
        ];
    }

    public function masterMitra(): BelongsTo
    {
        return $this->belongsTo(MasterMitra::class, 'id_mitra');
    }
}
