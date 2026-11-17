<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Organik extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'nama',
        'nip',
        'jabatan',
        'golongan',
        'id_tim',
    ];

    protected $casts = [
        'id_tim' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
