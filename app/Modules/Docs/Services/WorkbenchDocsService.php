<?php

namespace App\Modules\Docs\Services;

class WorkbenchDocsService
{
    /**
     * Retrieve the structured documentation modules.
     * Designed to be highly scalable and cleanly isolated within its module.
     *
     * @return array
     */
    public static function getDocs(): array
    {
        return [
            'console_commands' => [
                'icon' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>',
                'title' => __('docs.cron_title'),
                'description' => __('docs.cron_desc'),
                'sections' => [
                    [
                        'title' => __('docs.cron_backup_title'),
                        'content' => __('docs.cron_backup_content'),
                    ],
                    [
                        'title' => __('docs.cron_warnings_title'),
                        'content' => __('docs.cron_warnings_content'),
                    ],
                    [
                        'title' => __('docs.cron_overtime_title'),
                        'content' => __('docs.cron_overtime_content'),
                    ]
                ]
            ],
            'payroll_multi_branches' => [
                'icon' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z" /></svg>',
                'title' => 'تنقلات الموظفين بين الفروع',
                'description' => 'تقرير يوضح آلية احتساب الرواتب والوضع المحاسبي للموظفين عند انتقالهم بين فروع مختلفة خلال نفس الشهر المالي.',
                'sections' => [
                    [
                        'title' => 'نجاح النظام منطقياً (الدوام والفترات)',
                        'content' => '<p><strong>السيناريو:</strong> انتقال موظف من الفرع (أ) في منتصف الشهر لمواصلة عمله في الفرع (ب) حتى نهاية الشهر.</p><p>تتعامل بنية النظام الحالية مع هذا الانتقال عبر <strong>تهيئة الشيفتات وسجل الحضور</strong> بامتياز. حيث يربط كلاس <code>EmployeePeriodHistory</code> التوزيع الزمني بالفرع الصحيح تلقائياً عبر تاريخي البداية والنهاية، مما يمنع التداخل ويضمن حساب الحضور والتأخير بدقة عالية.</p>',
                    ],
                    [
                        'title' => 'المشكلة القائمة (القصور المحاسبي المتوقع)',
                        'content' => '<p>تظهر المشكلة المنطقية عند إعداد <strong>مسيرات الرواتب الشهرية والتقارير المالية</strong> وتتمثل في نقطتين أساسيتين:</p><ul style="margin-top:0.5rem; padding-inline-start: 1rem;"><li style="margin-bottom:0.5rem;"><strong style="color:var(--primary)">غياب الموظف عن تقارير الفرع التأسيسي:</strong> تعتمد الفلترة الشائعة في النظام على حقل الفرع الحالي للموظف <code>branch_id</code>. مما يعني أن مدير الفرع (أ) لن يتمكن من رؤية الموظف في مسير فترته لأن فرع الموظف الحالي أصبح (ب).</li><li style="margin-bottom:0.5rem;"><strong style="color:var(--primary)">العبء المحاسبي غير العادل (Cost Allocation):</strong> عند استدعاء كلاس <code>SalaryCalculatorService</code> لإنشاء مسير رواتب الفرع (ب)، سيقوم باحتساب وجلب كافة تواريخ حضور الموظف للشهر كاملًا، مما يؤدي إلى تحميل ميزانية الفرع (ب) براتب الشهر بالكامل رغم أن خدمة الموظف الفعلية لديهم كانت مقصورة على النصف الثاني فقط.</li></ul>',
                    ],
                    [
                        'title' => 'أبرز الحلول العملية',
                        'content' => '<p>لمعالجة هذه المعضلة ضمن متطلبات الأنظمة المحاسبية الضخمة، يُوصى بتطبيق أحد الحلين:</p><ol style="margin-top:0.5rem; padding-inline-start: 1.25rem;"><li style="margin-bottom:0.5rem;">أتمتة واعتماد <strong>سجل فرعي للموظف (Employee Branch History)</strong> يتم الرجوع إليه في كل مرة يُطبع فيها مسير الرواتب المرفق لفرع محدد. بحيث تُحصّل أيام الحضور الملتزمة وتُفوتر تلقائياً بمسير كل فرع على حدة وبالمقدار الفعلي.</li><li style="margin-bottom:0.5rem;">تحييد مسؤولي الفروع عن صرف الرواتب وجعل إصدار الرواتب من هيكلية <strong>مركزية (Centralized Payroll)</strong> تصدر عن الإدارة العليا ككتلة واحدة للموظف بغض النظر عن تنقلاته، دون إلحاق التكلفة بالمراكز المحاسبية المستقلة للفروع.</li></ol>',
                    ],
                    [
                        'title' => 'أفضل الممارسات الذكية (رؤية خبراء الـ HR)',
                        'content' => '<p>من منظور هندسة الموارد البشرية الاحترافية للكيانات المعقدة، نوصي بتطبيق الاستراتيجيات الآتية لضمان سلامة العمليات:</p><ul style="margin-top:0.5rem; padding-inline-start: 1rem;"><li style="margin-bottom:0.75rem;"><strong style="color:var(--primary)">التوزيع الديناميكي لمراكز التكلفة (Dynamic Cost Centers):</strong> عدم الاعتماد الكلي على حقل الفرع الثابت للموظف. بل تحويل الشيفتات إلى مراكز تكلفة مستقلة. وفي نهاية الشهر يصدر مسير رواتب موحد للموظف، ثم تُولّد قيود محاسبية تلقائية (Journal Entries) توزع التكلفة المالية للراتب بالنسبة والتناسب (Pro-rata) على الفروع بناءً على أيام العمل المقضية في شيفتات كل تخصص أو فرع.</li><li style="margin-bottom:0.75rem;"><strong style="color:var(--primary)">جدولة النقل المستقبلي والموقوت (Effective Dating):</strong> كأفضل ممارسة إدارية، يُفضّل وضع ضوابط للنظام تمنع النقل المالي الدائم للموظف في منتصف الدورة المالية. وإذا اقتضت طبيعة العمل ذلك، يُعامل الموظف بمبدأ <strong>"الانتداب" (Borrowed Employee)</strong> مالياً حتى إغلاق الشهر، ليتم تفعيل نقله رسمياً ومحاسبياً مع اليوم الأول من الشهر الجديد لضمان استقرار المسيرات والفواتير.</li><li style="margin-bottom:0.75rem;"><strong style="color:var(--primary)">تقرير الأعباء عوضاً عن مسيرات الرواتب:</strong> مسؤولو الفروع لا يجب أن يشغلوا أنفسهم بدورة إصدار الرواتب. الممارسة الأذكى تتمثل بتشغيل الإدارة المركزية لمسير روتيني موحد (Global Payroll)، والاكتفاء بمنح مدير الفرع صلاحية لتقرير محاسبي منفصل يسمى <strong>"مساهمة الفرع بالأجور" (Branch Wage Allocation)</strong>. هذا التقرير يقرأ الأرقام من تواريخ الانصراف الفعلية في الفرع لتزويد المدير بمؤشرات واضحة عن تكلفة العمالة المستهلكة فقط.</li></ul>',
                    ]
                ]
            ],
            // Scale and add future docs here easily.
        ];
    }
}
