@extends('layouts.load')

@section('content')
<div class="content-area">
  <div class="add-catalogItem-content1">
    <div class="row">
      <div class="col-lg-12">
        <div class="catalogItem-description">
          <div class="body-area">
            @include('alerts.operator.form-error')
            <form id="muaadhformdata" action="{{route('operator-membership-plan-create')}}" method="POST" enctype="multipart/form-data">
              {{csrf_field()}}
              <div class="row">
                <div class="col-lg-4">
                  <div class="left-area"><h4 class="heading">{{ __("Title") }} *</h4></div>
                </div>
                <div class="col-lg-7">
                  <input type="text" class="form-control" name="title" placeholder="{{ __("Enter Plan Title") }}" required="" value="">
                </div>
              </div>
              <div class="row">
                <div class="col-lg-4">
                  <div class="left-area"><h4 class="heading">{{ __("Cost") }} *</h4></div>
                </div>
                <div class="col-lg-7">
                  <input type="text" class="form-control" name="price" placeholder="{{ __("Enter Plan Cost") }}" required="" value="">
                </div>
              </div>
              <div class="row">
                <div class="col-lg-4">
                  <div class="left-area"><h4 class="heading">{{ __("Days") }} *</h4></div>
                </div>
                <div class="col-lg-7">
                  <input type="text" class="form-control" name="days" placeholder="{{ __("Enter Plan Days") }}" required="" value="">
                </div>
              </div>
              <div class="row">
                <div class="col-lg-4">
                  <div class="left-area"><h4 class="heading">{{ __("CatalogItem Limitations") }}*</h4></div>
                </div>
                <div class="col-lg-7">
                  <select id="limit" name="limit" required="">
                    <option value="">{{ __("Select an Option") }}</option>
                    <option value="0">{{ __("Unlimited") }}</option>
                    <option value="1">{{ __("Limited") }}</option>
                  </select>
                </div>
              </div>
              <div class="showbox" id="limits">
                <div class="row">
                  <div class="col-lg-4">
                    <div class="left-area"><h4 class="heading">{{ __("Allowed CatalogItems") }} *</h4></div>
                  </div>
                  <div class="col-lg-7">
                    <input type="number" min="1" class="form-control" id="allowed_catalogitems" name="allowed_catalogitems" placeholder="{{ __("Enter Allowed CatalogItems") }}" value="1">
                  </div>
                </div>
              </div>
              <div class="row">
                <div class="col-lg-4">
                  <div class="left-area"><h4 class="heading">{{ __("Details") }} *</h4></div>
                </div>
                <div class="col-lg-7">
                  <textarea class="nic-edit" name="details" placeholder="{{ __("Details") }}"></textarea>
                </div>
              </div>
              <div class="row">
                <div class="col-lg-4"><div class="left-area"></div></div>
                <div class="col-lg-7">
                  <button class="btn btn-primary" type="submit">{{ __("Create Plan") }}</button>
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
(function($) {
  "use strict";
  $("#limit").on('change',function() {
    val = $(this).val();
    if(val == 1) {
      $("#limits").show();
      $("#allowed_catalogitems").prop("required", true);
    } else {
      $("#limits").hide();
      $("#allowed_catalogitems").prop("required", false);
    }
  });
})(jQuery);
</script>
@endsection
