@extends('layouts.operator')

@section('content')

<div class="content-area">

    <div class="mr-breadcrumb">
        <div class="row">
            <div class="col-lg-12">
                <h4 class="heading">{{ __('Blog Settings') }}</h4>
                <ul class="links">
                    <li>
                        <a href="{{ route('operator.dashboard') }}">{{ __('Dashboard') }} </a>
                    </li>
                    <li>
                        <a href="javascript:;">{{ __('Blog') }}</a>
                    </li>
                    <li>
                        <a href="javascript:;">{{ __('Blog Settings') }}</a>
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
                            style="background: url({{asset('assets/images/'.$gs->admin_loader)}}) no-repeat scroll center center rgba(45, 45, 45, 0.5);">
                        </div>
                        <form action="{{ route('operator-gs-update') }}" id="muaadhform" method="POST"
                            enctype="multipart/form-data">
                            @csrf

                            @include('alerts.operator.form-both')

                            <div class="row justify-content-center">
                                <div class="col-lg-3">
                                    <div class="left-area">
                                        <h4 class="heading">{{ __('Display Posts Per Page') }} *
                                        </h4>
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <input type="number" class="form-control" placeholder="{{ __('Display Posts Per Page') }}" name="page_count"
                                    value="{{ $gs->page_count }}" required="" min="0">
                                </div>
                            </div>

                            <div class="row justify-content-center">
                                <div class="col-lg-3">
                                    <div class="left-area">

                                    </div>
                                </div>
                                <div class="col-lg-6">
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
