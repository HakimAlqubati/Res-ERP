<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <title>Employee Task Report</title>
    <style>
        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            /* Use a UTF-8 compatible font */
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: center;
        }

        th {
            background-color: #f4f4f4;
        }

        header {
            text-align: center;
            border-bottom: 2px solid #ccc;
            padding-bottom: 10px;
            position: relative;
        }

        .company-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .company-info img {
            width: 80px;
            height: auto;
        }

        .company-details {
            flex: 1;
            text-align: center;
        }

        .logo-left {
            position: absolute;
            top: 10px;
            left: 20px;
        }

        .logo-right {
            position: absolute;
            top: 10px;
            right: 20px;
        }
    </style>
</head>

<body>
    <header>
        <div class="company-info">
            <img src="{{ asset('/storage/' . setting('company_logo')) }}" alt="Company Logo" style="width: 100px; height: auto;" class="logo-left">
            <div class="company-details">
                <h1>{{ setting('company_name') }}</h1>
                <p>{{ setting('address') }}</p>
                <p>{{ setting('company_phone') }}</p>
            </div>
            <img src="{{ url('/storage/logo/default.png') }}" alt="Company Logo" style="width: 100px; height: auto;" class="logo-right">
        </div>
        <h2>Employee Task Report</h2>
    </header>

    <table>
        <thead>
            <tr>

                <th>Employee</th>
                <th>Task Title</th>
                <th>Status</th>
                <th>Time Spent</th>
                <th>Progress</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($data as $record)
                <tr>

                    <td>{{ $record->employee_name }}</td>
                    <td>{{ $record->task_title }}</td>
                    <td>{{ $record->task_status }}</td>
                    <td>{{ gmdate('H:i:s', $record->total_spent_seconds ?? 0) }}</td>
                    <td>{{ $record->progress_percentage }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>

</html>
