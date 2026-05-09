@extends('stock::layouts.report')

@section('title', 'Receipts & Invoices')

@section('content')
        <div class="header" style="display: flex; justify-content: space-between; align-items: flex-end;">
            <div>
                <h1>Receipts & Invoices</h1>
                <p>Track exactly what products came in each receipt and how much is left.</p>
            </div>
            <div style="font-size: 1.1rem; font-weight: 600; color: var(--primary);">
                Total GRNs: {{ $report->total() }}
            </div>
        </div>

        <div class="card">
            <form class="filter-form" method="GET" action="{{ route('stock.reports.grn-consumption.index') }}" style="align-items: flex-end;">
                <div class="form-group">
                    <label class="form-label">Store <span style="color: var(--danger);">*</span></label>
                    <select name="store_id" style="min-width: 150px;" required>
                        <option value="">-- Select Store --</option>
                        @foreach($stores as $store)
                            <option value="{{ $store->id }}" {{ request('store_id') == $store->id ? 'selected' : '' }}>
                                {{ $store->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

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

                <div class="form-group">
                    <label class="form-label">Sort By</label>
                    <select name="sort_by" style="min-width: 150px;">
                        <option value="">Latest</option>
                        <option value="remaining_desc" {{ request('sort_by') === 'remaining_desc' ? 'selected' : '' }}>Highest Remaining</option>
                        <option value="remaining_asc" {{ request('sort_by') === 'remaining_asc' ? 'selected' : '' }}>Lowest Remaining</option>
                    </select>
                </div>

                <div style="display: flex; gap: 0.5rem; margin-bottom: 0.15rem;">
                    <button type="submit" class="btn-primary">Filter</button>
                    <a href="{{ route('stock.reports.grn-consumption.index') }}" class="btn-link">Clear</a>
                </div>
            </form>

            <!-- Table Section -->
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Product Details</th>
                            <th>Unit</th>
                            <th class="text-right">Entry Qty</th>
                            <th class="text-right">
                                @php
                                    $currentSort = request('sort_by');
                                    $nextSort = $currentSort === 'remaining_desc' ? 'remaining_asc' : 'remaining_desc';
                                    $sortIcon = $currentSort === 'remaining_desc' ? '↓' : ($currentSort === 'remaining_asc' ? '↑' : '↕');
                                @endphp
                                <a href="{{ request()->fullUrlWithQuery(['sort_by' => $nextSort]) }}" style="color: inherit; text-decoration: none;">
                                    Remaining Qty {{ $sortIcon }}
                                </a>
                            </th>
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
                                    <svg style="width: 48px; height: 48px; margin: 0 auto 1rem auto; color: var(--text-light);" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path></svg>
                                    @if(empty(request('store_id')))
                                        <div>Please select a Store to view the report.</div>
                                    @else
                                        <div>No GRNs found matching the criteria.</div>
                                    @endif
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
