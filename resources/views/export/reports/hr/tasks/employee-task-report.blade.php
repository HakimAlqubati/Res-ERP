<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Task Report</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            background-color: #f9f9f9;
        }

        .task-report {
            width: 100%;
            max-width: 900px;
            background: #fff;
            padding: 20px;
            border: 1px solid #ccc;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        header {
            text-align: center;
            border-bottom: 2px solid #ccc;
            padding-bottom: 10px;
            position: relative;
            margin-bottom: 20px;
        }

        .company-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .company-info .company-details {
            flex: 1;
            text-align: center;
        }

        .logo-left {
            position: absolute;
            top: 10px;
            left: 20px;
            width: 80px;
        }

        .logo-right {
            position: absolute;
            top: 10px;
            right: 20px;
            width: 80px;
        }

        header h1 {
            font-size: 24px;
            margin: 5px 0;
        }

        header p {
            margin: 2px 0;
        }

        .task-title {
            margin-top: 5px;
            font-size: 20px;
            font-weight: bold;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: center;
        }

        th {
            background-color: #f4f4f4;
        }
    </style>
</head>

<body>
    <div class="task-report">
        <header>
            <div class="company-info">
                <!-- Left Logo -->
                <img src="{{ asset('/storage/' . setting('company_logo') . '') }}" alt="Company Logo" class="logo-left">

                <!-- Company Name and Address in Center -->
                <div class="company-details">
                    <h1>{{ setting('company_name') }}</h1>
                    
                </div>

                <!-- Right Logo -->
                <img src="{{ url('/') . '/storage/logo/default.png' }}" alt="Company Logo" class="logo-right">
            </div>

            <h2 class="task-title">Employee Task Report</h2>
        </header>

        <table>
            <thead>
                <tr>
                    <th>Employee ID</th>
                    <th>Employee No</th>
                    <th>Employee Name</th>
                    <th>Task ID</th>
                    <th>Task Title</th>
                    <th>Status</th>
                    <th>Time Spent</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($data as $record)
                    <tr>
                        <td>{{ $record->employee_id }}</td>
                        <td>{{ $record->employee_no }}</td>
                        <td>{{ $record->employee_name }}</td>
                        <td>{{ $record->task_id }}</td>
                        <td>{{ $record->task_title }}</td>
                        <td>{{ ucfirst($record->task_status) }}</td>
                        <td>{{ gmdate('H:i:s', $record->total_spent_seconds ?? 0) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</body>

</html>
