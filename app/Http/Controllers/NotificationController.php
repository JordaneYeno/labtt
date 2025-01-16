<?php

namespace App\Http\Controllers;

use App\Exports\NotificationExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Notifications\CustumGateway;
use App\Http\Requests\Notifications\GetAllGroupInfo;
use App\Http\Requests\WaGroup\SendMessage;
use App\Models\Abonnement;
use App\Models\Fichier;
use App\Models\Message;
use App\Models\Notification;
use App\Models\Param;
use App\Models\Tarifications;
use App\Models\Template;
use App\Models\Transaction;
use App\Models\User;
use App\Services\Convertor;
use App\Services\PaginationService;
use App\Services\SmsCount;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use JWTAuth;
use Mail;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Str;

class NotificationController extends Controller
{
    protected $user;
    protected $canSend;
    public function __construct()
    {
        $this->canSend = false;
        $this->user = User::getCurrentUser();
    }

    public function test()
    {
        Abonnement::where('user_id', 2)->increment('solde', 21111111);
    }
    public function exportData()
    {
        return Excel::download(new NotificationExport, 'notification-table.xlsx');
    }

    // start cusumer Hobotta API

    public function custumGateway(CustumGateway $request)
    {
        
        $perPage = $request->perPage ? $request->perPage : 9;
        $paginate = new PaginationService();

        $contacts = $request->recipients;

        if ($request->canalkey === null) {
            return response()->json([ 
                'status' => 'error',
                'message' => 'Veuillez indiquer le canal de diffusion.',
            ], 400);
        }

        $allAbonnements = Abonnement::get();
        $user = auth()->user();
        $solde = $allAbonnements->where('user_id', $user->id)->pluck('solde')->first();
        $responses = [];
        $total = $totalMedia = 0;
        $colorTheme = $allAbonnements->where('user_id', $user->id)->pluck('cs_color')->first();
        $userDeviceId = (new Abonnement)->getCurrentWassengerDeviceWithoutAuth($user->id);

        switch ($request->canalkey) {
            case "whatsapp":
                if (Param::getStatusWhatsapp() == 0) {
                    return response()->json([
                        'status' => 'échec',
                        'message' => 'Service WhatsApp désactivé',
                    ], 422);
                }

                if ($userDeviceId === null) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Device introuvable.',
                    ], 400); // Code 400 : Bad Request
                }

                $API_KEY_WHATSAPP = Param::getTokenWhatsapp();
                $destinatairesWhatsapp = explode(',', $contacts);
                $total = count($destinatairesWhatsapp) * (new Tarifications)->getWhatsappPrice();

                // Vérification de tous les numéros avant la facturation
                foreach ($destinatairesWhatsapp as $destinataire) {
                    if (!is_numeric($destinataire)) 
                    {
                        return response()->json([
                            'statut' => 'error',
                            'message' => 'Numéro invalide',
                            'destinataire' => $destinataire,
                        ], 400);
                    }

                    if((new Abonnement)->getInternaltional($user->id) == 0)
                    {
                        $conv = new Convertor();
                        $interphone = $conv->internationalisation($destinataire, request('country', 'GA'));
                        if ($interphone == null) {
                            return response()->json([
                                'status' => 'error',
                                'message' => 'Numéro de téléphone invalide',
                                'destinataire' => $destinataire,
                            ], 400);
                        }
                    
                    }
                }

                if ($total > $solde) {
                    return response()->json([
                        'status' => 'échec',
                        'message' => 'Votre solde est insuffisant pour effectuer cette campagne',
                        'prix_campagne' => $total,
                        'solde' => $solde,
                    ], 400);
                }

                $message = Message::create([
                    'user_id' => $user->id,
                    'ed_reference' => $this->generateHexReference(),
                    'title' => $request->title,
                    'message' => $request->message,
                    'canal' => 'api whatsapp',
                    'status' => 4,  // status en de depart 
                ]);

                if ($request->hasFile('file')) {
                    $file = $request->file('file');
                    $maxFileSize = 5000 * 1024; // 5000 Ko en octets
                    $allowedTypes = [
                        "application/msword",
                        "application/vnd.openxmlformats-officedocument.wordprocessingml.document",
                        "application/vnd.ms-excel",
                        "application/vnd.ms-powerpoint",
                        "application/pdf",
                        "image/jpeg",
                        "image/png",
                        "image/gif",
                        "video/mp4",
                    ];

                    if ($file->getSize() > $maxFileSize) {
                        return response()->json(['status' => 'echec', 'message' => 'La taille du fichier ne doit pas dépasser 5000 Ko']);}

                    if (!in_array($file->getMimeType(), $allowedTypes)) {
                        return response()->json(['status' => 'echec', 'message' => 'Type de fichier non autorisé']);}

                    $this->storeFile($message->id, $file, $user->id, false);
                }

                // Facturer les media WhatsApp
                $totalMedia = count($destinatairesWhatsapp) * (count(Fichier::where('message_id', $message->id)->pluck('lien')) * (new Tarifications)->getWhatsappMediaPrice('media')); 
                // Facturer la campagne WhatsApp
                Abonnement::__factureWhatsapp(count($destinatairesWhatsapp), $total,$totalMedia, $message->id);
                
                // Débiter le solde de l'utilisateur
                $Pprice = $total + $totalMedia;
                (new Transaction)->__addTransactionAfterSendMessage($user->id, 'debit', $Pprice, $message->id, count($destinatairesWhatsapp), Abonnement::__getSolde($user->id), null, 'whatsapp');
                
                $errors = false;

                foreach ($destinatairesWhatsapp as $destinataire) {
                    if((new Abonnement)->getInternaltional($user->id) == 0) 
                    {
                        $conv = new Convertor();                        
                        $interphone = $conv->internationalisation($destinataire, request('country', 'GA'));
                    }

                    $notification = Notification::create([
                        'destinataire' => $destinataire,
                        'canal' => 'whatsapp',
                        'notify' => 4,  // api direct #without cron 
                        'chrone' => 4,  // envoi direct #without cron
                        'message_id' => $message->id,
                    ]);
                    
                    $isWa = (new WaGroupController())->isExistOnWa(((new Abonnement)->getInternaltional($user->id) == 0) ? $interphone :$destinataire); //check phone wa_number! 
                    $files = Fichier::where('message_id', $notification->message_id)->pluck('lien'); 
                    
                    if ($isWa != false) 
                    {
                        if (count($files) >= 1) { // isExistFile
                            sleep(1);
                            $fileUrl = route('files.show', ['folder' => $user->id, 'filename'=> basename($files[0])]); //url
                            
                            if (strpos($files[0], '.mp4') !== false) {
                                $data = [
                                    "phone" => (new Abonnement)->getInternaltional($user->id) == 0 ? $interphone : $destinataire,
                                    "message" => strip_tags($message->message),
                                    "media" => ["url" => $fileUrl],
                                    "device" => $userDeviceId, // Spécification du deviceId
                                ];

                                $curl = curl_init();
                                curl_setopt_array($curl, [
                                    CURLOPT_URL => "https://api.wassenger.com/v1/messages",
                                    CURLOPT_RETURNTRANSFER => true,
                                    CURLOPT_SSL_VERIFYPEER => false, //ssl off
                                    CURLOPT_ENCODING => "",
                                    CURLOPT_MAXREDIRS => 10,
                                    CURLOPT_TIMEOUT => 30,
                                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                                    CURLOPT_CUSTOMREQUEST => "POST",
                                    CURLOPT_POSTFIELDS => json_encode($data),
                                    CURLOPT_HTTPHEADER => [
                                        "Content-Type: application/json",
                                        "Token: $API_KEY_WHATSAPP",
                                    ],
                                ]);

                                $response = curl_exec($curl);
                                $err = curl_error($curl);
                                curl_close($curl);
                            } else {
                                $data = ["url" => $fileUrl];

                                $curl = curl_init();
                                curl_setopt_array($curl, [
                                    CURLOPT_URL => "https://api.wassenger.com/v1/files",
                                    CURLOPT_RETURNTRANSFER => true,
                                    CURLOPT_SSL_VERIFYPEER => false, //ssl off
                                    CURLOPT_ENCODING => "",
                                    CURLOPT_MAXREDIRS => 10,
                                    CURLOPT_TIMEOUT => 30,
                                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                                    CURLOPT_CUSTOMREQUEST => "POST",
                                    CURLOPT_POSTFIELDS => json_encode($data),
                                    CURLOPT_HTTPHEADER => [
                                        "Content-Type: application/json",
                                        "Token: $API_KEY_WHATSAPP",
                                    ],
                                ]);

                                $response = curl_exec($curl);
                                $err = curl_error($curl);
                                curl_close($curl);

                                if ($err) {
                                    echo "cURL Error #:" . $err;
                                } else {
                                    $reponse_banner = json_decode($response);

                                    if (is_array($reponse_banner) == true) {
                                        $itemsList = array("file" => $reponse_banner[0]->id);
                                    } else {
                                        $itemsList = array("file" => $reponse_banner->meta->file);
                                    }
                                    sleep(2); // sleep(3);

                                    $data = ["phone" => (new Abonnement)->getInternaltional($user->id) == 0 ? $interphone : $destinataire, "message" => strip_tags($message->message), "media" => $itemsList, "device" => $userDeviceId];
                                    $curl = curl_init();
                                    curl_setopt_array($curl, [
                                        CURLOPT_URL => "https://api.wassenger.com/v1/messages",
                                        CURLOPT_RETURNTRANSFER => true,
                                        CURLOPT_SSL_VERIFYPEER => false, //ssl off
                                        CURLOPT_ENCODING => "",
                                        CURLOPT_MAXREDIRS => 10,
                                        CURLOPT_TIMEOUT => 30,
                                        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                                        CURLOPT_CUSTOMREQUEST => "POST",
                                        CURLOPT_POSTFIELDS => json_encode($data),
                                        CURLOPT_HTTPHEADER => [
                                            "Content-Type: application/json",
                                            "Token: $API_KEY_WHATSAPP",
                                        ],
                                    ]);

                                    $response = curl_exec($curl);
                                    $reponse = json_decode($response);
                                    $err = curl_error($curl);
                                    curl_close($curl);
                                }
                            }
                        } else if (count($files) == 0) {
                            $data = ["phone" => (new Abonnement)->getInternaltional($user->id) == 0 ? $interphone : $destinataire, "message" => strip_tags($request->message), "device" => $userDeviceId];
                            $curl = curl_init();
                            curl_setopt_array($curl, [
                                CURLOPT_URL => "https://api.wassenger.com/v1/messages",
                                CURLOPT_RETURNTRANSFER => true,
                                CURLOPT_ENCODING => "",
                                CURLOPT_MAXREDIRS => 10,
                                CURLOPT_TIMEOUT => 30,
                                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                                CURLOPT_CUSTOMREQUEST => "POST",
                                CURLOPT_POSTFIELDS => json_encode($data),
                                CURLOPT_HTTPHEADER => [
                                    "Content-Type: application/json",
                                    "Token: $API_KEY_WHATSAPP",
                                ],
                            ]);
                            $response = curl_exec($curl);
                            $err = curl_error($curl);
                            curl_close($curl);
                        }

                        if ($err) {
                            $errors = true;

                            $responses[] = [
                                'status' => 'error',
                                'message' => 'Erreur lors de l\'envoi du message',
                                'destinataire' => $destinataire,
                                'canal' => $notification->canal,
                            ];
                        } else {
                            $reponse = json_decode($response);

                            if (!empty($reponse->id)) {
                                $notification->delivery_status = $reponse->deliveryStatus;
                                $notification->save();
                                $notification->wassenger_id = $reponse->id;
                                $notification->save();

                                $responses[] = [
                                    'status' => 'success',
                                    'message' => 'Message envoyé avec succès',
                                    'destinataire' => $destinataire,
                                    'canal' => $notification->canal,
                                ];
                            } else {
                                $errors = true;
                                $notification->delivery_status = 'echec';
                                $notification->save();
                                // credit
                                Abonnement::creditWhatsapp(1, $message->id);
                                // Abonnement::creditMediaWhatsapp(1, $message->id, count($files));
                                                            
                                $responses[] = [
                                    'status' => 'error',
                                    'message' => 'Message non envoyé',
                                    'destinataire' => $destinataire,
                                    'canal' => $notification->canal,
                                    'encode' => $reponse->errorCode ?? null
                                ];
                            }
                        }
                    } 
                    else 
                    {
                        $responses[] = [
                            'status' => 'error',
                            'message' => 'Erreur lors de l\'envoi du message whatsapp',
                            'destinataire' => $destinataire,
                            'canal' => $notification->canal,
                        ];

                        $notification->delivery_status = 'echec';
                        $notification->save();
                        // credit
                        Abonnement::creditMessageAndMediaWhatsapp(1, $message->id, count($files)); //rembourse en cas d'echec
                                
                        if ($request->rescue == 'sms_fallback' && $isWa == false) {
                                                            
                            if (Param::getStatusSms() == 0) {
                                return response()->json([
                                    'status' => 'échec',
                                    'message' => 'Service SMS désactivé',
                                ], 422);
                            }
                        
                            $smsCount = (new SmsCount)->countSmsSend(strip_tags($request->message));
                            $destinatairesSms = explode(',', $destinataire);
                            $total += $smsTotal = ((new Tarifications)->getSmsPrice() * $smsCount) * count($destinatairesSms);
                        
                            //______??_______//
                        
                            foreach ($destinatairesSms as $destinataire) {
                                if (!is_numeric($destinataire)) {
                                    return response()->json([
                                        'statut' => 'error',
                                        'message' => 'Numéro invalide',
                                        'destinataire' => $destinataire,
                                    ], 400);
                                }
                            }
                        
                            if ($total > $solde) {
                                return response()->json([
                                    'status' => 'échec',
                                    'message' => 'Votre solde est insuffisant pour effectuer cette campagne',
                                    'prix_campagne' => $total,
                                    'solde' => $solde,
                                ], 400);
                            }
                        
                            //______??_______//
                        
                            Abonnement::factureSms(count($destinatairesSms), $smsTotal, $message->id, $message->message);
                        
                            $rescue = Message::where('id', $message->id)->first();  //______ disable !
                            $rescue->canal = $rescue->canal .= ' rescue sms+ ';
                            $rescue->save();  //______ disable !
                        
                            $notification = Notification::create([
                                'destinataire' => $destinataire,
                                'canal' => 'sms+',
                                'notify' => 4,  // api direct #without cron 
                                'chrone' => 4,  // envoi direct #without cron
                                'message_id' => $message->id,
                            ]);
                            // send sms if error sent whatsapp
                        
                            if((new Abonnement)->getInternaltional($user->id) == 0) 
                            {
                                $conv = new Convertor();
                                $interphone = $conv->internationalisation($destinataire, request('country', 'GA'));
                            }
                                    
                            $text = strip_tags($message->message);
                            $data =
                                [
                                    'message' => (new SmsCount)->removeAccents(str_replace('&nbsp;', ' ', $text)),
                                    'receiver' => ((new Abonnement)->getInternaltional($user->id) == 0) ?$interphone :$destinataire,
                                    'sender' => $allAbonnements->where('user_id', $user->id)->pluck('sms')->first() === 'default' ?  strtoupper(Param::getSmsSender() /*'bakoai'*/) : strtoupper($allAbonnements->where('user_id', $user->id)->pluck('sms')->first()),
                            ];

                            $curl = curl_init();
                            curl_setopt_array($curl, [
                                CURLOPT_URL => 'https://devdocks.bakoai.pro/api/smpp/send',
                                CURLOPT_RETURNTRANSFER => true,
                                CURLOPT_SSL_VERIFYPEER => false, // off ssl
                                CURLOPT_ENCODING => '',
                                CURLOPT_MAXREDIRS => 10,
                                CURLOPT_TIMEOUT => 0,
                                CURLOPT_FOLLOWLOCATION => true,
                                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                                CURLOPT_CUSTOMREQUEST => 'POST',
                                CURLOPT_POSTFIELDS => json_encode($data),
                                CURLOPT_HTTPHEADER =>
                                [
                                    'Authorization: Basic ' . base64_encode('hobotta:hobotta'),
                                    'Content-Type: application/json',
                                ],
                            ]);
                        
                            $response = curl_exec($curl);
                            $err = curl_error($curl);
                            curl_close($curl);
                        
                            sleep(1);
                            if ($err) {
                                $errors = true;
                                $notification->delivery_status = 'echec';
                                $notification->save();
                        
                                // credit
                                Abonnement::creditSms(1, $message->id);
                                $responses[] = [
                                    'statut' => 'error',
                                    'message' => "Erreur lors de l'envoi du message à $destinataire",
                                ];
                            } else {
                                $notification->delivery_status = 'sent';
                                $notification->save();
                                $responses[] = [
                                    'status' => 'success',
                                    'message' => 'Message envoyé avec succès',
                                    'destinataire' => $destinataire,
                                    'canal' => $notification->canal,
                                ];
                            }
                        }
                    }
                }

                if ($errors) {
                    $message->status = 5; // Modifier le statut du message à 5 en cas d'erreur
                    $message->save();

                    // return response()->json([// echec
                    //     'status' => 'error',
                    //     'message' => 'Des erreurs sont survenues lors de l\'envoi de certains messages.',
                    //     'details' => $responses,
                    // ], 500);
                }

                $myAbonnements = Abonnement::get(); $addCredit = $myAbonnements->where('user_id', $message->user_id)->first();
                $mydebit = Transaction::get(); $debitClient = $mydebit->where('message_id', $message->id)->first();
                $current_credit = Message::get()->where('id', $message->id)->pluck('credit')->first();

                if ($addCredit && $current_credit) 
                {
                    $message->credit = 0; $message->save();
                    $addCredit->solde += $current_credit; $addCredit->save(); 
                    $debitClient->montant = $total-$current_credit+$totalMedia; $debitClient->save();
                }

                $message->status = 6; // Modifier le statut du message à 6 en cas de succès //le status 6 indiques le message est bien envoyé
                $message->save();
                $paginator = $paginate->paginate_resp($responses, $perPage, request('page', 1));


                return response()->json([
                    'status' => 'success',
                    'message' => 'Votre campagne a été lancée avec succès',
                    'idx' => $message->ed_reference,
                    'details' => $paginator,
                    'total_paye' => $total-$current_credit+$totalMedia,
                    'ancien_solde' => $solde,
                    'nouveau_solde' => Abonnement::__getSolde($user->id), 
                ], 200);

            case "email":
                if (Param::getStatusEmail() == 0) {
                    return response()->json([
                        'status' => 'échec',
                        'message' => 'Service email désactivé',
                    ], 422);
                }

                if ((Abonnement::getAbo($user->id))->email_status == 0) {
                    return response()->json([
                        'status' => 'échec',
                        'message' => 'Service email désactivé',
                    ], 422);
                }

                $data["mylogo"] = route('users.profile', ['id' => $user->id]);

                $data["title"] = $request->title;
                $data["body"] = $request->message;
                $data['template'] = $request->template ?? 0;
                $data['color_theme'] = $colorTheme;

                $data["localisation"] = $allAbonnements->where('user_id', $user->id)->pluck('entreprese_localisation')->first();
                $data["contact"] = $allAbonnements->where('user_id', $user->id)->pluck('entreprese_contact')->first();
                $data["from_name"] = $allAbonnements->where('user_id', $user->id)->pluck('entreprese_name')->first();
                $data["ville"] = $allAbonnements->where('user_id', $user->id)->pluck('entreprese_ville')->first();
                $data["from_email"] = $allAbonnements->where('user_id', $user->id)->pluck('email')->first();
                $data["imagePath"] = $allAbonnements->where('user_id', $user->id)->pluck('logo')->first();
                $data["mail"] = $allAbonnements->where('user_id', $user->id)->pluck('email')->first();

                if ($data["imagePath"] == null || $data["contact"] == null || $data["ville"] == null || $data["mail"] == null || $data["from_name"] == null) {
                    return response()->json([
                        'status' => 'echec',
                        'signature' => 'error',
                        'message' => 'paramètre signature vide',
                    ], 422);
                }


                $expediteur = $allAbonnements->where('user_id', $user->id)->pluck('email')->first();
                $mail_copie = [];

                if (!empty($request->mail_copie)) {
                    $mail_copie = explode(",", $request->mail_copie);
                }
                if (!empty($request->expediteur)) {
                    $expediteur = $request->expediteur;
                }


                $destinatairesEmail = explode(',', $contacts);
                $total += $emailTotal = count($destinatairesEmail) * (new Tarifications)->getEmailPrice();

                // Vérification de tous les emails avant la facturation
                foreach ($destinatairesEmail as $destinataire) {
                    $verify_mail = filter_var($destinataire, FILTER_VALIDATE_EMAIL);
                    if ($verify_mail == false) {
                        return response()->json([
                            'statut' => 'error',
                            'message' => 'Email non valide',
                            'destinataire' => $destinataire,
                        ], 400);
                    }
                }

                if ($total > $solde) {
                    return response()->json([
                        'status' => 'échec',
                        'message' => 'Votre solde est insuffisant pour effectuer cette campagne',
                        'prix_campagne' => $total,
                        'solde' => $solde,
                    ], 400);
                }

                $message = Message::create([
                    'user_id' => auth()->user()->id,
                    'ed_reference' => $this->generateHexReference(),
                    'title' => $request->title,
                    'message' => $request->message,
                    'canal' => 'api email',
                    'status' => 4,  // status en de depart
                ]);

                if ($request->hasFile('file')) {
                    $file = $request->file('file');
                    $maxFileSize = 5000 * 1024; // 5000 Ko en octets
                    $allowedTypes = [
                        "application/msword",
                        "application/vnd.openxmlformats-officedocument.wordprocessingml.document",
                        "application/vnd.ms-excel",
                        "application/vnd.ms-powerpoint",
                        "application/pdf",
                        "image/jpeg",
                        "image/png",
                        "image/gif",
                        "video/mp4",
                    ];

                    if ($file->getSize() > $maxFileSize) {
                        return response()->json(['status' => 'echec', 'message' => 'La taille du fichier ne doit pas dépasser 5000 Ko']);
                    }

                    if (!in_array($file->getMimeType(), $allowedTypes)) {
                        return response()->json(['status' => 'echec', 'message' => 'Type de fichier non autorisé']);
                    }

                    $this->storeFile($message->id, $file, $user->id, false);
                }

                $signature = $allAbonnements->where('user_id', $message->user_id)->first();
                
                $data["expediteur"] = $expediteur;
                $data["title"] = $request->title;
                $data["body"] = $request->message;
                $data['mail_copie'] = $mail_copie;
                $data['template'] = $request->template ?? 0;
                $data["localisation"] = $signature->entreprese_localisation;
                $data["contact"] = $signature->entreprese_contact;
                $data["from_name"] = $signature->entreprese_name;
                $data["ville"] = $signature->entreprese_ville;
                $data["from_email"] = $signature->email;
                $data["imagePath"] =  route('users.profile', ['id' => $message->user_id]);
                $data["mail"] = $signature->email;

                if ($data["imagePath"] == null || $data["contact"] == null || $data["ville"] == null || $data["mail"] == null || $data["from_name"] == null) {
                    $this->deleteMessage($message->id);
                    return response()->json([
                        'statut' => 'error',
                        'message' => 'parametre signature',
                    ], 400);
                }

                Abonnement::factureEmail(count($destinatairesEmail), $emailTotal, $message->id);
                (new Transaction)->__addTransactionAfterSendMessage($user->id, 'debit', $total, $message->id, count($destinatairesEmail), Abonnement::__getSolde($user->id), null, 'email');

                $errors = false;

                foreach ($destinatairesEmail as $destinataire) {
                    $notification = Notification::create([
                        'destinataire' => $destinataire,
                        'canal' => 'email',
                        'notify' => 4,  // api direct #without cron
                        'chrone' => 4,  // envoi direct #without cron
                        'message_id' => $message->id,
                    ]);

                    $files = Fichier::where('message_id', $notification->message_id)->pluck('lien');
    
                    $data["email"] = $destinataire; 

                    Mail::send('mail.campagne', $data, function ($objet_mail) use ($data, $files, $message) {
                        $objet_mail->to($data["email"])
                            ->subject($data["title"])
                            ->from($data['from_email'], $data ['from_name']);
                        if (count($files) > 0) {

                            $totalMedia = /*count($files)*/ 1 * (new Tarifications)->getWhatsappMediaPrice('media'); 
                            foreach ($files as $file) 
                            {                                    
                                $filename = basename($file);
                                $folder = $message->user_id;
                                $file_path = public_path("storage/banner/{$folder}/{$filename}");
                                $objet_mail->attach($file_path);
                            }
                        }
                    });

                    if (Mail::failures()) {
                        $errors = true;
                        $notification->delivery_status = 'echec';
                        $notification->save();
                        // credit
                        Abonnement::creditEmail(1, $message->id);

                        $responses[] = [
                            'statut' => 'error',
                            'message' => 'Erreur lors de l\'envoi de l\'email',
                            'destinataire' => $destinataire,
                        ];
                    } else {
                        $notification->delivery_status = 'sent';
                        $notification->save();
                        $responses[] = [
                            'status' => 'success',
                            'message' => 'Email envoyé avec succès',
                            'destinataire' => $destinataire,
                            'canal' => $notification->canal,
                        ];
                    }
                }

                if ($errors) {
                    $message->status = 5; // Modifier le statut du message à 5 en cas d'erreur
                    $message->save();

                    // return response()->json([
                    //     'status' => 'error',
                    //     'message' => 'Des erreurs sont survenues lors de l\'envoi de certains emails.',
                    //     'details' => $responses,
                    // ], 500);
                }

                $myAbonnements = Abonnement::get(); $addCredit = $myAbonnements->where('user_id', $message->user_id)->first();
                $mydebit = Transaction::get(); $debitClient = $mydebit->where('message_id', $message->id)->first();
                $current_credit = Message::get()->where('id', $message->id)->pluck('credit')->first();
        
                if ($addCredit && $current_credit) 
                {
                    $message->credit = 0; $message->save();
                    $addCredit->solde += $current_credit; $addCredit->save(); 
                    $debitClient->montant = $total-$current_credit-$totalMedia; $debitClient->save();
                }

                $message->status = 6; // Modifier le statut du message à 6 en cas de succès //le status 6 indiques le message est bien envoyé
                $message->save();
                $paginator = $paginate->paginate_resp($responses, $perPage, request('page', 1));


                return response()->json([
                    'status' => 'success',
                    'message' => 'Votre campagne a été lancée avec succès',
                    'idx' => $message->ed_reference,
                    'details' => $paginator,
                    'total_paye' => $total-$current_credit+$totalMedia,
                    'ancien_solde' => $solde,
                    'nouveau_solde' => Abonnement::__getSolde($user->id),
                ], 200);

            case "sms":
                if (Param::getStatusSms() == 0) {
                    return response()->json([
                        'status' => 'échec',
                        'message' => 'Service SMS désactivé',
                    ], 422);
                }

                $smsCount = (new SmsCount)->countSmsSend(strip_tags($request->message));
                $destinatairesSms = explode(',', $contacts);
                $total += $smsTotal = ((new Tarifications)->getSmsPrice() * $smsCount) * count($destinatairesSms);

                foreach ($destinatairesSms as $destinataire) {
                    if (!is_numeric($destinataire)) {
                        return response()->json([
                            'statut' => 'error',
                            'message' => 'Numéro invalide',
                            'destinataire' => $destinataire,
                        ], 400);
                    }
                }

                if ($total > $solde) {
                    return response()->json([
                        'status' => 'échec',
                        'message' => 'Votre solde est insuffisant pour effectuer cette campagne',
                        'prix_campagne' => $total,
                        'solde' => $solde,
                    ], 400);
                }

                $message = Message::create([
                    'user_id' => auth()->user()->id,
                    'ed_reference' => $this->generateHexReference(),
                    'title' => $request->title,
                    'message' => $request->message,
                    'canal' => 'api sms',
                    'status' => 4,  // status en de depart
                ]);

                Abonnement::factureSms(count($destinatairesSms), $smsTotal, $message->id, $message->message);
                (new Transaction)->__addTransactionAfterSendMessage($user->id, 'debit', $total, $message->id, count($destinatairesSms), Abonnement::__getSolde($user->id), null, 'sms');

                $errors = false;

                foreach ($destinatairesSms as $destinataire) {
                    $notification = Notification::create([
                        'destinataire' => $destinataire,
                        'canal' => 'sms',
                        'notify' => 4,  // api direct #without cron
                        'chrone' => 4,  // envoi direct #without cron
                        'message_id' => $message->id,
                    ]);

                    if((new Abonnement)->getInternaltional($user->id) == 0) 
                    {
                        $conv = new Convertor();
                        $interphone = $conv->internationalisation($destinataire, request('country', 'GA'));
                    }

                    $text = strip_tags($message->message);
                    $data =
                        [
                            'message' => (new SmsCount)->removeAccents(str_replace('&nbsp;', ' ', $text)),
                            'receiver' => ((new Abonnement)->getInternaltional($user->id) == 0) ?$interphone :$destinataire,
                            'sender' => $allAbonnements->where('user_id', $user->id)->pluck('sms')->first() === 'default' ?  strtoupper(Param::getSmsSender() /*'bakoai'*/)  : strtoupper($allAbonnements->where('user_id', $user->id)->pluck('sms')->first()),
                        ];

                    $curl = curl_init();
                    curl_setopt_array($curl, [
                        CURLOPT_URL => 'https://devdocks.bakoai.pro/api/smpp/send',
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_SSL_VERIFYPEER => false, // off ssl
                        CURLOPT_ENCODING => '',
                        CURLOPT_MAXREDIRS => 10,
                        CURLOPT_TIMEOUT => 0,
                        CURLOPT_FOLLOWLOCATION => true,
                        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                        CURLOPT_CUSTOMREQUEST => 'POST',
                        CURLOPT_POSTFIELDS => json_encode($data),
                        CURLOPT_HTTPHEADER =>
                        [
                            'Authorization: Basic ' . base64_encode('hobotta:hobotta'),
                            'Content-Type: application/json',
                        ],
                    ]);

                    $response = curl_exec($curl); //dd($response);
                    $err = curl_error($curl);
                    curl_close($curl);

                    sleep(1);
                    if ($err) {
                        $errors = true;
                        $notification->delivery_status = 'echec';
                        $notification->save();

                        // credit
                        Abonnement::creditSms(1, $message->id);
                        $responses[] = [
                            'statut' => 'error',
                            'message' => "Erreur lors de l'envoi du message à $destinataire",
                        ];
                    } else {
                        $notification->delivery_status = 'sent';
                        $notification->save();
                        $responses[] = [
                            'status' => 'success',
                            'message' => 'Message envoyé avec succès',
                            'destinataire' => $destinataire,
                            'canal' => $notification->canal,
                        ];
                    }
                }

                if ($errors) {
                    $message->status = 5; // Modifier le statut du message à 5 en cas d'erreur //le status 5 indiques le message non envoyé
                    $message->save();


                    // return response()->json([
                    //     'status' => 'error',
                    //     'message' => 'Des erreurs sont survenues lors de l\'envoi de certains messages.',
                    //     'details' => $responses,
                    // ], 500);
                }

                $myAbonnements = Abonnement::get(); $addCredit = $myAbonnements->where('user_id', $message->user_id)->first();
                $mydebit = Transaction::get(); $debitClient = $mydebit->where('message_id', $message->id)->first();
                $current_credit = Message::get()->where('id', $message->id)->pluck('credit')->first();
        
                if ($addCredit && $current_credit) 
                {
                    $message->credit = 0; $message->save();
                    $addCredit->solde += $current_credit; $addCredit->save(); 
                    $debitClient->montant = $total-$current_credit; $debitClient->save();
                }

                $message->status = 6; // Modifier le statut du message à 6 en cas de succès //le status 6 indiques le message est bien envoyé
                $message->save();
                $paginator = $paginate->paginate_resp($responses, $perPage, request('page', 1));


                return response()->json([
                    'status' => 'success',
                    'message' => 'Votre campagne a été lancée avec succès',
                    'idx' => $message->ed_reference,
                    'details' => $paginator,
                    'total_paye' => $total-$current_credit,
                    'ancien_solde' => $solde,
                    'nouveau_solde' => Abonnement::__getSolde($user->id),
                ], 200);

            default:
                return response()->json([
                    'status' => 'error',
                    'message' => 'Canal invalide',
                ], 400);
        }
    }

    public function getAllGroupInfo(/*GetAllGroupInfo*/Request $request)
    {
        $groups = (new WaGroupController)->getAllGroups($request); // instance
        return $groups;
    }

    public function multiSendAtGroups(SendMessage $request)
    {
        $perPage = $request->perPage ? $request->perPage : 9;
        $paginate = new PaginationService();

        $allAbonnements = Abonnement::get();
        $user = auth()->user();
        $solde = $allAbonnements->where('user_id', $user->id)->pluck('solde')->first();
        $responses = [];
        $total = 0;
        $userDeviceId = (new Abonnement)->getCurrentWassengerDeviceWithoutAuth($user->id);


        if (Param::getStatusWhatsapp() == 0) {
            return response()->json([
                'status' => 'échec',
                'message' => 'Service WhatsApp désactivé',
            ], 422);
        }
        
        if ($userDeviceId === null) {
            return response()->json([
                'status' => 'error',
                'message' => 'Device introuvable.',
            ], 400); // Code 400 : Bad Request
        }

        $API_KEY_WHATSAPP = Param::getTokenWhatsapp();
        $groupes = explode(',', $request->wid);
        $total = count($groupes) * (new Tarifications)->getWhatsappPrice();

        if ($total > $solde) {
            return response()->json([
                'status' => 'échec',
                'message' => 'Votre solde est insuffisant pour effectuer cette campagne',
                'prix_campagne' => $total,
                'solde' => $solde,
            ], 400);
        }

        $message = Message::create([
            'user_id' => $user->id,
            'ed_reference' => $this->generateHexReference(),
            'title' => 'WA' . '_GROUP ™',
            'message' => $request->message,
            'canal' => 'api group whatsapp',
            'status' => 4,  // status en de depart 
        ]);

        // Facturer la campagne WhatsApp
        Abonnement::factureGroupWhatsapp(count($groupes), $total, $message->id);

        // Débiter le solde de l'utilisateur
        (new Transaction)->__addTransactionAfterSendMessage($user->id, 'debit', $total, $message->id, count($groupes), Abonnement::__getSolde($user->id), null, 'whatsapp');

        $errors = false;

        foreach ($groupes as $groupe) {
            $data = ["group" => $groupe, "message" => $request->message, "device" => $userDeviceId];

            $notification = Notification::create([
                'destinataire' => $groupe,
                'canal' => 'whatsapp',
                'notify' => 4,  // api direct #without cron 
                'chrone' => 4,  // envoi direct #without cron
                'message_id' => $message->id,
            ]);

            $curl = curl_init();
            curl_setopt_array($curl, [
                CURLOPT_URL => "https://api.wassenger.com/v1/messages",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_SSL_VERIFYPEER => false,  // ssl off
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "POST",
                CURLOPT_POSTFIELDS => json_encode($data),
                CURLOPT_HTTPHEADER => [
                    "Content-Type: application/json",
                    "Token: $API_KEY_WHATSAPP",
                ],
            ]); //queued

            $response = curl_exec($curl);
            $err = curl_error($curl);

            curl_close($curl);

            if ($err) {
                $responses[] = "cURL Error #:" . $err; // Si erreur, on l'ajoute à la liste des réponses
            } else {
                $reponse = json_decode($response);
                if (!empty($reponse->id)) {

                    $infoGroup = (new WaGroupController)->getInfoGroup($groupe); // instance

                    if (is_object($infoGroup)) {
                        $notification->delivery_status = 'echec';
                        $notification->save();
                        $name = 'invalid key';
                        // credit
                        Abonnement::creditGroupWhatsapp(1, $message->id);
                    } else {
                        $notification->delivery_status = $reponse->deliveryStatus;
                        $notification->save();
                        $notification->wassenger_id = $reponse->id;
                        $notification->save();
                        $name = $infoGroup;

                        // dd((new WaGroupController)->verifyIsSent('672fc73b1b9cab7266cd44d5')); // instance
                    }

                    $responses[] = [
                        'name' => $name,
                        'wid_group' => $groupe,
                        'detail' => $name !== 'invalid key' ? [json_decode($response)] : null,
                    ];
                } else {
                    $errors = true;
                    $responses[] = [
                        'err' => (new WaGroupController)->getInfoGroup($groupe),
                        'wid_group' => $groupe,
                        'detail' =>  [json_decode($response)],
                    ];
                }
            }
        } 

        $myAbonnements = Abonnement::get(); $addCredit = $myAbonnements->where('user_id', $message->user_id)->first();
        $mydebit = Transaction::get(); $debitClient = $mydebit->where('message_id', $message->id)->first();
        $current_credit = Message::get()->where('id', $message->id)->pluck('credit')->first();

        if ($addCredit && $current_credit) 
        {
            $message->credit = 0; $message->save();
            $addCredit->solde += $current_credit; $addCredit->save(); 
            $debitClient->montant = $total-$current_credit; $debitClient->save();
        }

        $message->status = 6; // Modifier le statut du message à 6 en cas de succès //le status 6 indiques le message est bien envoyé
        $message->save();
        $paginator = $paginate->paginate_resp($responses, $perPage, request('page', 1));

        return response()->json([
            'status' => 'success',
            'message' => 'Message envoyé avec succès',
            'idx' => $message->ed_reference,
            'responses' => $paginator,
            'total_paye' => $total-$current_credit,
            'ancien_solde' => $solde,
            'nouveau_solde' => Abonnement::__getSolde($user->id),
        ], 200);
    }

    // end cusumer Hobotta API
    public function createNotification($isNumeric, $dests, $message, $roleUser, $usrId, $canal) //see
    {
        foreach ($dests as $dest) {
            if ($isNumeric === true) {
                if (!is_numeric($dest)) {
                    $this->canSend = false;
                    return response()->json([
                        'status' => 'error',
                        'message' => 'numéro non valide',
                        'numéro' => $dest,
                    ], 400);
                }
            } else {
                $verify_mail = filter_var($dest, FILTER_VALIDATE_EMAIL);
                if ($verify_mail == false) {
                    $this->canSend = false;
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Email non valide',
                        'email' => $dest,
                    ], 400);
                }
            }

            $message->date_envoie === null
                ?
                Notification::create([
                    'destinataire' => $dest,
                    'canal' => $canal,
                    'message_id' => $message->id,
                ])
                :
                Notification::create([
                    'destinataire' => $dest,
                    'canal' => $canal,
                    'notify' => 2,  //  gateway api  #partenaires 
                    'message_id' => $message->id,
                ]);
        }

        return true;
    }

    public function storeFile($messageId, $files, $user, $service)
    {
        if ($service === false) {

            // Si $files n'est pas un tableau, le convertir en tableau
            if (!is_array($files)) {$files = [$files];}

            if (count($files) > 2) {
                return response()->json(['status' => 'echec', 'message' => 'vous ne pouvez uploader que 2 fichiers maximum']);
            }
            foreach ($files as $file) {
                $extension = $file->getClientOriginalExtension();
                $originalName = $file->getClientOriginalName();
                $size = $file->getSize();
                $mimeType = $file->getMimeType();
                $filename = uniqid() . '.' . $extension;
                $path = $file->storeAs('public/banner/' . $user, $filename);

                // Génération du chemin relatif à partir du chemin de stockage
                $lien = str_replace('public/banner/', '/', $path);
                $file = Fichier::create([
                    'lien' => $lien,
                    'nom' => $originalName,
                    'extension' => $extension,
                    'mime_type' => $mimeType,
                    'taille' => $size,
                    'message_id' => $messageId,
                ]);
            }
        } else {
            $extension = $files->getClientOriginalExtension();
            $originalName = $files->getClientOriginalName();
            $size = $files->getSize();
            $mimeType = $files->getMimeType();
            $path = $files->store('public/banner/' . $user);

            $url = config('app.url');
            if (strpos($url, 'test') != false) {
                $url = '/' . $path;
                $url = asset(str_replace('public', 'public/storage', $url));
            } else {
                $url = '/' . $path;
                $url = asset(str_replace('public', 'storage', $url));
            }

            $file = Fichier::create([
                'lien' => $path,
                'nom' => $originalName,
                'extension' => $extension,
                'mime_type' => $mimeType,
                'taille' => $size,
                'message_id' => $messageId,
            ]);
        }
    }

    public function createMessage($userId, $title, $message, $canal, $dateEnvoie)
    {
        !$dateEnvoie
            ?
            $message = Message::create([
                'user_id' => $userId,
                'ed_reference' => $this->generateHexReference(),
                'title' => $title,
                'message' => $message,
                'status' => 0,
                'canal' => $canal,
            ])
            :
            $message = Message::create([
                'user_id' => $userId,
                'ed_reference' => $this->generateHexReference(),
                'title' => $title,
                'message' => $message,
                'status' => 2,
                'canal' => $canal,
                'date_envoie' => $dateEnvoie,
            ]);
        return $message;
    }

    public function deleteMessage($messageId)
    {
        Message::where('id', $messageId)->delete();
    }

    public function verifySolde(Request $request)
    {
        if (!User::isActivate() && !User::isSuperAdmin()) :
            return response()->json(['status' => 'echec', 'message' => 'Vérifier que votre compte est activé et que vous avez enregistré au moins un service.'], 200);
        endif;
        $solde = (new Abonnement)->getSolde();
        $total = 0;
        $contacts = $request->contacts;
        $whatsapp = $email = $sms = false;
        $smsTotal = $emailTotal = $whatsappTotal = $totalMedia = 0;
        $user = User::getCurrentUSer();
        $canal = '';

        $message = $this->createMessage($user->id, $request->title, $request->message, $request->canal, $request->date_envoie);
        $request->hasFile('banner') ? $this->storeFile($message->id, $request->file('banner'), $user->id, false) : null;

        $allabonnements = Abonnement::get();
        $signature = $allabonnements->where('user_id', $message->user_id);
        $userDeviceId = (new Abonnement)->getCurrentWassengerDeviceWithoutAuth($message->user_id);
        
        try {
            $destinatairesWhatsapp = explode(',', $contacts['whatsapp']);
            $destinatairesEmail = explode(',', $contacts['emails']);
            $destinatairesSms = explode(',', $contacts['sms']);
            
            // Facturer les media WhatsApp
            $totalMedia = count($destinatairesWhatsapp) * (count(Fichier::where('message_id', $message->id)->pluck('lien')) * (new Tarifications)->getWhatsappMediaPrice('media')); 
            
            if ($contacts['whatsapp'] != '') {

                if (Param::getStatusWhatsapp() == 0) {
                    $this->deleteMessage($message->id);
                    return response()->json([
                        'status' => 'echec',
                        'service' => 'error',
                        'message' => 'Service whatsapp désactivé',
                    ]);
                }

                if ($userDeviceId === null) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Device introuvable.',
                    ]);
                }

                // $total += $whatsappTotal = count($destinatairesWhatsapp) * (new Tarifications)->getWhatsappPrice();
                $total += $whatsappTotal = count($destinatairesWhatsapp) * (new Tarifications)->getWhatsappPrice() + $totalMedia;
                $isSendWhatsapp = $this->createWhatsapp($destinatairesWhatsapp, $message);
                if (gettype($isSendWhatsapp) == 'boolean') :
                    $canal .= ' whatsapp ';
                    $whatsapp = true;
                    $this->canSend = true;
                else :
                    $this->deleteMessage($message->id);
                    return $isSendWhatsapp;
                endif;
            }

            if ($contacts['sms'] != '') {

                if (Param::getStatusSms() == 0) {
                    $this->deleteMessage($message->id);
                    return response()->json([
                        'status' => 'echec',
                        'service' => 'error',
                        'message' => 'Service sms désactivé',
                    ]);
                }

                $smsCount = (new SmsCount)->countSmsSend(strip_tags($request->message));
                $total += $smsTotal = ((new Tarifications)->getSmsPrice() * $smsCount) * count($destinatairesSms);
                $isSendSms = $this->createSms($destinatairesSms, $message, $signature->pluck('sms')->first());
                if (gettype($isSendSms) == 'boolean') :
                    $canal .= ' sms ';
                    $sms = true;
                    $this->canSend = true;
                else :
                    $this->deleteMessage($message->id);
                    return $isSendSms;
                endif;
            }

            if ($contacts['emails'] != '') {
                if (Param::getStatusEmail() == 0) {
                    $this->deleteMessage($message->id);
                    return response()->json([
                        'status' => 'echec',
                        'service' => 'error',
                        'message' => 'Service email désactivé',
                    ]);
                }

                if ((Abonnement::getAbo($user->id))->email_status == 0) {
                    $this->deleteMessage($message->id);
                    return response()->json([
                        'status' => 'echec',
                        'service' => 'error',
                        'message' => 'Service email désactivé',
                    ]);
                }

                $data["title"] = $request->title;
                $data["body"] = $request->message;
                $data['template'] = $request->template ?? 0;
                $data['from_name'] = 'BAKOAI';

                $data["localisation"] = $signature->pluck('entreprese_localisation')->first();
                $data["contact"] = $signature->pluck('entreprese_contact')->first();
                $data["from_name"] = $signature->pluck('entreprese_name')->first();
                $data["ville"] = $signature->pluck('entreprese_ville')->first();
                $data["from_email"] = $signature->pluck('email')->first();
                $data["imagePath"] = $signature->pluck('logo')->first();
                $data["mail"] = $signature->pluck('email')->first();

                if ($data["imagePath"] == null || $data["contact"] == null || $data["ville"] == null || $data["mail"] == null || $data["from_name"] == null) {
                    $this->deleteMessage($message->id);
                    return response()->json([
                        'status' => 'echec',
                        'signature' => 'error',
                        'message' => 'paramètre signature vide',
                    ]);
                }

                $total += $emailTotal = count($destinatairesEmail) * (new Tarifications)->getEmailPrice();
                $isSendMail = $this->createEmail($destinatairesEmail, $message, $signature->pluck('email')->first());
                if (gettype($isSendMail) == 'boolean') :
                    $canal .= ' email ';
                    $email = true;
                    $this->canSend = true;
                else :
                    $this->deleteMessage($message->id);
                    return $isSendMail;
                endif;
            }

            $solde = User::isSuperAdmin() ? 10000000000 : $solde;
            if (!$this->canSend) :
                return response()->json(['status' => 'echec', 'message' => 'aucun contacts trouvé']);
            endif;
            if ($total <= $solde) { //ici
                Message::where('id', $message->id)->update(['canal' => $canal]);
                if ($whatsapp) :
                    // Abonnement::factureWhatsapp(count($destinatairesWhatsapp), $whatsappTotal, $message->id);
                    Abonnement::__factureWhatsapp(count($destinatairesWhatsapp), $total,$totalMedia, $message->id);                
                endif;
                if ($sms) :
                    Abonnement::factureSms(count($destinatairesSms), $smsTotal, $message->id, $message->message);
                endif;
                if ($email) :
                    Abonnement::factureEmail(count($destinatairesEmail), $emailTotal, $message->id);
                endif;
                $transaction = (new Transaction)->addTransactionAfterSendMessage('debit', $total, $message->id, $emailTotal, $smsTotal, $whatsappTotal, Abonnement::getSolde());
                return response()->json([
                    'status' => 'success',
                    'message' => 'Votre campagne a été lancée avec succès',
                    'idx' => $message->ed_reference,
                    'total_paye' => $total,
                    'ancien_solde' => $solde,
                    'nouveau_solde' => Abonnement::getSolde(),
                ], 200);
            }

            $this->deleteMessage($message->id);
            return response()->json([
                'status' => 'echec',
                'message' => 'Votre solde est insuffisant pour effectuer cette campagne',
                'prix_whatsapp' => $whatsappTotal,
                'prix_email' => $emailTotal,
                'prix_sms' => $smsTotal,
                'total' => $total,
                'solde' => $solde,
            ]);
        } catch (Exception $th) {
            return $th;
        }
    }

    public function createEmail($destinataires, $message, $email_awt)
    {
        if (!User::isSuperAdmin() && (new Abonnement)->getEmailStatus(auth()->user()->id)->getData()->email_status != 'accepté') {
            return response()->json([
                'status' => 'echec',
                'message' => 'Veuillez configurer votre adresse email de campagne',
            ]);
        }
        Message::where('id', $message->id)->update(['email_awt' => $email_awt]);
        foreach ($destinataires as $dest) {
            $verify_mail = filter_var($dest, FILTER_VALIDATE_EMAIL);
            if (!$verify_mail) {
                $this->canSend = false;
                return response()->json([
                    'status' => 'echec',
                    'message' => 'Email non valide',
                    'email' => $dest,
                ], Response::HTTP_OK);
            }

            $message->date_envoie === null
                ?
                Notification::create([
                    'destinataire' => $dest,
                    'canal' => 'email',
                    'message_id' => $message->id,
                ])
                :
                Notification::create([
                    'destinataire' => $dest,
                    'canal' => 'email',
                    'notify' => 2,  //  gateway api  #partenaires
                    'message_id' => $message->id,
                ]);
        }

        return true;
    }

    public function createWhatsapp($dests, $message) //see
    {
        if (!User::isSuperAdmin() && (new Abonnement)->getWhatsappStatus(auth()->user()->id)->getData()->whatsapp_status != 'accepté') {
            return response()->json([
                'status' => 'echec',
                'message' => 'Veuillez configurer votre numéro whatsapp de campagne',
            ]);
        }

        foreach ($dests as $dest) {
            if (!is_numeric($dest)) {
                $this->canSend = false;
                return response()->json([
                    'status' => 'error',
                    'message' => 'numéro non valide',
                    'numéro' => $this->verifymNumber($dest),
                ], Response::HTTP_OK);
            }

            $message->date_envoie === null
                ?
                Notification::create([
                    'destinataire' => $dest,
                    'canal' => 'whatsapp',
                    'message_id' => $message->id,
                ])
                :
                Notification::create([
                    'destinataire' => $dest,
                    'canal' => 'whatsapp',
                    'notify' => 2,  //  gateway api  #partenaires
                    'message_id' => $message->id,
                ]);
        }

        return true;
    }

    public function createSms($destinataires, $message, $code_textopro)
    {
        if (!User::isSuperAdmin() && (new Abonnement)->getSmsStatus(auth()->user()->id)->getData()->sms_status != 'accepté') {
            return response()->json([
                'status' => 'echec',
                'message' => 'Veuillez configurer votre code sms de campagne',
            ]);
        }

        Message::where('id', $message->id)->update(['code_textopro' => $code_textopro]);
        foreach ($destinataires as $dest) {
            if (!is_numeric($dest) || (strlen($dest)) > 12 || strlen($dest) < 8) {
                $this->canSend = false;
                return response()->json([
                    'status' => 'error',
                    'message' => 'numéro non valide',
                    'numéro' => $this->verifymNumber($dest),
                ], Response::HTTP_OK);
            }

            $message->date_envoie === null
                ?
                Notification::create([
                    'destinataire' => $dest,
                    'canal' => 'sms',
                    'message_id' => $message->id,
                ])
                :
                Notification::create([
                    'destinataire' => $dest,
                    'canal' => 'sms',
                    'notify' => 2,  //  gateway api  #partenaires
                    'message_id' => $message->id,
                ]);
        }

        return true;
    }

    public function create_msg_masse(Request $request)
    {
        if ($request->type == "tous") {

            $expediteur = 'noreply@pvitservice.com';

            if (!empty($request->expediteur)) {

                $expediteur = $request->expediteur;
            }

            $data = $request->only('title', 'message', 'type', 'canal');
            $validator = Validator::make(
                $data,
                [
                    'title' => 'required',
                    'message' => 'required',
                    'canal' => 'required',
                    'type' => 'required',

                ],
                [
                    'title.required' => 'veuillez attribuer un objet au message ',
                    'message.required' => 'veuillez saisir le message à envoyer ',

                ]
            );
            if ($validator->fails()) {
                return response()->json(['message' => $validator->messages()->first(), 'statut' => 'error'], 200);
            }
            $user = JWTAuth::toUser($request->token);
            $message = Message::create([
                'user_id' => $user->id,
                'ed_reference' => $this->generateHexReference(),
                'title' => $request->title,
                'message' => $request->canal == 'whatsapp' || $request->canal == 'SMS' ? strip_tags($request->message) : $request->message,
                'status' => 0,
                'email_awt' => $user->email,
                // 'banner' => $request->banner,
                'destinataires' => $request->type,
                'canal' => $request->canal,
                'slug' => $request->slug,
                'expediteur' => $expediteur,
            ]);

            return response()->json([
                'statut' => 'success',
                'message' => 'Message envoyé avec succes',
            ], Response::HTTP_OK);
        }
    }

    public function sendChrone()
    {
        return response()->json([
            'statut' => 'error',
            'message' => 'service not found',
        ], 400);


        $message = Message::where('status', 0)->first();
        if (!empty($message)) {
            $email_awt = $message->email_awt;
        }
        $users = json_decode($this->getUsers());
        if (!empty($message)) {
            if ($message->destinataires == 'tous') {
                Transaction::update(['status' => $message->id]);
                if ($message->canal == 'email') {
                    Message::where('id', $message->id)->update(['start' => date("Y-m-d H:i:s")]);
                    $files = [];
                    if ($message->banner != '') {
                        foreach ($message->file('banner') as $file) {
                            $filename = basename($message->banner);
                            Storage::disk('local')->put($filename, file_get_contents($message->banner));
                            $path = Storage::path($filename);
                            Fichier::create([
                                'message_id' => $message,
                                'nom' => $filename,
                                'lien' => $path,
                            ]);
                            $files[] = $path;
                        }
                    }

                    if (!empty($users)) {
                        foreach ($users as $user) {
                            $verify_mail = filter_var($user->email, FILTER_VALIDATE_EMAIL);
                            $data["email"] = $verify_mail;
                            $data["title"] = $message->title;
                            $data["body"] = $message->message;
                            $data["from"] = $message->expediteur;
                            if (count($files) != 0) {
                                Mail::send('mail.notification', $data, function ($message) use ($data, $files) {
                                    $message->to($data["email"], $data["email"])
                                        ->subject($data["title"])
                                        ->from($data['from']);

                                    foreach ($files as $file) {
                                        $message->attach($file);
                                    }
                                });
                            } else {
                                Mail::send('mail.notification', $data, function ($message) use ($data) {
                                    $message->to($data["email"], $data["email"])
                                        ->subject($data["title"])
                                        ->from($data['from']);
                                });
                            }

                            Notification::create([
                                'user' => $data["email"],
                                'notify' => 1,
                                'message_id' => $message->id,
                            ]);
                        }

                        return response()->json([
                            'statut' => 'success',
                            'message' => 'Message envoyé avec succes',
                        ], Response::HTTP_OK);
                    } else {
                        $this->update_msg_finish($message->id);
                    }
                } else if ($message->canal == 'whatsapp') {
                    $url = '';
                    $tokenWhatsapp = Param::all()->first();
                    Message::where('id', $message->id)->update(['start' => date("Y-m-d H:i:s")]);
                    sleep(2);
                    //dd($message->banner);
                    if ($message->banner) {
                        foreach ($message->file('banner') as $file) {
                            $data = ["url" => $file];
                            $curl = curl_init();
                            curl_setopt_array($curl, [
                                CURLOPT_URL => "https://api.wassenger.com/v1/files",
                                CURLOPT_RETURNTRANSFER => true,
                                CURLOPT_ENCODING => "",
                                CURLOPT_MAXREDIRS => 10,
                                CURLOPT_TIMEOUT => 30,
                                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                                CURLOPT_CUSTOMREQUEST => "POST",
                                CURLOPT_POSTFIELDS => json_encode($data),
                                CURLOPT_HTTPHEADER => [
                                    "Content-Type: application/json",
                                    "Token: $tokenWhatsapp",
                                ],
                            ]);

                            $response = curl_exec($curl);
                            $err = curl_error($curl);
                            curl_close($curl);
                            if ($err) {
                                echo "cURL Error #:" . $err;
                            } else {
                                $reponse = json_decode($response);
                                // Fichier::create([
                                //     'message_id' => $message->id,
                                //     'nom' => $file,
                                //     'lien' => $reponse->url
                                // ]);
                            }
                        }
                    }
                    if (!$message->banner) {
                        if (!empty($users)) {
                            foreach ($users as $user) {
                                // $number = $this->convertNumbert($user->phone);
                                $number = Convertor::convertNumbert($user->phone);

                                $data = ["phone" => $number, "message" => $message->message];
                                $curl = curl_init();
                                curl_setopt_array($curl, [
                                    CURLOPT_URL => "https://api.wassenger.com/v1/messages",
                                    CURLOPT_RETURNTRANSFER => true,
                                    CURLOPT_SSL_VERIFYPEER => false, //ssl off
                                    CURLOPT_ENCODING => "",
                                    CURLOPT_MAXREDIRS => 10,
                                    CURLOPT_TIMEOUT => 30,
                                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                                    CURLOPT_CUSTOMREQUEST => "POST",
                                    CURLOPT_POSTFIELDS => json_encode($data),
                                    CURLOPT_HTTPHEADER => [
                                        "Content-Type: application/json",
                                        "Token: $tokenWhatsapp",
                                    ],
                                ]);
                                $response = curl_exec($curl);
                                $reponse = json_decode($response);
                                Notification::create([
                                    'destinataire' => $user->phone,
                                    'notify' => 1,
                                    'wassenger_id' => $reponse->id,
                                    'message_id' => $message->id,
                                ]);
                            }
                        } else {
                            sleep(2);//sleep(3);
                            $this->update_msg_finish($message->id);
                        }
                    } else {
                        if (is_array($reponse) == true) {
                            $itemsList = array("file" => $reponse[0]->id);
                        } else {
                            $itemsList = array("file" => $reponse->meta->file);
                        }

                        if (!empty($users)) {
                            foreach ($users as $user) {
                                // $number = $this->convertNumbert($user->phone);
                                $number = Convertor::convertNumbert($user->phone);

                                $data = ["phone" => $number, "message" => strip_tags($message->message), "media" => $itemsList];
                                $curl = curl_init();
                                curl_setopt_array($curl, [
                                    CURLOPT_URL => "https://api.wassenger.com/v1/messages",
                                    CURLOPT_RETURNTRANSFER => true,
                                    CURLOPT_ENCODING => "",
                                    CURLOPT_MAXREDIRS => 10,
                                    CURLOPT_TIMEOUT => 30,
                                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                                    CURLOPT_CUSTOMREQUEST => "POST",
                                    CURLOPT_POSTFIELDS => json_encode($data),
                                    CURLOPT_HTTPHEADER => [
                                        "Content-Type: application/json",
                                        "Token: $tokenWhatsapp",
                                    ],
                                ]);
                                $response = curl_exec($curl);
                                $reponse = json_decode($response);
                                $notif = Notification::create([

                                    'destinataire' => $user->phone,
                                    'notify' => 1,
                                    'wassenger_id' => $reponse->id,
                                    'message_id' => $message->id,
                                ]);
                            }

                            return response()->json([
                                'statut' => 'success',
                                'message' => 'Message envoyé avec succes',
                            ], Response::HTTP_OK);
                        } else {
                            sleep(2);
                            $this->update_msg_finish($message->id);
                        }
                    }
                } else if ($message->canal == 'sms') {
                    if (!empty($users)) {
                        foreach ($users as $user) {
                            // $number = $this->convertNumbert($user->phone);
                            $number = Convertor::convertNumbert($user->phone);

                            $curl = curl_init();
                            curl_setopt_array(
                                $curl,
                                array(
                                    CURLOPT_URL => 'https://textopro.ci/api/send-sms',
                                    CURLOPT_RETURNTRANSFER => true,
                                    CURLOPT_ENCODING => '',
                                    CURLOPT_MAXREDIRS => 10,
                                    CURLOPT_TIMEOUT => 0,
                                    CURLOPT_FOLLOWLOCATION => true,
                                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                                    CURLOPT_CUSTOMREQUEST => 'POST',
                                    CURLOPT_POSTFIELDS => array('email' => 'contact@bakoai.pro', 'password' => 'Banty@192', 'tel' => $number, 'message' => $message->message),
                                )
                            );
                            Notification::create([
                                'destinataire' => $user->phone,
                                'notify' => 1,
                                'message_id' => $message->id,
                            ]);

                            $response = curl_exec($curl);
                            curl_close($curl);
                        }

                        return response()->json([
                            'statut' => 'success',
                            'message' => 'Message envoyé avec succes',
                        ], Response::HTTP_OK);
                    } else {
                        $this->update_msg_finish($message->id);
                    }
                }
            }
        } else {
            return response()->json([
                'statut' => 'error',
                'message' => 'Auncun message a envoyé',
            ], Response::HTTP_OK);
        }
    }


    public function sendNotif()
    {
        $responses = [];
        $allmessages = Message::get();
        $allabonnements = Abonnement::get();
        $allnotifications = Notification::orderBy('created_at', 'asc')->get();
        $API_KEY_WHATSAPP = Param::getTokenWhatsapp();

        $sendAtDate = $allmessages->whereNotNull('date_envoie')->where('status', '2')->take(120); // messages programmés

        if (count($sendAtDate) !== 0) {
            $timestamp = Carbon::parse(now()->format('Y-m-d H:i:s'));
            foreach ($sendAtDate as $activate) {
                if ($timestamp->greaterThan($activate->date_envoie)) {
                    $changeStatusNotif = $allnotifications->where('message_id', $activate->id)->all();
                    foreach ($changeStatusNotif as $notify_me) {
                        $notify_me->notify = 0; $notify_me->save(); // activation du status cron
                    }
                }
            }
        }

       $notifications = Notification::where('notify', 0)
                                  ->where('chrone', 0)
                                  ->orderBy('created_at', 'asc')
                                  ->take(15)
                                //   ->lockForUpdate()
                                  ->get();

        if ($notifications->isEmpty()) 
        { 
            return response()->json([
                'statut' => 'error',
                'message' => 'Aucune notifications disponible pour le moment',
            ], Response::HTTP_OK);
        }
        $errors = false;
        foreach ($notifications as $notification) { 
            $notification->chrone = 1; $notification->save(); // initialise le status cron d'envoi de messages
            
            $message = $allmessages->where('id', $notification->message_id)->first();
            $files = Fichier::where('message_id', $notification->message_id)->pluck('lien');
            $messageToUpdate = $allmessages->where('id', $message->id)->first();
            $messageToUpdate->update(['start' => !empty($messageToUpdate->start) ? $messageToUpdate->start : date("Y-m-d H:i:s")]);

            if (strpos($message->canal, 'email') != false && $notification->canal == 'email') {
                $signature = $allabonnements->where('user_id', $message->user_id);
                $colorTheme = $allabonnements->where('user_id', $message->user_id)->pluck('cs_color')->first();

                $data["mylogo"] = route('users.profile', ['id' => $message->user_id]);
                $data['color_theme'] = $colorTheme;
                $data["email"] = $notification->destinataire;
                $data["title"] = $message->title;
                $data["body"] = $message->message;
                $data["from_email"] = $message->email_awt;
                $data["localisation"] = $signature->pluck('entreprese_localisation')->first();
                $data["contact"] = $signature->pluck('entreprese_contact')->first();
                $data["from_name"] = $signature->pluck('entreprese_name')->first();
                $data["ville"] = $signature->pluck('entreprese_ville')->first();
                $data["mail"] = $signature->pluck('email')->first();

                if (count($files) > 0) 
                {
                    $url = route('files.show', ['folder' => $message->user_id, 'filename'=> basename($files[0])]); 
                    $data["file"] = $url;
                }
                 
                $templateExists = (new Abonnement)->checkIsCustomTemplate($message->user_id) == 1;
                $name_template = '';
                if($templateExists){$name_template = Template::where('user_id', $message->user_id)->pluck('name')->first();}

                $template = $templateExists
                ? "mail.clients.{$message->user_id}.{$name_template}"
                : "mail.campagne";

                try {
                    Mail::send($template, $data, function ($message) use ($data, $files) {
                        $message->to($data["email"])
                            ->subject($data["title"])
                            ->from($data['from_email'], $data['from_name']);
                    });
                    $notification->delivery_status = 'sent';
                    $notification->save();
                } catch (\Exception $e) {

                    $notification->notify = 3; // echec envoi message ?? notify = 3 extrait du passage de la cron 
                    $notification->save();
                    $notification->delivery_status = 'echec';
                    $notification->save();

                    // credit
                    Abonnement::creditEmailWithoutAuth(1, $message->id, $message->user_id);
                }

                $this->update_notification($notification->id);
            } else if (strpos($message->canal, 'whatsapp') != false && $notification->canal == 'whatsapp') {
                if((new Abonnement)->getInternaltional($message->user_id) == 0)
                {
                    $conv = new Convertor();
                    $interphone = $conv->internationalisation($notification->destinataire, request('country', 'GA'));

                    if ($interphone === 'invalid number') 
                    {
                        $notification->destinataire = '0' . $notification->destinataire;
                        $notification->save();
                    }
                }
                
                $isWa = (new WaGroupController())->isExistOnWaWithoutAuth(((new Abonnement)->getInternaltional($message->user_id) == 0) ? $interphone : $notification->destinataire, $message->user_id); //check phone wa_number! 
                if ($isWa != false) {

                    if (count($files) > 0) {
                        // sleep(2);

                        if (strpos($files, '.mp4') != false) {
                            $url = route('files.show', ['folder' => $message->user_id, 'filename'=> basename($files[0])]); 
                        
                            $data = ["phone" => ((new Abonnement)->getInternaltional($message->user_id) == 0) ?$interphone :$notification->destinataire, "message" => strip_tags($message->message), "media" => ["url" => $url], "device" => (new Abonnement)->getCurrentWassengerDeviceWithoutAuth($message->user_id)];
                            $curl = curl_init();
                            curl_setopt_array($curl, [
                                CURLOPT_URL => "https://api.wassenger.com/v1/messages",
                                CURLOPT_RETURNTRANSFER => true,
                                CURLOPT_SSL_VERIFYPEER => false, //ssl off
                                CURLOPT_ENCODING => "",
                                CURLOPT_MAXREDIRS => 10,
                                CURLOPT_TIMEOUT => 30,
                                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                                CURLOPT_CUSTOMREQUEST => "POST",
                                CURLOPT_POSTFIELDS => json_encode($data),
                                CURLOPT_HTTPHEADER => [
                                    "Content-Type: application/json",
                                    "Token: $API_KEY_WHATSAPP",
                                ],
                            ]);

                            $response = curl_exec($curl);
                            $err = curl_error($curl);
                            curl_close($curl);

                            if ($err) {
                                $errors = true;
                                $notification->notify = 3; // echec envoi message ?? notify = 3 extrait du passage de la cron 
                                $notification->save();

                                $notification->delivery_status = 'echec';
                                $notification->save();
                                // credit
                                Abonnement::creditMessageAndMediaWhatsappWithoutAuth(1, $message->id, 1, $message->user_id);

                                $tel = ((new Abonnement)->getInternaltional($message->user_id) == 0) ?$interphone :$notification->destinataire;
                                $responses[] = [
                                    'statut' => 'error',
                                    'message' => "Erreur lors de l'envoi du message à $tel",
                                ];
                                // echo "cURL Error #:" . $err;
                            } else {
                                $reponse = json_decode($response);
                                if (!empty($reponse->id)) {
                                    $this->update_notification_wassenger($notification->id, $reponse->id);
                                }
                            }
                        } else {
                            $url = route('files.show', ['folder' => $message->user_id, 'filename'=> basename($files[0])]); 
                        
                            $data = ["url" => $url];
                            $curl = curl_init();
                            curl_setopt_array($curl, [
                                CURLOPT_URL => "https://api.wassenger.com/v1/files",
                                CURLOPT_RETURNTRANSFER => true,
                                CURLOPT_SSL_VERIFYPEER => false, //ssl off
                                CURLOPT_ENCODING => "",
                                CURLOPT_MAXREDIRS => 10,
                                CURLOPT_TIMEOUT => 30,
                                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                                CURLOPT_CUSTOMREQUEST => "POST",
                                CURLOPT_POSTFIELDS => json_encode($data),
                                CURLOPT_HTTPHEADER => [
                                    "Content-Type: application/json",
                                    "Token: $API_KEY_WHATSAPP",
                                ],
                            ]);

                            $response = curl_exec($curl);
                            $err = curl_error($curl);
                            curl_close($curl);
                            if ($err) {
                                echo "cURL Error #:" . $err;
                            } else {
                                $reponse_banner = json_decode($response);

                                if (is_array($reponse_banner) == true) {
                                    $itemsList = array("file" => $reponse_banner[0]->id);
                                } else {
                                    $itemsList = array("file" => $reponse_banner->meta->file);
                                }
                                // sleep(2);//sleep(3);

                                $data = ["phone" => ((new Abonnement)->getInternaltional($message->user_id) == 0) ?$interphone :$notification->destinataire, "message" => strip_tags($message->message), "media" => $itemsList, "device" => (new Abonnement)->getCurrentWassengerDeviceWithoutAuth($message->user_id)];
                                $curl = curl_init();
                                curl_setopt_array($curl, [
                                    CURLOPT_URL => "https://api.wassenger.com/v1/messages",
                                    CURLOPT_RETURNTRANSFER => true,
                                    CURLOPT_SSL_VERIFYPEER => false, //ssl off
                                    CURLOPT_ENCODING => "",
                                    CURLOPT_MAXREDIRS => 10,
                                    CURLOPT_TIMEOUT => 30,
                                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                                    CURLOPT_CUSTOMREQUEST => "POST",
                                    CURLOPT_POSTFIELDS => json_encode($data),
                                    CURLOPT_HTTPHEADER => [
                                        "Content-Type: application/json",
                                        "Token: $API_KEY_WHATSAPP",
                                    ],
                                ]);

                                $response = curl_exec($curl);
                                $reponse = json_decode($response);
                                $err = curl_error($curl);
                                curl_close($curl);
                                if ($err) {
                                    $errors = true;
                                    $notification->notify = 3; // echec envoi message ?? notify = 3 extrait du passage de la cron 
                                    $notification->save();
                                    $notification->delivery_status = 'echec';
                                    $notification->save();

                                    // credit
                                    Abonnement::creditMessageAndMediaWhatsappWithoutAuth(1, $message->id, count($files), $message->user_id);

                                    $tel = ((new Abonnement)->getInternaltional($message->user_id) == 0) ?$interphone :$notification->destinataire; 
                                    $responses[] = [
                                        'statut' => 'error',
                                        'message' => "Erreur lors de l'envoi du message à $tel",
                                    ];
                                    // echo "cURL Error #:" . $err;
                                } 
                                else 
                                {
                                    $reponse = json_decode($response);
                                    if (!empty($reponse->id)) {
                                        $notification->delivery_status = $reponse->deliveryStatus;
                                        $notification->save();
                                        $this->update_notification_wassenger($notification->id, $reponse->id);
                                    }
                                }
                            } 
                        }
                    } else if (count($files) == 0) {
                        // sleep(2); 
                        $data = ["phone" => ((new Abonnement)->getInternaltional($message->user_id) == 0) ?$interphone :$notification->destinataire, "message" => strip_tags($message->message), "device" => (new Abonnement)->getCurrentWassengerDeviceWithoutAuth($message->user_id)];
                        $curl = curl_init();
                        curl_setopt_array($curl, [
                            CURLOPT_URL => "https://api.wassenger.com/v1/messages",
                            CURLOPT_RETURNTRANSFER => true,
                            CURLOPT_SSL_VERIFYPEER => false, //ssl off
                            CURLOPT_ENCODING => "",
                            CURLOPT_MAXREDIRS => 10,
                            CURLOPT_TIMEOUT => 30,
                            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                            CURLOPT_CUSTOMREQUEST => "POST",
                            CURLOPT_POSTFIELDS => json_encode($data),
                            CURLOPT_HTTPHEADER => [
                                "Content-Type: application/json",
                                "Token: $API_KEY_WHATSAPP",
                            ],
                        ]);

                        $response = curl_exec($curl); //dd($response);
                        $err = curl_error($curl);
                        curl_close($curl);
                        if ($err) 
                        {
                            $errors = true;
                            $notification->notify = 3; // echec envoi message ?? notify = 3 extrait du passage de la cron 
                            $notification->save();
                            $notification->delivery_status = 'echec';
                            $notification->save();

                            // credit
                            Abonnement::creditWhatsappWithoutAuth(1, $message->id, $message->user_id);

                            $tel = ((new Abonnement)->getInternaltional($message->user_id) == 0) ?$interphone :$notification->destinataire;
                            $responses[] = [
                                'statut' => 'error',
                                'message' => "Erreur lors de l'envoi du message à $tel",
                            ];
                            // echo "cURL Error #:" . $err;
                        } else {
                            $reponse = json_decode($response);
                            if (!empty($reponse->id)) {
                                $notification->delivery_status = $reponse->deliveryStatus;
                                $notification->save();
                                $this->update_notification_wassenger($notification->id, $reponse->id);
                            }
                        }
                    } else {
                        return 'nulled';
                    }
                }
                else 
                {
                    $notification->delivery_status = 'echec';
                    $notification->save();
                    // credit
                    Abonnement::creditMessageAndMediaWhatsappWithoutAuth(1, $message->id, count($files), $message->user_id); //rembourse en cas d'echec           
                }

            } else if (strpos($message->canal, 'sms') !== false && $notification->canal === 'sms') { // Utilise `!== false` pour éviter les erreurs avec des positions `0`.
                if((new Abonnement)->getInternaltional($message->user_id) == 0)
                {
                    $conv = new Convertor();
                    $interphone = $conv->internationalisation($notification->destinataire, request('country', 'GA'));

                    if ($interphone === 'invalid number') {
                        $notification->destinataire = '0' . $notification->destinataire;
                        $notification->save();
                    }
                }

                $smsSender = $allabonnements->where('user_id', $message->user_id)->pluck('sms')->first();
                $sender = ($smsSender === 'default') ? strtoupper(Param::getSmsSender()) : strtoupper($smsSender);

                $text = strip_tags($message->message);
                $messageData = [
                    'message' => (new SmsCount)->removeAccents(str_replace('&nbsp;', ' ', $text)),
                    'receiver' => ((new Abonnement)->getInternaltional($message->user_id) == 0) ?$interphone :$notification->destinataire,
                    'sender' => $sender,
                ];


                if ($notification->has_final_status == 1 && $notification->notify == 0 && $notification->chrone == 1) {
                    $curl = curl_init();
                    curl_setopt_array($curl, [
                        CURLOPT_URL => 'https://devdocks.bakoai.pro/api/smpp/send',
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_SSL_VERIFYPEER => false, // off ssl
                        CURLOPT_ENCODING => "",
                        CURLOPT_MAXREDIRS => 10,
                        CURLOPT_TIMEOUT => 30,
                        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                        CURLOPT_CUSTOMREQUEST => "POST",
                        CURLOPT_POSTFIELDS => json_encode($messageData),
                        CURLOPT_HTTPHEADER => [
                            'Authorization: Basic ' . base64_encode('hobotta:hobotta'),
                            'Content-Type: application/json',
                        ],
                    ]);

                    $response = curl_exec($curl);
                    $err = curl_error($curl);
                    curl_close($curl);

                    if ($err) {
                        $errors = true;
                        $notification->notify = 3; // Échec de l'envoi
                        $notification->save();
                        $notification->delivery_status = 'echec';
                        $notification->save();

                        // credit
                        Abonnement::creditSmsWithoutAuth(1, $message->id, $message->user_id);

                        $tel = ((new Abonnement)->getInternaltional($message->user_id) == 0) ?$interphone : $notification->destinataire;
                        $responses[] = [
                            'statut' => 'error',
                            'message' => "Erreur lors de l'envoi du message à $tel",
                        ];
                    } else {
                        $reponse = json_decode($response);
                        if (isset($reponse->status_code) && $reponse->status_code == "0") {
                            $this->update_notification_smsApi($notification->id);
                            $notification->delivery_status = 'sent';
                            $notification->save();
                        }
                    }
                }

                $notification->has_final_status = 1;
                $notification->save();

                sleep(1); // Ajout d'une pause de 1 secondes avant de poursuivre
            } else {
                return response()->json([
                    'status' => 'error cron',
                ], 200);
            }

            if ($errors) {
                $message->status = 5; // Modifier le statut du message à 5 en cas d'erreur //le status 5 indiques le message non envoyé
                $message->save();

                return response()->json([
                    'status' => 'error',
                    'message' => 'Des erreurs sont survenues lors de l\'envoi de certains messages.',
                    'details' => $responses,
                ], 500); 
            }
        }
        sleep(1);
        $this->update_msg_finish($message->id); 
    }

    public function getUsers()
    {
        $curl = curl_init();
        curl_setopt_array(
            $curl,
            array(
                CURLOPT_URL => 'https://devtests.bakoai.pro:7443/pvitservice/api/get-user-notify',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'GET',
            )
        );
        $response = curl_exec($curl);
        curl_close($curl);
        return $response;
    }

    public function getApiWhatsapp()
    {
        $API_KEY_WHATSAPP = Param::all()->first();
        return $API_KEY_WHATSAPP;
    }

    public function update_msg_finish($id)
    {
        Message::where('id', $id)->update(['status' => 1, 'finish' => date("Y-m-d H:i:s")]);
        $curl = curl_init();
        curl_setopt_array(
            $curl,
            array(
                CURLOPT_URL => 'http://devtests.bakoai.pro/pvitservice/api/get-user-notify-update',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'GET',
            )
        );
        $response = curl_exec($curl);
        curl_close($curl);
    }

    public function verify_reception()
    {
        $API_KEY_WHATSAPP = Param::getTokenWhatsapp();
        $messages = Message::all('canal', 'id');

        foreach ($messages as $message) {
            if (strpos($message->canal, 'whatsapp') != false) {
                $notifications = Notification::where('message_id', $message->id)->where('canal', 'whatsapp')->where('has_final_status', 0)->take(100)->get();

                $ids = array();
                for ($i = 0; $i < count($notifications); ++$i) {
                    $ids[] = $notifications[$i]->wassenger_id;
                }
                $all_ids = implode(",", $ids);
                if (!empty($all_ids)) {
                    $curl = curl_init();
                    curl_setopt_array($curl, [
                        CURLOPT_URL => "https://api.wassenger.com/v1/messages?ids=$all_ids",
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_SSL_VERIFYPEER => false, //ssl off
                        CURLOPT_ENCODING => "",
                        CURLOPT_MAXREDIRS => 10,
                        CURLOPT_TIMEOUT => 30,
                        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                        CURLOPT_CUSTOMREQUEST => "GET",
                        CURLOPT_HTTPHEADER => [
                            "Content-Type: application/json",
                            "Token: $API_KEY_WHATSAPP",
                        ],
                    ]);
                    $response = curl_exec($curl);
                    $reponses = json_decode($response);

                    if (count($reponses) != 0) {
                        foreach ($reponses as $reponse) {
                            foreach ($notifications as $notification) {
                                if ($reponse->id == $notification->wassenger_id) {
                                    Notification::where('message_id', $message->id)->where('canal', 'whatsapp')->update(['delivery_status' => $reponse->deliveryStatus, 'has_final_status' => 1]);
                                }
                            }
                        }
                    }
                } else {
                    Message::where('id', $message->id)->update(['verify' => 1]);
                }
            }
        }
    }

    public function get_user_wassenger(Request $request)
    {
        $message = Message::where('slug', $request->id)->first();
    }

    public function update_notification($id)
    {
        Notification::where('id', $id)->update(['notify' => 1]);
    }

    public function update_notification_wassenger($id, $wassenger_id)
    {
        Notification::where('id', $id)->update(['notify' => 1, 'wassenger_id' => $wassenger_id, 'has_final_status' => 1]);
    }

    public function update_notification_smsApi($id)
    {
        Notification::where('id', $id)->update(['notify' => 1]);
    }

    public function relancer(Request $request)
    {
        $message = Message::where('slug', $request->id)->first();
        $notifications = Notification::where('message_id', $message->id)->get();
        return $notifications;
    }

    public function format_date($dat)
    {
        $date = date_create($dat);
        $newdate = date_format($date, 'Y-m-d H:i:s.ms_');
        $string = str_replace(' ', 'T', $newdate); // Replaces all spaces with hyphens.
        $newString = str_replace('_', 'Z', $string);
        return $newString;
    }

    public static function convertNumbert($number, $indicatif = 241)
    {
        $numberIndicatif = substr($number, 0, strlen($indicatif));
        if ($numberIndicatif == $indicatif) {
            return $number;
        } else {
            if ($indicatif == 241) {
                if (substr($number, 0, 1) == 0) {
                    $number = $indicatif . substr($number, 1);
                    return $number;
                } else {
                    return $number;
                }
            }
        }
    }

    public static function verifymNumber($number)
    {
        if (strlen($number) == 8) {
            $number = '0' . $number;
            return $number;
        } else {
            return 'destinataire incorrect';
        }
    }

    public function generateHexReference()
    {
        $reference = bin2hex(random_bytes(12));
        return $reference;
    }

    public function getStatus()
    {
        $user = auth()->user();
        // $notifications = Notification::where('message_id', $message->id)->where('canal', 'whatsapp')->where('has_final_status', 0)->take(100)->get();
    }

    public function send_notification(Request $request)
    {
        $json = file_get_contents('php://input');
        $data = json_decode($json);
        if (!isset($data->app_id)) {
            return response()->json(array("errors" => " YOU MUST PROVIDE APP_ID"));
        }
        if (!isset($data->users)) {
            return response()->json(array("errors" => " YOU MUST PROVIDE USERS"));
        }

        if (!isset($data->channel_id)) {
            return response()->json(array("errors" => " YOU MUST PROVIDE CHANNELS ID"));
        }

        if (!isset($data->api_rest_key)) {
            return response()->json(array("errors" => " YOU MUST PROVIDE APP_REST_KEY"));
        }

        if (!isset($data->title)) {
            return response()->json(array("errors" => " YOU MUST PROVIDE TITLE"));
        }

        if (!isset($data->body)) {
            return response()->json(array("errors" => " YOU MUST PROVIDE CONTENT MESSAGE"));
        }

        $json_send = array();
        $json_send["app_id"] = $data->app_id;
        if ($data->users == "all") {
            $json_send["included_segments"] = ["Subscribed Users"];
        } else {
            if (is_array($data->users)) {
                $json_send["include_player_ids"] = $data->users;
            } else {
                $json_send["include_player_ids"] = [$data->users];
            }
        }

        $json_send["content_available"] = true;
        $json_send["android_channel_id"] = $data->channel_id;
        $json_send["headings"] = array("en" => $data->title, "fr" => $data->title);
        $json_send["contents"] = array("en" => $data->body, "fr" => $data->body);
        if (isset($data->data)) {
            $json_send["data"] = $data->data;
        }

        $curl = curl_init();
        curl_setopt_array(
            $curl,
            array(
                CURLOPT_URL => 'https://onesignal.com/api/v1/notifications',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => json_encode($json_send),
                CURLOPT_HTTPHEADER => array(
                    'Authorization: Basic ' . $data->api_rest_key,
                    'Content-Type: application/json',
                ),
            )
        );

        $response = curl_exec($curl);
        return response()->json(json_decode($response));
    }
}