@extends('layouts.load')

@section('content')

<div class="content-area">
    <div class="add-product-content1">
        <div class="row">
            <div class="col-lg-12">
                <div class="product-description">
                    <div class="body-area">
                        @include('alerts.admin.form-error')
                        <form id="muaadhformdata" action="{{ route('admin-childcat-create') }}" method="POST" enctype="multipart/form-data">
                            @csrf

                            <div class="row">
                                <div class="col-lg-4">
                                    <div class="left-area">
                                        <h4 class="heading">{{ __('Category') }} *</h4>
                                    </div>
                                </div>
                                <div class="col-lg-7">
                                    <select id="cat" required="">
                                        <option value="">{{ __('Select Category') }}</option>
                                        @foreach($cats as $cat)
                                            <option data-href="{{ route('admin-subcat-load', $cat->id) }}" value="{{ $cat->id }}">{{ $cat->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-lg-4">
                                    <div class="left-area">
                                        <h4 class="heading">{{ __('Sub Category') }} *</h4>
                                    </div>
                                </div>
                                <div class="col-lg-7">
                                    <select id="subcat" name="subcategory_id" required="" disabled=""></select>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-lg-2">
                                    <div class="left-area">
                                        <h4 class="heading">{{ __('Name') }} *</h4>
                                    </div>
                                </div>
                                <div class="col-lg-5">
                                    <input type="text" class="input-field" name="name" placeholder="{{ __('English') }}" required value="{{ old('name') }}">
                                </div>
                                <div class="col-lg-5">
                                    <input type="text" class="input-field" name="name_ar" placeholder="{{ __('Arabic') }}" required value="{{ old('name_ar') }}">
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
