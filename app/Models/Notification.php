<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'destinataire','message_id','notify','chrone','canal','delivery_status','wassenger_id'
    ];

    public function message()
    {
        return $this->belongsTo(Message::class);
    }

    protected $casts = [
        'created_at' => 'datetime:d M Y h:i:s',
    ];
}
