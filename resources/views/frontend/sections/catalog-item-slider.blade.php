{{--
================================================================================
SECTION PARTIAL: CatalogItem Slider
================================================================================
Receives: $merchantItems (collection of MerchantItem models)
Each MerchantItem = one listing (catalogItem + merchant + quality brand)
Uses the same slider style as Related Products
================================================================================
--}}

<div class="gs-catalogItem-cards-slider-area">
    <div class="catalogItem-cards-slider">
        @foreach($merchantItems as $mp)
            @include('includes.frontend.home_catalog_item', [
                'class' => '',
                'catalogItem' => $mp->catalogItem,
                'mp' => $mp
            ])
        @endforeach
    </div>
</div>
