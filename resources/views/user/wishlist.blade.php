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
                    <h5>@lang('Total Products Found:') <span id="wishlist-count">{{ $wishlists->count() }}</span></h5>
                </div>
                @if($wishlists->count() > 0) 
                <div class="row gy-4 mt-20">
                    @foreach ($wishlists as $product)
                    @include('includes.frontend.home_product', [
                    'class' => 'col-sm-6 col-md-6 col-lg-4 col-xl-3',
                    'wishlist' => true,
                    ])
                    @endforeach
                </div>
                {{ $wishlists->links('includes.frontend.pagination') }}
                
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