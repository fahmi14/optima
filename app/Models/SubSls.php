<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubSls extends Model
{
    use HasFactory;

    protected $table = 'sub_sls';

    protected $fillable = [
        'id_kec',
        'id_desa',
        'sls_id',
        'id_sls',
        'subsls',
    ];

    public function kecamatan(): BelongsTo
    {
        return $this->belongsTo(Kecamatan::class, 'id_kec');
    }

    public function desa(): BelongsTo
    {
        return $this->belongsTo(Desa::class, 'id_desa');
    }

    public function slsRef(): BelongsTo
    {
        return $this->belongsTo(Sls::class, 'sls_id');
    }
}
