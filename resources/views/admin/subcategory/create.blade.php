@extends('layouts.load')

@section('content')

<div class="content-area">
    <div class="add-product-content1">
        <div class="row">
            <div class="col-lg-12">
                <div class="product-description">
                    <div class="body-area">
                        @include('alerts.admin.form-error')
                        <form id="muaadhformdata" action="{{ route('admin-subcat-create') }}" method="POST" enctype="multipart/form-data">
                            @csrf

                            @include('components.admin.form-row', [
                                'label' => __('Main Category'),
                                'name' => 'category_id',
                                'type' => 'select',
                                'options' => $cats,
                                'optionValue' => 'id',
                                'optionLabel' => 'name',
                                'placeholder' => __('Select Main Category'),
                                'required' => true
                            ])

                            <div class="row">
                                <div class="col-lg-2">
                                    <div class="left-area">
                                        <h4 class="heading">{{ __('Name') }} *</h4>
                                    </div>
                                </div>
                                <div class="col-lg-5">
                                    <input type="text" class="form-control" name="name" placeholder="{{ __('English') }}" required value="{{ old('name') }}">
                                </div>
                                <div class="col-lg-5">
                                    <input type="text" class="form-control" name="name_ar" placeholder="{{ __('Arabic') }}" required value="{{ old('name_ar') }}">
                                </div>
                            </div>

                            @include('components.admin.form-row', [
                                'label' => __('Slug'),
                                'name' => 'slug',
                                'value' => old('slug'),
                                'placeholder' => __('Enter Slug'),
                                'required' => true,
                                'subheading' => __('(In English)')
                            ])

                            <br>
                            @include('components.admin.submit-button', [
                                'label' => __('Create')
                            ])

                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
