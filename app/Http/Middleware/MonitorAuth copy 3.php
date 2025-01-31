<?php 


namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\AuthMonitorsCredential; // Modèle de la table auth_monitors_credentials
use Tymon\JWTAuth\Facades\JWTAuth;  // Si tu utilises JWT pour générer un token
use Tymon\JWTAuth\Token; // Ajoute ceci pour utiliser l'objet Token

class MonitorAuth
{
    public function handle(Request $request, Closure $next)
    {
        // Récupérer le token à partir des en-têtes de la requête
        $token = $request->bearerToken(); // ou $request->header('Authorization')

        if (!$token) {
            return response()->json([
                'status' => 'erreur',
                'message' => 'accès non autorisé'
            ], 401);
        }

         // Convertir le token en un objet Token
         $tokenObject = new Token($token);

         // Décoder le token
         $decoded = JWTAuth::decode($tokenObject); // Passe l'objet Token

        //  dd($decoded->sub);
        // dd(JWTAuth::getPayload($token)->toArray());
        // Ici, tu peux décoder le token et vérifier s'il correspond à un monitor
        try {
            // Décoder le token - tu pourrais aussi avoir des clés différentes si le token est signé avec un autre provider
            $decoded = JWTAuth::decode($token); // Exemple avec JWTAuth (si tu utilises ce package pour générer les tokens)
            $monitorId = $decoded->sub;  // Supposons que tu mets l'ID du monitor dans le "subject" du JWT

            // Vérifie si le monitor existe dans la table auth_monitors_credentials
            $monitor = AuthMonitorsCredential::find($monitorId);

            if (!$monitor) {
                return response()->json(['error' => 'Monitor not found or invalid token'], 401);
            }

            // Authentifie le monitor dans la requête pour l'utiliser ailleurs dans le contrôleur
            $request->merge(['monitor' => $monitor]);

        } catch (\Exception $e) {
            // Si le token n'est pas valide ou s'il y a une erreur dans le décodage
            return response()->json(['error' => 'Unauthorized, invalid token'], 401);
        }

        // Continuer la requête vers le contrôleur suivant
        return $next($request);
    }
}
