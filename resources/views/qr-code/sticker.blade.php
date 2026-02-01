<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Equipment Sticker</title>
    <style>
        @page {
            size: 50mm 50mm;
            margin: 0;
        }

        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 2mm;
            width: 50mm;
            height: 50mm;
            text-align: center;
            box-sizing: border-box;
        }

        .container {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
        }

        .qr-code img,
        .qr-code svg {
            width: 35mm !important;
            height: 35mm !important;
        }

        .code {
            margin-top: 2px;
            font-size: 10px;
            font-weight: bold;
            word-wrap: break-word;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="qr-code">
            <!-- Ensure simple QR code generation without complex SVG attributes that mPDF might dislike -->
            {!! str_replace('<?xml version="1.0" encoding="UTF-8"?>', '', \SimpleSoftwareIO\QrCode\Facades\QrCode::size(120)->generate($url)) !!}
        </div>
        <div class="code">{{ $code }}</div>
    </div>
</body>

</html>