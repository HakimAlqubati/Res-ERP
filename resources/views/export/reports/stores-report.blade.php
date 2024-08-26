<!DOCTYPE html>

<head>
    <title> </title>

    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
</head>
<style>
    * {
        font-family: 'examplefont', sans-serif !important;
    }

    body {
        font-family: 'examplefont', sans-serif !important;
        background-color: #ffffff;
        /* direction: rtl !important; */
        direction: {{ App::getLocale() === 'ar' ? 'rtl' : 'ltr' }} !important;
        width: 22cm;
        /* width: 100%; */
        text-align: justify;
        text-rendering: geometricPrecision;
    }

    body p {
        /*margin: 0;*/
    }


    pre {
        font-family: 'examplefont', sans-serif !important;
    }

    .pad {
        /*padding-top: 10px;*/
        /*padding-bottom: 10px;*/
        text-align: center
    }

    .pad2 {
        padding-top: 20px;
        padding-bottom: 20px;
        text-align: center
    }

    .page-break {
        page-break-after: always;
    }

    table.tpretty {
        width: 100%;
    }

    table.tpretty tbody tr td {
        text-align: center;
    }

    table.tpretty tfoot tr th {
        border-bottom: 2px solid;
        border-top: 2px solid;
        font-weight: bold;
        text-align: center;
    }

    table.tpretty th,
    table.tpretty td {
        border: 1px solid gainsboro;
        padding: 0.2em;
    }

    table.tpretty thead tr th.empty {
        border: 0 none;
    }

    .table:not(.table-dark) {
        color: inherit;
    }

    .table-bordered {
        border: 1px solid #dee2e6;
    }

    .table {
        width: 100%;
        margin-bottom: 1rem;
        color: #212529;
        background-color: transparent;
    }
</style>

<body>

    <div style="width: 100%">
        <div style="width: 33%;float: left;text-align: left">
            <p>{{ __('lang.store') }}:
                ({{ isset($store_id) && is_numeric($store_id) ? \App\Models\Store::find($store_id)->name : __('lang.all_stores') }})
            </p>
            <p>{{ __('lang.supplier') }}:
                ({{ isset($supplier_id) && is_numeric($supplier_id) ? \App\Models\User::find($supplier_id)->name : __('lang.all_suppliers') }})
            </p>
        </div>

        <div style="width: 34%;float: center">
            <h3>({{ __('lang.stores_report') }})</h3>
        </div>

        <div style="width: 33%;float: center">

        </div>

    </div>

    <table class="tpretty">
        <thead>

            <tr>
                <th>{{ __('lang.product_id') }} </th>
                <th>{{ __('lang.product') }}</th>
                <th>{{ __('lang.unit') }}</th>
                <th>{{ __('lang.purchased_qty') }}</th>
                <th>{{ __('lang.qty_sent_to_branches') }}</th>
                <th>{{ __('lang.qty_in_stock') }}</th>
            </tr>
        </thead>
        <tbody>

            @php
                $total_income = 0;
                $total_ordered = 0;
                $total_remaining = 0;
            @endphp
            @foreach ($stores_report_data as $key => $report_item)
                @php
                    $total_income += $report_item?->income;
                    $total_ordered += $report_item?->ordered;
                    $total_remaining += $report_item?->remaining;
                @endphp
                <tr>
                    <td> {{ $report_item?->product_id }} </td>
                    <td> {{ $report_item?->product_name }} </td>
                    <td> {{ $report_item?->unit_name }} </td>
                    <td> {{ $report_item?->income }} </td>
                    <td> {{ $report_item?->ordered }} </td>
                    <td> {{ $report_item?->remaining }} </td>
                </tr>
            @endforeach
            <tr>
                <td colspan="3"> {{ __('lang.total') }} </td>
                <td> {{ $total_income }} </td>
                <td> {{ $total_ordered }} </td>
                <td> {{ $total_remaining }} </td>
            </tr>
        </tbody>

    </table>


</body>

</html>
