{{-- resources/views/partials/api/alternatives.blade.php --}}
{{-- Simplified alternatives list with offers button --}}
{{-- Uses unified catalog-modal CSS classes from muaadh-system.css --}}
{{-- Integrates with illustrated.js navigation system --}}
{{-- Pre-computed: $originalPart, $otherAlternatives in CatalogItemApiController (DATA_FLOW_POLICY) --}}

<div class="catalog-offers-content ill-alt">
    @if($originalPart || $otherAlternatives->count() > 0)
        {{-- Original Part Card --}}
        @if($originalPart)
            <div class="catalog-original-part mb-3">
                <div class="catalog-original-part__inner">
                    <img src="{{ $originalPart->resolved_photo }}"
                         alt="{{ $originalPart->part_number }}"
                         class="catalog-original-part__photo"
                         loading="lazy">
                    <div class="catalog-original-part__info">
                        <span class="catalog-original-part__number">{{ $originalPart->part_number }}</span>
                        <span class="catalog-original-part__name">{{ $originalPart->localized_name }}</span>
                    </div>
                    <div class="catalog-original-part__meta">
                        @if($originalPart->lowest_price_formatted)
                            <span class="catalog-original-part__price">
                                <small class="text-muted">@lang('From')</small>
                                <strong class="text-success">{{ $originalPart->lowest_price_formatted }}</strong>
                                @if($originalPart->highest_price_formatted && $originalPart->lowest_price != $originalPart->highest_price)
                                    <small class="text-muted">@lang('To')</small>
                                    <strong class="text-danger">{{ $originalPart->highest_price_formatted }}</strong>
                                @endif
                            </span>
                        @endif
                        @if($originalPart->offers_count > 0)
                            <span class="catalog-badge catalog-badge-success catalog-badge-sm">{{ $originalPart->offers_count }}</span>
                        @endif
                    </div>
                    <button type="button"
                            class="catalog-btn catalog-btn-primary catalog-btn-sm alt-offers-btn"
                            data-catalog-item-id="{{ $originalPart->id }}"
                            data-part-number="{{ $originalPart->part_number }}"
                            data-name="{{ $originalPart->localized_name }}">
                        <i class="fas fa-tags"></i>
                        @lang('Offers')
                    </button>
                </div>
            </div>
        @endif

        {{-- Alternatives Section --}}
        @if($otherAlternatives->count() > 0)
            <div class="catalog-section-header">
                <h5>
                    <i class="fas fa-exchange-alt"></i>
                    @lang('Alternatives')
                </h5>
                <span class="catalog-badge catalog-badge-secondary">{{ $otherAlternatives->count() }} @lang('items')</span>
            </div>

            {{-- Alternatives Cards (Same style as original part) --}}
            <div class="catalog-alternatives-list">
                @foreach($otherAlternatives as $catalogItem)
                    <div class="catalog-original-part {{ $catalogItem->offers_count > 0 ? '' : 'catalog-original-part--no-offers' }}">
                        <div class="catalog-original-part__inner">
                            <img src="{{ $catalogItem->resolved_photo }}"
                                 alt="{{ $catalogItem->part_number }}"
                                 class="catalog-original-part__photo"
                                 loading="lazy">
                            <div class="catalog-original-part__info">
                                <span class="catalog-original-part__number">{{ $catalogItem->part_number }}</span>
                                <span class="catalog-original-part__name">{{ $catalogItem->localized_name }}</span>
                            </div>
                            <div class="catalog-original-part__meta">
                                @if($catalogItem->lowest_price_formatted)
                                    <span class="catalog-original-part__price">
                                        <small class="text-muted">@lang('From')</small>
                                        <strong class="text-success">{{ $catalogItem->lowest_price_formatted }}</strong>
                                        @if($catalogItem->highest_price_formatted && $catalogItem->lowest_price != $catalogItem->highest_price)
                                            <small class="text-muted">@lang('To')</small>
                                            <strong class="text-danger">{{ $catalogItem->highest_price_formatted }}</strong>
                                        @endif
                                    </span>
                                @endif
                                @if($catalogItem->offers_count > 0)
                                    <span class="catalog-badge catalog-badge-success catalog-badge-sm">{{ $catalogItem->offers_count }}</span>
                                @endif
                            </div>
                            <button type="button"
                                    class="catalog-btn catalog-btn-primary catalog-btn-sm alt-offers-btn"
                                    data-catalog-item-id="{{ $catalogItem->id }}"
                                    data-part-number="{{ $catalogItem->part_number }}"
                                    data-name="{{ $catalogItem->localized_name }}">
                                <i class="fas fa-tags"></i>
                                @lang('Offers')
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

    @else
        {{-- No original part and no alternatives --}}
        <div class="catalog-empty">
            <i class="fas fa-exchange-alt"></i>
            <p>@lang('No alternatives available for this part')</p>
        </div>
    @endif
</div>
