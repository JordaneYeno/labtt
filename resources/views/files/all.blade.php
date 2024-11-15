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
                                @if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif']))
                                    <img src="{{ route('files.show', ['path' => $folder . '/' . $subfile]) }}" alt="{{ $subfile }}" style="max-width: 200px;">
                                @elseif (in_array($extension, ['mp4', 'webm', 'ogg']))
                                    <video controls style="max-width: 200px;">
                                        <source src="{{ route('files.show', ['path' => $folder . '/' . $subfile]) }}" type="video/{{ $extension }}">
                                        Your browser does not support the video tag.
                                    </video>
                                @else
                                    <a href="{{ route('files.download', ['path' => $folder . '/' . $subfile]) }}" target="_blank">
                                        {{ $subfile }}
                                    </a>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </li>
            @else
                <li>
                    @php
                        $extension = pathinfo($fileOrSubfolder, PATHINFO_EXTENSION);
                    @endphp
                    @if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif']))
                        <img src="{{ route('files.show', ['path' => $fileOrSubfolder]) }}" alt="{{ $fileOrSubfolder }}" style="max-width: 200px;">
                    @elseif (in_array($extension, ['mp4', 'webm', 'ogg']))
                        <video controls style="max-width: 200px;">
                            <source src="{{ route('files.show', ['path' => $fileOrSubfolder]) }}" type="video/{{ $extension }}">
                            Your browser does not support the video tag.
                        </video>
                    @else
                        <a href="{{ route('files.download', ['path' => $fileOrSubfolder]) }}" target="_blank">
                            {{ $fileOrSubfolder }}
                        </a>
                    @endif
                </li>
            @endif
        @endforeach
    </ul>
</body>
</html>
