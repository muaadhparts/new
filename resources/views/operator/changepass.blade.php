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
    <link href="{{asset('assets/operator/css/responsive.css')}}" rel="stylesheet"/>
    
    @yield('styles')

  </head>
  <body>

    <!-- Login and Sign up Area Start -->
    <section class="login-signup">
      <div class="container">
        <div class="row justify-content-center">
          <div class="col-lg-5">
            <div class="login-area">
              <div class="header-area">
                <h4 class="title">{{ __('Change Password') }}</h4>
              </div>
              <div class="login-form">
                <div class="gocover" style="background: url({{asset('assets/images/'.$gs->admin_loader)}}) no-repeat scroll center center rgba(45, 45, 45, 0.5);"></div>
                @include('alerts.operator.form-both')
                <form id="passwordform" action="{{ route('operator.change.password') }}" method="POST">
                  @csrf

                  <div class="form-input">
                    <input type="password" name="cpass" class="Password" placeholder="{{ __('Current Password') }}" required=""><i class="icofont-ui-password"></i>
                  </div>

                  <div class="form-input">
                    <input type="password" name="newpass" class="Password" placeholder="{{ __('New Password') }}" required=""><i class="icofont-ui-password"></i>
                  </div>

                  <div class="form-input">
                    <input type="password" name="renewpass" class="Password" placeholder="{{ __('Re-Type New Password') }}" required=""><i class="icofont-ui-password"></i>
                  </div>
                  
                  <input type="hidden" name="file_token" value="{{ $token }}">

                  <button class="btn btn-primary">{{ __('Submit') }}</button>
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

    <script>

        $("#passwordform").on('submit',function(e){
          e.preventDefault();
          $('button.submit-btn').prop('disabled',true);
          $('.gocover').show();
              $.ajax({
               method:"POST",
               url:$(this).prop('action'),
               data:new FormData(this),
               dataType:'JSON',
               contentType: false,
               cache: false,
               processData: false,
               success:function(data)
               {  
                $('.gocover').hide();
                  if ((data.errors)) {
                  $('.alert-success').hide();
                  $('.alert-danger').show();
                  $('.alert-danger ul').html('');
                    for(var error in data.errors)
                    {
                      $('.alert-danger ul').append('<li>'+ data.errors[error] +'</li>');
                    }
                  }
                  else {
                    $('.alert-success').show();
                    $('.alert-success p').html(data);
                  }
                  $('button.submit-btn').prop('disabled',false);
               }
              });
        
        });
        
        </script>

  </body>

</html>