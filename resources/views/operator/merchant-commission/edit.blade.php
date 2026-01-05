@extends('layouts.load')

@section('content')

<div class="content-area">
    <div class="add-catalogItem-content1">
        <div class="row">
            <div class="col-lg-12">
                <div class="catalogItem-description">
                    <div class="body-area">
                        @include('alerts.operator.form-error')

                        <div class="mb-4 p-3" style="background: var(--surface-secondary); border-radius: 8px;">
                            <h6 class="mb-2">{{ __('Merchant Information') }}</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <small class="text-muted">{{ __('Name') }}:</small>
                                    <p class="mb-1"><strong>{{ $merchant->name }}</strong></p>
                                </div>
                                <div class="col-md-6">
                                    <small class="text-muted">{{ __('Shop Name') }}:</small>
                                    <p class="mb-1"><strong>{{ $merchant->shop_name ?: '-' }}</strong></p>
                                </div>
                                <div class="col-md-6">
                                    <small class="text-muted">{{ __('Email') }}:</small>
                                    <p class="mb-0">{{ $merchant->email }}</p>
                                </div>
                                <div class="col-md-6">
                                    <small class="text-muted">{{ __('Phone') }}:</small>
                                    <p class="mb-0">{{ $merchant->phone ?: '-' }}</p>
                                </div>
                            </div>
                        </div>

                        <form id="muaadhformdata" action="{{ route('operator-merchant-commission-update', $merchant->id) }}" method="POST" enctype="multipart/form-data">
                            @csrf

                            @include('components.operator.form-row', [
                                'label' => __('Fixed Commission'),
                                'name' => 'fixed_commission',
                                'type' => 'number',
                                'value' => old('fixed_commission', $commission->fixed_commission),
                                'placeholder' => __('Enter Fixed Commission Amount'),
                                'required' => true,
                                'subheading' => __('Fixed amount added to product price'),
                                'step' => '0.01',
                                'min' => '0'
                            ])

                            @include('components.operator.form-row', [
                                'label' => __('Percentage Commission'),
                                'name' => 'percentage_commission',
                                'type' => 'number',
                                'value' => old('percentage_commission', $commission->percentage_commission),
                                'placeholder' => __('Enter Percentage Commission'),
                                'required' => true,
                                'subheading' => __('Percentage markup on product price (0-100)'),
                                'step' => '0.01',
                                'min' => '0',
                                'max' => '100'
                            ])

                            <div class="row">
                                <div class="col-lg-4">
                                    <div class="left-area">
                                        <h4 class="heading">{{ __('Status') }}</h4>
                                    </div>
                                </div>
                                <div class="col-lg-7">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', $commission->is_active) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="is_active">{{ __('Active') }}</label>
                                    </div>
                                    <small class="text-muted">{{ __('When inactive, no commission is applied to this merchant') }}</small>
                                </div>
                            </div>
                            <br>

                            @include('components.operator.form-row', [
                                'label' => __('Notes'),
                                'name' => 'notes',
                                'type' => 'textarea',
                                'value' => old('notes', $commission->notes),
                                'placeholder' => __('Optional notes about this commission setting'),
                                'required' => false
                            ])

                            <br>
                            @include('components.operator.submit-button', [
                                'label' => __('Save Commission Settings')
                            ])

                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
