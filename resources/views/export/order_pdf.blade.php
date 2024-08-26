<!DOCTYPE html>

<head>
    <title>{{ $order->id }}</title>

    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
</head>
<style>
    * {
        font-family: 'examplefont', sans-serif !important;
    }

    body {
        font-family: 'examplefont', sans-serif !important;
        background-color: #ffffff;
        direction: rtl !important;
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
            {{-- <img src="https://w7.pngwing.com/pngs/882/726/png-transparent-chef-cartoon-chef-photography-cooking-fictional-character-thumbnail.png"
                style="width: 8rem;height: 8rem;"> --}}
        </div>

        <div style="width: 34%;float: center">
            <p class="mt-5"> {{ __('lang.branch') . ' ' . $order->branch->name }} </p>
            {{-- <p class="mt-5"> {{ __('lang.branch_manager') . ' ' . $order->customer->name }} </p> --}}
            <p class="mt-5"> {{ __('lang.order-no-') . $order->id }}# </p>
        </div>

        <div style="width: 33%;float: right">
        </div>
    </div>
    <hr>
    <table class="tpretty">
        <thead>

            <tr>
                <th> {{ __('lang.product_id') }} </th>
                <th> {{ __('lang.product') }} </th>
                <th> {{ __('lang.unit') }} </th>
                <th> {{ __('lang.quantity') }} </th>
                {{-- <th> {{ __('lang.unit_price') }} </th> --}}
                {{-- <th> {{ __('lang.total_price') }} </th> --}}
            </tr>

        </thead>
        <tbody>
            @php
                $totalQty = 0;
                $totalPrice = 0;
                $totalPrices = 0;
            @endphp
            @foreach ($orderDetails as $valDetail)
                @php
                    $totalQty += $valDetail->available_quantity;
                    $totalPrice += $valDetail->price;
                    $totalPrices += $valDetail->price * $valDetail->available_quantity;
                @endphp
                <tr>
                    <td> {{ $valDetail->product_id }} </td>
                    <td> {{ $valDetail->product->name }} </td>
                    <td> {{ $valDetail->unit->name }} </td>
                    <td> {{ $valDetail->available_quantity }} </td>
                    {{-- <td> {{ $valDetail->price }} </td> --}}
                    {{-- <td> {{ $valDetail->price * $valDetail->available_quantity }} </td> --}}
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>

                <th colspan="3">
                    {{ __('lang.total') }}
                </th>
                <th> {{ $totalQty }} </th>
                {{-- <th> {{ $totalPrice }} </th>
                <th> {{ $totalPrices }} </th> --}}
            </tr>
        </tfoot>
    </table>

</body>

</html>
