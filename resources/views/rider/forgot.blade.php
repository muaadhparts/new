@extends('layouts.front')

@section('content')
    <section class="gs-breadcrumb-section bg-class"
        data-background="{{ $gs->breadcrumb_banner ? asset('assets/images/' . $gs->breadcrumb_banner) : asset('assets/images/noimage.png') }}">
        <div class="container">
            <div class="row justify-content-center content-wrapper">
                <div class="col-12">
                    <h2 class="breadcrumb-title">@lang('Rider Forgot Password')</h2>
                    <ul class="bread-menu">
                        <li><a href="{{ route('front.index') }}">Home</a></li>
                        <li><a href="#">@lang('Rider Forgot Password')</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </section>


    <section class="gs-reg-section wow-replaced" data-wow-delay=".1s">
        <div class="container">
            <div class="row">
                <div class="col-lg-6 mx-auto reg-area">
                    <div class="reg-content">
                        <h4 class="text-center">
                        @lang('Rider Forgot Password')
                        </h4>
                        <form action="{{ route('rider.forgot.submit') }}" method="POST">
                            @csrf
                            <div class="form-group">

                                <label for="email">@lang('Email Address')</label>
                                <input type="email" name="email" class="form-control" id="email"
                                    placeholder="@lang('Enter your email address')" value="">
                                <button type="submit" class="template-btn btn-forms">@lang('Submit')</button>

                                <p class="login-redirect"> <span><a href="{{ route('rider.login') }}">@lang('Go to login')</a></span>
                                </p>
                            </div>
                        </form>
                    </div>
                </div>
    </section>
@endsection
