{{-- Part Result Offers Partial - Can be loaded via AJAX --}}
<div class="part-result-offers" id="partResultOffersContainer" data-part-number="{{ $part_number }}">
    @foreach($offersData['grouped_offers'] as $qualityGroup)
        {{-- Quality Brand Group --}}
        <div class="quality-group m-card mb-3">
            {{-- Quality Header --}}
            <div class="quality-group-header m-card__header d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-2">
                    @if($qualityGroup['quality_logo'])
                        <img src="{{ $qualityGroup['quality_logo'] }}"
                             alt="{{ $qualityGroup['quality_name'] }}"
                             class="quality-logo"
                             style="height: 24px;">
                    @endif
                    <span class="quality-name fw-bold">{{ $qualityGroup['quality_name'] }}</span>
                    <small class="text-muted">({{ $qualityGroup['offers_count'] }} @lang('offers'))</small>
                </div>
                <span class="quality-price fw-bold">
                    <small class="text-muted fw-normal">@lang('From')</small>
                    <span class="text-success">{{ $qualityGroup['lowest_price_formatted'] }}</span>
                    @if(($qualityGroup['highest_price_formatted'] ?? null) && ($qualityGroup['lowest_price'] ?? 0) != ($qualityGroup['highest_price'] ?? 0))
                        <small class="text-muted fw-normal mx-1">@lang('To')</small>
                        <span class="text-danger">{{ $qualityGroup['highest_price_formatted'] }}</span>
                    @endif
                </span>
            </div>

            <div class="m-card__body">
                {{-- Merchants within Quality --}}
                @foreach($qualityGroup['merchants'] as $merchantGroup)
                    <div class="merchant-group mb-3 {{ !$loop->last ? 'border-bottom pb-3' : '' }}">
                        {{-- Merchant Header --}}
                        <div class="merchant-header d-flex align-items-center gap-2 mb-2">
                            @if($merchantGroup['merchant_photo'])
                                <img src="{{ $merchantGroup['merchant_photo'] }}"
                                     alt="{{ $merchantGroup['merchant_name'] }}"
                                     class="merchant-avatar rounded-circle"
                                     style="width: 32px; height: 32px; object-fit: cover;">
                            @else
                                <div class="merchant-avatar bg-secondary rounded-circle d-flex align-items-center justify-content-center"
                                     style="width: 32px; height: 32px;">
                                    <i class="fas fa-store text-white small"></i>
                                </div>
                            @endif
                            <span class="merchant-name fw-semibold">{{ $merchantGroup['merchant_name'] }}</span>
                            <small class="text-muted">({{ $merchantGroup['offers_count'] }} @lang('branches'))</small>
                        </div>

                        {{-- Branches / Offers --}}
                        <div class="branches-list">
                            @foreach($merchantGroup['branches'] as $branchData)
                                @php
                                    $offer = $branchData['offer'];
                                    $canBuy = $offer['can_buy'] ?? false;
                                    $inStock = $offer['in_stock'] ?? false;
                                    $preordered = $offer['preordered'] ?? false;
                                    $stock = $offer['stock'] ?? 0;
                                    $minQty = $offer['minimum_qty'] ?? 1;
                                    $uniqueId = 'offer_' . ($offer['merchant_item_id'] ?? rand());
                                @endphp

                                <div class="branch-offer d-flex flex-wrap align-items-center gap-3 p-2 rounded {{ $canBuy ? 'bg-light' : 'bg-light opacity-75' }} mb-2">
                                    {{-- Branch Info --}}
                                    <div class="branch-info flex-grow-1">
                                        <div class="d-flex align-items-center gap-2 mb-1">
                                            <i class="fas fa-warehouse text-muted"></i>
                                            <span class="branch-name">{{ $branchData['branch_name'] }}</span>

                                            {{-- Stock Badge --}}
                                            @if($inStock)
                                                <span class="badge bg-success">{{ $stock }} @lang('Available')</span>
                                            @elseif($preordered)
                                                <span class="badge bg-warning text-dark">@lang('Preorder')</span>
                                            @else
                                                <span class="badge bg-secondary">@lang('Out of Stock')</span>
                                            @endif
                                        </div>

                                        {{-- Merchant Photos --}}
                                        @if(!empty($offer['photos']))
                                            <div class="offer-photos d-flex gap-1 mt-1">
                                                @foreach(array_slice($offer['photos'], 0, 4) as $photo)
                                                    <img src="{{ $photo['url'] }}"
                                                         alt=""
                                                         class="offer-photo-thumb rounded"
                                                         style="width: 40px; height: 40px; object-fit: cover; cursor: pointer;"
                                                         loading="lazy">
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>

                                    {{-- Price --}}
                                    <div class="branch-price text-end">
                                        <span class="price-current fw-bold fs-5 text-primary">
                                            {{ $offer['final_price_formatted'] }}
                                        </span>
                                        @if($offer['previous_price_formatted'] ?? null)
                                            <br>
                                            <del class="price-old text-muted small">{{ $offer['previous_price_formatted'] }}</del>
                                            @if(($offer['discount_percentage'] ?? 0) > 0)
                                                <span class="badge bg-danger">-{{ number_format($offer['discount_percentage'], 0) }}%</span>
                                            @endif
                                        @endif
                                    </div>

                                    {{-- Actions --}}
                                    <div class="catalog-offers-branch-actions">
                                        @if($canBuy)
                                            {{-- Add to Cart --}}
                                            <button type="button"
                                                    class="catalog-btn catalog-btn-success m-cart-add"
                                                    data-merchant-item-id="{{ $offer['merchant_item_id'] }}"
                                                    data-merchant-user-id="{{ $offer['user_id'] }}"
                                                    data-catalog-item-id="{{ $catalogItem->id }}"
                                                    data-min-qty="{{ $minQty }}"
                                                    data-stock="{{ $stock }}"
                                                    data-preordered="{{ $preordered ? 1 : 0 }}">
                                                <i class="fas fa-cart-plus"></i>
                                                <span class="d-none d-md-inline">@lang('Add')</span>
                                            </button>
                                        @else
                                            <span class="text-muted">@lang('Unavailable')</span>
                                        @endif

                                        {{-- Shipping Quote --}}
                                        <button type="button"
                                                class="catalog-btn catalog-btn-outline"
                                                data-shipping-quote
                                                data-merchant-user-id="{{ $offer['user_id'] }}"
                                                data-catalog-item-name="{{ $catalogItem->showName() }}"
                                                title="@lang('احسب الشحن')">
                                            <i class="fas fa-truck"></i>
                                        </button>

                                        {{-- Add to Favorites --}}
                                        @auth
                                            <button type="button"
                                                    class="catalog-btn catalog-btn-outline favorite"
                                                    data-href="{{ route('user-favorite-add-merchant', $offer['merchant_item_id']) }}"
                                                    title="@lang('Add to Favorites')">
                                                <i class="far fa-heart"></i>
                                            </button>
                                        @else
                                            <a href="{{ route('user.login') }}"
                                               class="catalog-btn catalog-btn-outline"
                                               title="@lang('Add to Favorites')">
                                                <i class="far fa-heart"></i>
                                            </a>
                                        @endauth

                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endforeach
</div>
