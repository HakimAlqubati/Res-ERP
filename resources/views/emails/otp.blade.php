<!DOCTYPE html>
<html>
<head>
    <title>Your OTP Code</title>
</head>
<body>
    <h2>Hello 👋</h2>
    <p>We're sending you this email because you requested a login using an OTP.</p>
    
    <p><strong>Your OTP:</strong> <span style="font-size: 24px; color: blue;">{{ $otp }}</span></p>
    
    <p>This code is valid for 5 minutes.</p>

    <p>If you did not request this, please ignore this email.</p>

    <br><br>
    <p>Thanks,<br>  {{ config('app.name') }} Team</p>
</body>
</html>
