@extends('layouts.operator')

@section('content')
<div class="content-area">
    <div class="mr-breadcrumb">
        <div class="row">
            <div class="col-lg-12">
                <h4 class="heading">{{ __('Edit Merchant Credential') }} <i class="fas fa-edit"></i></h4>
                <ul class="links">
                    <li>
                        <a href="{{ route('operator.dashboard') }}">{{ __('Dashboard') }}</a>
                    </li>
                    <li>
                        <a href="{{ route('operator.merchant-credentials.index') }}">{{ __('Merchant Credentials') }}</a>
                    </li>
                    <li>
                        <a href="javascript:;">{{ __('Edit') }}</a>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <div class="add-catalogItem-content1">
        <div class="row">
            <div class="col-lg-8 offset-lg-2">
                <div class="catalogItem-description">
                    <div class="body-area">
                        <div class="gocover" style="background: url({{asset('assets/images/'.$gs->admin_loader)}}) no-repeat scroll center center rgba(45, 45, 45, 0.5);"></div>

                        @include('alerts.operator.form-both')

                        <form action="{{ route('operator.merchant-credentials.update', $credential->id) }}" method="POST">
                            @csrf
                            @method('PUT')

                            {{-- Owner Info (Read Only) --}}
                            <div class="row justify-content-center">
                                <div class="col-lg-4">
                                    <div class="left-area">
                                        <h4 class="heading">{{ __('Owner') }}</h4>
                                    </div>
                                </div>
                                <div class="col-lg-7">
                                    @if($credential->user_id == 0)
                                        <div class="alert alert-info border">
                                            <i class="fas fa-building me-2"></i>
                                            <strong>{{ __('Platform (Operator)') }}</strong>
                                            <br>
                                            <small class="text-muted">{{ __('Shared API - Can be used by merchants via shipping/payment settings') }}</small>
                                        </div>
                                    @else
                                        <div class="alert alert-light border">
                                            <i class="fas fa-store me-2"></i>
                                            <strong>{{ $credential->user?->shop_name ?: $credential->user?->name ?: 'N/A' }}</strong>
                                            <br>
                                            <small class="text-muted">ID: {{ $credential->user_id }}</small>
                                        </div>
                                    @endif
                                </div>
                            </div>

                            {{-- Service Info (Read Only) --}}
                            <div class="row justify-content-center mt-3">
                                <div class="col-lg-4">
                                    <div class="left-area">
                                        <h4 class="heading">{{ __('Service') }}</h4>
                                    </div>
                                </div>
                                <div class="col-lg-7">
                                    <div class="alert alert-light border">
                                        <span class="badge bg-primary me-2">
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
                                                @default
                                                    <i class="fas fa-key me-1"></i>
                                            @endswitch
                                            {{ ucfirst(str_replace('_', ' ', $credential->service_name)) }}
                                        </span>
                                        <code>{{ $credential->key_name }}</code>
                                    </div>
                                </div>
                            </div>

                            {{-- Environment --}}
                            <div class="row justify-content-center mt-3">
                                <div class="col-lg-4">
                                    <div class="left-area">
                                        <h4 class="heading">{{ __('Environment') }} *</h4>
                                        <p class="sub-heading">{{ __('Live or Sandbox mode') }}</p>
                                    </div>
                                </div>
                                <div class="col-lg-7">
                                    <select name="environment" id="environment" class="form-control" required>
                                        @foreach($environments as $key => $name)
                                            <option value="{{ $key }}" {{ old('environment', $credential->environment) == $key ? 'selected' : '' }}>
                                                {{ $name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            {{-- Current Value --}}
                            <div class="row justify-content-center mt-3">
                                <div class="col-lg-4">
                                    <div class="left-area">
                                        <h4 class="heading">{{ __('Current Value') }}</h4>
                                    </div>
                                </div>
                                <div class="col-lg-7">
                                    @php
                                        $decrypted = $credential->decrypted_value;
                                        $masked = $decrypted
                                            ? substr($decrypted, 0, 8) . '••••••••' . substr($decrypted, -4)
                                            : '••••••••';
                                    @endphp
                                    <div class="alert alert-secondary">
                                        <code>{{ $masked }}</code>
                                    </div>
                                </div>
                            </div>

                            {{-- New Value --}}
                            <div class="row justify-content-center mt-3">
                                <div class="col-lg-4">
                                    <div class="left-area">
                                        <h4 class="heading">{{ __('New Value') }}</h4>
                                        <p class="sub-heading">{{ __('Leave empty to keep current') }}</p>
                                    </div>
                                </div>
                                <div class="col-lg-7">
                                    <div class="input-group">
                                        <input type="password" name="credential_value" id="credential_value"
                                               class="form-control"
                                               placeholder="{{ __('Enter new value to change') }}">
                                        <button type="button" class="btn btn-outline-secondary" id="togglePassword">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                    <small class="text-muted">
                                        <i class="fas fa-info-circle me-1"></i>
                                        {{ __('Only fill this if you want to change the credential value') }}
                                    </small>
                                </div>
                            </div>

                            {{-- Description --}}
                            <div class="row justify-content-center mt-3">
                                <div class="col-lg-4">
                                    <div class="left-area">
                                        <h4 class="heading">{{ __('Description') }}</h4>
                                    </div>
                                </div>
                                <div class="col-lg-7">
                                    <textarea name="description" class="form-control" rows="2"
                                              placeholder="{{ __('Optional description') }}">{{ old('description', $credential->description) }}</textarea>
                                </div>
                            </div>

                            {{-- Status --}}
                            <div class="row justify-content-center mt-3">
                                <div class="col-lg-4">
                                    <div class="left-area">
                                        <h4 class="heading">{{ __('Status') }}</h4>
                                    </div>
                                </div>
                                <div class="col-lg-7">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="is_active" id="is_active"
                                               {{ old('is_active', $credential->is_active) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="is_active">
                                            {{ __('Active') }}
                                        </label>
                                    </div>
                                </div>
                            </div>

                            {{-- Last Used --}}
                            @if($credential->last_used_at)
                            <div class="row justify-content-center mt-3">
                                <div class="col-lg-4">
                                    <div class="left-area">
                                        <h4 class="heading">{{ __('Last Used') }}</h4>
                                    </div>
                                </div>
                                <div class="col-lg-7">
                                    <small class="text-muted">
                                        <i class="fas fa-clock me-1"></i>
                                        {{ $credential->last_used_at->diffForHumans() }}
                                        ({{ $credential->last_used_at->format('Y-m-d H:i') }})
                                    </small>
                                </div>
                            </div>
                            @endif

                            {{-- Submit --}}
                            <div class="row justify-content-center mt-4">
                                <div class="col-lg-4">
                                    <div class="left-area"></div>
                                </div>
                                <div class="col-lg-7">
                                    <button class="btn btn-primary" type="submit">
                                        <i class="fas fa-save me-1"></i> {{ __('Update Credential') }}
                                    </button>
                                    <a href="{{ route('operator.merchant-credentials.index') }}" class="btn btn-secondary">
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
    });
</script>
@endsection
