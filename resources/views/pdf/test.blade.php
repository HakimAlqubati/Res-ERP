<!DOCTYPE html>
<html lang="ar">

<head>
    <meta charset="UTF-8">
    <title>{{ 'sdf' }}</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            direction: rtl;
            text-align: right;
        }

        h1 {
            color: darkblue;
        }
    </style>
</head>

<body>
    <img style="top: 12px;width: 51px;" src="{{ url('/') . '/storage/logo/default.png' }}" alt="Company Logo"
        class="logo-right">

    <h1>{{ $employee?->name }}</h1>  
</body>

</html>
