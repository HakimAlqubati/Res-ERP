<!-- resources/views/pdfs/product_items.blade.php -->
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Product Item Report</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: center; }
        th { background: #f9f9f9; }
    </style>
</head>
<body>
    <h3>Product Item Report: {{ $product->name }} ({{ $product->code }})</h3>

    <table>
        <thead>
            <tr>
                <th>Parent Code</th>
                <th>Parent Name</th>
                <th>Item Name</th>
                <th>Unit</th>
                <th>Quantity</th>
                <th>Waste %</th>
                <th>Quantity After Waste</th>
            </tr>
        </thead>
        <tbody>
            @foreach($items as $item)
                <tr>
                    <td>{{ $item->parent_code }}</td>
                    <td>{{ $item->parent_product }}</td>
                    <td>{{ $item->item_product_name }}</td>
                    <td>{{ $item->item_unit_name }}</td>
                    <td>{{ $item->item_quantity }}</td>
                    <td>{{ $item->waste_percentage }}</td>
                    <td>{{ $item->item_quantity_after_waste }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
