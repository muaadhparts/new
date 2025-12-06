{{-- resources/views/partials/api/alternatives.blade.php --}}
{{-- API-based alternatives partial (No Livewire) --}}

<div class="modal-content-wrapper ill-alt">
    @if($alternatives && $alternatives->count() > 0)
        {{-- Header --}}
        <div class="modal-section-header">
            <h5 class="mb-0 fw-bold text-primary">
                <i class="fas fa-exchange-alt me-2"></i>
                @lang('labels.substitutions')
            </h5>
            <span class="badge bg-secondary">{{ $alternatives->count() }} @lang('items')</span>
        </div>

        {{-- Desktop Table --}}
        <div class="d-none d-md-block">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 modal-table">
                    <thead class="table-light">
                        <tr>
                            <th class="text-nowrap">@lang('Part Number')</th>
                            <th>@lang('Name')</th>
                            <th class="text-nowrap">@lang('Brand')</th>
                            <th class="text-nowrap">@lang('Quality')</th>
                            <th class="text-nowrap">@lang('Vendor')</th>
                            <th class="text-center text-nowrap">@lang('Stock')</th>
                            <th class="text-center text-nowrap">@lang('Qty')</th>
                            <th class="text-end text-nowrap">@lang('Price')</th>
                            <th class="text-center" style="width: 100px;">@lang('Action')</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($alternatives as $idx => $mp)
                            @php
                                $product = $mp->product;
                                $vp = method_exists($mp,'vendorSizePrice') ? (float)$mp->vendorSizePrice() : (float)$mp->price;
                                $inStock = ($mp->stock ?? 0) > 0;
                                $hasPrice = $vp > 0;
                                $highlight = ($inStock || $mp->preordered) && $hasPrice;
                                $qualityBrand = $mp->qualityBrand;
                                $minQty = (int)($mp->minimum_qty ?? 1);
                                if ($minQty < 1) $minQty = 1;
                                $stock = (int)($mp->stock ?? 0);
                                $preordered = (int)($mp->preordered ?? 0);
                                $canBuy = ($inStock || $preordered) && $hasPrice;
                                $uniqueId = 'alt_' . $mp->id . '_' . $idx;
                            @endphp

                            <tr class="{{ $highlight ? 'table-row-available' : 'table-row-unavailable' }}">
                                <td><code class="fw-bold text-dark">{{ $product->sku }}</code></td>
                                <td class="text-truncate" style="max-width: 200px;">{{ getLocalizedProductName($product) }}</td>
                                <td>
                                    @if($product->brand)
                                        <span class="badge bg-light text-dark border">{{ Str::ucfirst(getLocalizedBrandName($product->brand)) }}</span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    @if($qualityBrand)
                                        <div class="d-flex align-items-center gap-1">
                                            @if($qualityBrand->logo)
                                                <img src="{{ $qualityBrand->logo_url }}" alt="{{ getLocalizedQualityName($qualityBrand) }}" class="quality-logo" style="max-height: 22px; max-width: 50px; object-fit: contain;">
                                            @endif
                                            <span class="small">{{ getLocalizedQualityName($qualityBrand) }}</span>
                                        </div>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td><span class="small">{{ $mp->user ? ($mp->user->shop_name ?: $mp->user->name) : '-' }}</span></td>
                                <td class="text-center">
                                    @if($inStock)
                                        <span class="badge bg-success">{{ $mp->stock }}</span>
                                    @elseif($preordered)
                                        <span class="badge bg-warning text-dark">@lang('Preorder')</span>
                                    @else
                                        <span class="badge bg-secondary">0</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($canBuy)
                                        <div class="qty-control d-inline-flex align-items-center">
                                            <button type="button" class="qty-btn qty-minus" data-target="{{ $uniqueId }}" data-min="{{ $minQty }}">-</button>
                                            <input type="text" class="qty-input" id="qty_{{ $uniqueId }}" value="{{ $minQty }}" readonly data-min="{{ $minQty }}" data-stock="{{ $stock }}" data-preordered="{{ $preordered }}">
                                            <button type="button" class="qty-btn qty-plus" data-target="{{ $uniqueId }}" data-stock="{{ $stock }}" data-preordered="{{ $preordered }}">+</button>
                                        </div>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <span class="fw-bold {{ $hasPrice ? 'text-success' : 'text-muted' }}">
                                        {{ $hasPrice ? \App\Models\Product::convertPrice($vp) : '-' }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm">
                                        <button type="button" class="btn btn-outline-primary quick-view" data-id="{{ $product->id }}" data-url="{{ route('modal.quickview', ['id' => $product->id]) }}?user={{ $mp->user_id }}" title="@lang('Quick View')">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        @if($canBuy)
                                            <button type="button" class="btn btn-success alt-add-to-cart" data-id="{{ $product->id }}" data-mp-id="{{ $mp->id }}" data-user="{{ $mp->user_id }}" data-qty-id="{{ $uniqueId }}" data-addnum-url="{{ route('merchant.cart.add', $mp->id) }}" title="@lang('Add To Cart')">
                                                <i class="fas fa-cart-plus"></i>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Mobile Cards --}}
        <div class="d-block d-md-none modal-cards">
            @foreach($alternatives as $idx => $mp)
                @php
                    $product = $mp->product;
                    $vp = method_exists($mp,'vendorSizePrice') ? (float)$mp->vendorSizePrice() : (float)$mp->price;
                    $inStock = ($mp->stock ?? 0) > 0;
                    $hasPrice = $vp > 0;
                    $highlight = ($inStock || $mp->preordered) && $hasPrice;
                    $qualityBrand = $mp->qualityBrand;
                    $minQty = (int)($mp->minimum_qty ?? 1);
                    if ($minQty < 1) $minQty = 1;
                    $stock = (int)($mp->stock ?? 0);
                    $preordered = (int)($mp->preordered ?? 0);
                    $canBuy = ($inStock || $preordered) && $hasPrice;
                    $uniqueId = 'altm_' . $mp->id . '_' . $idx;
                @endphp

                <div class="modal-card {{ $highlight ? 'card-available' : 'card-unavailable' }}">
                    <div class="modal-card-header">
                        <code class="fw-bold">{{ $product->sku }}</code>
                        @if($inStock)
                            <span class="badge bg-success">{{ $mp->stock }} @lang('In Stock')</span>
                        @elseif($preordered)
                            <span class="badge bg-warning text-dark">@lang('Preorder')</span>
                        @else
                            <span class="badge bg-secondary">@lang('Out of Stock')</span>
                        @endif
                    </div>

                    <div class="modal-card-body">
                        <div class="modal-card-title">{{ getLocalizedProductName($product) }}</div>

                        <div class="modal-card-details">
                            @if($product->brand)
                                <div class="modal-detail">
                                    <span class="modal-label">@lang('Brand'):</span>
                                    <span>{{ getLocalizedBrandName($product->brand) }}</span>
                                </div>
                            @endif

                            @if($qualityBrand)
                                <div class="modal-detail">
                                    <span class="modal-label">@lang('Quality'):</span>
                                    <div class="d-inline-flex align-items-center gap-1">
                                        @if($qualityBrand->logo)
                                            <img src="{{ $qualityBrand->logo_url }}" alt="{{ getLocalizedQualityName($qualityBrand) }}" class="quality-logo" style="max-height: 18px; max-width: 40px; object-fit: contain;">
                                        @endif
                                        <span>{{ getLocalizedQualityName($qualityBrand) }}</span>
                                    </div>
                                </div>
                            @endif

                            @if($mp->user)
                                <div class="modal-detail">
                                    <span class="modal-label">@lang('Vendor'):</span>
                                    <span>{{ $mp->user->shop_name ?: $mp->user->name }}</span>
                                </div>
                            @endif

                            @if($canBuy)
                                <div class="modal-detail">
                                    <span class="modal-label">@lang('Qty'):</span>
                                    <div class="qty-control d-inline-flex align-items-center">
                                        <button type="button" class="qty-btn qty-minus" data-target="{{ $uniqueId }}" data-min="{{ $minQty }}">-</button>
                                        <input type="text" class="qty-input" id="qty_{{ $uniqueId }}" value="{{ $minQty }}" readonly data-min="{{ $minQty }}" data-stock="{{ $stock }}" data-preordered="{{ $preordered }}">
                                        <button type="button" class="qty-btn qty-plus" data-target="{{ $uniqueId }}" data-stock="{{ $stock }}" data-preordered="{{ $preordered }}">+</button>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="modal-card-footer">
                        <div class="card-price {{ $hasPrice ? 'text-success' : 'text-muted' }}">
                            {{ $hasPrice ? \App\Models\Product::convertPrice($vp) : __('Price not available') }}
                        </div>
                        <div class="card-actions">
                            <button type="button" class="btn btn-sm btn-outline-primary quick-view" data-id="{{ $product->id }}" data-url="{{ route('modal.quickview', ['id' => $product->id]) }}?user={{ $mp->user_id }}">
                                <i class="fas fa-eye"></i>
                            </button>
                            @if($canBuy)
                                <button type="button" class="btn btn-sm btn-success alt-add-to-cart" data-id="{{ $product->id }}" data-mp-id="{{ $mp->id }}" data-user="{{ $mp->user_id }}" data-qty-id="{{ $uniqueId }}" data-addnum-url="{{ route('merchant.cart.add', $mp->id) }}">
                                    <i class="fas fa-cart-plus"></i> @lang('Add')
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

    @else
        <div class="modal-empty">
            <i class="fas fa-box-open fa-3x text-muted mb-3"></i>
            <p class="text-muted mb-0">@lang('No alternatives found')</p>
        </div>
    @endif
</div>

<style>
/* ========== Unified Modal Styles ========== */
.modal-content-wrapper {
    max-height: 70vh;
    overflow-y: auto;
    padding: 0;
}

.modal-section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 15px;
    background: #f8f9fa;
    border-bottom: 1px solid #e0e0e0;
    margin-bottom: 0;
    position: sticky;
    top: 0;
    z-index: 10;
}

/* Desktop Table */
.modal-table {
    font-size: 0.9rem;
    margin-bottom: 0;
}

.modal-table th {
    font-weight: 600;
    font-size: 0.8rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: #555;
    border-bottom: 2px solid #dee2e6;
    padding: 10px 12px;
    background: #f8f9fa;
}

.modal-table td {
    padding: 10px 12px;
    vertical-align: middle;
}

.modal-table tbody tr {
    transition: background-color 0.15s ease;
}

.modal-table tbody tr:hover {
    background-color: #f8f9fa;
}

.table-row-available {
    background-color: #f0fff4;
}

.table-row-available:hover {
    background-color: #e6ffed !important;
}

.table-row-unavailable {
    opacity: 0.7;
}

/* Quantity Control */
.qty-control {
    border: 1px solid #ddd;
    border-radius: 4px;
    overflow: hidden;
}

.qty-btn {
    width: 26px;
    height: 26px;
    border: none;
    background: #f5f5f5;
    cursor: pointer;
    font-weight: bold;
    font-size: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: background 0.2s;
}

.qty-btn:hover {
    background: #e0e0e0;
}

.qty-input {
    width: 36px;
    height: 26px;
    text-align: center;
    border: none;
    font-weight: 600;
    font-size: 13px;
    background: #fff;
}

/* Mobile Cards */
.modal-cards {
    padding: 10px;
}

.modal-card {
    background: #fff;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    margin-bottom: 10px;
    overflow: hidden;
    transition: box-shadow 0.15s ease;
}

.modal-card:active {
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.modal-card.card-available {
    border-color: #28a745;
    border-width: 1px 1px 1px 4px;
}

.modal-card.card-unavailable {
    opacity: 0.8;
    background: #f9f9f9;
}

.modal-card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px 12px;
    background: #f8f9fa;
    border-bottom: 1px solid #e0e0e0;
}

.modal-card-body {
    padding: 12px;
}

.modal-card-title {
    font-weight: 600;
    font-size: 0.95rem;
    margin-bottom: 8px;
    color: #333;
}

.modal-card-details {
    font-size: 0.85rem;
}

.modal-detail {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 4px 0;
    border-bottom: 1px dashed #eee;
}

.modal-detail:last-child {
    border-bottom: none;
}

.modal-label {
    color: #666;
    font-weight: 500;
}

.modal-card-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px 12px;
    background: #f8f9fa;
    border-top: 1px solid #e0e0e0;
}

.card-price {
    font-weight: 700;
    font-size: 1.1rem;
}

.card-actions {
    display: flex;
    gap: 6px;
}

/* Empty State */
.modal-empty {
    text-align: center;
    padding: 40px 20px;
    color: #999;
}

/* Scrollbar */
.modal-content-wrapper::-webkit-scrollbar {
    width: 6px;
}

.modal-content-wrapper::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 3px;
}

.modal-content-wrapper::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 3px;
}

.modal-content-wrapper::-webkit-scrollbar-thumb:hover {
    background: #a1a1a1;
}

/* RTL Support */
[dir="rtl"] .modal-detail {
    flex-direction: row-reverse;
}

[dir="rtl"] .modal-section-header {
    flex-direction: row-reverse;
}

[dir="rtl"] .modal-card.card-available {
    border-width: 1px 4px 1px 1px;
}
</style>

{{-- JavaScript moved to illustrated.js for proper event delegation --}}
