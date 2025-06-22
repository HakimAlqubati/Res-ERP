<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Delivery & Invoicing Report</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap CSS (CDN) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f6f9;
        }

        h2 {
            color: #0d6efd;
        }

        .table-warning {
            background-color: #fff3cd !important;
        }

        .table th,
        .table td {
            vertical-align: middle;
        }

        .alert {
            text-align: center;
        }
    </style>
</head>

<body>

    <div class="container py-4">
        <h2 class="mb-4 text-center">📦 Delivery & Invoicing Report</h2>

        @if ($report->isEmpty())
            <div class="alert alert-info text-center">
                No data available to display in the report.
            </div>
        @else
            <table class="table table-bordered table-hover text-center">
                <thead class="table-dark">
                    <tr>
                        <th>#</th>
                        <th>Reseller</th>
                        <th>DO</th>
                        <th>Invoiced</th>
                        <th>Balance</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($report as $index => $row)
                        <tr class="{{ $row['balance'] > 0 ? 'table-warning' : '' }}">
                            <td>{{ $index + 1 }}</td>
                            <td>{{ $row['branch'] }}</td>
                            <td>{{ number_format($row['do_total'], 2) }}</td>
                            <td>{{ number_format($row['invoiced_total'], 2) }}</td>
                            <td>{{ number_format($row['balance'], 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

</body>

</html>
