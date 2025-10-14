@extends('layouts.front')

@section('content')


<div class="gs-user-panel-review wow-replaced" data-wow-delay=".1s">
  <div class="container">
    <div class="d-flex">
      <!-- sidebar -->
      @include('includes.user.sidebar')
      <!-- main content -->
      <div class="gs-dashboard-user-content-wrapper gs-dashboard-outlet">
        <div class="gs-deposit-section">
          <div class="gs-deposit-title d-flex align-items-center">
            <a href="{{ url()->previous() }}" class="back-btn">
              <i class="fa-solid fa-arrow-left-long"></i>
            </a>

            <h3>@lang('Reward Point')</h3>
          </div>
          <div class="deposit-area">
            <div class="deposit-area-title">
              <h4>@lang('Convert Point')</h4>
            </div>
            <form action="{{ route('user-reward-convert-submit') }}" method="POST">
              @csrf
              <div class="form-group">
                <label for="c_point">@lang('Current Point') ({{ $gs->reward_point }} @lang('Reward Point To') ${{
                  $gs->reward_dolar }})
                </label>
                <input type="text" class="form-control" id="c_point" placeholder="{{ Auth::user()->reward }}" disabled>

                <label for="reward_point">@lang('Reward Amount')</label>
                <input type="text" class="form-control" id="reward" name="reward_point"
                  placeholder="@lang('Reward Point')">

                <label for="convert_total">@lang('Convert Total')</label>
                <div class="input-with-addon">
                  <input type="text" class="form-control" id="convert_total" placeholder="@lang('Convert Total')"
                    disabled>
                  <span class="currency-addon">@lang('USD')</span>
                </div>
                <div class="multi-btn d-flex gap-4">
                  <button type="button" id="check" class="template-btn dark-btn flex-grow-1">
                    @lang('Check')
                  </button>

                  <button type="submit" class="template-btn flex-grow-1">
                    @lang('Convert')
                  </button>
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
@section('script')


<script type="text/javascript">

  $(document).on('click', '#check', function () {
    let point = parseInt($('#reward').val());
    if (!isNaN(point)) {
      if (point < '{{$gs->reward_point}}') {
        toastr.error('Minimum Convert Point is {{$gs->reward_point}}');
      } else if (point > '{{$user->reward}}') {
        toastr.error('Your reward point is ' + '{{$user->reward}}');
      } else {
        let amount = (point / '{{$gs->reward_point}}') * '{{$gs->reward_dolar}}';
        $('#convert_total').val(amount);
      }
    }
  })

</script>

@endsection