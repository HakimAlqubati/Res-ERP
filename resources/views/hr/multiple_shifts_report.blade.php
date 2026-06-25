<!DOCTYPE html>
<html lang="ar" dir="rtl" class="dark">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تقرير الموظفين ذوي الشيفتات المتعددة</title>
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
                        'fade-in-up': 'fadeInUp 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards',
                        'float': 'float 6s ease-in-out infinite',
                        'pulse-slow': 'pulse 4s cubic-bezier(0.4, 0, 0.6, 1) infinite',
                    }
                }
            }
        }
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Cairo', sans-serif;
            background-color: #121212;
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

        @keyframes float {
            0%, 100% {
                transform: translateY(0px);
            }
            50% {
                transform: translateY(-10px);
            }
        }

        .glass-effect {
            background: rgba(30, 30, 30, 0.75);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.05);
        }

        .shimmer-text {
            background: linear-gradient(90deg, #ffffff 0%, #ffffff 40%, #0d7c66 50%, #ffffff 60%, #ffffff 100%);
            background-size: 200% auto;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            animation: shimmer 4s ease-in-out infinite;
        }

        @keyframes shimmer {
            0%, 100% {
                background-position: -200% center;
            }
            50% {
                background-position: 200% center;
            }
        }
    </style>
</head>

<body class="text-dark-text min-h-screen flex flex-col selection:bg-primary selection:text-white">

    <!-- Header Section -->
    <header class="w-full bg-dark-card border-b border-dark-border py-6 px-8 flex flex-col md:flex-row justify-between items-center gap-4 shadow-lg z-10">
        <div class="flex items-center gap-4">
            <div class="p-3 bg-primary/10 rounded-xl border border-primary/20">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <div>
                <h1 class="text-2xl font-black text-white tracking-wide">تقرير الشيفتات المتعددة</h1>
                <p class="text-xs text-gray-400">كشف بالموظفين الذين لديهم أكثر من وردية في يوم واحد، أو لديهم تحضيرات بدون وردية وهم مسند لهم وردية.</p>
            </div>
        </div>
        
        <!-- Quick Stats -->
        <div class="flex items-center gap-4">
            <div class="px-4 py-2 bg-dark-bg/60 rounded-xl border border-dark-border text-center">
                <span class="block text-[10px] text-gray-500 font-bold uppercase tracking-wider">عدد الحالات</span>
                <span class="text-lg font-black text-primary">{{ count($reportData) }} حالة</span>
            </div>
        </div>
    </header>

    <!-- Main Container -->
    <main class="flex-grow p-6 md:p-10 max-w-7xl mx-auto w-full flex flex-col gap-8">
        
        <!-- Filter Form Card -->
        <section class="w-full glass-effect rounded-2xl p-6 shadow-xl animate-fade-in-up">
            <form action="{{ route('hr.reports.multiple-shifts') }}" method="GET" class="flex flex-col md:flex-row items-end gap-6">
                
                <div class="flex-grow grid grid-cols-1 md:grid-cols-2 gap-6 w-full">
                    <!-- Start Date -->
                    <div class="space-y-2">
                        <label class="block text-gray-300 text-sm font-bold" for="start_date">تاريخ البدء</label>
                        <input class="w-full bg-dark-input text-white border border-dark-border rounded-xl px-4 py-3 focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all duration-300" 
                               id="start_date" 
                               name="start_date" 
                               type="date" 
                               value="{{ $startDate }}" 
                               required>
                    </div>

                    <!-- End Date -->
                    <div class="space-y-2">
                        <label class="block text-gray-300 text-sm font-bold" for="end_date">تاريخ الانتهاء</label>
                        <input class="w-full bg-dark-input text-white border border-dark-border rounded-xl px-4 py-3 focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all duration-300" 
                               id="end_date" 
                               name="end_date" 
                               type="date" 
                               value="{{ $endDate }}" 
                               required>
                    </div>
                </div>

                <!-- Submit Button -->
                <button type="submit" class="w-full md:w-auto bg-gradient-to-r from-primary to-primary-light hover:from-primary-dark hover:to-primary text-white font-bold py-3.5 px-8 rounded-xl shadow-lg shadow-primary/20 hover:-translate-y-0.5 active:translate-y-0 transition-all duration-200 flex justify-center items-center gap-2 flex-shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <span>تحديث التقرير</span>
                </button>

            </form>
        </section>

        <!-- Results Table Section -->
        <section class="w-full glass-effect rounded-2xl shadow-2xl overflow-hidden animate-fade-in-up" style="animation-delay: 0.15s;">
            @if(count($reportData) > 0)
                <div class="overflow-x-auto">
                    <table class="w-full text-right border-collapse">
                        <thead>
                            <tr class="bg-dark-card border-b border-dark-border text-gray-400 font-bold text-xs uppercase tracking-wider">
                                <th class="py-4 px-6">الموظف</th>
                                <th class="py-4 px-6">الفرع</th>
                                <th class="py-4 px-6">التاريخ</th>
                                <th class="py-4 px-6 text-center">الشيفتات المسندة للتعيين</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-dark-border text-sm">
                            @foreach($reportData as $row)
                                <tr class="hover:bg-white/5 transition-colors duration-200 group">
                                    <!-- Employee Name -->
                                    <td class="py-5 px-6 font-semibold text-white group-hover:text-primary transition-colors">
                                        {{ $row['employee_name'] }}
                                    </td>
                                    <!-- Branch Name -->
                                    <td class="py-5 px-6 text-gray-400">
                                        {{ $row['branch_name'] }}
                                    </td>
                                    <!-- Date -->
                                    <td class="py-5 px-6 text-gray-300">
                                        {{ $row['date'] }}
                                    </td>
                                    <!-- Assigned Shifts -->
                                    <td class="py-5 px-6">
                                        <div class="flex flex-wrap gap-2.5 justify-center">
                                            @foreach($row['shifts'] as $index => $shift)
                                                @if(!empty($shift['is_no_shift']))
                                                    <div class="flex items-center gap-2 bg-amber-500/10 border border-amber-500/20 text-amber-400 text-xs font-semibold px-3 py-1.5 rounded-lg shadow-sm">
                                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-400"></span>
                                                        <span class="font-bold">{{ $shift['name'] }}</span>
                                                        <span class="text-[10px] text-gray-400">({{ $shift['start'] }})</span>
                                                    </div>
                                                @else
                                                    <div class="flex items-center gap-2 bg-primary/10 border border-primary/20 text-primary-light text-xs font-semibold px-3 py-1.5 rounded-lg shadow-sm">
                                                        <span class="w-1.5 h-1.5 rounded-full bg-primary-light"></span>
                                                        <span class="font-bold">{{ $shift['name'] }}</span>
                                                        <span class="text-[10px] text-gray-400">({{ $shift['start'] }} - {{ $shift['end'] }})</span>
                                                    </div>
                                                @endif
                                            @endforeach
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <!-- Empty State -->
                <div class="py-20 text-center max-w-md mx-auto flex flex-col items-center justify-center">
                    <div class="relative w-36 h-36 mb-8 animate-float">
                        <div class="absolute inset-0 bg-gradient-to-tr from-primary/20 to-blue-500/20 rounded-full blur-2xl animate-pulse-slow"></div>
                        <div class="relative bg-dark-card border border-dark-border rounded-full w-full h-full flex items-center justify-center shadow-2xl">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                            </svg>
                        </div>
                    </div>
                    <h3 class="text-xl font-bold mb-2 text-white">لا توجد حالات</h3>
                    <p class="text-sm text-gray-400">لم يتم العثور على أي موظفين لديهم أكثر من وردية في يوم واحد أو تحضيرات بدون وردية في الفترة المحددة.</p>
                </div>
            @endif
        </section>
        
    </main>

    <!-- Footer -->
    <footer class="bg-dark-card border-t border-dark-border py-4 px-8 mt-auto z-10">
        <div class="max-w-7xl mx-auto flex flex-col md:flex-row justify-between items-center gap-2 text-xs text-gray-500">
            <span>نظام الموارد البشرية - كشف الشيفتات المتعددة</span>
            <span>&copy; {{ date('Y') }} Workbench ERP</span>
        </div>
    </footer>

</body>

</html>
