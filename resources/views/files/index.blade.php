@php
    function renderFileLink($path, $extension, $fileName) {
        // Afficher l'image si c'est une image
        if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif'])) {
            return '<img src="' . route('files.show', ['folder' => $path, 'filename' => $fileName]) . '" alt="' . $fileName . '" style="max-width: 200px;">';
        }
        // Afficher la vidéo si c'est une vidéo
        elseif (in_array($extension, ['mp4', 'webm', 'ogg'])) {
            return '<video controls style="max-width: 200px;">
                        <source src="' . route('files.show', ['folder' => $path, 'filename' => $fileName]) . '" type="video/' . $extension . '">
                        Your browser does not support the video tag.
                    </video>';
        }
        // Afficher un lien pour télécharger d'autres types de fichiers
        elseif (in_array($extension, ['mp3', 'pdf', 'doc', 'xls', 'txt'])) {
            return '<a href="' . route('files.show', ['folder' => $path, 'filename' => $fileName]) . '" target="_blank">' . $fileName . '</a>';
        } else {
            // Pour les autres types de fichiers (si nécessaire)
            return '<a href="' . route('files.show', ['folder' => $path, 'filename' => $fileName]) . '" target="_blank">' . $fileName . '</a>';
        }
    }
@endphp

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Files</title>
</head>
<body>
    <h1>Files</h1>
    <ul>
        @foreach ($files as $folder => $fileOrSubfolder)
            @if (is_array($fileOrSubfolder))
                <li>
                    <strong>{{ $folder }}</strong>
                    <ul>
                        @foreach ($fileOrSubfolder as $subfile)
                            <li>
                                @php
                                    $extension = pathinfo($subfile, PATHINFO_EXTENSION);
                                @endphp
                                {!! renderFileLink($folder, $extension, $subfile) !!}
                            </li>
                        @endforeach
                    </ul>
                </li>
            @else
                <li>
                    @php
                        $extension = pathinfo($fileOrSubfolder, PATHINFO_EXTENSION);
                    @endphp
                    {!! renderFileLink($folder, $extension, $fileOrSubfolder) !!}
                </li>
            @endif
        @endforeach
    </ul>
</body>
</html>
