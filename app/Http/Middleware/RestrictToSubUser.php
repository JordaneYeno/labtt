<?php

namespace App\Http\Middleware;

use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;

class RestrictToSubUser
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
        $token = $request->bearerToken(); //dd($token);

        if (!$token) {
            return response()->json([
                'status' => 'erreur',
                'message' => 'accès non autorisé'
            ], 401);
        }

        try {
            if (! $user = JWTAuth::parseToken()->authenticate()) {
                return response()->json(['error' => 'User not found'], 404);
            }

            // Vérifier si le token est expiré
            if (Carbon::now()->gt($user->token_expires_at)) {
                return response()->json(['error' => 'Token expired'], 401);
            }

            if ($user->owner_id === null) { return response()->json([ 'error' => 'Forbidden' ], 403); }
        } catch (\Tymon\JWTAuth\Exceptions\TokenExpiredException $e) {
            return response()->json(['error' => 'Token expired'], 401);
        } catch (\Tymon\JWTAuth\Exceptions\TokenInvalidException $e) {
            return response()->json(['error' => 'Invalid token'], 401);
        } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {
            return response()->json(['error' => 'Token not provided'], 400);
        }

        $request->attributes->add(['user' => $user]);

        return $next($request);
    }
}
