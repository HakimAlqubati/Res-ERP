@extends('stock::layouts.report')

@section('title', 'Hierarchical GRN Consumption')

@section('content')
        <div class="header">
            <h1>GRN Consumption Tracking</h1>
        </div>

        <div class="card">
            <!-- Filter Section -->
            <form class="filter-form" method="GET" action="{{ route('stock.reports.grn-consumption.index') }}" style="flex-wrap: wrap; align-items: center;">
                <div class="autocomplete-container">
                    <input type="text" class="autocomplete-input" placeholder="Filter by Product..." value="{{ $selectedProduct->name ?? '' }}" autocomplete="off">
                    <input type="hidden" name="product_id" value="{{ request('product_id') }}">
                    <div class="clear-autocomplete">&times;</div>
                    <div class="autocomplete-dropdown"></div>
                </div>
                <input type="text" name="grn_number" placeholder="Search by GRN Number..." value="{{ request('grn_number') }}">
                <input type="text" name="search" placeholder="Search GRN or notes..." value="{{ request('search') }}">
                <select name="completion_status" style="padding: 0.6rem 1rem; border: 1px solid var(--border); border-radius: 6px; outline: none; font-size: 0.95rem; min-width: 150px;">
                    @foreach(\App\Modules\Stock\Reports\Enums\FilterCompletionStatus::cases() as $case)
                        <option value="{{ $case->value }}" {{ request('completion_status') === $case->value ? 'selected' : '' }}>
                            {{ $case->label() }}
                        </option>
                    @endforeach
                </select>
                <select name="invoice_status" style="padding: 0.6rem 1rem; border: 1px solid var(--border); border-radius: 6px; outline: none; font-size: 0.95rem; min-width: 150px;">
                    @foreach(\App\Modules\Stock\Reports\Enums\FilterInvoiceLinkStatus::cases() as $case)
                        <option value="{{ $case->value }}" {{ request('invoice_status') === $case->value ? 'selected' : '' }}>
                            {{ $case->label() }}
                        </option>
                    @endforeach
                </select>
                <button type="submit" class="btn-primary">Filter</button>
                <a href="{{ route('stock.reports.grn-consumption.index') }}" class="btn-link">Clear</a>
            </form>

            <!-- Table Section -->
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Product Details</th>
                            <th>Unit</th>
                            <th class="text-right">Entry Qty</th>
                            <th class="text-right">Remaining Qty</th>
                            <th>Entry Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($report as $grnResult)
                            <!-- GRN Header Row -->
                            <tr class="grn-row">
                                <td colspan="5">
                                    <span style="font-size: 1.05rem;">📦 GRN: #{{ $grnResult->grnNumber }}</span>
                                    <span style="color: var(--text-light); font-size: 0.85rem; margin-left: 0.5rem; font-weight: normal;">
                                        ({{ $grnResult->formattedGrnDate }})
                                    </span>
                                    @if($grnResult->isLinkedToInvoice)
                                        <span class="badge blue" style="margin-left: 1rem;">Invoice: #{{ $grnResult->invoiceNumber }}</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="{{ $grnResult->statusBadgeClass }}">{{ $grnResult->statusText }}</span>
                                </td>
                            </tr>
                            
                            <!-- GRN Items Rows -->
                            @foreach($grnResult->items as $item)
                                <tr class="item-row">
                                    <td style="padding-left: 3rem;">{{ $item->productName }}</td>
                                    <td>
                                        <div>{{ $item->unitName }}</div>
                                        <div class="text-muted" style="font-size: 0.8rem;">Pack: {{ $item->packageSize }}</div>
                                    </td>
                                    <td class="text-right">{{ $item->entryQuantity }}</td>
                                    <td class="text-right font-semibold" style="color: {{ $item->remainingQuantityColor }};">
                                        {{ $item->remainingQuantity }}
                                    </td>
                                    <td>{{ $item->formattedEntryDate }}</td>
                                    <td>
                                        <span class="{{ $item->statusBadgeClass }}">{{ $item->statusText }}</span>
                                    </td>
                                </tr>
                            @endforeach
                        @empty
                            <tr>
                                <td colspan="6" class="empty-state">
                                    <svg style="width: 48px; height: 48px; margin: 0 auto 1rem auto; color: #cbd5e1;" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path></svg>
                                    <div>No Goods Received Notes found.</div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @if($report->hasPages())
                <div class="pagination-container">
                    {{ $report->withQueryString()->links('pagination::bootstrap-4') }}
                </div>
            @endif
        </div>
@endsection
