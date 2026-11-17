<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Desa extends Model
{
    use HasFactory;

    protected $table = 'desas';

    protected $fillable = [
        'id_kec',
        'desa',
        'kd_desa',
    ];

    public function kecamatan(): BelongsTo
    {
        return $this->belongsTo(Kecamatan::class, 'id_kec');
    }

    public function sls(): HasMany
    {
        return $this->hasMany(Sls::class, 'id_desa');
    }
}
