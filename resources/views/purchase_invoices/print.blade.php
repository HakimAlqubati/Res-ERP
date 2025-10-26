{{-- resources/views/invoices/purchase/print.blade.php --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $meta['title'] }} | {{ $meta['app_name'] }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        /* ====== Theme ====== */
        :root{
            --primary:#0d7c66;
            --primary-ink:#064e42;
            --bg:#ffffff;
            --fg:#0f172a;
            --muted:#6b7280;
            --soft:#f6faf8;
            --soft-2:#f0f5f3;
            --border:#e6eeeb;
            --danger:#be123c;
            --ink:#0f172a;
            --radius-lg:16px;
            --radius:12px;
            --shadow:0 1px 2px rgba(0,0,0,.06),0 8px 24px rgba(13,124,102,.06);
            --shadow-strong:0 10px 30px rgba(13,124,102,.12);
        }
        *{box-sizing:border-box}
        html,body{margin:0;padding:0;background:var(--bg);color:var(--fg);font:14px/1.6 ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, "Helvetica Neue", Arial}
        .container{max-width:980px;margin:24px auto;padding:28px;background:#fff;border:1px solid var(--border);border-radius:var(--radius-lg);box-shadow:var(--shadow)}
        .header{display:grid;grid-template-columns:1.2fr .8fr;gap:24px;align-items:flex-start}
        .brand{display:flex;align-items:center;gap:12px}
        .brand-mark{width:42px;height:42px;border-radius:10px;background:linear-gradient(135deg,var(--primary),#12a388);box-shadow:var(--shadow-strong)}
        .brand-name{font-weight:800;font-size:22px;letter-spacing:.2px;color:var(--primary-ink)}
        .title{margin-top:6px;font-weight:900;font-size:28px;letter-spacing:.3px}
        .badge{display:inline-flex;align-items:center;gap:6px;padding:6px 10px;border-radius:999px;font-size:12px;border:1px solid var(--border);background:var(--soft);color:var(--primary-ink)}
        .badge .dot{width:7px;height:7px;border-radius:999px;background:var(--primary)}
        .muted{color:var(--muted)}
        .meta-card{border:1px solid var(--border);border-radius:var(--radius);padding:14px 16px;background:var(--soft);box-shadow:var(--shadow)}
        .meta-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px}
        .kv{display:grid;grid-template-columns:130px 1fr;gap:8px}
        .kv .k{color:var(--muted)}
        .kv .v{font-weight:700;color:var(--ink)}
        .section{margin-top:22px;border:1px solid var(--border);border-radius:var(--radius);overflow:hidden}
        .section .head{display:flex;justify-content:space-between;align-items:center;padding:14px 18px;background:linear-gradient(180deg,var(--soft),var(--soft-2));border-bottom:1px solid var(--border);font-weight:800;color:var(--primary-ink)}
        .head .pill{padding:4px 10px;border-radius:999px;border:1px solid var(--border);background:#fff;color:var(--muted);font-size:12px}
        table{width:100%;border-collapse:separate;border-spacing:0}
        thead th{position:sticky;top:0;background:linear-gradient(180deg,var(--soft),#fff);text-align:left;font-size:12px;letter-spacing:.3px;color:var(--primary-ink);border-bottom:1px solid var(--border);padding:10px 12px}
        tbody td{border-bottom:1px solid var(--border);padding:12px}
        tbody tr:nth-child(even){background:rgba(13,124,102,.03)}
        tbody tr:hover{background:rgba(13,124,102,.06)}
        tfoot td{padding:10px 12px}
        .right{text-align:right}
        .notes-wrap{display:grid;grid-template-columns:1fr 320px;gap:16px;padding:16px}
        .note{border:1px dashed var(--border);border-radius:12px;padding:12px 14px;background:#fff}
        .note h4{margin:0 0 6px 0;font-size:13px;letter-spacing:.2px;color:var(--primary-ink)}
        .totals{border:1px solid var(--border);border-radius:12px;overflow:hidden;background:#fff;box-shadow:var(--shadow)}
        .totals-row{display:grid;grid-template-columns:1fr auto;gap:12px;padding:12px 14px;border-bottom:1px solid var(--border)}
        .totals-row:last-child{border-bottom:0;background:linear-gradient(180deg,var(--soft),var(--soft-2));font-weight:900}
        .label{color:var(--muted)}
        .value{font-weight:800}
        .footer{margin-top:22px;padding-top:14px;border-top:1px solid var(--border);display:flex;justify-content:space-between;color:var(--muted);font-size:12px}
        .status-ribbon{position:relative}
        .status-ribbon::after{
            content:"{{ $meta['cancelled'] ? 'CANCELLED' : '' }}";
            position:absolute;right:-46px;top:24px;
            transform:rotate(35deg);
            padding:6px 60px;border:1px solid rgba(190,18,60,.2);
            color:#fff;background:linear-gradient(135deg,#ef476f, #d61f45);
            font-weight:900;letter-spacing:2px;font-size:12px;opacity:{{ $meta['cancelled'] ? '0.18':'0' }};
        }
        .chip{display:inline-flex;align-items:center;gap:8px;border:1px solid var(--border);background:#fff;border-radius:999px;padding:6px 10px}
        .chip .dot{width:7px;height:7px;border-radius:999px;background:var(--primary)}
        .tag{display:inline-flex;align-items:center;padding:2px 8px;border-radius:999px;border:1px solid var(--border);background:#fff;color:var(--muted);font-size:12px}
        .strong{font-weight:800}
        .w-50{width:50px}
        /* Print */
        @media print{
            .container{box-shadow:none;border:none;margin:0;max-width:100%;padding:16mm}
            .badge,.chip,.tag{print-color-adjust:exact;-webkit-print-color-adjust:exact}
            thead th{background:var(--soft)!important}
            .status-ribbon::after{opacity:{{ $meta['cancelled'] ? '0.25':'0' }}}
            a[href]:after{content:""}
        }
        /* Small screens */
        @media (max-width:720px){
            .header{grid-template-columns:1fr}
            .meta-grid{grid-template-columns:1fr}
            .notes-wrap{grid-template-columns:1fr}
        }
    </style>
</head>
<body>
<div class="container status-ribbon">
    <div class="header">
        <div>
            <div class="brand">
                <div class="brand-mark"></div>
                <div class="brand-name">{{ $meta['app_name'] }}</div>
            </div>
            <div class="title">{{ $meta['title'] }}</div>
            <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:8px">
                <span class="badge"><span class="dot"></span> No. {{ $meta['invoice_no'] }}</span>
                <span class="badge"><span class="dot"></span> Date: {{ $meta['date'] }}</span>
                @if($meta['has_grn'])
                    <span class="badge"><span class="dot"></span> GRN linked</span>
                @endif
                @if($meta['has_attachment'])
                    <span class="badge"><span class="dot"></span> Attachment</span>
                @endif
                @if($meta['cancelled'])
                    <span class="badge" style="border-color:#fecaca;background:#ffe4e6;color:#9f1239">Cancelled</span>
                @endif
            </div>
            <div class="muted" style="margin-top:8px">Generated at {{ now()->format('Y-m-d H:i') }}</div>
        </div>
        <div class="meta-card">
            <div class="meta-grid">
                <div class="kv">
                    <div class="k">Prepared by</div>
                    <div class="v">{{ $meta['created_by'] ?: 'N/A' }}</div>
                </div>
                <div class="kv">
                    <div class="k">Store</div>
                    <div class="v">{{ $meta['store'] ?: 'N/A' }}</div>
                </div>
                <div class="kv">
                    <div class="k">Supplier</div>
                    <div class="v">{{ $meta['supplier'] ?: 'N/A' }}</div>
                </div>
                <div class="kv">
                    <div class="k">Items</div>
                    <div class="v">{{ $meta['items_count'] }}</div>
                </div>
                @if($meta['cancelled'])
                    <div class="kv" style="grid-column:1 / -1">
                        <div class="k">Cancel reason</div>
                        <div class="v" style="color:var(--danger)">{{ $meta['cancel_reason'] ?: 'N/A' }}</div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="section" style="margin-top:24px">
        <div class="head">
            <span>Items</span>
            <span class="pill">Total rows: {{ $meta['items_count'] }}</span>
        </div>
        <div style="overflow:hidden">
            <table>
                <thead>
                    <tr>
                        <th class="w-50">#</th>
                        <th>Product</th>
                        <th>Unit</th>
                        <th class="right">Qty</th>
                        <th class="right">Price</th>
                        <th class="right">Package</th>
                        <th class="right">Waste %</th>
                        <th class="right">Line total</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($items as $i => $row)
                    <tr>
                        <td class="w-50">{{ $i + 1 }}</td>
                        <td class="strong">{{ $row['product'] }}</td>
                        <td>{{ $row['unit'] }}</td>
                        <td class="right">{{ number_format($row['quantity'], 2) }}</td>
                        <td class="right">{{ number_format($row['price'], 2) }}</td>
                        <td class="right">{{ $row['package_size'] ?? '-' }}</td>
                        <td class="right">{{ $row['waste_pct'] !== null ? number_format($row['waste_pct'], 2) : '-' }}</td>
                        <td class="right strong">{{ number_format($row['line_total'], 2) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="muted" style="text-align:center;padding:18px">No items found.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <div class="notes-wrap">
            <div class="note">
                <h4>Notes</h4>
                @if($invoice->description)
                    <div>{{ $invoice->description }}</div>
                @else
                    <div class="muted">No notes provided.</div>
                @endif
            </div>
            <div class="totals">
                <div class="totals-row">
                    <div class="label">Subtotal</div>
                    <div class="value right">{{ number_format($meta['total'], 2) }}</div>
                </div>
                <div class="totals-row" style="background:linear-gradient(180deg,#e9f7f2,#dff2ec);border-color:#cfe8df">
                    <div class="label" style="color:var(--primary-ink)">Total</div>
                    <div class="value right" style="color:var(--primary-ink)">{{ number_format($meta['total'], 2) }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="footer">
        <div>Printed by {{ $meta['app_name'] }}</div>
        <div>{{ now()->format('Y-m-d') }}</div>
    </div>
</div>
</body>
</html>
