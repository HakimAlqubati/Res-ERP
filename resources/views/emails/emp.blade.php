<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Your Account Details</title>
</head>
<body>
    <h2>Welcome, {{ $name }}!</h2>

    <p>Your HRM account has been created.</p>

    <p><strong>Email:</strong> {{ $email }}</p>
    <p><strong>Password:</strong> {{ '123456' }}</p>

    <p>Please log in and complete your profile as soon as possible.</p>

    <p>Regards,<br>HR Department</p>
</body>
</html>
