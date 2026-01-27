@extends('layouts.front')
@section('content')
    <div class="gs-user-panel-review wow fadeInUp" data-wow-delay=".1s">
        <div class="container">
            <div class="d-flex">
                <!-- sidebar -->
                @include('includes.user.sidebar')
                <!-- main content -->
                <div class="gs-dashboard-user-content-wrapper gs-dashboard-outlet">
                    <div class="gs-edit-profile-section">
                        <h3>@lang('Apply as Merchant')</h3>
                        <p class="text-muted mb-4">@lang('Fill out the form below to apply for a merchant account. Your application will be reviewed by our team.')</p>

                        <form action="{{ route('user.apply-merchant-submit') }}" method="POST">
                            @csrf
                            <div class="edit-profile-area">
                                <div class="row">
                                    <div class="col-lg-8 col-12">
                                        <div class="form-group mb-3">
                                            <label for="shop_name">@lang('Shop Name') <span class="text-danger">*</span></label>
                                            <input type="text" id="shop_name" name="shop_name" class="form-control @error('shop_name') is-invalid @enderror"
                                                placeholder="@lang('Enter your shop name')" value="{{ old('shop_name') }}" required>
                                            @error('shop_name')
                                                <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </div>

                                        <div class="form-group mb-3">
                                            <label for="shop_number">@lang('Shop Number / Registration Number')</label>
                                            <input type="text" id="shop_number" name="shop_number" class="form-control @error('shop_number') is-invalid @enderror"
                                                placeholder="@lang('Enter shop registration number (optional)')" value="{{ old('shop_number') }}" maxlength="10">
                                            @error('shop_number')
                                                <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </div>

                                        <div class="form-group mb-3">
                                            <label for="shop_address">@lang('Shop Address') <span class="text-danger">*</span></label>
                                            <textarea id="shop_address" name="shop_address" class="form-control @error('shop_address') is-invalid @enderror"
                                                placeholder="@lang('Enter your shop address')" style="height: 100px" required>{{ old('shop_address') }}</textarea>
                                            <button type="button" class="btn btn-outline-primary btn-sm mt-2"
                                                onclick="openMapPicker({ addressField: '#shop_address' })">
                                                <i class="fas fa-map-marker-alt me-1"></i> @lang('Select on Map')
                                            </button>
                                            @error('shop_address')
                                                <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </div>

                                        <div class="form-group mb-4">
                                            <label for="shop_message">@lang('About Your Business')</label>
                                            <textarea id="shop_message" name="shop_message" class="form-control"
                                                placeholder="@lang('Tell us about your business, products you sell, etc. (optional)')" style="height: 120px">{{ old('shop_message') }}</textarea>
                                        </div>

                                        <div class="m-alert m-alert--info mb-4">
                                            <div class="d-flex align-items-start">
                                                <i class="fas fa-info-circle me-2 mt-1"></i>
                                                <div>
                                                    <strong>@lang('What happens next?')</strong>
                                                    <ul class="mb-0 mt-2 ps-3">
                                                        <li>@lang('Your application will be reviewed by our team')</li>
                                                        <li>@lang('You will be able to access your merchant dashboard')</li>
                                                        <li>@lang('Full selling capabilities will be enabled after verification')</li>
                                                    </ul>
                                                </div>
                                            </div>
                                        </div>

                                        <button class="m-btn m-btn--primary" type="submit">
                                            <i class="fas fa-paper-plane me-2"></i>@lang('Submit Application')
                                        </button>
                                    </div>

                                    <div class="col-lg-4 col-12 mt-4 mt-lg-0">
                                        <div class="m-card">
                                            <div class="m-card__header">
                                                <h5 class="mb-0">@lang('Your Information')</h5>
                                            </div>
                                            <div class="m-card__body">
                                                {{-- User info pre-computed via DTO (DATA_FLOW_POLICY) --}}
                                                <p class="mb-2"><strong>@lang('Name'):</strong> {{ $profile->name }}</p>
                                                <p class="mb-2"><strong>@lang('Email'):</strong> {{ $profile->email }}</p>
                                                <p class="mb-2"><strong>@lang('Phone'):</strong> {{ $profile->phone ?: '-' }}</p>
                                                <p class="mb-0"><strong>@lang('Address'):</strong> {{ $profile->address ?: '-' }}</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
