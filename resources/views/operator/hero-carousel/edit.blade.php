@extends('layouts.operator')

@section('content')
    <div class="content-area">
        <div class="mr-breadcrumb">
            <div class="row">
                <div class="col-lg-12">
                    <h4 class="heading">{{ __('Edit Hero Carousel') }} <a class="add-btn" href="{{ route('operator-hero-carousel-index') }}"><i
                                class="fas fa-arrow-left"></i> {{ __('Back') }}</a></h4>
                    <ul class="links">
                        <li>
                            <a href="{{ route('operator.dashboard') }}">{{ __('Dashboard') }} </a>
                        </li>
                        <li>
                            <a href="javascript:;">{{ __('Home Page Settings') }}</a>
                        </li>
                        <li>
                            <a href="{{ route('operator-hero-carousel-index') }}">{{ __('Hero Carousels') }}</a>
                        </li>
                        <li>
                            <a href="{{ route('operator-hero-carousel-edit', $data->id) }}">{{ __('Edit') }}</a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="add-catalogItem-content1 add-catalogItem-content2">
            <div class="row">
                <div class="col-lg-12">
                    <div class="catalogItem-description">
                        <div class="body-area">
                            <div class="gocover"
                                style="background: url({{ asset('assets/images/' . $gs->admin_loader) }}) no-repeat scroll center center rgba(45, 45, 45, 0.5);">
                            </div>
                            <form id="muaadhform" action="{{ route('operator-hero-carousel-update', $data->id) }}" method="POST"
                                enctype="multipart/form-data">
                                {{ csrf_field() }}
                                @include('alerts.operator.form-both')


                                {{-- Sub Name Section --}}

                                <div class="panel panel-default slider-panel">
                                    <div class="panel-heading text-center">
                                        <h3>{{ __('Sub Name') }}</h3>
                                    </div>
                                    <div class="panel-body">
                                        <div class="form-group">
                                            <div class="col-sm-12">
                                                <label class="control-label"
                                                    for="subname_text">{{ __('Text') }}*</label>

                                                <textarea class="form-control" name="subname_text" id="subname_text" rows="5"
                                                    placeholder="{{ __('Enter Name Text') }}">{{ $data->subname_text }}</textarea>
                                            </div>
                                        </div>


                                        <div class="form-group">
                                            <div class="col-sm-12">
                                                <div class="row">

                                                    <div class="col-sm-4">
                                                        <label class="control-label"
                                                            for="subname_color">{{ __('Font Color') }} *</label>
                                                        <div class="input-group colorpicker-component cp">
                                                            <input type="text" name="subname_color"
                                                                value="{{ $data->subname_color }}"
                                                                class="form-control cp" />
                                                            <span class="input-group-module"><i></i></span>
                                                        </div>

                                                    </div>

                                                </div>

                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- Sub Name Section Ends --}}

                                {{-- Name Section --}}

                                <div class="panel panel-default slider-panel">
                                    <div class="panel-heading text-center">
                                        <h3>{{ __('Name') }}</h3>
                                    </div>
                                    <div class="panel-body">
                                        <div class="form-group">
                                            <div class="col-sm-12">
                                                <label class="control-label" for="name_text">{{ __('Text') }}*</label>

                                                <textarea class="form-control" name="name_text" id="name_text" rows="5"
                                                    placeholder="{{ __('Enter Name Text') }}">{{ $data->name_text }}</textarea>
                                            </div>
                                        </div>


                                        <div class="form-group">
                                            <div class="col-sm-12">
                                                <div class="row">

                                                    <div class="col-sm-4">
                                                        <label class="control-label"
                                                            for="name_color">{{ __('Font Color') }} *</label>
                                                        <div class="input-group colorpicker-component cp">
                                                            <input type="text" name="name_color"
                                                                value="{{ $data->name_color }}" class="form-control cp" />
                                                            <span class="input-group-module"><i></i></span>
                                                        </div>
                                                    </div>

                                                </div>

                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- Name Section Ends --}}


                                {{-- Details Section --}}

                                <div class="panel panel-default slider-panel">
                                    <div class="panel-heading text-center">
                                        <h3>{{ __('Description') }}</h3>
                                    </div>
                                    <div class="panel-body">
                                        <div class="form-group">
                                            <div class="col-sm-12">
                                                <label class="control-label"
                                                    for="details_text">{{ __('Text') }}*</label>

                                                <textarea class="form-control" name="details_text" id="details_text" rows="5" placeholder="Enter Name Text">{{ $data->details_text }}</textarea>
                                            </div>
                                        </div>


                                        <div class="form-group">
                                            <div class="col-sm-12">
                                                <div class="row">

                                                    <div class="col-sm-4">
                                                        <label class="control-label"
                                                            for="details_color">{{ __('Font Color') }} *</label>
                                                        <div class="input-group colorpicker-component cp">
                                                            <input type="text" name="details_color"
                                                                value="{{ $data->details_color }}"
                                                                class="form-control cp" />
                                                            <span class="input-group-module"><i></i></span>
                                                        </div>

                                                    </div>

                                                </div>

                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- Name Section Ends --}}


                                <div class="row">
                                    <div class="col-lg-4">
                                        <div class="left-area">
                                            <h4 class="heading">{{ __('Current Featured Image') }} *</h4>
                                        </div>
                                    </div>
                                    <div class="col-lg-7">
                                        <div class="img-upload full-width-img">
                                            <div id="image-preview" class="img-preview"
                                                style="background: url({{ $data->photo ? asset('assets/images/sliders/' . $data->photo) : asset('assets/images/noimage.png') }});">
                                                <label for="image-upload" class="img-label" id="image-label"><i
                                                        class="icofont-upload-alt"></i>{{ __('Upload Image') }}</label>
                                                <input type="file" name="photo" class="img-upload"
                                                    id="image-upload">
                                            </div>
                                            <p class="text">{{ __('Prefered Size: (1920x800) or Square Sized Image') }}
                                            </p>
                                        </div>

                                    </div>
                                </div>


                                <div class="row">
                                    <div class="col-lg-4">
                                        <div class="left-area">
                                            <h4 class="heading">{{ __('Link') }} *</h4>
                                        </div>
                                    </div>
                                    <div class="col-lg-7">
                                        <input type="text" class="form-control" name="link" placeholder="Link"
                                            required="" value="{{ $data->link }}">

                                    </div>
                                </div>


                                <div class="row">
                                    <div class="col-lg-4">
                                        <div class="left-area">

                                        </div>
                                    </div>
                                    <div class="col-lg-7">
                                        <button class="btn btn-primary" type="submit">{{ __('Save') }}</button>
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
