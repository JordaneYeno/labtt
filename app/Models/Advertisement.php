<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Advertisement extends Model
{
    protected $fillable = ['client_id', 'title', 'description', 'media_path', 'start_date', 'end_date', 'status'];

    public function kiosks()
    {
        return $this->belongsToMany(Kiosk::class);
    }
}
