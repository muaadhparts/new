@extends('layouts.operator')

@section('content')
<input type="hidden" id="headerdata" value="{{strtoupper($country->country_name)}} / {{ __('STATE TAX') }}">
            <div class="content-area">
              <div class="mr-breadcrumb">
                <div class="row">
                  <div class="col-lg-12">
                      <h4 class="heading"><u>{{ __($country->country_name) }}</u> / {{ __('Tax') }} <a class="add-btn" href="{{route('operator-country-tax')}}"><i class="fas fa-arrow-left"></i> {{ __('Back') }}</a></h4>
                      <ul class="links">
                        <li>
                          <a href="{{ route('operator.dashboard') }}">{{ __('Dashboard') }} </a>
                        </li>
                        <li>
                          <a href="javascript:;">{{ __('Country') }} </a>
                        </li>
                        <li>
                          <a href="{{route('operator-country-index')}}">{{ __('Manage Tax') }} </a>
                        </li>
                        <li>
                          <a href="javascript:;">{{ __('Tax') }}</a>
                        </li>
                        
                      </ul>
                  </div>
                </div>
              </div>
              <div class="add-catalogItem-content">
                <div class="row">
                  <div class="col-lg-12">
                    <div class="catalogItem-description">
                      <div class="body-area">
                      <div class="gocover" style="background: url({{asset('assets/images/'.$gs->admin_loader)}}) no-repeat scroll center center rgba(45, 45, 45, 0.5);"></div>
                      <form id="muaadhform" action="{{route('operator-tax-update',$country->id)}}" method="POST" enctype="multipart/form-data">
                        {{csrf_field()}}
                      @include('includes.admin.form-both') 

                      <div class="row">
                        <div class="col-lg-4">
                          <div class="left-area">
                              <h4 class="heading">{{ __('Country') }} *</h4>
                              <p class="sub-heading">{{ __('(In Any Language)') }}</p>
                          </div>
                        </div>
                        <div class="col-lg-7">
                          <input type="text" readonly class="form-control"  value="{{$country->country_name}}">
                        </div>
                      </div>

                      <div class="row">
                        <div class="col-lg-4">
                          <div class="left-area">
                              <h4 class="heading">{{ __('Tax') }} (%)  *</h4>

                          </div>
                        </div>
                        <div class="col-lg-7">
                          <input type="text" name="tax" class="form-control" placeholder="{{__('Enter Tax')}}"  value="{{$country->tax}}">
                        </div>
                      </div>
                      
                      <br>
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


@section('scripts')
    {{-- No scripts needed - state tax removed --}}
@endsection