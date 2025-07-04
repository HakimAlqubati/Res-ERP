<x-filament-panels::page>
    @push('styles')
        <style>
            .tile-container {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
                /* تعديل العرض */
                gap: 1.5rem;
                padding-top: 3rem;
            }

            .tile {
                background-color: #0d7c66;
                /* خلفية خضراء */
                border: 1.5px solid #0d7c66;
                border-radius: 1.5rem;
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                padding: 1rem;
                font-weight: 500;
                font-size: 0.75rem;
                color: #ffffff;
                /* النص باللون الأبيض */
                width: 180px;
                height: 180px;
                transition: all 0.3s ease-in-out;
                opacity: 0;
                transform: translateY(20px);
                animation: fadeInUp 0.6s ease forwards;
                text-decoration: none;
                text-align: center;
                overflow: hidden;
                /* يمنع النص من الخروج عن الحدود */
            }

            .tile:hover {
                transform: scale(1.05) translateY(-4px);
                background: #ffffff;
                /* الخلفية بيضاء عند التمرير */
                color: #0d7c66;
                /* النص باللون الأخضر عند التمرير */
                border-color: #0d7c66;
                /* تغير حدود الزر للون الأخضر */
                font-weight: 700;
            }

            .tile svg {
                width: 6.5rem;
                height: 6.5rem;
                color: #ffffff;
                /* الأيقونة باللون الأبيض */
                flex-shrink: 0;
                margin-bottom: 0.5rem;
            }

            .tile:hover svg {
                color: #0d7c66;
                /* الأيقونة باللون الأخضر عند التمرير */
            }

            @keyframes fadeInUp {
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }

            @media (prefers-color-scheme: dark) {
                .tile {
                    background-color: #0d7c66;
                    /* خلفية خضراء */
                    color: white;
                    border-color: #334155;
                }

                .tile:hover {
                    background: linear-gradient(135deg, #ffffff, #f3f4f6);
                    border-color: #fb923c;
                    color: #0d7c66;
                    /* النص باللون الأخضر عند التمرير */
                }

                .tile svg {
                    color: #ffffff;
                }

                .tile:hover svg {
                    color: #0d7c66;
                }
            }
            .fi-header{
                display: none;
            }
        </style>
    @endpush
    
    <div class="tile-container">
        @foreach ($this->getReportLinks() as $link)
            <a href="{{ $link['url'] }}" class="tile">
                @svg($link['icon'], 'w-10 h-10') <!-- تكبير الأيقونة -->
                <span>{{ $link['title'] }}</span>
            </a>
        @endforeach
    </div>
</x-filament-panels::page>
