<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Fichier extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function shortLink()
    {
        return $this->hasOne(ShortLink::class);
    }

    // create shortlink
    protected static function booted()
    {
        static::created(function ($fichier) {
            $shortCode = ShortLink::generateShortCode();
            $fichier->shortLink()->create([
                'short_code' => $shortCode,
            ]);
        });
    }
}
