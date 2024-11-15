<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Assistance extends Model
{
    use HasFactory;

    protected $fillable = [
        'agent',
        'phone',
        'role',
        'status',
    ];

    protected $casts = [
        'created_at' => 'datetime:d M Y h:i:s',
    ];
}

