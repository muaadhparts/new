@extends('layouts.vendor')
@section('content')
    <div class="gs-vendor-outlet">
        <!-- breadcrumb start  -->
        <div class="gs-vendor-breadcrumb has-mb">
            <h4 class="text-capitalize">@lang('Edit Profile')</h4>
            <ul class="breadcrumb-menu">
                <li>
                    <a href="{{ route('vendor.dashboard') }}" class="text-capitalize">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                            stroke="#4C3533" class="home-icon-vendor-panel-breadcrumb">
                            <path
                                d="M9 21V13.6C9 13.0399 9 12.7599 9.109 12.546C9.20487 12.3578 9.35785 12.2049 9.54601 12.109C9.75993 12 10.04 12 10.6 12H13.4C13.9601 12 14.2401 12 14.454 12.109C14.6422 12.2049 14.7951 12.3578 14.891 12.546C15 12.7599 15 13.0399 15 13.6V21M2 9.5L11.04 2.72C11.3843 2.46181 11.5564 2.33271 11.7454 2.28294C11.9123 2.23902 12.0877 2.23902 12.2546 2.28295C12.4436 2.33271 12.6157 2.46181 12.96 2.72L22 9.5M4 8V17.8C4 18.9201 4 19.4802 4.21799 19.908C4.40974 20.2843 4.7157 20.5903 5.09202 20.782C5.51985 21 6.0799 21 7.2 21H16.8C17.9201 21 18.4802 21 18.908 20.782C19.2843 20.5903 19.5903 20.2843 19.782 19.908C20 19.4802 20 18.9201 20 17.8V8L13.92 3.44C13.2315 2.92361 12.8872 2.66542 12.5091 2.56589C12.1754 2.47804 11.8246 2.47804 11.4909 2.56589C11.1128 2.66542 10.7685 2.92361 10.08 3.44L4 8Z"
                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </a>
                </li>
                <li>
                    <a href="{{ route('vendor.dashboard') }}" class="text-capitalize">
                        @lang('Dashboard')
                    </a>
                </li>
                <li>
                    <a href="#" class="text-capitalize"> @lang('Edit Profile') </a>
                </li>
            </ul>
        </div>
        <!-- breadcrumb end -->

        <!-- Edit Profile area start  -->
        <div class="vendor-edit-profile-section-wrapper">
            <div class="gs-edit-profile-section">
                <a href="{{ route('vendor-verify') }}" class="template-btn dark-btn ms-auto">@lang('Verify Your Account')</a>
                <form class="edit-profile-area" action="{{ route('vendor-profile-update') }}" method="POST">
                    @csrf
                    <div class="row row-cols-1 row-cols-sm-2">
                        <div class="col  ">
                            <div class="form-group">
                                <label for="shop-name">@lang('Shop Name')</label>
                                <input type="text" id="shop-name" class="form-control" placeholder="@lang('Test Shops')"
                                    value="{{ $data->shop_name }}" name="shop_name">
                                @error('shop_name')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                        <div class="col ">
                            <div class="form-group">
                                <label for="owner-name">@lang('Owner Name')</label>
                                <input type="text" id="owner-name" class="form-control" placeholder="@lang('Owner Name')"
                                    value="{{ $data->owner_name }}" name="owner_name">
                                @error('owner_name')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                        <div class="col">
                            <div class="form-group">
                                <label for="shop-number">@lang('Shop Number')</label>
                                <input type="text" id="shop-number" class="form-control" placeholder="@lang('Shop Number')"
                                    value="{{ $data->shop_number }}" name="shop_number">
                                @error('shop_number')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                        <div class="col">
                            <div class="form-group">
                                <label for="shop-address">@lang('Shop Address')</label>
                                <input type="text" id="shop-address" class="form-control" placeholder="@lang('Shop Address')"
                                    value="{{ $data->shop_address }}" name="shop_address">
                                @error('shop_address')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                        <div class="col">
                            <div class="form-group">
                                <label for="registration-number">@lang('Registration Number')</label>
                                <input type="text" id="registration-number" class="form-control"
                                    placeholder="@lang('Registration Number')" value="{{ $data->reg_number }}" name="reg_number">
								@error('reg_number')
									<span class="text-danger">{{ $message }}</span>
								@enderror
                            </div>
                        </div>

                        <div class="col-12 col-sm-12 form-group">
                            <label for="name">@lang('Shop Details')</label>
                            <textarea id="name" class="form-control" placeholder="@lang('Enter Your Name')" rows="10">{{ $data->description }}</textarea>
							@error('description')
								<span class="text-danger">{{ $message }}</span>
							@enderror
                        </div>
                        <div class="col-12 col-sm-12">
                            <button class="template-btn btn-forms" type="submit">
                                @lang('Save')
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        <!-- Edit Profile area end  -->
    </div>
@endsection
