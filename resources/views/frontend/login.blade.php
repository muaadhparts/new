@extends('layouts.front')

@section('content')
    <section class="gs-breadcrumb-section bg-class"
        data-background="{{ $gs->breadcrumb_banner ? asset('assets/images/' . $gs->breadcrumb_banner) : asset('assets/images/noimage.png') }}">
        <div class="container">
            <div class="row justify-content-center content-wrapper">
                <div class="col-12">
                    <h2 class="breadcrumb-title">@lang('User Login')</h2>
                    <ul class="bread-menu">
                        <li><a href="{{ route('front.index') }}">@lang('Home')</a></li>
                        <li><a href="javascript:;">@lang('User Login')</a></li>
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
                        <h4 class="text-center">@lang('Welcome Back! Please login') </h4>
                        <form action="{{ route('user.login.submit') }}" method="POST">
                            @csrf
                            <div class="form-group">

                                <div>
                                    <label for="email">@lang('Email Address')</label>
                                    <input type="email" name="email" class="form-control" id="email"
                                        placeholder="@lang('Enter your email address')">

                                    @if ($errors->has('email'))
                                        <span class="text-danger">{{ $errors->first('email') }}</span>
                                    @endif
                                </div>

                                <div>
                                    <label for="create-password">@lang('Your Password')</label>
                                    <div class="pass-wrapper">
                                        <input type="password" name="password" class="form-control" id="create-password"
                                            placeholder="@lang('Enter your password')">
                                        <svg class="eye-off" xmlns="http://www.w3.org/2000/svg" width="16"
                                            height="16" viewBox="0 0 16 16" fill="none">
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
                                </div>

                                @error('password')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror

                                <div class="row mt-2 align-items-center">
                                    <div class="col d-flex ">
                                        <!-- Checkbox -->
                                        <div class="gs-checkbox-wrapper">
                                     
                                            <input   type="checkbox" value=""
                                                id="form2Example31">
                                                        <label class="icon-label pb-0 mb-3" for="form2Example31">
                                                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 12 12" fill="none">
                                                                <path d="M10 3L4.5 8.5L2 6" stroke="#EE1243" stroke-width="1.6666" stroke-linecap="round"
                                                                    stroke-linejoin="round" />
                                                            </svg>
                                                        </label>
                                            <label   for="form2Example31"> @lang('Remember me')
                                            </label>
                                        </div>
                                    </div>

                                    <div class="col d-flex justify-content-end login-forgot">
                                        <!-- Simple link -->
                                        <a href="{{ route('user.forgot') }}">@lang('Forgot password?')</a>
                                    </div>
                                </div>



                                <button type="submit" class="template-btn btn-forms">@lang('Login')</button>
                                <p class="text-center login-or">@lang('Or')</p> <br>

                                @if (addon("otp"))
                                    <a href="{{ route('user.otp.login') }}"
                                        class="template-btn outline-btn login-outline">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
                                            <g clip-path="url(#clip0_24_853)">
                                              <path d="M12 24C18.6274 24 24 18.6274 24 12C24 5.37258 18.6274 0 12 0C5.37258 0 0 5.37258 0 12C0 18.6274 5.37258 24 12 24Z" fill="#2196F3"/>
                                              <path d="M18 14.481C16.7055 14.481 15.471 14.199 14.3325 13.6425C14.154 13.557 13.947 13.5435 13.758 13.608C13.569 13.674 13.4145 13.812 13.3275 13.9905L12.7875 15.108C11.1675 14.178 9.8235 12.8325 8.892 11.2125L10.011 10.6725C10.191 10.5855 10.3275 10.431 10.3935 10.242C10.458 10.053 10.446 9.846 10.359 9.6675C9.801 8.5305 9.519 7.296 9.519 6C9.519 5.586 9.183 5.25 8.769 5.25H6C5.586 5.25 5.25 5.586 5.25 6C5.25 13.0305 10.9695 18.75 18 18.75C18.414 18.75 18.75 18.414 18.75 18V15.231C18.75 14.817 18.414 14.481 18 14.481Z" fill="#FAFAFA"/>
                                            </g>
                                            <defs>
                                              <clipPath id="clip0_24_853">
                                                <rect width="24" height="24" fill="white"/>
                                              </clipPath>
                                            </defs>
                                          </svg>
                                        @lang('Login with Number')
                                    </a>
                                @endif



                                @if ($socialsetting->f_check == 1)
                                    <a href="{{ route('social-provider', 'facebook') }}"
                                        class="template-btn outline-btn login-outline">

                                        <svg xmlns="http://www.w3.org/2000/svg" width="25" height="24"
                                            viewBox="0 0 25 24" fill="none">
                                            <g clip-path="url(#clip0_596_35210)">
                                                <path
                                                    d="M24.2682 12.2765C24.2682 11.4608 24.2021 10.6406 24.061 9.83813H12.7422V14.4591H19.2239C18.955 15.9495 18.0907 17.2679 16.8252 18.1056V21.104H20.6922C22.963 19.014 24.2682 15.9274 24.2682 12.2765Z"
                                                    fill="#4285F4" />
                                                <path
                                                    d="M12.7391 24.0008C15.9756 24.0008 18.705 22.9382 20.6936 21.1039L16.8266 18.1055C15.7507 18.8375 14.3618 19.252 12.7435 19.252C9.61291 19.252 6.95849 17.1399 6.00607 14.3003H2.01562V17.3912C4.05274 21.4434 8.20192 24.0008 12.7391 24.0008Z"
                                                    fill="#34A853" />
                                                <path
                                                    d="M6.00473 14.3002C5.50206 12.8099 5.50206 11.196 6.00473 9.70569V6.61475H2.01869C0.316687 10.0055 0.316687 14.0004 2.01869 17.3912L6.00473 14.3002Z"
                                                    fill="#FBBC04" />
                                                <path
                                                    d="M12.7391 4.74966C14.4499 4.7232 16.1034 5.36697 17.3425 6.54867L20.7685 3.12262C18.5991 1.0855 15.7198 -0.034466 12.7391 0.000808666C8.20192 0.000808666 4.05274 2.55822 2.01562 6.61481L6.00166 9.70575C6.94967 6.86173 9.6085 4.74966 12.7391 4.74966Z"
                                                    fill="#EA4335" />
                                            </g>
                                            <defs>
                                                <clipPath id="clip0_596_35210">
                                                    <rect width="24" height="24" fill="white"
                                                        transform="translate(0.5)" />
                                                </clipPath>
                                            </defs>
                                        </svg>

                                        @lang('Login with Google')
                                    </a>
                                @endif
                                @if ($socialsetting->g_check == 1)
                                    <a href="{{ route('social-provider', 'google') }}"
                                        class="template-btn outline-btn login-outline">

                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                            viewBox="0 0 24 24" fill="none">
                                            <g clip-path="url(#clip0_596_36880)">
                                                <path
                                                    d="M24 12C24 5.37258 18.6274 0 12 0C5.37258 0 0 5.37258 0 12C0 17.9895 4.3882 22.954 10.125 23.8542V15.4688H7.07812V12H10.125V9.35625C10.125 6.34875 11.9166 4.6875 14.6576 4.6875C15.9701 4.6875 17.3438 4.92188 17.3438 4.92188V7.875H15.8306C14.34 7.875 13.875 8.80008 13.875 9.75V12H17.2031L16.6711 15.4688H13.875V23.8542C19.6118 22.954 24 17.9895 24 12Z"
                                                    fill="#1877F2" />
                                                <path
                                                    d="M16.6711 15.4688L17.2031 12H13.875V9.75C13.875 8.80102 14.34 7.875 15.8306 7.875H17.3438V4.92188C17.3438 4.92188 15.9705 4.6875 14.6576 4.6875C11.9166 4.6875 10.125 6.34875 10.125 9.35625V12H7.07812V15.4688H10.125V23.8542C11.3674 24.0486 12.6326 24.0486 13.875 23.8542V15.4688H16.6711Z"
                                                    fill="white" />
                                            </g>
                                            <defs>
                                                <clipPath id="clip0_596_36880">
                                                    <rect width="24" height="24" fill="white" />
                                                </clipPath>
                                            </defs>
                                        </svg>

                                        @lang('Login with Facebook')
                                    </a>
                                @endif


                                <p class="login-redirect">@lang("Don't have an account?")
                                    <span>
                                        <a href="{{ route('user.register') }}">@lang('Create New Account')
                                        </a>
                                    </span>
                                </p>
                            </div>
                        </form>
                    </div>
    </section>
@endsection
