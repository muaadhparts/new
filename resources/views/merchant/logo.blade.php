@extends('layouts.merchant')
@section('content')
    <div class="gs-merchant-outlet">
        <!-- breadcrumb start  -->
        <div class="gs-merchant-breadcrumb has-mb">
            <h4 class="text-capitalize">@lang('Merchant Logo')</h4>
            <ul class="breadcrumb-menu">
                <li>
                    <a href="{{ route('merchant.dashboard') }}" class="text-capitalize">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" class="home-icon-merchant-panel-breadcrumb">
                            <path
                                d="M9 21V13.6C9 13.0399 9 12.7599 9.109 12.546C9.20487 12.3578 9.35785 12.2049 9.54601 12.109C9.75993 12 10.04 12 10.6 12H13.4C13.9601 12 14.2401 12 14.454 12.109C14.6422 12.2049 14.7951 12.3578 14.891 12.546C15 12.7599 15 13.0399 15 13.6V21M2 9.5L11.04 2.72C11.3843 2.46181 11.5564 2.33271 11.7454 2.28294C11.9123 2.23902 12.0877 2.23902 12.2546 2.28295C12.4436 2.33271 12.6157 2.46181 12.96 2.72L22 9.5M4 8V17.8C4 18.9201 4 19.4802 4.21799 19.908C4.40974 20.2843 4.7157 20.5903 5.09202 20.782C5.51985 21 6.0799 21 7.2 21H16.8C17.9201 21 18.4802 21 18.908 20.782C19.2843 20.5903 19.5903 20.2843 19.782 19.908C20 19.4802 20 18.9201 20 17.8V8L13.92 3.44C13.2315 2.92361 12.8872 2.66542 12.5091 2.56589C12.1754 2.47804 11.8246 2.47804 11.4909 2.56589C11.1128 2.66542 10.7685 2.92361 10.08 3.44L4 8Z"
                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </a>
                </li>
                <li>
                    <a href="{{ route('merchant.dashboard') }}" class="text-capitalize">
                        @lang('Dashboard')
                    </a>
                </li>
                <li>
                    <a href="#" class="text-capitalize"> @lang('Merchant Logo') </a>
                </li>
            </ul>
        </div>
        <!-- breadcrumb end -->

        <!-- Logo Upload area start  -->
        <div class="merchant-edit-profile-section-wrapper">
            <div class="gs-edit-profile-section">
                <div class="row">
                    <div class="col-lg-8">
                        <div class="m-card">
                            <div class="m-card__header">
                                <h5>@lang('Merchant Logo for Invoices')</h5>
                            </div>
                            <div class="m-card__body">
                                <p class="text-muted mb-4">
                                    @lang('Upload your merchant logo. This logo will appear on invoices when you are the payment receiver.')
                                </p>

                                <!-- Current Logo Preview -->
                                @if($logoUrl)
                                <div class="mb-4">
                                    <label class="form-label">@lang('Current Logo')</label>
                                    <div class="logo-preview-wrapper" style="background: var(--bg-secondary); padding: 20px; border-radius: 8px; text-align: center;">
                                        <img src="{{ $logoUrl }}" alt="@lang('Merchant Logo')"
                                            style="max-width: 200px; max-height: 200px; object-fit: contain;">
                                    </div>
                                    <form action="{{ route('merchant-logo-delete') }}" method="POST" class="mt-2" onsubmit="return confirm('@lang('Are you sure you want to delete this logo?')')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="m-btn m-btn--danger m-btn--sm">
                                            <i class="fas fa-trash"></i> @lang('Delete Logo')
                                        </button>
                                    </form>
                                </div>
                                @else
                                <div class="mb-4">
                                    <div class="logo-preview-wrapper" style="background: var(--bg-secondary); padding: 40px; border-radius: 8px; text-align: center;">
                                        <i class="fas fa-image" style="font-size: 48px; color: var(--text-muted);"></i>
                                        <p class="text-muted mt-2">@lang('No logo uploaded yet')</p>
                                    </div>
                                </div>
                                @endif

                                <!-- Upload Form -->
                                <form action="{{ route('merchant-logo-update') }}" method="POST" enctype="multipart/form-data">
                                    @csrf
                                    <div class="form-group">
                                        <label for="merchant_logo" class="form-label">@lang('Upload New Logo')</label>
                                        <input type="file" id="merchant_logo" name="merchant_logo" class="form-control"
                                            accept="image/jpeg,image/jpg,image/png,image/svg+xml" required>
                                        @error('merchant_logo')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                        <small class="text-muted">@lang('Allowed formats: JPEG, JPG, PNG, SVG. Max size: 2MB.')</small>
                                    </div>

                                    <div class="form-group mt-3">
                                        <button type="submit" class="m-btn m-btn--primary">
                                            <i class="fas fa-upload"></i>
                                            @if($logoUrl)
                                                @lang('Update Logo')
                                            @else
                                                @lang('Upload Logo')
                                            @endif
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="m-card">
                            <div class="m-card__header">
                                <h5>@lang('Logo Guidelines')</h5>
                            </div>
                            <div class="m-card__body">
                                <ul class="list-unstyled">
                                    <li class="mb-2">
                                        <i class="fas fa-check-circle text-success me-2"></i>
                                        @lang('Use a square or landscape logo')
                                    </li>
                                    <li class="mb-2">
                                        <i class="fas fa-check-circle text-success me-2"></i>
                                        @lang('Recommended size: 400x400 pixels')
                                    </li>
                                    <li class="mb-2">
                                        <i class="fas fa-check-circle text-success me-2"></i>
                                        @lang('Use transparent background (PNG)')
                                    </li>
                                    <li class="mb-2">
                                        <i class="fas fa-info-circle text-info me-2"></i>
                                        @lang('This logo appears on invoices when you receive payments directly')
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Logo Upload area end  -->
    </div>
@endsection
