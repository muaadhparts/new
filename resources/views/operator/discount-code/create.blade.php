@extends('layouts.operator')

@section('styles')

<link href="{{asset('assets/admin/css/jquery-ui.css')}}" rel="stylesheet" type="text/css">

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
                            </div>
                          </div>
                          <div class="col-lg-7">
                              <select name="user_id">
                                  <option value="">{{ __('Select Merchant') }}</option>
                                  @foreach($merchants as $merchant)
                                    <option value="{{ $merchant->id }}">{{ $merchant->shop_name ?? $merchant->name }}</option>
                                  @endforeach
                              </select>
                          </div>
                        </div>

                        <div class="row">
                          <div class="col-lg-4">
                            <div class="left-area">
                                <h4 class="heading">{{ __('Allow CatalogItem Type') }}*</h4>
                            </div>
                          </div>
                          <div class="col-lg-7">
                              <select  name="apply_to" required="" id="select_apply_to">
                                  <option value="" selected>{{ __('Select Type') }}</option>
                                  <option value="category">{{ __('Category') }}</option>
                                  <option value="sub_category">{{ __('Sub Category') }}</option>
                                  <option value="child_category">{{ __('Child Category') }}</option>

                                </select>
                          </div>
                        </div>

                        <div class="row d-none" id="category">
                          <div class="col-lg-4">
                            <div class="left-area">
                                <h4 class="heading">{{ __('Category') }}*</h4>
                            </div>
                          </div>
                          <div class="col-lg-7">
                              <select  name="category">
                                  <option value="">{{ __('Select Category') }}</option>
                                    @foreach($categories as $cat)
                                      <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                    @endforeach
                                </select>
                          </div>
                        </div>

                        <div class="row d-none" id="sub_category">
                          <div class="col-lg-4">
                            <div class="left-area">
                                <h4 class="heading">{{ __('Subcategory') }}*</h4>
                            </div>
                          </div>
                          <div class="col-lg-7">
                              <select  name="sub_category" >
                                  <option value="">{{ __('Select Subcategory') }}</option>
                                    @foreach($sub_categories as $scat)
                                      <option value="{{ $scat->id }}">{{ $scat->name }}</option>
                                    @endforeach
                                </select>
                          </div>
                        </div>

                        <div class="row d-none" id="child_category">
                          <div class="col-lg-4">
                            <div class="left-area">
                                <h4 class="heading">{{ __('Child Category') }}*</h4>
                            </div>
                          </div>
                          <div class="col-lg-7">
                              <select  name="child_category" >
                                  <option value="">{{ __('Select Child Category') }}</option>
                                    @foreach($child_categories as $ccat)
                                      <option value="{{ $ccat->id }}">{{ $ccat->name }}</option>
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
                              <select id="type" name="type" >
                                <option value="">{{ __('Choose a type') }}</option>
                                <option value="0">{{ __('Discount By Percentage') }}</option>
                                <option value="1">{{ __('Discount By Amount') }}</option>
                              </select>
                          </div>
                        </div>

                        <div class="row hidden">
                          <div class="col-lg-4">
                            <div class="left-area">
                                <h4 class="heading"></h4>
                            </div>
                          </div>
                          <div class="col-lg-3">
                            <input type="text" class="form-control less-width" name="price" placeholder="" required="" value=""><span></span>
                          </div>
                        </div>

                        <div class="row">
                          <div class="col-lg-4">
                            <div class="left-area">
                                <h4 class="heading">{{ __('Quantity') }} *</h4>
                            </div>
                          </div>
                          <div class="col-lg-7">
                              <select id="times" required="">
                                <option value="0">{{ __('Unlimited') }}</option>
                                <option value="1">{{ __('Limited') }}</option>
                              </select>
                          </div>
                        </div>

                        <div class="row hidden">
                          <div class="col-lg-4">
                            <div class="left-area">
                                <h4 class="heading">{{ __('Value') }} *</h4>
                            </div>
                          </div>
                          <div class="col-lg-7">
                            <input type="text" class="form-control less-width" name="times" placeholder="{{ __('Enter Value') }}" value=""><span></span>
                          </div>
                        </div>


                        <div class="row">
                          <div class="col-lg-4">
                            <div class="left-area">
                                <h4 class="heading">{{ __('Start Date') }} *</h4>
                                <p class="sub-heading">{{ __('(In Any Language)') }}</p>
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
                                <p class="sub-heading">{{ __('(In Any Language)') }}</p>
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

{{-- Discount Type --}}

    $('#type').on('change', function() {
      var val = $(this).val();
      var selector = $(this).parent().parent().next();
      if(val == "")
      {
        selector.hide();
      }
      else {
        if(val == 0)
        {
          selector.find('.heading').html('{{ __('Percentage') }} *');
          selector.find('input').attr("placeholder", "{{ __('Enter Percentage') }}").next().html('%');
          selector.css('display','flex');
        }
        else if(val == 1){
          selector.find('.heading').html('{{ __('Amount') }} *');
          selector.find('input').attr("placeholder", "{{ __('Enter Amount') }}").next().html('$');
          selector.css('display','flex');
        }
      }
    });


{{-- Discount Qty --}}

  $(document).on("change", "#times" , function(){
    var val = $(this).val();
    var selector = $(this).parent().parent().next();
    if(val == 1){
    selector.css('display','flex');
    }
    else{
    selector.find('input').val("");
    selector.hide();    
    }
});

</script>

<script type="text/javascript">
    var dateToday = new Date();
    var dates =  $( "#from,#to" ).datepicker({
        defaultDate: "+1w",
        changeMonth: true,
        changeYear: true,
        minDate: dateToday,
        onSelect: function(selectedDate) {
        var option = this.id == "from" ? "minDate" : "maxDate",
          instance = $(this).data("datepicker"),
          date = $.datepicker.parseDate(instance.settings.dateFormat || $.datepicker._defaults.dateFormat, selectedDate, instance.settings);
          dates.not(this).datepicker("option", option, date);
    }
});



$(document).on('change','#select_apply_to',function(){
  let apply_to = $(this).val();
  if(apply_to == 'category'){
    $('#category').removeClass('d-none');
    $('#child_category').addClass('d-none');
    $('#sub_category').addClass('d-none');
  }else if(apply_to =='sub_category'){
    $('#category').addClass('d-none');
    $('#child_category').addClass('d-none');
    $('#sub_category').removeClass('d-none');
  }else{
    $('#category').addClass('d-none');
    $('#child_category').removeClass('d-none');
    $('#sub_category').addClass('d-none');
  }
})

</script>

@endsection

