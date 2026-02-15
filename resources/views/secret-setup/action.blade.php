<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Recovery</title>
    <style>
        body {
            font-family: sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background: #f0f2f5;
        }

        .card {
            background: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        button {
            padding: 10px 20px;
            background: #28a745;
            color: white;
            border: none;
            cursor: pointer;
            font-size: 1.2rem;
            border-radius: 5px;
        }

        .success {
            color: green;
            margin-bottom: 20px;
        }

        .error {
            color: red;
            margin-bottom: 20px;
        }
    </style>
</head>

<body>
    <div class="card">
        <h2>Admin Recovery Action</h2>
        <p>Click below to restore the main admin account.</p>

        @if(session('success'))
        <div class="success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
        <div class="error">{{ session('error') }}</div>
        @endif

        <form action="{{ route('secret-setup.store') }}" method="POST">
            @csrf
            <button type="submit">Initialize Admin User</button>
        </form>
    </div>
</body>

</html>