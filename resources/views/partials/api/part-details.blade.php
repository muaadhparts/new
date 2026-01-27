{{-- resources/views/partials/api/part-details.blade.php --}}
{{-- Server-rendered Part Details for Callout Modal --}}
{{-- Replaces JS renderProducts() function --}}
{{-- Uses unified catalog-modal CSS classes --}}
{{-- All data pre-computed in CalloutController (DATA_FLOW_POLICY) --}}

<div class="catalog-offers-content ill-parts">
    @if(!empty($catalogItems) && count($catalogItems) > 0)
        {{-- Header with summary --}}
        <div class="catalog-offers-header">
            <div class="catalog-offers-summary d-flex align-items-center gap-2 flex-wrap">
                <span class="catalog-badge catalog-badge-primary">
                    <i class="fas fa-cogs"></i>
                    {{ count($catalogItems) }} @lang('parts')
                </span>
                @if(!empty($pagination['total']) && $pagination['total'] > count($catalogItems))
                    <span class="catalog-badge catalog-badge-secondary">
                        <i class="fas fa-list"></i>
                        {{ $pagination['total'] }} @lang('total')
                    </span>
                @endif
            </div>
        </div>

        {{-- Desktop Table --}}
        <div class="d-none d-md-block">
            <div class="table-responsive">
                <table class="table table-hover align-middle catalog-table table-nowrap">
                    <thead>
                        <tr>
                            <th class="text-center text-nowrap">@lang('Part Number')</th>
                            <th class="text-center text-nowrap">@lang('Callout')</th>
                            <th class="text-center text-nowrap">@lang('Qty')</th>
                            <th class="text-center text-nowrap">@lang('Name')</th>
                            <th class="text-center text-nowrap">@lang('Fits')</th>
                            <th class="text-center text-nowrap">@lang('Match')</th>
                            <th class="text-center text-nowrap">@lang('Extensions')</th>
                            <th class="text-center text-nowrap">@lang('From')</th>
                            <th class="text-center text-nowrap">@lang('To')</th>
                            <th class="text-center text-nowrap">@lang('Offers')</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($catalogItems as $part)
                            <tr class="{{ $part['is_generic'] ? 'is-generic' : '' }}">
                                <td class="text-center">
                                    <a href="javascript:;"
                                       class="part-link"
                                       data-part_number="{{ $part['part_number'] ?? '' }}">
                                        {{ $part['part_number'] ?? '' }}
                                    </a>
                                </td>
                                <td class="text-center">{{ $part['display_callout'] }}</td>
                                <td class="text-center">{{ $part['display_qty'] }}</td>
                                <td class="text-center">{{ $part['localized_name'] }}</td>
                                <td class="text-center">
                                    @if(($part['catalog_item_id'] ?? null) && count($part['fitment_brands'] ?? []) > 0)
                                        <button type="button"
                                                class="catalog-btn catalog-btn-outline catalog-btn-sm fitment-details-btn"
                                                data-catalog-item-id="{{ $part['catalog_item_id'] }}"
                                                data-part-number="{{ $part['part_number'] ?? '' }}">
                                            @if(count($part['fitment_brands']) === 1 && !empty($part['fitment_brands'][0]['logo']))
                                                <img src="{{ $part['fitment_brands'][0]['logo'] }}" alt="" class="catalog-btn__logo">
                                            @else
                                                <i class="fas fa-car"></i>
                                            @endif
                                            @if(count($part['fitment_brands']) === 1)
                                                <span>{{ $part['fitment_brands'][0]['name'] }}</span>
                                            @else
                                                <span>@lang('Fits')</span>
                                                <span class="catalog-badge catalog-badge-sm">{{ count($part['fitment_brands']) }}</span>
                                            @endif
                                        </button>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if(!empty($part['match_values_array']))
                                        <div class="catalog-match-badges justify-content-center">
                                            @foreach($part['match_values_array'] as $mv)
                                                <span class="catalog-match-badge">{{ $mv }}</span>
                                            @endforeach
                                        </div>
                                    @else
                                        <span class="catalog-match-badge catalog-match-badge--generic">
                                            @lang('Generic')
                                        </span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if(!empty($part['extensions_parsed']))
                                        <div class="catalog-ext-badges justify-content-center">
                                            @foreach($part['extensions_parsed'] as $ext)
                                                <span class="catalog-ext-badge">
                                                    <span class="catalog-ext-badge__key">{{ __('ext.' . $ext['key']) }}:</span>
                                                    {{ $ext['value'] }}
                                                </span>
                                            @endforeach
                                        </div>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($part['period_from'])
                                        <span class="text-muted small">{{ $part['period_from'] }}</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($part['period_to'])
                                        <span class="text-muted small">{{ $part['period_to'] }}</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($part['part_number'] ?? null)
                                        <a href="javascript:;"
                                           class="catalog-btn catalog-btn-outline catalog-btn-sm alt-link"
                                           data-part_number="{{ $part['part_number'] }}">
                                            <i class="fas fa-tags"></i>
                                            @if($part['total_offers'] > 0)
                                                @if($part['has_self_and_alt'])
                                                    {{ $part['self_offers'] ?? 0 }} @lang('offers') + {{ $part['alt_offers'] ?? 0 }} @lang('alt')
                                                @elseif($part['has_self_only'])
                                                    {{ $part['self_offers'] ?? 0 }} @lang('offers')
                                                @else
                                                    {{ $part['alt_offers'] ?? 0 }} @lang('alt offers')
                                                @endif
                                            @else
                                                @lang('No offers')
                                            @endif
                                        </a>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Mobile Cards --}}
        <div class="d-block d-md-none catalog-cards">
            @foreach($catalogItems as $part)
                <div class="catalog-modal-card {{ $part['is_generic'] ? 'card-available' : '' }}">
                    {{-- Header --}}
                    <div class="catalog-modal-card__header">
                        <div class="catalog-modal-card__part-info">
                            <a href="javascript:;"
                               class="catalog-modal-card__number part-link"
                               data-part_number="{{ $part['part_number'] ?? '' }}">
                                {{ $part['part_number'] ?? '' }}
                            </a>
                            @if(($part['catalog_item_id'] ?? null) && count($part['fitment_brands'] ?? []) > 0)
                                <button type="button"
                                        class="catalog-btn catalog-btn-outline catalog-btn-sm fitment-details-btn ms-2"
                                        data-catalog-item-id="{{ $part['catalog_item_id'] }}"
                                        data-part-number="{{ $part['part_number'] ?? '' }}">
                                    @if(count($part['fitment_brands']) === 1 && !empty($part['fitment_brands'][0]['logo']))
                                        <img src="{{ $part['fitment_brands'][0]['logo'] }}" alt="" class="catalog-btn__logo">
                                    @else
                                        <i class="fas fa-car"></i>
                                    @endif
                                    @if(count($part['fitment_brands']) === 1)
                                        <span>{{ $part['fitment_brands'][0]['name'] }}</span>
                                    @else
                                        <span>@lang('Fits')</span>
                                        <span class="catalog-badge catalog-badge-sm">{{ count($part['fitment_brands']) }}</span>
                                    @endif
                                </button>
                            @endif
                        </div>
                        <div class="catalog-modal-card__badges">
                            @if($part['display_callout'])
                                <span class="catalog-badge catalog-badge-secondary">
                                    #{{ $part['display_callout'] }}
                                </span>
                            @endif
                            @if($part['display_qty'])
                                <span class="catalog-badge catalog-badge-info">
                                    x{{ $part['display_qty'] }}
                                </span>
                            @endif
                        </div>
                    </div>

                    {{-- Body --}}
                    <div class="catalog-modal-card__body">
                        {{-- Name --}}
                        <div class="catalog-part-row">
                            <span class="catalog-part-row__label">@lang('Name')</span>
                            <span class="catalog-part-row__value">{{ $part['localized_name'] }}</span>
                        </div>

                        {{-- Match Values --}}
                        <div class="catalog-part-row">
                            <span class="catalog-part-row__label">@lang('Match')</span>
                            <span class="catalog-part-row__value">
                                @if(!empty($part['match_values_array']))
                                    <div class="catalog-match-badges">
                                        @foreach($part['match_values_array'] as $mv)
                                            <span class="catalog-match-badge">{{ $mv }}</span>
                                        @endforeach
                                    </div>
                                @else
                                    <span class="catalog-match-badge catalog-match-badge--generic">
                                        @lang('Generic')
                                    </span>
                                @endif
                            </span>
                        </div>

                        {{-- Extensions --}}
                        @if(!empty($part['extensions_parsed']))
                            <div class="catalog-part-row">
                                <span class="catalog-part-row__label">@lang('Extensions')</span>
                                <span class="catalog-part-row__value">
                                    <div class="catalog-ext-badges">
                                        @foreach($part['extensions_parsed'] as $ext)
                                            <span class="catalog-ext-badge">
                                                <span class="catalog-ext-badge__key">{{ __('ext.' . $ext['key']) }}:</span>
                                                {{ $ext['value'] }}
                                            </span>
                                        @endforeach
                                    </div>
                                </span>
                            </div>
                        @endif

                        {{-- Period --}}
                        @if($part['period_from'] || $part['period_to'])
                            <div class="catalog-part-row">
                                <span class="catalog-part-row__label">@lang('Period')</span>
                                <span class="catalog-part-row__value">
                                    <span class="d-flex align-items-center gap-2 text-muted small">
                                        @if($part['period_from'])
                                            <span>@lang('From'): {{ $part['period_from'] }}</span>
                                        @endif
                                        @if($part['period_to'])
                                            <span>@lang('To'): {{ $part['period_to'] }}</span>
                                        @endif
                                    </span>
                                </span>
                            </div>
                        @endif
                    </div>

                    {{-- Footer --}}
                    <div class="catalog-modal-card__footer">
                        <div class="catalog-modal-card__price">
                            @if($part['total_offers'] > 0)
                                @if($part['has_self_and_alt'])
                                    <span class="catalog-badge catalog-badge-success">
                                        {{ $part['self_offers'] ?? 0 }} @lang('offers')
                                    </span>
                                    <span class="catalog-badge catalog-badge-info">
                                        + {{ $part['alt_offers'] ?? 0 }} @lang('alt')
                                    </span>
                                @elseif($part['has_self_only'])
                                    <span class="catalog-badge catalog-badge-success">
                                        {{ $part['self_offers'] ?? 0 }} @lang('offers')
                                    </span>
                                @else
                                    <span class="catalog-badge catalog-badge-info">
                                        {{ $part['alt_offers'] ?? 0 }} @lang('alt offers')
                                    </span>
                                @endif
                            @else
                                <span class="catalog-badge catalog-badge-secondary">
                                    @lang('No offers')
                                </span>
                            @endif
                        </div>
                        <div class="catalog-modal-card__actions">
                            @if($part['part_number'] ?? null)
                                <button type="button"
                                        class="catalog-btn catalog-btn-primary alt-link"
                                        data-part_number="{{ $part['part_number'] }}">
                                    <i class="fas fa-tags"></i>
                                    @lang('View Offers')
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Pagination (using pre-computed paginationData) --}}
        @if(($paginationData['show'] ?? false))
            <div class="catalog-pagination">
                <div class="catalog-pagination__info">
                    @lang('Showing') {{ $paginationData['from'] }}-{{ $paginationData['to'] }} @lang('of') {{ $paginationData['total'] }}
                </div>
                <nav class="catalog-pagination__nav">
                    {{-- Previous --}}
                    @if($paginationData['hasPrev'])
                        <a href="javascript:;"
                           class="catalog-pagination__link pagination-link"
                           data-page="{{ $paginationData['prevPage'] }}">
                            @lang('Previous')
                        </a>
                    @endif

                    {{-- Page numbers (pre-computed range) --}}
                    @foreach($paginationData['pageRange'] as $pageNum)
                        <a href="javascript:;"
                           class="catalog-pagination__link pagination-link {{ $pageNum === $paginationData['currentPage'] ? 'is-active' : '' }}"
                           data-page="{{ $pageNum }}">
                            {{ $pageNum }}
                        </a>
                    @endforeach

                    {{-- Next --}}
                    @if($paginationData['hasNext'])
                        <a href="javascript:;"
                           class="catalog-pagination__link pagination-link"
                           data-page="{{ $paginationData['nextPage'] }}">
                            @lang('Next')
                        </a>
                    @endif
                </nav>
            </div>
        @endif

    @else
        {{-- No Results --}}
        <div class="catalog-empty">
            <i class="fas fa-cogs"></i>
            <p>@lang('No parts found for this callout')</p>
        </div>
    @endif
</div>
