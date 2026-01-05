@extends('layouts.load')

@section('content')

<div class="content-area">
    <div class="add-catalogItem-content1">
        <div class="row">
            <div class="col-lg-12">
                <div class="catalogItem-description">
                    <div class="body-area">
                        @include('alerts.operator.form-error')
                        <form id="muaadhformdata" action="{{ route('operator-currency-update', $data->id) }}" method="POST" enctype="multipart/form-data">
                            @csrf

                            @include('components.operator.form-row', [
                                'label' => __('Name'),
                                'name' => 'name',
                                'value' => old('name', $data->name),
                                'placeholder' => __('Enter Currency Name'),
                                'required' => true,
                                'subheading' => __('(In Any Language)')
                            ])

                            @include('components.operator.form-row', [
                                'label' => __('Sign'),
                                'name' => 'sign',
                                'value' => old('sign', $data->sign),
                                'placeholder' => __('Enter Currency Sign'),
                                'required' => true
                            ])

                            @include('components.operator.form-row', [
                                'label' => __('Value'),
                                'name' => 'value',
                                'value' => old('value', $data->value),
                                'placeholder' => __('Enter Currency Value'),
                                'required' => true,
                                'subheading' => __('(Please Enter The Value For 1 USD = ?)')
                            ])

                            <br>
                            @include('components.operator.submit-button', [
                                'label' => __('Save')
                            ])

                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
