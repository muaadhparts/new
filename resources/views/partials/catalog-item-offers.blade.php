{{-- resources/views/partials/catalog-item-offers.blade.php --}}
{{-- Offers modal content: Grouped by Quality Brand → Merchant → Branch --}}
{{-- Uses catalog-unified.css for styling --}}

<div class="catalog-offers-content" data-catalog-item-id="{{ $catalog_item['id'] ?? '' }}">
    @if(isset($grouped_offers) && count($grouped_offers) > 0)
        {{-- Header with catalog item info --}}
        <div class="catalog-offers-header">
            <div class="d-flex align-items-center gap-3 mb-3">
                @if($catalog_item['photo'] ?? null)
                    <img src="{{ $catalog_item['photo'] }}"
                         alt="{{ $catalog_item['name'] ?? '' }}"
                         class="catalog-offers-thumb"
                         loading="lazy">
                @endif
                <div class="flex-grow-1">
                    <h5 class="catalog-offers-title mb-1">{{ $catalog_item['name'] ?? '' }}</h5>
                    @if($catalog_item['part_number'] ?? null)
                        <code class="text-muted">{{ $catalog_item['part_number'] }}</code>
                    @endif
                </div>
            </div>

            {{-- Summary & Sort --}}
            <div class="catalog-offers-summary d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <span class="catalog-badge catalog-badge-primary">
                        <i class="fas fa-tags"></i>
                        {{ $offers_count ?? 0 }} @lang('offers')
                    </span>
                    @if($lowest_price_formatted ?? null)
                        <span class="catalog-offers-from">
                            <small class="text-muted">@lang('From')</small>
                            <strong class="text-success">{{ $lowest_price_formatted }}</strong>
                            @if(($highest_price_formatted ?? null) && ($lowest_price ?? 0) != ($highest_price ?? 0))
                                <small class="text-muted mx-1">@lang('To')</small>
                                <strong class="text-danger">{{ $highest_price_formatted }}</strong>
                            @endif
                        </span>
                    @endif
                </div>

                {{-- Sort Dropdown --}}
                @if(($offers_count ?? 0) > 1)
                    <div class="catalog-offers-sort d-flex align-items-center gap-2">
                        <select class="form-select form-select-sm"
                                id="offersSort"
                                data-catalog-item-id="{{ $catalog_item['id'] ?? '' }}"
                                style="min-width: 140px;">
                            <option value="price_asc" {{ ($current_sort ?? 'price_asc') === 'price_asc' ? 'selected' : '' }}>
                                @lang('Lowest Price')
                            </option>
                            <option value="price_desc" {{ ($current_sort ?? '') === 'price_desc' ? 'selected' : '' }}>
                                @lang('Highest Price')
                            </option>
                        </select>
                    </div>
                @endif
            </div>
        </div>

        {{-- Grouped Offers --}}
        <div class="catalog-offers-groups">
            @foreach($grouped_offers as $qualityGroup)
                <div class="catalog-offers-quality-group">
                    {{-- Quality Brand Header --}}
                    <div class="catalog-offers-quality-header">
                        <div class="d-flex align-items-center gap-2">
                            @if($qualityGroup['quality_logo'] ?? null)
                                <img src="{{ $qualityGroup['quality_logo'] }}"
                                     alt="{{ $qualityGroup['quality_name'] }}"
                                     class="catalog-offers-quality-logo">
                            @endif
                            <div>
                                <span class="catalog-offers-quality-name">{{ $qualityGroup['quality_name'] }}</span>
                                <small class="text-muted ms-2">
                                    ({{ $qualityGroup['offers_count'] }} @lang('offers'))
                                </small>
                            </div>
                        </div>
                        <span class="catalog-offers-quality-price">
                            <small class="text-muted">@lang('From')</small>
                            <span class="text-success">{{ $qualityGroup['lowest_price_formatted'] }}</span>
                            @if(($qualityGroup['highest_price_formatted'] ?? null) && ($qualityGroup['lowest_price'] ?? 0) != ($qualityGroup['highest_price'] ?? 0))
                                <small class="text-muted mx-1">@lang('To')</small>
                                <span class="text-danger">{{ $qualityGroup['highest_price_formatted'] }}</span>
                            @endif
                        </span>
                    </div>

                    {{-- Merchants within Quality --}}
                    <div class="catalog-offers-merchants">
                        @foreach($qualityGroup['merchants'] as $merchantGroup)
                            <div class="catalog-offers-merchant-group">
                                {{-- Merchant Header --}}
                                <div class="catalog-offers-merchant-header">
                                    <div class="d-flex align-items-center gap-2">
                                        @if($merchantGroup['merchant_photo'] ?? null)
                                            <img src="{{ $merchantGroup['merchant_photo'] }}"
                                                 alt="{{ $merchantGroup['merchant_name'] }}"
                                                 class="catalog-offers-merchant-avatar">
                                        @else
                                            <div class="catalog-offers-merchant-avatar catalog-offers-merchant-avatar--placeholder">
                                                <i class="fas fa-store"></i>
                                            </div>
                                        @endif
                                        <span class="catalog-offers-merchant-name">{{ $merchantGroup['merchant_name'] }}</span>
                                    </div>
                                </div>

                                {{-- Branches within Merchant --}}
                                <div class="catalog-offers-branches">
                                    @foreach($merchantGroup['branches'] as $branchData)
                                        @php $offer = $branchData['offer']; $canBuy = $offer['can_buy'] ?? false; $inStock = $offer['in_stock'] ?? false; $preordered = $offer['preordered'] ?? false; @endphp
                                        @php $stock = $offer['stock'] ?? 0; $minQty = $offer['minimum_qty'] ?? 1; $uniqueId = 'offer_' . ($offer['merchant_item_id'] ?? rand()); @endphp

                                        <div class="catalog-offers-branch {{ $canBuy ? 'catalog-offers-branch--available' : 'catalog-offers-branch--unavailable' }}">
                                            {{-- Branch Info --}}
                                            <div class="catalog-offers-branch-info">
                                                <span class="catalog-offers-branch-name">
                                                    <i class="fas fa-warehouse"></i>
                                                    {{ $branchData['branch_name'] }}
                                                </span>

                                                {{-- Stock Badge --}}
                                                @if($inStock)
                                                    <span class="catalog-badge catalog-badge-success">
                                                        {{ $stock }} @lang('Available')
                                                    </span>
                                                @elseif($preordered)
                                                    <span class="catalog-badge catalog-badge-warning">
                                                        @lang('Preorder')
                                                    </span>
                                                @else
                                                    <span class="catalog-badge catalog-badge-secondary">
                                                        @lang('Out of Stock')
                                                    </span>
                                                @endif
                                            </div>

                                            {{-- Price --}}
                                            <div class="catalog-offers-branch-price">
                                                <span class="catalog-offers-price-current">
                                                    {{ $offer['final_price_formatted'] }}
                                                </span>
                                                @if($offer['previous_price_formatted'] ?? null)
                                                    <del class="catalog-offers-price-old">
                                                        {{ $offer['previous_price_formatted'] }}
                                                    </del>
                                                    @if(($offer['discount_percentage'] ?? 0) > 0)
                                                        <span class="catalog-badge catalog-badge-danger">
                                                            -{{ number_format($offer['discount_percentage'], 0) }}%
                                                        </span>
                                                    @endif
                                                @endif
                                            </div>

                                            {{-- Merchant Photos (if any) --}}
                                            @if(!empty($offer['photos']))
                                                <div class="catalog-offers-photos">
                                                    @foreach(array_slice($offer['photos'], 0, 4) as $photo)
                                                        <img src="{{ $photo['url'] }}"
                                                             alt=""
                                                             class="catalog-offers-photo-thumb"
                                                             loading="lazy">
                                                    @endforeach
                                                </div>
                                            @endif

                                            {{-- Actions --}}
                                            <div class="catalog-offers-branch-actions">
                                                @if($canBuy)
                                                    {{-- Add to Cart Button --}}
                                                    <button type="button"
                                                            class="catalog-btn catalog-btn-success m-cart-add"
                                                            data-merchant-item-id="{{ $offer['merchant_item_id'] }}"
                                                            data-merchant-user-id="{{ $offer['user_id'] }}"
                                                            data-catalog-item-id="{{ $catalog_item['id'] }}"
                                                            data-min-qty="{{ $minQty }}"
                                                            data-stock="{{ $stock }}"
                                                            data-preordered="{{ $preordered ? 1 : 0 }}">
                                                        <i class="fas fa-cart-plus"></i>
                                                        <span class="d-none d-md-inline">@lang('Add')</span>
                                                    </button>
                                                @else
                                                    <span class="text-muted">@lang('Unavailable')</span>
                                                @endif

                                                {{-- Shipping Quote Button --}}
                                                @if(($catalog_item['weight'] ?? 0) > 0 && ($offer['branch_id'] ?? null))
                                                <button type="button"
                                                        class="catalog-btn catalog-btn-outline"
                                                        data-shipping-quote
                                                        data-merchant-id="{{ $offer['user_id'] }}"
                                                        data-branch-id="{{ $offer['branch_id'] }}"
                                                        data-weight="{{ $catalog_item['weight'] }}"
                                                        data-catalog-item-name="{{ $catalog_item['name'] ?? '' }}"
                                                        title="@lang('احسب الشحن')">
                                                    <i class="fas fa-truck"></i>
                                                </button>
                                                @endif

                                                {{-- Add to Favorites Button --}}
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

                                                {{-- View Product Button --}}
                                                <a href="{{ route('front.part-result', $catalog_item['part_number'] ?? '') }}"
                                                   class="catalog-btn catalog-btn-outline"
                                                   title="@lang('View Product')">
                                                    <i class="fas fa-eye"></i>
                                                </a>
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
    @else
        {{-- No Offers --}}
        <div class="catalog-empty">
            <i class="fas fa-box-open"></i>
            <p>@lang('No offers available')</p>
        </div>
    @endif
</div>
