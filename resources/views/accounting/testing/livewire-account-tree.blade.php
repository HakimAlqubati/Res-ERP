<div>
    {{-- Header with Search and Controls --}}
    <div class="mb-6 p-4 bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800">
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            {{-- Search Input --}}
            <div class="relative flex-1 max-w-md">
                <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                    <svg width="20" height="20" class="text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
                <input wire:model.live.debounce.300ms="search"
                    type="text"
                    placeholder="بحث في الحسابات..."
                    class="w-full pr-10 pl-4 py-2.5 text-sm bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all duration-200 placeholder-gray-400">
            </div>

            {{-- Controls --}}
            <div class="flex items-center gap-3">
                <button wire:click="expandAll"
                    class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-700 transition-all duration-200">
                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4" />
                    </svg>
                    <span>توسيع الكل</span>
                </button>

                <button wire:click="collapseAll"
                    class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-700 transition-all duration-200">
                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 9V4.5M9 9H4.5M9 9L3.75 3.75M9 15v4.5M9 15H4.5M9 15l-5.25 5.25M15 9h4.5M15 9V4.5M15 9l5.25-5.25M15 15h4.5M15 15v4.5m0-4.5l5.25 5.25" />
                    </svg>
                    <span>طي الكل</span>
                </button>

                {{-- Stats Badge --}}
                <div class="hidden sm:flex items-center gap-2 px-4 py-2.5 bg-gradient-to-r from-primary-500/10 to-primary-600/10 dark:from-primary-500/20 dark:to-primary-600/20 rounded-xl border border-primary-200 dark:border-primary-800">
                    <svg width="20" height="20" class="text-primary-600 dark:text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                    <span class="text-sm font-semibold text-primary-700 dark:text-primary-300">{{ $totalAccounts }} حساب</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Tree Container --}}
    <div class="p-4 sm:p-6 bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800">
        <div class="space-y-1">
            @php
            $accountTypeColors = [
            'assets' => ['bg' => 'bg-blue-500', 'light' => 'bg-blue-50 dark:bg-blue-900/30', 'text' => 'text-blue-600 dark:text-blue-400', 'border' => 'border-blue-200 dark:border-blue-800'],
            'liabilities' => ['bg' => 'bg-red-500', 'light' => 'bg-red-50 dark:bg-red-900/30', 'text' => 'text-red-600 dark:text-red-400', 'border' => 'border-red-200 dark:border-red-800'],
            'equity' => ['bg' => 'bg-purple-500', 'light' => 'bg-purple-50 dark:bg-purple-900/30', 'text' => 'text-purple-600 dark:text-purple-400', 'border' => 'border-purple-200 dark:border-purple-800'],
            'revenue' => ['bg' => 'bg-emerald-500', 'light' => 'bg-emerald-50 dark:bg-emerald-900/30', 'text' => 'text-emerald-600 dark:text-emerald-400', 'border' => 'border-emerald-200 dark:border-emerald-800'],
            'expenses' => ['bg' => 'bg-orange-500', 'light' => 'bg-orange-50 dark:bg-orange-900/30', 'text' => 'text-orange-600 dark:text-orange-400', 'border' => 'border-orange-200 dark:border-orange-800'],
            'other' => ['bg' => 'bg-gray-500', 'light' => 'bg-gray-50 dark:bg-gray-800', 'text' => 'text-gray-600 dark:text-gray-400', 'border' => 'border-gray-200 dark:border-gray-700'],
            ];

            $renderTree = function($items, $level = 0) use (&$renderTree, $expandedNodes, $accountTypeColors) {
            $isRtl = true;
            $marginClass = $isRtl ? 'mr-6' : 'ml-6';

            $output = '<ul class="relative space-y-2 ' . ($level > 0 ? $marginClass : '') . '">';

                foreach ($items as $index => $item) {
                $isExpanded = in_array($item['id'], $expandedNodes);
                $hasChildren = $item['has_children'];
                $colors = $accountTypeColors[$item['account_type']] ?? $accountTypeColors['other'];
                $isLast = $index === count($items) - 1;

                $output .= '<li class="relative">';

                    // Vertical connecting line for children
                    if ($level > 0) {
                    $output .= '<div class="absolute right-[-1.5rem] top-0 bottom-0 w-px bg-gray-200 dark:bg-gray-700"></div>';
                    // Horizontal connector line
                    $output .= '<div class="absolute right-[-1.5rem] top-5 w-6 h-px bg-gray-200 dark:bg-gray-700"></div>';
                    // Connection dot
                    $output .= '<div class="absolute right-[-1.75rem] top-[0.95rem] w-2 h-2 rounded-full border-2 border-white dark:border-gray-900 ' . $colors['bg'] . ' z-10"></div>';

                    // Hide line for last item after the dot
                    if ($isLast) {
                    $output .= '<div class="absolute right-[-1.6rem] top-5 bottom-0 w-1 bg-white dark:bg-gray-900"></div>';
                    }
                    }

                    $output .= '<div class="group">';

                        // Main Node Card
                        $cardClasses = $level === 0
                        ? 'p-3 rounded-xl border-2 ' . $colors['border'] . ' ' . $colors['light'] . ' shadow-sm hover:shadow-md'
                        : 'p-2.5 rounded-lg border ' . ($hasChildren ? $colors['border'] . ' ' . $colors['light'] : 'border-gray-100 dark:border-gray-800 bg-gray-50 dark:bg-gray-800/50') . ' hover:shadow-sm';

                        $output .= '<div wire:click="toggleNode(' . $item['id'] . ')" class="flex items-center gap-2 cursor-pointer transition-all duration-300 ' . $cardClasses . '">';

                            // Toggle/Expand Icon
                            if ($hasChildren) {
                            $output .= '<div class="flex-shrink-0 w-6 h-6 flex items-center justify-center rounded ' . $colors['bg'] . ' text-white transition-transform duration-300 ' . ($isExpanded ? 'rotate-90' : '') . '">';
                                $output .= '<svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" />
                                </svg>';
                                $output .= '</div>';

                            // Folder Icon
                            $output .= '<div class="flex-shrink-0 w-7 h-7 flex items-center justify-center rounded-lg ' . $colors['light'] . '">';
                                if ($isExpanded) {
                                $output .= '<svg width="18" height="18" class="' . $colors['text'] . '" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 19a2 2 0 01-2-2V7a2 2 0 012-2h4l2 2h4a2 2 0 012 2v1M5 19h14a2 2 0 002-2v-5a2 2 0 00-2-2H9a2 2 0 00-2 2v5a2 2 0 01-2 2z" />
                                </svg>';
                                } else {
                                $output .= '<svg width="18" height="18" class="' . $colors['text'] . '" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                                </svg>';
                                }
                                $output .= '</div>';
                            } else {
                            // Empty spacer for alignment
                            $output .= '<div class="w-6"></div>';

                            // File/Document Icon
                            $output .= '<div class="flex-shrink-0 w-7 h-7 flex items-center justify-center rounded-lg bg-gray-100 dark:bg-gray-800">';
                                $output .= '<svg width="14" height="14" class="text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>';
                                $output .= '</div>';
                            }

                            // Account Code & Name
                            $output .= '<div class="flex-grow min-w-0 flex items-center gap-2">';
                                $output .= '<span class="flex-shrink-0 px-2 py-0.5 rounded font-mono text-xs font-bold ' . $colors['light'] . ' ' . $colors['text'] . ' border ' . $colors['border'] . '">' . $item['account_code'] . '</span>';
                                $output .= '<span class="font-medium text-gray-800 dark:text-gray-100 truncate text-sm">' . $item['account_name'] . '</span>';
                                $output .= '</div>';

                            // Children Count Badge
                            if ($hasChildren) {
                            $output .= '<span class="flex-shrink-0 px-2 py-0.5 rounded-full text-xs font-bold ' . $colors['bg'] . ' text-white">' . $item['children_count'] . '</span>';
                            }

                            $output .= '</div>';

                        // Children Container with Animation
                        if ($hasChildren && $isExpanded) {
                        $output .= '<div class="mt-2 overflow-hidden transition-all duration-500 ease-in-out">';
                            $output .= $renderTree($item['children'], $level + 1);
                            $output .= '</div>';
                        }

                        $output .= '</div>';
                    $output .= '</li>';
                }

                $output .= '</ul>';
            return $output;
            };
            @endphp

            @if(count($tree) > 0)
            <div class="relative">
                {!! $renderTree($tree) !!}
            </div>
            @else
            <div class="flex flex-col items-center justify-center py-12 text-center">
                <div class="w-16 h-16 mb-4 flex items-center justify-center rounded-full bg-gray-100 dark:bg-gray-800">
                    <svg width="32" height="32" class="text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-300 mb-1">لا توجد نتائج</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">جرب البحث بكلمات مختلفة</p>
            </div>
            @endif
        </div>
    </div>

    <style>
        /* Ensure SVG sizing works properly */
        svg {
            flex-shrink: 0;
        }
    </style>
</div>