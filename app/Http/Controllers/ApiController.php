<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\Convertor;
use Illuminate\Support\Str;
use Mail;

use App\Models\Abonnement;
use App\Models\Param;
use Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use JWTAuth;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Exceptions\JWTException;


/*
 *
 *
 * @property PaginationService $paginate
 *
 */

class ApiController extends Controller
{
    public function checkPassword(Request $request)
    {
        $data = $request->only('password');
        $validator = Validator::make(
            $data,
            ['password' => 'required'],
            ['password.required' => 'veuillez saisir votre code d\'indentification']
        );
        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->messages()->first()], 200);
        }

        $hashedPassword =  User::where('id', auth()->user()->id)->first('password')->password;
        if (Hash::check($request->password, $hashedPassword)) {
            return response()->json([
                'item' => 1,
                'status' => 'success',
                'message' => 'correct',
            ], Response::HTTP_OK);
        } else {
            return response()->json([
                'status' => 'error',
                'message' => 'mot de passe incorrect',
            ], Response::HTTP_OK);
        }
    }

    public function setSlugGenerate()
    {
        $slug = User::where('id', auth()->user()->id)->first();
        if (isset($slug)) {
            $slug->update(['slug' => Str::random(32)]);
            return response()->json([
                'status' => 'succes',
                'message' => "nouveau slug",
                'change' => 1,
            ], 200);
        }
    }

    public function getSlugGenerate()
    {
        $user = User::where('id', auth()->user()->id)->pluck('slug')->first();
        return $user;
    }

    public function __getSlugGenerate($userId)
    {
        $users = User::where('id', $userId)->pluck('slug')->first();
        return $users;
    }

    public function recoveryPassword(Request $request)
    {
        $data = $request->only('email');
        $validator = Validator::make(
            $data,
            ['email' => 'required',],
            ['email.required' => 'veuillez saisir l\'adresse email',]
        );

        if ($validator->fails()) {
            return response()->json(['message' => $validator->messages()->first(), 'status' => 'error'], 400);
        }

        $otp = rand(100000, 999999);
        $initToken = Str::random(9);
        $useremail = $request->email;

        $isEmail = filter_var($useremail, FILTER_VALIDATE_EMAIL);
        if (!$isEmail) {
            return response()->json([
                'status' => 'error', 
                'message' => 'Email non valide',
            ], Response::HTTP_OK);
        }

        $users = User::get();
        $isUser =  $users->where('email', $useremail)->first();

        if (isset($isUser)) {
            $users->where('email', $useremail)->first()->update(['password' => bcrypt($initToken),]);
            $template = "<p>Information de connexion &nbsp;!<br><br>Login: $useremail<br>Password: $initToken<br> Vous pouvez changer votre mot de passe dans les paramètres";

            $data["email"] = $request->email;
            $data["title"] = 'Récupération du compte';
            $data["body"] = $template;
            $data["from"] = Param::getEmailAwt();
        $data['from_name'] = 'Hobotta';

            Mail::send('mail.notify', $data, function ($message) use ($data) {
                $message->to($data["email"])
                    ->subject($data["title"])
                    ->from($data['from'], $data['from_name']);
            });

            return response()->json([
                'status' => 'succes',
                'message' => "mot de passe à jour",
                'change' => 1,
            ], 200);
        } else {
            return response()->json([
                'status' => 'error',
                'message' => "Désolé, aucun utilisateur ne correspond",
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function registerUser(UserRequest $request)
    {
        $otp = rand(100000, 999999);
        $initToken = Str::random(32);
        $username = $request->name;
        $useremail = $request->email;
        $userphone = $request->phone;
        $originalUrl =  Param::getBaseurlFront() . "/verify-otp/" . $initToken;

        // $template = "<p>Bonjour $username&nbsp;!<br><br>Votre compte à bien été crée avec succès il vous suffit maintenant de cliquer sur le lien ci-dessous et entrer le code de vérification.<br><br><a href= $originalUrl>Suive ce lien</a><br>Code de vérification: <strong>$otp</strong><br><br>Si vous n'êtes pas le destinataire de ce mail merci de l'ignorer et de contacter notre support.</p><p>Merci,<br><br>Cordialement.</p>";
        $template = "<p>Bonjour $username&nbsp;!<br><br>Votre compte à bien été crée avec succès.<br><br>Si vous n'êtes pas le destinataire de ce mail merci de l'ignorer et de contacter notre support.</p><p>Merci,<br><br>Cordialement.</p>";

        $isEmail = filter_var($useremail, FILTER_VALIDATE_EMAIL);
        if (!$isEmail) {
            return response()->json([
                'status' => 'error',
                'message' => 'Email non valide',
            ], Response::HTTP_OK);
        }
        $user = User::create([
            'name' => $username,
            'email' => $useremail,
            'phone' => $userphone,
            'password' => bcrypt($request->password),
            'role_id' => 0,
        ]);

        $abonnement = Abonnement::create([
            'user_id' => $user->id
        ]);


        try {
            $data["email"] = $request->email;
            $data["title"] = 'Création de compte';
            $data["body"] = $template;
            $data["from"] = Param::getEmailAwt();
            $data['from_name'] = 'Hobotta';

            Mail::send('mail.notify', $data, function ($message) use ($data) {
                $message->to($data["email"], $data["email"])
                    ->subject($data["title"])
                    ->from($data['from'], $data['from_name']);
            });


            return $user ? response()->json([
                'status' => 'success',
                'canal' => 'email',
                'message' => 'Utilisateur créé avec succès',
                'data' => new UserResource($user),
            ], Response::HTTP_OK) :
                response()->json([
                    'status' => 'success',
                    'message' => 'une erreur s\'est produite',
                ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            //throw $th;
            // $API_KEY_WHATSAPP = Param::getTokenWhatsapp();
            // $number = Convertor::convertNumbert($request->phone);

            // $message = $template;

            // $data = ["phone" => $number, "message" => strip_tags($message)];
            // $curl = curl_init();
            // curl_setopt_array($curl, [
            //     CURLOPT_URL => "https://api.wassenger.com/v1/messages",
            //     CURLOPT_RETURNTRANSFER => true,
            //     CURLOPT_ENCODING => "",
            //     CURLOPT_MAXREDIRS => 10,
            //     CURLOPT_TIMEOUT => 30,
            //     CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            //     CURLOPT_CUSTOMREQUEST => "POST",
            //     CURLOPT_POSTFIELDS => json_encode($data),
            //     CURLOPT_HTTPHEADER => [
            //         "Content-Type: application/json",
            //         "Token: $API_KEY_WHATSAPP",
            //     ],
            // ]);

            // $response = curl_exec($curl);

            // $err = curl_error($curl);
            // curl_close($curl);
            // if ($err) {
            //     return
            //         response()->json([
            //             'status' => 'error',
            //             'message' => 'une erreur curl ' . $err,
            //         ], Response::HTTP_OK);
            // } else {
            //     // $reponse_banner = json_decode($response);
            //     $reponse = json_decode($response);
            //     if (!empty($reponse->id)) {

            //         return $user ? response()->json([
            //             'status' => 'success',
            //             'canal' => 'whatsapp',
            //             'message' => 'Utilisateur créé avec succès',
            //             'data' => new UserResource($user),
            //         ], Response::HTTP_OK) :
            //             response()->json([
            //                 'status' => 'success',
            //                 'message' => 'une erreur s\'est produite',
            //             ], Response::HTTP_OK);
            //     }
            // }


            return $user ? response()->json([
                'status' => 'success',
                'canal' => 'email',
                'message' => 'Utilisateur créé avec succès',
                'data' => new UserResource($user),
            ], Response::HTTP_OK) :
                response()->json([
                    'status' => 'success',
                    'message' => 'une erreur s\'est produite',
                ], Response::HTTP_OK);
        }
    }

    public function registerAgent(UserRequest $request/**/)
    {
        $otp = rand(100000, 999999);
        $initToken = Str::random(32);
        $username = $request->name;
        $useremail = $request->email;
        $userphone = $request->phone;
        $originalUrl =  Param::getBaseurlFront() . "/verify-otp/" . $initToken;

        // $template = "<p>Bonjour $username&nbsp;!<br><br>Votre compte à bien été crée avec succès il vous suffit maintenant de cliquer sur le lien ci-dessous et entrer le code de vérification.<br><br><a href= $originalUrl>Suive ce lien</a><br>Code de vérification: <strong>$otp</strong><br><br>Si vous n'êtes pas le destinataire de ce mail merci de l'ignorer et de contacter notre support.</p><p>Merci,<br><br>Cordialement.</p>";
        $template = "<p>Bonjour $username&nbsp;!<br><br>Votre compte à bien été crée avec succès.<br><br>Si vous n'êtes pas le destinataire de ce mail merci de l'ignorer et de contacter notre support.</p><p>Merci,<br><br>Cordialement.</p>";
        $user = User::create([
            'name' => $username,
            'email' => $useremail,
            'phone' => $userphone,
            'password' => bcrypt($request->password),
            'role_id' => 1,
            'init_token' => $initToken,
            'altern_key' => $otp,
            // 'password' => bcrypt('123456'),
        ]);

        try {

            $data["email"] = $request->email;
            $data["title"] = 'Validation du compte';
            $data["body"] = $template;
            $data["from"] = Param::getEmailAwt();

            Mail::send('mail.notify', $data, function ($message) use ($data) {
                $message->to($data["email"], $data["email"])
                    ->subject($data["title"])
                    ->from($data['from']);
            });


            return $user ? response()->json([
                'status' => 'success',
                'canal' => 'email',
                'message' => 'Agent créé avec succès',
                'data' => new UserResource($user),
            ], Response::HTTP_OK) :
                response()->json([
                    'status' => 'success',
                    'message' => 'une erreur s\'est produite',
                ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            $API_KEY_WHATSAPP = Param::getTokenWhatsapp();
            $number = Convertor::convertNumbert($request->phone);

            $message = $template;

            $data = ["phone" => $number, "message" => strip_tags($message)];
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
            if ($err) {
                return
                    response()->json([
                        'status' => 'error',
                        'message' => 'une erreur curl ' . $err,
                    ], Response::HTTP_OK);
            } else {
                $reponse = json_decode($response);
                if (!empty($reponse->id)) {

                    return $user ? response()->json([
                        'status' => 'success',
                        'canal' => 'whatsapp',
                        'message' => 'Agent créé avec succès',
                        'data' => new UserResource($user),
                    ], Response::HTTP_OK) :
                        response()->json([
                            'status' => 'success',
                            'message' => 'une erreur s\'est produite',
                        ], Response::HTTP_OK);
                }
            }
        }
    }

    public function authenticate(Request $request)
    {
        $credentials = $request->only('email', 'password');
        $validator = Validator::make($credentials, [
            'email' => 'required|email',
            'password' => 'required|string|min:6|max:50',
        ]);
        if ($validator->fails()) {
            return response()->json(['message' => $validator->messages()->first(), 'status' => 'error'], 200);
        }
        try {
            if (!$token = JWTAuth::attempt($credentials)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Les identifiants de connexion ne sont pas valides.',
                ], 400);
            }
        } catch (JWTException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Impossible de créer le jeton.',
            ], 500);
        }

        $user = Auth::user();
        if ($user->status == 0 && $user->role_id == 0) {
            auth()->logout();
            return response()->json([
                'status' => 'echec',
                'message' => 'Votre compte est en attente d\'activation.',
            ]);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Connexion effectuée avec succes',
            'expires_in' => JWTAuth::factory()->getTTL(),
            'token' => $token,
            'user' => $user,
        ]);
    }

    public function logout(Request $request)
    {
        //valid credential
        $validator = Validator::make($request->only('token'), [
            'token' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->messages()->first(), 'statut' => 'error'], 200);
        }

        try {
            JWTAuth::invalidate($request->token);

            return response()->json([
                'statut' => 'success',
                'message' => "L'utilisateur a été déconnecté",
            ]);
        } catch (JWTException $exception) {
            return response()->json([
                'statut' => 'error',
                'message' => "Désolé, l'utilisateur ne peut pas être déconnecté",
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getUser(Request $request)
    {
        $this->validate($request, [
            'token' => 'required',
        ]);

        $user = JWTAuth::authenticate($request->token);

        return response()->json(['user' => $user]);
    }

    public function getClients(Request $request)
    {
        $clients = User::where('role_id', 0)
            ->orderBy('created_at', 'DESC')
            ->select('id', 'name', 'phone', 'email', 'status', 'created_at')
            ->paginate(25);
        return response()->json([
            "status" => "success",
            "message" => "tous les clients",
            "clients" => $clients
        ]);
    }

    public function getAgents(Request $request)
    {
        $agents = User::where('role_id', 1)
            ->orderBy('created_at', 'DESC')
            ->select('id', 'name', 'phone', 'email', 'status', 'created_at')
            ->paginate(25);
        return response()->json([
            "status" => "success",
            "message" => "tous les agents",
            "agents" =>  $agents
        ]);
    }

    public function getUserStatut(Request $request)
    {
        if ($request->id) :
            $user = User::where('id', $request->id)->where('delete_status', 0)->first();
            if ($user) {
                return response()->json(['statut' => 'success', 'user_statut' => $user->status]);
            }
            return response()->json(['statut' => 'fail', 'message' => 'aucun utilisateur trouvé']);
        else :
            return response()->json(['status' => 'fail', 'message' => 'l\'id ne doit pas être vide']);;
        endif;
    }

    public function deleteUser(Request $request)
    {
        $user = User::where('id', $request->id)->where('delete_status', 0);
        $userGet = $user->first();
        if (!$userGet) : return response()->json(['status' => 'erreur', 'message' => 'utilisateur non trouvé']);
        endif;

        $user->update(['delete_status' => 1]);
        return response()->json(['status' => 'success', 'message' => 'utilisateur supprimé']);
    }

    public function activateUser(Request $request)
    {
        $user = User::where('id', $request->id)->where('delete_status', 0);
        $userGet = $user->first();
        if (!$userGet) : return response()->json(['status' => 'erreur', 'message' => 'utilisateur non trouvé']);
        endif;
        if ($userGet->statut == 0) {
            $user->update(['status' => 1]);
            // (new SendMailService)->sendMail("Demande d'activation de compte", $user, "Votre demande d'inscription a été approuvée.");
            return response()->json(['status' => 'success', 'message' => 'utilisateur activé']);
        }
        return response()->json(['status' => 'echec', 'message' => 'utilisateur déjà activé']);
    }

    public function disableUser(Request $request)
    {
        $user = User::where('id', $request->id)->where('status', 1)->update(['status' => 0]);
        // $abonnement = Abonnement::where('user_id', $user->id)->update(['status' => 0]);
        return response()->json(['status' => 'success', 'message' => 'utilisateur désactivé']);
    }

    public function init_token(Request $request)
    {
        $user = User::where('init_token', $request->init_token)->first();

        if (isset($user)) {

            return response()->json([
                'statut' => 'success',
                'message' => "L'utilisateur a été trouvé",
                'data' => $user,
            ], 200);
        } else {
            return response()->json([
                'statut' => 'error',
                'message' => "Désolé, aucun utilisateur ne correspond",
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function createpwd_verify(Request $request)
    {
        $data = $request->only('id', 'password');
        $validator = Validator::make(
            $data,
            ['id' => 'required', 'password' => 'required'],
            ['id.required' => 'utilisateur non identifier', 'password.required' => 'veuillez saisir votre code d\'indentification']
        );
        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->messages()->first()], 200);
        }

        $user = User::where('id', $request->id)->first();

        if ($request->password == null) {
            return response()->json([
                'status' => 'succes',
                'message' => "error le mots de passe est obligatoire",
            ], 200);
        }

        if (isset($user)) {
            $user->update(['password' => bcrypt($request->password)]);
            return response()->json([
                'status' => 'success',
                'message' => "mot de passe à jour",
                'change' => 1,
                'data' => $user,

            ], 200);
        } else {
            return response()->json([
                'status' => 'error',
                'message' => "Désolé, aucun utilisateur ne correspond",
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function otp_verify(Request $request)
    {
        $user = User::where('init_token', $request->init_token)->where('altern_key', $request->altern_key)->first();

        if (isset($user)) {
            $user->update(['init_token' => null, 'altern_key' => null, 'is_valid' => '1']);
            return response()->json([
                'statut' => 'success',
                'message' => "Compte approuver",
                'data' => $user,
            ], 200);
        } else {
            return response()->json([
                'statut' => 'error',
                'message' => "Désolé, aucun utilisateur ne correspond",
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function upadatePass(Request $request)
    {
        $user = User::where('id', auth()->user()->id)->first();
        if ($request->password == null) {
            return response()->json([
                'status' => 'succes',
                'message' => "error le mots de passe est obligatoire",
            ], 200);
        }

        if (isset($user)) {
            $user->update(['password' => bcrypt($request->password)]);
            return response()->json([
                'status' => 'success',
                'message' => "mot de passe à jour",
                'change' => 1,
                'data' => $user,

            ], 200);
        } else {
            return response()->json([
                'status' => 'error',
                'message' => "Désolé, aucun utilisateur ne correspond",
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
