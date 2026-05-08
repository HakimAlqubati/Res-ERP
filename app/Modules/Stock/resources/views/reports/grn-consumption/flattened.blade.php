@extends('stock::layouts.report')

@section('title', 'Detailed Items')

@section('content')
        <div class="header" style="display: flex; justify-content: space-between; align-items: flex-end;">
            <div>
                <h1>Detailed Items Ledger</h1>
                <p>A simple list of every single item received across all receipts.</p>
            </div>
            <div style="font-size: 1.1rem; font-weight: 600; color: var(--primary);">
                Total GRNs: {{ $report->total() }}
            </div>
        </div>

        <div class="card">
            <!-- Filter Section -->
            <form class="filter-form" method="GET" action="{{ route('stock.reports.grn-consumption-items.index') }}" style="align-items: flex-end;">
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
                    <label class="form-label">GRN Number</label>
                    <input type="text" name="grn_number" placeholder="e.g. GRN-001" value="{{ request('grn_number') }}">
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
                    <label class="form-label">Dead Stock (Days)</label>
                    <input type="text" name="older_than_days" placeholder="Older than..." value="{{ request('older_than_days') }}" style="min-width: 120px;">
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
                    <a href="{{ route('stock.reports.grn-consumption-items.index') }}" class="btn-link">Clear</a>
                </div>
            </form>

            <!-- Table Section -->
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Unit</th>
                            <th>Source GRN</th>
                            <th>Entry Date</th>
                            <th class="text-right">Entry Qty</th>
                            <th class="text-right">Remaining Qty</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($report as $item)
                            <tr class="item-row">
                                <td class="font-bold">{{ $item->product_name }}</td>
                                <td>
                                    <div>{{ $item->unit_name }}</div>
                                    <div class="text-muted" style="font-size: 0.8rem;">Pack: {{ $item->package_size }}</div>
                                </td>
                                <td>
                                    <div class="font-bold">#{{ $item->grn_number }}</div>
                                    @if($item->is_linked_to_invoice)
                                        <div class="text-muted">Inv: {{ $item->invoice_number }}</div>
                                    @endif
                                </td>
                                <td>{{ $item->formatted_entry_date }}</td>
                                <td class="text-right font-bold">{{ $item->entry_quantity }}</td>
                                <td class="text-right font-bold" style="color: {{ $item->remaining_quantity_color }};">
                                    {{ $item->remaining_quantity }}
                                </td>
                                <td>
                                    <span class="{{ $item->status_badge_class }}">{{ $item->status_text }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="empty-state">
                                    <svg style="width: 48px; height: 48px; margin: 0 auto 1rem auto; color: var(--text-light);" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path></svg>
                                    <div>No items found matching the criteria.</div>
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
