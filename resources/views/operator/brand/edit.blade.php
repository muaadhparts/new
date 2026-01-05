@extends('layouts.load')

@section('content')

<div class="content-area">
    <div class="add-catalogItem-content1">
        <div class="row">
            <div class="col-lg-12">
                <div class="catalogItem-description">
                    <div class="body-area">
                        @include('alerts.operator.form-error')
                        <form id="muaadhformdata" action="{{ route('operator-brand-update', $data->id) }}" method="POST" enctype="multipart/form-data">
                            @csrf

                            @include('components.operator.form-row', [
                                'label' => __('Name'),
                                'name' => 'name',
                                'value' => old('name', $data->name),
                                'placeholder' => __('Enter Brand Name'),
                                'required' => true,
                                'subheading' => __('(e.g. Nissan, Toyota)')
                            ])

                            @include('components.operator.image-upload', [
                                'label' => __('Current Brand Image'),
                                'name' => 'photo',
                                'current' => $data->photo ? 'brand/' . $data->photo : null,
                                'required' => false
                            ])

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
