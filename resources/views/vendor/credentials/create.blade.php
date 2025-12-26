@extends('layouts.vendor')

@section('content')
    <div class="gs-vendor-outlet">
        <!-- breadcrumb start -->
        <div class="gs-vendor-breadcrumb has-mb">
            <div class="d-flex gap-4 custom-gap-sm-2 flex-wrap align-items-center">
                <h4 class="text-capitalize">@lang('Add New Credential')</h4>
            </div>
            <ul class="breadcrumb-menu">
                <li>
                    <a href="{{ route('vendor.dashboard') }}" class="text-capitalize">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                            fill="none" stroke="currentColor" class="home-icon-vendor-panel-breadcrumb">
                            <path
                                d="M9 21V13.6C9 13.0399 9 12.7599 9.109 12.546C9.20487 12.3578 9.35785 12.2049 9.54601 12.109C9.75993 12 10.04 12 10.6 12H13.4C13.9601 12 14.2401 12 14.454 12.109C14.6422 12.2049 14.7951 12.3578 14.891 12.546C15 12.7599 15 13.0399 15 13.6V21M2 9.5L11.04 2.72C11.3843 2.46181 11.5564 2.33271 11.7454 2.28294C11.9123 2.23902 12.0877 2.23902 12.2546 2.28295C12.4436 2.33271 12.6157 2.46181 12.96 2.72L22 9.5M4 8V17.8C4 18.9201 4 19.4802 4.21799 19.908C4.40974 20.2843 4.7157 20.5903 5.09202 20.782C5.51985 21 6.0799 21 7.2 21H16.8C17.9201 21 18.4802 21 18.908 20.782C19.2843 20.5903 19.5903 20.2843 19.782 19.908C20 19.4802 20 18.9201 20 17.8V8L13.92 3.44C13.2315 2.92361 12.8872 2.66542 12.5091 2.56589C12.1754 2.47804 11.8246 2.47804 11.4909 2.56589C11.1128 2.66542 10.7685 2.92361 10.08 3.44L4 8Z"
                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </a>
                </li>
                <li>
                    <a href="{{ route('vendor.dashboard') }}" class="text-capitalize">@lang('Dashboard')</a>
                </li>
                <li>
                    <a href="{{ route('vendor-credentials-index') }}" class="text-capitalize">@lang('API Credentials')</a>
                </li>
                <li>
                    <a href="#" class="text-capitalize">@lang('Add New')</a>
                </li>
            </ul>
        </div>
        <!-- breadcrumb end -->

        @if($errors->any())
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <ul class="mb-0">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="gs-vendor-erning">
            <div class="vendor-table-wrapper product-catalogs-table-wrapper">
                <form action="{{ route('vendor-credentials-store') }}" method="POST">
                    @csrf
                    <div class="row">
                        <!-- Service Type -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label">@lang('Service Type') <span class="text-danger">*</span></label>
                            <select name="service_type" id="service_type" class="form-select" required>
                                <option value="">@lang('Select Type')</option>
                                <option value="payment" {{ old('service_type') == 'payment' ? 'selected' : '' }}>@lang('Payment Gateway')</option>
                                <option value="shipping" {{ old('service_type') == 'shipping' ? 'selected' : '' }}>@lang('Shipping Provider')</option>
                            </select>
                        </div>

                        <!-- Service Name -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label">@lang('Service') <span class="text-danger">*</span></label>
                            <select name="service_name" id="service_name" class="form-select" required>
                                <option value="">@lang('Select Service')</option>
                            </select>
                        </div>

                        <!-- Key Name -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label">@lang('Key Name') <span class="text-danger">*</span></label>
                            <select name="key_name" id="key_name" class="form-select" required>
                                <option value="api_key">API Key</option>
                                <option value="secret_key">Secret Key</option>
                                <option value="refresh_token">Refresh Token</option>
                                <option value="access_token">Access Token</option>
                                <option value="merchant_id">Merchant ID</option>
                                <option value="account_number">Account Number</option>
                            </select>
                        </div>

                        <!-- Environment -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label">@lang('Environment') <span class="text-danger">*</span></label>
                            <select name="environment" id="environment" class="form-select" required>
                                <option value="live" {{ old('environment') == 'live' ? 'selected' : '' }}>@lang('Live (Production)')</option>
                                <option value="sandbox" {{ old('environment') == 'sandbox' ? 'selected' : '' }}>@lang('Sandbox (Testing)')</option>
                            </select>
                            <small class="text-muted">@lang('Select Live for production or Sandbox for testing.')</small>
                        </div>

                        <!-- Value -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label">@lang('Value') <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="password" name="value" id="credential_value" class="form-control"
                                    placeholder="@lang('Enter credential value')" required>
                                <button class="btn btn-outline-secondary toggle-password" type="button">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <small class="text-muted">@lang('This value will be encrypted before storage.')</small>
                        </div>

                        <!-- Description -->
                        <div class="col-12 mb-3">
                            <label class="form-label">@lang('Description')</label>
                            <textarea name="description" class="form-control" rows="2"
                                placeholder="@lang('Optional description for this credential')">{{ old('description') }}</textarea>
                        </div>

                        <!-- Submit -->
                        <div class="col-12">
                            <button type="submit" class="template-btn">
                                <i class="fas fa-save me-2"></i>@lang('Save Credential')
                            </button>
                            <a href="{{ route('vendor-credentials-index') }}" class="template-btn black-btn ms-2">
                                <i class="fas fa-times me-2"></i>@lang('Cancel')
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('script')
<script type="text/javascript">
    "use strict";

    var availableServices = @json($availableServices);

    $('#service_type').on('change', function() {
        var type = $(this).val();
        var $serviceSelect = $('#service_name');
        $serviceSelect.html('<option value="">@lang("Select Service")</option>');

        if (type && availableServices[type]) {
            $.each(availableServices[type], function(key, value) {
                $serviceSelect.append('<option value="' + key + '">' + value + '</option>');
            });
        }
    });

    // Toggle password visibility
    $('.toggle-password').on('click', function() {
        var input = $('#credential_value');
        var icon = $(this).find('i');

        if (input.attr('type') === 'password') {
            input.attr('type', 'text');
            icon.removeClass('fa-eye').addClass('fa-eye-slash');
        } else {
            input.attr('type', 'password');
            icon.removeClass('fa-eye-slash').addClass('fa-eye');
        }
    });

    // Trigger change if old value exists
    @if(old('service_type'))
        $('#service_type').trigger('change');
        setTimeout(function() {
            $('#service_name').val('{{ old('service_name') }}');
        }, 100);
    @endif
</script>
@endsection
