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
            <form class="filter-form" method="GET" action="{{ route('stock.reports.grn-consumption-items.index') }}" style="flex-wrap: wrap; align-items: center;">
                <div class="autocomplete-container">
                    <input type="text" class="autocomplete-input" placeholder="Filter by Product..." value="{{ $selectedProduct->name ?? '' }}" autocomplete="off">
                    <input type="hidden" name="product_id" value="{{ request('product_id') }}">
                    <div class="clear-autocomplete">&times;</div>
                    <div class="autocomplete-dropdown"></div>
                </div>
                <input type="text" name="search" placeholder="Search product, GRN or notes..." value="{{ request('search') }}">
                <input type="text" name="older_than_days" placeholder="Older than X days" value="{{ request('older_than_days') }}" style="min-width: 150px;">
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
                <a href="{{ route('stock.reports.grn-consumption-items.index') }}" class="btn-link">Clear</a>
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
                                    <svg style="width: 48px; height: 48px; margin: 0 auto 1rem auto; color: #cbd5e1;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path></svg>
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
