@extends('layouts.load')

@section('content')

<div class="content-area">
    <div class="add-product-content1">
        <div class="row">
            <div class="col-lg-12">
                <div class="product-description">
                    <div class="body-area">
                        @include('alerts.admin.form-error')
                        <form id="muaadhformdata" action="{{ route('admin-brand-update', $data->id) }}" method="POST" enctype="multipart/form-data">
                            @csrf

                            @include('components.admin.form-row', [
                                'label' => __('Name'),
                                'name' => 'name',
                                'value' => old('name', $data->name),
                                'placeholder' => __('Enter Brand Name'),
                                'required' => true,
                                'subheading' => __('(e.g. Nissan, Toyota)')
                            ])

                            @include('components.admin.image-upload', [
                                'label' => __('Current Brand Image'),
                                'name' => 'photo',
                                'current' => $data->photo ? 'brand/' . $data->photo : null,
                                'required' => false
                            ])

                            @include('components.admin.submit-button', [
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
