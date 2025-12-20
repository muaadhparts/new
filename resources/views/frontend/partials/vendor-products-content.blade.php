{{-- Vendor Products Content (used in both full page and AJAX) --}}
<div class="tab-content" id="myTabContent">
    <!-- product list view start  -->
    <div class="tab-pane fade {{ $view == 'list-view' ? 'show active' : '' }}"
        id="layout-list-pane" role="tabpanel" tabindex="0">
        <div class="row gy-4">
            @foreach ($vprods as $product)
                @include('includes.frontend.home_product', ['layout' => 'list', 'mp' => $product->vendor_merchant_product ?? null])
            @endforeach
        </div>
    </div>

    <div class="tab-pane fade {{ $view == 'grid-view' ? 'show active' : '' }}"
        id="layout-grid-pane" role="tabpanel" tabindex="0">
        <div class="row gy-4">
            @foreach ($vprods as $product)
                @include('includes.frontend.home_product', [
                    'class' => 'col-sm-6 col-md-6 col-xl-4',
                    'mp' => $product->vendor_merchant_product ?? null,
                ])
            @endforeach
        </div>
    </div>
    <!-- product grid view end  -->
</div>
