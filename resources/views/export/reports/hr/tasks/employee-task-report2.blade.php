<!DOCTYPE html>
<html  lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Task Data</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cairo&display=swap" rel="stylesheet">

    <style>
        /* General Reset */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        /* Body Styles */
        body {
            /* font-family: 'Arial', sans-serif; */
            font-family: 'Cairo', sans-serif;
            /* font-family: 'Arial', 'Cairo', 'Tahoma', sans-serif; */
            background-color: #f5f7fa;
            display: flex;
            justify-content: center;
            padding: 30px;
        }

        .container {
            background-color: #fff;
            width: 100%;
            max-width: 800px;
            border-radius: 10px;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
            padding: 20px;
        }

        /* Header Styles */
        .header {
            text-align: center;
            margin-bottom: 20px;
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        .logo-left,
        .logo-right {
            width: 80px;
            height: auto;
        }

        .company-details h1 {
            font-size: 24px;
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
        }

        .company-details p {
            font-size: 14px;
            color: #555;
        }

        .header h2 {
            font-size: 22px;
            font-weight: bold;
            color: #444;
            margin-top: 10px;
        }

        .task-header {
            margin-top: 20px;
            text-align: center;
        }

        .task-info {
            font-size: 16px;
            font-weight: bold;
            color: #333;
        }

        /* Employee Info Table */
        .employee-info {
            margin-bottom: 20px;
        }

        .employee-table {
            width: 100%;
            border-collapse: collapse;
        }

        .employee-table td {
            padding: 10px;
            font-size: 16px;
            color: #333;
        }

        .employee-table td strong {
            color: #444;
        }

        /* Task Details Table */
        .task-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .task-table th,
        .task-table td {
            padding: 12px;
            text-align: left;
            font-size: 16px;
        }

        .task-table th {
            background-color: rgba(0, 72, 39, 0.8);
            color: white;
            font-weight: bold;
        }

        .task-table td {
            border-bottom: 1px solid #e1e1e1;
        }

        .task-table tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        .task-table tr:hover {
            background-color: #e8f4f8;
        }

        /* Footer Styles */
        .footer {
            text-align: center;
            margin-top: 30px;
            font-size: 14px;
            color: #888;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .container {
                padding: 10px;
            }

            .header-content {
                flex-direction: column;
                align-items: center;
            }

            .logo-left,
            .logo-right {
                width: 60px;
            }

            .company-details h1 {
                font-size: 20px;
            }

            .task-table th,
            .task-table td {
                font-size: 14px;
            }
        }

        .done-icon {
            color: #4caf50;
            /* Green color for done */
        }

        .undone-icon {
            color: #f44336;
            /* Red color for not done */
        }

        .employee-info {
            border: 2px solid #0d7c66;
            border-radius: 7px;
        }
    </style>

</head>

<body>
    <div class="container">
        <header class="header">
            <div class="header-content">
                <!-- Left Logo -->
                <img src="{{ asset('/storage/' . setting('company_logo')) }}" alt="Company Logo" class="logo-left">

                <!-- Company Details -->
                <div class="company-details">
                    {{-- <h1>{{ setting('company_name') }}</h1>
                    <p>{{ setting('address') }}</p>
                    <p>{{ setting('company_phone') }}</p> --}}
                </div>

                <!-- Right Logo -->
                <img src="{{ url('/') . '/storage/logo/default.png' }}" alt="Company Logo" class="logo-right">
            </div>
            <h2>Employee Task Overview</h2>
            {{-- <div class="task-header">
                <div class="task-info">
                    <strong>Task ID:</strong> {{ $tasks[0]->task_id ?? 'N/A' }} <br>
                    <strong>Task Title:</strong> {{ $tasks[0]->task_title ?? 'N/A' }}
                </div>
            </div> --}}
        </header>

        <!-- Employee Information Section -->
        <section class="employee-info">
            <table class="employee-table">
                <tr>
                    <td> <strong>Name:</strong>
                        <p >{{ $employee?->name }}</p>
                    </td>
                    <td><strong>Employee ID:</strong> {{ $employee?->employee_no }}</td>
                    <td><strong>Task ID:</strong> #({{ $task->id ?? 'N/A' }})</td>

                </tr>
                <tr>
                    <td colspan="3">
                        <strong>Task Title:</strong> ({{ $task->title ?? 'N/A' }})
                    </td>
                </tr>
                <tr>
                    <td colspan="3">
                        <strong>Task Description:</strong> {{ $task->description ?? '' }}
                    </td>
                </tr>
            </table>
        </section>
        <section class="employee-info">
            <table class="employee-table">
                <tr>
                    <td><strong>Status:</strong> {{ $task?->task_status }}</td>

                    <td><strong>Time Spent:</strong> {{ $task?->total_spent_time }}</td>
                    <td><strong>Rating:</strong> {{ $task?->task_rating?->rating_value }}</td>

                </tr>
                <tr>

                    <td colspan="3"><strong>Rating Comment:</strong> {{ $task?->task_rating?->comment }}</td>
                </tr>

            </table>
        </section>

        <!-- Task Data Section -->
        <section class="task-details">
            <table class="task-table">
                <thead>
                    <tr>
                        <th>Step Order</th>
                        <th>Step Name</th>
                        <th>Is Done</th>

                    </tr>
                </thead>
                <tbody>
                    @foreach ($task->steps as $step)
                        <tr>
                            <td>{{ $step?->order }}</td>
                            <td>{{ $step?->title }}</td>
                            <td>
                                @if ($step?->done)
                                    <i class="fas fa-check-circle done-icon"></i>
                                @else
                                    <i class="fas fa-times-circle undone-icon"></i>
                                @endif

                            </td>

                        </tr>
                    @endforeach
                </tbody>
            </table>
        </section>

        <footer class="footer">
            <p>&copy; {{ date('Y') }} {{ setting('company_name') }}. All Rights Reserved.</p>
        </footer>
    </div>
</body>

</html>
