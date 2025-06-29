<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Stock Inventory Report</title>
    <style>
        /* Global Styles */
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            padding: 0;
            background-color: #f4f4f4;
            color: #333;
        }

        /* Header */
        .header-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px;
            background-color: #0d7c66;
            color: white;
            border-radius: 8px;
        }

        .logo-left {
            width: 80px;
            height: auto;
        }

        .company-details {
            text-align: center;
            flex-grow: 1;
        }

        .company-name {
            font-size: 22px;
            font-weight: bold;
            margin-bottom: 5px;
        }

        /* Title */
        h2 {
            text-align: center;
            color: #2c3e50;
            margin-top: 15px;
        }

        /* Table Styles */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
        }

        th,
        td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        th {
            background-color: #0d7c66;
            color: white;
            text-transform: uppercase;
        }

        tbody tr:nth-child(odd) {
            background-color: #f9f9f9;
        }

        tbody tr:hover {
            background-color: #ecf0f1;
        }

        /* Print Styles */
        @media print {
            body {
                background: white;
            }

            .header-container {
                background: none;
                color: black;
                border-bottom: 2px solid black;
            }

            table {
                box-shadow: none;
            }
        }
    </style>
</head>

<body>

    <!-- Header Section -->
    <header>
        <div class="header-container">
            <!-- Left Logo -->
            <img src="{{ asset('/storage/' . setting('company_logo') . '') }}" alt="Company Logo" class="logo-left">

            <!-- Center Company Details -->
            <div class="company-details">
                <div class="company-name">{{ setting('company_name') ?? 'Company Name' }}</div>
            </div>
        </div>
        <h2>Stocktakes</h2>
    </header>

    <!-- Table Section -->
    <table>
        <thead>
            <tr> 
                <th>Product Code</th>
                <th>Product Name</th>
                <th>Category</th>
                <th>Unit</th>
                <th>Quantity</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($products as $product)
                @if (!empty($product->unitPrices))
                    @foreach ($product->unitPrices as $unit)
                        <tr> 
                            <td>{{ $product->code }}</td>
                            <td>{{ $product->name }}</td>
                            <td>{{ $product->category->name }}</td>
                            <td>{{ $unit->unit->name ?? 'N/A' }}</td> <!-- Display Unit Name -->
                            <td></td> <!-- Empty Quantity Field -->
                        </tr>
                    @endforeach
                @else
                    <tr>
                        <td>{{ $product->id }}</td>
                        <td>{{ $product->code }}</td>
                        <td>{{ $product->name }}</td>
                        <td>-</td>
                        <td></td>
                    </tr>
                @endif
            @endforeach
        </tbody>
        
    </table>

</body>

</html>
