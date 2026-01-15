@extends('layouts.operator')

@section('styles')
<link href="{{asset('assets/operator/css/jquery-ui.css')}}" rel="stylesheet" type="text/css">
@endsection

@section('content')

            <div class="content-area">

              <div class="mr-breadcrumb">
                <div class="row">
                  <div class="col-lg-12">
                      <h4 class="heading">{{ __('Add New Discount Code') }} <a class="add-btn" href="{{route('operator-discount-code-index')}}"><i class="fas fa-arrow-left"></i> {{ __('Back') }}</a></h4>
                      <ul class="links">
                        <li>
                          <a href="{{ route('operator.dashboard') }}">{{ __('Dashboard') }} </a>
                        </li>
                        <li>
                          <a href="{{ route('operator-discount-code-index') }}">{{ __('Discount Codes') }}</a>
                        </li>
                        <li>
                          <a href="{{ route('operator-discount-code-create') }}">{{ __('Add New Discount Code') }}</a>
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
                        @include('alerts.operator.form-both')
                      <form id="muaadhform" action="{{route('operator-discount-code-create')}}" method="POST" enctype="multipart/form-data">
                        {{csrf_field()}}

                        <div class="row">
                          <div class="col-lg-4">
                            <div class="left-area">
                                <h4 class="heading">{{ __('Code') }} *</h4>
                                <p class="sub-heading">{{ __('(In Any Language)') }}</p>
                            </div>
                          </div>
                          <div class="col-lg-7">
                            <input type="text" class="form-control" name="code" placeholder="{{ __('Enter Code') }}" required="" value="">
                          </div>
                        </div>

                        <div class="row">
                          <div class="col-lg-4">
                            <div class="left-area">
                                <h4 class="heading">{{ __('Merchant') }}</h4>
                                <p class="sub-heading">{{ __('(Optional)') }}</p>
                            </div>
                          </div>
                          <div class="col-lg-7">
                              <select name="user_id">
                                  <option value="">{{ __('All Merchants') }}</option>
                                  @foreach($merchants as $merchant)
                                    <option value="{{ $merchant->id }}">{{ $merchant->shop_name ?? $merchant->name }}</option>
                                  @endforeach
                              </select>
                          </div>
                        </div>

                        <div class="row">
                          <div class="col-lg-4">
                            <div class="left-area">
                                <h4 class="heading">{{ __('Type') }} *</h4>
                            </div>
                          </div>
                          <div class="col-lg-7">
                              <select id="type" name="type" required="">
                                <option value="">{{ __('Choose a type') }}</option>
                                <option value="0">{{ __('Discount By Percentage') }}</option>
                                <option value="1">{{ __('Discount By Amount') }}</option>
                              </select>
                          </div>
                        </div>

                        <div class="row" id="price-row" style="display: none;">
                          <div class="col-lg-4">
                            <div class="left-area">
                                <h4 class="heading" id="price-label"></h4>
                            </div>
                          </div>
                          <div class="col-lg-3">
                            <input type="number" step="0.01" min="0" class="form-control less-width" name="price" placeholder="" required="" value=""><span id="price-suffix"></span>
                          </div>
                        </div>

                        <div class="row">
                          <div class="col-lg-4">
                            <div class="left-area">
                                <h4 class="heading">{{ __('Quantity') }} *</h4>
                            </div>
                          </div>
                          <div class="col-lg-7">
                              <select id="times-type" required="">
                                <option value="0">{{ __('Unlimited') }}</option>
                                <option value="1">{{ __('Limited') }}</option>
                              </select>
                          </div>
                        </div>

                        <div class="row" id="times-row" style="display: none;">
                          <div class="col-lg-4">
                            <div class="left-area">
                                <h4 class="heading">{{ __('Usage Limit') }} *</h4>
                            </div>
                          </div>
                          <div class="col-lg-7">
                            <input type="number" min="1" class="form-control less-width" name="times" placeholder="{{ __('Enter Usage Limit') }}" value="">
                          </div>
                        </div>

                        <div class="row">
                          <div class="col-lg-4">
                            <div class="left-area">
                                <h4 class="heading">{{ __('Start Date') }} *</h4>
                            </div>
                          </div>
                          <div class="col-lg-7">
                            <input type="text" class="form-control" name="start_date" autocomplete="off" id="from" placeholder="{{ __('Select a date') }}" required="" value="">
                          </div>
                        </div>

                        <div class="row">
                          <div class="col-lg-4">
                            <div class="left-area">
                                <h4 class="heading">{{ __('End Date') }} *</h4>
                            </div>
                          </div>
                          <div class="col-lg-7">
                            <input type="text" class="form-control" name="end_date" autocomplete="off" id="to" placeholder="{{ __('Select a date') }}" required="" value="">
                          </div>
                        </div>

                        <br>
                        <div class="row">
                          <div class="col-lg-4">
                            <div class="left-area">

                            </div>
                          </div>
                          <div class="col-lg-7">
                            <button class="btn btn-primary" type="submit">{{ __('Create Discount Code') }}</button>
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

@section('scripts')

<script type="text/javascript">

// Discount Type - عرض/إخفاء حقل السعر
$('#type').on('change', function() {
    var val = $(this).val();
    var priceRow = $('#price-row');

    if (val === "") {
        priceRow.hide();
    } else {
        if (val == 0) {
            $('#price-label').html('{{ __('Percentage') }} *');
            $('input[name="price"]').attr("placeholder", "{{ __('Enter Percentage') }}");
            $('#price-suffix').html('%');
        } else {
            $('#price-label').html('{{ __('Amount') }} *');
            $('input[name="price"]').attr("placeholder", "{{ __('Enter Amount') }}");
            $('#price-suffix').html('{{ $curr->sign }}');
        }
        priceRow.css('display', 'flex');
    }
});

// Quantity Type - عرض/إخفاء حقل عدد الاستخدامات
$('#times-type').on('change', function() {
    var val = $(this).val();
    var timesRow = $('#times-row');

    if (val == 1) {
        timesRow.css('display', 'flex');
        $('input[name="times"]').prop('required', true);
    } else {
        timesRow.hide();
        $('input[name="times"]').val('').prop('required', false);
    }
});

// Date Picker
var dateToday = new Date();
var dates = $("#from, #to").datepicker({
    defaultDate: "+1w",
    changeMonth: true,
    changeYear: true,
    minDate: dateToday,
    dateFormat: 'yy-mm-dd',
    onSelect: function(selectedDate) {
        var option = this.id == "from" ? "minDate" : "maxDate",
            instance = $(this).data("datepicker"),
            date = $.datepicker.parseDate(instance.settings.dateFormat || $.datepicker._defaults.dateFormat, selectedDate, instance.settings);
        dates.not(this).datepicker("option", option, date);
    }
});

</script>

@endsection
