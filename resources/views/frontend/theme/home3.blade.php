@extends('layouts.front')

@section('content')
    <!-- hero section start -->
    <section class="hero-slider-wrapper">
        @foreach ($sliders as $slider)
            <div class="gs-hero-section" data-background="{{ asset('assets/images/sliders/' . $slider->photo) }}">
                <div class="container">
                    <div class="row align-items-center">
                        <div class="col-12 col-md-6 col-lg-6">
                            <div class="hero-content">
                                <h6 class="subtitle wow-replaced" style="color:{{ $slider->subtitle_color }}">
                                    {{ $slider->subtitle_text }}</h6>
                                <h1 class="title wow-replaced" data-wow-delay=".1s" style="color:{{ $slider->title_color }}">
                                    {{ $slider->title_text }}</h1>
                                <p class="des wow-replaced" data-wow-delay=".2s" style="color:{{ $slider->title_color }}">
                                    {{ $slider->details_text }}
                                    @php

                                    @endphp
                                </p>
                                <a class="template-btn hero-shop-now-btn wow-replaced " data-wow-delay=".3s"
                                    href="{{ $slider->link }}">
                                    @lang('Shop Now')
                                </a>

                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach

    </section>
    <!-- hero section end -->




    <!-- categories section start -->
    <div class="gs-cate-section">
        <div class="container wow-replaced">
            <div class="home3-cate-slider">
                @foreach ($featured_categories as $fcategory)
                    <div class="col-lg-2">
                        <a href="{{ route('front.category', $fcategory->slug) }}">
                            <div class="gs-single-cat h3-gs-single-cat">
                                <img class="cate-img square"
                                    src="{{ asset('assets/images/categories/' . $fcategory->image) }}" alt="category img">
                                <div class="inner-box">
                                    <h6 class="title">{{ $fcategory->name }}</h6>
                                    <p class="des">{{ $fcategory->products_count }} @lang('Products')</p>
                                </div>
                            </div>
                        </a>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
    <!-- categories section end -->

    @if ($ps->deal_of_the_day == 1)
        <!-- Deal of the Day -->
        <section class="gs-deal-of-day gs-deal-of-day-home2 gs-deal-of-day-home3">
            <div class="container">
                <div class="row">
                    <div class="col-lg-6  col-md-12 res-deal-img">
                        <img src="{{ $gs->deal_background ? asset('assets/images/' . $gs->deal_background) : asset('assets/images/noimage.png') }}"
                            class="img-fluid d-lg-none" alt="deal of the day">
                    </div>

                    <div class="deal-of-day-img  h-100">
                        <img class="wow-replaced h-100"
                            src="{{ $gs->deal_background ? asset('assets/images/' . $gs->deal_background) : asset('assets/images/noimage.png') }}"
                            alt="deal of the day">
                    </div>
                    <div class="col-lg-6 col-md-12">
                        <div class="deal-of-day-wrapper">
                            <div class="deal-of-day-content">
                                <h2 class="title wow-replaced">@lang('Deal') <br> @lang('of the Day')</h2>
                                <h6 class="sub-title wow-replaced" data-wow-delay=".1s">{{ $gs->deal_title }}</h6>
                                <p class="deal-description wow-replaced" data-wow-delay=".2s">{{ $gs->deal_details }}</p>
                                <div class="countdown-wrapper flex-wrap " id="countdown">
                                    <div class="countdown-item-wrapper d-flex">

                                        <div class="countdown-item wow-replaced" data-wow-delay=".3s">
                                            <h6 class="countdown-number" id="days"><span
                                                    class="countdown-title">@lang('Day')</span></h6>
                                            <span class="countdown-title">@lang('Day')</span>
                                        </div>
                                        <div class="countdown-item wow-replaced" data-wow-delay=".4s">
                                            <h6 class="countdown-number" id="hours"><span
                                                    class="countdown-title">@lang('Hour')</span></h6>
                                            <span class="countdown-title">@lang('Hour')</span>

                                        </div>
                                        <div class="countdown-item wow-replaced" data-wow-delay=".5s">
                                            <h6 class="countdown-number" id="minutes"><span
                                                    class="countdown-title">@lang('Min')</span></h6>
                                            <span class="countdown-title">@lang('Min')</span>
                                        </div>
                                        <div class="countdown-item wow-replaced" data-wow-delay=".6s">
                                            <h6 class="countdown-number" id="seconds"><span
                                                    class="countdown-title">@lang('Sec')</span></h6>
                                            <span class="countdown-title">@lang('Sec')</span>
                                        </div>
                                    </div>
                                    <a href="#" class="template-btn w-100 wow-replaced"
                                        data-wow-delay=".7s">@lang('Shop
                                                                        Now')</a>
                                </div>

                            </div>
                        </div>
                    </div>


                </div>
            </div>
            @php
                $date = Carbon\Carbon::createFromFormat('Y-m-d', $gs->deal_time);
                $formattedDate = $date->format('Y-m-d\TH:i:s');
            @endphp
            <input type="hidden" id="countdown-date" value="{{ $formattedDate }}">

        </section>
        <!-- Deal of the Day Completed -->
    @endif



    <!-- explore product section start -->
    <section class="gs-explore-product-section bg-light-white">
        <div class="container">
            <!-- title box  & nav-tab -->
            <div class="row mb-36 justify-content-center">
                <div class="col-12">
                    <div class="gs-title-box text-center">
                        <h2 class="title wow-replaced">@lang('Explore Our Products')</h2>
                    </div>
                    <!-- product nav  -->
                    <ul class="nav explore-tab-navbar wow-replaced" data-wow-delay=".1s" id="myTab" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="ex-product-1" data-bs-toggle="tab"
                                data-bs-target="#ex-product-1-pane" type="button" role="tab"
                                aria-controls="ex-product-1-pane" aria-selected="true">@lang('NEW ARRIVAL')</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="ex-product-2" data-bs-toggle="tab"
                                data-bs-target="#ex-product-2-pane" type="button" role="tab"
                                aria-controls="ex-product-2-pane" aria-selected="false">@lang('TRENDING')</button>
                        </li>

                    </ul>
                </div>
            </div>


            <!-- tab content -->
            <div class="tab-content" id="myTabContent">
                <div class="tab-pane fade show active" id="ex-product-1-pane" role="tabpanel"
                    aria-labelledby="ex-product-1" tabindex="0">
                    <div class="row gy-4">
                        @foreach ($latest_products as $product)
                            @include('includes.frontend.home_product')
                        @endforeach
                    </div>
                </div>


                <div class="tab-pane fade" id="ex-product-2-pane" role="tabpanel" aria-labelledby="ex-product-2"
                    tabindex="0">

                    <div class="row gy-4">
                        @foreach ($trending_products as $product)
                            @include('includes.frontend.home_product')
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- explore product section end -->


    <!-- product offer section start -->
    <section class="gs-offer-section home-3 py-120">
        <div class="container">
            <!-- title box -->
            <div class="row mb-60 justify-content-center">
                <div class="col-lg-7">
                    <div class="gs-title-box text-center">
                        <h2 class="title wow-replaced">@lang('Best Month offer') </h2>
                        <p class="des mb-0 wow-replaced" data-wow-delay=".1s">@lang('Erat pellentesque curabitur euismod dui
                                                etiam pellentesque rhoncus fermentum tristique lobortis lectus magnis. Consequat porta turpis
                                                maecenas')</p>
                    </div>
                </div>
            </div>

            <!-- main content -->
            <div class="row gy-4">
                <div class="col-lg-8  wow-replaced" data-wow-delay=".2s">
                    <a href="{{ $arrivals[0]['url'] }}" class="">
                        <div class="single-offer-product verticle h-100">
                            <img class="promo-img" src="{{ asset('assets/images/arrival/' . $arrivals[0]['photo']) }}"
                                alt="offer product">

                        </div>
                    </a>
                </div>
                <!-- larger than lg device -->
                <div class="col-lg-4 d-none d-lg-block">
                    <div class="product-wrapper">
                        <a href="{{ $arrivals[1]['url'] }}" class="wow-replaced">
                            <div class="single-offer-product">
                                <img class="promo-img"
                                    src="{{ asset('assets/images/arrival/' . $arrivals[1]['photo']) }}"
                                    alt="offer product">
                            </div>
                        </a>
                        <a href="{{ $arrivals[2]['url'] }}" class="wow-replaced">
                            <div class="single-offer-product">
                                <img class="promo-img"
                                    src="{{ asset('assets/images/arrival/' . $arrivals[2]['photo']) }}"
                                    alt="offer product">
                            </div>
                        </a>
                    </div>
                </div>
                <!-- smaller then lg device -->
                <div class="col-md-6 d-lg-none">
                    <a href="{{ $arrivals[1]['url'] }}" class="wow-replaced">
                        <div class="single-offer-product">
                            <img class="promo-img"
                                src="{{ asset('assets/images/arrival/' . $arrivals[1]['photo']) }}"
                                alt="offer product">
                        </div>
                    </a>
                </div>
                <div class="col-md-6 d-lg-none">
                    <a href="{{ $arrivals[2]['url'] }}" class="wow-replaced">
                        <div class="single-offer-product">
                            <img class="promo-img"
                                src="{{ asset('assets/images/arrival/' . $arrivals[2]['photo']) }}"
                                alt="offer product">
                        </div>
                    </a>
                </div>

            </div>
        </div>
    </section>
    <!-- product offer section end -->


    <!-- Faetured Products Section Started -->
    <section class="gs-explore-product-section bg-light-white">
        <div class="container">
            <!-- title box -->
            <div class="mb-60">
                <div class="gs-title-box ">
                    <h2 class="title wow-replaced">@lang('Our Featured Products') </h2>
                </div>
            </div>
            <!--  content -->
            <div class="row product-cards-slider gy-4 mt-4 mt-lg-0">
                @foreach ($popular_products as $product)
                    @include('includes.frontend.home_product')
                @endforeach
            </div>
        </div>
    </section>
    <!-- Featured Product Section Completed -->

    <!-- Service Section -->
    <section class="gs-service-section px-4  bg-light-white">
        <div class="container">
            <div class="row service-row">
                @foreach (DB::table('services')->get() as $service)
                    <div class="col-lg-3 col-md-6 col-sm-12 services-area wow-removed">
                        <div class="single-service d-flex flex-lg-column flex-xl-row text-lg-center text-xl-start">
                            <div class="icon-wrapper">
                                <img src="{{ asset('assets/images/services/' . $service->photo) }}" alt="service">
                            </div>
                            <div class="service-content">
                                <h6 class="service-title">{{ $service->title }}</h6>
                                <p class="service-desc">{{ $service->details }}</p>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
    <!-- Service Section Completed -->

    <!-- Explore Product Section -->
    <section class="gs-explore-product-section bg-light-white">
        <div class="container">
            <!-- title box -->
            <div class=" mb-60">
                <div class="gs-title-box ">
                    <h2 class="title wow-replaced">@lang('Best Selling Products') </h2>
                </div>
            </div>

            <div class="product-cards-slider">
                @foreach ($best_products as $product)
                    @include('includes.frontend.home_product')
                @endforeach
            </div>

        </div>
    </section>
    <!-- Explore Product Section Completed -->


    @if ($ps->blog == 1)
        <!-- Latest Post Section  -->
        <section class="gs-latest-post-section home-3 py-120 mt-0 mb-0">
            <div class="container">
                <div class="row mb-60 justify-content-center">
                    <div class="col-lg-7">
                        <div class="gs-title-box text-center">
                            <h2 class="title wow-replaced">@lang('Latest Post') </h2>
                            <p class="des mb-0 wow-replaced" data-wow-delay=".1s">@lang('Cillum eu id enim aliquip aute ullamco
                                                    anim. Culpa deserunt nostrud excepteur voluptate velit ipsum esse enim.')</p>
                        </div>
                    </div>
                </div>
                <div class="row gy-4 row-cols-1 row-cols-md-2 row-cols-lg-3 latest-post-area">

                    @foreach (App\Models\Blog::latest()->take(3)->get() as $blog)
                        <div class="col wow-replaced" data-wow-delay=".2s">
                            <a href="{{ route('front.blogshow', $blog->slug) }}" class="single-post h2-single-post">
                                <div class="post-img">
                                    <img src="{{ asset('assets/images/blogs/' . $blog->photo) }}" alt="post">
                                </div>
                                <div class="post-content-wrapper">
                                    <div class="post-content">
                                        <h6 class="post-title">{{ Str::limit($blog->title, 60) }}</h6>

                                        <div class="poster">
                                            <div class="poster-avater-wrapper">
                                                <img src="{{ asset('assets/images/admins/' . App\Models\Admin::first()->photo) }}"
                                                    alt="" class="avater">
                                            </div>
                                            <div class="poster_name-and-post_date-wrapper">
                                                <span class="poster_name">@lang('Admin')</span>
                                                <span
                                                    class="post_date">{{ date('d M, Y', strtotime($blog->created_at)) }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
        <!-- Latest Post Section   -->
    @endif
    <!-- Partner Section -->
    <section class="gs-partner-section home-3 bg-light-white py-120">
        <div class="container title-box-and-partners-container">
            <div class="title-box-wrapper">
                <div class="row justify-content-center align-items-center h-100">
                    <div class="col-12">
                        <div class="gs-title-box">
                            <h2 class="title wow-replaced">@lang('Our Partners') </h2>
                            <p class="des mb-0 wow-replaced" data-wow-delay=".1s">@lang('Cillum eu id enim aliquip aute
                                                        ullamco anim. Culpa deserunt nostrud excepteur voluptate velit ipsum esse enim Cillum eu id
                                                        enim aliquip aute ullamco.')
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="gs-partners">
                <div
                    class="row gy-4 row-cols-xxl-4 row-cols-xl-3 row-cols-lg-4 row-cols-md-3 row-cols-sm-2 row-cols-1 justify-content-center">

                    @foreach (DB::table('partners')->get() as $data)
                        <div class="col">
                            <div class="wow-replaced " data-wow-delay=".1s">
                                <a href="javascript:;">
                                    <div class="single-partner">
                                        <img src="{{ asset('assets/images/partner/' . $data->photo) }}" alt="partner">
                                    </div>
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

    </section>
    <!-- Partner Section Completed -->
@endsection
