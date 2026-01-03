@extends('layouts.load')

@section('content')

<div class="content-area">
    <div class="add-catalogItem-content1">
        <div class="row">
            <div class="col-lg-12">
                <div class="catalogItem-description">
                    <div class="body-area">
                        @include('alerts.admin.form-error')
                        <form id="muaadhformdata" action="{{ route('admin-brand-store') }}" method="POST" enctype="multipart/form-data">
                            @csrf

                            @include('components.admin.form-row', [
                                'label' => __('Name'),
                                'name' => 'name',
                                'value' => old('name'),
                                'placeholder' => __('Enter Brand Name'),
                                'required' => true,
                                'subheading' => __('(e.g. Nissan, Toyota)')
                            ])

                            @include('components.admin.image-upload', [
                                'label' => __('Brand Image'),
                                'name' => 'photo',
                                'required' => true
                            ])

                            @include('components.admin.submit-button', [
                                'label' => __('Create Brand')
                            ])

                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
