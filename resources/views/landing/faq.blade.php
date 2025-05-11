<!-- resources/views/landing/faq.blade.php -->
<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>الأسئلة الشائعة حول نظام Workbench ERP | نظام إدارة المطاعم والمخزون | NLT ماليزيا</title>

    <meta name="description"
        content="كل ما تحتاج معرفته عن نظام Workbench ERP لإدارة المطاعم والمخازن والمطبخ المركزي. اكتشف مزايا النظام، طريقة العمل، التوسع، التقارير، الدعم الفني، وخدمات شركة NLT في ماليزيا.">

    <meta name="keywords"
        content="
    Workbench ERP,
    ERP للمطاعم,
    نظام ERP عربي,
    نظام إدارة مطاعم,
    نظام مخزون مطاعم,
    نظام توريد داخلي,
    نظام فروع مطاعم,
    نظام مطبخ مركزي,
    نظام تصنيع داخلي,
    تقارير ERP,
    تحليل أداء الفروع,
    نظام مشتريات المطاعم,
    نظام تتبع الهدر,
    ERP ماليزي,
    ERP في كوالالمبور,
    Next Level Tech,
    NLT ماليزيا,
    ERP جاهز للمطاعم,
    نظام ERP مع الدعم الفني,
    سعر نظام ERP للمطاعم,
    الأسئلة الشائعة عن ERP,
    ERP باللغة العربية,
    ERP للسلسلة الغذائية,
    ERP للسوبر ماركت,
    نظام محاسبة مطاعم,
    نظام إدارة جرد,
    ERP لسلاسل التوريد
    ">

    <style>
        body {
            font-family: 'Tajawal', sans-serif;
            background-color: #f9f9f9;
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: 900px;
            margin: 60px auto;
            background-color: #fff;
            padding: 40px;
            border-radius: 16px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.05);
        }

        h1 {
            text-align: center;
            color: #2c3e50;
            margin-bottom: 30px;
        }

        .accordion {
            margin-bottom: 10px;
            border-radius: 12px;
            overflow: hidden;
            border: 1px solid #e0e0e0;
        }

        .accordion-header {
            background-color: #f1f1f1;
            padding: 15px 20px;
            cursor: pointer;
            font-weight: bold;
            color: #333;
            transition: background-color 0.3s;
        }

        .accordion-header:hover {
            background-color: #e0e0e0;
        }

        .accordion-body {
            padding: 15px 20px;
            background-color: #fff;
            display: none;
            border-top: 1px solid #ddd;
            color: #555;
            line-height: 1.7;
        }

        footer {
            margin-top: 50px;
            text-align: center;
            color: #888;
            font-size: 14px;
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const headers = document.querySelectorAll('.accordion-header');

            headers.forEach(header => {
                header.addEventListener('click', () => {
                    const body = header.nextElementSibling;

                    // أغلق كل الأجوبة الأخرى
                    document.querySelectorAll('.accordion-body').forEach(b => {
                        if (b !== body) b.style.display = 'none';
                    });

                    // بدّل عرض العنصر الحالي
                    body.style.display = (body.style.display === 'block') ? 'none' : 'block';
                });
            });
        });
    </script>

</head>

<body>
    <div class="container">
        <figure>
            <img src="https://nltworkbench.com/storage/logo/default.png"
                alt="شعار نظام Workbench ERP لإدارة المطاعم والمخزون" width="200" height="auto" loading="lazy">
            <figcaption>شعار Workbench ERP - مقدم من شركة NLT - ماليزيا</figcaption>
        </figure>

        <h1>الأسئلة الشائعة عن نظام Workbench ERP</h1>

        @php
            $faqs = [
                [
                    'ما هو Workbench ERP؟',
                    'هو نظام ERP متكامل لإدارة المطاعم وسلاسل التوريد الغذائية، يُغطي إدارة المخزون، المشتريات، الطلبات اليومية، التصنيع الداخلي، وتتبع الأداء، مقدم من شركة NLT الماليزية.',
                ],
                [
                    'هل النظام مناسب لسلسلة مطاعم تحتوي على فروع ومطبخ مركزي؟',
                    'نعم، النظام يدعم الفروع الفردية والمطبخ المركزي، مع فصل كامل في حركة المخزون والطلبات بينهما، ويوفر تقارير دقيقة للمراقبة.',
                ],
                [
                    'هل النظام يدعم تتبع المواد المركبة والتصنيع الداخلي؟',
                    'نعم، يمكنك تعريف وصفات وإنتاج مركبات غذائية، وتتبع الكميات والهدر، وتوليد حركات إدخال تلقائية للمخزون الناتج.',
                ],
                [
                    'هل النظام متاح باللغة العربية؟',
                    'نعم، تم تطوير واجهات النظام بالكامل باللغة العربية، مع دعم التقويم الهجري والميلادي.',
                ],
                [
                    'ما هي الشركة المطورة للنظام؟',
                    'تم تطوير النظام بواسطة NLT - Next Level Tech، وهي شركة تقنية مقرها كوالالمبور - ماليزيا، متخصصة في حلول ERP للمطاعم والشركات الغذائية.',
                ],
                [
                    'هل يمكن تخصيص النظام حسب احتياجات المطعم؟',
                    'نعم، يتم تصميم الحلول بحسب متطلبات العميل، مع دعم عمليات التوسع وتعدد الفروع.',
                ],
                [
                    'هل النظام يدعم التحليلات والتقارير؟',
                    'يوفر النظام تقارير يومية، أسبوعية، وشهرية تغطي المبيعات، الاستهلاك، الهدر، الطلبات، والمخزون، مع مؤشرات أداء رئيسية.',
                ],
                [
                    'كيف يمكن التواصل مع الشركة؟',
                    'من خلال الموقع الرسمي <a href="http://nextleveltech.xyz" target="_blank">nextleveltech.xyz</a> أو عبر البريد الإلكتروني الموجود في صفحة التواصل.',
                ],
            ];
        @endphp

        @foreach ($faqs as $faq)
            <div class="accordion">
                <div class="accordion-header">{{ $faq[0] }}</div>
                <div class="accordion-body">{!! $faq[1] !!}</div>
            </div>
        @endforeach

        <footer>
            جميع الحقوق محفوظة © {{ date('Y') }} | Workbench ERP<br>
            تنفيذ وتطوير بواسطة NLT - Next Level Tech | ماليزيا - كوالالمبور
        </footer>
    </div>
</body>

</html>
