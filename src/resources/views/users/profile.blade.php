@php
    function renderFileLink($path, $extension, $fileName) {
        if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif'])) {
            return '<img src="' . route('files.show', ['path' => $path]) . '" alt="' . $fileName . '" style="max-width: 200px;">';
        } elseif (in_array($extension, ['mp4', 'webm', 'ogg'])) {
            return '<video controls style="max-width: 200px;"><source src="' . route('files.show', ['path' => $path]) . '" type="video/' . $extension . '">Your browser does not support the video tag.</video>';
        } else {
            return '<a href="' . route('files.download', ['path' => $path]) . '" target="_blank">' . $fileName . '</a>';
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
                                {!! renderFileLink($folder . '/' . $subfile, $extension, $subfile) !!}
                            </li>
                        @endforeach
                    </ul>
                </li>
            @else
                <li>
                    @php
                        $extension = pathinfo($fileOrSubfolder, PATHINFO_EXTENSION);
                    @endphp
                    {!! renderFileLink($fileOrSubfolder, $extension, $fileOrSubfolder) !!}
                </li>
            @endif
        @endforeach
    </ul>
</body>
</html>
