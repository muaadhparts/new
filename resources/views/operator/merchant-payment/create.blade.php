@extends('layouts.load')

@section('content')

            <div class="content-area">

              <div class="add-catalogItem-content1">
                <div class="row">
                  <div class="col-lg-12">
                    <div class="catalogItem-description">
                      <div class="body-area">
                        @include('alerts.operator.form-error') 
                      <form id="muaadhformdata" action="{{route('operator-merchant-payment-store')}}" method="POST" enctype="multipart/form-data">
                        {{csrf_field()}}

                        <div class="row">
                          <div class="col-lg-4">
                            <div class="left-area">
                                <h4 class="heading">{{ __('Name') }} *</h4>
                                <p class="sub-heading">{{ __('(In Any Language)') }}</p>
                            </div>
                          </div>
                          <div class="col-lg-7">
                            <input type="text" class="form-control" name="name" placeholder="{{ __('Name') }}" required="" value="">
                          </div>
                        </div>


                        <div class="row">
                          <div class="col-lg-4">
                            <div class="left-area">
                                <h4 class="heading">{{ __('Subname') }} *</h4>
                                <p class="sub-heading">{{ __('(Optional)') }}</p>
                            </div>
                          </div>
                          <div class="col-lg-7">

                              <input type="text" class="form-control" name="subname" placeholder="{{ __('Subname') }}" value="">

                          </div>
                        </div>

                        <div class="row">
                          <div class="col-lg-4">
                            <div class="left-area">
                              <h4 class="heading">
                                  {{ __('Description') }} *
                              </h4>
                            </div>
                          </div>
                          <div class="col-lg-7">
                              <textarea  class="nic-edit" name="details" placeholder="{{ __('Description') }}"></textarea> 
                          </div>
                        </div>



                        <div class="row">
                          <div class="col-lg-4">
                            <div class="left-area">
                              
                            </div>
                          </div>
                          <div class="col-lg-7">
                            <button class="btn btn-primary" type="submit">{{ __('Create Merchant Payment') }}</button>
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
