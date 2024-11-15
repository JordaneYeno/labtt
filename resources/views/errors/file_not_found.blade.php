<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fichier non trouvé</title>

    <style type="text/css">
        *,
        body {
            box-sizing: border-box;
            margin: 0;
            padding: 0
        }

        .container_line {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            width: 100%;
        }

        .fixedwidth {
            text-decoration: none;
            height: auto;
            border: 0;
            width: 537px;
            max-width: 100%;
            display: block;
            margin-left : -5rem!important
        }

        @media only screen and (max-width: 600px) { .fixedwidth {margin-left : -3rem!important} }
    </style>
</head>

<body>

    <div class="container_line">
        <img class='center fixedwidth' align='center' border='0'
            src="{{ asset('images/404_error_page_not_found.gif') }}">
    </div>
</body>

</html>
