<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Code Print Sheet</title>
    <style>
        @page {
            size: 50mm 50mm;
            margin: 0;
        }

        body {
            font-family: Arial, sans-serif;
            text-align: center;
            margin: 0;
            padding: 2mm;
            width: 50mm;
            height: 50mm;
            box-sizing: border-box;
            overflow: hidden;
        }

        .header {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 5px;
            padding-bottom: 2px;
            margin-bottom: 2px;
            height: 15px;
        }

        .logo {
            width: 12px;
            height: 12px;
        }

        .title {
            font-size: 9px;
            font-weight: bold;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 35mm;
        }

        .qr-block {
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 0 auto;
        }

        .qr-block svg {
            width: 32mm !important;
            height: 32mm !important;
        }

        .asset-tag {
            font-size: 10px;
            font-weight: bold;
            margin-top: 2px;
        }

        @media print {
            body {
                margin: 0;
                padding: 2mm;
            }
        }
    </style>
</head>

<body>

    <!-- Header -->
    <div class="header">
        <img src="{{ url('/') . '/storage/logo/default.png' }}" alt="Logo" class="logo">
        <div class="title">{{ $qrCode['name'] }}</div>
    </div>

    <!-- QR Code -->
    <div class="qr-block">
        {!! QrCode::size(200)->generate($qrCode['data']) !!}
    </div>

    <div class="asset-tag">{{ $qrCode['asset_tag'] }}</div>

</body>

</html>