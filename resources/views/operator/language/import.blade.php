@extends('layouts.operator')

@section('styles')

<style type="text/css">
  
textarea.input-field {
  padding: 20px 20px 0px 20px;
  border-radius: 0px;
}

</style>

@endsection

@section('content')

            <div class="content-area">
              <div class="mr-breadcrumb">
                <div class="row">
                  <div class="col-lg-12">
                      <h4 class="heading">{{ __('Import Language') }} <a class="add-btn" href="{{route('operator-lang-index')}}"><i class="fas fa-arrow-left"></i> {{ __('Back') }}</a></h4>
                      <ul class="links">
                        <li>
                          <a href="{{ route('operator.dashboard') }}">{{ __('Dashboard') }} </a>
                        </li>
                        <li><a href="javascript:;">{{ __('Language Settings') }}</a></li>
                        <li>
                          <a href="{{ route('operator-lang-index') }}">{{ __('Website Language') }}</a>
                        </li>
                        <li>
                          <a href="{{ route('operator-lang-import') }}">{{ __('Import Language') }}</a>
                        </li>
                      </ul>
                  </div>
                </div>
              </div>
              <div class="add-catalogItem-content1">
                <div class="row">
                  <div class="col-lg-12">
                    <div class="catalogItem-description">
                      <div class="body-area">
                      <div class="gocover" style="background: url({{asset('assets/images/'.$gs->admin_loader)}}) no-repeat scroll center center rgba(45, 45, 45, 0.5);"></div>
                      <form id="muaadhform" action="{{route('operator-lang-import-store')}}" method="POST" enctype="multipart/form-data">
                        {{csrf_field()}}
                      @include('alerts.operator.form-both')  

                        <div class="row">
                          <div class="col-lg-4">
                            <div class="left-area">
                                <h4 class="heading">{{ __('Language') }} *</h4>
                                <p class="sub-heading">{{ __('(In Any Language)') }}</p>
                            </div>
                          </div>
                          <div class="col-lg-7">
                            <input type="text" class="form-control" name="language" placeholder="{{ __('Language') }}" required="" value="English">
                          </div>
                        </div>
                        
                        <div class="row">
                          <div class="col-lg-4">
                            <div class="left-area">
                                <h4 class="heading">{{ __('Language Direction') }} *</h4>

                            </div>
                          </div>
                          <div class="col-lg-7">
                            <select name="rtl" class="form-control" required="">
                              <option value="0">{{ __('Left To Right') }}</option>
                              <option value="1">{{ __('Right To Left') }}</option>
                            </select>
                          </div>
                        </div>

                        <div class="row">
                            <div class="col-lg-4">
                              <div class="left-area">
                                  <h4 class="heading">{{ __('Upload File') }} *</h4>
                                  <p class="sub-heading">{{ __('(Only .csv file)') }}</p>
                              </div>
                            </div>
                            <div class="col-lg-7">
                                <input type="file" id="csvfile" name="csvfile" accept=".csv">
                            </div>
                        </div>

                        <div class="row">
                          <div class="col-lg-4">
                            <div class="left-area">
                              
                            </div>
                          </div>
                          <div class="col-lg-7">
                            <button class="btn btn-primary" type="submit">{{ __('Create Language') }}</button>
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

