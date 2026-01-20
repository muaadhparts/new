@extends('layouts.operator')

@section('content')

<div class="content-area">
  <div class="mr-breadcrumb">
    <div class="row">

      <div class="col-lg-12">
        <h4 class="heading">{{ __('Home Page Customization') }}</h4>
        <ul class="links">
          <li>
            <a href="{{ route('operator.dashboard') }}">{{ __('Dashboard') }} </a>
          </li>
          <li>
            <a href="javascript:;">{{ __('Home Page Settings') }}</a>
          </li>
          <li>
            <a href="{{ route('operator-fs-customize') }}">{{ __('Home Page Customization') }}</a>
          </li>
        </ul>
      </div>

    </div>
  </div>

  <div class="add-catalogItem-content1">
    <div class="row">
      <div class="col-lg-12">
        <div class="catalogItem-description">
          <div class="social-links-area">
            <div class="gocover"
              style="background: url({{asset('assets/images/'.$gs->admin_loader)}}) no-repeat scroll center center rgba(45, 45, 45, 0.5);">
            </div>
            <form id="muaadhform" action="{{ route('operator-fs-homeupdate') }}" method="POST"
              enctype="multipart/form-data">
              @csrf

              @include('alerts.operator.form-both')

              <div class="row justify-content-center">

                <div class="col-lg-4 d-flex justify-content-between">
                  <label class="control-label" for="category">{{ __('Categories') }} *</label>
                  <label class="switch">
                    <input type="checkbox" name="category" value="1" {{ $data->category == 1 ? "checked" : "" }}>
                    <span class="toggle-switch round"></span>
                  </label>
                </div>

                <div class="col-lg-2"></div>

                <div class="col-lg-4 d-flex justify-content-between">
                  <label class="control-label" for="brand">{{ __('Brand') }} *</label>
                  <label class="switch">
                    <input type="checkbox" name="brand" value="1" {{ $data->brand == 1 ? "checked" : "" }}>
                    <span class="toggle-switch round"></span>
                  </label>
                </div>

              </div>

              <div class="row justify-content-center">

                <div class="col-lg-4 d-flex justify-content-between">
                  <label class="control-label" for="blog">{{ __('Blogs') }} *</label>
                  <label class="switch">
                    <input type="checkbox" name="blog" value="1" {{ $data->blog == 1 ? "checked" : "" }}>
                    <span class="toggle-switch round"></span>
                  </label>
                </div>

                <div class="col-lg-2"></div>

                <div class="col-lg-4 d-flex justify-content-between">
                  <label class="control-label" for="third_left_banner">{{ __('Newsletter') }} *</label>
                  <label class="switch">
                    <input type="checkbox" name="third_left_banner" value="1" {{ $data->third_left_banner == 1 ? "checked" : "" }}>
                    <span class="toggle-switch round"></span>
                  </label>
                </div>

              </div>

              <div class="row justify-content-center">

                <div class="col-lg-4 d-flex justify-content-between">
                  <label class="control-label" for="our_services">{{ __('Our Services') }} *</label>
                  <label class="switch">
                    <input type="checkbox" name="our_services" value="1" {{ $data->our_services == 1 ? "checked" : "" }}>
                    <span class="toggle-switch round"></span>
                  </label>
                </div>

                <div class="col-lg-2"></div>

                <div class="col-lg-4 d-flex justify-content-between">
                  <label class="control-label" for="top_brand">{{ __('Top Brand') }} *</label>
                  <label class="switch">
                    <input type="checkbox" name="top_brand" value="1" {{ $data->top_brand == 1 ? "checked" : "" }}>
                    <span class="toggle-switch round"></span>
                  </label>
                </div>

              </div>

              <div class="row">
                <div class="col-12 text-center">
                  <button type="submit" class="btn btn-primary">{{ __('Submit') }}</button>
                </div>
              </div>

            </form>

              </div>

              <br>


          </div>
        </div>
      </div>
    </div>
  </div>
</div>

@endsection
