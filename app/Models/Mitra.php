<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mitra extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'nama',
        'nik',
        'kecamatan',
        'no_hp',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
