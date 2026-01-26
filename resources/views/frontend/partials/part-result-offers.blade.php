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
                                <div class="branch-offer d-flex flex-wrap align-items-center gap-3 p-2 rounded {{ $branchData['offer']['can_buy'] ? 'bg-light' : 'bg-light opacity-75' }} mb-2">
                                    {{-- Branch Info --}}
                                    <div class="branch-info flex-grow-1">
                                        <div class="d-flex align-items-center gap-2 mb-1">
                                            <i class="fas fa-warehouse text-muted"></i>
                                            <span class="branch-name">{{ $branchData['branch_name'] }}</span>

                                            {{-- Stock Badge --}}
                                            @if($branchData['offer']['in_stock'])
                                                <span class="badge bg-success">{{ $branchData['offer']['stock'] }} @lang('Available')</span>
                                            @elseif($branchData['offer']['preordered'])
                                                <span class="badge bg-warning text-dark">@lang('Preorder')</span>
                                            @else
                                                <span class="badge bg-secondary">@lang('Out of Stock')</span>
                                            @endif
                                        </div>

                                        {{-- Merchant Photos --}}
                                        @if(!empty($branchData['offer']['photos']))
                                            <div class="offer-photos d-flex gap-1 mt-1">
                                                @foreach(array_slice($branchData['offer']['photos'], 0, 4) as $photo)
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
                                            {{ $branchData['offer']['final_price_formatted'] }}
                                        </span>
                                        @if($branchData['offer']['previous_price_formatted'])
                                            <br>
                                            <del class="price-old text-muted small">{{ $branchData['offer']['previous_price_formatted'] }}</del>
                                            @if($branchData['offer']['discount_percentage'] > 0)
                                                <span class="badge bg-danger">-{{ $branchData['offer']['discount_percentage_formatted'] }}</span>
                                            @endif
                                        @endif
                                    </div>

                                    {{-- Actions --}}
                                    <div class="catalog-offers-branch-actions">
                                        @if($branchData['offer']['can_buy'])
                                            {{-- Add to Cart --}}
                                            <button type="button"
                                                    class="catalog-btn catalog-btn-success m-cart-add"
                                                    data-merchant-item-id="{{ $branchData['offer']['merchant_item_id'] }}"
                                                    data-merchant-user-id="{{ $branchData['offer']['user_id'] }}"
                                                    data-catalog-item-id="{{ $catalogItem->id }}"
                                                    data-min-qty="{{ $branchData['offer']['minimum_qty'] }}"
                                                    data-stock="{{ $branchData['offer']['stock'] }}"
                                                    data-preordered="{{ $branchData['offer']['preordered'] ? 1 : 0 }}">
                                                <i class="fas fa-cart-plus"></i>
                                                <span class="d-none d-md-inline">@lang('Add')</span>
                                            </button>
                                        @else
                                            <span class="text-muted">@lang('Unavailable')</span>
                                        @endif

                                        {{-- Shipping Quote --}}
                                        @if($catalogItem->weight > 0 && $branchData['offer']['branch_id'])
                                        <button type="button"
                                                class="catalog-btn catalog-btn-outline"
                                                data-shipping-quote
                                                data-merchant-id="{{ $branchData['offer']['user_id'] }}"
                                                data-branch-id="{{ $branchData['offer']['branch_id'] }}"
                                                data-weight="{{ $catalogItem->weight }}"
                                                data-catalog-item-name="{{ $catalogItem->showName() }}"
                                                title="@lang('احسب الشحن')">
                                            <i class="fas fa-truck"></i>
                                        </button>
                                        @endif

                                        {{-- Add to Favorites --}}
                                        @auth
                                            <button type="button"
                                                    class="catalog-btn catalog-btn-outline favorite"
                                                    data-href="{{ route('user-favorite-add-merchant', $branchData['offer']['merchant_item_id']) }}"
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
