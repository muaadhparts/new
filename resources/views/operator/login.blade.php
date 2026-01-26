<!doctype html>
<html lang="en" dir="ltr">
  
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
      <meta name="author" content="Muaadh">
    <!-- Title -->
    <title>{{$gs->site_name}}</title>
    <!-- favicon -->
    <link rel="icon"  type="image/x-icon" href="{{asset('assets/images/'.$gs->favicon)}}"/>
    <!-- Bootstrap -->
    <link href="{{asset('assets/operator/css/bootstrap.min.css')}}" rel="stylesheet" />
    <!-- Fontawesome -->
    <link rel="stylesheet" href="{{asset('assets/operator/css/fontawesome.css')}}">
    <!-- icofont -->
    <link rel="stylesheet" href="{{asset('assets/operator/css/icofont.min.css')}}">
    <!-- Sidemenu Css -->
    <link href="{{asset('assets/operator/plugins/fullside-menu/css/dark-side-style.css')}}" rel="stylesheet" />
    <link href="{{asset('assets/operator/plugins/fullside-menu/waves.min.css')}}" rel="stylesheet" />

    <link href="{{asset('assets/operator/css/plugin.css')}}" rel="stylesheet" />
    <link href="{{asset('assets/operator/css/jquery.tagit.css')}}" rel="stylesheet" />   
      <link rel="stylesheet" href="{{ asset('assets/operator/css/bootstrap-coloroicker.css') }}">
    <!-- Main Css -->
    <link href="{{asset('assets/operator/css/style.css')}}" rel="stylesheet"/>
    <link href="{{asset('assets/operator/css/custom.css')}}" rel="stylesheet"/>
    <link href="{{asset('assets/operator/css/responsive.css')}}" rel="stylesheet" />

    {{-- Frontend Theme Files --}}
    <link rel="stylesheet" href="{{ asset('assets/front/css/style.css') }}">
    {{-- Theme Colors - Generated from Admin Panel (MUST load LAST) --}}
    @themeStyles

  </head>
  <body>

    <!-- Login and Sign up Area Start -->
    <section class="login-signup">
      <div class="container">
        <div class="row justify-content-center">
          <div class="col-lg-5">
            <div class="login-area">
              <div class="header-area">
                <h4 class="name">{{ __('Login Now') }}</h4>
                <p class="text">{{ __('Welcome back, please sign in below') }}</p>
              </div>
              <div class="login-form">
                @include('alerts.operator.form-login')
                <form id="loginform" action="{{ route('operator.login.submit') }}" method="POST">
                  @csrf
                  <div class="mb-3">
                    <input type="email" name="email" class="form-control" placeholder="{{ __('Type Email Address') }}" value="" required autofocus>
                  </div>
                  <div class="mb-3">
                    <input type="password" name="password" class="form-control" placeholder="{{ __('Type Password') }}" required>
                  </div>
                  <div class="form-forgot-pass">
                    <div class="left">
                      <input type="checkbox" name="remember"  id="rp" {{ old('remember') ? 'checked' : '' }}>
                      <label for="rp">{{ __('Remember Password') }}</label>
                    </div>
                    <div class="right">
                      <a href="{{ route('operator.forgot') }}">
                        {{ __('Forgot Password?') }}
                      </a>
                    </div>
                  </div>
                  <input id="authdata" type="hidden"  value="{{ __('Authenticating...') }}">
                  <button class="btn btn-primary">{{ __('Login') }}</button>
                </form>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
    <!--Login and Sign up Area End -->


    <!-- Dashboard Core -->
    <script src="{{asset('assets/operator/js/vendors/jquery-1.12.4.min.js')}}"></script>
    <script src="{{asset('assets/operator/js/vendors/bootstrap.min.js')}}"></script>
    <script src="{{asset('assets/operator/js/jqueryui.min.js')}}"></script>
    <!-- Fullside-menu Js-->
    <script src="{{asset('assets/operator/plugins/fullside-menu/jquery.slimscroll.min.js')}}"></script>
    <script src="{{asset('assets/operator/plugins/fullside-menu/waves.min.js')}}"></script>

    <script src="{{asset('assets/operator/js/plugin.js')}}"></script>
    <script src="{{asset('assets/operator/js/tag-it.js')}}"></script>
    <script src="{{asset('assets/operator/js/nicEdit.js')}}"></script>
    <script src="{{ asset('assets/operator/js/bootstrap-colorpicker.min.js') }}"></script>
    <script src="{{asset('assets/operator/js/load.js')}}"></script>
    <!-- Custom Js-->
    <script src="{{asset('assets/operator/js/custom.js')}}"></script>
    <!-- AJAX Js-->
    <script src="{{asset('assets/operator/js/myscript.js')}}"></script>

  </body>

</html>