<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $meta['title'] }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            DEFAULT: '#0d7c66',
                            light: '#10a37f',
                            dark: '#095c4c'
                        }
                    }
                }
            }
        }
    </script>
    <style>
        body {
            font-family: 'Tajawal', sans-serif;
        }

        .animate-fade-in {
            animation: fade-in 0.3s ease;
        }

        @keyframes fade-in {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @media (max-width: 900px) {
            aside {
                position: relative !important;
                width: 100% !important;
                height: auto !important;
            }

            main {
                margin-right: 0 !important;
                max-width: 100% !important;
            }

            .layout {
                flex-direction: column;
            }
        }
    </style>
</head>

<body class="bg-gradient-to-br from-[#0a1f1c] via-[#0f2922] to-[#1a3d35] min-h-screen text-gray-200">

    @php
    $tabs = [
    'required' => [
    'title' => $labels['requiredTitle'],
    'icon' => '✓',
    'items' => $requiredItems,
    'columns' => ['name' => $labels['itemColumn'], 'reason' => $labels['reasonColumn']],
    ],
    'notRequired' => [
    'title' => $labels['notRequiredTitle'],
    'icon' => '✗',
    'items' => $notRequiredItems,
    'columns' => ['name' => $labels['itemColumn'], 'reason' => $labels['reasonColumn']],
    ],
    'categories' => [
    'title' => $labels['categoriesTitle'],
    'icon' => '📂',
    'items' => $financialCategories,
    'columns' => ['name' => $labels['itemColumn'], 'type' => $labels['typeColumn']],
    ],
    ];
    @endphp

    <div class="layout flex min-h-screen">

        {{-- Sidebar --}}
        <aside class="w-72 bg-[#0a1f1c]/95 border-l border-primary/20 p-6 fixed top-0 right-0 h-screen overflow-y-auto z-50">
            <a href="{{ url('/admin') }}" class="inline-flex items-center gap-2 text-primary-light hover:text-green-400 text-sm mb-5 transition-colors">
                → {{ $labels['backLink'] }}
            </a>

            <div class="text-center mb-8 pb-5 border-b border-primary/20">
                <h1 class="text-xl font-bold text-white mb-2">{{ $meta['title'] }}</h1>
                <p class="text-sm text-gray-400">{{ $meta['description'] }}</p>
            </div>

            <div class="flex flex-col gap-3">
                @foreach($tabs as $id => $tab)
                <button onclick="showTab('{{ $id }}', this)" class="tab-btn w-full p-4 bg-primary/10 border border-primary/20 rounded-xl text-gray-400 font-semibold cursor-pointer transition-all hover:bg-primary/20 hover:-translate-x-1 flex items-center gap-3 text-right {{ $loop->first ? 'active border-r-4 border-r-primary-light text-white' : '' }}">
                    <span class="text-lg w-6 text-center">{{ $tab['icon'] }}</span>
                    <span class="flex-1">{{ $tab['title'] }}</span>
                    <span class="bg-white/15 px-2.5 py-1 rounded-full text-xs">{{ count($tab['items']) }}</span>
                </button>
                @endforeach
            </div>
 
        </aside>

        {{-- Main Content --}}
        <main class="flex-1 mr-72 p-10 overflow-y-auto h-screen">

            @foreach($tabs as $id => $tab)
            <div id="{{ $id }}" class="tab-content {{ $loop->first ? 'block' : 'hidden' }} bg-primary/10 border border-primary/20 rounded-2xl p-8 animate-fade-in">
                <div class="mb-6 pb-4 border-b border-primary/20">
                    <h2 class="text-2xl font-bold text-primary-light flex items-center gap-3">
                        {{ $tab['icon'] }} {{ $tab['title'] }}
                    </h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr>
                                @foreach($tab['columns'] as $col)
                                <th class="text-right p-4 bg-primary/95 font-semibold text-white sticky -top-10 z-10 {{ $loop->first ? 'rounded-tr-lg' : 'rounded-tl-lg' }}">{{ $col }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($tab['items'] as $item)
                            <tr class="hover:bg-primary/10 transition-colors">
                                @foreach($tab['columns'] as $key => $label)
                                <td class="p-4 border-b border-primary/10 {{ $loop->first ? 'font-medium text-white' : 'text-gray-400 text-sm' }}">{{ $item[$key] }}</td>
                                @endforeach
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endforeach

            {{-- Golden Rule --}}
            <div class="mt-8 bg-gradient-to-br from-primary/25 to-primary-dark/25 border border-primary/40 rounded-2xl p-6 text-center">
                <h3 class="text-lg text-primary-light mb-3">📌 {{ $labels['goldenRuleTitle'] }}</h3>
                <p class="text-lg text-white leading-relaxed">{{ $goldenRule }}</p>
            </div>

            {{-- Export Button --}}
            <div class="mt-8 text-center">
                <button onclick="exportToExcel()" class="inline-flex items-center gap-3 px-8 py-4 bg-primary hover:bg-primary-light text-white font-semibold rounded-xl transition-all hover:scale-105 shadow-lg">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    تصدير إلى Excel
                </button>
            </div>

        </main>
    </div>

    <script src="https://cdn.sheetjs.com/xlsx-0.20.1/package/dist/xlsx.full.min.js"></script>
    <script>
        function showTab(tabId, btn) {
            document.querySelectorAll('.tab-content').forEach(c => {
                c.classList.add('hidden');
                c.classList.remove('block');
            });
            document.querySelectorAll('.tab-btn').forEach(b => {
                b.classList.remove('active', 'border-r-4', 'border-r-primary-light', 'text-white');
                b.classList.add('text-gray-400');
            });
            document.getElementById(tabId).classList.remove('hidden');
            document.getElementById(tabId).classList.add('block');
            btn.classList.add('active', 'border-r-4', 'border-r-primary-light', 'text-white');
            btn.classList.remove('text-gray-400');
        }

        function exportToExcel() {
            const wb = XLSX.utils.book_new();

            // Tab 1: Required Items
            const requiredData = [
                ['{{ $labels["itemColumn"] }}', '{{ $labels["reasonColumn"] }}'],
                @foreach($requiredItems as $item)['{{ $item["name"] }}', '{{ $item["reason"] }}'],
                @endforeach
            ];
            const ws1 = XLSX.utils.aoa_to_sheet(requiredData);
            XLSX.utils.book_append_sheet(wb, ws1, '{{ $labels["requiredTitle"] }}'.substring(0, 31));

            // Tab 2: Not Required Items
            const notRequiredData = [
                ['{{ $labels["itemColumn"] }}', '{{ $labels["reasonColumn"] }}'],
                @foreach($notRequiredItems as $item)['{{ $item["name"] }}', '{{ $item["reason"] }}'],
                @endforeach
            ];
            const ws2 = XLSX.utils.aoa_to_sheet(notRequiredData);
            XLSX.utils.book_append_sheet(wb, ws2, '{{ $labels["notRequiredTitle"] }}'.substring(0, 31));

            // Tab 3: Financial Categories
            const categoriesData = [
                ['{{ $labels["itemColumn"] }}', '{{ $labels["typeColumn"] }}'],
                @foreach($financialCategories as $category)['{{ $category["name"] }}', '{{ $category["type"] }}'],
                @endforeach
            ];
            const ws3 = XLSX.utils.aoa_to_sheet(categoriesData);
            XLSX.utils.book_append_sheet(wb, ws3, '{{ $labels["categoriesTitle"] }}'.substring(0, 31));

            // Golden Rule Sheet
            const goldenRuleData = [
                ['{{ $labels["goldenRuleTitle"] }}'],
                ['{{ $goldenRule }}'],
            ];
            const ws4 = XLSX.utils.aoa_to_sheet(goldenRuleData);
            XLSX.utils.book_append_sheet(wb, ws4, 'القاعدة الذهبية');

            // Export
            XLSX.writeFile(wb, 'تقرير_بنود_الراتب_والتسجيل_المالي.xlsx');
        }
    </script>
</body>

</html>