<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Advertisement extends Model
{
    use HasFactory;

    protected $fillable = ['client_id', 'title', 'description', 'media_path', 'link',  'delay', 'start_date', 'end_date', 'status', 'ed_reference'];

    protected $hidden = ['id', 'updated_at', 'created_at', 'client_id'];
}
