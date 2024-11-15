<?php

namespace App\Http\Controllers;

use App\Models\ShortLink;
use Illuminate\Http\Request;

class ShortLinkController extends Controller
{
    public function show($folder, $filename)
    {
        $filePath = 'banner/' . $folder . '/' . $filename;

        if (!Storage::disk('public')->exists($filePath)) {
            return response()->view('errors.file_not_found', [], 404);
        }

        return Storage::disk('public')->response($filePath);
    }

    public function redirect($code)
    {
        $linkcode = ShortLink::where('short_code', $code)->with('fichier')->firstOrFail()->fichier->lien;
        $parts = explode('/', $linkcode);

        $folder = $parts[1];
        $filename = $parts[2];

        // Retourner la réponse de show directement
        return $this->show($folder, $filename);
    }
}












// public function redirect($code)
// {
//     // Récupère le lien court avec la relation fichier
//     $shortLink = ShortLink::where('short_code', $code)->with('fichier')->firstOrFail();
    
//     // Chemin du dossier où sont stockés les fichiers
//     $directory = 'public/banner/' . $shortLink->fichier->lien;

//     // Vérifie si le dossier existe
//     if (!Storage::exists($directory)) {
//         abort(404, 'Directory does not exist.');
//     }

//     // Utilise la méthode getFiles pour récupérer les fichiers du dossier
//     $files = $this->getFiles($directory);

//     // Si aucun fichier n'est trouvé, redirige vers le lien du fichier
//     if (empty($files)) {
//         return redirect()->to($shortLink->fichier->lien);
//     }

//     // Retourne la vue 'files.index' avec les fichiers trouvés
//     return view('files.index', compact('files'));
// }

// Méthode pour récupérer les fichiers du dossier spécifié
// protected function getFiles($directory)
// {
//     // Filtrer les fichiers pour récupérer uniquement les images
//     return collect(Storage::files($directory))
//         ->filter(function ($file) {
//             $extension = pathinfo($file, PATHINFO_EXTENSION);
//             return in_array($extension, ['jpg', 'jpeg', 'png', 'gif']);
//         })
//         ->map(function ($file) {
//             return url('files/show/' . basename($file));
//         })
//         ->toArray();
// }
