<?php

namespace App\Http\Controllers\Api;

use App\Models\Abonnement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\Controller;
use App\Models\User;

class FileController extends Controller
{
    public function index()
    {
        $directory = 'public/banner'; // Chemin vers le répertoire dans storage
        if (!Storage::exists($directory)) {
            abort(404, 'Directory does not exist.');
        }

        $files = $this->getFiles($directory);

        return view('files.index', compact('files'));
    }
    
    public function getLogo()
    {
        $logo = Abonnement::where('user_id', auth()->user()->id)->pluck('logo')->first();
        
        if ($logo === null) { return null; }    
        $url = route('users.profile', ['id' => auth()->user()->id]);
        
        return $logo === null
        ? response()->json([
            'status' => 'success',
            'message' => 'lien du logo',
            'lien' => null
        ])
        : response()->json([
            'status' => 'success',
            'message' => 'lien du logo',
            'lien' => $url
        ]);
    }

    public function setLogo(Request $request)
    {
        $this->deleteFolderContents(auth()->user()->id);

        $validator = Validator::make($request->all(), [
            'logo' => 'required|file|mimes:jpeg,png,jpg,webp|max:2048', // Types de fichiers autorisés et taille maximale de 2 Mo (2048 kilo-octets)
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->messages()->first(), 'status' => 'error'], 400);
        }

        if ($request->hasFile('logo')) {
            Storage::disk('local')->exists(Abonnement::getLogo()) ? Storage::delete(Abonnement::getLogo()) : null;
            $path = $request->file('logo')->store('public/banner/' . auth()->user()->id);
            Abonnement::where('id', auth()->user()->id)->update(['logo' => $path]);
            return response()->json(['status' => 'success', 'message' => 'logo modifié avec succès', 'path' => $path]);
        }
        return response()->json(['status' => 'echec', 'message' => 'aucun fichier envoyé']);
    }

    public function deleteFolderContents()
    {
        $baseDir = 'public/banner/logo';

        if (Storage::exists($baseDir)) {
            $userDirectories = Storage::directories($baseDir);

            foreach ($userDirectories as $userDir) {
                $userId = basename($userDir);

                $logoToKeep = $this->isLogo($userId);
                $logoFileName = basename($logoToKeep);
                $files = Storage::files($userDir);
                foreach ($files as $file) {
                    if (basename($file) !== $logoFileName) {
                        Storage::delete($file);
                    }
                }
            }
        }
        return true;
    }

    public function isLogo($userId)
    {
        $logo = Abonnement::where('user_id', $userId)->pluck('logo')->first();
        return $logo ? basename($logo) : null;
    }

    public function show($folder, $filename)
    {
        $filePath = 'banner/' . $folder . '/' . $filename;

        if (!Storage::disk('public')->exists($filePath)) {
            // Retourner une vue personnalisée ou un message d'erreur
            return response()->view('errors.file_not_found', [], 404);
        }

        return Storage::disk('public')->response($filePath);
    }

    public function showLogo($id)
    {
        $user = User::findOrFail($id);
    
        $filePath = "banner/logo/{$user->id}/" .$this->isLogo($user->id);
        if (!Storage::disk('public')->exists($filePath)) { return response()->view('errors.file_not_found', [], 404); }

        return Storage::disk('public')->response($filePath);
    }

    public function getImagesInFolder($folder)
    {
        $directory = 'public/banner/' . $folder;

        $directories = Storage::directories('public/banner');
        if (!in_array($directory, $directories)) {
            return response()->json(['error' => 'Folder not found.'], 404);
        }

        $files = Storage::files($directory);
        $outfiles = [];

        foreach ($files as $file) {
            $extension = pathinfo($file, PATHINFO_EXTENSION);
            if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif'])) {
                // URL relative
                $relativeUrl = Storage::url($file);

                // Créez l'URL complète en utilisant l'URL du serveur
                $fullUrl = url('files/show/' . $folder . '/' . basename($file));
                $outfiles[] = $fullUrl;
            }
        }

        return response()->json(['outfiles' => $outfiles]);
    }
}
