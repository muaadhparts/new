@extends('layouts.operator')

@section('content')
    <div class="content-area">
        <div class="mr-breadcrumb">
            <div class="row">
                <div class="col-lg-12">
                    <h4 class="heading">{{ __('Home Page') }}</h4>
                    <ul class="links">
                        <li>
                            <a href="{{ route('operator.dashboard') }}">{{ __('Dashboard') }} </a>
                        </li>
                        <li>
                            <a href="javascript:;">{{ __('Home Page Setting') }}</a>
                        </li>
                        <li>
                            <a href="{{ route('operator-home-page-index') }}">{{ __('Home Page') }}</a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="add-catalogItem-content1 add-catalogItem-content2">
            <div class="row">
                <div class="col-lg-12">
                    <div class="catalogItem-description">
                        <div class="body-area p-4">
                            @include('alerts.form-success')

                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                {{ __('Manage your home page themes and sections. Create multiple themes and switch between them.') }}
                            </div>

                            <div class="row justify-content-center mt-4">
                                <div class="col-lg-5 mb-3">
                                    <a href="{{ route('operator-homethemes-index') }}" class="btn btn-primary btn-lg w-100">
                                        <i class="fas fa-layer-group me-2"></i>
                                        {{ __('Manage Home Themes') }}
                                    </a>
                                </div>
                                <div class="col-lg-5 mb-3">
                                    <a href="{{ route('operator-theme-colors') }}" class="btn btn-secondary btn-lg w-100">
                                        <i class="fas fa-palette me-2"></i>
                                        {{ __('Theme Colors') }}
                                    </a>
                                </div>
                            </div>

                            <div class="row justify-content-center mt-3">
                                <div class="col-lg-5 mb-3">
                                    <a href="{{ route('operator-fs-customize') }}" class="btn btn-outline-primary w-100">
                                        <i class="fas fa-cog me-2"></i>
                                        {{ __('Legacy Customization') }}
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
