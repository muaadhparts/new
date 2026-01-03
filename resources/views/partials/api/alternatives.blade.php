{{-- resources/views/partials/api/alternatives.blade.php --}}
{{-- API-based alternatives partial (No Livewire) --}}
{{-- Uses catalog-unified.css for styling --}}

<div class="catalog-modal-content ill-alt">
    @if($alternatives && $alternatives->count() > 0)
        {{-- Header --}}
        <div class="catalog-section-header">
            <h5>
                <i class="fas fa-exchange-alt"></i>
                @lang('labels.substitutions')
            </h5>
            <span class="badge bg-secondary">{{ $alternatives->count() }} @lang('items')</span>
        </div>

        {{-- Desktop Table --}}
        <div class="d-none d-md-block">
            <div class="table-responsive">
                <table class="table table-hover align-middle catalog-table">
                    <thead>
                        <tr>
                            <th>@lang('Part Number')</th>
                            <th>@lang('Name')</th>
                            <th>@lang('Brand')</th>
                            <th>@lang('Quality')</th>
                            <th>@lang('Merchant')</th>
                            <th class="text-center">@lang('Stock')</th>
                            <th class="text-center">@lang('Qty')</th>
                            <th class="text-end">@lang('Price')</th>
                            <th class="text-center" style="width: 100px;">@lang('Action')</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($alternatives as $idx => $mp)
                            @php
                                $catalogItem = $mp->catalogItem;
                                $vp = method_exists($mp,'merchantSizePrice') ? (float)$mp->merchantSizePrice() : (float)$mp->price;
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

                            <tr class="{{ $highlight ? 'row-available' : 'row-unavailable' }}">
                                <td><code class="fw-bold text-dark">{{ $catalogItem->sku }}</code></td>
                                <td class="text-truncate" style="max-width: 200px;">{{ getLocalizedCatalogItemName($catalogItem) }}</td>
                                <td>
                                    @if($catalogItem->brand)
                                        <span class="catalog-quality-badge">
                                            @if($catalogItem->brand->photo_url)
                                                <img src="{{ $catalogItem->brand->photo_url }}" alt="{{ getLocalizedBrandName($catalogItem->brand) }}" class="catalog-quality-badge__logo">
                                            @endif
                                            {{ Str::ucfirst(getLocalizedBrandName($catalogItem->brand)) }}
                                        </span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    @if($qualityBrand)
                                        <span class="catalog-quality-badge">
                                            @if($qualityBrand->logo)
                                                <img src="{{ $qualityBrand->logo_url }}" alt="{{ getLocalizedQualityName($qualityBrand) }}" class="catalog-quality-badge__logo">
                                            @endif
                                            {{ getLocalizedQualityName($qualityBrand) }}
                                        </span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td><span class="small">{{ $mp->user ? getLocalizedShopName($mp->user) : '-' }}</span></td>
                                <td class="text-center">
                                    @if($inStock)
                                        <span class="catalog-badge catalog-badge-success">{{ $mp->stock }}</span>
                                    @elseif($preordered)
                                        <span class="catalog-badge catalog-badge-warning">@lang('Preorder')</span>
                                    @else
                                        <span class="catalog-badge catalog-badge-secondary">0</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($canBuy)
                                        <div class="catalog-qty-control">
                                            <button type="button" class="catalog-qty-btn qty-minus" data-target="{{ $uniqueId }}" data-min="{{ $minQty }}">-</button>
                                            <input type="text" class="catalog-qty-input qty-input" id="qty_{{ $uniqueId }}" value="{{ $minQty }}" readonly data-min="{{ $minQty }}" data-stock="{{ $stock }}" data-preordered="{{ $preordered }}">
                                            <button type="button" class="catalog-qty-btn qty-plus" data-target="{{ $uniqueId }}" data-stock="{{ $stock }}" data-preordered="{{ $preordered }}">+</button>
                                        </div>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <span class="fw-bold {{ $hasPrice ? 'text-success' : 'text-muted' }}">
                                        {{ $hasPrice ? \App\Models\CatalogItem::convertPrice($vp) : '-' }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm">
                                        <button type="button" class="btn btn-outline-primary quick-view" data-id="{{ $catalogItem->id }}" data-url="{{ route('modal.quickview', ['id' => $catalogItem->id]) }}?user={{ $mp->user_id }}" title="@lang('Quick View')">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        @if($canBuy)
                                            <button type="button" class="btn btn-success alt-add-to-cart" data-id="{{ $catalogItem->id }}" data-mp-id="{{ $mp->id }}" data-user="{{ $mp->user_id }}" data-qty-id="{{ $uniqueId }}" data-addnum-url="{{ route('merchant.cart.add', $mp->id) }}" title="@lang('Add To Cart')">
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
        <div class="d-block d-md-none catalog-cards">
            @foreach($alternatives as $idx => $mp)
                @php
                    $catalogItem = $mp->catalogItem;
                    $vp = method_exists($mp,'merchantSizePrice') ? (float)$mp->merchantSizePrice() : (float)$mp->price;
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

                <div class="catalog-card {{ $highlight ? 'card-available' : 'card-unavailable' }}">
                    <div class="catalog-card-header">
                        <code class="fw-bold">{{ $catalogItem->sku }}</code>
                        @if($inStock)
                            <span class="catalog-badge catalog-badge-success">{{ $mp->stock }} @lang('In Stock')</span>
                        @elseif($preordered)
                            <span class="catalog-badge catalog-badge-warning">@lang('Preorder')</span>
                        @else
                            <span class="catalog-badge catalog-badge-secondary">@lang('Out of Stock')</span>
                        @endif
                    </div>

                    <div class="catalog-card-body">
                        <div class="catalog-card-title">{{ getLocalizedCatalogItemName($catalogItem) }}</div>

                        <div class="catalog-card-details">
                            @if($catalogItem->brand)
                                <div class="catalog-card-detail">
                                    <span class="catalog-card-label">@lang('Brand'):</span>
                                    <span class="catalog-quality-badge">
                                        @if($catalogItem->brand->photo_url)
                                            <img src="{{ $catalogItem->brand->photo_url }}" alt="{{ getLocalizedBrandName($catalogItem->brand) }}" class="catalog-quality-badge__logo">
                                        @endif
                                        {{ getLocalizedBrandName($catalogItem->brand) }}
                                    </span>
                                </div>
                            @endif

                            @if($qualityBrand)
                                <div class="catalog-card-detail">
                                    <span class="catalog-card-label">@lang('Quality'):</span>
                                    <span class="catalog-quality-badge">
                                        @if($qualityBrand->logo)
                                            <img src="{{ $qualityBrand->logo_url }}" alt="{{ getLocalizedQualityName($qualityBrand) }}" class="catalog-quality-badge__logo">
                                        @endif
                                        {{ getLocalizedQualityName($qualityBrand) }}
                                    </span>
                                </div>
                            @endif

                            @if($mp->user)
                                <div class="catalog-card-detail">
                                    <span class="catalog-card-label">@lang('Merchant'):</span>
                                    <span>{{ getLocalizedShopName($mp->user) }}</span>
                                </div>
                            @endif

                            @if($canBuy)
                                <div class="catalog-card-detail">
                                    <span class="catalog-card-label">@lang('Qty'):</span>
                                    <div class="catalog-qty-control">
                                        <button type="button" class="catalog-qty-btn qty-minus" data-target="{{ $uniqueId }}" data-min="{{ $minQty }}">-</button>
                                        <input type="text" class="catalog-qty-input qty-input" id="qty_{{ $uniqueId }}" value="{{ $minQty }}" readonly data-min="{{ $minQty }}" data-stock="{{ $stock }}" data-preordered="{{ $preordered }}">
                                        <button type="button" class="catalog-qty-btn qty-plus" data-target="{{ $uniqueId }}" data-stock="{{ $stock }}" data-preordered="{{ $preordered }}">+</button>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="catalog-card-footer">
                        <div class="catalog-card-price {{ $hasPrice ? 'text-success' : 'text-muted' }}">
                            {{ $hasPrice ? \App\Models\CatalogItem::convertPrice($vp) : __('Price not available') }}
                        </div>
                        <div class="catalog-card-actions">
                            <button type="button" class="catalog-btn catalog-btn-outline quick-view" data-id="{{ $catalogItem->id }}" data-url="{{ route('modal.quickview', ['id' => $catalogItem->id]) }}?user={{ $mp->user_id }}">
                                <i class="fas fa-eye"></i>
                            </button>
                            @if($canBuy)
                                <button type="button" class="catalog-btn catalog-btn-success alt-add-to-cart" data-id="{{ $catalogItem->id }}" data-mp-id="{{ $mp->id }}" data-user="{{ $mp->user_id }}" data-qty-id="{{ $uniqueId }}" data-addnum-url="{{ route('merchant.cart.add', $mp->id) }}">
                                    <i class="fas fa-cart-plus"></i> @lang('Add')
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

    @else
        <div class="catalog-empty">
            <i class="fas fa-box-open"></i>
            <p>@lang('No alternatives found')</p>
        </div>
    @endif
</div>

{{-- JavaScript moved to illustrated.js for proper event delegation --}}
