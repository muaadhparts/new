@extends('layouts.operator')

@section('content')

<div class="content-area">
              <div class="mr-breadcrumb">
                <div class="row">
                  <div class="col-lg-12">
                      <h4 class="heading">{{ __('Google Analytics') }}</h4>
                    <ul class="links">
                      <li>
                        <a href="{{ route('operator.dashboard') }}">{{ __('Dashboard') }} </a>
                      </li>
                      <li>
                        <a href="javascript:;">{{ __('SEO Tools') }}</a>
                      </li>
                      <li>
                        <a href="{{ route('operator-seotool-analytics') }}">{{ __('Google Analytics') }}</a>
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
                        <form id="muaadhform" action="{{ route('operator-seotool-analytics-update') }}" method="POST" enctype="multipart/form-data">
                          {{csrf_field()}}
                        @include('alerts.operator.form-both')  

                        {{-- Google Tag Manager (Recommended) --}}
                        <div class="row justify-content-center">
                          <div class="col-lg-3">
                            <div class="left-area">
                              <h4 class="heading">
                                  {{ __('Google Tag Manager ID') }}
                              </h4>
                              <p class="sub-heading">{{ __('Recommended - e.g. GTM-XXXXXXX') }}</p>
                            </div>
                          </div>
                          <div class="col-lg-6">
                              <div class="tawk-area">
                                <input class="form-control" name="gtm_id" type="text" placeholder="GTM-XXXXXXX" value="{{ $tool->gtm_id ?? '' }}">
                                <small class="text-muted">{{ __('If GTM ID is set, Google Analytics below will be ignored (configure GA inside GTM instead)') }}</small>
                              </div>
                          </div>
                        </div>

                        <hr class="my-4">

                        {{-- Google Analytics (Legacy) --}}
                        <div class="row justify-content-center">
                          <div class="col-lg-3">
                            <div class="left-area">
                              <h4 class="heading">
                                  {{ __('Google Analytics ID') }}
                              </h4>
                              <p class="sub-heading">{{ __('Legacy - use GTM instead') }}</p>
                            </div>
                          </div>
                          <div class="col-lg-6">
                              <div class="tawk-area">
                                <input class="form-control" name="google_analytics" type="text" placeholder="{{ __('Google Analytics ID') }} " value="{{ $tool->google_analytics }}">
                              </div>
                          </div>
                        </div>

                        <div class="row justify-content-center">
                            <div class="col-lg-3">
                              <div class="left-area">
                                <h4 class="heading">
                                    {{ __('Facebook Pixel ID') }}
                                </h4>
                              </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="tawk-area">
                                  <input class="form-control" name="facebook_pixel" type="text" placeholder="{{ __('Facebook Pixel ID') }} " value="{{ $tool->facebook_pixel }}">
                                </div>
                            </div>
                          </div>

                        <hr class="my-4">
                        <h5 class="text-center mb-4">{{ __('Search Engine Verification') }}</h5>

                        {{-- Google Search Console --}}
                        <div class="row justify-content-center">
                            <div class="col-lg-3">
                              <div class="left-area">
                                <h4 class="heading">
                                    {{ __('Google Search Console') }}
                                </h4>
                                <p class="sub-heading">{{ __('Verification code only') }}</p>
                              </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="tawk-area">
                                  <input class="form-control" name="search_console_verification" type="text" placeholder="google-site-verification=XXXXX" value="{{ $tool->search_console_verification ?? '' }}">
                                  <small class="text-muted">{{ __('Get from Google Search Console > Settings > Ownership verification') }}</small>
                                </div>
                            </div>
                        </div>

                        {{-- Bing Webmaster --}}
                        <div class="row justify-content-center">
                            <div class="col-lg-3">
                              <div class="left-area">
                                <h4 class="heading">
                                    {{ __('Bing Webmaster') }}
                                </h4>
                                <p class="sub-heading">{{ __('Verification code only') }}</p>
                              </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="tawk-area">
                                  <input class="form-control" name="bing_verification" type="text" placeholder="XXXXXXXXXXXXXXXXXXXXXXXX" value="{{ $tool->bing_verification ?? '' }}">
                                  <small class="text-muted">{{ __('Get from Bing Webmaster Tools') }}</small>
                                </div>
                            </div>
                        </div>

                        <div class="row justify-content-center mt-4">
                          <div class="col-lg-3">
                            <div class="left-area">

                            </div>
                          </div>
                          <div class="col-lg-6">
                            <button class="btn btn-primary" type="submit">{{ __('Save') }}</button>
                          </div>
                        </div>
                      </div>
                     </form>
                    </div>
                  </div>
                </div>
              </div>
            </div>

@endsection