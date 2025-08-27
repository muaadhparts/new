@extends('layouts.front')

@section('content')

<!-- Brand Section -->
<section class="gs-brand-section home-3 bg-light-white py-4">
    <livewire:search-box/>

    <div class="container title-box-and-brands-container">

        <!-- Title Box -->
        <div class="title-box-wrapper">
            <div class="row justify-content-center align-items-center h-100">
                <div class="col-12">
                    <div class="gs-title-box">
                        <h2 class="title wow-replaced">@lang('Genuine Parts Catalogues')</h2>
                        <p class="des mb-0 wow-replaced" data-wow-delay=".1s">
                            @lang('Explore genuine OEM parts catalogues for all vehicle models. Fast search. precise results, and certified quality guaranteed.')
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Brands -->
        <div class="gs-brands">
            <div class="row gy-4 row-cols-xxl-4 row-cols-xl-3 row-cols-lg-4 row-cols-md-3 row-cols-sm-2 row-cols-1 justify-content-center">
                @foreach (DB::table('brands')->get() as $brand)
                    <div class="col">
                        <div class="wow-replaced" data-wow-delay=".1s">
                            <a href="{{ route('catlogs.index', $brand->name) }}">
                                <div class="single-brand">
                                    <img src="{{ asset('assets/images/brand/' . $brand->photo) }}" alt="brand" class="img-fluid">
                                </div>
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

    </div>
</section>
<!-- Brand Section Completed -->

<!-- Categories Section -->
<section class="gs-cate-section" dir="ltr">
    <div class="container wow-replaced">
        <div class="row gy-4 justify-content-center">
            @foreach ($featured_categories as $fcategory)
                <div class="col-lg-2 col-md-3 col-sm-4 col-6">
                    <a href="{{ route('front.category', $fcategory->slug) }}">
                        <div class="gs-single-cat h3-gs-single-cat">
                            <img class="cate-img square" 
                                 src="{{ asset('assets/images/categories/' . $fcategory->image) }}" 
                                 alt="category img">
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
</section>

<!-- Service Section -->
<section class="gs-service-section px-4 bg-light-white">
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

@endsection
