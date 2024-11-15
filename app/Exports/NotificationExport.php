<?php

namespace App\Exports;

use App\Http\Resources\UserCollection;
use App\Http\Resources\UserResource;
use App\Models\Contact;
use App\Models\Message;
use App\Models\Notification;
use App\Models\Transaction;
use App\Models\User;
use App\Exports\TableExport;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;


class NotificationExport implements  WithMultipleSheets
{
    // protected $messageId;

    // public function __construct(int $messageId){
    //     $this->messageId = $messageId;
    // }
    public function sheets(): array
    {
        $sheets = [];

        $message = Message::all(['title', 'canal', 'message'])->toArray();
        $sheets[] = new TableExport($message, 'message');

        // $destinataires = Message::where('message_id', $this->messageId)->toArray();
        // $sheets[] = new TableExport($destinataires, 'destinataires', 'dest');

        // $user = User::all(['name', 'email', 'phone'])->toArray();
        // $sheets[] = new TableExport($user, 'utilisateur');

        // $contact = Contact::all(['nom', 'prénom', 'numéro', 'email'])->toArray();
        // $sheets[] = new TableExport($contact, 'contact');

        return $sheets;
    }
    
}