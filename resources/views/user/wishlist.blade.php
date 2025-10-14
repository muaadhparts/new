@extends('layouts.front')

@section('content')
<section class="gs-breadcrumb-section bg-class"
    data-background="{{ $gs->breadcrumb_banner ? asset('assets/images/' . $gs->breadcrumb_banner) : asset('assets/images/noimage.png') }}">
    <div class="container">
        <div class="row justify-content-center content-wrapper">
            <div class="col-12">
                <h2 class="breadcrumb-title">@lang('Product')</h2>
                <ul class="bread-menu">
                    <li><a href="{{ route('front.index') }}">@lang('Home')</a></li>
                    <li><a href="javascript:;">@lang('Product')</a></li>
                </ul>
            </div>
        </div>
    </div>
</section>
<div class="gs-blog-wrapper">
    <div class="container">
        <div class="row flex-column-reverse flex-lg-row">

            <div class="col-12 col-lg-12 col-xl-12 gs-main-blog-wrapper">
                <div class=" product-nav-wrapper">
                    <h5>@lang('Total Products Found:') <span id="wishlist-count">{{ $wishlistItems->count() }}</span></h5>
                </div>
                @if($wishlistItems->count() > 0)
                <div class="row gy-4 mt-20">
                    @foreach ($wishlistItems as $wishlistItem)
                        @php
                            // Create a product object that includes vendor-specific data
                            $product = $wishlistItem->product;
                            if ($wishlistItem->effective_merchant_product) {
                                $product->vendor_user_id = $wishlistItem->effective_merchant_product->user_id;
                                $product->price = $wishlistItem->effective_merchant_product->price;
                                $product->previous_price = $wishlistItem->effective_merchant_product->previous_price;
                                $product->stock = $wishlistItem->effective_merchant_product->stock;
                                $product->wishlist_item_id = $wishlistItem->id;
                            }
                        @endphp
                        @include('includes.frontend.home_product', [
                            'class' => 'col-sm-6 col-md-6 col-lg-4 col-xl-3',
                            'wishlist' => true,
                        ])
                    @endforeach
                </div>
                {{ $wishlistItems->links('includes.frontend.pagination') }}
                
                @else
                <div class="product-nav-wrapper d-flex justify-content-center mt-4">
                    <h5>@lang('No Product Found')</h5>
                </div>
                @endif

            </div>
        </div>
    </div>
</div>
@endsection