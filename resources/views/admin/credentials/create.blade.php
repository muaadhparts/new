@extends('layouts.admin')

@section('content')
<div class="content-area">
    <div class="mr-breadcrumb">
        <div class="row">
            <div class="col-lg-12">
                <h4 class="heading">{{ __('Add New Credential') }} <i class="fas fa-plus-circle"></i></h4>
                <ul class="links">
                    <li>
                        <a href="{{ route('admin.dashboard') }}">{{ __('Dashboard') }}</a>
                    </li>
                    <li>
                        <a href="{{ route('admin.credentials.index') }}">{{ __('API Credentials') }}</a>
                    </li>
                    <li>
                        <a href="javascript:;">{{ __('Add New') }}</a>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <div class="add-product-content1">
        <div class="row">
            <div class="col-lg-8 offset-lg-2">
                <div class="product-description">
                    <div class="body-area">
                        <div class="gocover" style="background: url({{asset('assets/images/'.$gs->admin_loader)}}) no-repeat scroll center center rgba(45, 45, 45, 0.5);"></div>

                        @include('alerts.admin.form-both')

                        <form action="{{ route('admin.credentials.store') }}" method="POST" id="credentialForm">
                            @csrf

                            <div class="row justify-content-center">
                                <div class="col-lg-4">
                                    <div class="left-area">
                                        <h4 class="heading">{{ __('Service') }} *</h4>
                                        <p class="sub-heading">{{ __('Select the service provider') }}</p>
                                    </div>
                                </div>
                                <div class="col-lg-7">
                                    <select name="service_name" id="service_name" class="form-control" required>
                                        <option value="">{{ __('Select Service') }}</option>
                                        @foreach($services as $key => $name)
                                            <option value="{{ $key }}">{{ $name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="row justify-content-center mt-3" id="customServiceRow" style="display: none;">
                                <div class="col-lg-4">
                                    <div class="left-area">
                                        <h4 class="heading">{{ __('Custom Service Name') }} *</h4>
                                    </div>
                                </div>
                                <div class="col-lg-7">
                                    <input type="text" name="custom_service_name" class="form-control"
                                           placeholder="{{ __('e.g., my_custom_service') }}">
                                </div>
                            </div>

                            <div class="row justify-content-center mt-3">
                                <div class="col-lg-4">
                                    <div class="left-area">
                                        <h4 class="heading">{{ __('Key Type') }} *</h4>
                                        <p class="sub-heading">{{ __('Type of credential') }}</p>
                                    </div>
                                </div>
                                <div class="col-lg-7">
                                    <select name="key_name" id="key_name" class="form-control" required>
                                        <option value="">{{ __('Select Key Type') }}</option>
                                        @foreach($keyTypes as $key => $name)
                                            <option value="{{ $key }}">{{ $name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="row justify-content-center mt-3" id="customKeyRow" style="display: none;">
                                <div class="col-lg-4">
                                    <div class="left-area">
                                        <h4 class="heading">{{ __('Custom Key Name') }} *</h4>
                                    </div>
                                </div>
                                <div class="col-lg-7">
                                    <input type="text" name="custom_key_name" class="form-control"
                                           placeholder="{{ __('e.g., my_secret_key') }}">
                                </div>
                            </div>

                            <div class="row justify-content-center mt-3">
                                <div class="col-lg-4">
                                    <div class="left-area">
                                        <h4 class="heading">{{ __('Credential Value') }} *</h4>
                                        <p class="sub-heading">{{ __('The actual API key or token') }}</p>
                                    </div>
                                </div>
                                <div class="col-lg-7">
                                    <div class="input-group">
                                        <input type="password" name="credential_value" id="credential_value"
                                               class="form-control" required
                                               placeholder="{{ __('Enter your API key or token') }}">
                                        <button type="button" class="btn btn-outline-secondary" id="togglePassword">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                    <small class="text-muted">
                                        <i class="fas fa-lock me-1"></i>
                                        {{ __('This value will be encrypted before storage') }}
                                    </small>
                                </div>
                            </div>

                            <div class="row justify-content-center mt-3">
                                <div class="col-lg-4">
                                    <div class="left-area">
                                        <h4 class="heading">{{ __('Description') }}</h4>
                                        <p class="sub-heading">{{ __('Optional description') }}</p>
                                    </div>
                                </div>
                                <div class="col-lg-7">
                                    <textarea name="description" class="form-control" rows="2"
                                              placeholder="{{ __('e.g., Production API key for payment gateway') }}"></textarea>
                                </div>
                            </div>

                            <div class="row justify-content-center mt-4">
                                <div class="col-lg-4">
                                    <div class="left-area"></div>
                                </div>
                                <div class="col-lg-7">
                                    <button class="btn btn-primary" type="submit">
                                        <i class="fas fa-save me-1"></i> {{ __('Save Credential') }}
                                    </button>
                                    <a href="{{ route('admin.credentials.index') }}" class="btn btn-secondary">
                                        <i class="fas fa-arrow-left me-1"></i> {{ __('Cancel') }}
                                    </a>
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

@section('scripts')
<script>
    $(document).ready(function() {
        // Toggle password visibility
        $('#togglePassword').click(function() {
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

        // Show/hide custom service name field
        $('#service_name').change(function() {
            if ($(this).val() === 'other') {
                $('#customServiceRow').show();
                $('input[name="custom_service_name"]').attr('required', true);
            } else {
                $('#customServiceRow').hide();
                $('input[name="custom_service_name"]').attr('required', false);
            }
        });

        // Show/hide custom key name field
        $('#key_name').change(function() {
            if ($(this).val() === 'other') {
                $('#customKeyRow').show();
                $('input[name="custom_key_name"]').attr('required', true);
            } else {
                $('#customKeyRow').hide();
                $('input[name="custom_key_name"]').attr('required', false);
            }
        });
    });
</script>
@endsection
