{{-- Merchant Products Content (used in both full page and AJAX) --}}
<div class="tab-content" id="myTabContent">
    <!-- catalogItem list view start  -->
    <div class="tab-pane fade {{ $view == 'list-view' ? 'show active' : '' }}"
        id="layout-list-pane" role="tabpanel" tabindex="0">
        <div class="row gy-4">
            @foreach ($vprods as $catalogItem)
                @include('includes.frontend.home_catalog_item', ['layout' => 'list', 'catalogItem' => $catalogItem, 'mp' => $catalogItem->merchant_merchant_item ?? null])
            @endforeach
        </div>
    </div>

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
    <!-- catalogItem grid view end  -->
</div>
