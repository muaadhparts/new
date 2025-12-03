@extends('layouts.admin')

@section('content')
<div class="content-area">
    <div class="mr-breadcrumb">
        <div class="row">
            <div class="col-lg-12">
                <h4 class="heading">{{ __('System Activation') }}</h4>
                <ul class="links">
                    <li>
                        <a href="{{ route('admin.dashboard') }}">{{ __('Dashboard') }} </a>
                    </li>
                    <li>
                        <a href="{{ route('admin-activation-form') }}">{{ __('Activation') }}</a>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body text-center py-5">
                    @if($activeLicense)
                        {{-- System is activated --}}
                        <i class="icofont-check-circled text-success" style="font-size: 80px;"></i>
                        <h2 class="mt-4 text-success">{{ __('System Activated!') }}</h2>
                        <p class="text-muted mb-4">{{ __('Your system is successfully activated.') }}</p>

                        <div class="table-responsive mt-4">
                            <table class="table table-bordered">
                                <tr>
                                    <th class="text-right" width="40%">{{ __('License Key') }}</th>
                                    <td class="text-left"><code>{{ $activeLicense->license_key }}</code></td>
                                </tr>
                                <tr>
                                    <th class="text-right">{{ __('License Type') }}</th>
                                    <td class="text-left">
                                        <span class="badge badge-primary">{{ ucfirst($activeLicense->license_type) }}</span>
                                    </td>
                                </tr>
                                <tr>
                                    <th class="text-right">{{ __('Owner') }}</th>
                                    <td class="text-left">{{ $activeLicense->owner_name ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <th class="text-right">{{ __('Domain') }}</th>
                                    <td class="text-left">{{ $activeLicense->domain ?? request()->getHost() }}</td>
                                </tr>
                                <tr>
                                    <th class="text-right">{{ __('Activated At') }}</th>
                                    <td class="text-left">{{ $activeLicense->activated_at ? $activeLicense->activated_at->format('Y-m-d H:i:s') : 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <th class="text-right">{{ __('Expires At') }}</th>
                                    <td class="text-left">
                                        @if($activeLicense->expires_at)
                                            <span class="badge {{ $activeLicense->expires_at->isPast() ? 'badge-danger' : 'badge-success' }}">
                                                {{ $activeLicense->expires_at->format('Y-m-d') }}
                                            </span>
                                        @else
                                            <span class="badge badge-success">{{ __('Lifetime') }}</span>
                                        @endif
                                    </td>
                                </tr>
                            </table>
                        </div>

                        <div class="mt-4">
                            <a href="{{ route('admin-license-index') }}" class="btn btn-primary">
                                <i class="fas fa-cog"></i> {{ __('Manage Licenses') }}
                            </a>
                        </div>
                    @else
                        {{-- System not activated --}}
                        <i class="icofont-warning-alt text-warning" style="font-size: 80px;"></i>
                        <h2 class="mt-4 text-warning">{{ __('System Not Activated') }}</h2>
                        <p class="text-muted mb-4">{{ __('Please enter your license key to activate the system.') }}</p>

                        <div class="row justify-content-center">
                            <div class="col-md-8">
                                <div class="gocover" style="background: url({{ asset('assets/images/' . $gs->admin_loader) }}) no-repeat scroll center center rgba(45, 45, 45, 0.5); display: none;"></div>
                                @include('alerts.admin.form-both')

                                <form id="muaadhform" action="{{ route('admin-activate-purchase') }}" method="POST">
                                    @csrf
                                    <div class="form-group">
                                        <label for="license_key" class="text-left d-block">{{ __('License Key') }} *</label>
                                        <input type="text" class="form-control form-control-lg text-center" id="license_key" name="license_key"
                                               placeholder="MU-XXXX-XXXX-XXXX-XXXX" required
                                               style="font-family: monospace; letter-spacing: 2px;">
                                    </div>

                                    <button type="submit" class="btn btn-success btn-lg mt-3">
                                        <i class="fas fa-key"></i> {{ __('Activate System') }}
                                    </button>
                                </form>
                            </div>
                        </div>

                        <hr class="my-5">

                        <p class="text-muted">
                            {{ __('Don\'t have a license key?') }}
                            <a href="{{ route('admin-license-index') }}" class="text-primary">{{ __('Create one here') }}</a>
                        </p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    // Format license key input
    $('#license_key').on('input', function() {
        var value = $(this).val().toUpperCase().replace(/[^A-Z0-9]/g, '');
        var formatted = '';

        // Add MU- prefix if not present
        if (value.length > 0 && !value.startsWith('MU')) {
            // Keep as is for now
        }

        $(this).val(value);
    });
});
</script>
@endsection
