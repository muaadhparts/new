{{-- AJAX response for new unified catalog category tree --}}
{{-- $view pre-computed in CatalogController::newCategory (DATA_FLOW_POLICY) --}}

{{-- Products Content Container for AJAX replacement --}}
<div id="ajax-catalogItems-content">
    @if ($cards->total() == 0)
        <div class="m-no-results-box">
            <div class="m-no-results-box__icon">
                <i class="fas fa-search"></i>
            </div>
            <h4 class="m-no-results-box__name">@lang('No matching catalogItems')</h4>
            <p class="m-no-results-box__subname">@lang('Try selecting a different category or browse all categories.')</p>

            @if($selectedCategory)
            <div class="m-no-results-box__filters">
                <h6 class="m-no-results-box__filters-name">@lang('Selected Category:')</h6>
                <ul class="m-no-results-box__filters-list">
                    <li>
                        <span class="m-no-results-box__filter-value">
                            @foreach($breadcrumb as $crumb)
                                {{ app()->getLocale() == 'ar' ? ($crumb->label_ar ?: $crumb->label_en) : $crumb->label_en }}
                                @if(!$loop->last)
                                    <i class="fas fa-chevron-{{ app()->getLocale() === 'ar' ? 'left' : 'right' }} mx-1"></i>
                                @endif
                            @endforeach
                        </span>
                    </li>
                </ul>
            </div>
            @endif
        </div>
    @else
        <div class="tab-content" id="myTabContent">
            <!-- catalogItem list view start -->
            <div class="tab-pane fade {{ $view == 'list-view' ? 'show active' : '' }}"
                id="layout-list-pane" role="tabpanel" tabindex="0">
                <div class="row gy-4">
                    @foreach ($cards as $card)
                        @include('includes.frontend.home_catalog_item', ['card' => $card, 'layout' => 'list'])
                    @endforeach
                </div>
            </div>

            <div class="tab-pane fade {{ $view == 'grid-view' ? 'show active' : '' }}"
                id="layout-grid-pane" role="tabpanel" tabindex="0">
                <div class="row gy-4">
                    @foreach ($cards as $card)
                        @include('includes.frontend.home_catalog_item', ['card' => $card, 'layout' => 'grid', 'class' => 'col-6 col-md-4 col-lg-3'])
                    @endforeach
                </div>
            </div>
        </div>
    @endif
</div>

{{-- Pagination data for JavaScript --}}
<script type="application/json" id="ajax-pagination-data">
{
    "currentPage": {{ $cards->currentPage() }},
    "lastPage": {{ $cards->lastPage() }},
    "total": {{ $cards->total() }},
    "perPage": {{ $cards->perPage() }}
}
</script>

{{-- Updated Pagination UI --}}
<div class="m-pagination-simple"
     data-current="{{ $cards->currentPage() }}"
     data-last="{{ $cards->lastPage() }}"
     data-total="{{ $cards->total() }}">

    {{-- Previous Button --}}
    <button type="button" class="m-pagination-simple__btn m-pagination-simple__prev {{ $cards->onFirstPage() ? 'm-pagination-simple__btn--disabled' : '' }}"
            {{ $cards->onFirstPage() ? 'disabled' : '' }}>
        <i class="fas fa-chevron-{{ app()->getLocale() === 'ar' ? 'right' : 'left' }}"></i>
    </button>

    {{-- Page Input --}}
    <div class="m-pagination-simple__input-group">
        <input type="number"
               class="m-pagination-simple__input"
               value="{{ $cards->currentPage() }}"
               min="1"
               max="{{ $cards->lastPage() }}">
        <span class="m-pagination-simple__separator">@lang('of')</span>
        <span class="m-pagination-simple__total">{{ $cards->lastPage() }}</span>
    </div>

    {{-- Next Button --}}
    <button type="button" class="m-pagination-simple__btn m-pagination-simple__next {{ !$cards->hasMorePages() ? 'm-pagination-simple__btn--disabled' : '' }}"
            {{ !$cards->hasMorePages() ? 'disabled' : '' }}>
        <i class="fas fa-chevron-{{ app()->getLocale() === 'ar' ? 'left' : 'right' }}"></i>
    </button>
</div>
