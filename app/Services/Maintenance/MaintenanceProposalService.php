<?php

namespace App\Services\Maintenance;

/**
 * MaintenanceProposalService
 * 
 * Service class containing all improvement proposals for the 
 * Service Request & Equipment Management System
 */
class MaintenanceProposalService
{
    /**
     * Get all proposals organized by category
     */
    public function getAllProposals(): array
    {
        return [
            'notifications' => $this->getNotificationsProposal(),
            'dashboard' => $this->getDashboardProposal(),
            'sla' => $this->getSLAProposal(),
            'predictive' => $this->getPredictiveMaintenanceProposal(),
            'mobile' => $this->getMobileAppProposal(),
            'spare_parts' => $this->getSparePartsProposal(),
            'reports' => $this->getReportsProposal(),
            'workflow' => $this->getWorkflowProposal(),
            'rating' => $this->getRatingProposal(),
            'knowledge_base' => $this->getKnowledgeBaseProposal(),
            'iot' => $this->getIoTProposal(),
            'technical' => $this->getTechnicalImprovementsProposal(),
        ];
    }

    /**
     * Get single proposal by key
     */
    public function getProposal(string $key): ?array
    {
        $proposals = $this->getAllProposals();
        return $proposals[$key] ?? null;
    }

    /**
     * Get proposals summary for index page
     */
    public function getProposalsSummary(): array
    {
        return [
            [
                'key' => 'notifications',
                'title' => 'نظام الإشعارات الذكية',
                'title_en' => 'Smart Notifications System',
                'icon' => 'bell',
                'color' => 'blue',
                'priority' => 2,
                'effort' => 'متوسط',
                'description' => 'إشعارات فورية للموظفين والمديرين عند تغيير حالة الطلبات',
            ],
            [
                'key' => 'dashboard',
                'title' => 'لوحة التحكم المتقدمة',
                'title_en' => 'Advanced Dashboard',
                'icon' => 'chart-bar',
                'color' => 'green',
                'priority' => 1,
                'effort' => 'متوسط',
                'description' => 'لوحة تحكم تفاعلية مع KPIs ورسوم بيانية',
            ],
            [
                'key' => 'sla',
                'title' => 'نظام SLA',
                'title_en' => 'SLA Management',
                'icon' => 'clock',
                'color' => 'yellow',
                'priority' => 4,
                'effort' => 'عالي',
                'description' => 'اتفاقية مستوى الخدمة مع تصعيد تلقائي',
            ],
            [
                'key' => 'predictive',
                'title' => 'الصيانة التنبؤية',
                'title_en' => 'Predictive Maintenance',
                'icon' => 'cpu',
                'color' => 'purple',
                'priority' => 10,
                'effort' => 'عالي جداً',
                'description' => 'تحليل ذكي للتنبؤ بالأعطال قبل حدوثها',
            ],
            [
                'key' => 'mobile',
                'title' => 'تطبيق الموبايل',
                'title_en' => 'Mobile App',
                'icon' => 'device-mobile',
                'color' => 'indigo',
                'priority' => 8,
                'effort' => 'عالي جداً',
                'description' => 'تطبيق للفنيين مع مسح QR وتحديث ميداني',
            ],
            [
                'key' => 'spare_parts',
                'title' => 'إدارة قطع الغيار',
                'title_en' => 'Spare Parts Management',
                'icon' => 'cog',
                'color' => 'orange',
                'priority' => 7,
                'effort' => 'عالي',
                'description' => 'ربط طلبات الخدمة بالمخزون وقطع الغيار',
            ],
            [
                'key' => 'reports',
                'title' => 'التقارير المتقدمة',
                'title_en' => 'Advanced Reports',
                'icon' => 'document-report',
                'color' => 'teal',
                'priority' => 3,
                'effort' => 'متوسط',
                'description' => 'تقارير شاملة عن الأداء والتكاليف',
            ],
            [
                'key' => 'workflow',
                'title' => 'سير العمل المتقدم',
                'title_en' => 'Advanced Workflow',
                'icon' => 'adjustments',
                'color' => 'red',
                'priority' => 9,
                'effort' => 'عالي',
                'description' => 'أتمتة التكليف والتصعيد والإغلاق',
            ],
            [
                'key' => 'rating',
                'title' => 'نظام التقييم',
                'title_en' => 'Rating System',
                'icon' => 'star',
                'color' => 'amber',
                'priority' => 5,
                'effort' => 'منخفض',
                'description' => 'تقييم جودة الخدمة وأداء الفنيين',
            ],
            [
                'key' => 'knowledge_base',
                'title' => 'قاعدة المعرفة',
                'title_en' => 'Knowledge Base',
                'icon' => 'book-open',
                'color' => 'cyan',
                'priority' => 6,
                'effort' => 'متوسط',
                'description' => 'حلول موثقة للمشاكل الشائعة',
            ],
            [
                'key' => 'iot',
                'title' => 'تكامل IoT',
                'title_en' => 'IoT Integration',
                'icon' => 'wifi',
                'color' => 'pink',
                'priority' => 11,
                'effort' => 'عالي جداً',
                'description' => 'ربط المعدات الذكية بالنظام',
            ],
            [
                'key' => 'technical',
                'title' => 'تحسينات تقنية',
                'title_en' => 'Technical Improvements',
                'icon' => 'code',
                'color' => 'gray',
                'priority' => 12,
                'effort' => 'متنوع',
                'description' => 'تحسينات برمجية وتجربة المستخدم',
            ],
        ];
    }

    /**
     * Smart Notifications Proposal
     */
    protected function getNotificationsProposal(): array
    {
        return [
            'title' => 'نظام الإشعارات الذكية',
            'title_en' => 'Smart Notifications System',
            'icon' => 'bell',
            'color' => 'blue',
            'priority' => 2,
            'effort' => 'متوسط',
            'estimated_days' => '5-7 أيام',
            'description' => 'نظام إشعارات متكامل يضمن وصول المعلومات المهمة للأشخاص المناسبين في الوقت المناسب.',
            'features' => [
                [
                    'title' => 'إشعار عند إنشاء طلب جديد',
                    'description' => 'إشعار فوري للمسؤول عن الفرع ومدير الصيانة عند إنشاء طلب جديد',
                    'icon' => 'plus-circle',
                ],
                [
                    'title' => 'إشعار التكليف',
                    'description' => 'إشعار للموظف عند تكليفه بطلب جديد مع تفاصيل الطلب',
                    'icon' => 'user-add',
                ],
                [
                    'title' => 'إشعار تغيير الحالة',
                    'description' => 'إشعار للعميل/المنشئ عند تغيير حالة الطلب',
                    'icon' => 'refresh',
                ],
                [
                    'title' => 'تنبيه الصيانة الدورية',
                    'description' => 'تنبيهات قبل موعد الصيانة الدورية (3 أيام، يوم واحد)',
                    'icon' => 'calendar',
                ],
                [
                    'title' => 'تنبيه انتهاء الضمان',
                    'description' => 'تنبيه عند اقتراب انتهاء ضمان المعدة',
                    'icon' => 'shield-exclamation',
                ],
                [
                    'title' => 'ملخص يومي',
                    'description' => 'إشعار يومي للمدير بالطلبات العاجلة غير المعالجة',
                    'icon' => 'mail',
                ],
            ],
            'technologies' => [
                'Laravel Notifications (Database, Email, SMS)',
                'Pusher/Ably للإشعارات الفورية',
                'تكامل مع WhatsApp Business API',
                'Firebase Cloud Messaging للموبايل',
            ],
            'database_changes' => [
                [
                    'table' => 'notification_settings',
                    'fields' => ['user_id', 'channel', 'event_type', 'enabled'],
                ],
                [
                    'table' => 'notification_logs',
                    'fields' => ['user_id', 'type', 'data', 'read_at', 'sent_at'],
                ],
            ],
            'implementation_steps' => [
                'إنشاء Notification classes لكل نوع إشعار',
                'إعداد القنوات (Email, Database, SMS)',
                'إنشاء صفحة إعدادات الإشعارات للمستخدم',
                'إنشاء Jobs للإشعارات المجدولة',
                'تكامل مع Pusher للإشعارات الفورية',
            ],
        ];
    }

    /**
     * Dashboard Proposal
     */
    protected function getDashboardProposal(): array
    {
        return [
            'title' => 'لوحة التحكم المتقدمة',
            'title_en' => 'Advanced Dashboard',
            'icon' => 'chart-bar',
            'color' => 'green',
            'priority' => 1,
            'effort' => 'متوسط',
            'estimated_days' => '7-10 أيام',
            'description' => 'لوحة تحكم تفاعلية تعرض إحصائيات ومؤشرات أداء رئيسية بشكل مرئي جذاب.',
            'features' => [
                [
                    'title' => 'KPIs رئيسية',
                    'description' => 'عدد الطلبات المفتوحة/المغلقة، متوسط وقت الإغلاق، نسبة SLA',
                    'icon' => 'trending-up',
                ],
                [
                    'title' => 'رسوم بيانية تفاعلية',
                    'description' => 'Trend الطلبات اليومية/الأسبوعية، توزيع حسب الفرع',
                    'icon' => 'chart-pie',
                ],
                [
                    'title' => 'خريطة حرارية',
                    'description' => 'الفروع الأكثر طلبات، مناطق الفرع الأكثر مشاكل',
                    'icon' => 'map',
                ],
                [
                    'title' => 'أداء الفنيين',
                    'description' => 'جدول مرتب بأداء كل فني مع إحصائياته',
                    'icon' => 'users',
                ],
                [
                    'title' => 'المعدات الحرجة',
                    'description' => 'قائمة بأكثر المعدات مشاكل وتحتاج انتباه',
                    'icon' => 'exclamation',
                ],
                [
                    'title' => 'فلاتر متقدمة',
                    'description' => 'فلترة حسب الفرع، الفترة الزمنية، النوع',
                    'icon' => 'filter',
                ],
            ],
            'technologies' => [
                'Chart.js أو ApexCharts للرسوم',
                'Livewire لتحديث البيانات الفوري',
                'Filament Widgets',
                'CSS Grid للتخطيط المرن',
            ],
            'widgets' => [
                'StatWidget - إحصائيات سريعة',
                'ChartWidget - رسوم بيانية',
                'TableWidget - جداول بيانات',
                'ListWidget - قوائم الطلبات',
            ],
            'implementation_steps' => [
                'تصميم تخطيط Dashboard',
                'إنشاء Widgets لكل مؤشر',
                'إنشاء API endpoints للبيانات',
                'إضافة فلاتر تفاعلية',
                'تحسين الأداء مع caching',
            ],
        ];
    }

    /**
     * SLA Proposal
     */
    protected function getSLAProposal(): array
    {
        return [
            'title' => 'نظام SLA (اتفاقية مستوى الخدمة)',
            'title_en' => 'SLA Management System',
            'icon' => 'clock',
            'color' => 'yellow',
            'priority' => 4,
            'effort' => 'عالي',
            'estimated_days' => '10-14 يوم',
            'description' => 'نظام لمتابعة مستوى الخدمة وضمان الالتزام بالمعايير المحددة.',
            'features' => [
                [
                    'title' => 'تعريف SLA حسب الأولوية',
                    'description' => 'High: رد 2 ساعة، حل 24 ساعة | Medium: رد 8 ساعات، حل 72 ساعة | Low: رد 24 ساعة، حل 7 أيام',
                    'icon' => 'clock',
                ],
                [
                    'title' => 'التصعيد التلقائي',
                    'description' => 'تصعيد للمدير عند تجاوز SLA',
                    'icon' => 'arrow-up',
                ],
                [
                    'title' => 'تتبع الأوقات',
                    'description' => 'Response Time و Resolution Time لكل طلب',
                    'icon' => 'clock',
                ],
                [
                    'title' => 'تقارير انتهاك SLA',
                    'description' => 'تقارير تفصيلية بالطلبات التي تجاوزت SLA',
                    'icon' => 'document-report',
                ],
            ],
            'sla_definitions' => [
                ['priority' => 'High', 'response' => '2 ساعة', 'resolution' => '24 ساعة', 'color' => 'red'],
                ['priority' => 'Medium', 'response' => '8 ساعات', 'resolution' => '72 ساعة', 'color' => 'yellow'],
                ['priority' => 'Low', 'response' => '24 ساعة', 'resolution' => '7 أيام', 'color' => 'green'],
            ],
            'database_changes' => [
                [
                    'table' => 'hr_sla_policies',
                    'fields' => ['name', 'priority', 'response_hours', 'resolution_hours', 'escalation_level'],
                ],
                [
                    'table' => 'hr_service_requests (تحديث)',
                    'fields' => ['first_response_at', 'resolved_at', 'sla_breached', 'escalation_level'],
                ],
            ],
            'implementation_steps' => [
                'إنشاء جدول سياسات SLA',
                'إضافة حقول التتبع في جدول الطلبات',
                'إنشاء Observer لحساب الأوقات',
                'إنشاء Job للتصعيد التلقائي',
                'إنشاء تقارير SLA',
            ],
        ];
    }

    /**
     * Predictive Maintenance Proposal
     */
    protected function getPredictiveMaintenanceProposal(): array
    {
        return [
            'title' => 'الصيانة الوقائية التنبؤية',
            'title_en' => 'Predictive Maintenance',
            'icon' => 'cpu',
            'color' => 'purple',
            'priority' => 10,
            'effort' => 'عالي جداً',
            'estimated_days' => '20-30 يوم',
            'description' => 'استخدام البيانات التاريخية للتنبؤ بالأعطال قبل حدوثها.',
            'features' => [
                [
                    'title' => 'تحليل تاريخ الأعطال',
                    'description' => 'تحليل البيانات للتنبؤ بالمشاكل المستقبلية',
                    'icon' => 'chart-bar',
                ],
                [
                    'title' => 'جدولة صيانة ذكية',
                    'description' => 'جدولة تلقائية بناءً على الاستخدام والتاريخ',
                    'icon' => 'calendar',
                ],
                [
                    'title' => 'تنبيه مبكر',
                    'description' => 'تنبيه للمعدات ذات معدل أعطال مرتفع',
                    'icon' => 'exclamation-triangle',
                ],
                [
                    'title' => 'اقتراح الاستبدال',
                    'description' => 'اقتراح استبدال المعدة إذا تكررت المشاكل (> 5 أعطال/شهر)',
                    'icon' => 'switch-horizontal',
                ],
                [
                    'title' => 'درجة الموثوقية',
                    'description' => 'حساب Reliability Score لكل معدة',
                    'icon' => 'badge-check',
                ],
            ],
            'metrics' => [
                'MTBF - Mean Time Between Failures',
                'MTTR - Mean Time To Repair',
                'Failure Rate',
                'Reliability Score (0-100)',
            ],
            'database_changes' => [
                [
                    'table' => 'hr_equipment (تحديث)',
                    'fields' => ['failure_count', 'mtbf', 'mttr', 'reliability_score', 'last_failure_at'],
                ],
                [
                    'table' => 'hr_equipment_predictions',
                    'fields' => ['equipment_id', 'predicted_failure_date', 'confidence', 'recommendation'],
                ],
            ],
            'implementation_steps' => [
                'جمع وتحليل البيانات التاريخية',
                'إنشاء خوارزمية التنبؤ',
                'إنشاء Dashboard للصيانة التنبؤية',
                'إنشاء نظام التنبيهات المبكرة',
                'اختبار وتحسين الدقة',
            ],
        ];
    }

    /**
     * Mobile App Proposal
     */
    protected function getMobileAppProposal(): array
    {
        return [
            'title' => 'تطبيق موبايل للفنيين',
            'title_en' => 'Technician Mobile App',
            'icon' => 'device-mobile',
            'color' => 'indigo',
            'priority' => 8,
            'effort' => 'عالي جداً',
            'estimated_days' => '30-45 يوم',
            'description' => 'تطبيق جوال يمكّن الفنيين من إدارة طلباتهم من الميدان.',
            'features' => [
                [
                    'title' => 'قائمة الطلبات',
                    'description' => 'عرض الطلبات المكلف بها مع التفاصيل',
                    'icon' => 'clipboard-list',
                ],
                [
                    'title' => 'مسح QR Code',
                    'description' => 'مسح كود المعدة للوصول السريع لمعلوماتها',
                    'icon' => 'qrcode',
                ],
                [
                    'title' => 'تحديث الحالة',
                    'description' => 'تحديث حالة الطلب من الميدان مباشرة',
                    'icon' => 'refresh',
                ],
                [
                    'title' => 'رفع الصور',
                    'description' => 'التقاط ورفع صور قبل/بعد الإصلاح',
                    'icon' => 'camera',
                ],
                [
                    'title' => 'التوقيع الرقمي',
                    'description' => 'توقيع العميل الرقمي بعد إنجاز العمل',
                    'icon' => 'pencil',
                ],
                [
                    'title' => 'وضع Offline',
                    'description' => 'العمل بدون إنترنت مع مزامنة لاحقة',
                    'icon' => 'cloud-off',
                ],
                [
                    'title' => 'تتبع GPS',
                    'description' => 'تتبع موقع الفني في الميدان',
                    'icon' => 'location-marker',
                ],
            ],
            'technologies' => [
                'Flutter أو React Native',
                'Laravel API Backend',
                'SQLite للتخزين المحلي',
                'Firebase للإشعارات',
                'Google Maps API',
            ],
            'screens' => [
                'شاشة تسجيل الدخول',
                'قائمة الطلبات',
                'تفاصيل الطلب',
                'مسح QR',
                'رفع الصور',
                'التوقيع الرقمي',
                'الإعدادات',
            ],
            'implementation_steps' => [
                'تصميم UI/UX للتطبيق',
                'إنشاء API endpoints المطلوبة',
                'تطوير التطبيق (Flutter/React Native)',
                'تنفيذ وضع Offline',
                'اختبار ونشر على المتاجر',
            ],
        ];
    }

    /**
     * Spare Parts Management Proposal
     */
    protected function getSparePartsProposal(): array
    {
        return [
            'title' => 'نظام إدارة قطع الغيار',
            'title_en' => 'Spare Parts Management',
            'icon' => 'cog',
            'color' => 'orange',
            'priority' => 7,
            'effort' => 'عالي',
            'estimated_days' => '14-21 يوم',
            'description' => 'ربط طلبات الخدمة بالمخزون وتتبع استهلاك قطع الغيار.',
            'features' => [
                [
                    'title' => 'ربط القطع بالطلب',
                    'description' => 'تسجيل قطع الغيار المستخدمة في كل طلب',
                    'icon' => 'link',
                ],
                [
                    'title' => 'خصم تلقائي',
                    'description' => 'خصم تلقائي من المخزون عند الاستخدام',
                    'icon' => 'minus-circle',
                ],
                [
                    'title' => 'تنبيه المخزون',
                    'description' => 'تنبيه عند انخفاض مخزون قطع الغيار',
                    'icon' => 'exclamation',
                ],
                [
                    'title' => 'تقرير التكلفة',
                    'description' => 'تقرير تكلفة الصيانة لكل معدة',
                    'icon' => 'currency-dollar',
                ],
                [
                    'title' => 'اقتراح الشراء',
                    'description' => 'اقتراح شراء قطع غيار بناءً على الاستهلاك',
                    'icon' => 'shopping-cart',
                ],
            ],
            'database_changes' => [
                [
                    'table' => 'hr_service_request_parts',
                    'fields' => ['service_request_id', 'product_id', 'quantity', 'unit_cost', 'total_cost'],
                ],
                [
                    'table' => 'hr_equipment_parts (اختياري)',
                    'fields' => ['equipment_type_id', 'product_id', 'is_common'],
                ],
            ],
            'integration' => [
                'ربط مع نظام المخزون الحالي',
                'ربط مع نظام المشتريات',
                'ربط مع التقارير المالية',
            ],
            'implementation_steps' => [
                'إنشاء جدول قطع الغيار للطلبات',
                'إنشاء واجهة إضافة القطع في الطلب',
                'ربط مع المخزون للخصم التلقائي',
                'إنشاء تقارير التكلفة',
                'إنشاء نظام التنبيهات',
            ],
        ];
    }

    /**
     * Reports Proposal
     */
    protected function getReportsProposal(): array
    {
        return [
            'title' => 'التقارير المتقدمة',
            'title_en' => 'Advanced Reports',
            'icon' => 'document-report',
            'color' => 'teal',
            'priority' => 3,
            'effort' => 'متوسط',
            'estimated_days' => '7-10 أيام',
            'description' => 'مجموعة تقارير شاملة لتحليل أداء نظام الصيانة.',
            'reports' => [
                [
                    'name' => 'Equipment Health Report',
                    'name_ar' => 'تقرير صحة المعدات',
                    'description' => 'حالة جميع المعدات في الفروع',
                    'icon' => 'heart',
                ],
                [
                    'name' => 'Technician Performance',
                    'name_ar' => 'أداء الفنيين',
                    'description' => 'عدد الطلبات، متوسط وقت الإغلاق لكل فني',
                    'icon' => 'users',
                ],
                [
                    'name' => 'Branch Comparison',
                    'name_ar' => 'مقارنة الفروع',
                    'description' => 'مقارنة الفروع من حيث عدد الأعطال',
                    'icon' => 'office-building',
                ],
                [
                    'name' => 'Cost Analysis',
                    'name_ar' => 'تحليل التكاليف',
                    'description' => 'تكلفة الصيانة لكل معدة/فرع',
                    'icon' => 'currency-dollar',
                ],
                [
                    'name' => 'Warranty Tracker',
                    'name_ar' => 'متتبع الضمان',
                    'description' => 'المعدات التي ينتهي ضمانها قريباً',
                    'icon' => 'shield-check',
                ],
                [
                    'name' => 'Overdue Maintenance',
                    'name_ar' => 'الصيانات المتأخرة',
                    'description' => 'الصيانات الدورية المتأخرة',
                    'icon' => 'clock',
                ],
                [
                    'name' => 'Top Problematic Equipment',
                    'name_ar' => 'أكثر المعدات مشاكل',
                    'description' => 'أكثر 10 معدات تحتاج صيانة',
                    'icon' => 'exclamation-triangle',
                ],
            ],
            'export_formats' => ['PDF', 'Excel', 'CSV'],
            'features' => [
                'فلاتر متقدمة (تاريخ، فرع، نوع)',
                'جدولة إرسال تلقائي',
                'رسوم بيانية في التقارير',
                'مقارنة فترات زمنية',
            ],
            'implementation_steps' => [
                'تصميم قوالب التقارير',
                'إنشاء Queries للبيانات',
                'إنشاء Export functionality',
                'إضافة للوحة Filament',
                'إنشاء جدولة الإرسال',
            ],
        ];
    }

    /**
     * Workflow Proposal
     */
    protected function getWorkflowProposal(): array
    {
        return [
            'title' => 'سير العمل المتقدم',
            'title_en' => 'Advanced Workflow',
            'icon' => 'adjustments',
            'color' => 'red',
            'priority' => 9,
            'effort' => 'عالي',
            'estimated_days' => '14-21 يوم',
            'description' => 'أتمتة عمليات التكليف والتصعيد والإغلاق.',
            'features' => [
                [
                    'title' => 'التكليف التلقائي',
                    'description' => 'تكليف بناءً على نوع المعدة، الفرع، أو أقل فني مشغول',
                    'icon' => 'user-add',
                ],
                [
                    'title' => 'التصعيد التلقائي',
                    'description' => 'بعد 24 ساعة → المدير، بعد 48 ساعة → المدير العام',
                    'icon' => 'arrow-up',
                ],
                [
                    'title' => 'الإغلاق التلقائي',
                    'description' => 'إغلاق بعد 7 أيام من الحل بدون رد العميل',
                    'icon' => 'check-circle',
                ],
                [
                    'title' => 'قواعد مخصصة',
                    'description' => 'إنشاء قواعد workflow مخصصة',
                    'icon' => 'cog',
                ],
            ],
            'workflow_rules' => [
                [
                    'trigger' => 'إنشاء طلب جديد',
                    'condition' => 'نوع المعدة = تكييف',
                    'action' => 'تكليف فريق التكييف',
                ],
                [
                    'trigger' => 'مرور 24 ساعة',
                    'condition' => 'الحالة = New',
                    'action' => 'تصعيد للمدير',
                ],
                [
                    'trigger' => 'مرور 7 أيام',
                    'condition' => 'الحالة = Resolved',
                    'action' => 'إغلاق تلقائي',
                ],
            ],
            'database_changes' => [
                [
                    'table' => 'hr_workflow_rules',
                    'fields' => ['name', 'trigger', 'conditions', 'actions', 'active', 'priority'],
                ],
                [
                    'table' => 'hr_workflow_logs',
                    'fields' => ['rule_id', 'service_request_id', 'executed_at', 'result'],
                ],
            ],
            'implementation_steps' => [
                'تصميم نظام القواعد',
                'إنشاء Scheduler للتنفيذ',
                'إنشاء واجهة إدارة القواعد',
                'إنشاء سجل التنفيذ',
                'اختبار السيناريوهات',
            ],
        ];
    }

    /**
     * Rating System Proposal
     */
    protected function getRatingProposal(): array
    {
        return [
            'title' => 'نظام التقييم',
            'title_en' => 'Rating System',
            'icon' => 'star',
            'color' => 'amber',
            'priority' => 5,
            'effort' => 'منخفض',
            'estimated_days' => '3-5 أيام',
            'description' => 'نظام لتقييم جودة الخدمة وأداء الفنيين.',
            'features' => [
                [
                    'title' => 'تقييم العميل',
                    'description' => 'تقييم بعد إغلاق الطلب (1-5 نجوم)',
                    'icon' => 'star',
                ],
                [
                    'title' => 'تعليق نصي',
                    'description' => 'تعليق نصي اختياري من العميل',
                    'icon' => 'chat',
                ],
                [
                    'title' => 'تقييم الفني',
                    'description' => 'تقييم من المدير للفني',
                    'icon' => 'user',
                ],
                [
                    'title' => 'تقييم تلقائي',
                    'description' => 'حساب تلقائي بناءً على سرعة الاستجابة وجودة الحل',
                    'icon' => 'calculator',
                ],
            ],
            'rating_criteria' => [
                'سرعة الاستجابة (Response Time)',
                'جودة الحل (Quality)',
                'عدم تكرار المشكلة (No Recurrence)',
                'رضا العميل (Customer Satisfaction)',
            ],
            'database_changes' => [
                [
                    'table' => 'hr_service_request_ratings',
                    'fields' => ['service_request_id', 'rating', 'feedback', 'rated_by', 'rated_at'],
                ],
                [
                    'table' => 'hr_technician_ratings',
                    'fields' => ['employee_id', 'period', 'avg_rating', 'total_requests', 'avg_resolution_time'],
                ],
            ],
            'implementation_steps' => [
                'إنشاء جدول التقييمات',
                'إضافة نموذج التقييم بعد الإغلاق',
                'حساب متوسط تقييم الفني',
                'عرض التقييمات في الـ Dashboard',
                'إنشاء تقرير الأداء',
            ],
        ];
    }

    /**
     * Knowledge Base Proposal
     */
    protected function getKnowledgeBaseProposal(): array
    {
        return [
            'title' => 'قاعدة المعرفة',
            'title_en' => 'Knowledge Base',
            'icon' => 'book-open',
            'color' => 'cyan',
            'priority' => 6,
            'effort' => 'متوسط',
            'estimated_days' => '7-10 أيام',
            'description' => 'قاعدة بيانات للحلول الموثقة للمشاكل الشائعة.',
            'features' => [
                [
                    'title' => 'حلول موثقة',
                    'description' => 'توثيق حلول للمشاكل الشائعة',
                    'icon' => 'document-text',
                ],
                [
                    'title' => 'ربط بالمعدات',
                    'description' => 'ربط الحلول بأنواع المعدات',
                    'icon' => 'link',
                ],
                [
                    'title' => 'اقتراح تلقائي',
                    'description' => 'اقتراح حلول عند إنشاء طلب جديد',
                    'icon' => 'light-bulb',
                ],
                [
                    'title' => 'Wiki داخلي',
                    'description' => 'منصة wiki لفريق الصيانة',
                    'icon' => 'library',
                ],
                [
                    'title' => 'مرفقات',
                    'description' => 'فيديوهات، PDFs، دليل المستخدم',
                    'icon' => 'paper-clip',
                ],
                [
                    'title' => 'بحث متقدم',
                    'description' => 'بحث بالكلمات المفتاحية',
                    'icon' => 'search',
                ],
            ],
            'database_changes' => [
                [
                    'table' => 'hr_knowledge_base',
                    'fields' => ['title', 'solution', 'equipment_type_id', 'keywords', 'views_count', 'helpful_count'],
                ],
                [
                    'table' => 'hr_knowledge_base_media',
                    'fields' => ['knowledge_base_id', 'type', 'path', 'title'],
                ],
            ],
            'implementation_steps' => [
                'إنشاء جدول قاعدة المعرفة',
                'إنشاء واجهة إضافة/تعديل الحلول',
                'إنشاء محرك البحث',
                'ربط مع نموذج إنشاء الطلب',
                'إضافة إحصائيات الاستخدام',
            ],
        ];
    }

    /**
     * IoT Integration Proposal
     */
    protected function getIoTProposal(): array
    {
        return [
            'title' => 'تكامل IoT (إنترنت الأشياء)',
            'title_en' => 'IoT Integration',
            'icon' => 'wifi',
            'color' => 'pink',
            'priority' => 11,
            'effort' => 'عالي جداً',
            'estimated_days' => '45-60 يوم',
            'description' => 'ربط المعدات الذكية بالنظام لمراقبة فورية.',
            'features' => [
                [
                    'title' => 'ربط المعدات الذكية',
                    'description' => 'استقبال بيانات من المعدات المتصلة',
                    'icon' => 'link',
                ],
                [
                    'title' => 'تنبيهات تلقائية',
                    'description' => 'استقبال تنبيهات فورية عند الخلل',
                    'icon' => 'bell',
                ],
                [
                    'title' => 'بيانات الأداء',
                    'description' => 'قراءة درجة الحرارة، الاستهلاك، الضغط',
                    'icon' => 'chart-bar',
                ],
                [
                    'title' => 'طلب خدمة تلقائي',
                    'description' => 'إنشاء طلب خدمة تلقائي عند الخلل',
                    'icon' => 'plus-circle',
                ],
                [
                    'title' => 'Dashboard فوري',
                    'description' => 'مراقبة حالة المعدات في الوقت الفعلي',
                    'icon' => 'desktop-computer',
                ],
            ],
            'technologies' => [
                'MQTT Protocol',
                'InfluxDB لبيانات السلسلة الزمنية',
                'Grafana للمراقبة',
                'Arduino/Raspberry Pi للأجهزة',
            ],
            'use_cases' => [
                'مكيفات ذكية - مراقبة الحرارة',
                'ثلاجات - مراقبة درجة التبريد',
                'مولدات - مراقبة الوقود والتشغيل',
                'أنظمة إنذار - حالة الأمان',
            ],
            'implementation_steps' => [
                'اختيار بروتوكول الاتصال',
                'إنشاء API لاستقبال البيانات',
                'إنشاء قاعدة بيانات السلاسل الزمنية',
                'إنشاء Dashboard المراقبة',
                'ربط مع نظام الطلبات',
            ],
        ];
    }

    /**
     * Technical Improvements Proposal
     */
    protected function getTechnicalImprovementsProposal(): array
    {
        return [
            'title' => 'تحسينات تقنية',
            'title_en' => 'Technical Improvements',
            'icon' => 'code',
            'color' => 'gray',
            'priority' => 12,
            'effort' => 'متنوع',
            'estimated_days' => '10-15 يوم',
            'description' => 'تحسينات برمجية وتحسين تجربة المستخدم.',
            'improvements' => [
                [
                    'title' => 'Soft Delete للطلبات',
                    'description' => 'إمكانية استعادة الطلبات المحذوفة',
                    'effort' => 'منخفض',
                    'icon' => 'trash',
                ],
                [
                    'title' => 'Export Excel/PDF',
                    'description' => 'تصدير البيانات بصيغ مختلفة',
                    'effort' => 'منخفض',
                    'icon' => 'download',
                ],
                [
                    'title' => 'طباعة Work Order',
                    'description' => 'طباعة أمر العمل للفني',
                    'effort' => 'منخفض',
                    'icon' => 'printer',
                ],
                [
                    'title' => 'Timeline كامل',
                    'description' => 'عرض تاريخ كامل لكل طلب',
                    'effort' => 'متوسط',
                    'icon' => 'clock',
                ],
                [
                    'title' => 'بحث متقدم',
                    'description' => 'فلاتر متعددة للبحث',
                    'effort' => 'متوسط',
                    'icon' => 'search',
                ],
                [
                    'title' => 'Kanban Board',
                    'description' => 'سحب وإفلات لتغيير الحالات',
                    'effort' => 'عالي',
                    'icon' => 'view-boards',
                ],
                [
                    'title' => 'Mentions (@user)',
                    'description' => 'ذكر المستخدمين في التعليقات',
                    'effort' => 'متوسط',
                    'icon' => 'at-symbol',
                ],
                [
                    'title' => 'مرفقات متنوعة',
                    'description' => 'دعم أنواع ملفات أخرى غير الصور',
                    'effort' => 'منخفض',
                    'icon' => 'paper-clip',
                ],
                [
                    'title' => 'Clone Request',
                    'description' => 'نسخ طلب سابق',
                    'effort' => 'منخفض',
                    'icon' => 'duplicate',
                ],
                [
                    'title' => 'Parent/Child Requests',
                    'description' => 'ربط طلبات مرتبطة',
                    'effort' => 'متوسط',
                    'icon' => 'link',
                ],
            ],
            'implementation_priority' => [
                'أولوية عالية' => ['Soft Delete', 'Export', 'Timeline'],
                'أولوية متوسطة' => ['Work Order', 'بحث متقدم', 'Mentions'],
                'أولوية منخفضة' => ['Kanban', 'Clone', 'Parent/Child'],
            ],
        ];
    }

    /**
     * Get statistics for dashboard
     */
    public function getStatistics(): array
    {
        return [
            'total_proposals' => 12,
            'high_priority' => 5,
            'medium_priority' => 4,
            'low_priority' => 3,
            'estimated_total_days' => '150-200 يوم',
            'categories' => [
                'Automation' => 3,
                'Analytics' => 3,
                'User Experience' => 3,
                'Integration' => 3,
            ],
        ];
    }
}
