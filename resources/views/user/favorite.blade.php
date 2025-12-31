@extends('layouts.front')

@section('content')
<section class="gs-breadcrumb-section">
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
<div class="gs-blog-wrapper muaadh-section-gray">
    <div class="container">
        <div class="row flex-column-reverse flex-lg-row">

            <div class="col-12 col-lg-12 col-xl-12 gs-main-blog-wrapper">
                <div class=" product-nav-wrapper">
                    <h5>@lang('Total Products Found:') <span id="favorite-count">{{ $favorites->count() }}</span></h5>
                </div>
                @if($favorites->count() > 0)
                <div class="row gy-4 mt-20">
                    @foreach ($favorites as $favoriteItem)
                    @php
                        $catalogItem = $favoriteItem->catalogItem;
                        $mp = $favoriteItem->effective_merchant_item ?? $favoriteItem->merchantItem;
                    @endphp
                    @include('includes.frontend.home_catalog_item', [
                    'class' => 'col-6 col-md-4 col-lg-3',
                    'favorite' => true,
                    'favoriteId' => $favoriteItem->id,
                    'catalogItem' => $catalogItem,
                    'mp' => $mp
                    ])
                    @endforeach
                </div>
                {{ $favorites->links('includes.frontend.pagination') }}
                
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