{{-- resources/views/partials/api/part-details.blade.php --}}
{{-- Server-rendered Part Details for Callout Modal --}}
{{-- Replaces JS renderProducts() function --}}
{{-- Uses unified catalog-modal CSS classes --}}

@php
    $locale = app()->getLocale();
    $isArabic = str_starts_with($locale, 'ar');

    // Helper: localized part name
    $localizedName = function($part) use ($isArabic) {
        $en = $part['part_label_en'] ?? '';
        $ar = $part['part_label_ar'] ?? '';
        return $isArabic ? ($ar ?: $en ?: '—') : ($en ?: $ar ?: '—');
    };

    // Helper: format year-month
    $formatYearMonth = function($s) {
        if (empty($s)) return '';
        $raw = trim((string) $s);
        if (!$raw) return '';
        $d = preg_replace('/[^0-9]/', '', $raw);
        if (strlen($d) >= 6) {
            $y = substr($d, 0, 4);
            $m = substr($d, 4, 2);
            if (preg_match('/^(19|20)\d{2}$/', $y) && preg_match('/^(0[1-9]|1[0-2])$/', $m)) {
                return "{$y}-{$m}";
            }
        }
        if (strlen($d) === 4) return $d;
        return $raw;
    };

    // Helper: format period range
    $formatPeriod = function($begin, $end) use ($formatYearMonth) {
        $from = $formatYearMonth($begin);
        $to = $formatYearMonth($end);
        $parts = array_filter([$from, $to]);
        return implode(' → ', $parts);
    };

    // Helper: render extensions
    $renderExtensions = function($ext) {
        if (empty($ext)) return [];
        if (is_string($ext)) {
            try {
                $ext = json_decode($ext, true);
            } catch (\Exception $e) {
                return [];
            }
        }
        if (!is_array($ext)) return [];

        $result = [];
        if (array_keys($ext) !== range(0, count($ext) - 1)) {
            // Associative array
            foreach ($ext as $key => $value) {
                if (!empty($value)) {
                    $result[] = ['key' => $key, 'value' => $value];
                }
            }
        } else {
            // Sequential array
            foreach ($ext as $item) {
                $k = $item['extension_key'] ?? $item['key'] ?? '';
                $v = $item['extension_value'] ?? $item['value'] ?? '';
                if ($k && $v) {
                    $result[] = ['key' => $k, 'value' => $v];
                }
            }
        }
        return $result;
    };
@endphp

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
                            @php
                                $name = $localizedName($part);
                                $qty = isset($part['part_qty']) && trim((string)$part['part_qty']) !== '' ? $part['part_qty'] : '';
                                $callout = isset($part['part_callout']) && trim((string)$part['part_callout']) !== '' ? $part['part_callout'] : '';
                                $partNumber = $part['part_number'] ?? '';
                                $matchValues = $part['match_values'] ?? [];
                                if (is_string($matchValues)) {
                                    $matchValues = array_filter(array_map('trim', explode(',', $matchValues)));
                                }
                                $periodFrom = $formatYearMonth($part['part_begin'] ?? null);
                                $periodTo = $formatYearMonth($part['part_end'] ?? null);
                                $extensions = $renderExtensions($part['extensions'] ?? []);
                                $isGeneric = empty($matchValues);
                            @endphp
                            @php
                                $catalogItemId = $part['catalog_item_id'] ?? null;
                                $fitmentBrands = $part['fitment_brands'] ?? [];
                                $fitmentCount = count($fitmentBrands);
                            @endphp
                            <tr class="{{ $isGeneric ? 'is-generic' : '' }}">
                                <td class="text-center">
                                    <a href="javascript:;"
                                       class="part-link"
                                       data-part_number="{{ $partNumber }}">
                                        {{ $partNumber }}
                                    </a>
                                </td>
                                <td class="text-center">{{ $callout }}</td>
                                <td class="text-center">{{ $qty }}</td>
                                <td class="text-center">{{ $name }}</td>
                                <td class="text-center">
                                    @if($catalogItemId && $fitmentCount > 0)
                                        <button type="button"
                                                class="catalog-btn catalog-btn-outline catalog-btn-sm fitment-details-btn"
                                                data-catalog-item-id="{{ $catalogItemId }}"
                                                data-part-number="{{ $partNumber }}">
                                            @if($fitmentCount === 1 && !empty($fitmentBrands[0]['logo']))
                                                <img src="{{ $fitmentBrands[0]['logo'] }}" alt="" class="catalog-btn__logo">
                                            @else
                                                <i class="fas fa-car"></i>
                                            @endif
                                            @if($fitmentCount === 1)
                                                <span>{{ $fitmentBrands[0]['name'] }}</span>
                                            @else
                                                <span>@lang('Fits')</span>
                                                <span class="catalog-badge catalog-badge-sm">{{ $fitmentCount }}</span>
                                            @endif
                                        </button>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if(!empty($matchValues))
                                        <div class="catalog-match-badges justify-content-center">
                                            @foreach($matchValues as $mv)
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
                                    @if(!empty($extensions))
                                        <div class="catalog-ext-badges justify-content-center">
                                            @foreach($extensions as $ext)
                                                <span class="catalog-ext-badge">
                                                    <span class="catalog-ext-badge__key">{{ __('ext.' . $ext['key']) }}:</span>
                                                    {{ $ext['value'] }}
                                                </span>
                                            @endforeach
                                        </div>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($periodFrom)
                                        <span class="text-muted small">{{ $periodFrom }}</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($periodTo)
                                        <span class="text-muted small">{{ $periodTo }}</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @php
                                        $selfOffers = $part['self_offers'] ?? 0;
                                        $altOffers = $part['alt_offers'] ?? 0;
                                        $totalOffers = $selfOffers + $altOffers;
                                    @endphp
                                    @if($partNumber)
                                        <a href="javascript:;"
                                           class="catalog-btn catalog-btn-outline catalog-btn-sm alt-link"
                                           data-part_number="{{ $partNumber }}">
                                            <i class="fas fa-tags"></i>
                                            @if($totalOffers > 0)
                                                @if($selfOffers > 0 && $altOffers > 0)
                                                    {{-- عروض للصنف + عروض بديلة --}}
                                                    {{ $selfOffers }} @lang('offers') + {{ $altOffers }} @lang('alt')
                                                @elseif($selfOffers > 0)
                                                    {{-- عروض للصنف فقط --}}
                                                    {{ $selfOffers }} @lang('offers')
                                                @else
                                                    {{-- عروض بديلة فقط --}}
                                                    {{ $altOffers }} @lang('alt offers')
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
                @php
                    $name = $localizedName($part);
                    $qty = isset($part['part_qty']) && trim((string)$part['part_qty']) !== '' ? $part['part_qty'] : '';
                    $callout = isset($part['part_callout']) && trim((string)$part['part_callout']) !== '' ? $part['part_callout'] : '';
                    $partNumber = $part['part_number'] ?? '';
                    $catalogItemId = $part['catalog_item_id'] ?? null;
                    $fitmentBrands = $part['fitment_brands'] ?? [];
                    $fitmentCount = count($fitmentBrands);
                    $matchValues = $part['match_values'] ?? [];
                    if (is_string($matchValues)) {
                        $matchValues = array_filter(array_map('trim', explode(',', $matchValues)));
                    }
                    $periodFrom = $formatYearMonth($part['part_begin'] ?? null);
                    $periodTo = $formatYearMonth($part['part_end'] ?? null);
                    $extensions = $renderExtensions($part['extensions'] ?? []);
                    $isGeneric = empty($matchValues);
                @endphp
                <div class="catalog-modal-card {{ $isGeneric ? 'card-available' : '' }}">
                    {{-- Header --}}
                    <div class="catalog-modal-card__header">
                        <div class="catalog-modal-card__part-info">
                            <a href="javascript:;"
                               class="catalog-modal-card__number part-link"
                               data-part_number="{{ $partNumber }}">
                                {{ $partNumber }}
                            </a>
                            @if($catalogItemId && $fitmentCount > 0)
                                <button type="button"
                                        class="catalog-btn catalog-btn-outline catalog-btn-sm fitment-details-btn ms-2"
                                        data-catalog-item-id="{{ $catalogItemId }}"
                                        data-part-number="{{ $partNumber }}">
                                    @if($fitmentCount === 1 && !empty($fitmentBrands[0]['logo']))
                                        <img src="{{ $fitmentBrands[0]['logo'] }}" alt="" class="catalog-btn__logo">
                                    @else
                                        <i class="fas fa-car"></i>
                                    @endif
                                    @if($fitmentCount === 1)
                                        <span>{{ $fitmentBrands[0]['name'] }}</span>
                                    @else
                                        <span>@lang('Fits')</span>
                                        <span class="catalog-badge catalog-badge-sm">{{ $fitmentCount }}</span>
                                    @endif
                                </button>
                            @endif
                        </div>
                        <div class="catalog-modal-card__badges">
                            @if($callout)
                                <span class="catalog-badge catalog-badge-secondary">
                                    #{{ $callout }}
                                </span>
                            @endif
                            @if($qty)
                                <span class="catalog-badge catalog-badge-info">
                                    x{{ $qty }}
                                </span>
                            @endif
                        </div>
                    </div>

                    {{-- Body --}}
                    <div class="catalog-modal-card__body">
                        {{-- Name --}}
                        <div class="catalog-part-row">
                            <span class="catalog-part-row__label">@lang('Name')</span>
                            <span class="catalog-part-row__value">{{ $name }}</span>
                        </div>

                        {{-- Match Values --}}
                        <div class="catalog-part-row">
                            <span class="catalog-part-row__label">@lang('Match')</span>
                            <span class="catalog-part-row__value">
                                @if(!empty($matchValues))
                                    <div class="catalog-match-badges">
                                        @foreach($matchValues as $mv)
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
                        @if(!empty($extensions))
                            <div class="catalog-part-row">
                                <span class="catalog-part-row__label">@lang('Extensions')</span>
                                <span class="catalog-part-row__value">
                                    <div class="catalog-ext-badges">
                                        @foreach($extensions as $ext)
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
                        @if($periodFrom || $periodTo)
                            <div class="catalog-part-row">
                                <span class="catalog-part-row__label">@lang('Period')</span>
                                <span class="catalog-part-row__value">
                                    <span class="d-flex align-items-center gap-2 text-muted small">
                                        @if($periodFrom)
                                            <span>@lang('From'): {{ $periodFrom }}</span>
                                        @endif
                                        @if($periodTo)
                                            <span>@lang('To'): {{ $periodTo }}</span>
                                        @endif
                                    </span>
                                </span>
                            </div>
                        @endif
                    </div>

                    {{-- Footer --}}
                    <div class="catalog-modal-card__footer">
                        @php
                            $selfOffers = $part['self_offers'] ?? 0;
                            $altOffers = $part['alt_offers'] ?? 0;
                            $totalOffers = $selfOffers + $altOffers;
                        @endphp
                        <div class="catalog-modal-card__price">
                            @if($totalOffers > 0)
                                @if($selfOffers > 0 && $altOffers > 0)
                                    <span class="catalog-badge catalog-badge-success">
                                        {{ $selfOffers }} @lang('offers')
                                    </span>
                                    <span class="catalog-badge catalog-badge-info">
                                        + {{ $altOffers }} @lang('alt')
                                    </span>
                                @elseif($selfOffers > 0)
                                    <span class="catalog-badge catalog-badge-success">
                                        {{ $selfOffers }} @lang('offers')
                                    </span>
                                @else
                                    <span class="catalog-badge catalog-badge-info">
                                        {{ $altOffers }} @lang('alt offers')
                                    </span>
                                @endif
                            @else
                                <span class="catalog-badge catalog-badge-secondary">
                                    @lang('No offers')
                                </span>
                            @endif
                        </div>
                        <div class="catalog-modal-card__actions">
                            @if($partNumber)
                                <button type="button"
                                        class="catalog-btn catalog-btn-primary alt-link"
                                        data-part_number="{{ $partNumber }}">
                                    <i class="fas fa-tags"></i>
                                    @lang('View Offers')
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Pagination --}}
        @if(!empty($pagination) && ($pagination['last_page'] ?? 1) > 1)
            @php
                $currentPage = $pagination['current_page'] ?? 1;
                $lastPage = $pagination['last_page'] ?? 1;
                $total = $pagination['total'] ?? 0;
                $from = $pagination['from'] ?? 0;
                $to = $pagination['to'] ?? 0;
            @endphp
            <div class="catalog-pagination">
                <div class="catalog-pagination__info">
                    @lang('Showing') {{ $from }}-{{ $to }} @lang('of') {{ $total }}
                </div>
                <nav class="catalog-pagination__nav">
                    {{-- Previous --}}
                    @if($currentPage > 1)
                        <a href="javascript:;"
                           class="catalog-pagination__link pagination-link"
                           data-page="{{ $currentPage - 1 }}">
                            @lang('Previous')
                        </a>
                    @endif

                    {{-- Page numbers --}}
                    @php
                        $startPage = 1;
                        $endPage = $lastPage;

                        if ($lastPage > 5) {
                            if ($currentPage <= 3) {
                                $startPage = 1;
                                $endPage = 5;
                            } elseif ($currentPage >= $lastPage - 2) {
                                $startPage = $lastPage - 4;
                                $endPage = $lastPage;
                            } else {
                                $startPage = $currentPage - 2;
                                $endPage = $currentPage + 2;
                            }
                        }
                    @endphp

                    @for($i = $startPage; $i <= $endPage; $i++)
                        <a href="javascript:;"
                           class="catalog-pagination__link pagination-link {{ $i === $currentPage ? 'is-active' : '' }}"
                           data-page="{{ $i }}">
                            {{ $i }}
                        </a>
                    @endfor

                    {{-- Next --}}
                    @if($currentPage < $lastPage)
                        <a href="javascript:;"
                           class="catalog-pagination__link pagination-link"
                           data-page="{{ $currentPage + 1 }}">
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
