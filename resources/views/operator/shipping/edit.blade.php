@extends('layouts.load')

@section('content')
            <div class="content-area">

              <div class="add-catalogItem-content1">
                <div class="row">
                  <div class="col-lg-12">
                    <div class="catalogItem-description">
                      <div class="body-area">
                        @include('alerts.operator.form-error') 
                      <form id="muaadhformdata" action="{{route('operator-shipping-update',$data->id)}}" method="POST" enctype="multipart/form-data">
                        {{csrf_field()}}


                        <div class="row">
                          <div class="col-lg-4">
                            <div class="left-area">
                                <h4 class="heading">{{ __('Title') }} *</h4>
                                <p class="sub-heading">{{ __('(In Any Language)') }}</p>
                            </div>
                          </div>
                          <div class="col-lg-7">
                            <input type="text" class="form-control" name="title" placeholder="{{ __('Title') }}" required="" value="{{ $data->title }}">
                          </div>
                        </div>

                        <div class="row">
                          <div class="col-lg-4">
                            <div class="left-area">
                                <h4 class="heading">{{ __('Duration') }} *</h4>
                                <p class="sub-heading">{{ __('(In Any Language)') }}</p>
                            </div>
                          </div>
                          <div class="col-lg-7">
                            <input type="text" class="form-control" name="subtitle" placeholder="{{ __('Duration') }}" required="" value="{{ $data->subtitle }}">
                          </div>
                        </div>

                        <div class="row">
                          <div class="col-lg-4">
                            <div class="left-area">
                                <h4 class="heading">{{ __('Price') }} *</h4>
                                <p class="sub-heading">({{ __('In') }} {{ $sign->name }})</p>
                            </div>
                          </div>
                          <div class="col-lg-7">
                            <input type="number" class="form-control" name="price" placeholder="{{ __('Price') }}" required="" value="{{ $data->price * $sign->value }}" min="0" step="0.01">
                          </div>
                        </div>

                        <div class="row">
                          <div class="col-lg-4">
                            <div class="left-area">
                                <h4 class="heading">{{ __('Provider') }}</h4>
                                <p class="sub-heading">{{ __('Shipping Company Name') }}</p>
                            </div>
                          </div>
                          <div class="col-lg-7">
                            <input type="text" class="form-control" name="provider" placeholder="{{ __('e.g., Aramex, SMSA, DHL') }}" value="{{ $data->provider ?? '' }}">
                          </div>
                        </div>

                        <div class="row">
                          <div class="col-lg-4">
                            <div class="left-area">
                                <h4 class="heading">{{ __('Integration Type') }}</h4>
                            </div>
                          </div>
                          <div class="col-lg-7">
                            <select class="form-control" name="integration_type">
                                <option value="manual" {{ ($data->integration_type ?? 'manual') === 'manual' ? 'selected' : '' }}>{{ __('Manual') }}</option>
                                <option value="api" {{ ($data->integration_type ?? '') === 'api' ? 'selected' : '' }}>{{ __('API') }}</option>
                                <option value="none" {{ ($data->integration_type ?? '') === 'none' ? 'selected' : '' }}>{{ __('Free / None') }}</option>
                            </select>
                          </div>
                        </div>

                        <div class="row">
                          <div class="col-lg-4">
                            <div class="left-area">
                                <h4 class="heading">{{ __('Free Shipping Above') }}</h4>
                                <p class="sub-heading">({{ __('In') }} {{ $sign->name }})</p>
                            </div>
                          </div>
                          <div class="col-lg-7">
                            <input type="number" class="form-control" name="free_above" placeholder="{{ __('Free shipping if cart total above this amount') }}" value="{{ ($data->free_above ?? 0) * $sign->value }}" min="0" step="0.01">
                            <small class="text-muted">{{ __('Leave empty or 0 to disable free shipping') }}</small>
                          </div>
                        </div>

                        <div class="row">
                          <div class="col-lg-4">
                            <div class="left-area">
                                <h4 class="heading">{{ __('Status') }}</h4>
                            </div>
                          </div>
                          <div class="col-lg-7">
                            <select class="form-control" name="status">
                                <option value="1" {{ ($data->status ?? 1) == 1 ? 'selected' : '' }}>{{ __('Active') }}</option>
                                <option value="0" {{ ($data->status ?? 1) == 0 ? 'selected' : '' }}>{{ __('Inactive') }}</option>
                            </select>
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
