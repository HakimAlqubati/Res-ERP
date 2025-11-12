<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تطوير إعدادات حركة المخزون</title>
    <style>
        body {
            font-family: 'Cairo', sans-serif;
            background-color: #f8fafc;
            color: #1b1b1b;
            line-height: 1.9;
            margin: 0;
            padding: 0;
        }

        main {
            max-width: 950px;
            margin: 40px auto;
            background: #fff;
            padding: 50px 60px;
            border-radius: 18px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.07);
        }

        h2 {
            color: #15803d;
            font-size: 23px;
            margin-top: 35px;
            border-right: 5px solid #16a34a;
            padding-right: 10px;
        }

        p {
            margin: 14px 0;
            font-size: 17px;
        }

        .scenario {
            background-color: #f0fdf4;
            border: 1px solid #bbf7d0;
            border-right: 6px solid #16a34a;
            padding: 25px 35px;
            border-radius: 14px;
            margin: 25px 0;
        }

        .scenario h3 {
            color: #14532d;
            margin-top: 0;
        }

        .scenario ul {
            list-style: none;
            padding: 0;
        }

        .scenario ul li {
            margin: 8px 0;
        }

        .scenario ul li::before {
            content: '•';
            color: #16a34a;
            font-weight: bold;
            margin-left: 10px;
        }

        .config-box {
            background: #ecfdf5;
            border: 2px solid #10b981;
            border-radius: 12px;
            padding: 30px 35px;
            margin-top: 40px;
        }

        .config-title {
            font-size: 20px;
            font-weight: 700;
            color: #065f46;
            margin-bottom: 20px;
            text-align: center;
        }

        .config-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 25px;
        }

        .config-option {
            background: #fff;
            border: 1px solid #d1fae5;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.04);
        }

        .config-option label {
            display: block;
            font-weight: 600;
            color: #065f46;
            margin-bottom: 10px;
        }

        select {
            width: 100%;
            padding: 10px;
            border-radius: 8px;
            border: 1px solid #a7f3d0;
            background-color: #f9fafb;
            font-size: 15px;
        }
    </style>
</head>

<body>
    <main>

        <div class="scenario">
            <h3>السيناريو الحالي</h3>
            <p>
                في النظام الحالي، عند تغيير حالة الطلب إلى <strong>“جاهز للتسليم”</strong> يقوم النظام تلقائيًا
                بخصم الكميات من المخزن الرئيسي وإضافتها مباشرة إلى مخزن الفرع، وكأن البضاعة انتقلت فعليًا.
            </p>
        </div>

        <h2>الحاجة إلى التعديل</h2>
        <p>
            تختلف إدارات المطاعم في طريقة التشغيل، فبعضها يعتمد على سائقين لنقل الطلبات بين الفروع،
            بينما البعض الآخر يُنجز التسليم داخليًا دون سائقين. لذلك من الضروري أن يُتيح النظام مرونة
            لإدارة كل فرع في تحديد توقيت الخصم من المخزن الرئيسي، وتوقيت الإضافة إلى مخزن الفرع، بما
            يتوافق مع الواقع التشغيلي الفعلي.
        </p>

        <h2>السيناريوهات المقترحة</h2>

        <section class="scenario">
            <h3>1. في حال اعتماد نظام السائقين</h3>
            <p>
                يتم التحكم في حركة المخزون وفق المراحل الواقعية للنقل. الخصم يتم عند مغادرة السائق
                للمخزن الرئيسي، والإضافة تتم فقط بعد أن يؤكد <strong>مدير الفرع</strong> استلام البضاعة فعليًا.
            </p>
            <ul>
                <li><strong>جاهز للتسليم:</strong> لا يحدث أي تأثير على المخزون.</li>
                <li><strong>في الطريق:</strong> يتم خصم الكميات من المخزن الرئيسي.</li>
                <li><strong>تم التسليم من قبل مدير الفرع:</strong> يتم إدخال الكميات إلى مخزن الفرع
                    بعد تأكيد مدير الفرع استلامها بنجاح.</li>
            </ul>
        </section>

        <section class="scenario">
            <h3>2. في حال التسليم الداخلي</h3>
            <p>
                في هذا النموذج، لا يوجد سائق توصيل. يتم خصم الكمية بمجرد تجهيز الطلب، ثم تُضاف إلى
                مخزن الفرع بعد أن يقوم <strong>مدير الفرع</strong> بتغيير حالة الطلب إلى
                <strong>“تم التسليم من قبل مدير الفرع”</strong>.
            </p>
            <ul>
                <li><strong>جاهز للتسليم:</strong> يتم خصم الكميات من المخزن الرئيسي.</li>
                <li><strong>تم التسليم من قبل مدير الفرع:</strong> تتم الإضافة إلى مخزن الفرع.</li>
            </ul>
        </section>

        <div class="config-box">
            <div class="config-title">تصور واجهة إعدادات النظام</div>
            <p style="text-align:center; margin-bottom:25px; color:#065f46; font-weight:500;">
                من خلال هذه الواجهة، يمكن للإدارة تحديد توقيت الخصم والإضافة بدقة، بحيث يُعكس سير
                العمل الواقعي في النظام دون أي تدخل يدوي.
            </p>
            <div class="config-grid">
                <div class="config-option">
                    <label>وقت خصم الكمية من المخزن الرئيسي:</label>
                    <select>
                        <option selected>عند جاهز للتسليم</option>
                        <option>عند في الطريق</option>
                    </select>
                </div>
                <div class="config-option">
                    <label>وقت إدخال الكمية في مخزن الفرع:</label>
                    <select>
                        <option>عند جاهز للتسليم</option>
                        <option>عند تم التوصيل</option>
                        <option selected>عند تم التسليم من قبل مدير الفرع</option>
                    </select>
                </div>
            </div>
        </div>

    </main>
</body>

</html>
