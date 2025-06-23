<x-filament-panels::page>

    @push('styles')
        <style>
            .report-tile {
                transition: all 0.3s ease-in-out;
                transform: scale(1);
                opacity: 0;
                transform: translateY(20px);
                animation: fadeInUp 0.6s ease forwards;
            }

            .report-tile:hover {
                transform: scale(1.05) translateY(-4px);
                box-shadow: 0 12px 24px rgba(0, 0, 0, 0.1);
                border-color: #0d7c66;
                background: linear-gradient(135deg, #ffffff, #f3f4f6);
            }

            .report-tile:hover::after {
                content: "";
                position: absolute;
                inset: 0;
                border-radius: 0.75rem;
                box-shadow: 0 0 0 3px rgba(13, 124, 102, 0.2);
                pointer-events: none;
                transition: opacity 0.3s ease-in-out;
            }

            @keyframes fadeInUp {
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }

            @media (prefers-color-scheme: dark) {
                .report-tile {
                    background-color: #111827;
                    color: white;
                    border-color: #374151;
                }

                .report-tile:hover {
                    border-color: #fb923c;
                    background: linear-gradient(135deg, #1f2937, #111827);
                }

                .report-tile:hover::after {
                    box-shadow: 0 0 0 3px rgba(251, 146, 60, 0.3);
                }
            }

            
        </style>
    @endpush

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

        @foreach ($this->getReportLinks() as $link)
            <x-filament::link :href="$link['url']" color="orange"
                class="report-tile aspect-square relative flex items-center justify-center text-center p-4 rounded-xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 overflow-hidden">
                <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100 z-10">
                    {{ $link['title'] }}
                </h3>
            </x-filament::link>
        @endforeach
    </div>
</x-filament-panels::page>
