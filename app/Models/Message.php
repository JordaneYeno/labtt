<?php

namespace App\Models;

use App\Http\Controllers\ClientMessagesController;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    use HasFactory;

    protected $casts = [
        'created_at' => 'datetime:d M Y à H:i:s',
        'finish' => 'datetime:d M Y à H:i:s',
        'start' => 'datetime:d M Y à H:i:s',
        'date_envoie' => 'datetime:d M Y à H:i:s',
    ];
    protected $fillable = [
        'title', 'ed_reference', 'credit','email', 'message','status' , 'destinataires', 'canal',  'date_envoie', 'user_id', 'banner' , 'email_awt','start','finish','verify','slug', 'expediteur'
    ];  

    // nom_campagne, canal, titre_message, message, pj, destinataires, signature[nom, contact, logo], date de lancement (peut-etre un interval)
    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }
}