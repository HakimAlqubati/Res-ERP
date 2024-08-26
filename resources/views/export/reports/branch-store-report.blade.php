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

    <div>
        <div style="width: 33%;float: left;text-align: left">
            <p>{{ __('lang.branch_store_report') }}</p>
            <p>({{ isset($branch_id) && is_numeric($branch_id) ? \App\Models\Branch::find($branch_id)->name : __('lang.choose_branch') }})
            </p>
        </div>

        <div style="width: 34%;float: center">
            <p>{{ __('lang.start_date') . ': ' . $start_date }}</p>
            <br>
            <p>{{ __('lang.end_date') . ': ' . $end_date }}</p>
        </div>

        <div style="width: 33%;float: right">

        </div>
    </div>

    <table class="tpretty">
        <thead>

            <tr>
                <th>{{ __('lang.product_id') }} </th>
                <th>{{ __('lang.product') }}</th>
                <th>{{ __('lang.unit') }}</th>
                <th> {{ __('lang.qty_in_stock') }}</th>
            </tr>
        </thead>
        <tbody>

            @foreach ($branch_store_report_data as $key => $report_item)
                <tr>
                    <td> {{ $report_item?->product_id }} </td>
                    <td> {{ $report_item?->product_name }} </td>
                    <td> {{ $report_item?->unit_name }} </td>
                    <td> {{ $report_item?->total_quantity }} </td>
                </tr>
            @endforeach
            <tr>
                <td colspan="3">{{ __('lang.total_quantity') }}</td>
                <td>{{ $total_quantity }}</td>
            </tr>
        </tbody>

    </table>


</body>

</html>
