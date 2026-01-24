{{--
================================================================================
ALL CATALOGS PAGE (with pagination)
================================================================================
Shows all catalogs with pagination based on theme settings
================================================================================
--}}

@extends('layouts.front')

@section('content')

{{-- Breadcrumb --}}
<section class="gs-breadcrumb-section">
    <div class="container">
        <div class="row justify-content-center content-wrapper">
            <div class="col-12">
                <h2 class="breadcrumb-name">@lang('All Catalogs')</h2>
                <ul class="bread-menu">
                    <li><a href="{{ route('front.index') }}">@lang('Home')</a></li>
                    <li><a href="javascript:;">@lang('Catalogs')</a></li>
                </ul>
            </div>
        </div>
    </div>
</section>

{{-- Main Content --}}
<div class="muaadh-page-wrapper muaadh-section-gray">
    <section class="muaadh-section">
        <div class="container">
            <div class="muaadh-section-header">
                <span class="muaadh-badge-primary">@lang('Browse Catalogs')</span>
                <h2 class="muaadh-section-name">{{ $theme->name_categories ?? __('Shop by Catalog') }}</h2>
            </div>

            @if($catalogs->count() > 0)
                <div class="muaadh-categories-grid">
                    @foreach ($catalogs as $catalog)
                        <a href="{{ route('front.catalog', ['brand' => $catalog->brand->slug, 'catalog' => $catalog->slug]) }}" class="muaadh-category-card">
                            <div class="muaadh-category-img">
                                <img src="{{ Storage::url($catalog->largeImagePath) }}" alt="{{ $catalog->localized_name }}" loading="lazy">
                                <span class="muaadh-category-count">{{ $catalog->items_count }}</span>
                            </div>
                            <div class="muaadh-category-info">
                                <h6 class="muaadh-category-name">{{ $catalog->localized_name }}</h6>
                                <span class="muaadh-category-catalogItems">{{ $catalog->items_count }} @lang('Items')</span>
                            </div>
                        </a>
                    @endforeach
                </div>

                {{-- Pagination --}}
                @if($catalogs->hasPages())
                    <div class="d-flex justify-content-center mt-5">
                        {!! $catalogs->links('includes.frontend.pagination') !!}
                    </div>
                @endif
            @else
                <div class="alert alert-info text-center">
                    <h5>@lang('No catalogs available')</h5>
                </div>
            @endif
        </div>
    </section>
</div>

@endsection
