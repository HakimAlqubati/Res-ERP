<x-filament-panels::page>
    @push('styles')
        <style>
            .report-tile {
                transition: all 0.3s ease-in-out;
                opacity: 0;
                transform: translateY(20px);
                animation: fadeInUp 0.6s ease forwards;
                background-color: #ffffff;
                border: 1.5px solid #0f766e;
                border-radius: 1.5rem;
                padding: 1.25rem 1.5rem;
                display: flex;
                align-items: center;
                gap: 0.75rem;
                font-weight: 600;
                font-size: 1.125rem;
                color: #0f172a;
                white-space: nowrap;
            }

            .report-tile:hover {
                transform: scale(1.05) translateY(-4px);
                background: linear-gradient(135deg, #ffffff, #f3f4f6);
                box-shadow: 0 12px 24px rgba(0, 0, 0, 0.08);
            }

            .report-tile svg {
                width: 1.75rem;
                height: 1.75rem;
                color: #0f766e;
                flex-shrink: 0;
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
                    border-color: #334155;
                }

                .report-tile:hover {
                    background: linear-gradient(135deg, #1f2937, #111827);
                    border-color: #fb923c;
                }

                .report-tile svg {
                    color: #fb923c;
                }
            }
        </style>
    @endpush

    <!-- Add top padding here -->
    <div class="pt-12">
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6">
            @foreach ($this->getReportLinks() as $link)
                <a href="{{ $link['url'] }}" class="report-tile">
                    @svg($link['icon'], 'w-6 h-6')
                    <span>{{ $link['title'] }}</span>
                </a>
            @endforeach
        </div>
    </div>
</x-filament-panels::page>
