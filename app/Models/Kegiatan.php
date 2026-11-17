<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Kegiatan extends Model
{
    use HasFactory;

    protected $fillable = [
        'nama',
        'tgl_mulai',
        'tgl_selesai',
        'id_tim',
        'administrasi',
    ];

    protected $casts = [
        'tgl_mulai' => 'date',
        'tgl_selesai' => 'date',
        'administrasi' => 'array',
    ];

    public function tim()
    {
        return $this->belongsTo(Tim::class, 'id_tim');
    }

    public function matrikTugas()
    {
        return $this->hasMany(MatrikTugas::class, 'id_kegiatan');
    }
}
