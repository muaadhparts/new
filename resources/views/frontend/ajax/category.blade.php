{{-- AJAX Response for Category Products --}}
@php
    $view = request()->input('view_check', session('view', 'grid-view'));
    $catalogItems = $cards ?? $prods;
    $total = $prods->total();
@endphp

@if ($total > 0)
{{-- Products Content --}}
<div id="ajax-catalogItems-content">
    <div class="tab-content" id="myTabContent">
        <!-- catalogItem list view -->
        <div class="tab-pane fade {{ $view == 'list-view' ? 'show active' : '' }}"
            id="layout-list-pane" role="tabpanel" tabindex="0">
            <div class="row gy-4">
                @foreach ($catalogItems as $card)
                    @include('includes.frontend.home_catalog_item', ['card' => $card, 'layout' => 'list'])
                @endforeach
            </div>
        </div>

        <!-- catalogItem grid view -->
        <div class="tab-pane fade {{ $view == 'grid-view' ? 'show active' : '' }}"
            id="layout-grid-pane" role="tabpanel" tabindex="0">
            <div class="row gy-4">
                @foreach ($catalogItems as $card)
                    @include('includes.frontend.home_catalog_item', ['card' => $card, 'layout' => 'grid', 'class' => 'col-6 col-md-4 col-lg-3'])
                @endforeach
            </div>
        </div>
    </div>
</div>

{{-- Pagination Data (JSON for JavaScript) --}}
<script type="application/json" id="ajax-pagination-data">
{
    "currentPage": {{ $prods->currentPage() }},
    "lastPage": {{ $prods->lastPage() }},
    "total": {{ $total }},
    "hasMorePages": {{ $prods->hasMorePages() ? 'true' : 'false' }},
    "onFirstPage": {{ $prods->onFirstPage() ? 'true' : 'false' }}
}
</script>
@else
{{-- Zero Results Box with Filter Summary --}}
<div id="ajax-catalogItems-content">
    <div class="m-no-results-box">
        <div class="m-no-results-box__icon">
            <i class="fas fa-search"></i>
        </div>
        <h4 class="m-no-results-box__name">@lang('No matching catalogItems')</h4>
        <p class="m-no-results-box__subname">@lang('Try adjusting your filters to find what you are looking for.')</p>

        @if(isset($filterSummary) && $filterSummary['hasFilters'])
        <div class="m-no-results-box__filters">
            <h6 class="m-no-results-box__filters-name">@lang('Applied Filters:')</h6>
            <ul class="m-no-results-box__filters-list">
                {{-- Category Hierarchy --}}
                @if($filterSummary['category'])
                <li>
                    <span class="m-no-results-box__filter-label">@lang('Category'):</span>
                    <span class="m-no-results-box__filter-value">
                        {{ $filterSummary['category'] }}
                        @if($filterSummary['subcategory'])
                            <i class="fas fa-chevron-{{ app()->getLocale() === 'ar' ? 'left' : 'right' }} mx-1"></i>
                            {{ $filterSummary['subcategory'] }}
                        @endif
                        @if($filterSummary['childcategory'])
                            <i class="fas fa-chevron-{{ app()->getLocale() === 'ar' ? 'left' : 'right' }} mx-1"></i>
                            {{ $filterSummary['childcategory'] }}
                        @endif
                    </span>
                </li>
                @endif

                {{-- Merchants --}}
                @if(!empty($filterSummary['merchants']))
                <li>
                    <span class="m-no-results-box__filter-label">@lang('Merchant'):</span>
                    <span class="m-no-results-box__filter-value">{{ implode(', ', $filterSummary['merchants']) }}</span>
                </li>
                @endif

                {{-- Quality Brands --}}
                @if(!empty($filterSummary['qualityBrands']))
                <li>
                    <span class="m-no-results-box__filter-label">@lang('Quality Brand'):</span>
                    <span class="m-no-results-box__filter-value">{{ implode(', ', $filterSummary['qualityBrands']) }}</span>
                </li>
                @endif
            </ul>
        </div>
        @endif
    </div>
</div>

<script type="application/json" id="ajax-pagination-data">
{
    "currentPage": 1,
    "lastPage": 1,
    "total": 0,
    "hasMorePages": false,
    "onFirstPage": true
}
</script>
@endif
