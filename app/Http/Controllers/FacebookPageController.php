<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class FacebookPageController extends Controller
{
    public function getPageInformation(Request $request)
    {
        // Récupérer le jeton d'accès utilisateur depuis la requête
        // $accessToken = $request->input('access_token');
        $accessToken = "eyJhbGciOiJSUzI1NiIsImtpZCI6Ijc5M2Y3N2Q0N2ViOTBiZjRiYTA5YjBiNWFkYzk2ODRlZTg1NzJlZTYiLCJ0eXAiOiJKV1QifQ.eyJuYW1lIjoiQmFrb2FpIEJha29haSIsInBpY3R1cmUiOiJodHRwczovL2dyYXBoLmZhY2Vib29rLmNvbS8xMjIwOTcxNTM5MTQzODMzMzIvcGljdHVyZSIsImlzcyI6Imh0dHBzOi8vc2VjdXJldG9rZW4uZ29vZ2xlLmNvbS9vYXV0aDItZmY3MzAiLCJhdWQiOiJvYXV0aDItZmY3MzAiLCJhdXRoX3RpbWUiOjE3MTk0MTM3NzgsInVzZXJfaWQiOiJPU1dSTGJzMHc2ZkhXbTVQeWIzMXY3MXJLTVQyIiwic3ViIjoiT1NXUkxiczB3NmZIV201UHliMzF2NzFyS01UMiIsImlhdCI6MTcxOTQxMzc3OCwiZXhwIjoxNzE5NDE3Mzc4LCJlbWFpbCI6ImNvbnRhY3RAYmFrb2FpLnBybyIsImVtYWlsX3ZlcmlmaWVkIjpmYWxzZSwiZmlyZWJhc2UiOnsiaWRlbnRpdGllcyI6eyJmYWNlYm9vay5jb20iOlsiMTIyMDk3MTUzOTE0MzgzMzMyIl0sImVtYWlsIjpbImNvbnRhY3RAYmFrb2FpLnBybyJdfSwic2lnbl9pbl9wcm92aWRlciI6ImZhY2Vib29rLmNvbSJ9fQ.gn3ndgZkXjf8JCYuxsPmiPhCJHJ1GV8GfAXj_oHQg0_Hef8oQdEQ7yekM47wx1eRv0NPNUKLvyaoHakCY3WDCAT5_nkYaELSll5WxQqvGPwRb7dNKNS5zHcamiwdycLTFhrS8pbisjn__8Hi_szS6478mmDO3NLsK7bMf51oU-fPZi1shZ59UmkRc-flBkoKP4Bg6XFQPT8pzZmaTO2EHlo5j_iPTBQ2WqbPT2bkNZSNOgwbGKN1NoOqL_uorraveumNsdr8rRSduqT71ihS2VFoHy7zZJLULNFzkjQ82i-Uq5hAc7-L1P2SsgpqIAY0Ite5N-tkSaWbJPFUdNP7QA";
        
        // Construire la requête GraphQL
        $query = '{
            me {
                accounts {
                    id
                    name
                    category
                }
            }
        }';

        // Configuration de la requête HTTP avec Guzzle HTTP
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $accessToken,
            'Content-Type' => 'application/json',
        ])->post('https://graph.facebook.com/graphql', [
            'query' => $query,
        ]);

        // Gérer la réponse
        if ($response->successful()) {
            $data = $response->json();
            // Traiter $data pour extraire les informations des pages
            return response()->json($data);
        } else {
            $error = $response->json();
            // Gérer l'erreur de la requête
            return response()->json(['error' => $error], $response->status());
        }
    }
}
