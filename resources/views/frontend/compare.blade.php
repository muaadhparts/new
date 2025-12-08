@extends('layouts.front')
@section('content')
    <section class="gs-breadcrumb-section bg-class"
        data-background="{{ $gs->breadcrumb_banner ? asset('assets/images/' . $gs->breadcrumb_banner) : asset('assets/images/noimage.png') }}">
        <div class="container">
            <div class="row justify-content-center content-wrapper">
                <div class="col-12">
                    <h2 class="breadcrumb-title">@lang('Compare')</h2>
                    <ul class="bread-menu">
                        <li><a href="{{ route('front.index') }}">@lang('Home')</a></li>
                        <li><a href="{{ route('product.compare') }}">@lang('Compare')</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </section>



    <div class="gs-blog-wrapper gs-compare-wrapper  wow-replaced" data-wow-delay=".1s">
        <div class="container">
            @if (isset($products) && count($products) > 0)
                <div class="table table-responsive">

                    <table class="table-bordered">


                        <tr class="wow-replaced" data-wow-delay=".1s">
                            <td>
                                <h6 class="td-title">@lang('product Image')</h6>
                            </td>
                            @foreach ($products as $product)
                                @php
                                    $compareProductMerchant = $product['item']->merchantProducts()
                                        ->where('status', 1)
                                        ->whereHas('user', function ($user) {
                                            $user->where('is_vendor', 2);
                                        })
                                        ->orderByRaw('CASE WHEN (stock IS NULL OR stock = 0) THEN 1 ELSE 0 END ASC')
                                        ->orderBy('price')
                                        ->first();

                                    $compareProductUrl = $compareProductMerchant && $product['item']->slug
                                        ? route('front.product', ['slug' => $product['item']->slug, 'vendor_id' => $compareProductMerchant->user_id, 'merchant_product_id' => $compareProductMerchant->id])
                                        : ($product['item']->slug ? route('front.product.legacy', $product['item']->slug) : '#');
                                @endphp
                                <td>
                                    <a href="{{ $compareProductUrl }}">
                                        <img class="img-fluid w-150"
                                            src="{{ filter_var($product['item']->photo, FILTER_VALIDATE_URL) ? $product['item']->photo : ($product['item']->photo ? \Illuminate\Support\Facades\Storage::url($product['item']->photo) : asset('assets/images/noimage.png')) }}"
                                            alt="compare-img">
                                    </a>
                                </td>
                            @endforeach
                        </tr>
                        <tr class="wow-replaced" data-wow-delay=".1s">
                            <td>
                                <h6 class="td-title">@lang('product name')</h6>
                            </td>

                            @foreach ($products as $product)
                                @php
                                    $compareProductMerchant = $product['item']->merchantProducts()
                                        ->where('status', 1)
                                        ->whereHas('user', function ($user) {
                                            $user->where('is_vendor', 2);
                                        })
                                        ->orderByRaw('CASE WHEN (stock IS NULL OR stock = 0) THEN 1 ELSE 0 END ASC')
                                        ->orderBy('price')
                                        ->first();

                                    $compareProductUrl = $compareProductMerchant && $product['item']->slug
                                        ? route('front.product', ['slug' => $product['item']->slug, 'vendor_id' => $compareProductMerchant->user_id, 'merchant_product_id' => $compareProductMerchant->id])
                                        : ($product['item']->slug ? route('front.product.legacy', $product['item']->slug) : '#');
                                @endphp
                                <td>
                                    <a href="{{ $compareProductUrl }}">
                                        <h6 class="product-title">{{ getLocalizedProductName($product['item']) }}</h6>
                                    </a>
                                </td>
                            @endforeach
                        </tr>
                        <tr class="wow-replaced" data-wow-delay=".1s">
                            <td>
                                <h6 class="td-title">@lang('product price')</h6>
                            </td>
                            @foreach ($products as $product)
                                <td><span class="table-pera">{{ $product['item']->showPrice() }}</span></td>
                            @endforeach

                        </tr>

                        {{-- SKU Row --}}
                        <tr class="wow-replaced" data-wow-delay=".1s">
                            <td>
                                <h6 class="td-title">@lang('SKU')</h6>
                            </td>
                            @foreach ($products as $product)
                                <td><span class="table-pera font-monospace">{{ $product['item']->sku ?? __('N/A') }}</span></td>
                            @endforeach
                        </tr>

                        {{-- Brand Row --}}
                        <tr class="wow-replaced" data-wow-delay=".1s">
                            <td>
                                <h6 class="td-title">@lang('Brand')</h6>
                            </td>
                            @foreach ($products as $product)
                                <td><span class="table-pera">{{ $product['item']->brand ? getLocalizedBrandName($product['item']->brand) : __('N/A') }}</span></td>
                            @endforeach
                        </tr>

                        {{-- Quality Brand Row --}}
                        <tr class="wow-replaced" data-wow-delay=".1s">
                            <td>
                                <h6 class="td-title">@lang('Quality Brand')</h6>
                            </td>
                            @foreach ($products as $product)
                                @php
                                    $compareMp = $product['merchant_product'] ?? null;
                                @endphp
                                <td><span class="table-pera">{{ $compareMp && $compareMp->qualityBrand ? $compareMp->qualityBrand->display_name : __('N/A') }}</span></td>
                            @endforeach
                        </tr>

                        {{-- Vendor Row --}}
                        <tr class="wow-replaced" data-wow-delay=".1s">
                            <td>
                                <h6 class="td-title">@lang('Vendor')</h6>
                            </td>
                            @foreach ($products as $product)
                                @php
                                    $compareMp = $product['merchant_product'] ?? null;
                                @endphp
                                <td><span class="table-pera">{{ $compareMp && $compareMp->user ? $compareMp->user->shop_name : __('N/A') }}</span></td>
                            @endforeach
                        </tr>

                        {{-- Stock Row --}}
                        <tr class="wow-replaced" data-wow-delay=".1s">
                            <td>
                                <h6 class="td-title">@lang('Stock')</h6>
                            </td>
                            @foreach ($products as $product)
                                @php
                                    $compareMp = $product['merchant_product'] ?? null;
                                    $compareStock = $compareMp ? $compareMp->stock : null;
                                    if ($compareStock === null || $compareStock === '') {
                                        $compareStockText = __('Unlimited');
                                        $compareStockClass = 'text-success';
                                    } elseif ($compareStock == 0) {
                                        $compareStockText = __('Out Of Stock');
                                        $compareStockClass = 'text-danger';
                                    } else {
                                        $compareStockText = $compareStock . ' ' . __('Available');
                                        $compareStockClass = 'text-primary';
                                    }
                                @endphp
                                <td><span class="table-pera {{ $compareStockClass }}">{{ $compareStockText }}</span></td>
                            @endforeach
                        </tr>

                        <tr class="wow-replaced" data-wow-delay=".1s">
                            <td>
                                <h6 class="td-title">@lang('Rating')</h6>
                            </td>
                            @foreach ($products as $product)
                                @php
                                    $productWithRatings = App\Models\Product::withCount('ratings')
                                        ->withAvg('ratings', 'rating')
                                        ->find($product['item']->id);
                                @endphp
                                <td><span class="table-pera">{{ number_format($productWithRatings->ratings_avg_rating, 1) }}
                                        ({{ $productWithRatings->ratings_count }} @lang('Review'))
                                    </span></td>
                            @endforeach



                        </tr>
                        <tr class="wow-replaced" data-wow-delay=".1s">
                            <td>
                                <h6 class="td-title">@lang('Description')</h6>
                            </td>

                            @foreach ($products as $product)
                                <td>
                                    <span class="table-pera">
                                        {{ strip_tags($product['item']->details) }}
                                    </span>
                                </td>
                            @endforeach
                        </tr>
                        <tr class="wow-replaced" data-wow-delay=".1s">
                            <td>
                                <h6 class="td-title">@lang('Action')</h6>
                            </td>
                            @foreach ($products as $product)
                                <td class="btn-wrapper">
                                    <div class="hover-area">
                                        @if ($product['item']->product_type == 'affiliate')
                                            <a href="javascript:;" data-href="{{ $product['item']->affiliate_link }}"
                                                class="template-btn dark-btn w-10 add_to_cart_button affilate-btn"
                                                aria-label="{{ __('Add To Cart') }}"></a>
                                        @else
                                            @if ($product['item']->emptyStock())
                                                <a class="template-btn dark-btn w-100" href="#"
                                                    title="{{ __('Out Of Stock') }}"><i></i></a>
                                            @else
                                                @if ($product['item']->type != 'Listing')
                                                    <a href="javascript:;"
                                                     data-href="{{ route('product.cart.add', $product['item']->id) }}"
                                                        class=" add_cart_click template-btn dark-btn w-100">
                                                        @lang('Add To Cart')
                                                    </a>
                                                @endif
                                            @endif
                                        @endif
                                    </div>
                                </td>
                            @endforeach

                        </tr>
                        <tr class="wow-replaced" data-wow-delay=".1s">
                            <td>
                                <h6 class="td-title">@lang('Remove')</h6>
                            </td>
                            @foreach ($products as $product)
                                <td>
                                    <a href="{{ route('product.compare.remove', $product['item']->id) }}"
                                        class="template-btn danger-btn w-100">@lang('Remove')</a>
                                </td>
                            @endforeach
                        </tr>
                    </table>
                </div>
            @else
                <div class="row text-center">


                    <div class="col-lg-12">
                        <div class="compare-empty">
                            <h2 class="mb-4">@lang('Nothing to Compare')</h2>
                            <a href="{{ route('front.index') }}" class="template-btn">@lang('Continue Shopping')</a>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection
