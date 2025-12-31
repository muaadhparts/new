{{-- AJAX Response for Vendor Products --}}
@php
    // Determine view mode
    $view = request()->input('view_check', 'list-view');
@endphp

{{-- Products Content --}}
<div id="ajax-products-content">
    <div class="tab-content" id="myTabContent">
        <!-- product list view -->
        <div class="tab-pane fade {{ $view == 'list-view' ? 'show active' : '' }}"
            id="layout-list-pane" role="tabpanel" tabindex="0">
            <div class="row gy-4">
                @foreach ($vprods as $catalogItem)
                    @include('includes.frontend.home_catalog_item', ['layout' => 'list', 'catalogItem' => $catalogItem, 'mp' => $catalogItem->merchant_merchant_item ?? null])
                @endforeach
            </div>
        </div>

        <!-- product grid view -->
        <div class="tab-pane fade {{ $view == 'grid-view' ? 'show active' : '' }}"
            id="layout-grid-pane" role="tabpanel" tabindex="0">
            <div class="row gy-4">
                @foreach ($vprods as $catalogItem)
                    @include('includes.frontend.home_catalog_item', [
                        'class' => 'col-sm-6 col-md-6 col-xl-4',
                        'catalogItem' => $catalogItem,
                        'mp' => $catalogItem->merchant_merchant_item ?? null,
                    ])
                @endforeach
            </div>
        </div>
    </div>
</div>

{{-- Pagination Data (JSON for JavaScript) --}}
<script type="application/json" id="ajax-pagination-data">
{
    "currentPage": {{ $vprods->currentPage() }},
    "lastPage": {{ $vprods->lastPage() }},
    "total": {{ $vprods->total() }},
    "hasMorePages": {{ $vprods->hasMorePages() ? 'true' : 'false' }},
    "onFirstPage": {{ $vprods->onFirstPage() ? 'true' : 'false' }}
}
</script>
