<!DOCTYPE html>
<html lang="en" dir="ltr">

<head>
    <meta charset="UTF-8" />
    <title>Advance Request Slip</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />

    <style>
        body {
            font-family: 'cairo', sans-serif;
            font-size: 13px;
            line-height: 1.45;
            color: #222;
        }

        .wrap {
            width: 100%;
            margin: 0 auto;
        }

        .doc {
            padding: 10px;
        }

        .head-table {
            width: 100%;
            border-bottom: 2px solid #006644;
            margin-bottom: 15px;
            padding-bottom: 10px;
        }

        .head-table td {
            vertical-align: middle;
        }

        .logoBox {
            width: 60px;
            text-align: left;
        }

        .title-box {
            text-align: left;
            padding-left: 10px;
        }

        .title {
            font-weight: 800;
            font-size: 18px;
            color: #111;
            margin: 0;
            text-transform: uppercase;
        }

        .ref-box {
            text-align: right;
            font-weight: bold;
            font-size: 14px;
            color: #333;
        }

        /* Top Info Section */
        .top-info-table {
            width: 100%;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            margin-bottom: 25px;
            background-color: #fcfcfc;
        }
        .top-info-table td {
            padding: 15px;
            vertical-align: top;
        }
        
        .employee-col {
            width: 35%;
            border-right: 1px solid #e0e0e0;
        }
        
        .emp-name { font-size: 16px; font-weight: bold; margin-bottom: 3px; color: #111; }
        .emp-detail { font-size: 13px; color: #555; margin-bottom: 2px; }

        .loan-col {
            width: 65%;
            padding-left: 20px !important;
        }
        
        .loan-details-title {
            font-size: 12px;
            font-weight: bold;
            color: #555;
            text-transform: uppercase;
            margin-bottom: 15px;
            letter-spacing: 0.5px;
        }

        .loan-stats {
            width: 100%;
        }
        .loan-stats td {
            padding: 0;
            vertical-align: top;
        }
        
        .stat-label {
            font-size: 12px;
            color: #006644;
            font-weight: bold;
            margin-bottom: 5px;
            display: flex;
            align-items: center;
        }
        
        .stat-value {
            font-size: 16px;
            font-weight: bold;
            color: #111;
        }

        /* Split Section */
        .split-table {
            width: 100%;
            margin-bottom: 20px;
        }
        
        .split-table td {
            vertical-align: top;
        }

        .request-details-col {
            width: 35%;
            padding-right: 20px;
        }

        .section-title {
            font-size: 13px;
            font-weight: bold;
            text-transform: uppercase;
            color: #222;
            margin-bottom: 15px;
            border-bottom: 1px solid #e0e0e0;
            padding-bottom: 5px;
        }

        .req-item { margin-bottom: 15px; }
        .req-label { font-size: 13px; color: #111; font-weight: bold; margin-bottom: 2px;}
        .req-val { font-size: 20px; font-weight: bold; color: #111; }
        .req-val-small { font-size: 14px; color: #333; }

        .schedule-col {
            width: 65%;
        }

        .schedule-table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #006644;
            border-radius: 4px;
            overflow: hidden;
        }
        
        .schedule-table th {
            background-color: #006644;
            color: white;
            text-align: left;
            padding: 8px 10px;
            font-size: 13px;
        }
        
        .schedule-table td {
            padding: 8px 10px;
            border-bottom: 1px solid #e0e0e0;
            font-size: 13px;
        }

        .schedule-table tr:last-child td {
            border-bottom: none;
        }

        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: bold;
        }
        .badge-pending { border: 1px solid #aaa; color: #555; background-color: #f9f9f9; }
        .badge-paid { border: 1px solid #006644; color: #006644; background-color: #e6f9f0; }
        .badge-approved { border: 1px solid #006644; color: #006644; background-color: #e6f9f0; }

        .total-row td {
            background-color: #f5f5f5;
            font-weight: bold;
            border-top: 1px solid #ccc !important;
        }

        /* Workflow */
        .workflow-box {
            margin-top: 20px;
            padding-top: 20px;
        }

        .approver {
            display: inline-block;
            width: 48%;
            vertical-align: top;
        }

        .approver-name { font-weight: bold; font-size: 13px; color: #111; }
        .approver-time { font-size: 12px; color: #666; margin-top: 3px; }

        .final-box {
            margin-top: 20px;
            background-color: #f4f6f5;
            border: 1px solid #dcdcdc;
            border-radius: 6px;
            padding: 15px;
        }
        
        .final-text {
            font-size: 14px;
            font-weight: bold;
            color: #111;
            margin-bottom: 10px;
        }
    </style>
</head>

<body>
    <div class="wrap">
        <div class="doc">

            <!-- Header -->
            <table class="head-table" cellpadding="0" cellspacing="0">
                <tr>
                    <td class="logoBox">
                        @if(setting('company_logo'))
                            <img style="max-height: 40px;" src="{{ public_path('storage/' . setting('company_logo')) }}">
                        @else
                            <img style="max-height: 40px;" src="{{ public_path('storage/logo/default.png') }}">
                        @endif
                    </td>
                    <td class="title-box">
                        <div class="title">OFFICIAL LOAN REQUEST & APPROVAL SLIP</div>
                    </td>
                    <td class="ref-box">
                        REF-ADV-{{ \Carbon\Carbon::parse($application->created_at)->format('Y') }}-{{ str_pad($application->id, 4, '0', STR_PAD_LEFT) }}
                    </td>
                </tr>
            </table>

            <!-- Top Info Table -->
            <table class="top-info-table" cellpadding="0" cellspacing="0">
                <tr>
                    <td class="employee-col">
                        <div class="emp-name">{{ $application->employee?->name ?? 'N/A' }}</div>
                        <div class="emp-detail">{{ $application->employee?->employee_no ?? '-' }}</div>
                        <div class="emp-detail">{{ $application->employee?->job_title ?? 'N/A' }}</div>
                    </td>
                    <td class="loan-col">
                        <div class="loan-details-title">LOAN DETAILS</div>
                        <table class="loan-stats" cellpadding="0" cellspacing="0">
                            <tr>
                                <td style="width: 33%">
                                    <div class="stat-label">Amount</div>
                                    <div class="stat-value">{{ formatMoneyWithCurrency($advance?->advance_amount ?? 0) }}</div>
                                </td>
                                <td style="width: 33%">
                                    <div class="stat-label">Purpose</div>
                                    <div class="stat-value">{{ Str::limit($advance?->reason ?? $application->notes ?? 'Advance Request', 25) }}</div>
                                </td>
                                <td style="width: 33%">
                                    <div class="stat-label">Repayment Period</div>
                                    <div class="stat-value">{{ $advance?->number_of_months_of_deduction ?? 0 }} Months</div>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>

            <!-- Main Content Split -->
            <table class="split-table" cellpadding="0" cellspacing="0">
                <tr>
                    <!-- Left: Request Details -->
                    <td class="request-details-col">
                        <div class="section-title">REQUEST DETAILS</div>
                        
                        <div class="req-item">
                            <div class="req-label">Requested Amount</div>
                            <div class="req-val">{{ formatMoneyWithCurrency($advance?->advance_amount ?? 0) }}</div>
                        </div>

                        <div class="req-item">
                            <div class="req-label">Loan Purpose</div>
                            <div class="req-val-small">{{ $advance?->reason ?? $application->notes ?? 'Advance Request' }}</div>
                        </div>

                        <div class="req-item">
                            <div class="req-label">Repayment Period</div>
                            <div class="req-val-small">{{ $advance?->number_of_months_of_deduction ?? 0 }} Months</div>
                        </div>
                    </td>

                    <!-- Right: Installment Schedule -->
                    <td class="schedule-col">
                        <div class="section-title">INSTALLMENT SCHEDULE</div>
                        
                        <table class="schedule-table">
                            <thead>
                                <tr>
                                    <th style="width: 25%">Installment No.</th>
                                    <th style="width: 30%">Due Date</th>
                                    <th style="width: 45%; text-align: right;">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($installments as $inst)
                                <tr>
                                    <td>{{ $inst->sequence }}.</td>
                                    <td>{{ $inst->due_date ? \Carbon\Carbon::parse($inst->due_date)->format('M d, Y') : '-' }}</td>
                                    <td style="text-align: right;">
                                        {{ formatMoneyWithCurrency($inst->installment_amount) }}
                                        &nbsp;
                                        <span class="badge {{ $inst->is_paid ? 'badge-paid' : 'badge-pending' }}">
                                            {{ $inst->is_paid ? 'Paid' : 'Pending' }}
                                        </span>
                                    </td>
                                </tr>
                                @endforeach
                                
                                <tr class="total-row">
                                    <td colspan="2" style="text-align: right;">Total Repayment</td>
                                    <td style="text-align: right;">{{ formatMoneyWithCurrency($advance?->advance_amount ?? 0) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </td>
                </tr>
            </table>

            <!-- Approval Workflow -->
            <div class="workflow-box">
                <div class="section-title">APPROVAL WORKFLOW</div>
                
                @if($application->status === \App\Models\EmployeeApplicationV2::STATUS_APPROVED)
                    @if($application->approvedBy)
                    <div class="approver">
                        <div class="approver-name">
                            {{ $application->approvedBy->name }} 
                            <span class="badge badge-approved" style="margin-left: 5px;">Approved</span>
                        </div>
                        <div class="approver-time">
                            Approved: {{ $application->approved_at ? \Carbon\Carbon::parse($application->approved_at)->format('M d, Y | h:i A') : '-' }}
                        </div>
                    </div>
                    @endif
                @elseif($application->status === \App\Models\EmployeeApplicationV2::STATUS_REJECTED)
                    <div class="approver">
                        <div class="approver-name">
                            {{ $application->rejectedBy?->name ?? 'System' }} 
                            <span class="badge" style="border: 1px solid #cc0000; color: #cc0000; background-color: #ffe6e6;">Rejected</span>
                        </div>
                        <div class="approver-time">
                            Rejected: {{ $application->rejected_at ? \Carbon\Carbon::parse($application->rejected_at)->format('M d, Y | h:i A') : '-' }}
                        </div>
                    </div>
                @else
                    <div class="approver">
                        <div class="approver-name">Pending Approval</div>
                        <div class="approver-time">This application is awaiting review.</div>
                    </div>
                @endif
            </div>

            <!-- Final Summary Box -->
            <div class="final-box">
                <table width="100%" cellpadding="0" cellspacing="0">
                    <tr>
                        <td>
                            <div class="final-text">
                                Final Loan Funds to be disbursed on {{ $advance?->deduction_starts_from ? \Carbon\Carbon::parse($advance->deduction_starts_from)->format('M d, Y') : '-' }}.
                            </div>
                        </td>
                        <td style="text-align: right;">
                            @if($application->status === \App\Models\EmployeeApplicationV2::STATUS_APPROVED)
                                <span class="badge badge-approved" style="font-size: 13px; padding: 5px 12px;">Approved</span>
                            @elseif($application->status === \App\Models\EmployeeApplicationV2::STATUS_REJECTED)
                                <span class="badge" style="border: 1px solid #cc0000; color: #cc0000; background-color: #ffe6e6; font-size: 13px; padding: 5px 12px;">Rejected</span>
                            @else
                                <span class="badge badge-pending" style="font-size: 13px; padding: 5px 12px;">Pending</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td style="padding-top: 15px;">
                            <div class="approver-name">{{ $application->employee?->name }}</div>
                            <div class="approver-time">Request Date: {{ $application->application_date ? \Carbon\Carbon::parse($application->application_date)->format('M d, Y') : '-' }}</div>
                        </td>
                        <td style="text-align: right; vertical-align: bottom;">
                            <div class="approver-time">
                                @if($application->status === \App\Models\EmployeeApplicationV2::STATUS_APPROVED)
                                    Approved: {{ $application->approved_at ? \Carbon\Carbon::parse($application->approved_at)->format('M d, Y | h:i A') : '-' }}
                                @endif
                            </div>
                        </td>
                    </tr>
                </table>
            </div>

        </div>
    </div>
</body>

</html>
