<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class International extends Model
{
    use HasFactory;
    protected $fillable = ['country','sub',];

    protected $casts = ['created_at' => 'datetime:d M Y h:i:s'];
}
