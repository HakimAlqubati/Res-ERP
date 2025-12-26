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
    </style>
</head>

<body class="bg-gradient-to-br from-[#0a1f1c] via-[#0f2922] to-[#1a3d35] min-h-screen text-gray-200">

    @php
    $tabs = [
    'required' => [
    'title' => $labels['requiredTitle'],
    'shortTitle' => 'تحتاج تسجيل',
    'icon' => '✓',
    'items' => $requiredItems,
    'columns' => ['name' => $labels['itemColumn'], 'reason' => $labels['reasonColumn']],
    ],
    'notRequired' => [
    'title' => $labels['notRequiredTitle'],
    'shortTitle' => 'لا تحتاج تسجيل',
    'icon' => '✗',
    'items' => $notRequiredItems,
    'columns' => ['name' => $labels['itemColumn'], 'reason' => $labels['reasonColumn']],
    ],
    'categories' => [
    'title' => $labels['categoriesTitle'],
    'shortTitle' => 'الفئات المالية',
    'icon' => '📂',
    'items' => $financialCategories,
    'columns' => ['name' => $labels['itemColumn'], 'type' => $labels['typeColumn']],
    ],
    ];
    @endphp

    {{-- Mobile Header (visible on mobile only) --}}
    <header class="lg:hidden sticky top-0 z-50 bg-[#0a1f1c]/95 backdrop-blur-md border-b border-primary/20 p-4">
        <div class="flex items-center justify-between">
            <a href="{{ url('/admin') }}" class="text-primary-light text-sm">← {{ $labels['backLink'] }}</a>
            <img src="{{ asset('workbench.png') }}" alt="Logo" class="w-8 h-auto opacity-80">
        </div>
        <h1 class="text-lg font-bold text-white mt-3 text-center">{{ $meta['title'] }}</h1>
        <p class="text-xs text-gray-400 text-center mt-1">{{ $meta['description'] }}</p>
    </header>

    <div class="flex flex-col lg:flex-row min-h-screen">

        {{-- Sidebar (hidden on mobile, visible on desktop) --}}
        <aside class="hidden lg:block w-72 bg-[#0a1f1c]/95 border-l border-primary/20 p-6 fixed top-0 right-0 h-screen overflow-y-auto z-50">
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
                    <span class="flex-1 text-sm">{{ $tab['title'] }}</span>
                    <span class="bg-white/15 px-2 py-0.5 rounded-full text-xs">{{ count($tab['items']) }}</span>
                </button>
                @endforeach
            </div>

            <div class="absolute bottom-5 left-5 right-5 text-center pt-5 border-t border-primary/20">
                <img src="{{ asset('workbench.png') }}" alt="Logo" class="w-9 h-auto mx-auto mb-2 opacity-80">
                <span class="text-gray-500 text-xs">{{ $meta['brand'] }}</span>
            </div>
        </aside>

        {{-- Mobile Tabs (visible on mobile only) --}}
        <div class="lg:hidden sticky top-[120px] z-40 bg-[#0a1f1c]/95 backdrop-blur-md px-3 py-3 border-b border-primary/20">
            <div class="flex gap-2 overflow-x-auto pb-1 scrollbar-hide">
                @foreach($tabs as $id => $tab)
                <button onclick="showTab('{{ $id }}', this)" class="tab-btn-mobile flex-shrink-0 px-4 py-2.5 bg-primary/10 border border-primary/20 rounded-full text-gray-400 font-medium text-sm whitespace-nowrap transition-all {{ $loop->first ? 'active bg-primary/30 text-white border-primary-light' : '' }}">
                    <span>{{ $tab['icon'] }}</span>
                    <span>{{ $tab['shortTitle'] }}</span>
                    <span class="bg-white/15 px-1.5 py-0.5 rounded-full text-xs mr-1">{{ count($tab['items']) }}</span>
                </button>
                @endforeach
            </div>
        </div>

        {{-- Main Content --}}
        <main class="flex-1 lg:mr-72 p-4 lg:p-10 overflow-y-auto">

            @foreach($tabs as $id => $tab)
            <div id="{{ $id }}" class="tab-content {{ $loop->first ? 'block' : 'hidden' }} bg-primary/10 border border-primary/20 rounded-2xl p-4 lg:p-8 animate-fade-in mb-4 lg:mb-0">

                {{-- Header --}}
                <div class="mb-4 lg:mb-6 pb-3 lg:pb-4 border-b border-primary/20">
                    <h2 class="text-lg lg:text-2xl font-bold text-primary-light flex items-center gap-2 lg:gap-3">
                        {{ $tab['icon'] }} {{ $tab['title'] }}
                    </h2>
                </div>

                {{-- Desktop Table --}}
                <div class="hidden lg:block overflow-x-auto">
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

                {{-- Mobile Cards --}}
                <div class="lg:hidden space-y-3">
                    @foreach($tab['items'] as $item)
                    <div class="bg-primary/5 border border-primary/15 rounded-xl p-4">
                        @foreach($tab['columns'] as $key => $label)
                        <div class="{{ !$loop->first ? 'mt-2 pt-2 border-t border-primary/10' : '' }}">
                            <span class="text-xs text-gray-500 block mb-1">{{ $label }}</span>
                            <span class="{{ $loop->first ? 'font-medium text-white text-sm' : 'text-gray-400 text-xs leading-relaxed' }}">{{ $item[$key] }}</span>
                        </div>
                        @endforeach
                    </div>
                    @endforeach
                </div>

            </div>
            @endforeach

            {{-- Golden Rule --}}
            <div class="mt-4 lg:mt-8 bg-gradient-to-br from-primary/25 to-primary-dark/25 border border-primary/40 rounded-2xl p-4 lg:p-6 text-center">
                <h3 class="text-base lg:text-lg text-primary-light mb-2 lg:mb-3">📌 {{ $labels['goldenRuleTitle'] }}</h3>
                <p class="text-sm lg:text-lg text-white leading-relaxed">{{ $goldenRule }}</p>
            </div>

            {{-- Export Button --}}
            <div class="mt-4 lg:mt-8 text-center pb-8">
                <button onclick="exportToExcel()" class="inline-flex items-center gap-2 lg:gap-3 px-6 lg:px-8 py-3 lg:py-4 bg-primary hover:bg-primary-light text-white font-semibold rounded-xl transition-all hover:scale-105 shadow-lg text-sm lg:text-base">
                    <svg class="w-4 h-4 lg:w-5 lg:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
            // Hide all tabs
            document.querySelectorAll('.tab-content').forEach(c => {
                c.classList.add('hidden');
                c.classList.remove('block');
            });

            // Desktop buttons
            document.querySelectorAll('.tab-btn').forEach(b => {
                b.classList.remove('active', 'border-r-4', 'border-r-primary-light', 'text-white');
                b.classList.add('text-gray-400');
            });

            // Mobile buttons
            document.querySelectorAll('.tab-btn-mobile').forEach(b => {
                b.classList.remove('active', 'bg-primary/30', 'text-white', 'border-primary-light');
                b.classList.add('text-gray-400', 'bg-primary/10');
            });

            // Show selected tab
            document.getElementById(tabId).classList.remove('hidden');
            document.getElementById(tabId).classList.add('block');

            // Activate button
            if (btn.classList.contains('tab-btn')) {
                btn.classList.add('active', 'border-r-4', 'border-r-primary-light', 'text-white');
                btn.classList.remove('text-gray-400');
                // Also update mobile version
                document.querySelectorAll('.tab-btn-mobile').forEach((b, i) => {
                    if (b.getAttribute('onclick').includes(tabId)) {
                        b.classList.add('active', 'bg-primary/30', 'text-white', 'border-primary-light');
                        b.classList.remove('text-gray-400', 'bg-primary/10');
                    }
                });
            } else {
                btn.classList.add('active', 'bg-primary/30', 'text-white', 'border-primary-light');
                btn.classList.remove('text-gray-400', 'bg-primary/10');
                // Also update desktop version
                document.querySelectorAll('.tab-btn').forEach((b, i) => {
                    if (b.getAttribute('onclick').includes(tabId)) {
                        b.classList.add('active', 'border-r-4', 'border-r-primary-light', 'text-white');
                        b.classList.remove('text-gray-400');
                    }
                });
            }
        }

        function exportToExcel() {
            const wb = XLSX.utils.book_new();
            const requiredData = [
                ['{{ $labels["itemColumn"] }}', '{{ $labels["reasonColumn"] }}'], @foreach($requiredItems as $item)['{{ $item["name"] }}', '{{ $item["reason"] }}'], @endforeach
            ];
            XLSX.utils.book_append_sheet(wb, XLSX.utils.aoa_to_sheet(requiredData), '{{ $labels["requiredTitle"] }}'.substring(0, 31));
            const notRequiredData = [
                ['{{ $labels["itemColumn"] }}', '{{ $labels["reasonColumn"] }}'], @foreach($notRequiredItems as $item)['{{ $item["name"] }}', '{{ $item["reason"] }}'], @endforeach
            ];
            XLSX.utils.book_append_sheet(wb, XLSX.utils.aoa_to_sheet(notRequiredData), '{{ $labels["notRequiredTitle"] }}'.substring(0, 31));
            const categoriesData = [
                ['{{ $labels["itemColumn"] }}', '{{ $labels["typeColumn"] }}'], @foreach($financialCategories as $category)['{{ $category["name"] }}', '{{ $category["type"] }}'], @endforeach
            ];
            XLSX.utils.book_append_sheet(wb, XLSX.utils.aoa_to_sheet(categoriesData), '{{ $labels["categoriesTitle"] }}'.substring(0, 31));
            XLSX.utils.book_append_sheet(wb, XLSX.utils.aoa_to_sheet([
                ['{{ $labels["goldenRuleTitle"] }}'],
                ['{{ $goldenRule }}']
            ]), 'القاعدة الذهبية');
            XLSX.writeFile(wb, 'تقرير_بنود_الراتب_والتسجيل_المالي.xlsx');
        }
    </script>
</body>

</html>