@extends('layouts.operator')

@section('content')
<div class="content-area">
    <div class="mr-breadcrumb">
        <div class="row">
            <div class="col-lg-12">
                <h4 class="heading">{{ __('Merchant Credentials') }} <i class="fas fa-store"></i></h4>
                <ul class="links">
                    <li>
                        <a href="{{ route('operator.dashboard') }}">{{ __('Dashboard') }}</a>
                    </li>
                    <li>
                        <a href="javascript:;">{{ __('Settings') }}</a>
                    </li>
                    <li>
                        <a href="{{ route('operator.merchant-credentials.index') }}">{{ __('Merchant Credentials') }}</a>
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

                        @include('alerts.operator.form-both')

                        {{-- Header with Add Button --}}
                        <div class="row mb-4">
                            <div class="col-lg-6">
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i>
                                    <strong>{{ __('Merchant Payment & Shipping Credentials') }}</strong>
                                    <br>
                                    <small>{{ __('Manage MyFatoorah, Tryoto, and other payment/shipping credentials for each merchant.') }}</small>
                                </div>
                            </div>
                            <div class="col-lg-6 text-end">
                                <a href="{{ route('operator.merchant-credentials.create') }}" class="btn btn-primary">
                                    <i class="fas fa-plus me-1"></i> {{ __('Add Merchant Credential') }}
                                </a>
                            </div>
                        </div>

                        {{-- Filters --}}
                        <div class="row mb-4">
                            <div class="col-lg-12">
                                <form action="{{ route('operator.merchant-credentials.index') }}" method="GET" class="row g-3">
                                    <div class="col-md-4">
                                        <select name="merchant_id" class="form-control">
                                            <option value="">{{ __('All Merchants') }}</option>
                                            @foreach($merchants as $merchant)
                                                <option value="{{ $merchant->id }}" {{ request('merchant_id') == $merchant->id ? 'selected' : '' }}>
                                                    {{ $merchant->shop_name ?: $merchant->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <select name="service" class="form-control">
                                            <option value="">{{ __('All Services') }}</option>
                                            @foreach($services as $key => $name)
                                                <option value="{{ $key }}" {{ request('service') == $key ? 'selected' : '' }}>
                                                    {{ $name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <button type="submit" class="btn btn-secondary w-100">
                                            <i class="fas fa-filter me-1"></i> {{ __('Filter') }}
                                        </button>
                                    </div>
                                    @if(request()->hasAny(['merchant_id', 'service']))
                                        <div class="col-md-2">
                                            <a href="{{ route('operator.merchant-credentials.index') }}" class="btn btn-outline-secondary w-100">
                                                <i class="fas fa-times me-1"></i> {{ __('Clear') }}
                                            </a>
                                        </div>
                                    @endif
                                </form>
                            </div>
                        </div>

                        {{-- Credentials Table --}}
                        @if($credentials->isEmpty())
                            <div class="alert alert-warning text-center">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                {{ __('No merchant credentials found. Click "Add Merchant Credential" to add payment/shipping keys for merchants.') }}
                            </div>
                        @else
                            <div class="table-responsive">
                                <table class="table table-hover table-bordered">
                                    <thead class="table-dark">
                                        <tr>
                                            <th width="5%">#</th>
                                            <th width="15%">{{ __('Merchant') }}</th>
                                            <th width="12%">{{ __('Service') }}</th>
                                            <th width="12%">{{ __('Key Name') }}</th>
                                            <th width="8%">{{ __('Environment') }}</th>
                                            <th width="20%">{{ __('Value (Masked)') }}</th>
                                            <th width="8%">{{ __('Status') }}</th>
                                            <th width="20%">{{ __('Actions') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($credentials as $credential)
                                            <tr>
                                                <td>{{ $credential->id }}</td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <i class="fas fa-store me-2 text-muted"></i>
                                                        <div>
                                                            <strong>{{ $credential->user->shop_name ?: $credential->user->name }}</strong>
                                                            <br>
                                                            <small class="text-muted">ID: {{ $credential->user_id }}</small>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="badge bg-primary">
                                                        @switch($credential->service_name)
                                                            @case('myfatoorah')
                                                                <i class="fas fa-credit-card me-1"></i>
                                                                @break
                                                            @case('tryoto')
                                                                <i class="fas fa-shipping-fast me-1"></i>
                                                                @break
                                                            @case('stripe')
                                                                <i class="fab fa-stripe me-1"></i>
                                                                @break
                                                            @case('paypal')
                                                                <i class="fab fa-paypal me-1"></i>
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
                                                    @if($credential->environment === 'live')
                                                        <span class="badge bg-success">
                                                            <i class="fas fa-check-circle me-1"></i> Live
                                                        </span>
                                                    @else
                                                        <span class="badge bg-warning text-dark">
                                                            <i class="fas fa-flask me-1"></i> Sandbox
                                                        </span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <code class="text-muted" style="font-size: 11px;">
                                                        {{ $credential->masked_value ?? '••••••••' }}
                                                    </code>
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
                                                        <a href="{{ route('operator.merchant-credentials.edit', $credential->id) }}"
                                                           class="btn btn-info" name="{{ __('Edit') }}">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <form action="{{ route('operator.merchant-credentials.toggle', $credential->id) }}"
                                                              method="POST" style="display: inline;">
                                                            @csrf
                                                            <button type="submit" class="btn btn-{{ $credential->is_active ? 'warning' : 'success' }}"
                                                                    name="{{ $credential->is_active ? __('Deactivate') : __('Activate') }}">
                                                                <i class="fas fa-{{ $credential->is_active ? 'pause' : 'play' }}"></i>
                                                            </button>
                                                        </form>
                                                        <form action="{{ route('operator.merchant-credentials.test', $credential->id) }}"
                                                              method="POST" style="display: inline;">
                                                            @csrf
                                                            <button type="submit" class="btn btn-secondary" name="{{ __('Test') }}">
                                                                <i class="fas fa-check-circle"></i>
                                                            </button>
                                                        </form>
                                                        <form action="{{ route('operator.merchant-credentials.destroy', $credential->id) }}"
                                                              method="POST" style="display: inline;"
                                                              onsubmit="return confirm('{{ __('Are you sure you want to delete this credential?') }}')">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="btn btn-danger" name="{{ __('Delete') }}">
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

                        {{-- Legend --}}
                        <div class="mt-4">
                            <div class="card">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0"><i class="fas fa-info-circle me-2"></i>{{ __('Service Types') }}</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <h6><i class="fas fa-credit-card me-2 text-success"></i>{{ __('Payment Gateways') }}</h6>
                                            <ul class="list-unstyled ms-4">
                                                <li><strong>MyFatoorah</strong> - {{ __('Payment gateway for Middle East') }}</li>
                                                <li><strong>Stripe</strong> - {{ __('International payment gateway') }}</li>
                                                <li><strong>PayPal</strong> - {{ __('Online payment system') }}</li>
                                            </ul>
                                        </div>
                                        <div class="col-md-6">
                                            <h6><i class="fas fa-shipping-fast me-2 text-primary"></i>{{ __('Shipping Providers') }}</h6>
                                            <ul class="list-unstyled ms-4">
                                                <li><strong>Tryoto</strong> - {{ __('Shipping aggregator for Saudi & Gulf') }}</li>
                                            </ul>
                                        </div>
                                    </div>
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
