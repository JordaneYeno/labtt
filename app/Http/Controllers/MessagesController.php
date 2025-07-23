<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Mail\SendDiffusion;
use App\Models\Message;
use App\Models\Notification;
use App\Models\Param;
use App\Models\User;
use App\Services\PaginationService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use JWTAuth;
use Symfony\Component\HttpFoundation\Response;


class MessagesController extends Controller
{
    protected $user;

    public function __construct()
    {

        $this->user = JWTAuth::parseToken()->authenticate();
    }

    public function returnId()
    {
        return User::find(8)->messages;
    }

    public function sendMessageSimple(Request $request)
    {

        if ($request->canal == 'email') {

            $users = (explode(",", $request->contacts));

            $data = $request->only('title', 'message', 'contacts', 'canal');
            $validator = Validator::make(
                $data,
                [
                    'title' => 'required',
                    'message' => 'required',
                    'canal' => 'required',
                    'contacts' => 'required',
                ],
                [
                    'title.required' => 'veuillez attribuer un objet au message ',
                    'message.required' => 'veuillez saisir le message à envoyer ',
                    'contacts.required' => 'veuillez saisir à qui le message est distiné',

                ]
            );

            foreach ($users as $user) {

                $valid = $this->validEmail($user);

                if ($valid == false) {

                    return response()->json([
                        'statut' => 'error',
                        'message' => 'Email non valide',
                    ], Response::HTTP_OK);
                }
            }

            //Send failed response if request is not valid

            if ($validator->fails()) {
                return response()->json(['message' => $validator->messages()->first(), 'statut' => 'error'], 200);
            }

            //Request is valid, create new message
            $token = $request->token;
            $user = JWTAuth::toUser($token);

            Message::create([
                'user_id' => $user->id,
                'title' => $request->title,
                'message' => $request->canal == 'whatsapp' || $request->canal == 'SMS' ? strip_tags($request->message) : $request->message,
                'status' => 1,
                'destinataires' => $request->contacts,
                'canal' => $request->canal,
            ]);

            $details = [
                'title' => $request->title,
                'message' => $request->message,
            ];

            $mail_copie = $request->mail_copie;
            $data["email"] = $request->contacts;
            $data["expediteur"] = $request->expediteur;
            $data["title"] = $request->title;
            $data["body"] = $request->message;
            $data['mail_copie'] = $mail_copie;

            foreach ($users as $user) {
                Mail::send('mail.send_mail', $data, function ($message) use ($data) {
                    $message->to($data["email"], $data["email"])
                        ->cc('christophe@yopmail.com')
                        ->subject($data["title"])
                        ->from($data['expediteur']);
                });
            }

            return response()->json([
                'statut' => 'success',
                'message' => 'Message created successfully',
            ], Response::HTTP_OK);
        } elseif ($request->canal == 'whatsapp') {

            $prefix = 'http://devtests.bakoai.pro/pvitservice';

            $url = '';

            $API_KEY_WHATSAPP = Param::all()->first();

            if ($request->banner) {

                $data = ["url" => $request->banner];
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
                        "Token: $API_KEY_WHATSAPP->token",
                    ],
                ]);

                $response = curl_exec($curl);
                $err = curl_error($curl);
                curl_close($curl);
                if ($err) {
                    echo "cURL Error #:" . $err;
                } else {

                    $reponse = json_decode($response);
                }
            }

            $users = (explode(",", $request->contacts));

            $data = $request->only('title', 'message', 'contacts', 'canal');
            $validator = Validator::make(
                $data,
                [
                    'title' => 'required',
                    'message' => 'required',
                    'canal' => 'required',
                    'contacts' => 'required',

                ],
                [
                    'title.required' => 'veuillez attribuer un objet au message ',
                    'message.required' => 'veuillez saisir le message à envoyer ',
                    'contacts.required' => 'veuillez saisir à qui le message est distiné',

                ]
            );

            //Send failed response if request is not valid

            if ($validator->fails()) {
                return response()->json(['message' => $validator->messages()->first(), 'statut' => 'error'], 200);
            }

            $users = (explode(",", $request->contacts));

            foreach ($users as $user) {

                if (!is_numeric($user) || strlen($user) > 12) {

                    return response()->json([
                        'statut' => 'error',
                        'message' => 'Numero invalide',
                    ], Response::HTTP_OK);
                }
            }

            $user = JWTAuth::toUser($request->token);

            Message::create([
                'user_id' => $user->id,
                'title' => $request->title,
                'message' => $request->canal == 'whatsapp' || $request->canal == 'SMS' ? strip_tags($request->message) : $request->message,
                'status' => 1,
                'banner' => $url,
                'destinataires' => $request->contacts,
                'canal' => $request->canal,
            ]);

            //Request is valid, create new message

            if (!$request->banner) {

                foreach ($users as $user) {

                    $data = ["phone" => $user, "message" => strip_tags($request->message)];
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
                            "Token: $API_KEY_WHATSAPP->token",
                        ],
                    ]);

                    $response = curl_exec($curl);
                }

                $err = curl_error($curl);
                curl_close($curl);
                if ($err) {
                    echo "cURL Error #:" . $err;
                } else {

                    $message = json_decode($response);

                    Notification::create([

                        'user' => $user,
                        'id_message' => $message->id,
                    ]);

                    if (!empty($reponse->id)) {

                        return response()->json([
                            'statut' => 'success',
                            'message' => 'Message envoyé avec succes',
                        ], Response::HTTP_OK);
                    } else {

                        return response()->json([
                            'statut' => 'error',
                            'message' => 'Message non envoyé',
                        ], Response::HTTP_OK);
                    }
                }
            } else {

                if (is_array($reponse) == true) {

                    $itemsList = array("file" => $reponse[0]->id);
                } else {

                    $itemsList = array("file" => $reponse->meta->file);
                }

                foreach ($users as $user) {

                    $data = ["phone" => $user, "message" => strip_tags($request->message), "media" => $itemsList];
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
                            "Token: $API_KEY_WHATSAPP->token",
                        ],
                    ]);

                    $response = curl_exec($curl);
                }
                $err = curl_error($curl);
                curl_close($curl);
                if ($err) {
                    echo "cURL Error #:" . $err;
                } else {

                    $message = json_decode($response);
                    Notification::create([
                        'user' => $user,
                        'id_message' => $message->id,
                    ]);

                    if (!empty($reponse->id)) {
                        return response()->json([
                            'statut' => 'success',
                            'message' => 'Message envoyé avec succes',
                        ], Response::HTTP_OK);
                    } else {

                        return response()->json([
                            'statut' => 'error',
                            'message' => 'Message non envoyé',
                        ], Response::HTTP_OK);
                    }

                    //return $response;
                }
            }
        } else {

            $users = (explode(",", $request->contacts));

            $data = $request->only('title', 'message', 'contacts', 'canal');
            $validator = Validator::make(
                $data,
                [
                    'title' => 'required',
                    'message' => 'required',
                    'canal' => 'required',
                    'contacts' => 'required',

                ],
                [
                    'title.required' => 'veuillez attribuer un objet au message ',
                    'message.required' => 'veuillez saisir le message à envoyer ',
                    'contact.required' => 'veuillez saisir à qui le message est distiné',

                ]
            );

            //Send failed response if request is not valid
            if ($validator->fails()) {
                return response()->json(['message' => $validator->messages()->first(), 'statut' => 'error'], 200);
            }

            $users = (explode(",", $request->emails));

            foreach ($users as $user) {

                if (!is_numeric($user) || strlen($user) > 8) {

                    return response()->json([
                        'statut' => 'error',
                        'message' => 'Numero invalide',
                    ], Response::HTTP_OK);
                }
            }

            $user = JWTAuth::toUser($request->token);

            Message::create([
                'user_id' => $user->id,
                'title' => $request->title,
                'message' => $request->canal == 'whatsapp' || $request->canal == 'SMS' ? strip_tags($request->message) : $request->message,
                'status' => 1,
                'destinataires' => $request->emails,
                'canal' => $request->canal,

            ]);

            foreach ($users as $user) {

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
                        CURLOPT_POSTFIELDS => array('email' => 'contact@bakoai.pro', 'password' => 'Banty@192', 'tel' => $user, 'message' => $request->message),
                    )
                );

                $response = curl_exec($curl);

                curl_close($curl);
            }

            $response = curl_exec($curl);
            $err = curl_error($curl);
            curl_close($curl);
            if ($err) {
                echo "cURL Error #:" . $err;
            } else {

                return $response;
            }
        }
    }

    public function send(Request $request)
    {
        $prefix = 'http://devtests.bakoai.pro/pvitservice';
        $url = '';
        $token = $this->_getToken();

        $API_KEY_WHATSAPP = Param::all()->first();

        if ($request->banner) {

            $data = ["url" => $request->banner];
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

                    "Token: $API_KEY_WHATSAPP->token",
                ],
            ]);

            $response = curl_exec($curl);
            $err = curl_error($curl);
            curl_close($curl);
            if ($err) {
                echo "cURL Error #:" . $err;
            } else {

                $reponse = json_decode($response);
            }
        }

        $users = (explode(",", $request->contacts));

        $data = $request->only('title', 'message', 'contacts');
        $validator = Validator::make(
            $data,
            [
                'title' => 'required',
                'message' => 'required',
                'contacts' => 'required',

            ],
            [
                'title.required' => 'veuillez attribuer un objet au message ',
                'message.required' => 'veuillez saisir le message à envoyer ',
                'contacts.required' => 'veuillez saisir à qui le message est distiné',

            ]
        );

        //Send failed response if request is not valid
        if ($validator->fails()) {
            return response()->json(['message' => $validator->messages()->first(), 'statut' => 'error'], 200);
        }

        $users = (explode(",", $request->contacts));

        foreach ($users as $user) {

            if (!is_numeric($user)) {

                return response()->json([
                    'statut' => 'error',
                    'message' => 'Numero invalide',
                ], Response::HTTP_OK);
            }
        }

        $user = JWTAuth::toUser($token);

        $message = Message::create([
            'user_id' => $user->id,
            'title' => $request->title,
            'message' => strip_tags($request->message),
            'status' => 1,
            'banner' => $url,
            'destinataires' => $request->contacts,
            'canal' => 'whatsapp'
        ]);

        // $totalSold = (new Tarifications)->getWhatsappPrice() * count($users);
        // (new Abonnement)->decreditSolde($totalSold);
        // $transaction = (new Transaction)->addTransactionAfterSendMessage('debit', $totalSold, $message->id);
        // return $transaction;

        //Request is valid, create new message

        if (!$request->banner) {

            foreach ($users as $user) {

                $number = $this->convertNumbert($user);

                $data = ["phone" => $number, "message" => strip_tags($request->message)];
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
                        "Token: $API_KEY_WHATSAPP->token",
                    ],
                ]);

                $response = curl_exec($curl);
            }

            $err = curl_error($curl);
            curl_close($curl);
            if ($err) {
                echo "cURL Error #:" . $err;
            } else {

                $reponse = json_decode($response);

                if (!empty($reponse->id)) {

                    //dd($response);

                    return response()->json([
                        'statut' => 'success',
                        'message' => 'Message envoyé avec succes',
                    ], Response::HTTP_OK);
                } else {

                    return response()->json([
                        'statut' => 'error',
                        'message' => 'Message non envoyé',
                    ], Response::HTTP_OK);
                }
            }
        } else {

            if (is_array($reponse) == true) {

                $itemsList = array("file" => $reponse[0]->id);
            } else {

                $itemsList = array("file" => $reponse->meta->file);
            }

            foreach ($users as $user) {

                $number = $this->convertNumbert($user);

                $data = ["phone" => $number, "message" => strip_tags($request->message), "media" => $itemsList];
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
                        "Token: $API_KEY_WHATSAPP->token",
                    ],
                ]);

                $response = curl_exec($curl);
            }
            $err = curl_error($curl);
            curl_close($curl);
            if ($err) {
                echo "cURL Error #:" . $err;
            } else {

                $message = json_decode($response);
                Notification::create([

                    'user' => $user,
                    'id_message' => $message->id,
                ]);

                if (!empty($reponse->id)) {
                    return response()->json([
                        'statut' => 'success',
                        'message' => 'Message envoyé avec succes',
                    ], Response::HTTP_OK);
                } else {

                    return response()->json([
                        'statut' => 'error',
                        'message' => 'Message non envoyé',
                    ], Response::HTTP_OK);
                }

                //return $response;
            }
        }
    }

    public function sendSMS(Request $request)
    {

        $token = $this->_getToken();

        $users = (explode(",", $request->contacts));

        $data = $request->only('title', 'message', 'contacts');
        $validator = Validator::make(
            $data,
            [
                'title' => 'required',
                'message' => 'required',
                'contacts' => 'required',

            ],
            [
                'title.required' => 'veuillez attribuer un objet au message ',
                'message.required' => 'veuillez saisir le message à envoyer ',
                'contacts.required' => 'veuillez saisir à qui le message est distiné',

            ]
        );

        //Send failed response if request is not valid

        if ($validator->fails()) {
            return response()->json(['message' => $validator->messages()->first(), 'statut' => 'error'], 200);
        }

        //Request is valid, create new product

        $user = JWTAuth::toUser($token);

        $message = Message::create([
            'user_id' => $user->id,
            'title' => $request->title,
            'message' => strip_tags($request->message),
            'status' => 1,
            'banner' => null,
            'destinataires' => $request->contacts,
            'canal' => 'sms',

        ]);

        foreach ($users as $user) {

            if (!is_numeric($user)) {

                return response()->json([
                    'statut' => 'error',
                    'message' => 'Numero invalide',
                ], Response::HTTP_OK);
            }

            $number = $this->convertNumbert($user);

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
                    CURLOPT_POSTFIELDS => array('email' => 'contact@bakoai.pro', 'password' => 'Banty@192', 'tel' => $number, 'message' => strip_tags($request->message)),
                )
            );

            $response = curl_exec($curl);

            curl_close($curl);

            return $response;
        }
    }

    public function sendMailAllExchange(Request $request)
    {

        $expediteur = 'noreply@pvitservice.com';

        if (!empty($request->expediteur)) {

            $expediteur = $request->expediteur;
        }

        $users = (explode(",", $request->contacts));
        $data = $request->only('title', 'message', 'contacts');
        $validator = Validator::make(
            $data,
            [
                'title' => 'required',
                'message' => 'required',
                'contacts' => 'required',
            ],
            [
                'title.required' => 'veuillez attribuer un objet au message ',
                'message.required' => 'veuillez saisir le message à envoyer ',
                'contacts.required' => 'veuillez saisir à qui le message est distiné',

            ]
        );
        foreach ($users as $user) {

            $verify_mail = filter_var($user, FILTER_VALIDATE_EMAIL);
            if ($verify_mail == false) {

                return response()->json([
                    'statut' => 'error',
                    'message' => 'Email non valide',
                ], Response::HTTP_OK);
            }
        }
        //Send failed response if request is not valid

        if ($validator->fails()) {
            return response()->json(['message' => $validator->messages()->first(), 'statut' => 'error'], 200);
        }
        //Request is valid, create new message

        $token = $request->token;
        $user = JWTAuth::toUser($token);

        // Message::create([
        //     'user_id' => $user->id,
        //     'title' => $request->title,
        //     'message' => strip_tags($request->message),
        //     'status' => 1,
        //     'banner' => $url,
        //     'destinataires' => $request->contacts,
        //     'canal' => 'whatsapp',

        // ]);

        $message = Message::create([
            'user_id' => $user->id,
            'title' => $request->title,
            'message' => $request->canal == 'whatsapp' || $request->canal == 'SMS' ? strip_tags($request->message) : $request->message,
            'status' => 1,
            'destinataires' => $request->contacts,
            'canal' => 'email',

        ]);

        $details = [

            'title' => $request->title,
            'message' => $request->message,
            'expediteur' => $expediteur,
        ];

        foreach ($users as $user) {
            Mail::to($user)->send(new SendDiffusion($details, $details['title'], 'mail/mail_exchange_difusion'));
        }

        return response()->json([
            'statut' => 'success',
            'message' => 'Message created successfully',
        ], Response::HTTP_OK);
    }

    public function sendMail(Request $request)
    {
        $expediteur = 'noreply@pvitservice.com';
        $mail_copie = [];
        $token = $this->_getToken();

        if (!empty($request->mail_copie)) {
            $mail_copie = explode(",", $request->mail_copie);
        }

        if (!empty($request->expediteur)) {
            $expediteur = $request->expediteur;
        }

        $users = (explode(",", $request->contacts));
        $data = $request->only('title', 'message', 'contacts');
        $validator = Validator::make(
            $data,
            [
                'title' => 'required',
                'message' => 'required',
                'contacts' => 'required',
            ],
            [
                'title.required' => 'veuillez attribuer un objet au message ',
                'message.required' => 'veuillez saisir le message à envoyer ',
                'contacts.required' => 'veuillez saisir à qui le message est distiné',

            ]
        );
        foreach ($users as $user) {
            $verify_mail = filter_var($user, FILTER_VALIDATE_EMAIL);
            if ($verify_mail == false) {
                return response()->json([
                    'statut' => 'error',
                    'message' => 'Email non valide',
                ], Response::HTTP_OK);
            }
        }

        if ($validator->fails()) {
            return response()->json(['message' => $validator->messages()->first(), 'statut' => 'error'], 200);
        }

        $user = JWTAuth::toUser($token);

        $message = Message::create([
            'user_id' => $user->id,
            'title' => $request->title,
            'message' => $request->canal == 'whatsapp' || $request->canal == 'SMS' ? strip_tags($request->message) : $request->message,
            'status' => 1,
            'destinataires' => $request->contacts,
            'canal' => 'email',
        ]);

        $data["email"] = $request->contacts;
        $data["expediteur"] = $expediteur;
        $data["title"] = $request->title;
        $data["body"] = $request->message;
        $data['mail_copie'] = $mail_copie;
        $data['template'] = $request->template ?? 0;

        foreach ($users as $user) {
            Mail::send('mail.send_mail', $data, function ($message) use ($data) {
                $message->to($data["email"], $data["email"])
                    ->cc($data['mail_copie'])
                    ->subject($data["title"])
                    ->from($data['expediteur']);
            });
        }
        return response()->json([
            'statut' => 'success',
            'message' => 'Message created successfully',
        ], Response::HTTP_OK);
    }

    public function _getToken()
    {
        /**/
        $curl = curl_init();

        curl_setopt_array(
            $curl,
            array(
                CURLOPT_URL => 'https://devtests.bakoai.pro:7443/apinotifsv2/public/api/connexion',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => array('email' => 'teste01@gmail.com', 'password' => '123456'),
            )
        );

        $response = curl_exec($curl);
        $reponse = json_decode($response);

        // echo "<pre>";
        // print_r($reponse->user);
        // echo "</pre>";

        // die();
        curl_close($curl);

        return ($response);


        // $user = User::where('email', 'christ@gmail.com')->first();

        // return $user;
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


    public function getAllMessages(Request $request) 
    {
        try {
            $paginate = new PaginationService();
            $messages = Message::leftJoin('users', 'users.id', '=', 'messages.user_id')
                ->select(
                    'messages.id',
                    'users.name',
                    'messages.message',
                    'messages.created_at',
                    'messages.canal',
                    'messages.date_envoie',
                    'messages.title',
                    'messages.destinataires',
                )
                ->orderBy('created_at', 'DESC');
            if ($messages->get()->isEmpty()) {
                return response()->json([
                    'statut' => 'result error',
                    'message' => 'no result found'
                ], 200);
            }
            return response()->json([
                'statut' => 'success',
                // 'message' => count($messages->get())." message(s) found",
                "response" => $paginate->setPaginate($messages, $request->perPage),
            ], 200);
        } catch (Throwable $th) {
            return response()->json([
                'statut' => 'error',
                'message error' => $th
            ], 500);
        }
    }


    public function getMessagesByReferenceId(Request $request)
    {
        if (!empty($request->refkey)) :
            $paginate = new PaginationService();
            $searchrefkey = $request->refkey;
            $messages = Message::where('ed_reference', 'like', '%' . $searchrefkey . '%')
                ->select(
                    // 'messages.id',
                    // 'messages.date_envoie',
                    // 'messages.destinataires',
                    'messages.title',
                    'messages.canal',
                    'messages.message',
                    'messages.ed_reference',
                    'messages.created_at'
                )->paginate(25);

            return response()->json([
                'status' => 'success',
                "response" => $messages,
            ], 200);
        else :
            return response()->json([
                'status' => 'echec',
                'message' => 'veuillez saisir la reference'
            ], 200);
        endif;
    }

    public function getMessagesByCanal(Request $request)
    {
        if (!empty($request->canal)) :
            $paginate = new PaginationService();
            $searchCanal = $request->canal;
            $messages = Message::where('canal', 'like', '%' . $searchCanal . '%')
                ->select(
                    'messages.id',
                    'messages.message',
                    'messages.canal',
                    'messages.date_envoie',
                    'messages.title',
                    'messages.destinataires',
                    'messages.created_at'
                )->paginate(25);

            return response()->json([
                'status' => 'success',
                // 'message' => count($messages->get())." message(s) found",
                "response" => $messages,
            ], 200);
        else :
            return response()->json([
                'status' => 'echec',
                'message' => 'veuillez saisir un canal'
            ], 200);
        endif;
    }

    public function getMessagesByPeriod(Request $request)
    {
        if (!empty($request->dateStart) && !empty($request->dateEnd)) {
            try {
                $dateStart = $request->dateStart;
                $dateEnd = $request->dateEnd;
                if ($dateStart > $dateEnd) {
                    return response()->json([
                        'status' => 'erreur',
                        'message' => 'la date de début doit être inférieure à la date de fin.'
                    ], 200);
                }
                $messages = DB::table('messages')
                    ->where('messages.created_at', '>=', $dateStart . ' 00:00:00')
                    ->where('messages.created_at', '<=', $dateEnd . ' 23:59:59')
                    ->select(
                        'messages.id',
                        'messages.message',
                        'messages.created_at',
                        'messages.canal',
                        'messages.date_envoie',
                        'messages.title',
                        'messages.destinataires'
                    )
                    ->orderBy('created_at', 'DESC')
                    ->paginate(25);
                return response()->json([
                    'status' => 'success',
                    // 'message' => count($messages->get())." message(s) found",
                    "response" => $messages,
                ], 200);
            } catch (Exception $th) {
                return response()->json([
                    'status' => 'error',
                    'message' => $th
                ]);
            }
        } else {
            return response()->json([
                'status' => 'error',
                'message' => 'veuillez envoyer un interval de date correct'
            ], 200);
        }
    }

    public function getMessagesByKeywordCanal(Request $request)
    {
        if (!empty($request->search)) :
            $canal = $request->canal;
            try {
                $searchCanal = $request->search;
                $messages = DB::table('messages')
                    ->where('messages.canal', 'like', '%' . $request->canal . '%')
                    ->where(function ($query) use ($searchCanal) {
                        $query->orwhere('messages.title', 'like', '%' . $searchCanal . '%')
                            ->orwhere('messages.destinataires', 'like', '%' . $searchCanal . '%')
                            ->orwhere('messages.message', 'like', '%' . $searchCanal . '%');
                    })
                    ->select(
                        'messages.id',
                        'messages.message',
                        'messages.created_at',
                        'messages.canal',
                        'messages.date_envoie',
                        'messages.title',
                        'messages.destinataires'
                    )
                    ->orderBy('created_at', 'DESC')
                    ->paginate(25);
                return response()->json([
                    'status' => 'success',
                    'response' => $messages,
                ], 200);
            } catch (Throwable $th) {
                return response()->json([
                    'status' => 'error',
                    'message error' => $th
                ]);
            }
        else :
            return response()->json([
                'statut' => 'params error',
                'message' => 'please send a valid params'
            ], 200);;
        endif;
    }
}
