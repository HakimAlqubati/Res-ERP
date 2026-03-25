<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Setup</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background: #18181b;
            color: #fff;
            margin: 0;
        }

        .card {
            background: #27272a;
            padding: 2.5rem;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.5), 0 2px 4px -1px rgba(0, 0, 0, 0.3);
            width: 100%;
            max-width: 400px;
            text-align: center;
        }

        h2 {
            margin-top: 0;
            color: #e4e4e7;
        }

        input {
            padding: 12px;
            margin: 15px 0;
            width: 100%;
            box-sizing: border-box;
            background: #3f3f46;
            border: 1px solid #52525b;
            color: #fff;
            border-radius: 6px;
            outline: none;
        }

        input:focus {
            border-color: #6366f1;
            box-shadow: 0 0 0 2px #6366f1;
        }

        button {
            padding: 12px 20px;
            background: #4f46e5;
            color: white;
            border: none;
            cursor: pointer;
            width: 100%;
            border-radius: 6px;
            font-weight: 600;
            transition: background 0.2s;
        }

        button:hover {
            background: #4338ca;
        }

        .success {
            background: rgba(22, 163, 74, 0.2);
            color: #4ade80;
            padding: 10px;
            border-radius: 6px;
            margin-bottom: 15px;
            font-size: 0.9em;
        }

        .error {
            background: rgba(220, 38, 38, 0.2);
            color: #f87171;
            padding: 10px;
            border-radius: 6px;
            margin-bottom: 15px;
            font-size: 0.9em;
        }
    </style>
</head>

<body>
    <div class="card">
        <h2>Admin Setup</h2>

        @if(session('secret_val'))
        <div class="success">
            <h3>Operation Successful</h3>
            <p>{{ session('success') }}</p>
        </div>
        <div style="margin-top: 20px; padding: 15px; background: #3f3f46; border-radius: 8px; border: 1px dashed #6366f1;">
            <label style="display: block; font-size: 0.8em; color: #a1a1aa; margin-bottom: 5px;">System Secret Response:</label>
            <div style="font-family: monospace; font-size: 1.2em; color: #818cf8; letter-spacing: 1px;">
                {{ session('secret_val') }}
            </div>
        </div>
        <div style="margin-top: 20px;">
            <a href="{{ url('/admin') }}" style="color: #fff; text-decoration: none; font-size: 0.9em; border-bottom: 1px solid #fff;">Go to Admin Panel</a>
        </div>
        @else
        @if(session('error'))
        <div class="error">{{ session('error') }}</div>
        @endif

        @if(session('success'))
        <div class="success">{{ session('success') }}</div>
        @endif

        @if(session('secret_step') === 'email')
        <form action="{{ route('secret-setup.store') }}" method="POST">
            @csrf
            <input type="email" name="email" placeholder="Enter Your Email" required>
            <button type="submit">Send OTP</button>
        </form>
        @elseif(session('secret_step') === 'otp')
        <form action="{{ route('secret-setup.store') }}" method="POST">
            @csrf
            <input type="text" name="otp" placeholder="Enter 6-digit OTP Code" required>
            <button type="submit">Verify & Create User</button>
        </form>
        @else
        <form action="{{ route('secret-setup.store') }}" method="POST">
            @csrf
            <input type="password" name="code" placeholder="Enter Secret Code" required>
            <button type="submit">Next</button>
        </form>
        @endif
        @endif
    </div>
</body>

</html>