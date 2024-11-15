<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Wagroup extends Model
{
    use HasFactory;

    protected $fillable = [
        'total',
        'name',
        'kwid',
        'kid',
        'user_id',
    ];

    protected $casts = [
        'created_at' => 'datetime:d M Y h:i:s',
    ];
}
