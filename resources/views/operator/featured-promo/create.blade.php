@extends('layouts.operator')

@section('content')

            <div class="content-area">
              <div class="mr-breadcrumb">
                <div class="row">
                  <div class="col-lg-12">
                      <h4 class="heading">{{ __('Add Best Month Offer') }} <a class="add-btn" href="{{route('operator-featured-promo-index')}}"><i class="fas fa-arrow-left"></i> {{ __('Back') }}</a></h4>
                      <ul class="links">
                        <li>
                          <a href="{{ route('operator.dashboard') }}">{{ __('Dashboard') }} </a>
                        </li>
                        <li>
                          <a href="javascript:;">{{ __('Home Page Settings') }}</a>
                        </li>
                        <li>
                          <a href="{{ route('operator-featured-promo-index') }}">{{ __('Best Month Offer') }}</a>
                        </li>
                        <li>
                          <a href="{{ route('operator-featured-promo-create') }}">{{ __('Add Best Month Offer') }}</a>
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
                      <div class="gocover" style="background: url({{asset('assets/images/'.$gs->admin_loader)}}) no-repeat scroll center center rgba(45, 45, 45, 0.5);"></div>
                      <form id="muaadhform" action="{{route('operator-featured-promo-store')}}" method="POST" enctype="multipart/form-data">
                        {{csrf_field()}}
                      @include('alerts.operator.form-both')
                  

                    

                        <div class="row">
                          <div class="col-lg-4">
                            <div class="left-area">
                                <h4 class="heading">{{ __('Url') }} *</h4>
                            </div>
                          </div>
                          <div class="col-lg-7">
                            <input type="text" class="form-control" name="url" placeholder="{{ __('Add Url here') }}" required="" value="">
                          </div>
                        </div>

                   
                      {{-- Sub Title Section Ends--}}
                        <div class="row mt-5">
                          <div class="col-lg-4">
                            <div class="left-area">
                                <h4 class="heading">{{ __('Current Featured Image') }} *</h4>
                            </div>
                          </div>
                          <div class="col-lg-7">
                            <div class="img-upload full-width-img">
                                <div id="image-preview" class="img-preview" style="background: url({{ asset('assets/operator/images/upload.png') }});">
                                    <label for="image-upload" class="img-label" id="image-label"><i class="icofont-upload-alt"></i>{{ __('Upload Image') }}</label>
                                    <input type="file" name="photo" class="img-upload" id="image-upload">
                                  </div>
                                  <p class="text">{{ __('Prefered Size: (600x770) or Square Sized Image') }}</p>
                            </div>
                          </div>
                        </div>

                        <div class="row">
                          <div class="col-lg-4">
                            <div class="left-area">

                            </div>
                          </div>
                          <div class="col-lg-7">
                            <button class="btn btn-primary" type="submit">{{ __('Create') }}</button>
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
