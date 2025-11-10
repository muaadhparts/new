@extends('layouts.unified')
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
                            @foreach ($products as $merchantProductId => $productData)
                                @php
                                    $product = $productData['product'] ?? null;
                                @endphp
                                <td>
                                    @if($product)
                                        <a href="{{ route('front.product', ['slug' => $product->slug, 'vendor_id' => $productData['merchant_product']->user_id, 'merchant_product_id' => $productData['merchant_product']->id]) }}">
                                            <img class="img-fluid w-150"
                                                src="{{ $product->photo ? \Illuminate\Support\Facades\Storage::url($product->photo) : asset('assets/images/noimage.png') }}"
                                                alt="compare-img"
                                                style="max-width: 150px; height: auto; object-fit: cover;">
                                        </a>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                        <tr class="wow-replaced" data-wow-delay=".1s">
                            <td>
                                <h6 class="td-title">@lang('product name')</h6>
                            </td>

                            @foreach ($products as $merchantProductId => $productData)
                                @php
                                    $product = $productData['product'] ?? null;
                                @endphp
                                <td>
                                    @if($product)
                                        <a href="{{ route('front.product', ['slug' => $product->slug, 'vendor_id' => $productData['merchant_product']->user_id, 'merchant_product_id' => $productData['merchant_product']->id]) }}">
                                            <h6 class="product-title">{{ $product->name }}</h6>
                                        </a>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                        <tr class="wow-replaced" data-wow-delay=".1s">
                            <td>
                                <h6 class="td-title">@lang('Vendor')</h6>
                            </td>
                            @foreach ($products as $merchantProductId => $productData)
                                @php
                                    $merchantProduct = $productData['merchant_product'];
                                @endphp
                                <td>
                                    <span class="table-pera">{{ $merchantProduct->user->shop_name ?? $merchantProduct->user->name }}</span>
                                </td>
                            @endforeach
                        </tr>
                        <tr class="wow-replaced" data-wow-delay=".1s">
                            <td>
                                <h6 class="td-title">@lang('product price')</h6>
                            </td>
                            @foreach ($products as $merchantProductId => $productData)
                                @php
                                    $merchantProduct = $productData['merchant_product'];
                                    $currency = \App\Models\Currency::where('is_default', 1)->first();
                                @endphp
                                <td>
                                    <span class="table-pera">
                                        {{ $currency->sign }}{{ number_format($merchantProduct->price * $currency->value, 2) }}
                                        @if($merchantProduct->previous_price && $merchantProduct->previous_price > $merchantProduct->price)
                                            <del>{{ $currency->sign }}{{ number_format($merchantProduct->previous_price * $currency->value, 2) }}</del>
                                        @endif
                                    </span>
                                </td>
                            @endforeach

                        </tr>
                        <tr class="wow-replaced" data-wow-delay=".1s">
                            <td>
                                <h6 class="td-title">@lang('Stock')</h6>
                            </td>
                            @foreach ($products as $merchantProductId => $productData)
                                @php
                                    $merchantProduct = $productData['merchant_product'];
                                @endphp
                                <td>
                                    <span class="table-pera">
                                        @if($merchantProduct->stock && $merchantProduct->stock > 0)
                                            {{ $merchantProduct->stock }} @lang('in stock')
                                        @elseif($merchantProduct->stock === 0)
                                            @lang('Out of stock')
                                        @else
                                            @lang('Available')
                                        @endif
                                    </span>
                                </td>
                            @endforeach
                        </tr>
                        <tr class="wow-replaced" data-wow-delay=".1s">
                            <td>
                                <h6 class="td-title">@lang('Rating')</h6>
                            </td>
                            @foreach ($products as $merchantProductId => $productData)
                                @php
                                    $product = $productData['product'];
                                    $productWithRatings = App\Models\Product::withCount('ratings')
                                        ->withAvg('ratings', 'rating')
                                        ->find($product->id);
                                @endphp
                                <td><span class="table-pera">{{ number_format($productWithRatings->ratings_avg_rating ?? 0, 1) }}
                                        ({{ $productWithRatings->ratings_count ?? 0 }} @lang('Review'))
                                    </span></td>
                            @endforeach
                        </tr>
                        <tr class="wow-replaced" data-wow-delay=".1s">
                            <td>
                                <h6 class="td-title">@lang('Description')</h6>
                            </td>

                            @foreach ($products as $merchantProductId => $productData)
                                @php
                                    $product = $productData['product'];
                                @endphp
                                <td>
                                    <span class="table-pera">
                                        {{ strip_tags($product->details ?? '') }}
                                    </span>
                                </td>
                            @endforeach
                        </tr>
                        <tr class="wow-replaced" data-wow-delay=".1s">
                            <td>
                                <h6 class="td-title">@lang('Action')</h6>
                            </td>
                            @foreach ($products as $merchantProductId => $productData)
                                @php
                                    $product = $productData['product'];
                                    $merchantProduct = $productData['merchant_product'];
                                @endphp
                                <td class="btn-wrapper">
                                    <div class="hover-area">
                                        @if ($product->product_type == 'affiliate')
                                            <a href="javascript:;" data-href="{{ $product->affiliate_link }}"
                                                class="template-btn dark-btn w-100 add_to_cart_button affilate-btn">
                                                @lang('Buy Now')
                                            </a>
                                        @else
                                            @if (!$merchantProduct->stock || $merchantProduct->stock == 0)
                                                <span class="template-btn dark-btn w-100" title="{{ __('Out Of Stock') }}">
                                                    @lang('Out Of Stock')
                                                </span>
                                            @else
                                                @if ($product->type != 'Listing')
                                                    <a href="javascript:;"
                                                       data-href="{{ route('merchant.cart.add', $merchantProductId) }}"
                                                       data-merchant-product="{{ $merchantProductId }}"
                                                       data-product="{{ $product->id }}"
                                                        class="add_cart_click template-btn dark-btn w-100">
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
                            @foreach ($products as $merchantProductId => $productData)
                                <td>
                                    <a href="{{ route('merchant.compare.remove', $merchantProductId) }}"
                                        class="template-btn dark-outline w-100">@lang('Remove')</a>
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
