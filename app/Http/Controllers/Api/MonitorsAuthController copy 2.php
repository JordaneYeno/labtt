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
        if (!$token = JWTAuth::attempt($credentials)) 
        {
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




//     use Tymon\JWTAuth\Facades\JWTAuth;
// use App\Models\AuthMonitorsCredential;
// use Carbon\Carbon;

public function loginMonitors(Request $request)
{
    // Exemple de récupération de l'utilisateur (par son identifiant ou email, à adapter selon ta logique)
    $monitorCredential = AuthMonitorsCredential::where('username', $request->username)->first();

    if (!$monitorCredential || !Hash::check($request->password, $monitorCredential->password)) {
        return response()->json(['error' => 'Invalid credentials'], 401);
    }

    // Créer un token avec des claims personnalisés
    $token = JWTAuth::fromUser($monitorCredential, [
        'exp' => Carbon::now()->addYear()->timestamp, // Expiration du token
        'typ' => 'refresh'  // Type de token
    ]);

    return response()->json(['token' => $token]);
}

}
