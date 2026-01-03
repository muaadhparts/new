@extends('layouts.merchant')

@section('content')
    <div class="gs-merchant-outlet">
        <!-- breadcrumb start -->
        <div class="gs-merchant-breadcrumb has-mb">
            <div class="d-flex gap-4 custom-gap-sm-2 flex-wrap align-items-center">
                <h4 class="text-capitalize">@lang('Edit Credential')</h4>
            </div>
            <ul class="breadcrumb-menu">
                <li>
                    <a href="{{ route('merchant.dashboard') }}" class="text-capitalize">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                            fill="none" stroke="currentColor" class="home-icon-merchant-panel-breadcrumb">
                            <path
                                d="M9 21V13.6C9 13.0399 9 12.7599 9.109 12.546C9.20487 12.3578 9.35785 12.2049 9.54601 12.109C9.75993 12 10.04 12 10.6 12H13.4C13.9601 12 14.2401 12 14.454 12.109C14.6422 12.2049 14.7951 12.3578 14.891 12.546C15 12.7599 15 13.0399 15 13.6V21M2 9.5L11.04 2.72C11.3843 2.46181 11.5564 2.33271 11.7454 2.28294C11.9123 2.23902 12.0877 2.23902 12.2546 2.28295C12.4436 2.33271 12.6157 2.46181 12.96 2.72L22 9.5M4 8V17.8C4 18.9201 4 19.4802 4.21799 19.908C4.40974 20.2843 4.7157 20.5903 5.09202 20.782C5.51985 21 6.0799 21 7.2 21H16.8C17.9201 21 18.4802 21 18.908 20.782C19.2843 20.5903 19.5903 20.2843 19.782 19.908C20 19.4802 20 18.9201 20 17.8V8L13.92 3.44C13.2315 2.92361 12.8872 2.66542 12.5091 2.56589C12.1754 2.47804 11.8246 2.47804 11.4909 2.56589C11.1128 2.66542 10.7685 2.92361 10.08 3.44L4 8Z"
                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </a>
                </li>
                <li>
                    <a href="{{ route('merchant.dashboard') }}" class="text-capitalize">@lang('Dashboard')</a>
                </li>
                <li>
                    <a href="{{ route('merchant-credentials-index') }}" class="text-capitalize">@lang('API Credentials')</a>
                </li>
                <li>
                    <a href="#" class="text-capitalize">@lang('Edit')</a>
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

        <div class="gs-merchant-erning">
            <div class="merchant-table-wrapper catalogItem-catalogs-table-wrapper">
                <form action="{{ route('merchant-credentials-update', $credential->id) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="row">
                        <!-- Service Info (Read-only) -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label">@lang('Service')</label>
                            @php
                                $allServices = array_merge($availableServices['payment'] ?? [], $availableServices['shipping'] ?? []);
                                $serviceName = $allServices[$credential->service_name] ?? $credential->service_name;
                            @endphp
                            <input type="text" class="form-control" value="{{ $serviceName }}" disabled>
                        </div>

                        <!-- Key Name (Read-only) -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label">@lang('Key Name')</label>
                            <input type="text" class="form-control" value="{{ $credential->key_name }}" disabled>
                        </div>

                        <!-- Environment (Read-only) -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label">@lang('Environment')</label>
                            @if($credential->environment == 'live')
                                <span class="badge bg-success d-block py-2">{{ __('Live (Production)') }}</span>
                            @else
                                <span class="badge bg-warning text-dark d-block py-2">{{ __('Sandbox (Testing)') }}</span>
                            @endif
                        </div>

                        <!-- Current Value (Masked) -->
                        <div class="col-12 mb-3">
                            <label class="form-label">@lang('Current Value')</label>
                            @php
                                $value = $credential->decrypted_value;
                                $masked = $value ? substr($value, 0, 4) . str_repeat('*', max(0, strlen($value) - 8)) . substr($value, -4) : '****';
                            @endphp
                            <code class="d-block p-2 bg-light rounded">{{ $masked }}</code>
                        </div>

                        <!-- New Value -->
                        <div class="col-12 mb-3">
                            <label class="form-label">@lang('New Value')</label>
                            <div class="input-group">
                                <input type="password" name="value" id="credential_value" class="form-control"
                                    placeholder="@lang('Leave empty to keep current value')">
                                <button class="btn btn-outline-secondary toggle-password" type="button">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <small class="text-muted">@lang('Leave empty to keep the current value.')</small>
                        </div>

                        <!-- Description -->
                        <div class="col-12 mb-3">
                            <label class="form-label">@lang('Description')</label>
                            <textarea name="description" class="form-control" rows="2"
                                placeholder="@lang('Optional description for this credential')">{{ old('description', $credential->description) }}</textarea>
                        </div>

                        <!-- Status -->
                        <div class="col-12 mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_active" id="is_active" value="1"
                                    {{ old('is_active', $credential->is_active) ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_active">@lang('Active')</label>
                            </div>
                        </div>

                        <!-- Metadata -->
                        <div class="col-12 mb-3">
                            <div class="row text-muted small">
                                <div class="col-md-4">
                                    <strong>@lang('Created'):</strong> {{ $credential->created_at->format('Y-m-d H:i') }}
                                </div>
                                <div class="col-md-4">
                                    <strong>@lang('Last Updated'):</strong> {{ $credential->updated_at->format('Y-m-d H:i') }}
                                </div>
                                <div class="col-md-4">
                                    <strong>@lang('Last Used'):</strong> {{ $credential->last_used_at ? $credential->last_used_at->diffForHumans() : __('Never') }}
                                </div>
                            </div>
                        </div>

                        <!-- Submit -->
                        <div class="col-12">
                            <button type="submit" class="template-btn">
                                <i class="fas fa-save me-2"></i>@lang('Update Credential')
                            </button>
                            <a href="{{ route('merchant-credentials-index') }}" class="template-btn black-btn ms-2">
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
</script>
@endsection
