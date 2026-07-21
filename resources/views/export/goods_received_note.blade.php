<table>
    <tr>
        <th colspan="2" style="font-weight: bold; font-size: 14px; text-align: left;">Goods Received Note Details</th>
    </tr>
    <tr>
        <th style="font-weight: bold; text-align: left;">GRN Number:</th>
        <td style="text-align: left;">{{ $grn->grn_number }}</td>
    </tr>
    <tr>
        <th style="font-weight: bold; text-align: left;">GRN Date:</th>
        <td style="text-align: left;">{{ \Carbon\Carbon::parse($grn->grn_date)->format('Y-m-d') }}</td>
    </tr>
    <tr>
        <th style="font-weight: bold; text-align: left;">Store:</th>
        <td style="text-align: left;">{{ $grn->store?->name }}</td>
    </tr>
    <tr>
        <th style="font-weight: bold; text-align: left;">Supplier:</th>
        <td style="text-align: left;">{{ $grn->supplier?->name }}</td>
    </tr>
    <tr>
        <td colspan="2"></td>
    </tr>
</table>

<table>
    <thead>
        <tr>
            <th style="font-weight: bold; text-align: center; border: 1px solid #000000;">Product Code</th>
            <th style="font-weight: bold; text-align: center; border: 1px solid #000000;">Product Name</th>
            <th style="font-weight: bold; text-align: center; border: 1px solid #000000;">Unit</th>
            <th style="font-weight: bold; text-align: center; border: 1px solid #000000;">Package Size</th>
            <th style="font-weight: bold; text-align: center; border: 1px solid #000000;">Quantity</th>
            <th style="font-weight: bold; text-align: center; border: 1px solid #000000;">Price</th>
            <th style="font-weight: bold; text-align: center; border: 1px solid #000000;">Total Price</th>
        </tr>
    </thead>
    <tbody>
        @foreach($grn->grnDetails as $detail)
        <tr>
            <td style="text-align: left; border: 1px solid #000000;">{{ $detail->product?->code }}</td>
            <td style="text-align: left; border: 1px solid #000000;">{{ $detail->product?->name }}</td>
            <td style="text-align: center; border: 1px solid #000000;">{{ $detail->unit?->name }}</td>
            <td style="text-align: center; border: 1px solid #000000;">{{ $detail->package_size }}</td>
            <td style="text-align: center; border: 1px solid #000000;">{{ $detail->quantity }}</td>
            <td style="text-align: center; border: 1px solid #000000;">{{ $detail->price }}</td>
            <td style="text-align: center; border: 1px solid #000000;">{{ $detail->total_amount }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
