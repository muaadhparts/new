@php
    /** @var \App\Models\Product|\App\Models\MerchantProduct $product*/
    /** @var \App\Models\MerchantProduct|null $mp */

    // Check if $product is actually a MerchantProduct instance
    $isMerchantProduct = $product instanceof \App\Models\MerchantProduct;

    // Determine merchant product
    if ($isMerchantProduct) {
        // $product is already a MerchantProduct, use it directly
        $merchant = $product;
        // Get the actual Product model for product-specific data
        $actualProduct = $product->product;
    } else {
        // $product is a Product model
        $actualProduct = $product;

        // Get merchant product context if available
        $merchant = $mp ?? null;

        // If no merchant product passed, try to get the first active one
        if (!$merchant) {
            $merchant = $product->merchantProducts()
                ->where('status', 1)
                ->whereHas('user', function ($user) {
                    $user->where('is_vendor', 2);
                })
                ->orderByRaw('CASE WHEN (stock IS NULL OR stock = 0) THEN 1 ELSE 0 END ASC')
                ->orderBy('price')
                ->first();
        }
    }

    // Build product URL
    $productSlug = $isMerchantProduct ? optional($actualProduct)->slug : $product->slug;
    $productUrl = $merchant && $productSlug
        ? route('front.product', ['slug' => $productSlug, 'vendor_id' => $merchant->user_id, 'merchant_product_id' => $merchant->id])
        : ($productSlug ? route('front.product.legacy', $productSlug) : '#');

    // Calculate discount
    $offPercentage = $merchant && method_exists($merchant, 'offPercentage')
        ? $merchant->offPercentage()
        : ($actualProduct && method_exists($actualProduct, 'offPercentage') ? $actualProduct->offPercentage() : 0);
@endphp

<div class="{{ isset($class) ? $class : 'col-md-6 col-lg-4 col-xl-3' }}">

    <div class="single-product">
        <div class="img-wrapper">
            @if ($offPercentage && round($offPercentage) > 0)
                <span class="product-badge">-{{ round($offPercentage) }}%</span>
            @endif

            @if (Auth::check())
                @if (isset($wishlist))
                    <a href="javascript:;" class="removewishlist"
                        data-href="{{ route('user-wishlist-remove',App\Models\Wishlist::where('user_id', '=', $user->id)->where('product_id', '=', $actualProduct->id)->first()->id) }}">
                        <div class="add-to-wishlist-btn bg-danger">
                            <i class="fas fa-trash  text-white"></i>
                        </div>
                    </a>
                @else
                    <a href="javascript:;" class="wishlist" data-href="{{ route('user-wishlist-add', $actualProduct->id) }}">
                        <div class="add-to-wishlist-btn {{ wishlistCheck($actualProduct->id) ? 'active' : '' }}">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                fill="none">
                                <path fill-rule="evenodd" clip-rule="evenodd"
                                    d="M11.9932 5.13581C9.9938 2.7984 6.65975 2.16964 4.15469 4.31001C1.64964 6.45038 1.29697 10.029 3.2642 12.5604C4.89982 14.6651 9.84977 19.1041 11.4721 20.5408C11.6536 20.7016 11.7444 20.7819 11.8502 20.8135C11.9426 20.8411 12.0437 20.8411 12.1361 20.8135C12.2419 20.7819 12.3327 20.7016 12.5142 20.5408C14.1365 19.1041 19.0865 14.6651 20.7221 12.5604C22.6893 10.029 22.3797 6.42787 19.8316 4.31001C17.2835 2.19216 13.9925 2.7984 11.9932 5.13581Z"
                                    stroke="#030712" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </div>
                    </a>
                @endif
            @else
                <a href="{{ route('user.login') }}">
                    <div class="add-to-wishlist-btn">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                            fill="none">
                            <path fill-rule="evenodd" clip-rule="evenodd"
                                d="M11.9932 5.13581C9.9938 2.7984 6.65975 2.16964 4.15469 4.31001C1.64964 6.45038 1.29697 10.029 3.2642 12.5604C4.89982 14.6651 9.84977 19.1041 11.4721 20.5408C11.6536 20.7016 11.7444 20.7819 11.8502 20.8135C11.9426 20.8411 12.0437 20.8411 12.1361 20.8135C12.2419 20.7819 12.3327 20.7016 12.5142 20.5408C14.1365 19.1041 19.0865 14.6651 20.7221 12.5604C22.6893 10.029 22.3797 6.42787 19.8316 4.31001C17.2835 2.19216 13.9925 2.7984 11.9932 5.13581Z"
                                stroke="#030712" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </div>
                </a>
            @endif



            <img class="product-img"
                src="{{ $actualProduct->thumbnail ? asset('assets/images/thumbnails/' . $actualProduct->thumbnail) : asset('assets/images/noimage.png') }}"
                alt="product img">

            <div class="add-to-cart">

                @if ($actualProduct->type != 'Listing')
                    <a data-href="{{ route('product.compare.add', $actualProduct->id) }}" class="compare_product"
                        href="javascrit:;">
                        <div class="compare">
                            <svg class="icon" xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                viewBox="0 0 24 24" fill="none">
                                <path
                                    d="M18.1777 8C23.2737 8 23.2737 16 18.1777 16C13.0827 16 11.0447 8 5.43875 8C0.85375 8 0.85375 16 5.43875 16C11.0447 16 13.0828 8 18.1788 8H18.1777Z"
                                    stroke="#030712" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </div>
                    </a>
                @endif


                @if ($actualProduct->product_type == 'affiliate')
                    <a href="{{ $actualProduct->affiliate_link }}" class="add_to_cart_button">
                        <div class="add-cart">
                            @lang('Add To Cart')
                        </div>
                    </a>
                @else
                    @if ($actualProduct->emptyStock())
                        <div class="add-cart">
                            {{ __('Out of Stock') }}
                        </div>
                    @else
                        @if ($actualProduct->type != 'Listing')
                            <a {{ $actualProduct->cross_products ? 'data-bs-target=#exampleModal' : '' }} href="javascript:;"
                                data-href="{{ route('product.cart.add', $actualProduct->id) }}"
                                data-cross-href="{{ route('front.show.cross.product', $actualProduct->id) }}"
                                class="add_cart_click {{ $actualProduct->cross_products ? 'view_cross_product' : '' }}">
                                <div class="add-cart">
                                    @lang('Add To Cart')
                                </div>
                            </a>
                        @endif
                    @endif
                @endif


                @if ($actualProduct->type != 'Listing')
                    <a href="{{ $productUrl }}">
                        <div class="details">
                            <svg class="icon" xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                viewBox="0 0 24 24" fill="none">
                                <path
                                    d="M2.42012 12.7132C2.28394 12.4975 2.21584 12.3897 2.17772 12.2234C2.14909 12.0985 2.14909 11.9015 2.17772 11.7766C2.21584 11.6103 2.28394 11.5025 2.42012 11.2868C3.54553 9.50484 6.8954 5 12.0004 5C17.1054 5 20.4553 9.50484 21.5807 11.2868C21.7169 11.5025 21.785 11.6103 21.8231 11.7766C21.8517 11.9015 21.8517 12.0985 21.8231 12.2234C21.785 12.3897 21.7169 12.4975 21.5807 12.7132C20.4553 14.4952 17.1054 19 12.0004 19C6.8954 19 3.54553 14.4952 2.42012 12.7132Z"
                                    stroke="#030712" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                <path
                                    d="M12.0004 15C13.6573 15 15.0004 13.6569 15.0004 12C15.0004 10.3431 13.6573 9 12.0004 9C10.3435 9 9.0004 10.3431 9.0004 12C9.0004 13.6569 10.3435 15 12.0004 15Z"
                                    stroke="#030712" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </div>
                    </a>
                @endif


            </div>
        </div>
        <div class="content-wrapper">
            <a href="{{ $productUrl }}">
                <h6 class="product-title">{{ $actualProduct->showName() }}</h6>
            </a>
            <div class="price-wrapper">
                <h6>{{ $actualProduct->showPrice() }}</h6>

                <h6><del>{{ $actualProduct->showPreviousPrice() }}</del></h6>
            </div>

            <div class="ratings-wrapper">
                <svg xmlns="http://www.w3.org/2000/svg" width="17" height="16" viewBox="0 0 17 16"
                    fill="none">
                    <path
                        d="M8.5 0.5L10.4084 6.37336L16.584 6.37336L11.5878 10.0033L13.4962 15.8766L8.5 12.2467L3.50383 15.8766L5.41219 10.0033L0.416019 6.37336L6.59163 6.37336L8.5 0.5Z"
                        fill="#EEAE0B" />
                </svg>

                <span class="rating-title">{{ number_format($actualProduct->ratings_avg_rating, 1) }}
                    ({{ $actualProduct->ratings_count }})</span>
            </div>
        </div>
    </div>
</div>
