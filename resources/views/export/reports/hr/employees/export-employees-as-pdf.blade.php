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
        <img src="{{ asset('/storage/' . setting('company_logo') . '') }}" class="logo" alt="Company Logo">
        <div class="title">Employees Report</div>
        <div class="subtitle">Generated on {{ date('Y-m-d H:i:s') }}</div>
    </div>

    <table>
        <thead>
            <tr>
                <th>{{ __('lang.id') }}</th>
                <th>{{ __('lang.employee_no') }}</th>
                <th>{{ __('lang.full_name') }}</th>
                <th>{{ __('lang.branch') }}</th>
                <th>{{ __('lang.manager') }}</th>
                <th>{{ __('lang.email') }}</th>
                <th>{{ __('lang.phone_number') }}</th>
                <th>{{ __('lang.shift') }}</th>
                <th>{{ __('lang.start_date') }}</th>
                <th>{{ __('lang.termination_date') }}</th>
                <th>{{ __('lang.termination_reason') }}</th>
                <th>{{ __('lang.salary') }}</th>
                <th>{{ __('lang.working_hours') }}</th>
                <th>{{ __('lang.working_days') }}</th>
                <th>{{ __('lang.job_title') }}</th>
                <th>{{ __('lang.role_type') }}</th>
                <th>{{ __('lang.unrequired_docs') }}</th>
                <th>{{ __('lang.required_docs') }}</th>
                <th>{{ __('lang.active') }}</th>
                <th>{{ __('lang.nationality') }}</th>
                <th>{{ __('lang.has_auto_weekly_leave') }}</th>
                <th>{{ __('lang.created_at') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($data as $item)
                <tr>
                    <td>{{ $item->id }}</td>
                    <td>{{ $item->employee_no }}</td>
                    <td>{{ $item->name }}</td>
                    <td>{{ $item->branch?->name }}</td>
                    <td>{{ $item->manager?->name }}</td>
                    <td>{{ $item->email }}</td>
                    <td>{{ $item->phone_number }}</td>
                    <td>{{ $item->periods->pluck('name')->implode(', ') }}</td>
                    <td>{{ $item->join_date }}</td>
                    <td>{{ $item->serviceTermination?->termination_date }}</td>
                    <td>{{ $item->serviceTermination?->termination_reason }}</td>
                    <td>{{ $item->salary }}</td>
                    <td>{{ $item->working_hours }}</td>
                    <td>{{ $item->working_days }}</td>
                    <td>{{ $item->job_title }}</td>
                    <td>{{ $item->employeeType?->name }}</td>
                    <td>{{ $item->unrequired_documents_count }}</td>
                    <td>{{ $item->required_documents_count }}</td>
                    <td>{{ $item->active ? __('lang.active') : __('lang.terminated') }}</td>
                    <td>{{ $item->nationality }}</td>
                    <td>{{ $item->has_auto_weekly_leave ? __('lang.yes') : __('lang.no') }}</td>
                    <td>{{ $item->created_at }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>

</html>
