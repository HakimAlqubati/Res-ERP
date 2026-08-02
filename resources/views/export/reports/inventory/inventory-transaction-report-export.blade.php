<table>
    <thead>
        <tr>
            <th colspan="6" style="text-align: center; font-weight: bold; font-size: 14px;">
                Store: {{ $storeName }} | Date: {{ $date }}
            </th>
        </tr>
        <tr>
            <th>Product Code</th>
            <th>Product Name</th>
            <th>Category</th>
            <th>Unit Name</th>
            <th>Qty per Pack</th>
            <th>Quantity in Stock</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($reportData as $productReport)
            @foreach ($productReport as $data)
                <tr>
                    <td>{{ $data['product_code'] }}</td>
                    <td>{{ $data['product_name'] }}</td>
                    <td>{{ \App\Models\Product::find($data['product_id'] ?? null)?->category?->name ?? '' }}</td>
                    <td>{{ $data['unit_name'] }}</td>
                    <td>{{ $data['package_size'] }}</td>
                    <td>{{ $data['remaining_qty'] }}</td>
                </tr>
            @endforeach
        @endforeach
    </tbody>
</table>
