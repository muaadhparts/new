@extends('layouts.operator')

@section('content')

            <div class="content-area">
                <div class="mr-breadcrumb">
                    <div class="row">
                      <div class="col-lg-12">
                          <h4 class="heading">{{ __('Add Role') }} <a class="add-btn" href="{{route('operator-role-index')}}"><i class="fas fa-arrow-left"></i> {{ __('Back') }}</a></h4>
                          <ul class="links">
                            <li>
                              <a href="{{ route('operator.dashboard') }}">{{ __('Dashboard') }} </a>
                            </li>
                            <li>
                              <a href="{{ route('operator-role-index') }}">{{ __('Manage Roles') }}</a>
                            </li>
                            <li>
                              <a href="{{ route('operator-role-create') }}">{{ __('Add Role') }}</a>
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
                      <form id="muaadhform" action="{{route('operator-role-create')}}" method="POST" enctype="multipart/form-data">
                        {{csrf_field()}}
                      @include('alerts.operator.form-both') 

                        <div class="row">
                          <div class="col-lg-2">
                            <div class="left-area">
                                <h4 class="heading">{{ __("Name") }} *</h4>
                                <p class="sub-heading">{{ __("(In Any Language)") }}</p>
                            </div>
                          </div>
                          <div class="col-lg-10">
                            <input type="text" class="form-control" name="name" placeholder="{{ __('Name') }}" required="" value="">
                          </div>
                        </div>

                        <hr>
                        <h5 class="text-center">{{ __('Permissions') }}</h5>
                        <hr>

                        <div class="row justify-content-center">

                            <div class="col-lg-4 d-flex justify-content-between">
                              <label class="control-label">{{ __('Purchases') }} *</label>
                              <label class="switch">
                                <input type="checkbox" name="section[]" value="purchases">
                                <span class="toggle-switch round"></span>
                              </label>
                            </div>

                            <div class="col-lg-2"></div>

                            <div class="col-lg-4 d-flex justify-content-between">
                              <label class="control-label">{{ __('Manage Categories') }} *</label>
                              <label class="switch">
                                <input type="checkbox" name="section[]" value="categories">
                                <span class="toggle-switch round"></span>
                              </label>
                            </div>

                        </div>

                        <div class="row justify-content-center">

                          <div class="col-lg-4 d-flex justify-content-between">
                            <label class="control-label">{{ __('Manage country') }} *</label>
                            <label class="switch">
                              <input type="checkbox" name="section[]" value="manage-country">
                              <span class="toggle-switch round"></span>
                            </label>
                          </div>

                          <div class="col-lg-2"></div>

                          <div class="col-lg-4 d-flex justify-content-between">
                            <label class="control-label">{{ __('Tax Calculate') }} *</label>
                            <label class="switch">
                              <input type="checkbox" name="section[]" value="earning">
                              <span class="toggle-switch round"></span>
                            </label>
                          </div>
                      </div>
                      
                        <div class="row justify-content-center">

                          <div class="col-lg-4 d-flex justify-content-between">
                            <label class="control-label">{{ __('Catalog Items') }} *</label>
                            <label class="switch">
                              <input type="checkbox" name="section[]" value="catalogItems">
                              <span class="toggle-switch round"></span>
                            </label>
                          </div>

                          <div class="col-lg-2"></div>

                          <div class="col-lg-4 d-flex justify-content-between">
                            <label class="control-label">{{ __('Affiliate Catalog Items') }} *</label>
                            <label class="switch">
                              <input type="checkbox" name="section[]" value="affilate_products">
                              <span class="toggle-switch round"></span>
                            </label>
                          </div>

                      </div>

                      <div class="row justify-content-center">

                        <div class="col-lg-4 d-flex justify-content-between">
                          <label class="control-label">{{ __('Bulk CatalogItem Upload') }} *</label>
                          <label class="switch">
                            <input type="checkbox" name="section[]" value="bulk_product_upload">
                            <span class="toggle-switch round"></span>
                          </label>
                        </div>

                        <div class="col-lg-2"></div>

                        <div class="col-lg-4 d-flex justify-content-between">
                          <label class="control-label">{{ __('CatalogItem Discussion') }} *</label>
                          <label class="switch">
                            <input type="checkbox" name="section[]" value="product_discussion">
                            <span class="toggle-switch round"></span>
                          </label>
                        </div>

                      </div>

                      <div class="row justify-content-center">

                        <div class="col-lg-4 d-flex justify-content-between">
                          <label class="control-label">{{ __('Customers') }} *</label>
                          <label class="switch">
                            <input type="checkbox" name="section[]" value="customers">
                            <span class="toggle-switch round"></span>
                          </label>
                        </div>

                      </div>

                      <div class="row justify-content-center">

                        <div class="col-lg-4 d-flex justify-content-between">
                          <label class="control-label">{{ __('Customer Top Ups') }} *</label>
                          <label class="switch">
                            <input type="checkbox" name="section[]" value="customer_topups">
                            <span class="toggle-switch round"></span>
                          </label>
                        </div>

                        <div class="col-lg-2"></div>

                        <div class="col-lg-4 d-flex justify-content-between">
                          <label class="control-label">{{ __('Merchants') }} *</label>
                          <label class="switch">
                            <input type="checkbox" name="section[]" value="merchants">
                            <span class="toggle-switch round"></span>
                          </label>
                        </div>

                      </div>

                      <div class="row justify-content-center">

                        <div class="col-lg-4 d-flex justify-content-between">
                          <label class="control-label">{{ __('Merchant Subscriptions') }} *</label>
                          <label class="switch">
                            <input type="checkbox" name="section[]" value="merchant_subscriptions">
                            <span class="toggle-switch round"></span>
                          </label>
                        </div>

                        <div class="col-lg-2"></div>

                        <div class="col-lg-4 d-flex justify-content-between">
                          <label class="control-label">{{ __('Merchant Verifications') }} *</label>
                          <label class="switch">
                            <input type="checkbox" name="section[]" value="merchant_verifications">
                            <span class="toggle-switch round"></span>
                          </label>
                        </div>

                      </div>

                      <div class="row justify-content-center">

                        <div class="col-lg-4 d-flex justify-content-between">
                          <label class="control-label">{{ __('Merchant Subscription Plans') }} *</label>
                          <label class="switch">
                            <input type="checkbox" name="section[]" value="merchant_subscription_plans">
                            <span class="toggle-switch round"></span>
                          </label>
                        </div>

                        <div class="col-lg-2"></div>

                        <div class="col-lg-4 d-flex justify-content-between">
                          <label class="control-label">{{ __('Messages') }} *</label>
                          <label class="switch">
                            <input type="checkbox" name="section[]" value="chat_entries">
                            <span class="toggle-switch round"></span>
                          </label>
                        </div>

                      </div>

                      <div class="row justify-content-center">

                        <div class="col-lg-4 d-flex justify-content-between">
                          <label class="control-label">{{ __('General Settings') }} *</label>
                          <label class="switch">
                            <input type="checkbox" name="section[]" value="muaadh_settings">
                            <span class="toggle-switch round"></span>
                          </label>
                        </div>

                        <div class="col-lg-2"></div>

                        <div class="col-lg-4 d-flex justify-content-between">
                          <label class="control-label">{{ __('Home Page Settings') }} *</label>
                          <label class="switch">
                            <input type="checkbox" name="section[]" value="home_page_settings">
                            <span class="toggle-switch round"></span>
                          </label>
                        </div>

                      </div>

                      <div class="row justify-content-center">

                        <div class="col-lg-4 d-flex justify-content-between">
                          <label class="control-label">{{ __('Menu Page Settings') }} *</label>
                          <label class="switch">
                            <input type="checkbox" name="section[]" value="menu_page_settings">
                            <span class="toggle-switch round"></span>
                          </label>
                        </div>

                        <div class="col-lg-2"></div>

                        <div class="col-lg-4 d-flex justify-content-between">
                          <label class="control-label">{{ __('Email Settings') }} *</label>
                          <label class="switch">
                            <input type="checkbox" name="section[]" value="emails_settings">
                            <span class="toggle-switch round"></span>
                          </label>
                        </div>

                      </div>

                      <div class="row justify-content-center">

                        <div class="col-lg-4 d-flex justify-content-between">
                          <label class="control-label">{{ __('Payment Settings') }} *</label>
                          <label class="switch">
                            <input type="checkbox" name="section[]" value="payment_settings">
                            <span class="toggle-switch round"></span>
                          </label>
                        </div>

                        <div class="col-lg-2"></div>

                        <div class="col-lg-4 d-flex justify-content-between">
                          <label class="control-label">{{ __('Social Settings') }} *</label>
                          <label class="switch">
                            <input type="checkbox" name="section[]" value="social_settings">
                            <span class="toggle-switch round"></span>
                          </label>
                        </div>

                      </div>

                      <div class="row justify-content-center">

                        <div class="col-lg-4 d-flex justify-content-between">
                          <label class="control-label">{{ __('Language Settings') }} *</label>
                          <label class="switch">
                            <input type="checkbox" name="section[]" value="language_settings">
                            <span class="toggle-switch round"></span>
                          </label>
                        </div>

                        <div class="col-lg-2"></div>

                        <div class="col-lg-4 d-flex justify-content-between">
                          <label class="control-label">{{ __('SEO Tools') }} *</label>
                          <label class="switch">
                            <input type="checkbox" name="section[]" value="seo_tools">
                            <span class="toggle-switch round"></span>
                          </label>
                        </div>

                      </div>

                      <div class="row justify-content-center">

                        <div class="col-lg-4 d-flex justify-content-between">
                          <label class="control-label">{{ __('Manage Staffs') }} *</label>
                          <label class="switch">
                            <input type="checkbox" name="section[]" value="manage_staffs">
                            <span class="toggle-switch round"></span>
                          </label>
                        </div>

                        <div class="col-lg-2"></div>

                        <div class="col-lg-4 d-flex justify-content-between">
                          <label class="control-label">{{ __('Subscribers') }} *</label>
                          <label class="switch">
                            <input type="checkbox" name="section[]" value="subscribers">
                            <span class="toggle-switch round"></span>
                          </label>
                        </div>

                      </div>


                        <div class="row">
                          <div class="col-lg-5">
                            <div class="left-area">
                              
                            </div>
                          </div>
                          <div class="col-lg-7">
                            <button class="btn btn-primary" type="submit">{{ __('Create Role') }}</button>
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