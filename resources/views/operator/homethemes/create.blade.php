@extends('layouts.operator')

@section('content')
<div class="content-area">
    <div class="mr-breadcrumb">
        <div class="row">
            <div class="col-lg-12">
                <h4 class="heading">{{ __('Add New Theme') }}
                    <a class="add-btn" href="{{ route('operator-homethemes-index') }}">
                        <i class="fas fa-arrow-left"></i> {{ __('Back') }}
                    </a>
                </h4>
                <ul class="links">
                    <li>
                        <a href="{{ route('operator.dashboard') }}">{{ __('Dashboard') }} </a>
                    </li>
                    <li>
                        <a href="javascript:;">{{ __('Home Page Settings') }}</a>
                    </li>
                    <li>
                        <a href="{{ route('operator-homethemes-index') }}">{{ __('Home Page Themes') }}</a>
                    </li>
                    <li>
                        <a href="{{ route('operator-homethemes-create') }}">{{ __('Add New Theme') }}</a>
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

                        <form id="muaadhform" action="{{ route('operator-homethemes-store') }}" method="POST">
                            @csrf
                            @include('alerts.operator.form-both')

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
                                                <input type="text" class="form-control" name="name" required placeholder="{{ __('e.g. Modern Theme') }}">
                                            </div>
                                        </div>
                                        <div class="col-lg-6">
                                            <div class="form-group mb-3">
                                                <label class="control-label">{{ __('Slug') }}</label>
                                                <input type="text" class="form-control" name="slug" placeholder="{{ __('e.g. modern-theme (auto-generated if empty)') }}">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-lg-6">
                                            <div class="form-group mb-3">
                                                <label class="control-label">{{ __('Layout') }}</label>
                                                <select class="form-control" name="layout">
                                                    <option value="default">{{ __('Default') }}</option>
                                                    <option value="minimal">{{ __('Minimal') }}</option>
                                                    <option value="full-width">{{ __('Full Width') }}</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-lg-6">
                                            <div class="form-group mb-3">
                                                <label class="control-label">{{ __('Set as Active') }}</label>
                                                <label class="switch">
                                                    <input type="checkbox" name="is_active" value="1">
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
                                                <label class="control-label">{{ __('Hero Carousel') }}</label>
                                                <label class="switch">
                                                    <input type="checkbox" name="show_hero_carousel" value="1" checked>
                                                    <span class="slider round"></span>
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-lg-4 col-md-6 mb-3">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <label class="control-label">{{ __('Hero Search') }}</label>
                                                <label class="switch">
                                                    <input type="checkbox" name="show_hero_search" value="1" checked>
                                                    <span class="slider round"></span>
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-lg-4 col-md-6 mb-3">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <label class="control-label">{{ __('Brands') }}</label>
                                                <label class="switch">
                                                    <input type="checkbox" name="show_brands" value="1" checked>
                                                    <span class="slider round"></span>
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-lg-4 col-md-6 mb-3">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <label class="control-label">{{ __('Categories') }}</label>
                                                <label class="switch">
                                                    <input type="checkbox" name="show_categories" value="1" checked>
                                                    <span class="slider round"></span>
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-lg-4 col-md-6 mb-3">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <label class="control-label">{{ __('Arrival Section') }}</label>
                                                <label class="switch">
                                                    <input type="checkbox" name="show_arrival" value="1" checked>
                                                    <span class="slider round"></span>
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-lg-4 col-md-6 mb-3">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <label class="control-label">{{ __('Featured CatalogItems') }}</label>
                                                <label class="switch">
                                                    <input type="checkbox" name="show_featured_items" value="1" checked>
                                                    <span class="slider round"></span>
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-lg-4 col-md-6 mb-3">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <label class="control-label">{{ __('Deal of the Day') }}</label>
                                                <label class="switch">
                                                    <input type="checkbox" name="show_deal_of_day" value="1" checked>
                                                    <span class="slider round"></span>
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-lg-4 col-md-6 mb-3">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <label class="control-label">{{ __('Top Rated') }}</label>
                                                <label class="switch">
                                                    <input type="checkbox" name="show_top_rated" value="1" checked>
                                                    <span class="slider round"></span>
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-lg-4 col-md-6 mb-3">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <label class="control-label">{{ __('Big Save') }}</label>
                                                <label class="switch">
                                                    <input type="checkbox" name="show_big_save" value="1" checked>
                                                    <span class="slider round"></span>
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-lg-4 col-md-6 mb-3">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <label class="control-label">{{ __('Trending') }}</label>
                                                <label class="switch">
                                                    <input type="checkbox" name="show_trending" value="1" checked>
                                                    <span class="slider round"></span>
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-lg-4 col-md-6 mb-3">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <label class="control-label">{{ __('Best Sellers') }}</label>
                                                <label class="switch">
                                                    <input type="checkbox" name="show_best_sellers" value="1" checked>
                                                    <span class="slider round"></span>
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-lg-4 col-md-6 mb-3">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <label class="control-label">{{ __('Blogs') }}</label>
                                                <label class="switch">
                                                    <input type="checkbox" name="show_blogs" value="1" checked>
                                                    <span class="slider round"></span>
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-lg-4 col-md-6 mb-3">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <label class="control-label">{{ __('Capabilities') }}</label>
                                                <label class="switch">
                                                    <input type="checkbox" name="show_capabilities" value="1" checked>
                                                    <span class="slider round"></span>
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-lg-4 col-md-6 mb-3">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <label class="control-label">{{ __('Newsletter') }}</label>
                                                <label class="switch">
                                                    <input type="checkbox" name="show_newsletter" value="1" checked>
                                                    <span class="slider round"></span>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Section Purchase --}}
                            <div class="panel panel-default mb-4">
                                <div class="panel-heading">
                                    <h3 class="panel-title">{{ __('Sections Purchase') }}</h3>
                                </div>
                                <div class="panel-body">
                                    <div class="row">
                                        <div class="col-lg-3 col-md-4 mb-3">
                                            <label class="control-label">{{ __('Slider Purchase') }}</label>
                                            <input type="number" class="form-control" name="order_hero_carousel" value="1" min="1">
                                        </div>
                                        <div class="col-lg-3 col-md-4 mb-3">
                                            <label class="control-label">{{ __('Brands Purchase') }}</label>
                                            <input type="number" class="form-control" name="order_brands" value="2" min="1">
                                        </div>
                                        <div class="col-lg-3 col-md-4 mb-3">
                                            <label class="control-label">{{ __('Categories Purchase') }}</label>
                                            <input type="number" class="form-control" name="order_categories" value="3" min="1">
                                        </div>
                                        <div class="col-lg-3 col-md-4 mb-3">
                                            <label class="control-label">{{ __('Arrival Purchase') }}</label>
                                            <input type="number" class="form-control" name="order_arrival" value="4" min="1">
                                        </div>
                                        <div class="col-lg-3 col-md-4 mb-3">
                                            <label class="control-label">{{ __('Featured CatalogItems Purchase') }}</label>
                                            <input type="number" class="form-control" name="order_featured_items" value="5" min="1">
                                        </div>
                                        <div class="col-lg-3 col-md-4 mb-3">
                                            <label class="control-label">{{ __('Deal of Day Purchase') }}</label>
                                            <input type="number" class="form-control" name="order_deal_of_day" value="6" min="1">
                                        </div>
                                        <div class="col-lg-3 col-md-4 mb-3">
                                            <label class="control-label">{{ __('Top Rated Purchase') }}</label>
                                            <input type="number" class="form-control" name="order_top_rated" value="7" min="1">
                                        </div>
                                        <div class="col-lg-3 col-md-4 mb-3">
                                            <label class="control-label">{{ __('Big Save Purchase') }}</label>
                                            <input type="number" class="form-control" name="order_big_save" value="8" min="1">
                                        </div>
                                        <div class="col-lg-3 col-md-4 mb-3">
                                            <label class="control-label">{{ __('Trending Purchase') }}</label>
                                            <input type="number" class="form-control" name="order_trending" value="9" min="1">
                                        </div>
                                        <div class="col-lg-3 col-md-4 mb-3">
                                            <label class="control-label">{{ __('Best Sellers Purchase') }}</label>
                                            <input type="number" class="form-control" name="order_best_sellers" value="10" min="1">
                                        </div>
                                        <div class="col-lg-3 col-md-4 mb-3">
                                            <label class="control-label">{{ __('Blogs Purchase') }}</label>
                                            <input type="number" class="form-control" name="order_blogs" value="11" min="1">
                                        </div>
                                        <div class="col-lg-3 col-md-4 mb-3">
                                            <label class="control-label">{{ __('Services Purchase') }}</label>
                                            <input type="number" class="form-control" name="order_capabilities" value="12" min="1">
                                        </div>
                                        <div class="col-lg-3 col-md-4 mb-3">
                                            <label class="control-label">{{ __('Newsletter Purchase') }}</label>
                                            <input type="number" class="form-control" name="order_newsletter" value="13" min="1">
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
                                            <input type="text" class="form-control" name="title_brands" placeholder="{{ __('Genuine Parts Catalogues') }}">
                                        </div>
                                        <div class="col-lg-4 col-md-6 mb-3">
                                            <label class="control-label">{{ __('Categories Title') }}</label>
                                            <input type="text" class="form-control" name="title_categories" placeholder="{{ __('Shop by Category') }}">
                                        </div>
                                        <div class="col-lg-4 col-md-6 mb-3">
                                            <label class="control-label">{{ __('Featured CatalogItems Title') }}</label>
                                            <input type="text" class="form-control" name="title_featured_items" placeholder="{{ __('Featured CatalogItems') }}">
                                        </div>
                                        <div class="col-lg-4 col-md-6 mb-3">
                                            <label class="control-label">{{ __('Deal of Day Title') }}</label>
                                            <input type="text" class="form-control" name="title_deal_of_day" placeholder="{{ __('Deal of the Day') }}">
                                        </div>
                                        <div class="col-lg-4 col-md-6 mb-3">
                                            <label class="control-label">{{ __('Top Rated Title') }}</label>
                                            <input type="text" class="form-control" name="title_top_rated" placeholder="{{ __('Top Rated CatalogItems') }}">
                                        </div>
                                        <div class="col-lg-4 col-md-6 mb-3">
                                            <label class="control-label">{{ __('Big Save Title') }}</label>
                                            <input type="text" class="form-control" name="title_big_save" placeholder="{{ __('Big Save CatalogItems') }}">
                                        </div>
                                        <div class="col-lg-4 col-md-6 mb-3">
                                            <label class="control-label">{{ __('Trending Title') }}</label>
                                            <input type="text" class="form-control" name="title_trending" placeholder="{{ __('Trending CatalogItems') }}">
                                        </div>
                                        <div class="col-lg-4 col-md-6 mb-3">
                                            <label class="control-label">{{ __('Best Sellers Title') }}</label>
                                            <input type="text" class="form-control" name="title_best_sellers" placeholder="{{ __('Best Selling CatalogItems') }}">
                                        </div>
                                        <div class="col-lg-4 col-md-6 mb-3">
                                            <label class="control-label">{{ __('Blogs Title') }}</label>
                                            <input type="text" class="form-control" name="title_blogs" placeholder="{{ __('From Our Blog') }}">
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
                                            <input type="number" class="form-control" name="count_featured_items" value="8" min="1" max="24">
                                        </div>
                                        <div class="col-lg-3 col-md-4 mb-3">
                                            <label class="control-label">{{ __('Top Rated') }}</label>
                                            <input type="number" class="form-control" name="count_top_rated" value="6" min="1" max="24">
                                        </div>
                                        <div class="col-lg-3 col-md-4 mb-3">
                                            <label class="control-label">{{ __('Big Save') }}</label>
                                            <input type="number" class="form-control" name="count_big_save" value="6" min="1" max="24">
                                        </div>
                                        <div class="col-lg-3 col-md-4 mb-3">
                                            <label class="control-label">{{ __('Trending') }}</label>
                                            <input type="number" class="form-control" name="count_trending" value="6" min="1" max="24">
                                        </div>
                                        <div class="col-lg-3 col-md-4 mb-3">
                                            <label class="control-label">{{ __('Best Sellers') }}</label>
                                            <input type="number" class="form-control" name="count_best_sellers" value="8" min="1" max="24">
                                        </div>
                                        <div class="col-lg-3 col-md-4 mb-3">
                                            <label class="control-label">{{ __('Blogs') }}</label>
                                            <input type="number" class="form-control" name="count_blogs" value="3" min="1" max="12">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-lg-12 text-center">
                                    <button class="btn btn-primary btn-lg" type="submit">
                                        <i class="fas fa-save me-2"></i>{{ __('Create Theme') }}
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
