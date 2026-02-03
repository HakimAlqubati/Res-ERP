<!DOCTYPE html>
<html lang="ar" dir="rtl" class="dark">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>حاسبة معادلة العمل الأسبوعي</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        primary: '#0d7c66',
                        'primary-light': '#149d82',
                        'primary-dark': '#095c4b',
                        'dark-bg': '#121212',
                        'dark-card': '#1e1e1e',
                        'dark-border': '#333333',
                        'dark-text': '#e0e0e0',
                        'dark-input': '#2d2d2d',
                    },
                    animation: {
                        'fade-in-up': 'fadeInUp 0.5s ease-out forwards',
                        'pulse-slow': 'pulse 3s cubic-bezier(0.4, 0, 0.6, 1) infinite',
                    }
                }
            }
        }
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Cairo', sans-serif;
        }

        /* Custom Scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }

        ::-webkit-scrollbar-track {
            background: #1e1e1e;
        }

        ::-webkit-scrollbar-thumb {
            background: #333;
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #0d7c66;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .glass-effect {
            background: rgba(30, 30, 30, 0.7);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.05);
        }
    </style>
</head>

<body class="bg-dark-bg text-dark-text min-h-screen overflow-hidden flex flex-col md:flex-row selection:bg-primary selection:text-white">

    <!-- Right Side: Form Section (RTL: First child is on the Right) -->
    <div class="w-full md:w-1/3 min-w-[320px] bg-dark-card border-l border-dark-border h-auto md:h-screen flex flex-col shadow-2xl z-20 relative">
        <div class="p-8 flex-shrink-0">
            <div class="flex items-center gap-3 mb-2">
                <div class="p-2 bg-primary/10 rounded-lg">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                    </svg>
                </div>
                <h1 class="text-2xl font-bold text-white tracking-wide">حاسبة الاستحقاق</h1>
            </div>
            <p class="text-gray-400 text-sm leading-relaxed opacity-80">أدخل عدد أيام الشهر وأيام الغياب لحساب المستحقات والخصومات بدقة.</p>
        </div>

        <div class="flex-grow p-8 pt-0 overflow-y-auto">
            <form id="calculator-form" action="{{ route('hr.weekly-leave-calculator') }}" method="GET" class="space-y-6">

                <div class="space-y-2">
                    <label class="block text-gray-300 text-sm font-semibold" for="total_month_days">
                        إجمالي أيام الشهر (الوعاء)
                    </label>
                    <div class="relative group">
                        <input class="w-full bg-dark-input text-white border border-dark-border rounded-xl px-4 py-3 pl-12 focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all duration-300 placeholder-gray-600 group-hover:border-gray-500"
                            id="total_month_days"
                            name="total_month_days"
                            type="number"
                            value="{{ request('total_month_days', 30) }}"
                            placeholder="30"
                            required>
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                            <span class="text-gray-500 text-xs font-bold bg-dark-border px-2 py-1 rounded">يوم</span>
                        </div>
                    </div>
                </div>

                <div class="space-y-2">
                    <label class="block text-gray-300 text-sm font-semibold" for="absent_days">
                        عدد أيام الغياب
                    </label>
                    <div class="relative group">
                        <input class="w-full bg-dark-input text-white border border-dark-border rounded-xl px-4 py-3 pl-12 focus:outline-none focus:border-red-500 focus:ring-1 focus:ring-red-500 transition-all duration-300 placeholder-gray-600 group-hover:border-gray-500"
                            id="absent_days"
                            name="absent_days"
                            type="number"
                            step="0.1"
                            value="{{ request('absent_days', 0) }}"
                            placeholder="0"
                            required>
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                            <span class="text-gray-500 text-xs font-bold bg-dark-border px-2 py-1 rounded">يوم</span>
                        </div>
                    </div>
                </div>

                <div class="pt-4">
                    <button type="submit" id="submit-btn" class="w-full bg-gradient-to-r from-primary to-primary-light hover:from-primary-dark hover:to-primary text-white font-bold py-4 px-6 rounded-xl shadow-lg shadow-primary/20 transform hover:-translate-y-0.5 active:translate-y-0 transition-all duration-200 flex justify-center items-center gap-2 group">
                        <span>احسب النتيجة</span>
                        <svg class="w-5 h-5 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                        </svg>
                    </button>
                    <!-- Loading State Button (Hidden by default) -->
                    <button type="button" id="loading-btn" class="hidden w-full bg-dark-input text-gray-400 font-bold py-4 px-6 rounded-xl cursor-not-allowed flex justify-center items-center gap-2">
                        <svg class="animate-spin h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span>جاري الحساب...</span>
                    </button>
                </div>

            </form>
        </div>

        <div class="p-6 mt-auto border-t border-dark-border bg-dark-card z-10">
            <div class="flex items-center justify-between text-xs text-gray-500">
                <span>نظام الموارد البشرية</span>
                <span>&copy; {{ date('Y') }}</span>
            </div>
        </div>
    </div>

    <!-- Left Side: Result Section -->
    <div class="flex-grow h-screen overflow-y-auto bg-dark-bg relative flex flex-col custom-scrollbar scroll-smooth" id="result-container-wrapper">

        <!-- Background decorative elements -->
        <div class="absolute top-0 left-0 w-full h-full overflow-hidden pointer-events-none z-0">
            <div class="absolute top-[-10%] left-[-10%] w-[40%] h-[40%] bg-primary/5 rounded-full blur-[120px]"></div>
            <div class="absolute bottom-[-10%] right-[-10%] w-[30%] h-[30%] bg-blue-500/5 rounded-full blur-[100px]"></div>
        </div>

        <div class="flex-grow p-6 md:p-12 flex items-center justify-center relative z-10 w-full" id="result-container">
            @if(isset($result))
            <div id="results-content" class="w-full max-w-6xl space-y-8 animate-fade-in-up pb-10">

                <!-- Header for Results -->
                <div class="flex items-center justify-between border-b border-dark-border pb-6">
                    <div>
                        <h2 class="text-3xl font-bold text-white mb-2">نتائج التحليل</h2>
                        <p class="text-gray-400">بناءً على البيانات المدخلة: <span class="text-primary font-bold">{{ request('total_month_days') }}</span> يوم عمل، <span class="text-red-400 font-bold">{{ request('absent_days') }}</span> غياب.</p>
                    </div>
                    <div class="hidden md:block">
                        <span class="px-4 py-2 rounded-full bg-dark-card border border-dark-border text-xs text-gray-500 font-mono">
                            {{ now()->format('Y-m-d H:i') }}
                        </span>
                    </div>
                </div>

                <!-- Section 1: Analysis Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Worked Days -->
                    <div class="relative overflow-hidden bg-dark-card rounded-2xl p-8 border border-dark-border group hover:border-blue-500/30 transition-all duration-300">
                        <div class="absolute right-0 top-0 w-32 h-32 bg-blue-500/10 rounded-bl-full -mr-8 -mt-8 transition-transform group-hover:scale-110"></div>

                        <div class="relative z-10">
                            <div class="flex items-center gap-3 mb-4">
                                <div class="p-2 bg-blue-500/10 rounded-lg text-blue-400">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                    </svg>
                                </div>
                                <h3 class="text-gray-300 font-bold text-lg">أيام العمل الفعلية</h3>
                            </div>
                            <div class="flex items-baseline gap-2">
                                <span class="text-5xl font-black text-white group-hover:text-blue-400 transition-colors">{{ $result['analysis']['worked_days'] }}</span>
                                <span class="text-gray-500 font-bold">يوم</span>
                            </div>
                            <p class="text-blue-500/60 text-xs mt-2 font-medium">الأيام المحسوبة بعد خصم الغياب</p>
                        </div>
                    </div>

                    <!-- Earned Leave -->
                    <div class="relative overflow-hidden bg-dark-card rounded-2xl p-8 border border-dark-border group hover:border-primary/30 transition-all duration-300">
                        <div class="absolute right-0 top-0 w-32 h-32 bg-primary/10 rounded-bl-full -mr-8 -mt-8 transition-transform group-hover:scale-110"></div>

                        <div class="relative z-10">
                            <div class="flex items-center gap-3 mb-4">
                                <div class="p-2 bg-primary/10 rounded-lg text-primary">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                                <h3 class="text-gray-300 font-bold text-lg">استحقاق الإجازة</h3>
                            </div>
                            <div class="flex items-baseline gap-2">
                                <span class="text-5xl font-black text-white group-hover:text-primary transition-colors">{{ $result['analysis']['earned_leave_days'] }}</span>
                                <span class="text-gray-500 font-bold">يوم</span>
                            </div>
                            <p class="text-primary/60 text-xs mt-2 font-medium">عدد أيام الإجازة المسموح بها</p>
                        </div>
                    </div>
                </div>

                <!-- Section 2: Final Details -->
                <div>
                    <h3 class="text-xl font-bold text-gray-200 mb-6 flex items-center gap-2">
                        <div class="w-1 h-6 bg-primary rounded-full"></div>
                        تفاصيل الخصم والإضافي
                    </h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">

                        <!-- Overtime -->
                        <div class="bg-dark-card/50 backdrop-blur rounded-xl p-6 border border-dark-border hover:bg-dark-card transition duration-300 group">
                            <div class="text-gray-400 text-sm font-bold mb-3 group-hover:text-primary-light transition-colors">العمل الإضافي</div>
                            <div class="text-4xl font-black text-white mb-2">{{ $result['result']['overtime_days'] }}</div>
                            <div class="text-xs text-gray-600 font-medium">يوم مستحق</div>
                        </div>

                        <!-- Leave Penalty -->
                        <div class="bg-dark-card/50 backdrop-blur rounded-xl p-6 border border-dark-border hover:bg-dark-card transition duration-300 group">
                            <div class="text-gray-400 text-sm font-bold mb-3 group-hover:text-red-400 transition-colors">خصم نصيب الراحة</div>
                            <div class="text-4xl font-black text-white mb-2">{{ $result['result']['leave_penalty'] }}</div>
                            <div class="text-xs text-gray-600 font-medium">يوم خصم</div>
                        </div>

                        <!-- Absent Penalty -->
                        <div class="bg-dark-card/50 backdrop-blur rounded-xl p-6 border border-dark-border hover:bg-dark-card transition duration-300 group">
                            <div class="text-gray-400 text-sm font-bold mb-3 group-hover:text-red-500 transition-colors">خصم الغياب الصافي</div>
                            <div class="text-4xl font-black text-white mb-2">{{ $result['result']['final_absent_penalty'] }}</div>
                            <div class="text-xs text-gray-600 font-medium">يوم خصم</div>
                        </div>

                        <!-- Total Deduction -->
                        <div class="bg-gradient-to-b from-orange-900/20 to-dark-card rounded-xl p-6 border border-orange-500/20 hover:border-orange-500/40 transition duration-300 group relative overflow-hidden">
                            <div class="absolute inset-0 bg-orange-500/5 group-hover:bg-orange-500/10 transition"></div>
                            <div class="relative z-10">
                                <div class="text-orange-200/70 text-sm font-bold mb-3">إجمالي الخصم النهائي</div>
                                <div class="text-5xl font-black text-orange-500 mb-2">{{ $result['result']['total_deduction_days'] ?? 0 }}</div>
                                <div class="text-xs text-orange-500/50 font-medium">مجموع الخصومات</div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
            @else
            <!-- Empty State -->
            <div id="empty-state" class="text-center max-w-lg mx-auto animate-fade-in-up">
                <div class="relative w-48 h-48 mx-auto mb-8">
                    <div class="absolute inset-0 bg-gradient-to-tr from-primary/20 to-blue-500/20 rounded-full blur-2xl animate-pulse-slow"></div>
                    <div class="relative bg-dark-card border border-dark-border rounded-full w-full h-full flex items-center justify-center shadow-2xl">
                        <svg class="w-20 h-20 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                    </div>
                </div>
                <h3 class="text-3xl font-bold text-white mb-3">في انتظار البيانات</h3>
                <p class="text-gray-400 text-lg">قم بإدخال البيانات في النموذج الجانبي لعرض تحليل تفصيلي لاستحقاقات الإجازة والخصومات.</p>
            </div>
            @endif
        </div>
    </div>

    <!-- Hidden Template / Script Logic -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const form = document.getElementById('calculator-form');
            const submitBtn = document.getElementById('submit-btn');
            const loadingBtn = document.getElementById('loading-btn');
            const resultContainerWrapper = document.getElementById('result-container-wrapper');
            const resultContainer = document.getElementById('result-container');

            form.addEventListener('submit', function(e) {
                e.preventDefault();

                // Toggle Buttons
                submitBtn.classList.add('hidden');
                loadingBtn.classList.remove('hidden');

                const url = form.action;
                const formData = new FormData(form);
                const params = new URLSearchParams(formData).toString();
                const fullUrl = url + '?' + params;

                // Simple loading effect on result container
                resultContainer.classList.add('opacity-50', 'blur-sm', 'scale-95');
                resultContainer.classList.remove('scale-100', 'opacity-100', 'blur-0');

                fetch(fullUrl, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(response => response.text())
                    .then(html => {
                        const parser = new DOMParser();
                        const doc = parser.parseFromString(html, 'text/html');

                        // Try to find the results content wrapper
                        let newContent = doc.getElementById('results-content');

                        // Reset UI
                        submitBtn.classList.remove('hidden');
                        loadingBtn.classList.add('hidden');

                        // Update content
                        if (newContent) {
                            resultContainer.innerHTML = '';
                            resultContainer.appendChild(newContent);
                        } else {
                            // Fallback: This might happen if there's an error on the server and it returns the full error page or something else
                            // Or if the other page logic renders #empty-state
                            console.warn('Could not find #results-content in response');
                            const emptyState = doc.getElementById('empty-state');
                            if (emptyState) {
                                resultContainer.innerHTML = '';
                                resultContainer.appendChild(emptyState);
                            }
                        }

                        // Remove loading effects
                        resultContainer.classList.remove('opacity-50', 'blur-sm', 'scale-95');
                        resultContainer.classList.add('scale-100', 'opacity-100', 'blur-0');

                        // Smooth transition class
                        resultContainer.classList.add('transition-all', 'duration-500');

                    })
                    .catch(error => {
                        console.error('Error:', error);
                        submitBtn.classList.remove('hidden');
                        loadingBtn.classList.add('hidden');
                        alert('حدث خطأ. حاول مرة أخرى.');
                    });

                window.history.pushState({}, '', fullUrl);
            });
        });
    </script>
</body>

</html>