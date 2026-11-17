<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Wilayah extends Model
{
    use HasFactory;

    protected $fillable = [
        'nama',
        'kab',
        'kd_kab',
        'kec',
        'kd_kec',
        'desa',
        'kd_desa',
        'sls',
        'kd_sls',
        'subsls',
    ];

    public function matrikTugas()
    {
        return $this->hasMany(MatrikTugas::class, 'id_wilayah');
    }
}
