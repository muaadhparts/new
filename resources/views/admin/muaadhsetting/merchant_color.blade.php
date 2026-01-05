@extends('layouts.admin')

@section('content')

<div class="content-area">
              <div class="mr-breadcrumb">
                <div class="row">
                  <div class="col-lg-12">
                      <h4 class="heading">{{ __('Merchant Store Default Background') }}</h4>
                    <ul class="links">
                      <li>
                        <a href="{{ route('admin.dashboard') }}">{{ __('Dashboard') }} </a>
                      </li>
                      <li>
                        <a href="javascript:;">{{ __('Merchants') }}</a>
                      </li>
                      <li>
                        <a href="{{ route('admin-gs-contents') }}">{{ __('Merchant Store Default Background') }}</a>
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
                        <form action="{{ route('admin-gs-update') }}" id="muaadhform" method="POST" enctype="multipart/form-data">
                          @csrf

                        @include('alerts.admin.form-both')  


                        <div class="row justify-content-center">
                          <div class="col-lg-3">
                            <div class="left-area">
                                <h4 class="heading">{{ __('Set Background') }} *</h4>
                                <small>{{__('(This background will be displayed if merchant does not set his / her store background)')}}</small>
                            </div>
                          </div>
                          <div class="col-lg-6">
                              <div class="form-group"> 
                                <div class="input-group colorpicker-component cp">
                                <input type="text" class="form-control color-field" name="merchant_color" value="{{ $gs->merchant_color }}"  class="form-control cp"  />
                                  <span class="input-group-module"><i></i></span>
                                </div>
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