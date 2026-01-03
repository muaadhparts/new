@extends('layouts.admin')

@section('content')
<div class="content-area">
    <div class="mr-breadcrumb">
        <div class="row">
            <div class="col-lg-12">
                <h4 class="heading">{{ __('System Credentials') }} <i class="fas fa-cog"></i></h4>
                <ul class="links">
                    <li>
                        <a href="{{ route('admin.dashboard') }}">{{ __('Dashboard') }}</a>
                    </li>
                    <li>
                        <a href="javascript:;">{{ __('Settings') }}</a>
                    </li>
                    <li>
                        <a href="{{ route('admin.credentials.index') }}">{{ __('System Credentials') }}</a>
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

                        @include('alerts.admin.form-both')

                        {{-- Header with Add Button --}}
                        <div class="row mb-4">
                            <div class="col-lg-8">
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i>
                                    <strong>{{ __('System-Level Credentials') }}</strong>
                                    <br>
                                    <small>{{ __('Google Maps, DigitalOcean, and other system-wide API keys. For merchant payment/shipping credentials, use Merchant Credentials section.') }}</small>
                                </div>
                            </div>
                            <div class="col-lg-4 text-end">
                                <a href="{{ route('admin.credentials.create') }}" class="btn btn-primary">
                                    <i class="fas fa-plus me-1"></i> {{ __('Add System Credential') }}
                                </a>
                            </div>
                        </div>

                        {{-- Credentials Table --}}
                        @if($credentials->isEmpty())
                            <div class="alert alert-info text-center">
                                <i class="fas fa-info-circle me-2"></i>
                                {{ __('No credentials found. Click "Add New Credential" to add your first API key.') }}
                            </div>
                        @else
                            <div class="table-responsive">
                                <table class="table table-hover table-bordered">
                                    <thead class="table-dark">
                                        <tr>
                                            <th width="5%">#</th>
                                            <th width="15%">{{ __('Service') }}</th>
                                            <th width="15%">{{ __('Key Name') }}</th>
                                            <th width="25%">{{ __('Value (Masked)') }}</th>
                                            <th width="15%">{{ __('Description') }}</th>
                                            <th width="10%">{{ __('Status') }}</th>
                                            <th width="15%">{{ __('Actions') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($credentials as $credential)
                                            <tr>
                                                <td>{{ $credential->id }}</td>
                                                <td>
                                                    <span class="badge bg-primary">
                                                        @switch($credential->service_name)
                                                            @case('google_maps')
                                                                <i class="fab fa-google me-1"></i>
                                                                @break
                                                            @case('myfatoorah')
                                                                <i class="fas fa-credit-card me-1"></i>
                                                                @break
                                                            @case('tryoto')
                                                                <i class="fas fa-shipping-fast me-1"></i>
                                                                @break
                                                            @case('digitalocean')
                                                                <i class="fab fa-digital-ocean me-1"></i>
                                                                @break
                                                            @case('aws')
                                                                <i class="fab fa-aws me-1"></i>
                                                                @break
                                                            @case('stripe')
                                                                <i class="fab fa-stripe me-1"></i>
                                                                @break
                                                            @default
                                                                <i class="fas fa-key me-1"></i>
                                                        @endswitch
                                                        {{ ucfirst(str_replace('_', ' ', $credential->service_name)) }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <code>{{ $credential->key_name }}</code>
                                                </td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <code class="text-muted credential-value" style="font-size: 12px;">
                                                            {{ $credential->masked_value ?? '••••••••' }}
                                                        </code>
                                                    </div>
                                                </td>
                                                <td>
                                                    <small class="text-muted">{{ $credential->description ?? '-' }}</small>
                                                </td>
                                                <td>
                                                    @if($credential->is_active)
                                                        <span class="badge bg-success">{{ __('Active') }}</span>
                                                    @else
                                                        <span class="badge bg-danger">{{ __('Inactive') }}</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <div class="btn-group btn-group-sm">
                                                        <a href="{{ route('admin.credentials.edit', $credential->id) }}"
                                                           class="btn btn-info" title="{{ __('Edit') }}">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <form action="{{ route('admin.credentials.toggle', $credential->id) }}"
                                                              method="POST" style="display: inline;">
                                                            @csrf
                                                            <button type="submit" class="btn btn-{{ $credential->is_active ? 'warning' : 'success' }}"
                                                                    title="{{ $credential->is_active ? __('Deactivate') : __('Activate') }}">
                                                                <i class="fas fa-{{ $credential->is_active ? 'pause' : 'play' }}"></i>
                                                            </button>
                                                        </form>
                                                        <form action="{{ route('admin.credentials.test', $credential->id) }}"
                                                              method="POST" style="display: inline;">
                                                            @csrf
                                                            <button type="submit" class="btn btn-secondary" title="{{ __('Test') }}">
                                                                <i class="fas fa-check-circle"></i>
                                                            </button>
                                                        </form>
                                                        <form action="{{ route('admin.credentials.destroy', $credential->id) }}"
                                                              method="POST" style="display: inline;"
                                                              onsubmit="return confirm('{{ __('Are you sure you want to delete this credential?') }}')">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="btn btn-danger" title="{{ __('Delete') }}">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </form>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif

                        {{-- Usage Guide --}}
                        <div class="mt-4">
                            <div class="card">
                                <div class="card-header bg-light">
                                    <h5 class="mb-0"><i class="fas fa-code me-2"></i>{{ __('How to use in code') }}</h5>
                                </div>
                                <div class="card-body">
                                    <pre style="background: #1e1e1e; color: #d4d4d4; padding: 15px; border-radius: 5px; direction: ltr; text-align: left;"><code>use App\Services\ApiCredentialService;

$credService = new ApiCredentialService();

// Get specific credential
$googleKey = $credService->get('google_maps', 'api_key');
$fatoorahKey = $credService->get('myfatoorah', 'api_key');

// Or use helper methods
$googleKey = $credService->getGoogleMapsKey();
$fatoorahKey = $credService->getMyFatoorahKey();</code></pre>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
