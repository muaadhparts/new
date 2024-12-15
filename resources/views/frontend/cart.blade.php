@extends('layouts.front')
@section('content')
    <section class="gs-breadcrumb-section bg-class"
        data-background="{{ $gs->breadcrumb_banner ? asset('assets/images/' . $gs->breadcrumb_banner) : asset('assets/images/noimage.png') }}">
        <div class="container">
            <div class="row justify-content-center content-wrapper">
                <div class="col-12">
                    <h2 class="breadcrumb-title">@lang('Cart')</h2>
                    <ul class="bread-menu">
                        <li><a href="{{ route('front.index') }}">@lang('Home')</a></li>
                        <li><a href="{{route("front.cart")}}">@lang('Cart')</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </section>
    <section class="gs-cart-section load_cart">
        @include('frontend.ajax.cart-page')
    </section>
@endsection
