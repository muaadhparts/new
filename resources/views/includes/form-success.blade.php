@if (Session::has('success'))
      <div class="alert alert-success alert-dismissible">
              <button type="button" class="btn-close" data-bs-dismiss="alert">&times;</button>
              {{ Session::get('success') }}
      </div>


@endif

@if (Session::has('unsuccess'))

      <div class="alert alert-danger alert-dismissible">
              <button type="button" class="btn-close" data-bs-dismiss="alert">&times;</button>
              {{ Session::get('unsuccess') }}
      </div>
@endif

@if(session('message') === 'f')
      <div class="alert alert-danger alert-dismissible">
              <button type="button" class="btn-close" data-bs-dismiss="alert">&times;</button>
              @lang('Credentials doesn't match')
      </div>

@endif  