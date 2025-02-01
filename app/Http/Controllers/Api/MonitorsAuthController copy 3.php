<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\AuthMonitorsCredential;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Tymon\JWTAuth\Exceptions\JWTException;

class MonitorsAuthController extends Controller
{

    public function newLogin(Request $request)
    {
        $monitorCredential = AuthMonitorsCredential::where('email', $request->email)->first();

        if (!$monitorCredential || !Hash::check($request->password, $monitorCredential->password)) {
            return response()->json(['error' => 'Invalid credentials'], 401);
        }

        // Génère le token JWT avec le guard 'monitor-api'
        $token = JWTAuth::guard('monitor-api')->fromUser($monitorCredential);

        return response()->json(['token' => $token]);
    }

    public function getData(Request $request)
    {
        $user = $request->user(); 
        // Tu peux maintenant récupérer des données liées à cet utilisateur
        $monitorData = AuthMonitorsCredential::find($user->id);  // Exemple pour récupérer les informations de l'utilisateur

        return response()->json($monitorData);
    }

    public function loginTest(Request $request)
    {
        // Exemple de récupération de l'utilisateur (par email)
        $monitorCredential = AuthMonitorsCredential::where('username', $request->username)->first();

        if (!$monitorCredential || !Hash::check($request->password, $monitorCredential->password)) {
            return response()->json(['error' => 'Invalid credentials'], 401);
        }

        // Générer le token JWT
        $token = JWTAuth::fromUser($monitorCredential);

        return response()->json(['token' => $token]);
    }

    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'username' => 'required|string|unique:auth_monitors_credentials',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 401);
        }

        $credential = AuthMonitorsCredential::create([
            'username' => $request->input('username'),
            'password' => $request->input('password'),
        ]);

        $token = JWTAuth::fromUser($credential); // Génère un token pour le nouvel utilisateur

        return response()->json(compact('token'));
    }

    public function login(Request $request)
    {
        $credentials = $request->only(['username', 'password']);



        if (!$token = JWTAuth::attempt($credentials)) {
            return response()->json(['error' => 'Informations d\'identification invalides'], 401);
        }

        return response()->json(compact('token'));
    }

    // Dans un contrôleur, par exemple
    public function authenticate(Request $request)
    {
        $credentials = $request->only(['username', 'password']);


        // dd($credentials);
        if (!$token = JWTAuth::attempt($credentials)) {
            return response()->json(['error' => 'Informations d\'identification invalides'], 401);
        }

        return response()->json(compact('token'));
    }


    public function login1(Request $request)
    {
        // Valider les données reçues
        $request->validate([
            'username' => 'required|string|min:6|max:15',
            'password' => 'required|string|min:6|max:50',
        ]);

        // Rechercher l'utilisateur par username
        $user = AuthMonitorsCredential::where('username', $request->username)->first();
        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['error' => 'Invalid credentials'], 401);
        }

        // Vérifier si le compte est actif
        if (!$user->isAccountActive()) {
            return response()->json(['error' => 'Account is not active or expired'], 403);
        }

        // Générer un token JWT
        // $token = JWTAuth::fromUser($user); 
        // $token = JWTAuth::attempt($user);


        dd(JWTAuth::fromUser($user, ['exp' => Carbon::now()->addYear()->timestamp, 'typ' => 'refresh']));

        // dd($token);


        Log::info('Generated token:', ['token' => $token]);
        return response()->json([
            'access_token' => $token,   
            'token_type' => 'Bearer',
            'expires_in' => auth('api')->factory()->getTTL() * 60, // TTL en secondes
        ]);
    }

    // public function loginMonitors(Request $request)
    // {
    //     $request->validate([
    //         'username' => 'required|string|min:6|max:15',
    //         'password' => 'required|string|min:6|max:50',
    //     ]);

    //     $monitorCredential = AuthMonitorsCredential::where('username', $request->username)->first();
    //     $expiresIn = 3600; 
    //     if (!$monitorCredential || !Hash::check($request->password, $monitorCredential->password)) 
    //     {
    //         return response()->json(['error' => 'Invalid credentials'], 401);
    //     }

    //     $token = JWTAuth::fromUser($monitorCredential, [
    //         'exp' => Carbon::now()->addYear()->timestamp, // Expiration du token
    //         'typ' => 'refresh'  // Type de token
    //     ]);
    //     Log::info('Generated token:', ['token' => $token]);

    //     return response()->json([
    //         'access_token' => $token,
    //         'token_type' => 'bearer',,
    //         'expires_in' => $expiresIn
    //     ]);
    // }


    // public function loginMonitors(Request $request)
    // {
    //     $request->validate([
    //         'username' => 'required|string|min:6|max:15',
    //         'password' => 'required|string|min:6|max:50',
    //     ]);

    //     $monitorCredential = AuthMonitorsCredential::where('username', $request->username)->first();
    //     $expiresIn = 30; // Temps d'expiration du token en secondes

    //     if (!$monitorCredential || !Hash::check($request->password, $monitorCredential->password)) {
    //         return response()->json(['error' => 'Invalid credentials'], 401);
    //     }
        
    //     $token = JWTAuth::fromUser($monitorCredential, [
    //         'exp' => $expiresIn, 
    //         'typ' => 'refresh'
    //     ]);

    //     // Log::info('Generated token:', ['token' => $token]);

    //     // Générer un token de rafraîchissement (refresh token)
    //     $refreshToken = JWTAuth::getToken();  // Le refresh token peut être généré à partir du même token JWT

    //     // Log de la génération des tokens
    //     Log::info('Generated tokens:', ['access_token' => $token, 'refresh_token' => $refreshToken]);
   
    //     // Réponse avec les données du token
    //     return response()->json([
    //         'access_token' => $token,
    //         'token_type' => 'bearer',  
    //         'expires_in' => $expiresIn  
    //     ]);
    // }

    // public function refreshToken(Request $request)
    // {
    //     try {
    //         // On récupère le refresh token envoyé dans la requête
    //         $refreshToken = $request->input('refresh_token');
            
    //         // Vérification du refresh token et génération du nouveau access token
    //         $accessToken = JWTAuth::refresh($refreshToken);  // Méthode de tymon/jwt-auth pour rafraîchir le token
            
    //         // Retourner le nouveau access token
    //         return response()->json([
    //             'access_token' => $accessToken,
    //             'token_type' => 'bearer',
    //             'expires_in' => 3600 // 1 heure en secondes
    //         ]);
    //     } catch (\Exception $e) {
    //         return response()->json(['error' => 'Invalid refresh token'], 401);
    //     }
    // }


    // public function loginMonitors(Request $request)
    // {
    //     // Validation des données de connexion
    //     $request->validate([
    //         'username' => 'required|string|min:6|max:15',
    //         'password' => 'required|string|min:6|max:50',
    //     ]);

    //     // Recherche des informations d'identification
    //     $monitorCredential = AuthMonitorsCredential::where('username', $request->username)->first();
        
    //     // Vérification des informations de connexion
    //     if (!$monitorCredential || !Hash::check($request->password, $monitorCredential->password)) {
    //         return response()->json(['error' => 'Invalid credentials'], 401);
    //     }

    //     // Générer le token d'accès (access token) avec une expiration de 1 minute
    //     $accessToken = JWTAuth::fromUser($monitorCredential, [
    //         'exp' => Carbon::now()->addMinute()->timestamp, // Expiration dans 1 minute
    //     ]);

    //     // Générer un token de rafraîchissement (refresh token)
    //     $refreshToken = JWTAuth::getToken();  // Le refresh token peut être généré à partir du même token JWT

    //     // Log de la génération des tokens
    //     Log::info('Generated tokens:', ['access_token' => $accessToken, 'refresh_token' => $refreshToken]);

    //     // Réponse avec les tokens
    //     return response()->json([
    //         'access_token' => $accessToken,
    //         'refresh_token' => $refreshToken,  // On envoie également le refresh token
    //         'token_type' => 'bearer',
    //         'expires_in' => 60 // 1 minute en secondes
    //     ]);
    // }

    // // Rafraîchir le token d'accès
    // public function refreshToken(Request $request)
    // {
    //     try {
    //         // On récupère le refresh token envoyé dans la requête
    //         $refreshToken = $request->input('refresh_token');
            
    //         // Vérification du refresh token et génération du nouveau access token
    //         $accessToken = JWTAuth::refresh($refreshToken);  // Méthode de tymon/jwt-auth pour rafraîchir le token
            
    //         // Retourner le nouveau access token
    //         return response()->json([
    //             'access_token' => $accessToken,
    //             'token_type' => 'bearer',
    //             'expires_in' => 60 // 1 minute en secondes
    //         ]);
    //     } catch (\Exception $e) {
    //         return response()->json(['error' => 'Invalid refresh token'], 401);
    //     }
    // }


     // Méthode pour se connecter et obtenir un access token + refresh token
     public function loginMonitors44(Request $request)
     {
         // Validation des données de connexion
         $request->validate([
             'username' => 'required|string|min:6|max:15',
             'password' => 'required|string|min:6|max:50',
         ]);
 
         // Recherche des informations d'identification
         $monitorCredential = AuthMonitorsCredential::where('username', $request->username)->first();
         
         // Vérification des informations de connexion
         if (!$monitorCredential || !Hash::check($request->password, $monitorCredential->password)) {
             return response()->json(['error' => 'Invalid credentials'], 401);
         }
 
         // Générer le token d'accès (access token) avec une expiration de 3 minutes
         $accessToken = JWTAuth::fromUser($monitorCredential, [
             'exp' => Carbon::now()->addMinutes(3)->timestamp, // Expiration dans 3 minutes
         ]);
 
         // Générer un token de rafraîchissement (refresh token)
         $refreshToken = JWTAuth::getToken();  // Le refresh token peut être généré à partir du même token JWT
 
         // Log de la génération des tokens
         Log::info('Generated tokens:', ['access_token' => $accessToken, 'refresh_token' => $refreshToken]);
 
         // Réponse avec les tokens
         return response()->json([
             'access_token' => $accessToken,
             'refresh_token' => $refreshToken,  // On envoie également le refresh token
             'token_type' => 'bearer',
             'expires_in' => 180  // 3 minutes en secondes
         ]);
     }
 
     // Méthode pour rafraîchir le token d'accès
     public function refreshToken(Request $request)
     {
         try {
             // On récupère le refresh token envoyé dans la requête
             $refreshToken = $request->input('refresh_token');
             
             // Vérification du refresh token et génération du nouveau access token
             $accessToken = JWTAuth::refresh($refreshToken);  // Méthode de tymon/jwt-auth pour rafraîchir le token
             
             // Retourner le nouveau access token
             return response()->json([
                 'access_token' => $accessToken,
                 'token_type' => 'bearer',
                 'expires_in' => 180 // 3 minutes en secondes
             ]);
         } catch (\Exception $e) {
             return response()->json(['error' => 'Invalid refresh token'], 401);
         }
     }



     public function loginMonitors(Request $request)
{
    $request->validate([
        'username' => 'required|string|min:6|max:15',
        'password' => 'required|string|min:6|max:50',
    ]);

    // Vérification des informations d'identification
    $monitorCredential = AuthMonitorsCredential::where('username', $request->username)->first();
    if (!$monitorCredential || !Hash::check($request->password, $monitorCredential->password)) {
        return response()->json(['error' => 'Invalid credentials'], 401);
    }

    // Génération du token avec une expiration de 3 minutes
    $expiresIn = 3; // 3 minutes

    $token = JWTAuth::fromUser($monitorCredential, [
        'exp' => Carbon::now()->addMinutes($expiresIn)->timestamp, // Expiration à 3 minutes
        'typ' => 'access'  // Type de token
    ]);

    Log::info('Generated token:', ['token' => $token]);

    return response()->json([
        'access_token' => $token,
        'token_type' => 'bearer',
        'expires_in' => $expiresIn * 60, // Renvoie la durée en secondes
    ]);
}

}
