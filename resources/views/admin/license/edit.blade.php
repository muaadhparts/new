@extends('layouts.load')

@section('content')
<div class="content-area">
    <div class="add-product-content1">
        <div class="row">
            <div class="col-lg-12">
                <div class="product-description">
                    <div class="body-area">
                        @include('alerts.admin.form-error')
                        <form id="muaadhformdata" action="{{ route('admin-license-update', $data->id) }}" method="POST" enctype="multipart/form-data">
                            {{ csrf_field() }}

                            <div class="row">
                                <div class="col-lg-4">
                                    <div class="left-area">
                                        <h4 class="heading">{{ __('License Key') }}</h4>
                                    </div>
                                </div>
                                <div class="col-lg-7">
                                    <input type="text" class="form-control" value="{{ $data->license_key }}" readonly>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-lg-4">
                                    <div class="left-area">
                                        <h4 class="heading">{{ __('Owner Name') }}</h4>
                                    </div>
                                </div>
                                <div class="col-lg-7">
                                    <input type="text" class="form-control" name="owner_name" placeholder="{{ __('Enter Owner Name') }}" value="{{ $data->owner_name }}">
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-lg-4">
                                    <div class="left-area">
                                        <h4 class="heading">{{ __('Owner Email') }}</h4>
                                    </div>
                                </div>
                                <div class="col-lg-7">
                                    <input type="email" class="form-control" name="owner_email" placeholder="{{ __('Enter Owner Email') }}" value="{{ $data->owner_email }}">
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-lg-4">
                                    <div class="left-area">
                                        <h4 class="heading">{{ __('License Type') }} *</h4>
                                    </div>
                                </div>
                                <div class="col-lg-7">
                                    <select name="license_type" required>
                                        <option value="standard" {{ $data->license_type == 'standard' ? 'selected' : '' }}>{{ __('Standard') }}</option>
                                        <option value="extended" {{ $data->license_type == 'extended' ? 'selected' : '' }}>{{ __('Extended') }}</option>
                                        <option value="developer" {{ $data->license_type == 'developer' ? 'selected' : '' }}>{{ __('Developer') }}</option>
                                        <option value="unlimited" {{ $data->license_type == 'unlimited' ? 'selected' : '' }}>{{ __('Unlimited') }}</option>
                                    </select>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-lg-4">
                                    <div class="left-area">
                                        <h4 class="heading">{{ __('Status') }} *</h4>
                                    </div>
                                </div>
                                <div class="col-lg-7">
                                    <select name="status" required>
                                        <option value="active" {{ $data->status == 'active' ? 'selected' : '' }}>{{ __('Active') }}</option>
                                        <option value="inactive" {{ $data->status == 'inactive' ? 'selected' : '' }}>{{ __('Inactive') }}</option>
                                        <option value="expired" {{ $data->status == 'expired' ? 'selected' : '' }}>{{ __('Expired') }}</option>
                                        <option value="suspended" {{ $data->status == 'suspended' ? 'selected' : '' }}>{{ __('Suspended') }}</option>
                                    </select>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-lg-4">
                                    <div class="left-area">
                                        <h4 class="heading">{{ __('Max Domains') }} *</h4>
                                        <p class="sub-heading">{{ __('0 = Unlimited') }}</p>
                                    </div>
                                </div>
                                <div class="col-lg-7">
                                    <input type="number" class="form-control" name="max_domains" value="{{ $data->max_domains }}" min="0" required>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-lg-4">
                                    <div class="left-area">
                                        <h4 class="heading">{{ __('Used Domains') }}</h4>
                                    </div>
                                </div>
                                <div class="col-lg-7">
                                    <input type="text" class="form-control" value="{{ $data->used_domains }}" readonly>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-lg-4">
                                    <div class="left-area">
                                        <h4 class="heading">{{ __('Domain') }}</h4>
                                    </div>
                                </div>
                                <div class="col-lg-7">
                                    <input type="text" class="form-control" value="{{ $data->domain ?? 'N/A' }}" readonly>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-lg-4">
                                    <div class="left-area">
                                        <h4 class="heading">{{ __('Expires At') }}</h4>
                                        <p class="sub-heading">{{ __('Leave empty for lifetime') }}</p>
                                    </div>
                                </div>
                                <div class="col-lg-7">
                                    <input type="date" class="form-control" name="expires_at" value="{{ $data->expires_at ? $data->expires_at->format('Y-m-d') : '' }}">
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-lg-4">
                                    <div class="left-area">
                                        <h4 class="heading">{{ __('Activated At') }}</h4>
                                    </div>
                                </div>
                                <div class="col-lg-7">
                                    <input type="text" class="form-control" value="{{ $data->activated_at ? $data->activated_at->format('Y-m-d H:i:s') : 'Not activated yet' }}" readonly>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-lg-4">
                                    <div class="left-area">
                                        <h4 class="heading">{{ __('Notes') }}</h4>
                                    </div>
                                </div>
                                <div class="col-lg-7">
                                    <textarea class="form-control" name="notes" rows="3" placeholder="{{ __('Any notes about this license...') }}">{{ $data->notes }}</textarea>
                                </div>
                            </div>

                            <br>
                            <div class="row">
                                <div class="col-lg-4">
                                    <div class="left-area"></div>
                                </div>
                                <div class="col-lg-7">
                                    <button class="btn btn-primary" type="submit">{{ __('Update License') }}</button>
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
