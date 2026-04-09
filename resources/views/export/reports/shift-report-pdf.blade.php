<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <style>
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 11px;
            color: #000;
            margin: 0;
            padding: 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        th,
        td {
            border: 1px solid #333;
            padding: 4px 6px;
            text-align: left;
        }

        .sub-header {
            background-color: #b2dfdb;
            font-weight: bold;
            text-align: center;
            font-size: 10px;
            height: 30px;
        }

        .text-center {
            text-align: center;
        }
    </style>
</head>

<body>

    {{-- ========== HEADER ========== --}}
    <table style="border: none; width: 100%; margin-bottom: 10px;">
        <tr>
            <td style="border: none; width: 33%; text-align: left; vertical-align: top;">
                @if($companyLogo)
                <img src="{{ $companyLogo }}" alt="Company Logo" style="height: 60px; object-fit: contain;">
                @endif
            </td>
            <td style="border: none; width: 34%; text-align: center; vertical-align: top;">
                <h2 style="margin: 0 0 5px 0; font-size: 16px; text-decoration: underline;">SHIFT REPORT</h2>
                <p style="margin: 0; font-weight: bold; font-size: 13px;">{{ $branchName }}</p>
                <p style="margin: 5px 0 0 0; font-size: 11px; color: #374151;">{{ $periodName }}</p>
            </td>
            <td style="border: none; width: 33%; text-align: right; vertical-align: top;">
                <p style="margin: 0;"><strong>Date:</strong> {{ date('Y-m-d') }}</p>
                <img src="{{ public_path('storage/workbench.png') }}" alt="System Logo" style="height: 50px; margin-top: 10px; object-fit: contain; border-radius: 50%;">
            </td>
        </tr>
    </table>

    {{-- ========== REPORT DATA ========== --}}
    <table>
        <thead>
            <tr class="sub-header">
                <th style="width:40px;" class="text-center">#</th>
                <th>Employee Name</th>
                <th class="text-center">Shift</th>
                <th class="text-center">Start Time</th>
                <th class="text-center">End Time</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($items as $index => $item)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td>{{ $item['employee_name'] ?? '-' }}</td>
                <td class="text-center">{{ $item['period_name'] ?? '-' }}</td>
                <td class="text-center">{{ $item['period_start_at'] ?? '-' }}</td>
                <td class="text-center">{{ $item['period_end_at'] ?? '-' }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="5" class="text-center">No employees found in the selected shifts.</td>
            </tr>
            @endforelse
        </tbody>
    </table>

</body>

</html>
