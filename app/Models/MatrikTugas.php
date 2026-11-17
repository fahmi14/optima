<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MatrikTugas extends Model
{
    use HasFactory;

    protected $table = 'matrik_tugas';

    protected $fillable = [
        'id_kegiatan',
        'id_user',
        'role',
        'id_wilayah',
        'tgl',
        'beban',
    ];

    protected $casts = [
        'role' => 'array',
        'tgl' => 'date',
        'beban' => 'integer',
    ];

    public function kegiatan()
    {
        return $this->belongsTo(Kegiatan::class, 'id_kegiatan');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'id_user');
    }

    public function wilayah()
    {
        return $this->belongsTo(Wilayah::class, 'id_wilayah');
    }
}
