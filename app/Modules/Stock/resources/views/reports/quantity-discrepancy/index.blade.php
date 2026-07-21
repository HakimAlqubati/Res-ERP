@extends('stock::layouts.report')

@section('title', 'Quantity Discrepancy Report')

@section('content')
<div class="header" style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 2rem;">
    <div>
        <h1>Quantity Discrepancy Report</h1>
        <p>Report showing inventory transactions with discrepancies where output is greater than input.</p>
    </div>
    <div style="font-size: 1.1rem; font-weight: 600; color: var(--primary);">
        Total Discrepancies: {{ count($report) }}
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Movement Date</th>
                    <th>Product ID</th>
                    <th>Transactionable ID</th>
                    <th class="text-right">Quantity</th>
                    <th class="text-right">Count</th>
                    <th class="text-right">Q In</th>
                    <th class="text-right">Q Out</th>
                    <th class="text-right">Qty Diff</th>
                </tr>
            </thead>
            <tbody>
                @forelse($report as $row)
                    <tr>
                        <td>{{ $row->movement_date_ }}</td>
                        <td>{{ $row->product_id }}</td>
                        <td>{{ $row->sctionbleid }}</td>
                        <td class="text-right">{{ $row->quantity }}</td>
                        <td class="text-right">{{ $row->count_ }}</td>
                        <td class="text-right">{{ $row->qin }}</td>
                        <td class="text-right">{{ $row->qout }}</td>
                        <td class="text-right font-semibold" style="color: var(--danger, #dc3545);">{{ $row->qty_diff }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="empty-state text-center" style="padding: 3rem;">
                            <svg style="width: 48px; height: 48px; margin: 0 auto 1rem auto; color: var(--text-light);" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            <div>No discrepancies found matching the criteria.</div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
