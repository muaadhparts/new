@extends('layouts.admin')

@section('content')
<div class="content-area">
    <div class="mr-breadcrumb">
        <div class="row">
            <div class="col-lg-12">
                <h4 class="heading">{{ __('Edit Theme') }}: {{ $theme->name }}
                    <a class="add-btn" href="{{ route('admin-homethemes-index') }}">
                        <i class="fas fa-arrow-left"></i> {{ __('Back') }}
                    </a>
                </h4>
                <ul class="links">
                    <li>
                        <a href="{{ route('admin.dashboard') }}">{{ __('Dashboard') }} </a>
                    </li>
                    <li>
                        <a href="javascript:;">{{ __('Home Page Settings') }}</a>
                    </li>
                    <li>
                        <a href="{{ route('admin-homethemes-index') }}">{{ __('Home Page Themes') }}</a>
                    </li>
                    <li>
                        <a href="#">{{ __('Edit Theme') }}</a>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <div class="add-catalogItem-content1">
        <div class="row">
            <div class="col-lg-12">
                <div class="catalogItem-description">
                    <div class="body-area">
                        <div class="gocover" style="background: url({{ asset('assets/images/' . $gs->admin_loader) }}) no-repeat scroll center center rgba(45, 45, 45, 0.5);"></div>

                        <form id="muaadhform" action="{{ route('admin-homethemes-update', $theme->id) }}" method="POST">
                            @csrf
                            @method('PUT')
                            @include('alerts.admin.form-both')

                            {{-- Basic Info --}}
                            <div class="panel panel-default mb-4">
                                <div class="panel-heading">
                                    <h3 class="panel-title">{{ __('Basic Information') }}</h3>
                                </div>
                                <div class="panel-body">
                                    <div class="row">
                                        <div class="col-lg-6">
                                            <div class="form-group mb-3">
                                                <label class="control-label">{{ __('Theme Name') }} *</label>
                                                <input type="text" class="form-control" name="name" required value="{{ $theme->name }}">
                                            </div>
                                        </div>
                                        <div class="col-lg-6">
                                            <div class="form-group mb-3">
                                                <label class="control-label">{{ __('Slug') }}</label>
                                                <input type="text" class="form-control" name="slug" value="{{ $theme->slug }}">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-lg-6">
                                            <div class="form-group mb-3">
                                                <label class="control-label">{{ __('Layout') }}</label>
                                                <select class="form-control" name="layout">
                                                    <option value="default" {{ $theme->layout == 'default' ? 'selected' : '' }}>{{ __('Default') }}</option>
                                                    <option value="minimal" {{ $theme->layout == 'minimal' ? 'selected' : '' }}>{{ __('Minimal') }}</option>
                                                    <option value="full-width" {{ $theme->layout == 'full-width' ? 'selected' : '' }}>{{ __('Full Width') }}</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-lg-6">
                                            <div class="form-group mb-3">
                                                <label class="control-label">{{ __('Set as Active') }}</label>
                                                <label class="switch">
                                                    <input type="checkbox" name="is_active" value="1" {{ $theme->is_active ? 'checked' : '' }}>
                                                    <span class="slider round"></span>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Sections Toggle --}}
                            <div class="panel panel-default mb-4">
                                <div class="panel-heading">
                                    <h3 class="panel-title">{{ __('Sections Visibility') }}</h3>
                                </div>
                                <div class="panel-body">
                                    <div class="row">
                                        <div class="col-lg-4 col-md-6 mb-3">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <label class="control-label">{{ __('Slider') }}</label>
                                                <label class="switch">
                                                    <input type="checkbox" name="show_slider" value="1" {{ $theme->show_slider ? 'checked' : '' }}>
                                                    <span class="slider round"></span>
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-lg-4 col-md-6 mb-3">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <label class="control-label">{{ __('Hero Search') }}</label>
                                                <label class="switch">
                                                    <input type="checkbox" name="show_hero_search" value="1" {{ $theme->show_hero_search ? 'checked' : '' }}>
                                                    <span class="slider round"></span>
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-lg-4 col-md-6 mb-3">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <label class="control-label">{{ __('Brands') }}</label>
                                                <label class="switch">
                                                    <input type="checkbox" name="show_brands" value="1" {{ $theme->show_brands ? 'checked' : '' }}>
                                                    <span class="slider round"></span>
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-lg-4 col-md-6 mb-3">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <label class="control-label">{{ __('Categories') }}</label>
                                                <label class="switch">
                                                    <input type="checkbox" name="show_categories" value="1" {{ $theme->show_categories ? 'checked' : '' }}>
                                                    <span class="slider round"></span>
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-lg-4 col-md-6 mb-3">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <label class="control-label">{{ __('Arrival Section') }}</label>
                                                <label class="switch">
                                                    <input type="checkbox" name="show_arrival" value="1" {{ $theme->show_arrival ? 'checked' : '' }}>
                                                    <span class="slider round"></span>
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-lg-4 col-md-6 mb-3">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <label class="control-label">{{ __('Featured CatalogItems') }}</label>
                                                <label class="switch">
                                                    <input type="checkbox" name="show_featured_products" value="1" {{ $theme->show_featured_products ? 'checked' : '' }}>
                                                    <span class="slider round"></span>
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-lg-4 col-md-6 mb-3">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <label class="control-label">{{ __('Deal of the Day') }}</label>
                                                <label class="switch">
                                                    <input type="checkbox" name="show_deal_of_day" value="1" {{ $theme->show_deal_of_day ? 'checked' : '' }}>
                                                    <span class="slider round"></span>
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-lg-4 col-md-6 mb-3">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <label class="control-label">{{ __('Top Rated') }}</label>
                                                <label class="switch">
                                                    <input type="checkbox" name="show_top_rated" value="1" {{ $theme->show_top_rated ? 'checked' : '' }}>
                                                    <span class="slider round"></span>
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-lg-4 col-md-6 mb-3">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <label class="control-label">{{ __('Big Save') }}</label>
                                                <label class="switch">
                                                    <input type="checkbox" name="show_big_save" value="1" {{ $theme->show_big_save ? 'checked' : '' }}>
                                                    <span class="slider round"></span>
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-lg-4 col-md-6 mb-3">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <label class="control-label">{{ __('Trending') }}</label>
                                                <label class="switch">
                                                    <input type="checkbox" name="show_trending" value="1" {{ $theme->show_trending ? 'checked' : '' }}>
                                                    <span class="slider round"></span>
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-lg-4 col-md-6 mb-3">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <label class="control-label">{{ __('Best Sellers') }}</label>
                                                <label class="switch">
                                                    <input type="checkbox" name="show_best_sellers" value="1" {{ $theme->show_best_sellers ? 'checked' : '' }}>
                                                    <span class="slider round"></span>
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-lg-4 col-md-6 mb-3">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <label class="control-label">{{ __('Blogs') }}</label>
                                                <label class="switch">
                                                    <input type="checkbox" name="show_blogs" value="1" {{ $theme->show_blogs ? 'checked' : '' }}>
                                                    <span class="slider round"></span>
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-lg-4 col-md-6 mb-3">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <label class="control-label">{{ __('Services') }}</label>
                                                <label class="switch">
                                                    <input type="checkbox" name="show_services" value="1" {{ $theme->show_services ? 'checked' : '' }}>
                                                    <span class="slider round"></span>
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-lg-4 col-md-6 mb-3">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <label class="control-label">{{ __('Newsletter') }}</label>
                                                <label class="switch">
                                                    <input type="checkbox" name="show_newsletter" value="1" {{ $theme->show_newsletter ? 'checked' : '' }}>
                                                    <span class="slider round"></span>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Section Order --}}
                            <div class="panel panel-default mb-4">
                                <div class="panel-heading">
                                    <h3 class="panel-title">{{ __('Sections Order') }}</h3>
                                </div>
                                <div class="panel-body">
                                    <div class="row">
                                        <div class="col-lg-3 col-md-4 mb-3">
                                            <label class="control-label">{{ __('Slider Order') }}</label>
                                            <input type="number" class="form-control" name="order_slider" value="{{ $theme->order_slider }}" min="1">
                                        </div>
                                        <div class="col-lg-3 col-md-4 mb-3">
                                            <label class="control-label">{{ __('Brands Order') }}</label>
                                            <input type="number" class="form-control" name="order_brands" value="{{ $theme->order_brands }}" min="1">
                                        </div>
                                        <div class="col-lg-3 col-md-4 mb-3">
                                            <label class="control-label">{{ __('Categories Order') }}</label>
                                            <input type="number" class="form-control" name="order_categories" value="{{ $theme->order_categories }}" min="1">
                                        </div>
                                        <div class="col-lg-3 col-md-4 mb-3">
                                            <label class="control-label">{{ __('Arrival Order') }}</label>
                                            <input type="number" class="form-control" name="order_arrival" value="{{ $theme->order_arrival }}" min="1">
                                        </div>
                                        <div class="col-lg-3 col-md-4 mb-3">
                                            <label class="control-label">{{ __('Featured CatalogItems Order') }}</label>
                                            <input type="number" class="form-control" name="order_featured_products" value="{{ $theme->order_featured_products }}" min="1">
                                        </div>
                                        <div class="col-lg-3 col-md-4 mb-3">
                                            <label class="control-label">{{ __('Deal of Day Order') }}</label>
                                            <input type="number" class="form-control" name="order_deal_of_day" value="{{ $theme->order_deal_of_day }}" min="1">
                                        </div>
                                        <div class="col-lg-3 col-md-4 mb-3">
                                            <label class="control-label">{{ __('Top Rated Order') }}</label>
                                            <input type="number" class="form-control" name="order_top_rated" value="{{ $theme->order_top_rated }}" min="1">
                                        </div>
                                        <div class="col-lg-3 col-md-4 mb-3">
                                            <label class="control-label">{{ __('Big Save Order') }}</label>
                                            <input type="number" class="form-control" name="order_big_save" value="{{ $theme->order_big_save }}" min="1">
                                        </div>
                                        <div class="col-lg-3 col-md-4 mb-3">
                                            <label class="control-label">{{ __('Trending Order') }}</label>
                                            <input type="number" class="form-control" name="order_trending" value="{{ $theme->order_trending }}" min="1">
                                        </div>
                                        <div class="col-lg-3 col-md-4 mb-3">
                                            <label class="control-label">{{ __('Best Sellers Order') }}</label>
                                            <input type="number" class="form-control" name="order_best_sellers" value="{{ $theme->order_best_sellers }}" min="1">
                                        </div>
                                        <div class="col-lg-3 col-md-4 mb-3">
                                            <label class="control-label">{{ __('Blogs Order') }}</label>
                                            <input type="number" class="form-control" name="order_blogs" value="{{ $theme->order_blogs }}" min="1">
                                        </div>
                                        <div class="col-lg-3 col-md-4 mb-3">
                                            <label class="control-label">{{ __('Services Order') }}</label>
                                            <input type="number" class="form-control" name="order_services" value="{{ $theme->order_services }}" min="1">
                                        </div>
                                        <div class="col-lg-3 col-md-4 mb-3">
                                            <label class="control-label">{{ __('Newsletter Order') }}</label>
                                            <input type="number" class="form-control" name="order_newsletter" value="{{ $theme->order_newsletter }}" min="1">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Section Titles --}}
                            <div class="panel panel-default mb-4">
                                <div class="panel-heading">
                                    <h3 class="panel-title">{{ __('Section Titles (Optional - leave empty for defaults)') }}</h3>
                                </div>
                                <div class="panel-body">
                                    <div class="row">
                                        <div class="col-lg-4 col-md-6 mb-3">
                                            <label class="control-label">{{ __('Brands Title') }}</label>
                                            <input type="text" class="form-control" name="title_brands" value="{{ $theme->title_brands }}" placeholder="{{ __('Genuine Parts Catalogues') }}">
                                        </div>
                                        <div class="col-lg-4 col-md-6 mb-3">
                                            <label class="control-label">{{ __('Categories Title') }}</label>
                                            <input type="text" class="form-control" name="title_categories" value="{{ $theme->title_categories }}" placeholder="{{ __('Shop by Category') }}">
                                        </div>
                                        <div class="col-lg-4 col-md-6 mb-3">
                                            <label class="control-label">{{ __('Featured CatalogItems Title') }}</label>
                                            <input type="text" class="form-control" name="title_featured_products" value="{{ $theme->title_featured_products }}" placeholder="{{ __('Featured CatalogItems') }}">
                                        </div>
                                        <div class="col-lg-4 col-md-6 mb-3">
                                            <label class="control-label">{{ __('Deal of Day Title') }}</label>
                                            <input type="text" class="form-control" name="title_deal_of_day" value="{{ $theme->title_deal_of_day }}" placeholder="{{ __('Deal of the Day') }}">
                                        </div>
                                        <div class="col-lg-4 col-md-6 mb-3">
                                            <label class="control-label">{{ __('Top Rated Title') }}</label>
                                            <input type="text" class="form-control" name="title_top_rated" value="{{ $theme->title_top_rated }}" placeholder="{{ __('Top Rated CatalogItems') }}">
                                        </div>
                                        <div class="col-lg-4 col-md-6 mb-3">
                                            <label class="control-label">{{ __('Big Save Title') }}</label>
                                            <input type="text" class="form-control" name="title_big_save" value="{{ $theme->title_big_save }}" placeholder="{{ __('Big Save CatalogItems') }}">
                                        </div>
                                        <div class="col-lg-4 col-md-6 mb-3">
                                            <label class="control-label">{{ __('Trending Title') }}</label>
                                            <input type="text" class="form-control" name="title_trending" value="{{ $theme->title_trending }}" placeholder="{{ __('Trending CatalogItems') }}">
                                        </div>
                                        <div class="col-lg-4 col-md-6 mb-3">
                                            <label class="control-label">{{ __('Best Sellers Title') }}</label>
                                            <input type="text" class="form-control" name="title_best_sellers" value="{{ $theme->title_best_sellers }}" placeholder="{{ __('Best Selling CatalogItems') }}">
                                        </div>
                                        <div class="col-lg-4 col-md-6 mb-3">
                                            <label class="control-label">{{ __('Blogs Title') }}</label>
                                            <input type="text" class="form-control" name="title_blogs" value="{{ $theme->title_blogs }}" placeholder="{{ __('From Our Blog') }}">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- CatalogItem Counts --}}
                            <div class="panel panel-default mb-4">
                                <div class="panel-heading">
                                    <h3 class="panel-title">{{ __('CatalogItem Counts Per Section') }}</h3>
                                </div>
                                <div class="panel-body">
                                    <div class="row">
                                        <div class="col-lg-3 col-md-4 mb-3">
                                            <label class="control-label">{{ __('Featured CatalogItems') }}</label>
                                            <input type="number" class="form-control" name="count_featured_products" value="{{ $theme->count_featured_products }}" min="1" max="24">
                                        </div>
                                        <div class="col-lg-3 col-md-4 mb-3">
                                            <label class="control-label">{{ __('Top Rated') }}</label>
                                            <input type="number" class="form-control" name="count_top_rated" value="{{ $theme->count_top_rated }}" min="1" max="24">
                                        </div>
                                        <div class="col-lg-3 col-md-4 mb-3">
                                            <label class="control-label">{{ __('Big Save') }}</label>
                                            <input type="number" class="form-control" name="count_big_save" value="{{ $theme->count_big_save }}" min="1" max="24">
                                        </div>
                                        <div class="col-lg-3 col-md-4 mb-3">
                                            <label class="control-label">{{ __('Trending') }}</label>
                                            <input type="number" class="form-control" name="count_trending" value="{{ $theme->count_trending }}" min="1" max="24">
                                        </div>
                                        <div class="col-lg-3 col-md-4 mb-3">
                                            <label class="control-label">{{ __('Best Sellers') }}</label>
                                            <input type="number" class="form-control" name="count_best_sellers" value="{{ $theme->count_best_sellers }}" min="1" max="24">
                                        </div>
                                        <div class="col-lg-3 col-md-4 mb-3">
                                            <label class="control-label">{{ __('Blogs') }}</label>
                                            <input type="number" class="form-control" name="count_blogs" value="{{ $theme->count_blogs }}" min="1" max="12">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-lg-12 text-center">
                                    <button class="btn btn-primary btn-lg" type="submit">
                                        <i class="fas fa-save me-2"></i>{{ __('Update Theme') }}
                                    </button>
                                </div>
                            </div>

                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
