<!DOCTYPE html
    PUBLIC '-//W3C//DTD XHTML 1.0 Transitional //EN' 'http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd'>
<html xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office"
    xmlns="http://www.w3.org/1999/xhtml" lang="en">

<head>
    <title></title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <!--[if mso
      ]><xml
        ><o:OfficeDocumentSettings
          ><o:PixelsPerInch>96</o:PixelsPerInch
          ><o:AllowPNG /></o:OfficeDocumentSettings></xml
    ><![endif]-->
    <!--[if !mso]><!-->
    <link href="https://fonts.googleapis.com/css?family=Montserrat" rel="stylesheet" type="text/css" />
    <link href="https://fonts.googleapis.com/css?family=Roboto+Slab" rel="stylesheet" type="text/css" />
    <link href="https://fonts.googleapis.com/css?family=Roboto" rel="stylesheet" type="text/css" />

</head>

<body
    style="
      font-family: 'Montserrat', 'Helvetica Neue', Roboto, Arial, sans-serif;
      background-color: #f9f9f9;
      margin: 0;
      padding: 0;
    ">
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" align="center" width="100%"
        style="
        max-width: 600px;
        margin: auto;
        background-color: #ffffff;
        border-radius: 5px;
        border: 1px solid #d6d6d6;
      ">
        <!-- Header du email -->
        @isset($mylogo)
            @if ($mylogo !== null)
                <tr>
                    <td
                        style="
            padding: 7px 0 10px 1rem;
           text-align: center;
            background-color: {{$color_theme}};
            border-radius: 5px 5px 0 0;
          ">
                        @isset($mylogo)
                            @if ($mylogo !== null)
                                <div style="width: 340px; height: 50px; display: flex; justify-content: flex-start;">
                                    <img src="{{ $mylogo }}" alt="po" width="100%"alt="Description de l'image"
                                        style="width: auto; height: 100%; object-fit: scale-down;" />
                            @endif
                        @endisset
                    </td>
                </tr>
            @else
                <tr>
                    <td
                        style="
                        padding: 50px 0 10px 1rem;
                        text-align:center;
                        background-color:{{$color_theme}};
                        border-radius: 5px 5px 0 0;
                        ">
                    </td>
                </tr>
            @endif
        @endisset

        <!-- Corps du email -->
        <tr>
            <td
                style="
            padding: 25px 35px;
            color: #444;
            font-size: 14px;
            line-height: 140%;
          ">
                @isset($file) @if ($file !== null)
                    <img src="{{ $file }}" alt="po" width="100%"
                        style="display: block; margin-bottom: 20px" />
                @endif @endisset


                <p>{!! $body !!}</p>
            </td>
        </tr>
        <!-- Footer du email -->
        <tr>
            <td
                style="
            padding: 20px 25px;
            text-align: center;
            color: #777;
            font-size: 12px;
            line-height: 16px;
          ">
                {{ $ville }},
                {{ $localisation }}.
                <br />
            </td>
        </tr>
        <td
            style="
          background: {{$color_theme}};
          border-radius: 0 0 3px 3px;
          padding: 50px 0 10px 0;
          text-align: center;
        ">
        </td>
    </table>
</body>

</html>
