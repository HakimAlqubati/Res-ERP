<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>شجرة الحسابات</title>
    <link rel="icon" type="image/png" href="{{ asset('workbench.png') }}">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#0d7c66',
                    },
                    fontFamily: {
                        cairo: ['Cairo', 'sans-serif'],
                    },
                }
            }
        }
    </script>
    @livewireStyles
</head>

<body class="bg-gray-50 font-cairo text-gray-800 antialiased">
    <div class="min-h-screen py-10 px-4">
        <div class="max-w-4xl mx-auto">
            <header class="text-center mb-10">
                <h1 class="text-4xl font-bold text-primary flex items-center justify-center gap-4">
                    <span>📊</span>
                    <span>دليل الحسابات</span>
                </h1>
            </header>

            <main>
                @livewire('accounting::account-tree')
            </main>

            <footer class="mt-12 text-center text-gray-400 text-sm">
                &copy; {{ date('Y') }} Workbench
            </footer>
        </div>
    </div>

    @livewireScripts
</body>

</html>