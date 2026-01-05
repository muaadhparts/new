@extends('layouts.operator')

@section('content')
<div class="content-area">
    <div class="mr-breadcrumb">
        <div class="row">
            <div class="col-lg-12">
                <h4 class="heading">{{ __('Edit Credential') }} <i class="fas fa-edit"></i></h4>
                <ul class="links">
                    <li>
                        <a href="{{ route('operator.dashboard') }}">{{ __('Dashboard') }}</a>
                    </li>
                    <li>
                        <a href="{{ route('operator.credentials.index') }}">{{ __('API Credentials') }}</a>
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

                        <form action="{{ route('operator.credentials.update', $credential->id) }}" method="POST">
                            @csrf
                            @method('PUT')

                            {{-- Credential Info (Read-only) --}}
                            <div class="row justify-content-center">
                                <div class="col-lg-4">
                                    <div class="left-area">
                                        <h4 class="heading">{{ __('Service') }}</h4>
                                    </div>
                                </div>
                                <div class="col-lg-7">
                                    <input type="text" class="form-control" value="{{ ucfirst(str_replace('_', ' ', $credential->service_name)) }}" readonly>
                                    <small class="text-muted">{{ __('Service name cannot be changed') }}</small>
                                </div>
                            </div>

                            <div class="row justify-content-center mt-3">
                                <div class="col-lg-4">
                                    <div class="left-area">
                                        <h4 class="heading">{{ __('Key Name') }}</h4>
                                    </div>
                                </div>
                                <div class="col-lg-7">
                                    <input type="text" class="form-control" value="{{ $credential->key_name }}" readonly>
                                    <small class="text-muted">{{ __('Key name cannot be changed') }}</small>
                                </div>
                            </div>

                            {{-- Current Value (Masked) --}}
                            <div class="row justify-content-center mt-3">
                                <div class="col-lg-4">
                                    <div class="left-area">
                                        <h4 class="heading">{{ __('Current Value') }}</h4>
                                    </div>
                                </div>
                                <div class="col-lg-7">
                                    @php
                                        $decrypted = $credential->decrypted_value;
                                        $masked = $decrypted ? substr($decrypted, 0, 8) . '••••••••' . substr($decrypted, -4) : 'N/A';
                                    @endphp
                                    <div class="input-group">
                                        <input type="text" class="form-control" value="{{ $masked }}" readonly style="background: #f8f9fa;">
                                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
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
                                               placeholder="{{ __('Enter new value to update') }}">
                                        <button type="button" class="btn btn-outline-secondary" id="togglePassword">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                    <small class="text-muted">
                                        <i class="fas fa-info-circle me-1"></i>
                                        {{ __('Leave empty if you do not want to change the current value') }}
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
                                    <textarea name="description" class="form-control" rows="2">{{ $credential->description }}</textarea>
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
                                    <label class="switch">
                                        <input type="checkbox" name="is_active" value="1" {{ $credential->is_active ? 'checked' : '' }}>
                                        <span class="slider round"></span>
                                    </label>
                                    <span class="ms-2">{{ __('Active') }}</span>
                                </div>
                            </div>

                            {{-- Metadata --}}
                            <div class="row justify-content-center mt-3">
                                <div class="col-lg-4">
                                    <div class="left-area">
                                        <h4 class="heading">{{ __('Info') }}</h4>
                                    </div>
                                </div>
                                <div class="col-lg-7">
                                    <div class="alert alert-light border">
                                        <small>
                                            <strong>{{ __('Created:') }}</strong> {{ $credential->created_at?->format('Y-m-d H:i') ?? 'N/A' }}<br>
                                            <strong>{{ __('Updated:') }}</strong> {{ $credential->updated_at?->format('Y-m-d H:i') ?? 'N/A' }}<br>
                                            <strong>{{ __('Last Used:') }}</strong> {{ $credential->last_used_at?->format('Y-m-d H:i') ?? __('Never') }}
                                        </small>
                                    </div>
                                </div>
                            </div>

                            {{-- Actions --}}
                            <div class="row justify-content-center mt-4">
                                <div class="col-lg-4">
                                    <div class="left-area"></div>
                                </div>
                                <div class="col-lg-7">
                                    <button class="btn btn-primary" type="submit">
                                        <i class="fas fa-save me-1"></i> {{ __('Update Credential') }}
                                    </button>
                                    <a href="{{ route('operator.credentials.index') }}" class="btn btn-secondary">
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
