{{-- AJAX Response for Category Products --}}
@php
    $view = request()->input('view_check', session('view', 'grid-view'));
    $products = $cards ?? $prods;
@endphp

@if (count($products) > 0)
{{-- Products Content --}}
<div id="ajax-products-content">
    <div class="tab-content" id="myTabContent">
        <!-- product list view -->
        <div class="tab-pane fade {{ $view == 'list-view' ? 'show active' : '' }}"
            id="layout-list-pane" role="tabpanel" tabindex="0">
            <div class="row gy-4">
                @foreach ($products as $card)
                    @include('includes.frontend.home_product', ['card' => $card, 'layout' => 'list'])
                @endforeach
            </div>
        </div>

        <!-- product grid view -->
        <div class="tab-pane fade {{ $view == 'grid-view' ? 'show active' : '' }}"
            id="layout-grid-pane" role="tabpanel" tabindex="0">
            <div class="row gy-4">
                @foreach ($products as $card)
                    @include('includes.frontend.home_product', ['card' => $card, 'layout' => 'grid', 'class' => 'col-6 col-md-4 col-lg-3'])
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
    "total": {{ $prods->total() }},
    "hasMorePages": {{ $prods->hasMorePages() ? 'true' : 'false' }},
    "onFirstPage": {{ $prods->onFirstPage() ? 'true' : 'false' }}
}
</script>
@else
<div id="ajax-products-content">
    <div class="page-center py-5 text-center">
        <h4>{{ __('No Product Found.') }}</h4>
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
