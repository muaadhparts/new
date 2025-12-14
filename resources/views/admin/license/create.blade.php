@extends('layouts.load')

@section('content')
<div class="content-area">
    <div class="add-product-content1">
        <div class="row">
            <div class="col-lg-12">
                <div class="product-description">
                    <div class="body-area">
                        @include('alerts.admin.form-error')
                        <form id="muaadhformdata" action="{{ route('admin-license-store') }}" method="POST" enctype="multipart/form-data">
                            {{ csrf_field() }}

                            <div class="row">
                                <div class="col-lg-4">
                                    <div class="left-area">
                                        <h4 class="heading">{{ __('Owner Name') }}</h4>
                                    </div>
                                </div>
                                <div class="col-lg-7">
                                    <input type="text" class="form-control" name="owner_name" placeholder="{{ __('Enter Owner Name') }}" value="">
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-lg-4">
                                    <div class="left-area">
                                        <h4 class="heading">{{ __('Owner Email') }}</h4>
                                    </div>
                                </div>
                                <div class="col-lg-7">
                                    <input type="email" class="form-control" name="owner_email" placeholder="{{ __('Enter Owner Email') }}" value="">
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
                                        <option value="standard">{{ __('Standard') }}</option>
                                        <option value="extended">{{ __('Extended') }}</option>
                                        <option value="developer">{{ __('Developer') }}</option>
                                        <option value="unlimited">{{ __('Unlimited') }}</option>
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
                                    <input type="number" class="form-control" name="max_domains" placeholder="1" value="1" min="0" required>
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
                                    <input type="date" class="form-control" name="expires_at" value="">
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-lg-4">
                                    <div class="left-area">
                                        <h4 class="heading">{{ __('Notes') }}</h4>
                                    </div>
                                </div>
                                <div class="col-lg-7">
                                    <textarea class="form-control" name="notes" rows="3" placeholder="{{ __('Any notes about this license...') }}"></textarea>
                                </div>
                            </div>

                            <br>
                            <div class="row">
                                <div class="col-lg-4">
                                    <div class="left-area"></div>
                                </div>
                                <div class="col-lg-7">
                                    <button class="btn btn-primary" type="submit">{{ __('Generate License') }}</button>
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
