<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;


class MonitorAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */

    public function handle(Request $request, Closure $next)
    {
        // try {
        //     // Vérifie si le token est présent dans l'en-tête
        //     if (!$token = $request->header('Authorization')) {
        //         return response()->json(['error' => 'Token not provided'], 401);
        //     }

        //     // Tente de vérifier et décoder le token avec le guard 'monitor-api'
        //     $user = JWTAuth::setToken($token)->toUser();

        //     // Si le token est valide, l'utilisateur est authentifié
        //     $request->merge(['user' => $user]); // Ajouter l'utilisateur à la requête (facultatif)

        // } catch (JWTException $e) {
        //     // Si le token est invalide ou expiré
        //     return response()->json(['error' => 'Token is invalid or expired'], 401);
        // }

        // // Si tout va bien, passe à la prochaine étape
        // return $next($request);


        // if (auth()->check() && auth()->user()->role_id != 1) {
        //     return $next($request);
        // }

        // dd(auth()->check());


        // Récupérer le token à partir des en-têtes de la requête
        $token = $request->bearerToken(); // ou $request->header('Authorization')

        if (!$token) {
            return response()->json(['error' => 'Token not provided 2'], 401);
        }


        dd(JWTAuth::parseToken()->authenticate());

        // Tente de récupérer l'utilisateur à partir du token JWT
        try {
            if (! $user = JWTAuth::parseToken()->authenticate()) {
                return response()->json(['error' => 'User not found'], 404);
            }
        } catch (\Tymon\JWTAuth\Exceptions\TokenExpiredException $e) {
            return response()->json(['error' => 'Token expired'], 401);
        } catch (\Tymon\JWTAuth\Exceptions\TokenInvalidException $e) {
            return response()->json(['error' => 'Invalid token'], 401);
        } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {
            return response()->json(['error' => 'Token not provided'], 400);
        }

        // Ajoute l'utilisateur au request pour l'utiliser dans le contrôleur
        $request->attributes->add(['user' => $user]);

        return $next($request);
    }
}
