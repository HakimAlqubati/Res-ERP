<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>توثيق API — إنشاء معدّة (Equipment)</title>
    <style>
        :root {
            --bg: #0b0f14;
            --panel: #101720;
            --panel-2: #0f141b;
            --text: #e6edf3;
            --muted: #9fb0c3;
            --brand: #00d3a7;
            --brand-2: #06b6d4;
            --ok: #22c55e;
            --warn: #f59e0b;
            --err: #ef4444;
            --code: #0b1220;
            --border: #1b2633;
            --chip: #0f2230;
        }

        * {
            box-sizing: border-box
        }

        body {
            margin: 0;
            font-family: ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, "Noto Sans", "Helvetica Neue", Arial, "Apple Color Emoji", "Segoe UI Emoji";
            background: linear-gradient(180deg, #081018, #0b0f14 120px);
            color: var(--text);
        }

        .container {
            max-width: 1100px;
            margin: 0 auto;
            padding: 28px
        }

        header {
            display: flex;
            gap: 18px;
            align-items: center;
            margin-bottom: 20px
        }

        .logo {
            width: 42px;
            height: 42px;
            display: grid;
            place-items: center;
            border-radius: 12px;
            background: radial-gradient(120% 120% at 20% 20%, var(--brand), transparent 50%), radial-gradient(120% 120% at 80% 80%, var(--brand-2), transparent 50%), var(--panel);
            box-shadow: 0 8px 30px rgba(0, 0, 0, .35)
        }

        h1 {
            font-size: clamp(22px, 3.2vw, 32px);
            margin: 0
        }

        .subtitle {
            color: var(--muted);
            margin-top: 4px
        }

        .grid {
            display: grid;
            grid-template-columns: 1.2fr .8fr;
            gap: 20px
        }

        @media (max-width: 980px) {
            .grid {
                grid-template-columns: 1fr
            }
        }

        .card {
            background: linear-gradient(180deg, rgba(255, 255, 255, .02), rgba(255, 255, 255, .005)), var(--panel);
            border: 1px solid var(--border);
            border-radius: 18px;
            padding: 18px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, .25)
        }

        .card h2 {
            margin: 0 0 8px 0;
            font-size: 20px
        }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 12px;
            border-radius: 999px;
            border: 1px solid var(--border);
            background: var(--chip);
            color: var(--text);
            font-weight: 600;
            font-size: 13px
        }

        .row {
            display: flex;
            gap: 12px;
            flex-wrap: wrap
        }

        .chip {
            padding: 6px 10px;
            border-radius: 999px;
            background: var(--panel-2);
            border: 1px solid var(--border);
            color: var(--muted);
            font-size: 12px
        }

        code,
        kbd,
        pre {
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace
        }

        pre {
            background: linear-gradient(180deg, rgba(6, 12, 20, .6), rgba(6, 12, 20, .4)), var(--code);
            border: 1px solid var(--border);
            color: #d1e7ff;
            padding: 14px;
            border-radius: 14px;
            overflow: auto
        }

        .http {
            display: inline-grid;
            grid-auto-flow: column;
            gap: 8px;
            align-items: center
        }

        .method {
            font-weight: 800;
            letter-spacing: .5px
        }

        .GET,
        .POST,
        .PUT,
        .DELETE {
            padding: 6px 10px;
            border-radius: 8px;
            font-size: 12px
        }

        .GET {
            background: #1d4ed81a;
            border: 1px solid #1d4ed8;
            color: #93c5fd
        }

        .POST {
            background: #065f461a;
            border: 1px solid #00d3a7;
            color: #9ef8e5
        }

        .PUT {
            background: #4f46e51a;
            border: 1px solid #6366f1;
            color: #c7d2fe
        }

        .DELETE {
            background: #7f1d1d1a;
            border: 1px solid #ef4444;
            color: #fecaca
        }

        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            overflow: hidden;
            border-radius: 14px;
            border: 1px solid var(--border)
        }

        th,
        td {
            padding: 10px 12px;
            text-align: right
        }

        thead th {
            position: sticky;
            top: 0;
            background: var(--panel-2);
            color: #cfe1f4;
            font-weight: 700;
            border-bottom: 1px solid var(--border)
        }

        tbody tr:nth-child(even) {
            background: rgba(255, 255, 255, .02)
        }

        tbody td {
            border-bottom: 1px dashed rgba(255, 255, 255, .05)
        }

        .req {
            color: var(--ok);
            font-weight: 700
        }

        .opt {
            color: var(--muted)
        }

        .callout {
            border: 1px solid var(--border);
            background: linear-gradient(180deg, rgba(34, 197, 94, .08), rgba(34, 197, 94, .04));
            padding: 12px 14px;
            border-radius: 14px
        }

        .callout.warn {
            background: linear-gradient(180deg, rgba(245, 158, 11, .12), rgba(245, 158, 11, .06));
        }

        .callout.err {
            background: linear-gradient(180deg, rgba(239, 68, 68, .10), rgba(239, 68, 68, .05));
        }

        .footer {
            margin-top: 28px;
            color: var(--muted);
            font-size: 12px;
            display: flex;
            justify-content: space-between;
            gap: 10px;
            flex-wrap: wrap
        }

        .section-title {
            margin: 12px 0 8px 0;
            color: #cfe1f4
        }

        .kbd {
            background: #111826;
            border: 1px solid var(--border);
            padding: 2px 6px;
            border-radius: 6px
        }
    </style>
</head>

<body>
    <div class="container">
        <header>
            <div class="logo" aria-hidden="true">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M4 17l6-10 3 5 3-5 4 7" stroke="#e6fff8" stroke-width="2" stroke-linecap="round"
                        stroke-linejoin="round" />
                </svg>
            </div>
            <div>
                <h1>توثيق API — إنشاء معدّة (Equipment)</h1>
                <div class="subtitle">Workbench ERP · HR/Maintenance · نقطة نهاية لإنشاء سجل معدّة جديد مع توليد <kbd
                        class="kbd">asset_tag</kbd> تلقائيًا إذا لزم.</div>
            </div>
        </header>

        <div class="grid">
            <!-- LEFT -->
            <section class="card">
                <h2>نظرة عامة</h2>
                <p>تُستخدم هذه الواجهة لإنشاء معدّة جديدة داخل نظام الصيانة. عند عدم تمرير <code>asset_tag</code> يقوم
                    النظام تلقائيًا باستدعاء <code>AssetTagGenerator</code> لتوليده بناءً على <code>type_id</code>.
                    تُسجَّل عملية الإنشاء في سجل نشاط المعدّات <code>EquipmentLog</code> تلقائيًا.</p>
                <div class="row" style="margin-top:10px">
                    <span class="badge"><span class="method POST">POST</span> /api/v1/equipments</span>
                    <span class="chip">Auth: Bearer Token (auth:api)</span>
                    <span class="chip">Consumes: <code>application/json</code></span>
                    <span class="chip">Produces: <code>application/json</code></span>
                </div>

                <h3 class="section-title">مثال طلب (cURL)</h3>
                <pre><code>curl -X POST "https://&lt;your-host&gt;/api/v1/equipments" \ 
  -H "Authorization: Bearer &lt;TOKEN&gt;" \
  -H "Content-Type: application/json" \
  -d '{
    "asset_tag": "CMP-HP-0001",
    "name": "Laptop HP ProBook",
    "make": "HP",
    "model": "ProBook 450 G7",
    "serial_number": "SN-12345689",
    "status": "Active",
    "type_id": 1,
    "branch_id": 7,
    "branch_area_id": 1,
    "purchase_price": 1200.50,
    "purchase_date": "2025-01-10",
    "warranty_years": 2,
    "warranty_months": 0,
    "warranty_end_date": "2027-01-10",
    "periodic_service": true,
    "service_interval_days": 90,
    "last_serviced": "2025-01-15",
    "next_service_date": "2025-04-15"
  }'
</code></pre>

                <h3 class="section-title">الاستجابات المتوقعة</h3>
                <div class="callout"><strong>201 Created</strong> — تم إنشاء المعدّة بنجاح.</div>
                <pre><code>{
  "success": true,
  "message": "Equipment created successfully",
  "data": {
    "id": 123,
    "asset_tag": "CMP-HP-0001",
    "name": "Laptop HP ProBook",
    "make": "HP",
    "model": "ProBook 450 G7",
    "serial_number": "SN-12345689",
    "status": "Active",
    "type": { "id": 1, "name": "Computer", "category": { "id": 9, "name": "IT" } },
    "branch": { "id": 7, "name": "Head Office" },
    "purchase_price": 1200.50,
    "purchase_date": "2025-01-10",
    "warranty_years": 2,
    "warranty_months": 0,
    "warranty_end_date": "2027-01-10",
    "periodic_service": true,
    "service_interval_days": 90,
    "last_serviced": "2025-01-15",
    "next_service_date": "2025-04-15",
    "created_at": "2025-09-29T10:21:00Z"
  }
}
</code></pre>

                <div class="callout warn"><strong>422 Unprocessable Entity</strong> — فشل التحقق (Validation).</div>
                <pre><code>{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "name": ["The name field is required."],
    "type_id": ["The selected type id is invalid."]
  }
}
</code></pre>

                <div class="callout err"><strong>500 Server Error</strong> — فشل العملية.</div>
                <pre><code>{
  "success": false,
  "message": "Operation failed",
  "error": "&lt;رسالة الخطأ عند تفعيل وضع debug فقط&gt;"
}
</code></pre>
            </section>

            <!-- RIGHT -->
            <aside class="card">
                <h2>تفاصيل نقطة النهاية</h2>
                <div class="row">
                    <span class="badge"><span class="method POST">POST</span> /api/v1/equipments</span>
                </div>
                <ul>
                    <li>يتطلب توثيق عبر <code>auth:api</code> (Bearer Token).</li>
                    <li>في حال ترك <code>asset_tag</code> فارغًا، يتم توليده بواسطة
                        <code>AssetTagGenerator::generate(type_id)</code>.</li>
                    <li>تتم العملية داخل <code>DB::transaction</code> لضمان الذرّية.</li>
                    <li>يتم تسجيل حدث الإنشاء في <code>EquipmentLog</code> تلقائيًا.</li>
                </ul>

                <h3 class="section-title">ترويسات (Headers)</h3>
                <table>
                    <thead>
                        <tr>
                            <th>الاسم</th>
                            <th>القيمة</th>
                            <th>مطلوب؟</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Authorization</td>
                            <td><code>Bearer &lt;TOKEN&gt;</code></td>
                            <td class="req">نعم</td>
                        </tr>
                        <tr>
                            <td>Content-Type</td>
                            <td><code>application/json</code></td>
                            <td class="req">نعم</td>
                        </tr>
                        <tr>
                            <td>Accept</td>
                            <td><code>application/json</code></td>
                            <td class="opt">اختياري</td>
                        </tr>
                    </tbody>
                </table>

                <h3 class="section-title">حالات الحالة (Status Codes)</h3>
                <table>
                    <thead>
                        <tr>
                            <th>الكود</th>
                            <th>الوصف</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>201</td>
                            <td>تم الإنشاء بنجاح</td>
                        </tr>
                        <tr>
                            <td>422</td>
                            <td>خطأ تحقق من المدخلات</td>
                        </tr>
                        <tr>
                            <td>500</td>
                            <td>فشل غير متوقع أثناء التنفيذ</td>
                        </tr>
                    </tbody>
                </table>

                <h3 class="section-title">حالات <span class="chip">status</span></h3>
                <div class="row">
                    <span class="chip">Active</span>
                    <span class="chip">Under Maintenance</span>
                    <span class="chip">Retired</span>
                </div>
            </aside>
        </div>

        <section class="card" style="margin-top:20px">
            <h2>حقول الطلب (Request Body)</h2>
            <table>
                <thead>
                    <tr>
                        <th>الحقل</th>
                        <th>النوع</th>
                        <th>مطلوب؟</th>
                        <th>التحقق (Rules)</th>
                        <th>ملاحظات</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>asset_tag</td>
                        <td>string</td>
                        <td class="opt">اختياري</td>
                        <td>—</td>
                        <td>يُولد تلقائيًا إذا تُرك فارغًا.</td>
                    </tr>
                    <tr>
                        <td>name</td>
                        <td>string</td>
                        <td class="req">مطلوب</td>
                        <td>required · max:255</td>
                        <td></td>
                    </tr>
                    <tr>
                        <td>type_id</td>
                        <td>integer</td>
                        <td class="req">مطلوب</td>
                        <td>exists:hr_equipment_types,id</td>
                        <td>يعتمد مولّد <code>asset_tag</code> عليه.</td>
                    </tr>
                    <tr>
                        <td>status</td>
                        <td>enum</td>
                        <td class="req">مطلوب</td>
                        <td>in: Active, Under Maintenance, Retired</td>
                        <td></td>
                    </tr>
                    <tr>
                        <td>branch_id</td>
                        <td>integer</td>
                        <td class="opt">اختياري</td>
                        <td>integer</td>
                        <td></td>
                    </tr>
                    <tr>
                        <td>branch_area_id</td>
                        <td>integer</td>
                        <td class="opt">اختياري</td>
                        <td>exists:branch_areas,id</td>
                        <td></td>
                    </tr>
                    <tr>
                        <td>serial_number</td>
                        <td>string</td>
                        <td class="opt">اختياري</td>
                        <td>max:255 · unique:hr_equipment,serial_number</td>
                        <td>يُفضَّل تمريره لتفادي التكرار.</td>
                    </tr>
                    <tr>
                        <td>service_interval_days</td>
                        <td>integer</td>
                        <td class="opt">اختياري</td>
                        <td>integer · min:0</td>
                        <td>يعمل مع <code>periodic_service</code>.</td>
                    </tr>
                    <tr>
                        <td>last_serviced</td>
                        <td>date</td>
                        <td class="opt">اختياري</td>
                        <td>date</td>
                        <td></td>
                    </tr>
                    <tr>
                        <td>next_service_date</td>
                        <td>date</td>
                        <td class="opt">اختياري</td>
                        <td>date</td>
                        <td></td>
                    </tr>
                    <tr>
                        <td>warranty_years</td>
                        <td>integer</td>
                        <td class="req">مطلوب</td>
                        <td>integer · min:1</td>
                        <td></td>
                    </tr>
                    <tr>
                        <td>warranty_months</td>
                        <td>integer</td>
                        <td class="opt">اختياري</td>
                        <td>—</td>
                        <td></td>
                    </tr>
                    <tr>
                        <td>purchase_price</td>
                        <td>decimal</td>
                        <td class="opt">اختياري</td>
                        <td>—</td>
                        <td></td>
                    </tr>
                    <tr>
                        <td>purchase_date</td>
                        <td>date</td>
                        <td class="opt">اختياري</td>
                        <td>—</td>
                        <td></td>
                    </tr>
                    <tr>
                        <td>periodic_service</td>
                        <td>boolean</td>
                        <td class="opt">اختياري</td>
                        <td>—</td>
                        <td></td>
                    </tr>
                    <tr>
                        <td>warranty_end_date</td>
                        <td>date</td>
                        <td class="opt">اختياري</td>
                        <td>—</td>
                        <td>يمكن حسابه من السنوات/الأشهر.</td>
                    </tr>
                </tbody>
            </table>

            <div class="callout warn" style="margin-top:12px"><strong>ملاحظة:</strong> قد تحتوي
                <code>EquipmentResource</code> على بنية عرض تختلف حسب مواردك (مثل إخفاء/إظهار حقول أو علاقات). الأمثلة
                هنا إرشادية.</div>
        </section>

        <section class="card" style="margin-top:20px">
            <h2>السلوك الداخلي (Server Logic)</h2>
            <ol>
                <li>التحقق من الطلب عبر <code>StoreEquipmentRequest</code>.</li>
                <li>إن كان <code>asset_tag</code> فارغًا ⇒ استدعاء <code>AssetTagGenerator::generate(type_id)</code>.
                </li>
                <li>تنفيذ الإنشاء داخل <code>DB::transaction</code> ثم <code>load(['type.category','branch'])</code>.
                </li>
                <li>إرجاع استجابة <strong>201</strong> مع <code>{ success, message, data }</code>.</li>
                <li>في أخطاء التحقق ⇒ <strong>422</strong> مع <code>{ success:false, message:"Validation failed", errors
                        }</code>.</li>
                <li>في أي استثناء آخر ⇒ <strong>500</strong> مع رسالة عامة، وتفاصيل الخطأ فقط عند تفعيل <span
                        class="chip">debug</span>.</li>
            </ol>

            <h3 class="section-title">أحداث المودل (Model Events)</h3>
            <ul>
                <li><strong>creating</strong>: يضبط <code>created_by</code> و<code>qr_code</code> تلقائيًا.</li>
                <li><strong>created</strong>: يسجل <code>EquipmentLog::ACTION_CREATED</code>.</li>
                <li><strong>updated</strong>: يسجل <code>ACTION_UPDATED</code>.</li>
            </ul>
        </section>

        <section class="card" style="margin-top:20px">
            <h2>تكامل سريع (Laravel Guzzle مثال)</h2>
            <pre><code>use Illuminate\Support\Facades\Http;

$response = Http::withToken($token)
  -&gt;acceptJson()
  -&gt;post(url('/api/v1/equipments'), [
      'name' =&gt; 'Laptop HP ProBook',
      'status' =&gt; 'Active',
      'type_id' =&gt; 1,
      'warranty_years' =&gt; 2,
  ]);

if ($response-&gt;created()) {
    $payload = $response-&gt;json();
}
</code></pre>

            <h3 class="section-title">أخطاء شائعة</h3>
            <ul>
                <li><code>serial_number</code> مكرر ⇒ تحقق من فهرس <em>unique:hr_equipment,serial_number</em>.</li>
                <li><code>type_id</code> أو <code>branch_area_id</code> غير موجود ⇒ قاعدة <code>exists</code>.</li>
                <li>نسيان <code>warranty_years</code> (مطلوب) ⇒ 422.</li>
            </ul>
        </section>

        <div class="footer">
            <div>آخر تحديث: <time>2025-09-29</time></div>
            <div>Workbench ERP · HR/Maintenance</div>
        </div>
    </div>
</body>

</html>
