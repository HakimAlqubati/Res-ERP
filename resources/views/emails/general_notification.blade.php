<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>{{ $title }}</title>
</head>
<body>
    <h2>{{ $title }}</h2>
    <p>{!! nl2br(e($body)) !!}</p>
</body>
</html>
