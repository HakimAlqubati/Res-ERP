<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title></title>
</head>

<body>
    <h3>مرحباً {{ $user->name }}</h3>
    <p>هذه قائمة المنتجات التي وصلت إلى الحد الأدنى في المخزون:</p>

    <ul>
        @foreach ($products as $product)
            <li>
                {{ $product['name_ar'] }} - الكمية المتبقية: {{ $product['remaining_qty'] }} ({{ $product['unit'] }})
            </li>
        @endforeach
    </ul>

</body>

</html>
