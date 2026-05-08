@extends('stock::layouts.report')

@section('title', 'Products Summary')

@section('content')
        <div class="header" style="display: flex; justify-content: space-between; align-items: flex-end;">
            <div>
                <h1>Products Summary</h1>
                <p>Total received and consumed quantities for each product.</p>
            </div>
            <div style="font-size: 1.1rem; font-weight: 600; color: var(--primary);">
                Total Products: {{ $report->total() }}
            </div>
        </div>

        <div class="card">
            <!-- Filter Section -->
            <form class="filter-form" method="GET" action="{{ route('stock.reports.product-grn-aggregation.index') }}" style="align-items: flex-end;">
                <div class="form-group">
                    <label class="form-label">Product</label>
                    <div class="autocomplete-container">
                        <input type="text" class="autocomplete-input" placeholder="All Products" value="{{ $selectedProduct->name ?? '' }}" autocomplete="off">
                        <input type="hidden" name="product_id" value="{{ request('product_id') }}">
                        <div class="clear-autocomplete">&times;</div>
                        <div class="autocomplete-dropdown"></div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">From Date</label>
                    <input type="date" name="date_from" value="{{ request('date_from') }}">
                </div>
                
                <div class="form-group">
                    <label class="form-label">To Date</label>
                    <input type="date" name="date_to" value="{{ request('date_to') }}">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Status</label>
                    <select name="completion_status" style="min-width: 150px;">
                        @foreach(\App\Modules\Stock\Reports\Enums\FilterCompletionStatus::cases() as $case)
                            <option value="{{ $case->value }}" {{ request('completion_status') === $case->value ? 'selected' : '' }}>
                                {{ $case->label() }}
                            </option>
                        @endforeach
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Invoice</label>
                    <select name="invoice_status" style="min-width: 150px;">
                        @foreach(\App\Modules\Stock\Reports\Enums\FilterInvoiceLinkStatus::cases() as $case)
                            <option value="{{ $case->value }}" {{ request('invoice_status') === $case->value ? 'selected' : '' }}>
                                {{ $case->label() }}
                            </option>
                        @endforeach
                    </select>
                </div>
                
                <div style="display: flex; gap: 0.5rem; margin-bottom: 0.15rem;">
                    <button type="submit" class="btn-primary">Filter</button>
                    <a href="{{ route('stock.reports.product-grn-aggregation.index') }}" class="btn-link">Clear</a>
                </div>
            </form>

            <!-- Table Section -->
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Product Details</th>
                            <th>Unit</th>
                            <th class="text-center">GRNs Count</th>
                            <th class="text-right">Total Entry (Base)</th>
                            <th class="text-right">Total Consumed</th>
                            <th class="text-right">Remaining Stock</th>
                            <th style="width: 25%;">Consumption Rate</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($report as $item)
                            <tr class="item-row">
                                <td>
                                    <div class="font-bold" style="color: var(--text); font-size: 1rem;">{{ $item->productName }}</div>
                                    <div class="text-muted">Code: {{ $item->productCode }}</div>
                                </td>
                                <td>
                                    <div>{{ $item->unitName }}</div>
                                    <div class="text-muted" style="font-size: 0.8rem;">Pack: {{ $item->packageSize }}</div>
                                </td>
                                <td class="text-center">
                                    <span class="badge gray">{{ $item->grnsCount }}</span>
                                </td>
                                <td class="text-right font-bold">{{ $item->totalEntryQty }}</td>
                                <td class="text-right" style="color: var(--danger); font-weight: 500;">
                                    {{ $item->totalConsumedQty }}
                                </td>
                                <td class="text-right font-bold" style="color: {{ $item->remainingQtyColor }}; font-size: 1.05rem;">
                                    {{ $item->remainingQty }}
                                </td>
                                <td>
                                    <div class="progress-wrapper">
                                        <div class="progress-bg">
                                            <div class="progress-bar {{ $item->progressBarColorClass }}" style="width: {{ $item->consumptionPercentage }}%;"></div>
                                        </div>
                                        <div class="progress-text">{{ $item->consumptionPercentage }}%</div>
                                    </div>
                                </td>
                                <td>
                                    <span class="{{ $item->statusBadgeClass }}">{{ $item->statusText }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="empty-state">
                                    <svg style="width: 48px; height: 48px; margin: 0 auto 1rem auto; color: var(--text-light);" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path></svg>
                                    <div>No Products found matching the criteria.</div>
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
