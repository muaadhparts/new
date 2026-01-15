@extends('layouts.front')
@section('content')
    <section class="gs-breadcrumb-section">
        <div class="container">
            <div class="row justify-content-center content-wrapper">
                <div class="col-12">
                    <h2 class="breadcrumb-name">@lang('Cart')</h2>
                    <ul class="bread-menu">
                        <li><a href="{{ route('front.index') }}">@lang('Home')</a></li>
                        <li><a href="{{route("front.cart")}}">@lang('Cart')</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </section>
    <section class="gs-cart-section load_cart muaadh-section-gray">
        @include('frontend.ajax.cart-page')
    </section>
@endsection
