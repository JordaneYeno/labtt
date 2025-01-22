<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Kiosk extends Model
{
    protected $fillable = ['name', 'latitude', 'longitude', 'locked', 'last_seen'];

    public function advertisements()
    {
        return $this->belongsToMany(Advertisement::class);
    }
}
