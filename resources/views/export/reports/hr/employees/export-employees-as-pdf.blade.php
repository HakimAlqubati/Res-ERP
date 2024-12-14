<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Employees Report</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
        }

        .logo {
            max-width: 200px;
            margin-bottom: 15px;
        }

        .title {
            font-size: 24px;
            font-weight: bold;
            color: #333;
            margin-bottom: 10px;
        }

        .subtitle {
            font-size: 16px;
            color: #666;
            margin-bottom: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th {
            background-color: #f4f4f4;
            color: #333;
            font-weight: bold;
            padding: 12px;
            border: 1px solid #ddd;
            text-align: left;
        }

        td {
            padding: 10px;
            border: 1px solid #ddd;
        }

        tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        tr:hover {
            background-color: #f5f5f5;
        }
    </style>
</head>

<body>

    <div class="header">
        <img src="{{ public_path('images/logo.png') }}" class="logo" alt="Company Logo">
        <div class="title">Employees Report</div>
        <div class="subtitle">Generated on {{ date('Y-m-d H:i:s') }}</div>
    </div>

    <table>
        <thead>
            <tr>
                <th>{{ 'ID' }}</th>
                <th>{{ 'Employee No' }}</th>
                <th>{{ 'Name' }}</th>
                <th>{{ 'Branch' }}</th>
                <th>{{ 'Job Title' }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($data as $item)
                <tr>
                    <td>{{ $item->id }}</td>
                    <td>{{ $item->employee_no }}</td>
                    <td>{{ $item->name }}</td>
                    <td>{{ $item->branch?->name }}</td>
                    <td>{{ $item->job_title }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>

</html>
