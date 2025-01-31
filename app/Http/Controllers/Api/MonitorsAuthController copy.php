<?php

// namespace App\Http\Controllers\Api;

// use App\Http\Controllers\Controller;
// use Illuminate\Http\Request;
// use Tymon\JWTAuth\Facades\JWTAuth;
// use App\Models\AuthMonitorsCredential;
// use Illuminate\Support\Facades\Hash;

// class MonitorsAuthController extends Controller
// {

//     public function login(Request $request)
//     {
//         // Valide les credentials
//         $credentials = $request->validate([
//             'username' => 'required',
//             'password' => 'required',
//         ]);

//         // Authentifie l'utilisateur avec MonitorsAuth
//         if (!$token = JWTAuth::attempt($credentials)) {
//             return response()->json(['error' => 'Unauthorized'], 401);
//         }

//         // Retourne le token JWT
//         return response()->json([
//             'access_token' => $token,
//             'token_type' => 'Bearer',
//             'expires_in' => JWTAuth::factory()->getTTL() * 60, // Durée de vie du token en secondes
//         ]);
//     }

//     public function refresh()
//     {
//         // Rafraîchit le token JWT
//         $token = JWTAuth::refresh();

//         return response()->json([
//             'access_token' => $token,
//             'token_type' => 'Bearer',
//             'expires_in' => JWTAuth::factory()->getTTL() * 60,
//         ]);
//     }

//     public function logout()
//     {
//         // Invalide le token JWT
//         JWTAuth::invalidate();

//         return response()->json(['message' => 'Successfully logged out']);
//     }

//     /**
//      * Display a listing of the resource.
//      *
//      * @return \Illuminate\Http\Response
//      */
//     public function index()
//     { 
//         //
//         dd('os');
//     }

//     /**
//      * Store a newly created resource in storage.
//      *
//      * @param  \Illuminate\Http\Request  $request
//      * @return \Illuminate\Http\Response
//      */
//     public function store(Request $request)
//     {
//         //
//     }

//     /**
//      * Display the specified resource.
//      *
//      * @param  int  $id
//      * @return \Illuminate\Http\Response
//      */
//     public function show($id)
//     {
//         //
//     }

//     /**
//      * Update the specified resource in storage.
//      *
//      * @param  \Illuminate\Http\Request  $request
//      * @param  int  $id
//      * @return \Illuminate\Http\Response
//      */
//     public function update(Request $request, $id)
//     {
//         //
//     }

//     /**
//      * Remove the specified resource from storage.
//      *
//      * @param  int  $id
//      * @return \Illuminate\Http\Response
//      */
//     public function destroy($id)
//     {
//         //
//     }
// }


namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\AuthMonitorsCredential;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Hash;

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

//     // Dans un contrôleur, par exemple
// public function authenticate(Request $request)
// {
//     $credentials = $request->only(['username', 'password']);

//     if (!$token = JWTAuth::attempt($credentials)) {
//         return response()->json(['error' => 'Informations d\'identification invalides'], 401);
//     }

//     return response()->json(compact('token'));
// }

public function authenticate(Request $request)
    {
        // Valide les credentials
        $credentials = $request->validate([
            'username' => 'required',
            'password' => 'required',
        ]);

        // Authentifie l'utilisateur avec MonitorsAuth
        if (!$token = JWTAuth::attempt($credentials)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Retourne le token JWT
        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'expires_in' => JWTAuth::factory()->getTTL() * 60, // Durée de vie du token en secondes
        ]);
    }



    public function login2(Request $request)
    {
        $credentials = $request->only(['username', 'password']); 

        $validator = Validator::make($credentials, [
            'username' => 'required|username',
            'password' => 'required|string|min:6|max:50',
        ]);

        // Rechercher l'utilisateur par username
        $user = AuthMonitorsCredential::where('username', $request->username)->first();

        if ($validator->fails()) {
            return response()->json(['message' => $validator->messages()->first(), 'status' => 'error'], 200);
        }

        if (!$user || !Hash::check($request->password, $user->password)) 
        {
            return response()->json(['error' => 'Invalid credentials'], 401);
        }

        // Vérifier si le compte est actif
        if (!$user->isAccountActive()) {
            return response()->json(['error' => 'Account is not active or expired'], 403);
        }

        // Générer un token JWT
        $token = JWTAuth::fromUser($user);  dd($token);

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'expires_in' => auth('api')->factory()->getTTL() * 60, // TTL en secondes
        ]);
    }

    public function login1(Request $request)
    {
        // Valider les données reçues
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
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
        $token = JWTAuth::fromUser($user); 

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'expires_in' => auth('api')->factory()->getTTL() * 60, // TTL en secondes
        ]);
    }

}
