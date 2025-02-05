<?php

namespace App\Http\Middleware;

use Closure;
use Tymon\JWTAuth\Facades\JWTAuth;
use Exception;

class RefreshTokenMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        try {
            // Vérifie si le token est valide
            JWTAuth::parseToken()->authenticate();
        } catch (Exception $e) {
            // Si le token a expiré, essayez de le rafraîchir
            if ($e instanceof \Tymon\JWTAuth\Exceptions\TokenExpiredException) {
                try {
                    $refreshedToken = JWTAuth::refresh(JWTAuth::getToken());
                    $response = $next($request);
                    $response->headers->set('Authorization', 'Bearer ' . $refreshedToken);

                    return $response;
                } catch (Exception $refreshException) {
                    return response()->json(['error' => 'Impossible de rafraîchir le token'], 401);
                }
            }

            // Autre erreur
            return response()->json(['error' => 'Token invalide ou manquant'], 401);
        }

        return $next($request);
    }
}
