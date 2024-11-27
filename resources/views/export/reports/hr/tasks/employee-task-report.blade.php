<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Employee Task Report</title>
    <style>
        body {
            font-family: Arial, sans-serif;
        }
        table {
            width: 100%;
            border-collapse: collapse;
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
    <h2>Employee Task Report</h2>
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
                    <td>{{ $record->task_status }}</td>
                    <td>{{ gmdate('H:i:s', $record->total_spent_seconds ?? 0) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
