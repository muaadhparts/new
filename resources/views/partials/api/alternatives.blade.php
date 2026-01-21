{{-- resources/views/partials/api/alternatives.blade.php --}}
{{-- Simplified alternatives list with offers button --}}
{{-- Uses unified catalog-modal CSS classes from muaadh-system.css --}}
{{-- Integrates with illustrated.js navigation system --}}

@php
    // Helper function to resolve photo URL
    $resolvePhoto = function($photo) {
        if (!$photo) {
            return asset('assets/images/noimage.png');
        }
        if (filter_var($photo, FILTER_VALIDATE_URL)) {
            return $photo;
        }
        return \Illuminate\Support\Facades\Storage::url($photo);
    };
@endphp

<div class="catalog-modal-content ill-alt">
    @if($alternatives && $alternatives->count() > 0)
        {{-- Header --}}
        <div class="catalog-section-header">
            <h5>
                <i class="fas fa-exchange-alt"></i>
                @lang('Alternatives')
            </h5>
            <span class="catalog-badge catalog-badge-secondary">{{ $alternatives->count() }} @lang('items')</span>
        </div>

        {{-- Desktop Table --}}
        <div class="d-none d-md-block">
            <div class="table-responsive">
                <table class="table table-hover align-middle catalog-table">
                    <thead>
                        <tr>
                            <th style="width: 70px;">@lang('Photo')</th>
                            <th>@lang('Part Number')</th>
                            <th>@lang('Name')</th>
                            <th class="text-end">@lang('Price')</th>
                            <th class="text-center" style="width: 120px;">@lang('Action')</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($alternatives as $catalogItem)
                            <tr>
                                <td>
                                    <img src="{{ $resolvePhoto($catalogItem->photo) }}"
                                         alt="{{ $catalogItem->part_number }}"
                                         class="catalog-modal-card__photo"
                                         loading="lazy">
                                </td>
                                <td>
                                    <span class="catalog-modal-card__number">{{ $catalogItem->part_number }}</span>
                                </td>
                                <td class="text-truncate" style="max-width: 200px;">
                                    {{ $catalogItem->localized_name }}
                                </td>
                                <td class="text-end">
                                    @if($catalogItem->lowest_price_formatted)
                                        <div class="catalog-modal-card__price">
                                            <span class="catalog-modal-card__price-from">@lang('From')</span>
                                            <span class="catalog-modal-card__price-value">{{ $catalogItem->lowest_price_formatted }}</span>
                                        </div>
                                        <small class="text-muted">{{ $catalogItem->offers_count }} @lang('offers')</small>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <button type="button"
                                            class="catalog-btn catalog-btn-primary catalog-btn-sm alt-offers-btn"
                                            data-catalog-item-id="{{ $catalogItem->id }}"
                                            data-part-number="{{ $catalogItem->part_number }}"
                                            data-name="{{ $catalogItem->localized_name }}">
                                        <i class="fas fa-tags"></i>
                                        @lang('Offers')
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Mobile Cards - Using unified catalog-modal-card classes --}}
        <div class="d-block d-md-none">
            @foreach($alternatives as $catalogItem)
                <div class="catalog-modal-card {{ $catalogItem->offers_count > 0 ? 'card-available' : '' }}">
                    {{-- Header --}}
                    <div class="catalog-modal-card__header">
                        <div class="catalog-modal-card__part-info">
                            <img src="{{ $resolvePhoto($catalogItem->photo) }}"
                                 alt="{{ $catalogItem->part_number }}"
                                 class="catalog-modal-card__photo"
                                 loading="lazy">
                            <span class="catalog-modal-card__number">{{ $catalogItem->part_number }}</span>
                        </div>
                        <div class="catalog-modal-card__badges">
                            @if($catalogItem->offers_count > 0)
                                <span class="catalog-badge catalog-badge-success">
                                    {{ $catalogItem->offers_count }} @lang('offers')
                                </span>
                            @endif
                        </div>
                    </div>

                    {{-- Body --}}
                    <div class="catalog-modal-card__body">
                        <div class="catalog-card-name">{{ $catalogItem->localized_name }}</div>
                    </div>

                    {{-- Footer --}}
                    <div class="catalog-modal-card__footer">
                        <div class="catalog-modal-card__price">
                            @if($catalogItem->lowest_price_formatted)
                                <span class="catalog-modal-card__price-from">@lang('From')</span>
                                <span class="catalog-modal-card__price-value">{{ $catalogItem->lowest_price_formatted }}</span>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </div>
                        <div class="catalog-modal-card__actions">
                            <button type="button"
                                    class="catalog-btn catalog-btn-primary alt-offers-btn"
                                    data-catalog-item-id="{{ $catalogItem->id }}"
                                    data-part-number="{{ $catalogItem->part_number }}"
                                    data-name="{{ $catalogItem->localized_name }}">
                                <i class="fas fa-tags"></i>
                                @lang('Offers')
                            </button>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

    @else
        <div class="catalog-section-header">
            <h5>
                <i class="fas fa-exchange-alt"></i>
                @lang('Alternatives')
            </h5>
        </div>
        <div class="text-center py-4">
            <i class="fas fa-exchange-alt fa-2x text-muted mb-2 d-block"></i>
            <p class="text-muted mb-0">@lang('No alternatives available for this part')</p>
        </div>
    @endif
</div>
