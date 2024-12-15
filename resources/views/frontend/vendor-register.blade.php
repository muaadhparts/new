@extends('layouts.front')

@section('content')
    <section class="gs-breadcrumb-section bg-class"
        data-background="{{ $gs->breadcrumb_banner ? asset('assets/images/' . $gs->breadcrumb_banner) : asset('assets/images/noimage.png') }}">
        <div class="container">
            <div class="row justify-content-center content-wrapper">
                <div class="col-12">
                    <h2 class="breadcrumb-title">@lang('Vendor Register')</h2>
                    <ul class="bread-menu">
                        <li><a href="{{ route('front.index') }}">@lang('Home')</a></li>
                        <li><a href="#">@lang('Vendor Register')</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </section>


    <section class="gs-reg-section wow-replaced" data-wow-delay=".1s">
        <div class="container">
            <div class="row">
                <div class="col-lg-6 mx-auto reg-area">
                    <div class="reg-content">
                        <h4 class="text-center">@lang('Create Your New Account')</h4>
                        <form action="{{ route('user-register-submit') }}" method="POST">
                            @csrf
                            <div class="form-group">
                                <label for="name">@lang('Full Name')</label>
                                <input type="text" class="form-control" id="name" name="name"
                                    placeholder="@lang('Enter your full name')">
                                @error('name')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>
                            <div class="form-group">
                                <label for="email">@lang('Email Address')</label>
                                <input type="email" class="form-control" id="email" name="email"
                                    placeholder="@lang('Enter your email address')">
                                @error('email')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>
                            <div class="form-group">
                                <label for="phone">@lang('Phone Number')</label>
                                <input type="text" class="form-control" id="phone" name="phone"
                                    placeholder="@lang('Enter your phone number')">
                                @error('phone')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>
                            <div class="form-group">
                                <label for="address">@lang('Address')</label>
                                <input type="text" class="form-control" id="address" name="address"
                                    placeholder="@lang('Enter your address')">

                                @error('address')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>
                            <div class="form-group">

                                <label for="create-password">@lang('Create Password')</label>
                                <div class="pass-wrapper">
                                    <input type="password" name="password" class="form-control" id="create-password"
                                        placeholder="@lang('Enter your password')">
                                    <svg class="eye-off" xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                        viewBox="0 0 16 16" fill="none">
                                        <g clip-path="url(#clip0_6740_31508)">
                                            <path
                                                d="M15.517 6.27923C14.9151 5.29372 14.156 4.4134 13.2696 3.67323L15.1363 1.80657C15.2577 1.68083 15.3249 1.51243 15.3234 1.33763C15.3219 1.16283 15.2518 0.995625 15.1282 0.87202C15.0046 0.748415 14.8374 0.678302 14.6626 0.676783C14.4878 0.675264 14.3194 0.74246 14.1936 0.863899L12.1636 2.89657C10.9052 2.14911 9.46658 1.75954 8.00296 1.7699C3.87563 1.7699 1.52363 4.59523 0.488964 6.27923C0.169316 6.79624 0 7.39206 0 7.9999C0 8.60774 0.169316 9.20356 0.488964 9.72057C1.0908 10.7061 1.84996 11.5864 2.7363 12.3266L0.869631 14.1932C0.805957 14.2547 0.755169 14.3283 0.72023 14.4096C0.68529 14.491 0.666899 14.5784 0.66613 14.667C0.665361 14.7555 0.682229 14.8433 0.715749 14.9252C0.74927 15.0071 0.798772 15.0816 0.861367 15.1442C0.923962 15.2068 0.998397 15.2563 1.08033 15.2898C1.16226 15.3233 1.25005 15.3402 1.33856 15.3394C1.42708 15.3386 1.51456 15.3202 1.5959 15.2853C1.67724 15.2504 1.7508 15.1996 1.8123 15.1359L3.84696 13.1012C5.10381 13.8486 6.54074 14.2388 8.00296 14.2299C12.1303 14.2299 14.4823 11.4046 15.517 9.72057C15.8366 9.20356 16.0059 8.60774 16.0059 7.9999C16.0059 7.39206 15.8366 6.79624 15.517 6.27923ZM1.62496 9.02257C1.43505 8.71526 1.33446 8.36115 1.33446 7.9999C1.33446 7.63865 1.43505 7.28454 1.62496 6.97723C2.5143 5.53323 4.5243 3.10323 8.00296 3.10323C9.10981 3.09703 10.2011 3.36379 11.1803 3.8799L9.8383 5.2219C9.19825 4.79697 8.4309 4.60656 7.66645 4.683C6.902 4.75943 6.18754 5.09799 5.6443 5.64123C5.10106 6.18448 4.76249 6.89894 4.68606 7.66339C4.60963 8.42784 4.80003 9.19519 5.22496 9.83523L3.68496 11.3752C2.86837 10.715 2.17152 9.9192 1.62496 9.02257ZM10.003 7.9999C10.003 8.53033 9.79225 9.03904 9.41718 9.41411C9.04211 9.78919 8.5334 9.9999 8.00296 9.9999C7.70597 9.99875 7.4131 9.93037 7.1463 9.7999L9.80296 7.14323C9.93344 7.41003 10.0018 7.70291 10.003 7.9999ZM6.00296 7.9999C6.00296 7.46947 6.21368 6.96076 6.58875 6.58569C6.96382 6.21061 7.47253 5.9999 8.00296 5.9999C8.29996 6.00105 8.59283 6.06943 8.85963 6.1999L6.20296 8.85657C6.07249 8.58977 6.00411 8.29689 6.00296 7.9999ZM14.381 9.02257C13.4916 10.4666 11.4816 12.8966 8.00296 12.8966C6.89612 12.9028 5.80481 12.636 4.82563 12.1199L6.16763 10.7779C6.80768 11.2028 7.57503 11.3932 8.33948 11.3168C9.10393 11.2404 9.81839 10.9018 10.3616 10.3586C10.9049 9.81532 11.2434 9.10086 11.3199 8.33641C11.3963 7.57196 11.2059 6.80461 10.781 6.16457L12.321 4.62457C13.1376 5.28475 13.8344 6.0806 14.381 6.97723C14.5709 7.28454 14.6715 7.63865 14.6715 7.9999C14.6715 8.36115 14.5709 8.71526 14.381 9.02257Z"
                                                fill="#999999" />
                                        </g>
                                        <defs>
                                            <clipPath id="clip0_6740_31508">
                                                <rect width="16" height="16" fill="white" />
                                            </clipPath>
                                        </defs>
                                    </svg>
                                    <svg class="eye-on" xmlns="http://www.w3.org/2000/svg" width="18" height="14"
                                        viewBox="0 0 18 14" fill="none">
                                        <path
                                            d="M1.21444 7.57951C1.10377 7.40427 1.04843 7.31665 1.01745 7.1815C0.994183 7.07998 0.994183 6.91989 1.01745 6.81837C1.04843 6.68322 1.10377 6.5956 1.21444 6.42036C2.12902 4.97221 4.85134 1.31128 9 1.31128C13.1487 1.31128 15.871 4.97221 16.7856 6.42036C16.8962 6.5956 16.9516 6.68322 16.9825 6.81837C17.0058 6.91989 17.0058 7.07998 16.9825 7.1815C16.9516 7.31665 16.8962 7.40427 16.7856 7.57951C15.871 9.02766 13.1487 12.6886 9 12.6886C4.85134 12.6886 2.12902 9.02767 1.21444 7.57951Z"
                                            stroke="#1F0300" stroke-width="1.33333" stroke-linecap="round"
                                            stroke-linejoin="round" />
                                        <path
                                            d="M9 9.43793C10.3465 9.43793 11.438 8.3464 11.438 6.99994C11.438 5.65347 10.3465 4.56194 9 4.56194C7.65353 4.56194 6.56201 5.65347 6.56201 6.99994C6.56201 8.3464 7.65353 9.43793 9 9.43793Z"
                                            stroke="#1F0300" stroke-width="1.33333" stroke-linecap="round"
                                            stroke-linejoin="round" />
                                    </svg>
                                </div>

                                @error('password')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror

                            </div>

                            <div class="form-group">

                                <label for="confirm-password">@lang('Confirm Password')</label>
                                <div class="pass-wrapper">
                                    <input type="password" name="password_confirmation" class="form-control"
                                        id="confirm-password" placeholder="@lang('Enter your password')">
                                    <svg class="confirm-eye-off" xmlns="http://www.w3.org/2000/svg" width="16"
                                        height="16" viewBox="0 0 16 16" fill="none">
                                        <g clip-path="url(#clip0_6740_31508)">
                                            <path
                                                d="M15.517 6.27923C14.9151 5.29372 14.156 4.4134 13.2696 3.67323L15.1363 1.80657C15.2577 1.68083 15.3249 1.51243 15.3234 1.33763C15.3219 1.16283 15.2518 0.995625 15.1282 0.87202C15.0046 0.748415 14.8374 0.678302 14.6626 0.676783C14.4878 0.675264 14.3194 0.74246 14.1936 0.863899L12.1636 2.89657C10.9052 2.14911 9.46658 1.75954 8.00296 1.7699C3.87563 1.7699 1.52363 4.59523 0.488964 6.27923C0.169316 6.79624 0 7.39206 0 7.9999C0 8.60774 0.169316 9.20356 0.488964 9.72057C1.0908 10.7061 1.84996 11.5864 2.7363 12.3266L0.869631 14.1932C0.805957 14.2547 0.755169 14.3283 0.72023 14.4096C0.68529 14.491 0.666899 14.5784 0.66613 14.667C0.665361 14.7555 0.682229 14.8433 0.715749 14.9252C0.74927 15.0071 0.798772 15.0816 0.861367 15.1442C0.923962 15.2068 0.998397 15.2563 1.08033 15.2898C1.16226 15.3233 1.25005 15.3402 1.33856 15.3394C1.42708 15.3386 1.51456 15.3202 1.5959 15.2853C1.67724 15.2504 1.7508 15.1996 1.8123 15.1359L3.84696 13.1012C5.10381 13.8486 6.54074 14.2388 8.00296 14.2299C12.1303 14.2299 14.4823 11.4046 15.517 9.72057C15.8366 9.20356 16.0059 8.60774 16.0059 7.9999C16.0059 7.39206 15.8366 6.79624 15.517 6.27923ZM1.62496 9.02257C1.43505 8.71526 1.33446 8.36115 1.33446 7.9999C1.33446 7.63865 1.43505 7.28454 1.62496 6.97723C2.5143 5.53323 4.5243 3.10323 8.00296 3.10323C9.10981 3.09703 10.2011 3.36379 11.1803 3.8799L9.8383 5.2219C9.19825 4.79697 8.4309 4.60656 7.66645 4.683C6.902 4.75943 6.18754 5.09799 5.6443 5.64123C5.10106 6.18448 4.76249 6.89894 4.68606 7.66339C4.60963 8.42784 4.80003 9.19519 5.22496 9.83523L3.68496 11.3752C2.86837 10.715 2.17152 9.9192 1.62496 9.02257ZM10.003 7.9999C10.003 8.53033 9.79225 9.03904 9.41718 9.41411C9.04211 9.78919 8.5334 9.9999 8.00296 9.9999C7.70597 9.99875 7.4131 9.93037 7.1463 9.7999L9.80296 7.14323C9.93344 7.41003 10.0018 7.70291 10.003 7.9999ZM6.00296 7.9999C6.00296 7.46947 6.21368 6.96076 6.58875 6.58569C6.96382 6.21061 7.47253 5.9999 8.00296 5.9999C8.29996 6.00105 8.59283 6.06943 8.85963 6.1999L6.20296 8.85657C6.07249 8.58977 6.00411 8.29689 6.00296 7.9999ZM14.381 9.02257C13.4916 10.4666 11.4816 12.8966 8.00296 12.8966C6.89612 12.9028 5.80481 12.636 4.82563 12.1199L6.16763 10.7779C6.80768 11.2028 7.57503 11.3932 8.33948 11.3168C9.10393 11.2404 9.81839 10.9018 10.3616 10.3586C10.9049 9.81532 11.2434 9.10086 11.3199 8.33641C11.3963 7.57196 11.2059 6.80461 10.781 6.16457L12.321 4.62457C13.1376 5.28475 13.8344 6.0806 14.381 6.97723C14.5709 7.28454 14.6715 7.63865 14.6715 7.9999C14.6715 8.36115 14.5709 8.71526 14.381 9.02257Z"
                                                fill="#999999" />
                                        </g>
                                        <defs>
                                            <clipPath id="clip3_6740_31508">
                                                <rect width="16" height="16" fill="white" />
                                            </clipPath>
                                        </defs>
                                    </svg>
                                    <svg class="confirm-eye-on" xmlns="http://www.w3.org/2000/svg" width="18"
                                        height="14" viewBox="0 0 18 14" fill="none">
                                        <path
                                            d="M1.21444 7.57951C1.10377 7.40427 1.04843 7.31665 1.01745 7.1815C0.994183 7.07998 0.994183 6.91989 1.01745 6.81837C1.04843 6.68322 1.10377 6.5956 1.21444 6.42036C2.12902 4.97221 4.85134 1.31128 9 1.31128C13.1487 1.31128 15.871 4.97221 16.7856 6.42036C16.8962 6.5956 16.9516 6.68322 16.9825 6.81837C17.0058 6.91989 17.0058 7.07998 16.9825 7.1815C16.9516 7.31665 16.8962 7.40427 16.7856 7.57951C15.871 9.02766 13.1487 12.6886 9 12.6886C4.85134 12.6886 2.12902 9.02767 1.21444 7.57951Z"
                                            stroke="#1F0300" stroke-width="1.33333" stroke-linecap="round"
                                            stroke-linejoin="round" />
                                        <path
                                            d="M9 9.43793C10.3465 9.43793 11.438 8.3464 11.438 6.99994C11.438 5.65347 10.3465 4.56194 9 4.56194C7.65353 4.56194 6.56201 5.65347 6.56201 6.99994C6.56201 8.3464 7.65353 9.43793 9 9.43793Z"
                                            stroke="#1F0300" stroke-width="1.33333" stroke-linecap="round"
                                            stroke-linejoin="round" />
                                    </svg>
                                </div>


                                @if ($gs->is_capcha == 1)
                                    <div class="form-group mt-3">
                                        {!! NoCaptcha::display() !!}
                                        {!! NoCaptcha::renderJs() !!}
                                        @error('g-recaptcha-response')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                @endif


                            </div>


                            <input type="hidden" name="vendor" value="1">
                            <div class="form-group">
                                <button type="submit" class="template-btn btn-forms">@lang('Register')</button>

                                <p class="login-redirect">@lang('Already have an account?') <span><a
                                            href="{{ route('vendor.login') }}">@lang('Login')</a></span></p>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>


    </section>
@endsection

@section('script')
@endsection
