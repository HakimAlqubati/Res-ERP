<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تقرير مقارنة: النظام المحاسبي vs النظام المالي</title>
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
    'overview' => [
    'title' => 'نظرة عامة',
    'shortTitle' => 'نظرة عامة',
    'icon' => '📖',
    ],
    'components' => [
    'title' => 'المكونات الأساسية',
    'shortTitle' => 'المكونات',
    'icon' => '🏗️',
    ],
    'advantages' => [
    'title' => 'مميزات النظام المحاسبي',
    'shortTitle' => 'المميزات',
    'icon' => '🏆',
    ],
    'comparison' => [
    'title' => 'جدول المقارنة التفصيلي',
    'shortTitle' => 'المقارنة',
    'icon' => '📌',
    ],
    'usage' => [
    'title' => 'متى تستخدم كل نظام؟',
    'shortTitle' => 'الاستخدام',
    'icon' => '🤔',
    ],
    'terminology' => [
    'title' => 'مصطلحات محاسبية',
    'shortTitle' => 'المصطلحات',
    'icon' => '📖',
    ],
    'power' => [
    'title' => 'قوة النظام المحاسبي',
    'shortTitle' => 'القوة',
    'icon' => '⚡',
    ],
    ];

    $accountingFeatures = [
    ['name' => 'القيد المزدوج (Double Entry)', 'description' => 'كل معاملة تُسجَّل في حسابين على الأقل (مدين ودائن)، مما يضمن التوازن الدائم ويكشف الأخطاء تلقائياً.'],
    ['name' => 'دليل حسابات هرمي', 'description' => 'تنظيم الحسابات بشكل شجري يسمح بتقارير تفصيلية وإجمالية (الأصول ← الأصول المتداولة ← النقدية).'],
    ['name' => 'ميزان المراجعة', 'description' => 'تقرير يُظهر كل الحسابات مع إجمالي المدين والدائن. إذا تساوى المجموعين، فالدفاتر متوازنة.'],
    ['name' => 'ربط الحسابات البنكية والصناديق', 'description' => 'كل BankAccount وCashBox مرتبط بحساب عام (gl_account_id)، مما يسمح بتتبع الأرصدة محاسبياً.'],
    ['name' => 'حماية القيود المرحّلة', 'description' => 'القيود المرحّلة (Posted) لا يمكن تعديلها مباشرة. يجب إنشاء قيد عكسي للحفاظ على سلامة السجلات.'],
    ['name' => 'دعم العملات المتعددة', 'description' => 'كل قيد يمكن أن يكون بعملة مختلفة مع exchange_rate، ويُحتفظ بالمبالغ بالعملة المحلية والأجنبية.'],
    ['name' => 'قوائم مالية معتمدة', 'description' => 'إعداد: قائمة الدخل، الميزانية العمومية، قائمة التدفقات النقدية - متطلبات للجهات الرسمية والبنوك.'],
    ];

    $comparisonData = [
    ['feature' => 'القيد المزدوج', 'accounting' => 'متوفر', 'financial' => 'غير متوفر', 'accountingStatus' => 'available', 'financialStatus' => 'unavailable'],
    ['feature' => 'دليل حسابات هرمي', 'accounting' => 'متوفر', 'financial' => 'غير متوفر', 'accountingStatus' => 'available', 'financialStatus' => 'unavailable'],
    ['feature' => 'ميزان المراجعة', 'accounting' => 'متوفر', 'financial' => 'غير متوفر', 'accountingStatus' => 'available', 'financialStatus' => 'unavailable'],
    ['feature' => 'تتبع الأصول والالتزامات', 'accounting' => 'متوفر', 'financial' => 'غير متوفر', 'accountingStatus' => 'available', 'financialStatus' => 'unavailable'],
    ['feature' => 'إعداد الميزانية العمومية', 'accounting' => 'متوفر', 'financial' => 'غير متوفر', 'accountingStatus' => 'available', 'financialStatus' => 'unavailable'],
    ['feature' => 'ربط الحسابات البنكية', 'accounting' => 'متوفر (GL Account)', 'financial' => 'غير متوفر', 'accountingStatus' => 'available', 'financialStatus' => 'unavailable'],
    ['feature' => 'حماية القيود المرحّلة', 'accounting' => 'متوفر', 'financial' => 'غير متوفر', 'accountingStatus' => 'available', 'financialStatus' => 'unavailable'],
    ['feature' => 'دعم مراكز التكلفة', 'accounting' => 'متوفر', 'financial' => 'غير متوفر', 'accountingStatus' => 'available', 'financialStatus' => 'unavailable'],
    ['feature' => 'تسجيل الإيرادات والمصروفات', 'accounting' => 'متوفر', 'financial' => 'متوفر', 'accountingStatus' => 'available', 'financialStatus' => 'available'],
    ['feature' => 'تقارير حسب الفرع', 'accounting' => 'متوفر', 'financial' => 'متوفر', 'accountingStatus' => 'available', 'financialStatus' => 'available'],
    ['feature' => 'سهولة الاستخدام للمبتدئين', 'accounting' => 'متوسط', 'financial' => 'سهل جداً', 'accountingStatus' => 'partial', 'financialStatus' => 'available'],
    ['feature' => 'سرعة إدخال البيانات', 'accounting' => 'متوسط', 'financial' => 'سريع', 'accountingStatus' => 'partial', 'financialStatus' => 'available'],
    ];

    $componentsData = [
    'accounting' => [
    ['name' => 'Account (الحسابات)', 'type' => '5 أنواع: أصول، التزامات، حقوق ملكية، إيرادات، مصروفات'],
    ['name' => 'JournalEntry (قيود اليومية)', 'type' => 'حالات: مسودة / مرحّل'],
    ['name' => 'JournalEntryLine (بنود القيود)', 'type' => 'مدين + دائن لكل حساب'],
    ['name' => 'BankAccount (الحسابات البنكية)', 'type' => 'مرتبط بـ GL Account'],
    ['name' => 'CashBox (صناديق النقدية)', 'type' => 'مرتبط بـ GL Account'],
    ],
    'financial' => [
    ['name' => 'FinancialTransaction (المعاملات)', 'type' => 'إيراد أو مصروف'],
    ['name' => 'FinancialCategory (التصنيفات)', 'type' => 'تصنيفات مسطحة (Flat)'],
    ],
    ];

    $accountingUseCases = [
    'تحتاج قوائم مالية رسمية للبنوك أو الجهات الحكومية',
    'لديك أصول ثابتة (سيارات، معدات، مباني)',
    'لديك التزامات (قروض، ديون موردين)',
    'تحتاج تتبع حقوق الملاك ورأس المال',
    'تحتاج ميزان مراجعة للتدقيق',
    'لديك فريق محاسبة متخصص',
    ];

    $financialUseCases = [
    'الهدف فقط متابعة الإيرادات والمصروفات',
    'لا تحتاج قوائم مالية رسمية',
    'تريد نظام بسيط وسريع',
    'المستخدمون ليسوا محاسبين',
    'تركز على الأداء التشغيلي للفروع',
    'تحتاج تقارير مقارنة سريعة',
    ];

    $accountingPowerExamples = [
    [
    'title' => 'المعادلة المحاسبية الأساسية',
    'icon' => '⚖️',
    'formula' => 'الأصول = الالتزامات + حقوق الملكية',
    'description' => 'هذه المعادلة هي أساس كل النظام المحاسبي. كل معاملة يجب أن تحافظ على هذا التوازن.',
    'example' => 'إذا كانت الأصول = 100,000 ر.ي والالتزامات = 40,000 ر.ي، فإن حقوق الملكية = 60,000 ر.ي',
    'impossible_in_financial' => 'النظام المالي لا يتتبع الأصول والالتزامات، فقط الإيرادات والمصروفات',
    ],
    [
    'title' => 'حقوق الملاك (رأس المال)',
    'icon' => '👥',
    'formula' => 'حقوق الملكية = رأس المال + الأرباح المحتجزة - المسحوبات',
    'description' => 'تتبع ما يملكه أصحاب المنشأة فعلياً بعد خصم كل الديون.',
    'example' => 'رأس المال المبدئي: 50,000 + أرباح العام: 20,000 - مسحوبات الشريك: 5,000 = حقوق الملكية: 65,000 ر.ي',
    'impossible_in_financial' => 'لا يمكن تتبع رأس المال أو المسحوبات في النظام المالي',
    ],
    [
    'title' => 'ديون الموردين (الدائنون)',
    'icon' => '📦',
    'formula' => 'رصيد المورد = المشتريات الآجلة - المدفوعات',
    'description' => 'تتبع المبالغ المستحقة للموردين عن مشتريات لم تُسدد بعد.',
    'example' => 'اشتريت بضاعة بـ 30,000 ر.ي على الحساب، ودفعت 10,000 ر.ي ← المتبقي للمورد: 20,000 ر.ي',
    'impossible_in_financial' => 'النظام المالي يسجل الدفع فقط كمصروف، لا يتتبع الدين المتبقي',
    ],
    [
    'title' => 'الذمم المدينة (العملاء)',
    'icon' => '🧾',
    'formula' => 'رصيد العميل = المبيعات الآجلة - التحصيلات',
    'description' => 'تتبع المبالغ المستحقة من العملاء عن مبيعات لم تُحصّل بعد.',
    'example' => 'بعت بضاعة بـ 50,000 ر.ي على الحساب، وحصّلت 30,000 ر.ي ← المتبقي على العميل: 20,000 ر.ي',
    'impossible_in_financial' => 'النظام المالي يسجل التحصيل فقط كإيراد، لا يتتبع المبلغ المتبقي',
    ],
    [
    'title' => 'الأصول الثابتة والإهلاك',
    'icon' => '🏭',
    'formula' => 'مصروف الإهلاك = (تكلفة الأصل - قيمة الخردة) ÷ العمر الإنتاجي',
    'description' => 'توزيع تكلفة الأصل على سنوات استخدامه بدلاً من تسجيلها دفعة واحدة.',
    'example' => 'سيارة تكلفتها 120,000 ر.ي، عمرها 5 سنوات ← إهلاك سنوي: 24,000 ر.ي',
    'impossible_in_financial' => 'النظام المالي يسجل شراء السيارة كمصروف كامل في سنة واحدة!',
    ],
    [
    'title' => 'القروض والفوائد',
    'icon' => '🏦',
    'formula' => 'رصيد القرض = القرض الأصلي + الفوائد - الأقساط المسددة',
    'description' => 'تتبع القروض البنكية مع فصل الفائدة عن أصل القرض.',
    'example' => 'قرض: 100,000 ر.ي + فائدة: 15,000 ر.ي - أقساط مسددة: 30,000 ر.ي = المتبقي: 85,000 ر.ي',
    'impossible_in_financial' => 'النظام المالي يسجل القسط فقط كمصروف، لا يُظهر الدين المتبقي',
    ],
    ];

    $accountingTerms = [
    // أنواع الحسابات الرئيسية
    [
    'term' => 'الأصول (Assets)',
    'category' => 'أنواع الحسابات',
    'definition' => 'كل ما تملكه المنشأة وله قيمة مالية',
    'examples' => 'النقدية، البضاعة، السيارات، المباني، الذمم المدينة',
    'color' => 'green',
    ],
    [
    'term' => 'الالتزامات (Liabilities)',
    'category' => 'أنواع الحسابات',
    'definition' => 'كل ما على المنشأة من ديون للغير',
    'examples' => 'ديون الموردين، القروض البنكية، الرواتب المستحقة',
    'color' => 'red',
    ],
    [
    'term' => 'حقوق الملكية (Equity)',
    'category' => 'أنواع الحسابات',
    'definition' => 'ما يملكه أصحاب المنشأة بعد خصم الديون',
    'examples' => 'رأس المال، الأرباح المحتجزة، الاحتياطيات',
    'color' => 'blue',
    ],
    [
    'term' => 'الإيرادات (Revenue)',
    'category' => 'أنواع الحسابات',
    'definition' => 'الدخل الناتج من النشاط الرئيسي للمنشأة',
    'examples' => 'إيراد المبيعات، إيراد الخدمات، إيراد الإيجار',
    'color' => 'green',
    ],
    [
    'term' => 'المصروفات (Expenses)',
    'category' => 'أنواع الحسابات',
    'definition' => 'التكاليف التي تتحملها المنشأة لتحقيق الإيراد',
    'examples' => 'الرواتب، الإيجار، الكهرباء، المشتريات',
    'color' => 'yellow',
    ],
    // مصطلحات القيود
    [
    'term' => 'مدين (Debit)',
    'category' => 'القيد المزدوج',
    'definition' => 'الطرف الأيسر من القيد، يزيد الأصول والمصروفات',
    'examples' => 'عند شراء بضاعة نقداً: المشتريات (مدين)',
    'color' => 'primary',
    ],
    [
    'term' => 'دائن (Credit)',
    'category' => 'القيد المزدوج',
    'definition' => 'الطرف الأيمن من القيد، يزيد الالتزامات والإيرادات',
    'examples' => 'عند شراء بضاعة نقداً: النقدية (دائن)',
    'color' => 'primary',
    ],
    [
    'term' => 'قيد اليومية (Journal Entry)',
    'category' => 'القيد المزدوج',
    'definition' => 'تسجيل المعاملة المالية بطرفين متساويين (مدين ودائن)',
    'examples' => 'بيع بضاعة نقداً: النقدية (مدين) / المبيعات (دائن)',
    'color' => 'primary',
    ],
    // مصطلحات التقارير
    [
    'term' => 'ميزان المراجعة (Trial Balance)',
    'category' => 'التقارير',
    'definition' => 'تقرير يعرض كل الحسابات مع إجمالي المدين والدائن',
    'examples' => 'يجب أن يتساوى إجمالي المدين مع إجمالي الدائن',
    'color' => 'blue',
    ],
    [
    'term' => 'قائمة الدخل (Income Statement)',
    'category' => 'التقارير',
    'definition' => 'تقرير يعرض الإيرادات والمصروفات وصافي الربح',
    'examples' => 'إيرادات 100 - مصروفات 70 = صافي ربح 30',
    'color' => 'blue',
    ],
    [
    'term' => 'الميزانية العمومية (Balance Sheet)',
    'category' => 'التقارير',
    'definition' => 'تقرير يعرض الأصول والالتزامات وحقوق الملكية',
    'examples' => 'الأصول = الالتزامات + حقوق الملكية',
    'color' => 'blue',
    ],
    // مصطلحات الأصول
    [
    'term' => 'الأصول المتداولة (Current Assets)',
    'category' => 'تفصيل الأصول',
    'definition' => 'أصول يمكن تحويلها لنقد خلال سنة',
    'examples' => 'النقدية، البنك، البضاعة، العملاء',
    'color' => 'green',
    ],
    [
    'term' => 'الأصول الثابتة (Fixed Assets)',
    'category' => 'تفصيل الأصول',
    'definition' => 'أصول للاستخدام طويل المدى وليس للبيع',
    'examples' => 'المباني، السيارات، المعدات، الأثاث',
    'color' => 'green',
    ],
    [
    'term' => 'الذمم المدينة (Accounts Receivable)',
    'category' => 'تفصيل الأصول',
    'definition' => 'مبالغ مستحقة للمنشأة من العملاء',
    'examples' => 'عميل اشترى بضاعة بالآجل ولم يدفع بعد',
    'color' => 'green',
    ],
    // مصطلحات الالتزامات
    [
    'term' => 'الذمم الدائنة (Accounts Payable)',
    'category' => 'تفصيل الالتزامات',
    'definition' => 'مبالغ مستحقة على المنشأة للموردين',
    'examples' => 'بضاعة اشتريناها بالآجل ولم ندفع بعد',
    'color' => 'red',
    ],
    [
    'term' => 'المستحقات (Accruals)',
    'category' => 'تفصيل الالتزامات',
    'definition' => 'مصروفات مستحقة لم تُسدد بعد',
    'examples' => 'رواتب مستحقة، فواتير كهرباء مستحقة',
    'color' => 'red',
    ],
    // مصطلحات إضافية
    [
    'term' => 'الإهلاك (Depreciation)',
    'category' => 'مصطلحات إضافية',
    'definition' => 'توزيع تكلفة الأصل الثابت على عمره الإنتاجي',
    'examples' => 'سيارة بـ 100,000 / 5 سنوات = 20,000 إهلاك سنوي',
    'color' => 'yellow',
    ],
    [
    'term' => 'رأس المال (Capital)',
    'category' => 'مصطلحات إضافية',
    'definition' => 'المبلغ الذي يستثمره المالك في المنشأة',
    'examples' => 'بدأ المالك المشروع بـ 50,000 ر.ي',
    'color' => 'blue',
    ],
    [
    'term' => 'المسحوبات (Drawings)',
    'category' => 'مصطلحات إضافية',
    'definition' => 'مبالغ يسحبها المالك لاستخدامه الشخصي',
    'examples' => 'سحب الشريك 5,000 ر.ي لنفسه',
    'color' => 'yellow',
    ],
    [
    'term' => 'الأرباح المحتجزة (Retained Earnings)',
    'category' => 'مصطلحات إضافية',
    'definition' => 'الأرباح المتراكمة التي لم تُوزع على الملاك',
    'examples' => 'أرباح سنوات سابقة بقيت لتوسيع المشروع',
    'color' => 'blue',
    ],
    [
    'term' => 'الترحيل (Posting)',
    'category' => 'مصطلحات إضافية',
    'definition' => 'نقل القيد من المسودة إلى الدفاتر الرسمية',
    'examples' => 'بعد الترحيل لا يمكن تعديل القيد',
    'color' => 'primary',
    ],
    [
    'term' => 'دليل الحسابات (Chart of Accounts)',
    'category' => 'مصطلحات إضافية',
    'definition' => 'قائمة هرمية بجميع حسابات المنشأة',
    'examples' => 'الأصول → الأصول المتداولة → النقدية → صندوق الفرع',
    'color' => 'primary',
    ],
    ];
    @endphp

    {{-- Mobile Header --}}
    <header class="lg:hidden sticky top-0 z-50 bg-[#0a1f1c]/95 backdrop-blur-md border-b border-primary/20 p-4">
        <div class="flex items-center justify-between">
            <a href="{{ url('/admin') }}" class="text-primary-light text-sm">← العودة للوحة التحكم</a>
            <img src="{{ asset('workbench.png') }}" alt="Logo" class="w-8 h-auto opacity-80">
        </div>
        <h1 class="text-lg font-bold text-white mt-3 text-center">مقارنة: النظام المحاسبي vs المالي</h1>
        <p class="text-xs text-gray-400 text-center mt-1">تقرير تحليلي مفصّل للفروقات بين النظامين</p>
    </header>

    <div class="flex flex-col lg:flex-row min-h-screen">

        {{-- Sidebar (Desktop) --}}
        <aside class="hidden lg:block w-72 bg-[#0a1f1c]/95 border-l border-primary/20 p-6 fixed top-0 right-0 h-screen overflow-y-auto z-50">
            <a href="{{ url('/admin') }}" class="inline-flex items-center gap-2 text-primary-light hover:text-green-400 text-sm mb-5 transition-colors">
                → العودة للوحة التحكم
            </a>

            <div class="text-center mb-8 pb-5 border-b border-primary/20">
                <h1 class="text-xl font-bold text-white mb-2">مقارنة النظامين</h1>
                <p class="text-sm text-gray-400">المحاسبي vs المالي</p>
            </div>

            <div class="flex flex-col gap-3">
                @foreach($tabs as $id => $tab)
                <button onclick="showTab('{{ $id }}', this)" class="tab-btn w-full p-4 bg-primary/10 border border-primary/20 rounded-xl text-gray-400 font-semibold cursor-pointer transition-all hover:bg-primary/20 hover:-translate-x-1 flex items-center gap-3 text-right {{ $loop->first ? 'active border-r-4 border-r-primary-light text-white' : '' }}">
                    <span class="text-lg w-6 text-center">{{ $tab['icon'] }}</span>
                    <span class="flex-1 text-sm">{{ $tab['title'] }}</span>
                </button>
                @endforeach
            </div>

            <div class="absolute bottom-5 left-5 right-5 text-center pt-5 border-t border-primary/20">
                <img src="{{ asset('workbench.png') }}" alt="Logo" class="w-9 h-auto mx-auto mb-2 opacity-80">
                <span class="text-gray-500 text-xs">Res-ERP System</span>
            </div>
        </aside>

        {{-- Mobile Tabs --}}
        <div class="lg:hidden sticky top-[120px] z-40 bg-[#0a1f1c]/95 backdrop-blur-md px-3 py-3 border-b border-primary/20">
            <div class="flex gap-2 overflow-x-auto pb-1 scrollbar-hide">
                @foreach($tabs as $id => $tab)
                <button onclick="showTab('{{ $id }}', this)" class="tab-btn-mobile flex-shrink-0 px-4 py-2.5 bg-primary/10 border border-primary/20 rounded-full text-gray-400 font-medium text-sm whitespace-nowrap transition-all {{ $loop->first ? 'active bg-primary/30 text-white border-primary-light' : '' }}">
                    <span>{{ $tab['icon'] }}</span>
                    <span>{{ $tab['shortTitle'] }}</span>
                </button>
                @endforeach
            </div>
        </div>

        {{-- Main Content --}}
        <main class="flex-1 lg:mr-72 p-4 lg:p-10 overflow-y-auto">

            {{-- Tab 1: Overview --}}
            <div id="overview" class="tab-content block bg-primary/10 border border-primary/20 rounded-2xl p-4 lg:p-8 animate-fade-in mb-4 lg:mb-0">
                <div class="mb-4 lg:mb-6 pb-3 lg:pb-4 border-b border-primary/20">
                    <h2 class="text-lg lg:text-2xl font-bold text-primary-light flex items-center gap-2 lg:gap-3">
                        📖 نظرة عامة على النظامين
                    </h2>
                </div>

                <p class="text-gray-300 leading-relaxed mb-6 text-sm lg:text-base">
                    يهدف هذا التقرير إلى توضيح الفروقات الجوهرية بين <strong class="text-white">النظام المحاسبي (Accounting System)</strong>
                    و<strong class="text-white">النظام المالي (Financial System)</strong> المستخدمَين في منظومة ERP الخاصة بنا.
                </p>

                <div class="grid lg:grid-cols-2 gap-4 lg:gap-6">
                    {{-- Accounting Card --}}
                    <div class="bg-primary/15 border border-primary/30 rounded-xl p-5">
                        <div class="flex items-center gap-3 mb-4">
                            <span class="w-12 h-12 bg-green-600/30 rounded-xl flex items-center justify-center text-2xl">📚</span>
                            <h3 class="text-lg font-bold text-white">النظام المحاسبي</h3>
                        </div>
                        <p class="text-gray-400 text-sm leading-relaxed">
                            نظام محاسبة رسمي يعتمد على <strong class="text-primary-light">القيد المزدوج (Double Entry)</strong>
                            ودليل الحسابات الهرمي. يُستخدم لإعداد القوائم المالية الرسمية والتقارير المحاسبية المعتمدة.
                        </p>
                    </div>

                    {{-- Financial Card --}}
                    <div class="bg-yellow-900/15 border border-yellow-600/30 rounded-xl p-5">
                        <div class="flex items-center gap-3 mb-4">
                            <span class="w-12 h-12 bg-yellow-600/30 rounded-xl flex items-center justify-center text-2xl">💰</span>
                            <h3 class="text-lg font-bold text-white">النظام المالي</h3>
                        </div>
                        <p class="text-gray-400 text-sm leading-relaxed">
                            نظام تشغيلي مبسّط لتسجيل <strong class="text-yellow-400">الإيرادات والمصروفات اليومية</strong>
                            حسب الفروع والتصنيفات. يركز على المتابعة التشغيلية وليس المحاسبة الرسمية.
                        </p>
                    </div>
                </div>
            </div>

            {{-- Tab 2: Components --}}
            <div id="components" class="tab-content hidden bg-primary/10 border border-primary/20 rounded-2xl p-4 lg:p-8 animate-fade-in mb-4 lg:mb-0">
                <div class="mb-4 lg:mb-6 pb-3 lg:pb-4 border-b border-primary/20">
                    <h2 class="text-lg lg:text-2xl font-bold text-primary-light flex items-center gap-2 lg:gap-3">
                        🏗️ المكونات الأساسية لكل نظام
                    </h2>
                </div>

                <div class="grid lg:grid-cols-2 gap-6">
                    {{-- Accounting Components --}}
                    <div>
                        <h3 class="text-lg font-bold text-green-400 mb-4 flex items-center gap-2">
                            <span class="w-8 h-8 bg-green-600/30 rounded-lg flex items-center justify-center text-sm">📚</span>
                            النظام المحاسبي
                        </h3>
                        <div class="space-y-3">
                            @foreach($componentsData['accounting'] as $item)
                            <div class="bg-primary/5 border border-primary/15 rounded-xl p-4">
                                <span class="font-medium text-white text-sm block">{{ $item['name'] }}</span>
                                <span class="text-gray-400 text-xs mt-1 block">{{ $item['type'] }}</span>
                            </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- Financial Components --}}
                    <div>
                        <h3 class="text-lg font-bold text-yellow-400 mb-4 flex items-center gap-2">
                            <span class="w-8 h-8 bg-yellow-600/30 rounded-lg flex items-center justify-center text-sm">💰</span>
                            النظام المالي
                        </h3>
                        <div class="space-y-3">
                            @foreach($componentsData['financial'] as $item)
                            <div class="bg-yellow-900/10 border border-yellow-600/20 rounded-xl p-4">
                                <span class="font-medium text-white text-sm block">{{ $item['name'] }}</span>
                                <span class="text-gray-400 text-xs mt-1 block">{{ $item['type'] }}</span>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            {{-- Tab 3: Advantages --}}
            <div id="advantages" class="tab-content hidden bg-primary/10 border border-primary/20 rounded-2xl p-4 lg:p-8 animate-fade-in mb-4 lg:mb-0">
                <div class="mb-4 lg:mb-6 pb-3 lg:pb-4 border-b border-primary/20">
                    <h2 class="text-lg lg:text-2xl font-bold text-primary-light flex items-center gap-2 lg:gap-3">
                        🏆 لماذا يتفوق النظام المحاسبي؟
                    </h2>
                </div>

                {{-- Desktop Table --}}
                <div class="hidden lg:block overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr>
                                <th class="text-right p-4 bg-primary/95 font-semibold text-white sticky -top-10 z-10 rounded-tr-lg">الميزة</th>
                                <th class="text-right p-4 bg-primary/95 font-semibold text-white sticky -top-10 z-10 rounded-tl-lg">الشرح</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($accountingFeatures as $feature)
                            <tr class="hover:bg-primary/10 transition-colors">
                                <td class="p-4 border-b border-primary/10 font-medium text-white">{{ $feature['name'] }}</td>
                                <td class="p-4 border-b border-primary/10 text-gray-400 text-sm">{{ $feature['description'] }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Mobile Cards --}}
                <div class="lg:hidden space-y-3">
                    @foreach($accountingFeatures as $feature)
                    <div class="bg-primary/5 border border-primary/15 rounded-xl p-4">
                        <span class="font-medium text-white text-sm block mb-2">{{ $feature['name'] }}</span>
                        <span class="text-gray-400 text-xs leading-relaxed block">{{ $feature['description'] }}</span>
                    </div>
                    @endforeach
                </div>
            </div>

            {{-- Tab 4: Comparison --}}
            <div id="comparison" class="tab-content hidden bg-primary/10 border border-primary/20 rounded-2xl p-4 lg:p-8 animate-fade-in mb-4 lg:mb-0">
                <div class="mb-4 lg:mb-6 pb-3 lg:pb-4 border-b border-primary/20">
                    <h2 class="text-lg lg:text-2xl font-bold text-primary-light flex items-center gap-2 lg:gap-3">
                        📌 جدول المقارنة التفصيلي
                    </h2>
                </div>

                {{-- Desktop Table --}}
                <div class="hidden lg:block overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr>
                                <th class="text-right p-4 bg-primary/95 font-semibold text-white sticky -top-10 z-10 rounded-tr-lg w-1/3">الميزة</th>
                                <th class="text-right p-4 bg-green-700/80 font-semibold text-white sticky -top-10 z-10 w-1/3">النظام المحاسبي</th>
                                <th class="text-right p-4 bg-yellow-700/80 font-semibold text-white sticky -top-10 z-10 rounded-tl-lg w-1/3">النظام المالي</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($comparisonData as $row)
                            <tr class="hover:bg-primary/10 transition-colors">
                                <td class="p-4 border-b border-primary/10 font-medium text-white">{{ $row['feature'] }}</td>
                                <td class="p-4 border-b border-primary/10">
                                    <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-medium
                                        {{ $row['accountingStatus'] === 'available' ? 'bg-green-900/50 text-green-400' : '' }}
                                        {{ $row['accountingStatus'] === 'partial' ? 'bg-yellow-900/50 text-yellow-400' : '' }}
                                        {{ $row['accountingStatus'] === 'unavailable' ? 'bg-red-900/50 text-red-400' : '' }}">
                                        {{ $row['accountingStatus'] === 'available' ? '✓' : ($row['accountingStatus'] === 'partial' ? '⚡' : '✗') }}
                                        {{ $row['accounting'] }}
                                    </span>
                                </td>
                                <td class="p-4 border-b border-primary/10">
                                    <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-medium
                                        {{ $row['financialStatus'] === 'available' ? 'bg-green-900/50 text-green-400' : '' }}
                                        {{ $row['financialStatus'] === 'partial' ? 'bg-yellow-900/50 text-yellow-400' : '' }}
                                        {{ $row['financialStatus'] === 'unavailable' ? 'bg-red-900/50 text-red-400' : '' }}">
                                        {{ $row['financialStatus'] === 'available' ? '✓' : ($row['financialStatus'] === 'partial' ? '⚡' : '✗') }}
                                        {{ $row['financial'] }}
                                    </span>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Mobile Cards --}}
                <div class="lg:hidden space-y-3">
                    @foreach($comparisonData as $row)
                    <div class="bg-primary/5 border border-primary/15 rounded-xl p-4">
                        <span class="font-medium text-white text-sm block mb-3">{{ $row['feature'] }}</span>
                        <div class="flex gap-2">
                            <span class="flex-1 text-center px-2 py-1.5 rounded-lg text-xs
                                {{ $row['accountingStatus'] === 'available' ? 'bg-green-900/50 text-green-400' : '' }}
                                {{ $row['accountingStatus'] === 'partial' ? 'bg-yellow-900/50 text-yellow-400' : '' }}
                                {{ $row['accountingStatus'] === 'unavailable' ? 'bg-red-900/50 text-red-400' : '' }}">
                                المحاسبي: {{ $row['accounting'] }}
                            </span>
                            <span class="flex-1 text-center px-2 py-1.5 rounded-lg text-xs
                                {{ $row['financialStatus'] === 'available' ? 'bg-green-900/50 text-green-400' : '' }}
                                {{ $row['financialStatus'] === 'partial' ? 'bg-yellow-900/50 text-yellow-400' : '' }}
                                {{ $row['financialStatus'] === 'unavailable' ? 'bg-red-900/50 text-red-400' : '' }}">
                                المالي: {{ $row['financial'] }}
                            </span>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>

            {{-- Tab 5: Usage --}}
            <div id="usage" class="tab-content hidden bg-primary/10 border border-primary/20 rounded-2xl p-4 lg:p-8 animate-fade-in mb-4 lg:mb-0">
                <div class="mb-4 lg:mb-6 pb-3 lg:pb-4 border-b border-primary/20">
                    <h2 class="text-lg lg:text-2xl font-bold text-primary-light flex items-center gap-2 lg:gap-3">
                        🤔 متى تستخدم كل نظام؟
                    </h2>
                </div>

                <div class="grid lg:grid-cols-2 gap-4 lg:gap-6">
                    {{-- Accounting Use Cases --}}
                    <div class="bg-primary/15 border-r-4 border-green-500 rounded-xl p-5">
                        <h3 class="text-lg font-bold text-green-400 mb-4 flex items-center gap-2">
                            <span>✓</span> استخدم النظام المحاسبي إذا:
                        </h3>
                        <ul class="space-y-2">
                            @foreach($accountingUseCases as $useCase)
                            <li class="text-gray-300 text-sm flex items-start gap-2">
                                <span class="text-green-400 mt-1">•</span>
                                {{ $useCase }}
                            </li>
                            @endforeach
                        </ul>
                    </div>

                    {{-- Financial Use Cases --}}
                    <div class="bg-yellow-900/15 border-r-4 border-yellow-500 rounded-xl p-5">
                        <h3 class="text-lg font-bold text-yellow-400 mb-4 flex items-center gap-2">
                            <span>💡</span> استخدم النظام المالي إذا:
                        </h3>
                        <ul class="space-y-2">
                            @foreach($financialUseCases as $useCase)
                            <li class="text-gray-300 text-sm flex items-start gap-2">
                                <span class="text-yellow-400 mt-1">•</span>
                                {{ $useCase }}
                            </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>

            {{-- Tab 6: Terminology --}}
            <div id="terminology" class="tab-content hidden bg-primary/10 border border-primary/20 rounded-2xl p-4 lg:p-8 animate-fade-in mb-4 lg:mb-0">
                <div class="mb-4 lg:mb-6 pb-3 lg:pb-4 border-b border-primary/20">
                    <h2 class="text-lg lg:text-2xl font-bold text-primary-light flex items-center gap-2 lg:gap-3">
                        📖 مصطلحات محاسبية أساسية
                    </h2>
                    <p class="text-gray-400 text-sm mt-2">
                        قاموس شامل للمصطلحات المحاسبية المستخدمة في النظام
                    </p>
                </div>

                @php
                $groupedTerms = collect($accountingTerms)->groupBy('category');
                @endphp

                <div class="space-y-6">
                    @foreach($groupedTerms as $category => $terms)
                    <div>
                        <h3 class="text-lg font-bold text-white mb-4 flex items-center gap-2 pb-2 border-b border-primary/20">
                            @if($category === 'أنواع الحسابات')
                            <span class="text-xl">📊</span>
                            @elseif($category === 'القيد المزدوج')
                            <span class="text-xl">⚖️</span>
                            @elseif($category === 'التقارير')
                            <span class="text-xl">📈</span>
                            @elseif($category === 'تفصيل الأصول')
                            <span class="text-xl">💰</span>
                            @elseif($category === 'تفصيل الالتزامات')
                            <span class="text-xl">📋</span>
                            @else
                            <span class="text-xl">📝</span>
                            @endif
                            {{ $category }}
                        </h3>

                        <div class="grid lg:grid-cols-2 gap-3">
                            @foreach($terms as $term)
                            <div class="bg-primary/5 border border-primary/15 rounded-xl p-4 hover:bg-primary/10 transition-all">
                                <div class="flex items-start gap-3">
                                    <span class="w-3 h-3 mt-1.5 rounded-full flex-shrink-0
                                        {{ $term['color'] === 'green' ? 'bg-green-500' : '' }}
                                        {{ $term['color'] === 'red' ? 'bg-red-500' : '' }}
                                        {{ $term['color'] === 'blue' ? 'bg-blue-500' : '' }}
                                        {{ $term['color'] === 'yellow' ? 'bg-yellow-500' : '' }}
                                        {{ $term['color'] === 'primary' ? 'bg-primary-light' : '' }}
                                    "></span>
                                    <div class="flex-1">
                                        <h4 class="font-bold text-white text-sm mb-1">{{ $term['term'] }}</h4>
                                        <p class="text-gray-400 text-xs mb-2">{{ $term['definition'] }}</p>
                                        <div class="bg-[#0a1f1c]/50 rounded-lg px-3 py-1.5">
                                            <span class="text-xs text-gray-500">أمثلة:</span>
                                            <span class="text-xs text-gray-300 mr-1">{{ $term['examples'] }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endforeach
                </div>

                {{-- Quick Reference Card --}}
                <div class="mt-6 bg-gradient-to-br from-primary/25 to-primary-dark/25 border border-primary/40 rounded-2xl p-5 lg:p-6">
                    <h3 class="text-lg font-bold text-primary-light mb-4 flex items-center gap-2">
                        <span>💡</span> ملخص سريع: قواعد المدين والدائن
                    </h3>
                    <div class="grid lg:grid-cols-2 gap-4">
                        <div class="bg-green-900/20 border border-green-600/30 rounded-xl p-4">
                            <h4 class="text-green-400 font-semibold mb-2 text-sm">يزيد بالمدين (الطرف الأيسر) ⬅️</h4>
                            <ul class="text-gray-300 text-xs space-y-1">
                                <li>• الأصول (النقدية، البضاعة، العملاء...)</li>
                                <li>• المصروفات (الرواتب، الإيجار...)</li>
                                <li>• المسحوبات</li>
                            </ul>
                        </div>
                        <div class="bg-blue-900/20 border border-blue-600/30 rounded-xl p-4">
                            <h4 class="text-blue-400 font-semibold mb-2 text-sm">يزيد بالدائن (الطرف الأيمن) ➡️</h4>
                            <ul class="text-gray-300 text-xs space-y-1">
                                <li>• الالتزامات (الموردين، القروض...)</li>
                                <li>• الإيرادات (المبيعات، الخدمات...)</li>
                                <li>• حقوق الملكية (رأس المال...)</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Tab 7: Power of Accounting System --}}
            <div id="power" class="tab-content hidden bg-primary/10 border border-primary/20 rounded-2xl p-4 lg:p-8 animate-fade-in mb-4 lg:mb-0">
                <div class="mb-4 lg:mb-6 pb-3 lg:pb-4 border-b border-primary/20">
                    <h2 class="text-lg lg:text-2xl font-bold text-primary-light flex items-center gap-2 lg:gap-3">
                        ⚡ قوة النظام المحاسبي - ما لا يمكن عمله بالنظام المالي
                    </h2>
                    <p class="text-gray-400 text-sm mt-2">
                        هذه الأمثلة توضح العمليات المحاسبية المتقدمة التي يستحيل تنفيذها باستخدام النظام المالي البسيط
                    </p>
                </div>

                <div class="space-y-6">
                    @foreach($accountingPowerExamples as $example)
                    <div class="bg-gradient-to-br from-primary/15 to-primary/5 border border-primary/25 rounded-2xl p-5 lg:p-6">
                        {{-- Header --}}
                        <div class="flex items-center gap-3 mb-4">
                            <span class="w-12 h-12 bg-primary/30 rounded-xl flex items-center justify-center text-2xl">{{ $example['icon'] }}</span>
                            <h3 class="text-lg font-bold text-white">{{ $example['title'] }}</h3>
                        </div>

                        {{-- Formula Box --}}
                        <div class="bg-[#0a1f1c] border border-primary/40 rounded-xl p-4 mb-4">
                            <div class="text-xs text-gray-500 mb-1">المعادلة:</div>
                            <div class="text-lg lg:text-xl font-bold text-primary-light font-mono text-center py-2">
                                {{ $example['formula'] }}
                            </div>
                        </div>

                        {{-- Description --}}
                        <p class="text-gray-300 text-sm leading-relaxed mb-4">
                            {{ $example['description'] }}
                        </p>

                        {{-- Example Box --}}
                        <div class="bg-green-900/20 border border-green-600/30 rounded-xl p-4 mb-4">
                            <div class="flex items-center gap-2 mb-2">
                                <span class="text-green-400">📝</span>
                                <span class="text-green-400 text-sm font-semibold">مثال عملي:</span>
                            </div>
                            <p class="text-gray-300 text-sm">{{ $example['example'] }}</p>
                        </div>

                        {{-- Why Impossible in Financial --}}
                        <div class="bg-red-900/20 border border-red-600/30 rounded-xl p-4">
                            <div class="flex items-center gap-2 mb-2">
                                <span class="text-red-400">⚠️</span>
                                <span class="text-red-400 text-sm font-semibold">لماذا مستحيل في النظام المالي؟</span>
                            </div>
                            <p class="text-gray-400 text-sm">{{ $example['impossible_in_financial'] }}</p>
                        </div>
                    </div>
                    @endforeach
                </div>

                {{-- Summary Box --}}
                <div class="mt-6 bg-gradient-to-br from-primary/25 to-primary-dark/25 border border-primary/40 rounded-2xl p-5 lg:p-6">
                    <h3 class="text-lg font-bold text-primary-light mb-4 flex items-center gap-2">
                        <span>📊</span> خلاصة الفروقات الجوهرية
                    </h3>
                    <div class="grid lg:grid-cols-2 gap-4">
                        <div class="bg-primary/10 rounded-xl p-4">
                            <h4 class="text-green-400 font-semibold mb-2">النظام المحاسبي يُجيب على:</h4>
                            <ul class="text-gray-300 text-sm space-y-1">
                                <li>• كم نملك من أصول؟</li>
                                <li>• كم علينا من ديون؟</li>
                                <li>• ما هي حقوق الملاك الفعلية؟</li>
                                <li>• هل الدفاتر متوازنة؟</li>
                                <li>• كم المتبقي على العملاء؟</li>
                                <li>• كم المتبقي للموردين؟</li>
                            </ul>
                        </div>
                        <div class="bg-yellow-900/15 rounded-xl p-4">
                            <h4 class="text-yellow-400 font-semibold mb-2">النظام المالي يُجيب على:</h4>
                            <ul class="text-gray-300 text-sm space-y-1">
                                <li>• كم دخلنا هذا الشهر؟</li>
                                <li>• كم صرفنا هذا الشهر؟</li>
                                <li>• ما هو صافي الربح التشغيلي؟</li>
                                <li class="text-gray-500">• ❌ لا يعرف الأصول</li>
                                <li class="text-gray-500">• ❌ لا يعرف الالتزامات</li>
                                <li class="text-gray-500">• ❌ لا يعرف الأرصدة المتبقية</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

        </main>
    </div>

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
    </script>
</body>

</html>