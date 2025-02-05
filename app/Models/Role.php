<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use HasFactory;

    protected $table = 'roles';

    // Colonnes autorisées pour le remplissage
    protected $fillable = [
        'role_id', 
        'name',
    ];
}
