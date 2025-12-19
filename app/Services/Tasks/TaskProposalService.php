<?php

namespace App\Services\Tasks;

/**
 * TaskProposalService
 * 
 * Service class containing all improvement proposals for the 
 * Tasks Management System
 */
class TaskProposalService
{
    /**
     * Get all proposals organized by category
     */
    public function getAllProposals(): array
    {
        return [
            'kanban' => $this->getKanbanBoardProposal(),
            'automation' => $this->getAutomationProposal(),
            'templates' => $this->getTaskTemplatesProposal(),
            'notifications' => $this->getNotificationsProposal(),
            'time_tracking' => $this->getTimeTrackingProposal(),
            'dependencies' => $this->getDependenciesProposal(),
            'reports' => $this->getReportsProposal(),
            'calendar' => $this->getCalendarProposal(),
            'gamification' => $this->getGamificationProposal(),
            'collaboration' => $this->getCollaborationProposal(),
            'mobile' => $this->getMobileProposal(),
            'integration' => $this->getIntegrationProposal(),
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
                'key' => 'kanban',
                'title' => 'لوحة كانبان التفاعلية',
                'title_en' => 'Interactive Kanban Board',
                'icon' => 'view-boards',
                'color' => 'blue',
                'priority' => 1,
                'effort' => 'متوسط',
                'description' => 'لوحة سحب وإفلات لإدارة المهام بصرياً',
            ],
            [
                'key' => 'automation',
                'title' => 'الأتمتة الذكية',
                'title_en' => 'Smart Automation',
                'icon' => 'lightning-bolt',
                'color' => 'yellow',
                'priority' => 2,
                'effort' => 'عالي',
                'description' => 'التكليف والتصعيد والتذكير التلقائي',
            ],
            [
                'key' => 'templates',
                'title' => 'قوالب المهام',
                'title_en' => 'Task Templates',
                'icon' => 'template',
                'color' => 'green',
                'priority' => 3,
                'effort' => 'منخفض',
                'description' => 'قوالب جاهزة للمهام المتكررة',
            ],
            [
                'key' => 'notifications',
                'title' => 'نظام الإشعارات',
                'title_en' => 'Notification System',
                'icon' => 'bell',
                'color' => 'purple',
                'priority' => 4,
                'effort' => 'متوسط',
                'description' => 'إشعارات فورية ومجدولة للمهام',
            ],
            [
                'key' => 'time_tracking',
                'title' => 'تتبع الوقت المتقدم',
                'title_en' => 'Advanced Time Tracking',
                'icon' => 'clock',
                'color' => 'orange',
                'priority' => 5,
                'effort' => 'متوسط',
                'description' => 'تسجيل ساعات العمل وتحليل الإنتاجية',
            ],
            [
                'key' => 'dependencies',
                'title' => 'ترابط المهام',
                'title_en' => 'Task Dependencies',
                'icon' => 'link',
                'color' => 'red',
                'priority' => 6,
                'effort' => 'عالي',
                'description' => 'ربط المهام ببعضها (Parent/Child)',
            ],
            [
                'key' => 'reports',
                'title' => 'التقارير المتقدمة',
                'title_en' => 'Advanced Reports',
                'icon' => 'document-report',
                'color' => 'teal',
                'priority' => 7,
                'effort' => 'متوسط',
                'description' => 'تقارير أداء الموظفين والفرق',
            ],
            [
                'key' => 'calendar',
                'title' => 'التقويم المتكامل',
                'title_en' => 'Integrated Calendar',
                'icon' => 'calendar',
                'color' => 'indigo',
                'priority' => 8,
                'effort' => 'متوسط',
                'description' => 'عرض المهام في تقويم تفاعلي',
            ],
            [
                'key' => 'gamification',
                'title' => 'نظام التحفيز (Gamification)',
                'title_en' => 'Gamification System',
                'icon' => 'star',
                'color' => 'amber',
                'priority' => 9,
                'effort' => 'عالي',
                'description' => 'نقاط وشارات ولوحة الصدارة',
            ],
            [
                'key' => 'collaboration',
                'title' => 'التعاون المتقدم',
                'title_en' => 'Advanced Collaboration',
                'icon' => 'users',
                'color' => 'cyan',
                'priority' => 10,
                'effort' => 'متوسط',
                'description' => 'Mentions والعمل الجماعي على المهام',
            ],
            [
                'key' => 'mobile',
                'title' => 'تطبيق الموبايل',
                'title_en' => 'Mobile App',
                'icon' => 'device-mobile',
                'color' => 'pink',
                'priority' => 11,
                'effort' => 'عالي جداً',
                'description' => 'تطبيق جوال لإدارة المهام',
            ],
            [
                'key' => 'integration',
                'title' => 'التكامل مع الأنظمة',
                'title_en' => 'System Integration',
                'icon' => 'puzzle',
                'color' => 'gray',
                'priority' => 12,
                'effort' => 'عالي',
                'description' => 'ربط مع الـ HR والحضور والمشاريع',
            ],
        ];
    }

    /**
     * Kanban Board Proposal
     */
    protected function getKanbanBoardProposal(): array
    {
        return [
            'title' => 'لوحة كانبان التفاعلية',
            'title_en' => 'Interactive Kanban Board',
            'icon' => 'view-boards',
            'color' => 'blue',
            'priority' => 1,
            'effort' => 'متوسط',
            'estimated_days' => '7-10 أيام',
            'description' => 'لوحة تفاعلية لإدارة المهام بالسحب والإفلات، مع أعمدة لكل حالة.',
            'features' => [
                [
                    'title' => 'Drag & Drop',
                    'description' => 'سحب المهام بين الأعمدة لتغيير الحالة',
                    'icon' => 'cursor-click',
                ],
                [
                    'title' => 'أعمدة مخصصة',
                    'description' => 'New, In Progress, Closed, Rejected',
                    'icon' => 'view-boards',
                ],
                [
                    'title' => 'فلاتر سريعة',
                    'description' => 'فلترة حسب الموظف، الفرع، الأولوية',
                    'icon' => 'filter',
                ],
                [
                    'title' => 'بطاقات غنية',
                    'description' => 'عرض المعلومات الأساسية في البطاقة',
                    'icon' => 'credit-card',
                ],
                [
                    'title' => 'Progress Bar',
                    'description' => 'شريط تقدم الخطوات في كل بطاقة',
                    'icon' => 'trending-up',
                ],
                [
                    'title' => 'Quick Actions',
                    'description' => 'إجراءات سريعة (تعليق، رفض، إكمال)',
                    'icon' => 'lightning-bolt',
                ],
            ],
            'technologies' => [
                'Livewire 3 للتفاعلية',
                'SortableJS للسحب والإفلات',
                'Alpine.js للحركة',
                'Tailwind CSS للتصميم',
            ],
            'implementation_steps' => [
                'إنشاء مكون Livewire للـ Kanban',
                'تصميم واجهة الأعمدة والبطاقات',
                'تنفيذ السحب والإفلات',
                'ربط مع API لتحديث الحالة',
                'إضافة فلاتر وبحث',
                'اختبار وتحسين الأداء',
            ],
        ];
    }

    /**
     * Automation Proposal
     */
    protected function getAutomationProposal(): array
    {
        return [
            'title' => 'الأتمتة الذكية',
            'title_en' => 'Smart Automation',
            'icon' => 'lightning-bolt',
            'color' => 'yellow',
            'priority' => 2,
            'effort' => 'عالي',
            'estimated_days' => '14-21 يوم',
            'description' => 'نظام قواعد ذكي لأتمتة العمليات المتكررة.',
            'features' => [
                [
                    'title' => 'التكليف التلقائي',
                    'description' => 'توزيع المهام تلقائياً حسب التخصص أو العبء',
                    'icon' => 'user-add',
                ],
                [
                    'title' => 'التصعيد التلقائي',
                    'description' => 'تصعيد للمدير عند تأخر المهمة',
                    'icon' => 'arrow-up',
                ],
                [
                    'title' => 'التذكير التلقائي',
                    'description' => 'تذكير قبل موعد التسليم',
                    'icon' => 'bell',
                ],
                [
                    'title' => 'الإغلاق التلقائي',
                    'description' => 'إغلاق المهام المكتملة تلقائياً',
                    'icon' => 'check-circle',
                ],
                [
                    'title' => 'قواعد مخصصة',
                    'description' => 'إنشاء قواعد automation مخصصة',
                    'icon' => 'cog',
                ],
            ],
            'automation_rules' => [
                [
                    'trigger' => 'إنشاء مهمة جديدة',
                    'condition' => 'نوع المهمة = صيانة',
                    'action' => 'تكليف قسم الصيانة',
                ],
                [
                    'trigger' => 'مرور 24 ساعة',
                    'condition' => 'الحالة = New',
                    'action' => 'تصعيد للمدير + إشعار',
                ],
                [
                    'trigger' => 'اكتمال جميع الخطوات',
                    'condition' => 'جميع Steps = Done',
                    'action' => 'تغيير الحالة إلى Closed',
                ],
                [
                    'trigger' => 'قبل موعد التسليم بـ 2 ساعة',
                    'condition' => 'الحالة != Closed',
                    'action' => 'إشعار تذكيري',
                ],
            ],
            'database_changes' => [
                [
                    'table' => 'hr_task_automations',
                    'fields' => ['name', 'trigger_type', 'conditions', 'actions', 'active', 'priority'],
                ],
                [
                    'table' => 'hr_task_automation_logs',
                    'fields' => ['automation_id', 'task_id', 'executed_at', 'result'],
                ],
            ],
            'implementation_steps' => [
                'تصميم نظام القواعد',
                'إنشاء Scheduler للتنفيذ',
                'إنشاء واجهة إدارة القواعد',
                'تنفيذ أنواع الـ Triggers',
                'إنشاء سجل التنفيذ',
                'اختبار السيناريوهات',
            ],
        ];
    }

    /**
     * Task Templates Proposal
     */
    protected function getTaskTemplatesProposal(): array
    {
        return [
            'title' => 'قوالب المهام',
            'title_en' => 'Task Templates',
            'icon' => 'template',
            'color' => 'green',
            'priority' => 3,
            'effort' => 'منخفض',
            'estimated_days' => '3-5 أيام',
            'description' => 'قوالب جاهزة للمهام المتكررة لتوفير الوقت.',
            'features' => [
                [
                    'title' => 'قوالب جاهزة',
                    'description' => 'قوالب مسبقة التعريف للمهام الشائعة',
                    'icon' => 'template',
                ],
                [
                    'title' => 'خطوات مسبقة',
                    'description' => 'خطوات (Steps) معرفة مسبقاً',
                    'icon' => 'clipboard-list',
                ],
                [
                    'title' => 'تخصيص عند الاستخدام',
                    'description' => 'تعديل القالب قبل الإنشاء',
                    'icon' => 'pencil',
                ],
                [
                    'title' => 'مشاركة القوالب',
                    'description' => 'مشاركة القوالب بين الفروع',
                    'icon' => 'share',
                ],
            ],
            'template_examples' => [
                'مهمة صيانة دورية',
                'مهمة تدقيق مالي',
                'مهمة تقييم موظف',
                'مهمة إعداد تقرير شهري',
                'مهمة استقبال موظف جديد',
            ],
            'database_changes' => [
                [
                    'table' => 'hr_task_templates',
                    'fields' => ['name', 'description', 'default_assigned_to', 'default_due_days', 'active', 'branch_id'],
                ],
                [
                    'table' => 'hr_task_template_steps',
                    'fields' => ['template_id', 'title', 'order'],
                ],
            ],
            'implementation_steps' => [
                'إنشاء جدول القوالب',
                'إنشاء واجهة إدارة القوالب',
                'إضافة زر "إنشاء من قالب"',
                'تنفيذ نسخ القالب للمهمة',
            ],
        ];
    }

    /**
     * Notifications Proposal
     */
    protected function getNotificationsProposal(): array
    {
        return [
            'title' => 'نظام الإشعارات',
            'title_en' => 'Notification System',
            'icon' => 'bell',
            'color' => 'purple',
            'priority' => 4,
            'effort' => 'متوسط',
            'estimated_days' => '5-7 أيام',
            'description' => 'نظام إشعارات متكامل للمهام.',
            'features' => [
                [
                    'title' => 'إشعار التكليف',
                    'description' => 'إشعار فوري عند تكليف مهمة جديدة',
                    'icon' => 'user-add',
                ],
                [
                    'title' => 'إشعار التعليق',
                    'description' => 'إشعار عند إضافة تعليق على المهمة',
                    'icon' => 'chat',
                ],
                [
                    'title' => 'إشعار تغيير الحالة',
                    'description' => 'إشعار عند تحريك المهمة',
                    'icon' => 'arrow-right',
                ],
                [
                    'title' => 'تذكير الموعد',
                    'description' => 'تذكير قبل موعد التسليم',
                    'icon' => 'clock',
                ],
                [
                    'title' => 'إشعار الرفض',
                    'description' => 'إشعار عند رفض المهمة',
                    'icon' => 'x-circle',
                ],
                [
                    'title' => 'ملخص يومي',
                    'description' => 'ملخص المهام في بداية اليوم',
                    'icon' => 'mail',
                ],
            ],
            'notification_channels' => [
                'Database (داخل التطبيق)',
                'Email',
                'SMS',
                'Push Notification',
                'WhatsApp (اختياري)',
            ],
            'implementation_steps' => [
                'إنشاء Notification classes',
                'إعداد قنوات الإشعارات',
                'ربط مع أحداث المهام',
                'إنشاء إعدادات تفضيلات المستخدم',
                'إنشاء Jobs للإشعارات المجدولة',
            ],
        ];
    }

    /**
     * Time Tracking Proposal
     */
    protected function getTimeTrackingProposal(): array
    {
        return [
            'title' => 'تتبع الوقت المتقدم',
            'title_en' => 'Advanced Time Tracking',
            'icon' => 'clock',
            'color' => 'orange',
            'priority' => 5,
            'effort' => 'متوسط',
            'estimated_days' => '7-10 أيام',
            'description' => 'نظام متقدم لتسجيل وتحليل ساعات العمل على المهام.',
            'features' => [
                [
                    'title' => 'Timer مباشر',
                    'description' => 'بدء وإيقاف العمل على المهمة',
                    'icon' => 'play',
                ],
                [
                    'title' => 'تسجيل يدوي',
                    'description' => 'إدخال الوقت يدوياً',
                    'icon' => 'pencil',
                ],
                [
                    'title' => 'Timesheet',
                    'description' => 'سجل ساعات العمل الأسبوعي',
                    'icon' => 'table',
                ],
                [
                    'title' => 'تقدير vs فعلي',
                    'description' => 'مقارنة الوقت المقدر بالفعلي',
                    'icon' => 'scale',
                ],
                [
                    'title' => 'تحليل الإنتاجية',
                    'description' => 'تحليل إنتاجية الموظفين',
                    'icon' => 'chart-bar',
                ],
            ],
            'time_metrics' => [
                'Total Time Spent - إجمالي الوقت',
                'Time per Status - الوقت في كل حالة',
                'Estimated vs Actual - المقدر مقابل الفعلي',
                'Average Time per Task Type - متوسط الوقت حسب النوع',
            ],
            'database_changes' => [
                [
                    'table' => 'hr_task_time_entries',
                    'fields' => ['task_id', 'user_id', 'started_at', 'ended_at', 'duration_minutes', 'description'],
                ],
                [
                    'table' => 'hr_tasks (تحديث)',
                    'fields' => ['estimated_hours', 'actual_hours'],
                ],
            ],
            'implementation_steps' => [
                'إنشاء جدول سجلات الوقت',
                'إنشاء Timer component',
                'إنشاء واجهة Timesheet',
                'إنشاء تقارير الإنتاجية',
                'ربط مع نظام الرواتب (اختياري)',
            ],
        ];
    }

    /**
     * Dependencies Proposal
     */
    protected function getDependenciesProposal(): array
    {
        return [
            'title' => 'ترابط المهام',
            'title_en' => 'Task Dependencies',
            'icon' => 'link',
            'color' => 'red',
            'priority' => 6,
            'effort' => 'عالي',
            'estimated_days' => '10-14 يوم',
            'description' => 'نظام لربط المهام ببعضها (Parent/Child, Blocked By).',
            'features' => [
                [
                    'title' => 'Parent/Child',
                    'description' => 'مهام رئيسية وفرعية',
                    'icon' => 'folder',
                ],
                [
                    'title' => 'Blocked By',
                    'description' => 'مهمة تعتمد على أخرى',
                    'icon' => 'lock-closed',
                ],
                [
                    'title' => 'Related Tasks',
                    'description' => 'مهام مرتبطة',
                    'icon' => 'link',
                ],
                [
                    'title' => 'Subtasks Progress',
                    'description' => 'تقدم المهام الفرعية',
                    'icon' => 'trending-up',
                ],
                [
                    'title' => 'Gantt View',
                    'description' => 'عرض جانت للتبعيات',
                    'icon' => 'chart-bar',
                ],
            ],
            'dependency_types' => [
                'Parent/Child - مهمة رئيسية وفرعية',
                'Blocked By - يجب إكمال X قبل Y',
                'Blocks - X تمنع Y',
                'Related - مهام مرتبطة',
            ],
            'database_changes' => [
                [
                    'table' => 'hr_task_dependencies',
                    'fields' => ['task_id', 'depends_on_id', 'type'],
                ],
                [
                    'table' => 'hr_tasks (تحديث)',
                    'fields' => ['parent_task_id'],
                ],
            ],
            'implementation_steps' => [
                'إنشاء جدول التبعيات',
                'تعديل نموذج Task',
                'إنشاء واجهة ربط المهام',
                'منع إغلاق المهام المحظورة',
                'إنشاء عرض Gantt',
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
            'priority' => 7,
            'effort' => 'متوسط',
            'estimated_days' => '7-10 أيام',
            'description' => 'تقارير شاملة لتحليل أداء الموظفين والفرق.',
            'reports' => [
                [
                    'name' => 'Employee Performance',
                    'name_ar' => 'أداء الموظف',
                    'description' => 'عدد المهام، متوسط وقت الإنجاز، نسبة الرفض',
                ],
                [
                    'name' => 'Team Workload',
                    'name_ar' => 'حمل عمل الفريق',
                    'description' => 'توزيع المهام على الفريق',
                ],
                [
                    'name' => 'Task Completion Rate',
                    'name_ar' => 'معدل الإنجاز',
                    'description' => 'نسبة المهام المكتملة في الوقت',
                ],
                [
                    'name' => 'Rejection Analysis',
                    'name_ar' => 'تحليل الرفض',
                    'description' => 'أسباب الرفض وتكرارها',
                ],
                [
                    'name' => 'Branch Comparison',
                    'name_ar' => 'مقارنة الفروع',
                    'description' => 'أداء الفروع في إنجاز المهام',
                ],
                [
                    'name' => 'Yellow/Red Cards',
                    'name_ar' => 'الكروت التحذيرية',
                    'description' => 'تقرير الإنذارات والكروت',
                ],
            ],
            'export_formats' => ['PDF', 'Excel', 'CSV'],
            'implementation_steps' => [
                'تصميم قوالب التقارير',
                'إنشاء Query classes',
                'إنشاء Export functionality',
                'إضافة للوحة Filament',
                'إنشاء جدولة الإرسال',
            ],
        ];
    }

    /**
     * Calendar Proposal
     */
    protected function getCalendarProposal(): array
    {
        return [
            'title' => 'التقويم المتكامل',
            'title_en' => 'Integrated Calendar',
            'icon' => 'calendar',
            'color' => 'indigo',
            'priority' => 8,
            'effort' => 'متوسط',
            'estimated_days' => '5-7 أيام',
            'description' => 'عرض المهام في تقويم تفاعلي مع مواعيد التسليم.',
            'features' => [
                [
                    'title' => 'عرض يومي/أسبوعي/شهري',
                    'description' => 'تبديل بين العروض المختلفة',
                    'icon' => 'view-grid',
                ],
                [
                    'title' => 'Drag & Drop',
                    'description' => 'نقل المهام بين الأيام',
                    'icon' => 'cursor-click',
                ],
                [
                    'title' => 'ألوان الحالات',
                    'description' => 'تمييز المهام بألوان الحالة',
                    'icon' => 'color-swatch',
                ],
                [
                    'title' => 'تكامل مع Google Calendar',
                    'description' => 'مزامنة مع تقويم جوجل',
                    'icon' => 'cloud',
                ],
            ],
            'technologies' => [
                'FullCalendar.js',
                'Livewire للتفاعل',
                'Google Calendar API',
            ],
            'implementation_steps' => [
                'إدماج FullCalendar',
                'ربط مع بيانات المهام',
                'تنفيذ Drag & Drop للتواريخ',
                'تكامل مع Google Calendar',
            ],
        ];
    }

    /**
     * Gamification Proposal
     */
    protected function getGamificationProposal(): array
    {
        return [
            'title' => 'نظام التحفيز (Gamification)',
            'title_en' => 'Gamification System',
            'icon' => 'star',
            'color' => 'amber',
            'priority' => 9,
            'effort' => 'عالي',
            'estimated_days' => '14-21 يوم',
            'description' => 'نظام نقاط وشارات وتحديات لتحفيز الموظفين.',
            'features' => [
                [
                    'title' => 'نظام النقاط',
                    'description' => 'نقاط على إكمال المهام',
                    'icon' => 'star',
                ],
                [
                    'title' => 'الشارات (Badges)',
                    'description' => 'شارات للإنجازات',
                    'icon' => 'badge-check',
                ],
                [
                    'title' => 'لوحة الصدارة',
                    'description' => 'ترتيب الموظفين بالنقاط',
                    'icon' => 'chart-bar',
                ],
                [
                    'title' => 'التحديات',
                    'description' => 'تحديات أسبوعية/شهرية',
                    'icon' => 'fire',
                ],
                [
                    'title' => 'المستويات',
                    'description' => 'مستويات للموظفين',
                    'icon' => 'trending-up',
                ],
            ],
            'point_system' => [
                'إكمال مهمة = 10 نقاط',
                'إكمال في الوقت = +5 نقاط',
                'تقييم 5 نجوم = +10 نقاط',
                'رفض المهمة = -5 نقاط',
                'Yellow Card = -20 نقاط',
                'Red Card = -50 نقاط',
            ],
            'badges' => [
                'First Task - أول مهمة',
                'Speed Master - إنجاز 10 مهام في يوم',
                'Perfect Week - أسبوع بدون رفض',
                'Top Performer - المركز الأول لمدة شهر',
            ],
            'database_changes' => [
                [
                    'table' => 'hr_employee_points',
                    'fields' => ['employee_id', 'points', 'level', 'updated_at'],
                ],
                [
                    'table' => 'hr_employee_badges',
                    'fields' => ['employee_id', 'badge_id', 'earned_at'],
                ],
                [
                    'table' => 'hr_badges',
                    'fields' => ['name', 'description', 'icon', 'points_required'],
                ],
            ],
            'implementation_steps' => [
                'تصميم نظام النقاط',
                'إنشاء جداول Gamification',
                'ربط مع أحداث المهام',
                'إنشاء لوحة الصدارة',
                'تصميم الشارات',
                'إنشاء التحديات',
            ],
        ];
    }

    /**
     * Collaboration Proposal
     */
    protected function getCollaborationProposal(): array
    {
        return [
            'title' => 'التعاون المتقدم',
            'title_en' => 'Advanced Collaboration',
            'icon' => 'users',
            'color' => 'cyan',
            'priority' => 10,
            'effort' => 'متوسط',
            'estimated_days' => '7-10 أيام',
            'description' => 'أدوات تعاون متقدمة للعمل الجماعي على المهام.',
            'features' => [
                [
                    'title' => 'Mentions (@)',
                    'description' => 'ذكر المستخدمين في التعليقات',
                    'icon' => 'at-symbol',
                ],
                [
                    'title' => 'Multi-Assignee',
                    'description' => 'تكليف أكثر من شخص',
                    'icon' => 'users',
                ],
                [
                    'title' => 'Watchers',
                    'description' => 'متابعة المهمة بدون تكليف',
                    'icon' => 'eye',
                ],
                [
                    'title' => 'Reactions',
                    'description' => 'تفاعلات على التعليقات',
                    'icon' => 'heart',
                ],
                [
                    'title' => 'Activity Feed',
                    'description' => 'تغذية الأنشطة الأخيرة',
                    'icon' => 'rss',
                ],
            ],
            'database_changes' => [
                [
                    'table' => 'hr_task_assignees',
                    'fields' => ['task_id', 'employee_id', 'role'],
                ],
                [
                    'table' => 'hr_task_watchers',
                    'fields' => ['task_id', 'user_id'],
                ],
                [
                    'table' => 'hr_comment_reactions',
                    'fields' => ['comment_id', 'user_id', 'reaction'],
                ],
            ],
            'implementation_steps' => [
                'تنفيذ Mentions parsing',
                'إنشاء Multi-assignee',
                'إنشاء نظام Watchers',
                'إضافة Reactions',
                'إنشاء Activity Feed',
            ],
        ];
    }

    /**
     * Mobile App Proposal
     */
    protected function getMobileProposal(): array
    {
        return [
            'title' => 'تطبيق الموبايل',
            'title_en' => 'Mobile App',
            'icon' => 'device-mobile',
            'color' => 'pink',
            'priority' => 11,
            'effort' => 'عالي جداً',
            'estimated_days' => '30-45 يوم',
            'description' => 'تطبيق جوال لإدارة المهام من أي مكان.',
            'features' => [
                [
                    'title' => 'قائمة المهام',
                    'description' => 'عرض مهامي وإدارتها',
                    'icon' => 'clipboard-list',
                ],
                [
                    'title' => 'إشعارات Push',
                    'description' => 'إشعارات فورية',
                    'icon' => 'bell',
                ],
                [
                    'title' => 'تحديث الحالة',
                    'description' => 'تغيير حالة المهمة',
                    'icon' => 'refresh',
                ],
                [
                    'title' => 'التعليقات',
                    'description' => 'إضافة تعليقات وصور',
                    'icon' => 'chat',
                ],
                [
                    'title' => 'وضع Offline',
                    'description' => 'العمل بدون إنترنت',
                    'icon' => 'cloud-off',
                ],
            ],
            'technologies' => [
                'Flutter أو React Native',
                'Laravel API Backend',
                'Firebase Cloud Messaging',
                'SQLite للتخزين المحلي',
            ],
            'screens' => [
                'قائمة المهام',
                'تفاصيل المهمة',
                'التعليقات',
                'الإشعارات',
                'الملف الشخصي',
            ],
            'implementation_steps' => [
                'تصميم UI/UX',
                'تطوير API endpoints',
                'بناء التطبيق',
                'تنفيذ Offline mode',
                'نشر على المتاجر',
            ],
        ];
    }

    /**
     * Integration Proposal
     */
    protected function getIntegrationProposal(): array
    {
        return [
            'title' => 'التكامل مع الأنظمة',
            'title_en' => 'System Integration',
            'icon' => 'puzzle',
            'color' => 'gray',
            'priority' => 12,
            'effort' => 'عالي',
            'estimated_days' => '14-21 يوم',
            'description' => 'ربط نظام المهام مع الأنظمة الأخرى في الـ ERP.',
            'integrations' => [
                [
                    'system' => 'نظام الحضور',
                    'description' => 'ربط ساعات العمل بالمهام',
                    'icon' => 'clock',
                    'benefits' => 'تتبع الوقت الفعلي للمهام',
                ],
                [
                    'system' => 'نظام الرواتب',
                    'description' => 'احتساب مكافآت الأداء',
                    'icon' => 'currency-dollar',
                    'benefits' => 'ربط الأداء بالمكافآت',
                ],
                [
                    'system' => 'نظام الصيانة',
                    'description' => 'إنشاء مهام من طلبات الصيانة',
                    'icon' => 'cog',
                    'benefits' => 'تتبع مهام الصيانة',
                ],
                [
                    'system' => 'نظام المشاريع',
                    'description' => 'ربط المهام بالمشاريع',
                    'icon' => 'folder',
                    'benefits' => 'إدارة مشاريع متكاملة',
                ],
            ],
            'api_endpoints' => [
                'POST /tasks/from-service-request',
                'GET /tasks/by-project/{id}',
                'GET /employees/{id}/task-hours',
            ],
            'implementation_steps' => [
                'تحليل API الأنظمة الأخرى',
                'إنشاء Integration services',
                'تنفيذ Webhooks',
                'إنشاء Events/Listeners',
                'اختبار التكامل',
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
            'high_priority' => 6,
            'medium_priority' => 4,
            'low_priority' => 2,
            'estimated_total_days' => '120-170 يوم',
            'categories' => [
                'User Experience' => 4,
                'Automation' => 2,
                'Analytics' => 2,
                'Integration' => 4,
            ],
        ];
    }
}
