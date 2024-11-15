<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

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

    private function getFiles($directory)
    {
        $files = [];
        $directories = Storage::directories($directory);
        foreach ($directories as $dir) {
            $folderName = basename($dir);
            $files[$folderName] = $this->getFiles($dir);
        }

        foreach (Storage::files($directory) as $file) {
            $files[] = basename($file);
        }

        return $files;
    }

    public function download($path)
    {
        $path = 'public/banner/' . $path;
        if (!Storage::exists($path)) {
            abort(404, 'File not found.');
        }

        return Storage::download($path);
    }

    public function show($path)
    {
        $path = 'public/banner/' . $path;
        if (!Storage::exists($path)) {
            abort(404, 'File not found.');
        }

        return Storage::response($path);
    }

    // public function show($folder, $file)
    // {
    //     $path = 'public/banner/' . $folder . '/' . $file;
    //     if (!Storage::exists($path)) {
    //         abort(404, 'File not found.');
    //     }

    //     return Storage::response($path);
    // }
}
