<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;

use App\Models\AuthMonitorsCredential;

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
        $token = $request->bearerToken(); 

        if (!$token) {
            // return response()->json(['error' => 'Token not provided'], 401);
            return response()->json([
                'status' => 'erreur',
                'message' => 'accès non autorisé'
            ], 401);
        }


        // try {
        // //    dd(JWTAuth::getPayload($token)->toArray());
        // //    dd(JWTAuth::decode($token));
        //     $decoded = JWTAuth::decode($token); $monitorId = $decoded->sub; dd($decoded);
        //     $monitor = AuthMonitorCredential::find($monitorId);

        //     if (!$monitor) {
        //         return response()->json(['error' => 'Monitor not found or invalid token'], 401);
        //     }

        //     // Authentifie le monitor dans la requête pour l'utiliser ailleurs dans le contrôleur
        //     $request->merge(['monitor' => $monitor]);

        // } catch (\Exception $e) {
        //     // Si le token n'est pas valide ou s'il y a une erreur dans le décodage
        //     return response()->json(['error' => 'Unauthorized, invalid token'], 401);
        // }

        // Continuer la requête vers le contrôleur suivant
    //     return $next($request);
    // }


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
