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
                                <td>
                                    <a href="#">
                                        <img class="img-fluid w-150"
                                            src="{{ $product['item']['thumbnail'] ? asset('assets/images/thumbnails/' . $product['item']['thumbnail']) : asset('assets/images/noimage.png') }}"
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
                                <td>
                                    <a href="#">
                                        <h6 class="product-title">{{ $product['item']['name'] }}</h6>
                                    </a>
                                </td>
                            @endforeach
                        </tr>
                        <tr class="wow-replaced" data-wow-delay=".1s">
                            <td>
                                <h6 class="td-title">@lang('product price')</h6>
                            </td>
                            @foreach ($products as $product)
                                <td><span
                                        class="table-pera">{{ App\Models\Product::find($product['item']['id'])->showPrice() }}</span>
                                </td>
                            @endforeach

                        </tr>
                        <tr class="wow-replaced" data-wow-delay=".1s">
                            <td>
                                <h6 class="td-title">@lang('Rating')</h6>
                            </td>
                            @foreach ($products as $product)
                                @php
                                    $product = App\Models\Product::withCount('ratings')
                                        ->withAvg('ratings', 'rating')
                                        ->find($product['item']['id']);
                                @endphp
                                <td><span class="table-pera">{{ number_format($product->ratings_avg_rating, 1) }}
                                        ({{ $product->ratings_count }} @lang('Review'))
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
                                        {{ strip_tags($product['item']['details']) }}
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
                                        @if ($product['item']['product_type'] == 'affiliate')
                                            <a href="javascript:;" data-href="{{ $product['item']['affiliate_link'] }}"
                                                class="template-btn dark-btn w-10 add_to_cart_button affilate-btn"
                                                aria-label="{{ __('Add To Cart') }}"></a>
                                        @else
                                            @if ($product['item']->emptyStock())
                                                <a class="template-btn dark-btn w-100" href="#"
                                                    title="{{ __('Out Of Stock') }}"><i></i></a>
                                            @else
                                                @if ($product['item']['type'] != 'Listing')
                                                    <a href="javascript:;"
                                                     data-href="{{ route('product.cart.add', $product['item']['id']) }}"
                                                        class=" add_cart_click template-btn dark-btn w-100">
                                                        @lang('Add To Cart')
                                                    </a>
                                                @endif
                                            @endif
                                        @endif


                                </td>
                            @endforeach

                        </tr>
                        <tr class="wow-replaced" data-wow-delay=".1s">
                            <td>
                                <h6 class="td-title">@lang('Remove')</h6>
                            </td>
                            @foreach ($products as $product)
                                <td>
                                    <a href="{{ route('product.compare.remove', $product['item']['id']) }}"
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
