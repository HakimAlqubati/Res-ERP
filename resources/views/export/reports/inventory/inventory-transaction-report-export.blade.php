<table>
    <thead>
        <tr>
            <th>Product Code</th>
            <th>Product Name</th>
            <th>Unit ID</th>
            <th>Unit Name</th>
            <th>Package Size</th>
            <th>Quantity in Stock</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($reportData as $productReport)
            @foreach ($productReport as $data)
                <tr>
                    <td>{{ $data['product_code'] }}</td>
                    <td>{{ $data['product_name'] }}</td>
                    <td>{{ $data['unit_id'] }}</td>
                    <td>{{ $data['unit_name'] }}</td>
                    <td>{{ $data['package_size'] }}</td>
                    <td>{{ $data['remaining_qty'] }}</td>
                </tr>
            @endforeach
        @endforeach
    </tbody>
</table>
