<?php 

namespace App\Services;

use Mail;
use App\Models\Param;

class SendMailService{

    public function decisionMail($email, $title, $decision, $expediteur, $canal)
    {
        $data["email"] = $email;
        $data["title"] = $title;
        $data["decision"] = $decision;
        $data["from"] = $expediteur;
        $data["canal"] = $canal;
        
        Mail::send('mail.notification', ['data' => $data], function ($message) use ($data) {
            $message->to($data["email"], $data["email"])
                ->subject($data["title"])
                ->from($data['from']);
        });
    }

    public function submitMail($email, $title, $expediteur)
    {
        $data["email"] = $email;
        $data["title"] = $title;
        $data["from"] = $expediteur;

        Mail::send('mail.demande', ['data' => $data], function ($message) use ($data) {
            $message->to($data["email"], $data["email"])
                ->subject($data["title"])
                ->from($data['from']);
        });
    }

    public function sendMail($sujet, $user, $message){
        
        $data["email"] = Param::getAdminEmail();
        $data["title"] = $sujet;
        $data["from"] = Param::getEmailAwt();
        $data["message"] = $message;

        Mail::send('mail.compte', ['data' => $data], function ($message) use ($data) {
            $message->to($data["email"], $data["email"])
                ->subject($data["title"])
                ->from($data['from']);
        });
    }

    public function campagne($sujet, $destinataire, $message, $files){
//not finish
        $data["title"] = $sujet;
        $data["email"] = $ndestinataire;
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