@extends('layouts.admin')

@section('content')
    <div class="content-area">
        <div class="mr-breadcrumb">
            <div class="row">
                <div class="col-lg-12">
                    <h4 class="heading">{{ __('Home Page') }}</h4>
                    <ul class="links">
                        <li>
                            <a href="{{ route('admin.dashboard') }}">{{ __('Dashboard') }} </a>
                        </li>
                        <li>
                            <a href="javascript:;">{{ __('Home Page Setting') }}</a>
                        </li>
                        <li>
                            <a href="{{ route('admin-home-page-index') }}">{{ __('Home Page') }}</a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="add-product-content1 add-product-content2">
            <div class="row">
                <div class="col-lg-12">
                    <div class="product-description">
                        <div class="body-area p-4">
                            @include('alerts.form-success')

                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                {{ __('The home page uses a unified modern design. Use Theme Builder to customize colors and styling.') }}
                            </div>

                            <div class="row justify-content-center mt-4">
                                <div class="col-lg-6">
                                    <a href="{{ route('admin-gs-theme-colors') }}" class="btn btn-primary btn-lg w-100">
                                        <i class="fas fa-palette me-2"></i>
                                        {{ __('Open Theme Builder') }}
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
