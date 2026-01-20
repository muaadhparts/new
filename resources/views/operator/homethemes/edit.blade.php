@extends('layouts.operator')

@section('content')
<div class="content-area">
    <div class="mr-breadcrumb">
        <div class="row">
            <div class="col-lg-12">
                <h4 class="heading">{{ __('Edit Theme') }}: {{ $theme->name }}
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

                        <form id="muaadhform" action="{{ route('operator-homethemes-update', $theme->id) }}" method="POST">
                            @csrf
                            @method('PUT')
                            @include('alerts.operator.form-both')

                            {{-- Basic Info --}}
                            <div class="panel panel-default mb-4">
                                <div class="panel-heading">
                                    <h3 class="panel-name">{{ __('Basic Information') }}</h3>
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
                                                    <span class="toggle-switch round"></span>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Sections Toggle --}}
                            <div class="panel panel-default mb-4">
                                <div class="panel-heading">
                                    <h3 class="panel-name">{{ __('Sections Visibility') }}</h3>
                                </div>
                                <div class="panel-body">
                                    <div class="row">
                                        <div class="col-lg-4 col-md-6 mb-3">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <label class="control-label">{{ __('Hero Search') }}</label>
                                                <label class="switch">
                                                    <input type="checkbox" name="show_hero_search" value="1" {{ $theme->show_hero_search ? 'checked' : '' }}>
                                                    <span class="toggle-switch round"></span>
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-lg-4 col-md-6 mb-3">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <label class="control-label">{{ __('Brands') }}</label>
                                                <label class="switch">
                                                    <input type="checkbox" name="show_brands" value="1" {{ $theme->show_brands ? 'checked' : '' }}>
                                                    <span class="toggle-switch round"></span>
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-lg-4 col-md-6 mb-3">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <label class="control-label">{{ __('Categories') }}</label>
                                                <label class="switch">
                                                    <input type="checkbox" name="show_categories" value="1" {{ $theme->show_categories ? 'checked' : '' }}>
                                                    <span class="toggle-switch round"></span>
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-lg-4 col-md-6 mb-3">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <label class="control-label">{{ __('Arrival Section') }}</label>
                                                <label class="switch">
                                                    <input type="checkbox" name="show_arrival" value="1" {{ $theme->show_arrival ? 'checked' : '' }}>
                                                    <span class="toggle-switch round"></span>
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-lg-4 col-md-6 mb-3">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <label class="control-label">{{ __('Blogs') }}</label>
                                                <label class="switch">
                                                    <input type="checkbox" name="show_blogs" value="1" {{ $theme->show_blogs ? 'checked' : '' }}>
                                                    <span class="toggle-switch round"></span>
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-lg-4 col-md-6 mb-3">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <label class="control-label">{{ __('Newsletter') }}</label>
                                                <label class="switch">
                                                    <input type="checkbox" name="show_newsletter" value="1" {{ $theme->show_newsletter ? 'checked' : '' }}>
                                                    <span class="toggle-switch round"></span>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Section Order --}}
                            <div class="panel panel-default mb-4">
                                <div class="panel-heading">
                                    <h3 class="panel-name">{{ __('Sections Order') }}</h3>
                                </div>
                                <div class="panel-body">
                                    <div class="row">
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
                                            <label class="control-label">{{ __('Blogs Order') }}</label>
                                            <input type="number" class="form-control" name="order_blogs" value="{{ $theme->order_blogs }}" min="1">
                                        </div>
                                        <div class="col-lg-3 col-md-4 mb-3">
                                            <label class="control-label">{{ __('Newsletter Order') }}</label>
                                            <input type="number" class="form-control" name="order_newsletter" value="{{ $theme->order_newsletter }}" min="1">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Section Names --}}
                            <div class="panel panel-default mb-4">
                                <div class="panel-heading">
                                    <h3 class="panel-name">{{ __('Section Names (Optional - leave empty for defaults)') }}</h3>
                                </div>
                                <div class="panel-body">
                                    <div class="row">
                                        <div class="col-lg-4 col-md-6 mb-3">
                                            <label class="control-label">{{ __('Brands Name') }}</label>
                                            <input type="text" class="form-control" name="name_brands" value="{{ $theme->name_brands }}" placeholder="{{ __('Genuine Parts Catalogues') }}">
                                        </div>
                                        <div class="col-lg-4 col-md-6 mb-3">
                                            <label class="control-label">{{ __('Categories Name') }}</label>
                                            <input type="text" class="form-control" name="name_categories" value="{{ $theme->name_categories }}" placeholder="{{ __('Shop by Category') }}">
                                        </div>
                                        <div class="col-lg-4 col-md-6 mb-3">
                                            <label class="control-label">{{ __('Blogs Name') }}</label>
                                            <input type="text" class="form-control" name="name_blogs" value="{{ $theme->name_blogs }}" placeholder="{{ __('From Our Blog') }}">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Content Counts --}}
                            <div class="panel panel-default mb-4">
                                <div class="panel-heading">
                                    <h3 class="panel-name">{{ __('Content Counts Per Section') }}</h3>
                                </div>
                                <div class="panel-body">
                                    <div class="row">
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
