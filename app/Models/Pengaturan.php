<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pengaturan extends Model
{
    protected $fillable = [
        'kunci',
        'label',
        'nilai',
        'tipe',
        'keterangan',
    ];

    public static function get(string $key, mixed $default = null): mixed
    {
        $item = static::where('kunci', $key)->first();
        return $item ? $item->nilai : $default;
    }

    public static function set(string $key, mixed $value): void
    {
        static::updateOrCreate(['kunci' => $key], ['nilai' => $value]);
    }
}
