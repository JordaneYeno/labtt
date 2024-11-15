<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShortLink extends Model
{
    use HasFactory;

    protected $fillable = ['fichier_id', 'short_code'];

    public static function generateShortCode()
    {
        return substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 6);
    }

    // Relation avec le modèle Fichier
    public function fichier()
    {
        return $this->belongsTo(Fichier::class);
    }
}
