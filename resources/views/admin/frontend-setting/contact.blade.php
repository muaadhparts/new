@extends('layouts.admin')

@section('content')

<div class="content-area">
              <div class="mr-breadcrumb">
                <div class="row">
                  <div class="col-lg-12">
                      <h4 class="heading">{{ __('Contact Us') }}</h4>
                    <ul class="links">
                      <li>
                        <a href="{{ route('admin.dashboard') }}">{{ __('Dashboard') }} </a>
                      </li>
                      <li>
                        <a href="javascript:;">{{ __('Menu Page Settings') }}</a>
                      </li>
                      <li>
                        <a href="{{ route('admin-fs-contact') }}">{{ __('Contact Us Page') }}</a>
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
                        <form id="muaadhform" action="{{ route('admin-fs-update') }}" method="POST" enctype="multipart/form-data">
                          @csrf

                        @include('alerts.admin.form-both')  

                          <div class="row justify-content-center">
                              <div class="col-lg-3">
                                <div class="left-area">
                                    <h4 class="heading">{{ __('Email') }} *
                                      </h4>
                                </div>
                              </div>
                              <div class="col-lg-6">
                                <input type="email" class="form-control" placeholder="{{ __('Enter Email') }}" name="email" value="{{ $data->email }}">
                              </div>
                            </div>
    
                            <div class="row justify-content-center">
                              <div class="col-lg-3">
                                <div class="left-area">
                                    <h4 class="heading">{{ __('Website') }} *
                                      </h4>
                                </div>
                              </div>
                              <div class="col-lg-6">
                                <input type="text" class="form-control" placeholder="{{ __('Enter Website') }}" name="site" value="{{ $data->site }}">
                              </div>
                            </div>
    
                            <div class="row justify-content-center">
                              <div class="col-lg-3">
                                <div class="left-area">
                                    <h4 class="heading">{{ __('Phone') }} *
                                      </h4>
                                </div>
                              </div>
                              <div class="col-lg-6">
                                <input type="text" class="form-control" placeholder="{{ __('Enter Phone') }}" name="phone" value="{{ $data->phone }}">
                              </div>
                            </div>
    
                            <div class="row justify-content-center">
                              <div class="col-lg-3">
                                <div class="left-area">
                                    <h4 class="heading">{{ __('Fax') }} *
                                      </h4>
                                </div>
                              </div>
                              <div class="col-lg-6">
                                <input type="text" class="form-control" placeholder="{{ __('Enter Fax') }}" name="fax" value="{{ $data->fax }}">
                              </div>
                            </div>

                            <div class="row justify-content-center">
                                <div class="col-lg-3">
                                  <div class="left-area">
                                    <h4 class="heading">
                                        {{ __('Street Address') }} *
                                    </h4>
                                  </div>
                                </div>
                                <div class="col-lg-6">
                                      <textarea name="street" class="form-control" placeholder="Enter Street Address"> {{ $data->street }} </textarea>
                                </div>
                              </div>

                          <div class="row justify-content-center">
                              <div class="col-lg-3">
                                <div class="left-area">
                                  <h4 class="heading">
                                      {{ __('Contact Us Email Address') }} *
                                  </h4>
                                </div>
                              </div>
                              <div class="col-lg-6">
                                  <div class="tawk-area">
                                    <textarea name="contact_email"> {{ $data->contact_email }} </textarea>
                                  </div>
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