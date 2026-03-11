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

        .report-header {
            text-align: center;
            margin-bottom: 15px;
        }

        .report-header h2 {
            margin: 5px 0;
            font-size: 14px;
            text-decoration: underline;
        }

        .report-header p {
            margin: 3px 0;
            font-size: 11px;
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

        .section-header {
            background-color: #0d7c66;
            color: #fff;
            font-weight: bold;
            text-align: center;
            font-size: 12px;
        }

        .sub-header {
            background-color: #b2dfdb;
            font-weight: bold;
            text-align: center;
            font-size: 10px;
        }

        .text-center {
            text-align: center;
        }

        .signature-section {
            margin-top: 40px;
            font-size: 11px;
        }

        .signature-section p {
            margin: 3px 0;
        }

        .signature-line {
            border-top: 1px solid #000;
            margin: 30px 0 10px 0;
            width: 100%;
        }

        .managers-row {
            width: 100%;
            margin-top: 20px;
        }

        .managers-row td {
            border: none;
            width: 50%;
            padding: 5px 0;
            vertical-align: top;
        }
    </style>
</head>

<body>

    {{-- ========== HEADER ========== --}}
    <div class="report-header">
        @if($companyLogo)
        <img src="{{ $companyLogo }}" alt="Logo" style="height: 60px; margin-bottom: 5px;">
        @endif
        <p>Date: {{ \Carbon\Carbon::create($year, $month, 1)->endOfMonth()->format('M jS Y') }}</p>
        <p style="font-weight: bold; font-size: 13px;">{{ $branchName }}</p>
        <h2>ATTENDANCE REPORT &mdash; {{ \Carbon\Carbon::create($year, $month)->format('M') }}</h2>
    </div>

    {{-- ========== CURRENT STAFF ========== --}}
    <table>
        <thead>
            <tr>
                <td class="section-header" colspan="8">Current Staff &mdash; {{ \Carbon\Carbon::create($year, $month)->format('M Y') }}</td>
            </tr>
            <tr class="sub-header">
                <th rowspan="2" style="width:30px;">#</th>
                <th rowspan="2">NAME</th>
                <th rowspan="2" class="text-center">Present Days</th>
                <th colspan="2" class="text-center">Overtime</th>
                <th colspan="2" class="text-center">Deductions</th>
                <th rowspan="2">Note</th>
            </tr>
            <tr class="sub-header">
                <th class="text-center" style="width:45px;">Days</th>
                <th class="text-center" style="width:45px;">Hours</th>
                <th class="text-center" style="width:45px;">Days</th>
                <th class="text-center" style="width:45px;">Hours</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($report['current_staff'] as $i => $row)
            <tr>
                <td class="text-center">{{ $i + 1 }}.</td>
                <td style="font-weight: bold;">{{ $row['name'] }}</td>
                <td class="text-center">{{ $row['attendance']['present_days'] ?? '0' }}</td>
                <td class="text-center">{{ $row['overtime']['days'] ?: '0' }}</td>
                <td class="text-center">{{ $row['overtime']['hours'] ?: '0' }}</td>
                <td class="text-center">{{ $row['deductions']['days'] ?: '0' }}</td>
                <td class="text-center">{{ $row['deductions']['hours'] ?: '0' }}</td>
                <td>{{ $row['note'] ?? '0' }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="8" class="text-center">-</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    {{-- ========== NEW STAFF ========== --}}
    <table>
        <thead>
            <tr>
                <td class="section-header" colspan="9">New Staff &mdash; {{ \Carbon\Carbon::create($year, $month)->format('M Y') }}</td>
            </tr>
            <tr class="sub-header">
                <th rowspan="2" style="width:30px;">#.</th>
                <th rowspan="2">Name</th>
                <th rowspan="2" class="text-center">Present Days</th>
                <th colspan="2" class="text-center">Overtime</th>
                <th colspan="2" class="text-center">Deductions</th>
                <th colspan="2" class="text-center">Notes</th>
            </tr>
            <tr class="sub-header">
                <th class="text-center" style="width:45px;">Days</th>
                <th class="text-center" style="width:45px;">Hours</th>
                <th class="text-center" style="width:45px;">Days</th>
                <th class="text-center" style="width:45px;">Hours</th>
                <th class="text-center">Salary</th>
                <th class="text-center">Side Note</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($report['new_staff'] as $i => $row)
            <tr>
                <td class="text-center">{{ $i + 1 }}.</td>
                <td>{{ $row['name'] }}</td>
                <td class="text-center">{{ $row['attendance']['present_days'] ?? '0' }}</td>
                <td class="text-center">{{ $row['overtime']['days'] ?: '0' }}</td>
                <td class="text-center">{{ $row['overtime']['hours'] ?: '0' }}</td>
                <td class="text-center">{{ $row['deductions']['days'] ?: '0' }}</td>
                <td class="text-center">{{ $row['deductions']['hours'] ?: '0' }}</td>
                <td class="text-center">{{ $row['salary'] ?? '0' }}</td>
                <td>{{ $row['note'] ?? '0' }}</td>
            </tr>
            @empty
            @for ($j = 1; $j <= 5; $j++)
                <tr>
                <td class="text-center">{{ $j }}.</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                </tr>
                @endfor
                @endforelse
        </tbody>
    </table>

    {{-- ========== TERMINATED STAFF ========== --}}
    <table>
        <thead>
            <tr>
                <td class="section-header" colspan="9">Terminated Staff &mdash; {{ \Carbon\Carbon::create($year, $month)->format('M Y') }}</td>
            </tr>
            <tr class="sub-header">
                <th rowspan="2" style="width:30px;">#.</th>
                <th rowspan="2">Name</th>
                <th rowspan="2" class="text-center">Present Days</th>
                <th colspan="2" class="text-center">Overtime</th>
                <th colspan="2" class="text-center">Deductions</th>
                <th colspan="2" class="text-center">Notes</th>
            </tr>
            <tr class="sub-header">
                <th class="text-center" style="width:45px;">Days</th>
                <th class="text-center" style="width:45px;">Hours</th>
                <th class="text-center" style="width:45px;">Days</th>
                <th class="text-center" style="width:45px;">Hours</th>
                <th class="text-center">Leave Date</th>
                <th class="text-center">Side Note</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($report['terminated_staff'] as $i => $row)
            <tr>
                <td class="text-center">{{ $i + 1 }}.</td>
                <td>{{ $row['name'] }}</td>
                <td class="text-center">{{ $row['attendance']['present_days'] ?? '0' }}</td>
                <td class="text-center">{{ $row['overtime']['days'] ?: '0' }}</td>
                <td class="text-center">{{ $row['overtime']['hours'] ?: '0' }}</td>
                <td class="text-center">{{ $row['deductions']['days'] ?: '0' }}</td>
                <td class="text-center">{{ $row['deductions']['hours'] ?: '0' }}</td>
                <td class="text-center">{{ $row['termination_date'] ?? '0' }}</td>
                <td>{{ $row['note'] ?? '0' }}</td>
            </tr>
            @empty
            @for ($j = 1; $j <= 6; $j++)
                <tr>
                <td class="text-center">{{ $j }}.</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                </tr>
                @endfor
                @endforelse
        </tbody>
    </table>

    {{-- ========== SIGNATURES ========== --}}
    <div class="signature-section">
        <p><strong>Branch Manager</strong></p>
        <p>{{ $branchManager ?? '________________' }}</p>

        <div class="signature-line"></div>

        <p><strong><u>Office Use Only</u></strong></p>
        <p><strong>Checked & Approved by Management Team:</strong></p>

        <table class="managers-row" style="margin-top: 15px;">
            <tr>
                <td>
                    <p><strong>Operation Manager</strong></p>
                    <p>{{ $operationManager ?? '________________' }}</p>
                </td>
                <td>
                    <p><strong>Sustaining Manager</strong></p>
                    <p>{{ $sustainingManager ?? '________________' }}</p>
                </td>
            </tr>
            <tr>
                <td colspan="2" style="padding-top: 15px;">
                    <p><strong>Finance Manager</strong></p>
                    <p>{{ $financeManager ?? '________________' }}</p>
                </td>
            </tr>
        </table>
    </div>

</body>

</html>