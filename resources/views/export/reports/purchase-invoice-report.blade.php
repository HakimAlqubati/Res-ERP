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
        <div style="width: 33%;float: center">
        </div>

        <div style="width: 33%;float: center">
            <p>{{ __('lang.purchase_invoice_report') }}</p>
        </div>

        <div style="width: 33%;float: right">
        </div>
    </div>

    <table class="tpretty">
        <thead>

            <tr>
                <th colspan="3">
                    {{ __('lang.store') }}: ({{ $purchase_invoice_data['store_name'] }})
                </th>
                <th colspan="{{ $show_invoice_no == true ? '4' : '3' }}">
                    {{ __('lang.supplier') }}: ({{ $purchase_invoice_data['supplier_name'] }})
                </th>
            </tr>

            <tr>
                <th>{{ __('lang.product_id') }} </th>
                <th>{{ __('lang.product') }}</th>
                <th>{{ __('lang.unit') }}</th>
                <th>{{ __('lang.quantity') }}</th>
                @if ($show_invoice_no == true)
                    <th>{{ __('lang.invoice_no') }}</th>
                @endif
                <th>{{ __('lang.unit_price') }}</th>
                <th>{{ __('lang.total_amount') }}</th>
            </tr>
            
        </thead>
        <tbody>
            @php
                $total_sub_total = 0;
                $sum_unit_price = 0;
            @endphp
            @foreach ($purchase_invoice_data['results'] as $key => $invoice_item)
                @php
                    $unit_price = $invoice_item?->unit_price;
                    $sub_total = $invoice_item?->unit_price * $invoice_item?->quantity;

                    // Add the sub_total to the totalSubTotal variable
                    $total_sub_total += $sub_total;

                    // Add the unit_price to the sumUnitPrice variable
                    $sum_unit_price += $unit_price;
                @endphp
                <tr>
                    <td> {{ $invoice_item?->product_id }} </td>
                    <td> {{ $invoice_item?->product_name }} </td>
                    <td> {{ $invoice_item?->unit_name }} </td>
                    <td> {{ $invoice_item?->quantity }} </td>
                    @if ($show_invoice_no == true)
                        <td>
                            {{ '(' . $invoice_item->purchase_invoice_id . ') ' . $invoice_item->invoice_no }}
                        </td>
                    @endif
                    <td> {{ $unit_price }} </td>
                    <td> {{ $sub_total }} </td>
                </tr>
            @endforeach

            <tr>
                <td colspan="{{ $show_invoice_no == true ? '5' : '4' }}"> {{ __('lang.total') }}
                </td>
                <td> {{ $sum_unit_price }} </td>
                <td> {{ $total_sub_total }} </td>
            </tr>
        </tbody>

    </table>


</body>

</html>
