<!DOCTYPE html>
<html lang="en" dir="ltr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Payroll System')</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f3f4f6;
        }

        .glass-panel {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }
    </style>
</head>

<body class="text-gray-800">
    <div class="min-h-screen p-6">
        <header class="mb-8 max-w-7xl mx-auto flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-bold text-indigo-700">@yield('header', 'HR & Payroll System')</h1>
                <p class="text-gray-500 mt-1">@yield('subheader')</p>
            </div>
            <div>
                <a href="javascript:history.back()" class="bg-white border border-gray-300 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-50 transition shadow-sm font-medium">
                    Back
                </a>
            </div>
        </header>

        <main class="max-w-7xl mx-auto space-y-6">
            @yield('content')
        </main>

        <footer class="max-w-7xl mx-auto mt-12 text-center text-gray-400 text-sm">
            &copy; {{ date('Y') }} ERP System. All rights reserved.
        </footer>
    </div>
</body>

</html>