<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Monitors\CreateAdvertisementRequest;
use App\Http\Requests\Monitors\GetAdvertisementRequest;
use App\Models\Advertisement;
use App\Services\PaginationService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

use function PHPUnit\Framework\isEmpty;

class MonitorsAuthController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    // public function generateHexReference()
    // {
    //     $reference = bin2hex(random_bytes(12));
    //     return $reference;
    // }

    public function store(CreateAdvertisementRequest $request)
    {
        // Upload du fichier
        if ($request->hasFile('media')) {
            $file = $request->file('media');
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'mp4', 'mov', 'avi', 'mkv'];

            if (!in_array($file->getClientOriginalExtension(), $allowedExtensions)) {
                Log::warning('Tentative d\'upload d\'un fichier interdit.', [
                    'chemin' => $file->getPathname(),
                    'extension' => $file->getClientOriginalExtension(),
                ]);
                return response()->json([
                    'status' => 'error',
                    'message' => 'Ce type de fichier est interdit.'
                ], Response::HTTP_BAD_REQUEST);
            }

            $extension = $file->getClientOriginalExtension();
            $filename = time() . '_' . /*trim($file->getClientOriginalName())*/  uniqid() . '.' . $extension;
            // $filename = time() . '_' . /*trim($file->getClientOriginalName())*/ Str::uuid(). '.' . $extension;
            $filePath = $file->storeAs('uploads/ads', $filename, 'public');
        }

        $advertisement = Advertisement::create([
            'client_id' => auth()->user()->id,
            'title' => $request->title,
            'description' => $request->description,
            'media_path' => '/storage/' . $filePath,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'status' => 'active',
            'ed_reference' => bin2hex(random_bytes(12)),
        ]);

        return response()->json(['message' => 'Advertisement created!', 'data' => $advertisement],  Response::HTTP_CREATED);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    // public function getAds(Request $request)
    // {

    //     return response()->json([
    //         // 'access_token' => $token,   
    //         // 'expires_in' => auth('api')->factory()->getTTL() * 60, // TTL en secondes
    //         'token_type' => 'Bearer access',
    //     ]);
    // }

    /**
     * Rafraîchir le token JWT.
     */
    public function refreshToken(Request $request)
    {
        try {
            // Vérifie si le token actuel est valide et génère un nouveau token
            $newToken = JWTAuth::parseToken()->refresh();

            return response()->json([
                'status' => 'success',
                'token' => $newToken,
            ], 200);
        } catch (JWTException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Token invalide ou expiré',
            ], 401);
        }
    }

    public function getActiveAds(Request $request)
    {
        $perPage = $request->perPage ? $request->perPage : 10;
        $paginate = new PaginationService();

        if (!is_array($request->filters)) { return response()->json(['error' => 'Le paramètre filters doit être un tableau.'], 400); }

        if (empty($request->filters)) {
            $activeAds = Advertisement::where('status', 'active')
                ->select('status', 'title', 'media_path', 'start_date', 'end_date', 'ed_reference')
                ->get();
        } else {
            // // On extrait les IDs et les dates de fin à exclure
            // $filters = $request->filters;
            // $idsToExclude = array_column($filters, 'ref');
            // $dateEndProvided = array_column($filters, 'end_date');

            // $query = Advertisement::where('status', 'active');

            // if (!empty($idsToExclude)) { $query->whereNotIn('ed_reference', $idsToExclude); }

            // if (!empty($dateEndProvided)) 
            // {
            //     $query->where(function ($query) use ($idsToExclude, $dateEndProvided) {
            //         foreach ($idsToExclude as $index => $id) 
            //         {
            //             $dateEnd = $dateEndProvided[$index];
            //             $query->where(function ($query) use ($id, $dateEnd) {
            //                 $query->where('ed_reference', '!=', $id)
            //                     ->orWhere('end_date', '!=', $dateEnd);
            //             });
            //         }
            //     });
            // }


            // On extrait les IDs et les dates de fin à exclure
            $filters = $request->filters;
            $idsToExclude = array_column($filters, 'ref');
            $dateEndProvided = array_column($filters, 'end_date');

            // On récupère les annonces correspondant aux IDs à exclure
            $adsToCheck = Advertisement::whereIn('ed_reference', $idsToExclude)
                ->get();

            // On filtre les annonces pour inclure celles dont la date de fin est prolongée
            $adsToInclude = [];
            foreach ($adsToCheck as $ad) {
                $index = array_search($ad->ed_reference, $idsToExclude);
                if ($index !== false) {
                    $providedEndDate = $dateEndProvided[$index];
                    if ($ad->end_date > $providedEndDate) {
                        $adsToInclude[] = $ad->ed_reference;
                    }
                }
            }

            // On construit la requête pour exclure les IDs spécifiés, sauf ceux à inclure
            $query = Advertisement::where('status', 'active')
                ->whereNotIn('ed_reference', $idsToExclude)
                ->orWhereIn('ed_reference', $adsToInclude); // Inclure les annonces prolongées

            $activeAds = $query->get();
        }

        $paginator = $paginate->paginate_resp($activeAds, $perPage, request('page', 1));

        // Retourne les résultats au format JSON
        return response()->json(['advertisements' => $paginator], Response::HTTP_OK);
    }

    // public function getActiveAds(Request $request)
    // {
    //     $perPage = $request->perPage ? $request->perPage : 10;
    //     $paginate = new PaginationService();

    //     // Vérifie si filters est un tableau
    //     if (!is_array($request->filters)) {
    //         return response()->json(['error' => 'Le paramètre filters doit être un tableau.'], 400);
    //     }

    //     // Si filters est vide, on récupère toutes les annonces actives
    //     if (empty($request->filters)) {
    //         $activeAds = Advertisement::where('status', 'active')
    //             ->select('status', 'title', 'media_path', 'start_date', 'end_date', 'ed_reference')
    //             ->get();
    //     } else {
    //         // On extrait les IDs et les dates de fin à exclure
    //         $filters = $request->filters;
    //         $idsToExclude = array_column($filters, 'ref');
    //         $dateEndProvided = array_column($filters, 'end_date');

    //         // On récupère les annonces correspondant aux IDs à exclure
    //         $adsToCheck = Advertisement::whereIn('ed_reference', $idsToExclude)
    //             ->get();

    //         // On filtre les annonces pour inclure celles dont la date de fin est prolongée
    //         $adsToInclude = [];
    //         foreach ($adsToCheck as $ad) {
    //             $index = array_search($ad->ed_reference, $idsToExclude);
    //             if ($index !== false) {
    //                 $providedEndDate = $dateEndProvided[$index];
    //                 if ($ad->end_date > $providedEndDate) {
    //                     $adsToInclude[] = $ad->ed_reference;
    //                 }
    //             }
    //         }

    //         // On construit la requête pour exclure les IDs spécifiés, sauf ceux à inclure
    //         $query = Advertisement::where('status', 'active')
    //             ->whereNotIn('ed_reference', $idsToExclude)
    //             ->orWhereIn('ed_reference', $adsToInclude); // Inclure les annonces prolongées
    //     }

    //     // Exécution de la requête
    //     $activeAds = $query->get();

    //     // Pagination des résultats
    //     $paginator = $paginate->paginate_resp($activeAds, $perPage, request('page', 1));

    //     // Retourne les résultats au format JSON
    //     return response()->json(['advertisements' => $paginator], Response::HTTP_OK);
    // }
}
