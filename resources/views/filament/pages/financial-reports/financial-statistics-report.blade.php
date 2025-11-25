<x-filament::page>
    <style>
        table {
            width: 100%;
            border-collapse: inherit;
            border-spacing: initial;
        }

        .fi-tabs {
            /* display: none !important; */
        }

        .income-color {
            color: #059669;
        }

        .dark .income-color {
            color: #34d399;
        }

        .expense-color {
            color: #dc2626;
        }

        .dark .expense-color {
            color: #f87171;
        }

        .balance-color {
            color: #2563eb;
        }

        .dark .balance-color {
            color: #60a5fa;
        }
    </style>

    {{-- Print Button --}}
    <div class="flex justify-end gap-3 mb-4">
        <button id="printReport"
            class="px-6 py-2 font-semibold rounded-md border border-blue-600 bg-blue-500 hover:bg-blue-700 transition duration-300 shadow-md">
            🖨️ {{ __('Print') }}
        </button>
    </div>

    {{-- Filters --}}
    {{ $this->getTableFiltersForm() }}

    @if (isset($reportData) && !empty($reportData))
    <div id="reportContent">
        {{-- Report Header --}}
        <div class="mb-6">
            <table class="w-full text-sm text-left pretty reports table-striped border">
                <thead class="fixed-header">
                    <tr class="header_report">
                        <th class="{{ app()->getLocale() == 'en' ? 'no_border_right' : 'no_border_left' }}">
                            <div style="width: 100%;"></div>
                        </th>
                        <th colspan="2" class="no_border_right_left text-center">
                            <h3>{{ __('Financial Statistics Report') }}</h3>
                            <p class="text-sm text-gray-600">
                                {{ __('From') }}: {{ $filters['start_date'] ?? 'N/A' }} |
                                {{ __('To') }}: {{ $filters['end_date'] ?? 'N/A' }}
                            </p>
                        </th>
                        <th colspan="3"
                            class="{{ app()->getLocale() == 'ar' ? 'no_border_right' : 'no_border_left' }}"
                            style="text-align: center;">
                            <img style="display: inline-block;"
                                src="{{ asset('/storage/' . setting('company_logo') . '') }}" alt=""
                                class="logo-left circle-image">
                        </th>
                    </tr>
                </thead>
            </table>
        </div>
        {{-- Statistics Summary --}}
        @if (isset($reportData['statistics']))
        <div
            x-data='{
                        init() {
                            if (!window.Chart) {
                                const script = document.createElement("script");
                                script.src = "https://cdn.jsdelivr.net/npm/chart.js";
                                script.onload = () => {
                                    this.$nextTick(() => this.renderChart());
                                };
                                document.head.appendChild(script);
                            } else {
                                this.$nextTick(() => this.renderChart());
                            }
                        },
                        renderChart() {
                            if (!this.$refs.canvas) return;
                            const ctx = this.$refs.canvas.getContext("2d");
                            const reportData = @json($reportData[' statistics']['totals']);

            if (this.chart) {
            this.chart.destroy();
            }

            this.chart=new Chart(ctx, {
            type: "bar" ,
            data: {
            labels: ["{{ __('Total Income') }}", "{{ __('Total Expense') }}" ],
            datasets: [{
            label: "{{ __('Amount') }}" ,
            data: [reportData.income, reportData.expense],
            backgroundColor: [ "rgba(5, 150, 105, 0.2)" , // Income color "rgba(220, 38, 38, 0.2)" // Expense color
            ],
            borderColor: [ "rgba(5, 150, 105, 1)" , "rgba(220, 38, 38, 1)"
            ],
            borderWidth: 1
            }]
            },
            options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
            y: {
            beginAtZero: true,
            ticks: {
            callback: function(value) {
            return value.toLocaleString();
            }
            }
            }
            },
            plugins: {
            legend: {
            display: false
            },
            tooltip: {
            callbacks: {
            label: function(context) {
            let label=context.dataset.label || "" ;
            if (label) {
            label +=": " ;
            }
            if (context.parsed.y !==null) {
            label +=context.parsed.y.toLocaleString();
            }
            return label;
            }
            }
            }
            }
            }
            });
            }
            }'
            class="mb-6"
            style="height: 400px;">
            <canvas x-ref="canvas"></canvas>
        </div>
        @endif
    </div>
    @else
    <div class="please_select_message_div" style="text-align: center;">
        <h1 class="please_select_message_text">{{ __('Please select filters to generate the report.') }}</h1>
    </div>
    @endif

    {{-- JavaScript to Handle Printing --}}
    <script>
        document.getElementById("printReport").addEventListener("click", function() {
            const originalContent = document.body.innerHTML;
            const printContent = document.getElementById("reportContent").innerHTML;

            document.body.innerHTML = printContent;
            window.print();
            document.body.innerHTML = originalContent;
            location.reload(); // Reload to restore the page
        });
    </script>

    {{-- CSS to Hide Button in Print Mode --}}
    <style>
        @media print {
            #printReport {
                display: none;
            }
        }
    </style>

</x-filament::page>