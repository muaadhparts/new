@extends('layouts.operator')


@section('styles')

<style type="text/css">
.img-upload #image-preview {
  background-size: unset !important;
  }
</style>

@endsection

@section('content')

<div class="content-area">
              <div class="mr-breadcrumb">
                <div class="row">
                  <div class="col-lg-12">
                      <h4 class="heading">{{ __('Payment Informations') }}</h4>
                    <ul class="links">
                      <li>
                        <a href="{{ route('operator.dashboard') }}">{{ __('Dashboard') }} </a>
                      </li>
                      <li>
                        <a href="javascript:;">{{ __('Payment Settings') }}</a>
                      </li>
                      <li>
                        <a href="{{ route('operator-gs-payments') }}">{{ __('Payment Informations') }}</a>
                      </li>
                    </ul>
                  </div>
                </div>
              </div>
              <div class="add-catalogItem-content1 social-links-area">
                <div class="row">
                  <div class="col-lg-12">
                    <div class="catalogItem-description">
                      <div class="body-area">
                        <div class="gocover" style="background: url({{asset('assets/images/'.$gs->admin_loader)}}) no-repeat scroll center center rgba(45, 45, 45, 0.5);"></div>
                        <form action="{{ route('operator-gs-update-payment') }}" id="muaadhform" method="POST" enctype="multipart/form-data">
                          @csrf

                        @include('alerts.operator.form-both')


                        <div class="row add_lan_tab justify-content-center">
                          <div class="col-lg-12">
                            <nav>
                              <div class="nav nav-tabs nav-fill" id="nav-tab" role="tablist">
                                  <a class="nav-item nav-link active" id="nav-setting-tab" data-bs-toggle="tab" href="#nav-setting" role="tab"
                                  aria-controls="nav-setting" aria-selected="true">{{ __('Basic Informations') }}</a>
                                  <a class="nav-item nav-link" id="nav-merchant-tab" data-bs-toggle="tab" href="#nav-merchant" role="tab"
                                  aria-controls="nav-merchant" aria-selected="false">{{ __('Merchant Informations') }}</a>
                              </div>
                            </nav>
                            <div class="tab-content py-3 px-3 px-sm-0" id="nav-tabContent">
          
                              {{-- FRONTEND STARTS --}}
        
      
      
                              <div class="tab-pane fade show active" id="nav-setting" role="tabpanel" aria-labelledby="nav-setting-tab">
                                <div class="row justify-content-center">
                                  <div class="col-lg-4">
                                    <div class="left-area">
                                      <h4 class="heading">
                                          {{ __('Guest Checkout') }}
                                      </h4>
                                    </div>
                                  </div>
                                  <div class="col-lg-6">
                                      <div class="action-list">
                                          <select class="process select droplinks {{ $gs->guest_checkout == 1 ? 'drop-success' : 'drop-danger' }}">
                                            <option data-val="1" value="{{route('operator-gs-status',['guest_checkout',1])}}" {{ $gs->guest_checkout == 1 ? 'selected' : '' }}>{{ __('Activated') }}</option>
                                            <option data-val="0" value="{{route('operator-gs-status',['guest_checkout',0])}}" {{ $gs->guest_checkout == 0 ? 'selected' : '' }}>{{ __('Deactivated') }}</option>
                                          </select>
                                        </div>
                                  </div>
                                </div>
          
                                  <div class="row justify-content-center">
                                    <div class="col-lg-4">
                                      <div class="left-area">
                                          <h4 class="heading">{{ __('Currency Format') }} *</h4>
                                      </div>
                                    </div>
                                    <div class="col-lg-6">
                                        <select name="currency_format" required="">
                                            <option value="0" {{ $gs->currency_format == 0 ? 'selected' : '' }}>{{__('Before Price')}}</option>
                                            <option value="1" {{ $gs->currency_format == 1 ? 'selected' : '' }}>{{ __('After Price') }}</option>
                                        </select>
                                    </div>
                                  </div>
          
                                  <div class="row justify-content-center">
                                    <div class="col-lg-4">
                                      <div class="left-area">
                                          <h4 class="heading">
                                            {{ __('Decimal Separator') }} *
                                          </h4>
                                      </div>
                                    </div>
                                    <div class="col-lg-6">
                                      <select name="decimal_separator" class="form-control" required>
                                        <option value="">
                                          {{ __('Select Decimal Separator') }}
                                        </option>
                                        <option value="." {{ $gs->decimal_separator == '.' ? 'selected' : '' }}>
                                          {{ __('Dot(.)') }}
                                        </option>
                                        <option value="," {{ $gs->decimal_separator == ',' ? 'selected' : '' }}>
                                          {{ __('Comma(,)') }}
                                        </option>
                                      </select>
                                    </div>
                                  </div>

                                  <div class="row justify-content-center">
                                    <div class="col-lg-4">
                                      <div class="left-area">
                                          <h4 class="heading">
                                            {{ __('Thousand Separator') }} *
                                          </h4>
                                      </div>
                                    </div>
                                    <div class="col-lg-6">
                                      <select name="thousand_separator" class="form-control" required>
                                        <option value="">
                                          {{ __('Select Thousand Separator') }}
                                        </option>
                                        <option value="." {{ $gs->thousand_separator == '.' ? 'selected' : '' }}>
                                          {{ __('Dot(.)') }}
                                        </option>
                                        <option value="," {{ $gs->thousand_separator == ',' ? 'selected' : '' }}>
                                          {{ __('Comma(,)') }}
                                        </option>
                                      </select>
                                    </div>
                                  </div>

                                  <div class="row justify-content-center">
                                    <div class="col-lg-4">
                                      <div class="left-area">
                                          <h4 class="heading">{{ __('Withdraw Fee') }} *
                                            </h4>
                                      </div>
                                    </div>
                                    <div class="col-lg-6">
                                      <input type="text" class="form-control" placeholder="{{ __('Withdraw Fee') }}" name="withdraw_fee" value="{{ $gs->withdraw_fee }}" required="">
                                    </div>
                                  </div>
          
                                  <div class="row justify-content-center">
                                    <div class="col-lg-4">
                                      <div class="left-area">
                                          <h4 class="heading">{{ __('Withdraw Charge(%)') }} *
                                            </h4>
                                      </div>
                                    </div>
                                    <div class="col-lg-6">
                                      <input type="text" class="form-control" placeholder="{{ __('Withdraw Charge(%)') }}" name="withdraw_charge" value="{{ $gs->withdraw_charge }}" required="">
                                    </div>
                                  </div>
                              </div>
      
                              <div class="tab-pane fade show" id="nav-merchant" role="tabpanel" aria-labelledby="nav-merchant-tab">
                                <div class="row justify-content-center">
                                  <div class="col-lg-4">
                                    <div class="left-area">
                                        <h4 class="heading">{{ __('Fixed Commission') }} *
                                          </h4>
                                          <p class="sub-heading">{{ __('Fixed Commission Charge(CatalogItem Price + Commission)') }}</p>
                                          <p class="sub-heading">{{ __("(If you don't want to add any fixed commission, just set it to 0)") }}</p>
                                    </div>
                                  </div>
                                  <div class="col-lg-6">
                                    <input type="text" class="form-control" placeholder="{{ __('Fixed Commission') }}" name="fixed_commission" value="{{ $gs->fixed_commission }}" required="">
                                  </div>
                                </div>
        
        
                                <div class="row justify-content-center">
                                  <div class="col-lg-4">
                                    <div class="left-area">
                                        <h4 class="heading">{{ __('Percentage Commission(%)') }} *
                                          </h4>
                                          <p class="sub-heading">{{ __('Percentage Commission Charge(CatalogItem Price + Commission(%))') }}</p>
                                          <p class="sub-heading">{{ __("(If you don't want to add any percentage commission, just set it to 0)") }}</p>
                                    </div>
                                  </div>
                                  <div class="col-lg-6">
                                    <input type="text" class="form-control" placeholder="{{ __('Percentage Commission') }}" name="percentage_commission" value="{{ $gs->percentage_commission }}" required="">
                                  </div>
                                </div>
        
                                <div class="row justify-content-center">
                                  <div class="col-lg-4">
                                    <div class="left-area">
                                        <h4 class="heading">{{ __('Multiple Shipping') }} *
                                          </h4>
                                    </div>
                                  </div>
        
                                  <div class="col-lg-6">
                                    <label class="switch">
                                      <input type="checkbox" name="multiple_shipping" value="1" {{ $gs->multiple_shipping == 1 ? "checked":"" }}>
                                      <span class="slider round"></span>
                                    </label>
                                  </div>
                                  </div>
        
        
                                <div class="row justify-content-center">
                                  <div class="col-lg-4">
                                    <div class="left-area">
                                        <h4 class="heading">{{ __('Shipping Information For Merchant') }} *
                                          </h4>
                                    </div>
                                  </div>
        
                                  <div class="col-lg-6">
                                    <label class="switch">
                                      <input type="checkbox" name="merchant_ship_info" value="1" {{ $gs->merchant_ship_info == 1 ? "checked" : "" }}>
                                      <span class="slider round"></span>
                                    </label>
                                  </div>
                                  </div>
        
                              </div>


                            </div>
                          </div>
                        </div>



                        <div class="row justify-content-center">
                          <div class="col-lg-4">
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
