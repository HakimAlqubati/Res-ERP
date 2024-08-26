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
        <div style="width: 25%;float: left;text-align: left">
            <p>{{ __('lang.general_report_of_products') }}</p>
            <p>({{ $branch }})
            </p>
        </div>

        <div style="width: 20%;float: center">
            <p>{{ __('lang.start_date') . ': ' . $start_date }}</p>
            <p>{{ __('lang.end_date') . ': ' . $end_date }}</p>
        </div>
        <div style="width: 20%;float: center">
            <p>{{ __('lang.category') . ': (' . $category . ')' }}</p>
        </div>

        <div style="width: 20%;float: right">

        </div>
    </div>

    <table class="tpretty">
        <thead>

            <tr>
                <th>{{ __('lang.product') }}</th>
                <th>{{ __('lang.unit') }}</th>
                <th>{{ __('lang.quantity') }}</th>
                <th>{{ __('lang.price') }}</th>
            </tr>
        </thead>
        <tbody>

            @foreach ($report_data as $data)
                <tr>
                    <td> {{ $data?->product_name }} </td>
                    <td> {{ $data?->unit_name }} </td>
                    <td> {{ $data?->quantity }} </td>
                    <td> {{ $data?->price }} </td>
                </tr>
            @endforeach
            <tr>
                <td colspan="2"> {{ __('lang.total') }} </td>
                <td> {{ $total_quantity }} </td>
                <td> {{ $total_price }} </td>
            </tr>
        </tbody>

    </table>


</body>

</html>
