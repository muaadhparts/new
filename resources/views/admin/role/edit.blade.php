@extends('layouts.admin')

@section('content')
            <div class="content-area">
                <div class="mr-breadcrumb">
                    <div class="row">
                      <div class="col-lg-12">
                          <h4 class="heading">{{ __('Edit Role') }} <a class="add-btn" href="{{route('admin-role-index')}}"><i class="fas fa-arrow-left"></i> {{ __('Back') }}</a></h4>
                          <ul class="links">
                            <li>
                              <a href="{{ route('admin.dashboard') }}">{{ __('Dashboard') }} </a>
                            </li>
                            <li>
                              <a href="{{ route('admin-role-index') }}">{{ __('Manage Roles') }}</a>
                            </li>
                            <li>
                              <a href="{{ route('admin-role-edit',$data->id) }}">{{ __('Edit Role') }}</a>
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
                      <form id="muaadhform" action="{{route('admin-role-update',$data->id)}}" method="POST" enctype="multipart/form-data">
                          {{csrf_field()}}
                          @include('alerts.admin.form-both') 

                        <div class="row">
                          <div class="col-lg-4">
                            <div class="left-area">
                                <h4 class="heading">{{ __("Name") }} *</h4>
                                <p class="sub-heading">{{ __('(In Any Language)') }}</p>
                            </div>
                          </div>
                          <div class="col-lg-7">
                            <input type="text" class="form-control" name="name" placeholder="{{ __('Name') }}" value="{{$data->name}}" required="">
                          </div>
                        </div>


                        <hr>
                        <h5 class="text-center">{{ __('Permissions') }}</h5>
                        <hr>

                        <div class="row justify-content-center">

                          <div class="col-lg-4 d-flex justify-content-between">
                            <label class="control-label">{{ __('Purchases') }} *</label>
                            <label class="switch">
                              <input type="checkbox" name="section[]" value="purchases" {{ $data->sectionCheck('purchases') ? 'checked' : '' }}>
                              <span class="slider round"></span>
                            </label>
                          </div>

                          <div class="col-lg-2"></div>

                          <div class="col-lg-4 d-flex justify-content-between">
                            <label class="control-label">{{ __('Manage Categories') }} *</label>
                            <label class="switch">
                              <input type="checkbox" name="section[]" value="categories" {{ $data->sectionCheck('categories') ? 'checked' : '' }}>
                              <span class="slider round"></span>
                            </label>
                          </div>

                      </div>


                      <div class="row justify-content-center">

                        <div class="col-lg-4 d-flex justify-content-between">
                          <label class="control-label">{{ __('Manage country') }} *</label>
                          <label class="switch">
                            <input type="checkbox" name="section[]" value="manage-country"  {{ $data->sectionCheck('manage-country') ? 'checked' : '' }}>
                            <span class="slider round"></span>
                          </label>
                        </div>

                        <div class="col-lg-2"></div>

                        <div class="col-lg-4 d-flex justify-content-between">
                          <label class="control-label">{{ __('Tax Calculate') }} *</label>
                          <label class="switch">
                            <input type="checkbox" name="section[]" value="earning" {{ $data->sectionCheck('earning') ? 'checked' : '' }}>
                            <span class="slider round"></span>
                          </label>
                        </div>
                    </div>

                      <div class="row justify-content-center">

                        <div class="col-lg-4 d-flex justify-content-between">
                          <label class="control-label">{{ __('Catalog Items') }} *</label>
                          <label class="switch">
                            <input type="checkbox" name="section[]" value="catalogItems" {{ $data->sectionCheck('catalogItems') ? 'checked' : '' }}>
                            <span class="slider round"></span>
                          </label>
                        </div>

                        <div class="col-lg-2"></div>

                        <div class="col-lg-4 d-flex justify-content-between">
                          <label class="control-label">{{ __('Affiliate Catalog Items') }} *</label>
                          <label class="switch">
                            <input type="checkbox" name="section[]" value="affilate_products" {{ $data->sectionCheck('affilate_products') ? 'checked' : '' }}>
                            <span class="slider round"></span>
                          </label>
                        </div>

                    </div>

                    <div class="row justify-content-center">

                      <div class="col-lg-4 d-flex justify-content-between">
                        <label class="control-label">{{ __('Bulk CatalogItem Upload') }} *</label>
                        <label class="switch">
                          <input type="checkbox" name="section[]" value="bulk_product_upload" {{ $data->sectionCheck('bulk_product_upload') ? 'checked' : '' }}>
                          <span class="slider round"></span>
                        </label>
                      </div>

                      <div class="col-lg-2"></div>

                      <div class="col-lg-4 d-flex justify-content-between">
                        <label class="control-label">{{ __('CatalogItem Discussion') }} *</label>
                        <label class="switch">
                          <input type="checkbox" name="section[]" value="product_discussion" {{ $data->sectionCheck('product_discussion') ? 'checked' : '' }}>
                          <span class="slider round"></span>
                        </label>
                      </div>

                    </div>

                    <div class="row justify-content-center">

                      <div class="col-lg-4 d-flex justify-content-between">
                        <label class="control-label">{{ __('Set Discount Codes') }} *</label>
                        <label class="switch">
                          <input type="checkbox" name="section[]" value="set_discount_codes" {{ $data->sectionCheck('set_discount_codes') ? 'checked' : '' }}>
                          <span class="slider round"></span>
                        </label>
                      </div>

                      <div class="col-lg-2"></div>

                      <div class="col-lg-4 d-flex justify-content-between">
                        <label class="control-label">{{ __('Customers') }} *</label>
                        <label class="switch">
                          <input type="checkbox" name="section[]" value="customers" {{ $data->sectionCheck('customers') ? 'checked' : '' }}>
                          <span class="slider round"></span>
                        </label>
                      </div>

                    </div>

                    <div class="row justify-content-center">

                      <div class="col-lg-4 d-flex justify-content-between">
                        <label class="control-label">{{ __('Customer Deposits') }} *</label>
                        <label class="switch">
                          <input type="checkbox" name="section[]" value="customer_deposits" {{ $data->sectionCheck('customer_deposits') ? 'checked' : '' }}>
                          <span class="slider round"></span>
                        </label>
                      </div>

                      <div class="col-lg-2"></div>

                      <div class="col-lg-4 d-flex justify-content-between">
                        <label class="control-label">{{ __('Merchants') }} *</label>
                        <label class="switch">
                          <input type="checkbox" name="section[]" value="merchants" {{ $data->sectionCheck('merchants') ? 'checked' : '' }}>
                          <span class="slider round"></span>
                        </label>
                      </div>

                    </div>

                    <div class="row justify-content-center">

                      <div class="col-lg-4 d-flex justify-content-between">
                        <label class="control-label">{{ __('Merchant Subscriptions') }} *</label>
                        <label class="switch">
                          <input type="checkbox" name="section[]" value="merchant_subscriptions" {{ $data->sectionCheck('merchant_subscriptions') ? 'checked' : '' }}>
                          <span class="slider round"></span>
                        </label>
                      </div>

                      <div class="col-lg-2"></div>

                      <div class="col-lg-4 d-flex justify-content-between">
                        <label class="control-label">{{ __('Merchant Verifications') }} *</label>
                        <label class="switch">
                          <input type="checkbox" name="section[]" value="merchant_verifications" {{ $data->sectionCheck('merchant_verifications') ? 'checked' : '' }}>
                          <span class="slider round"></span>
                        </label>
                      </div>

                    </div>

                    <div class="row justify-content-center">

                      <div class="col-lg-4 d-flex justify-content-between">
                        <label class="control-label">{{ __('Merchant Subscription Plans') }} *</label>
                        <label class="switch">
                          <input type="checkbox" name="section[]" value="merchant_subscription_plans" {{ $data->sectionCheck('merchant_subscription_plans') ? 'checked' : '' }}>
                          <span class="slider round"></span>
                        </label>
                      </div>

                      <div class="col-lg-2"></div>

                      <div class="col-lg-4 d-flex justify-content-between">
                        <label class="control-label">{{ __('Messages') }} *</label>
                        <label class="switch">
                          <input type="checkbox" name="section[]" value="messages" {{ $data->sectionCheck('messages') ? 'checked' : '' }}>
                          <span class="slider round"></span>
                        </label>
                      </div>

                    </div>

                    <div class="row justify-content-center">

                      <div class="col-lg-4 d-flex justify-content-between">
                        <label class="control-label">{{ __('General Settings') }} *</label>
                        <label class="switch">
                          <input type="checkbox" name="section[]" value="muaadh_settings" {{ $data->sectionCheck('muaadh_settings') ? 'checked' : '' }}>
                          <span class="slider round"></span>
                        </label>
                      </div>

                      <div class="col-lg-2"></div>

                      <div class="col-lg-4 d-flex justify-content-between">
                        <label class="control-label">{{ __('Home Page Settings') }} *</label>
                        <label class="switch">
                          <input type="checkbox" name="section[]" value="home_page_settings" {{ $data->sectionCheck('home_page_settings') ? 'checked' : '' }}>
                          <span class="slider round"></span>
                        </label>
                      </div>

                    </div>

                    <div class="row justify-content-center">

                      <div class="col-lg-4 d-flex justify-content-between">
                        <label class="control-label">{{ __('Menu Page Settings') }} *</label>
                        <label class="switch">
                          <input type="checkbox" name="section[]" value="menu_page_settings" {{ $data->sectionCheck('menu_page_settings') ? 'checked' : '' }}>
                          <span class="slider round"></span>
                        </label>
                      </div>

                      <div class="col-lg-2"></div>

                      <div class="col-lg-4 d-flex justify-content-between">
                        <label class="control-label">{{ __('Email Settings') }} *</label>
                        <label class="switch">
                          <input type="checkbox" name="section[]" value="emails_settings" {{ $data->sectionCheck('email_settings') ? 'checked' : '' }}>
                          <span class="slider round"></span>
                        </label>
                      </div>

                    </div>

                    <div class="row justify-content-center">

                      <div class="col-lg-4 d-flex justify-content-between">
                        <label class="control-label">{{ __('Payment Settings') }} *</label>
                        <label class="switch">
                          <input type="checkbox" name="section[]" value="payment_settings" {{ $data->sectionCheck('payment_settings') ? 'checked' : '' }}>
                          <span class="slider round"></span>
                        </label>
                      </div>

                      <div class="col-lg-2"></div>

                      <div class="col-lg-4 d-flex justify-content-between">
                        <label class="control-label">{{ __('Social Settings') }} *</label>
                        <label class="switch">
                          <input type="checkbox" name="section[]" value="social_settings" {{ $data->sectionCheck('social_settings') ? 'checked' : '' }}>
                          <span class="slider round"></span>
                        </label>
                      </div>

                    </div>

                    <div class="row justify-content-center">

                      <div class="col-lg-4 d-flex justify-content-between">
                        <label class="control-label">{{ __('Language Settings') }} *</label>
                        <label class="switch">
                          <input type="checkbox" name="section[]" value="language_settings" {{ $data->sectionCheck('language_settings') ? 'checked' : '' }}>
                          <span class="slider round"></span>
                        </label>
                      </div>

                      <div class="col-lg-2"></div>

                      <div class="col-lg-4 d-flex justify-content-between">
                        <label class="control-label">{{ __('SEO Tools') }} *</label>
                        <label class="switch">
                          <input type="checkbox" name="section[]" value="seo_tools" {{ $data->sectionCheck('seo_tools') ? 'checked' : '' }}>
                          <span class="slider round"></span>
                        </label>
                      </div>

                    </div>

                    <div class="row justify-content-center">

                      <div class="col-lg-4 d-flex justify-content-between">
                        <label class="control-label">{{ __('Manage Staffs') }} *</label>
                        <label class="switch">
                          <input type="checkbox" name="section[]" value="manage_staffs" {{ $data->sectionCheck('manage_staffs') ? 'checked' : '' }}>
                          <span class="slider round"></span>
                        </label>
                      </div>

                      <div class="col-lg-2"></div>

                      <div class="col-lg-4 d-flex justify-content-between">
                        <label class="control-label">{{ __('Subscribers') }} *</label>
                        <label class="switch">
                          <input type="checkbox" name="section[]" value="subscribers" {{ $data->sectionCheck('subscribers') ? 'checked' : '' }}>
                          <span class="slider round"></span>
                        </label>
                      </div>

                    </div>

                        <div class="row">
                          <div class="col-lg-5">
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