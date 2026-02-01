<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Equipment QR Code</title>
    <style>
        @page {
            size: 50mm 50mm;
            margin: 0;
        }

        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            width: 50mm;
            height: 50mm;
            text-align: center;
        }

        table {
            width: 100%;
            height: 50mm; /* Force height */
            border-collapse: collapse;
            page-break-inside: avoid;
        }

        td {
            vertical-align: top;
            text-align: center;
            padding: 0;
        }

        .qr-code {
            margin-top: 5mm;
            margin-bottom: 2px;
        }

        .qr-code img,
        .qr-code svg {
            width: 35mm !important;
            height: 35mm !important;
        }

        .code {
            font-size: 8px; /* Slightly smaller to fit better if long */
            font-weight: bold;
            word-wrap: break-word;
            padding: 0 2mm;
        }
    </style>
</head>

<body>
    <table>
        <tr>
            <td>
                <div class="qr-code">
                    {!! str_replace('<?xml version="1.0" encoding="UTF-8"?>', '', \SimpleSoftwareIO\QrCode\Facades\QrCode::size(130)->generate($url)) !!}
                </div>
                <div class="code">{{ $code }}</div>
            </td>
        </tr>
    </table>
</body>

</html>