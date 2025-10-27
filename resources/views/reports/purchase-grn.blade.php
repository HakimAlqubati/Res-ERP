{{-- resources/views/reports/purchase-grn.blade.php --}}
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <title>تقرير الربط بين فواتير الشراء و GRN</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    {{-- إن كنت تستخدم Tailwind في المشروع سيُطبق تلقائياً، وإلا التصميم بسيط --}}
    <style>
        :root { --fg:#111827; --muted:#6B7280; --soft:#F9FAFB; --border:#E5E7EB; --accent:#2563EB; }
        * { box-sizing:border-box; }
        body { margin:0; font-family: ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, Arial; color:var(--fg); background:#fff; }
        .container { max-width:1000px; margin:24px auto; padding:0 16px; }
        .grid { display:grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap:16px; }
        .card { background:var(--soft); border:1px solid var(--border); border-radius:16px; padding:16px; }
        .card h3 { margin:0 0 8px; font-size:18px; }
        .kpis { display:grid; grid-template-columns: repeat(2, 1fr); gap:12px; margin-top:10px; }
        .kpi { background:#fff; border:1px solid var(--border); border-radius:12px; padding:12px; text-align:center; }
        .kpi .label { color:var(--muted); font-size:12px; }
        .kpi .value { font-size:20px; font-weight:700; margin-top:4px; }
        .header { display:flex; align-items:center; justify-content:space-between; margin-bottom:16px; }
        .title { font-size:22px; font-weight:700; }
        .note { color:var(--muted); font-size:12px; }
        .pct { color:var(--accent); font-weight:700; }
        table { width:100%; border-collapse:collapse; margin-top:16px; background:#fff; border-radius:12px; overflow:hidden; }
        th, td { padding:12px; border-bottom:1px solid var(--border); text-align:center; }
        th { background:#f3f4f6; font-weight:600; }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <div class="title">تقرير الربط بين فواتير الشراء و إشعارات الاستلام (GRN)</div>
        <div class="note">يعتمد على البيانات الحالية بدون فلاتر</div>
    </div>

    <div class="grid">
        <div class="card">
            <h3>الفواتير (Purchase Invoices)</h3>
            <div class="kpis">
                <div class="kpi">
                    <div class="label">إجمالي الفواتير</div>
                    <div class="value">{{ number_format($invoices['total']) }}</div>
                </div>
                <div class="kpi">
                    <div class="label">مرتبطة بـ GRN</div>
                    <div class="value">{{ number_format($invoices['linked']) }}</div>
                </div>
                <div class="kpi">
                    <div class="label">غير مرتبطة</div>
                    <div class="value">{{ number_format($invoices['unlinked']) }}</div>
                </div>
                <div class="kpi">
                    <div class="label">نسبة الارتباط</div>
                    <div class="value pct">{{ number_format($invoices['pct_linked'], 2) }}%</div>
                </div>
            </div>
        </div>

        <div class="card">
            <h3>إشعارات الاستلام (GRN)</h3>
            <div class="kpis">
                <div class="kpi">
                    <div class="label">إجمالي GRN</div>
                    <div class="value">{{ number_format($grn['total']) }}</div>
                </div>
                <div class="kpi">
                    <div class="label">مرتبطة بفاتورة</div>
                    <div class="value">{{ number_format($grn['linked']) }}</div>
                </div>
                <div class="kpi">
                    <div class="label">غير مرتبطة</div>
                    <div class="value">{{ number_format($grn['unlinked']) }}</div>
                </div>
                <div class="kpi">
                    <div class="label">نسبة الارتباط</div>
                    <div class="value pct">{{ number_format($grn['pct_linked'], 2) }}%</div>
                </div>
            </div>
        </div>
    </div>

    {{-- جدول مختصر موحّد (اختياري) --}}
    <table>
        <thead>
        <tr>
            <th>النوع</th>
            <th>الإجمالي</th>
            <th>مرتبطة</th>
            <th>غير مرتبطة</th>
            <th>نسبة الارتباط</th>
        </tr>
        </thead>
        <tbody>
        <tr>
            <td>الفواتير</td>
            <td>{{ number_format($invoices['total']) }}</td>
            <td>{{ number_format($invoices['linked']) }}</td>
            <td>{{ number_format($invoices['unlinked']) }}</td>
            <td>{{ number_format($invoices['pct_linked'], 2) }}%</td>
        </tr>
        <tr>
            <td>GRN</td>
            <td>{{ number_format($grn['total']) }}</td>
            <td>{{ number_format($grn['linked']) }}</td>
            <td>{{ number_format($grn['unlinked']) }}</td>
            <td>{{ number_format($grn['pct_linked'], 2) }}%</td>
        </tr>
        </tbody>
    </table>
</div>
</body>
</html>
