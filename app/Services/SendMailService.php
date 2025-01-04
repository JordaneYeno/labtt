<?php 

namespace App\Services;

use Mail;
use App\Models\Param;

class SendMailService{
    public function decisionMail($email, $title, $decision, $expediteur, $canal, $name)
    {
        $data["email"] = $email;
        $data["title"] = $title;
        $data["decision"] = $decision;
        $data["from"] = $expediteur;
        $data["canal"] = $canal;
        $data["name"] = $name;
        $data['from_name'] = 'Hobotta';
        
        Mail::send('mail.notification', $data, function ($message) use ($data) {
            $message->to($data["email"], $data["email"])
                ->subject($data["title"])
                ->from($data['from'], $data['from_name']);          
        });
    }

    public function submitMail($email, $title, $expediteur)
    {
        $data["email"] = $email;
        $data["title"] = $title;
        $data["from"] = $expediteur;
        $data['from_name'] = 'Hobotta';

        Mail::send('mail.demande', ['data' => $data], function ($message) use ($data) {
            $message->to($data["email"], $data["email"])
                ->subject($data["title"])
                ->from($data['from'], $data['from_name']);
        });
    }

    public function sendMail($sujet, $user, $message){
        
        $data["email"] = Param::getAdminEmail();
        $data["title"] = $sujet;
        $data["from"] = Param::getEmailAwt();
        $data["message"] = $message;
        $data['from_name'] = 'Hobotta';

        Mail::send('mail.compte', ['data' => $data], function ($message) use ($data) {
            $message->to($data["email"], $data["email"])
                ->subject($data["title"])
                ->from($data['from'], $data['from_name']);
        });
    }

    public function campagne($sujet, $destinataire, $message, $files){
//not finish
        $data["title"] = $sujet;
        $data["email"] = $destinataire;
        $data["body"]  = $message;
        $data["from"]  = 'noreply@pvitservice.com';

        Mail::send('mail.campagne', $data, function ($message) use ($data, $files) {
            $message->to($data["email"], $data["email"])
                ->subject($data["title"])
                ->from($data['from']);

            foreach ($files as $file) {
                $message->attach($file);
            }
        });
    }
}