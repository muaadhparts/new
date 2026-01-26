{{--
================================================================================
ALL CATALOGS PAGE - DTO-Based Architecture (Data Flow Policy Compliant)
================================================================================
All data comes from CatalogsPageDTO ($page) - pre-computed in CatalogsPageDataBuilder
Views only READ properties - no queries, no logic, no null coalescing
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
                <h2 class="muaadh-section-name">@lang('Shop by Catalog')</h2>
            </div>

            @if(count($page->catalogs) > 0)
                <div class="muaadh-categories-grid">
                    @foreach ($page->catalogs as $catalog)
                        <a href="{{ $catalog->detailsUrl }}" class="muaadh-category-card">
                            <div class="muaadh-category-img">
                                <img src="{{ $catalog->largeImageUrl }}" alt="{{ $catalog->name }}" loading="lazy">
                                <span class="muaadh-category-count">{{ $catalog->itemsCount }}</span>
                            </div>
                            <div class="muaadh-category-info">
                                <h6 class="muaadh-category-name">{{ $catalog->name }}</h6>
                                <span class="muaadh-category-catalogItems">{{ $catalog->itemsCount }} @lang('Items')</span>
                            </div>
                        </a>
                    @endforeach
                </div>

                {{-- Pagination --}}
                @if($page->hasMorePages || $page->currentPage > 1)
                    <div class="d-flex justify-content-center mt-5">
                        <nav aria-label="Catalogs pagination">
                            <ul class="pagination pagination-lg">
                                @foreach($page->paginationLinks as $link)
                                    @if($link['disabled'])
                                        <li class="page-item disabled">
                                            <span class="page-link">{!! $link['label'] !!}</span>
                                        </li>
                                    @elseif($link['active'])
                                        <li class="page-item active" aria-current="page">
                                            <span class="page-link">{{ $link['label'] }}</span>
                                        </li>
                                    @else
                                        <li class="page-item">
                                            <a class="page-link" href="{{ $link['url'] }}">{!! $link['label'] !!}</a>
                                        </li>
                                    @endif
                                @endforeach
                            </ul>
                        </nav>
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
