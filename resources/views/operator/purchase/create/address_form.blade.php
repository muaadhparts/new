{{-- Data pre-computed in PurchaseCreateController (DATA_FLOW_POLICY) --}}
@if (!empty($orderAddress))
<div class="row mt-2">
  <div class="col col-md-4 col-sm-6">
    <label for="name">Name *</label>
    <input type="text" class="form-control" required name="customer_name" id="name" value="{{ $orderAddress['customer_name'] ?? '' }}" placeholder="Name">
  </div>
  <div class="col col-md-4 col-sm-6">
    <label for="email">Email *</label>
    <input type="text" class="form-control" required name="customer_email" id="email" placeholder="Email" value="{{ $orderAddress['customer_email'] ?? '' }}">
  </div>
  <div class="col col-md-4 col-sm-6">
    <label for="phone">Phone *</label>
    <input type="text" class="form-control" required name="customer_phone" id="phone" placeholder="Phone" value="{{ $orderAddress['customer_phone'] ?? '' }}">
  </div>
  <div class="col col-md-12 col-sm-12">
    <label for="customer_address">Address *</label>
    <input type="text" class="form-control" required name="customer_address" id="customer_address" placeholder="Address" value="{{ $orderAddress['customer_address'] ?? '' }}">
  </div>
</div>
<div class="row">
  <div class="col col-md-6 col-sm-6">
    <label for="customer_country">Country * </label>
    <select type="text" class="form-control" name="customer_country" id="customer_country" required>
      {!! $countriesHtml !!}
  </select>
  </div>
  <div class="col col-md-6 col-sm-6">
    <label for="customer_city">City</label>
    <input type="text" class="form-control" name="customer_city" id="customer_city" placeholder="City" value="{{ $orderAddress['customer_city'] ?? '' }}">
  </div>
  <div class="col col-md-6 col-sm-6">
    <label for="customer_state">State</label>
    <input type="text" class="form-control" name="customer_state" id="customer_state" placeholder="State" value="{{ $orderAddress['customer_state'] ?? '' }}">
  </div>

  <div class="col col-md-6 col-sm-6">
    <label for="post_code">Postal Code</label>
    <input type="text" class="form-control" name="customer_zip" id="post_code" placeholder="Postal Code" value="{{ $orderAddress['customer_zip'] ?? '' }}">
  </div>

</div>

@else

{{-- $isUser pre-passed from controller (DATA_FLOW_POLICY) --}}
@if (($isUser ?? false) == 1)
  <div class="row mt-2">
    <div class="col col-md-4 col-sm-6">
      <label for="name">Name *</label>
      <input type="text" class="form-control" required name="customer_name" id="name" value="{{$user['name']}}" placeholder="Name">
    </div>
    <div class="col col-md-4 col-sm-6">
      <label for="email">Email *</label>
      <input type="text" class="form-control" required name="customer_email" id="email" placeholder="Email" value="{{$user['email']}}">
    </div>
    <div class="col col-md-4 col-sm-6">
      <label for="phone">Phone *</label>
      <input type="text" class="form-control" required name="customer_phone" id="phone" placeholder="Phone" value="{{$user['phone']}}">
    </div>
    <div class="col col-md-12 col-sm-12">
      <label for="customer_address">Address *</label>
      <input type="text" class="form-control" required name="customer_address" id="customer_address" placeholder="Address" value="{{$user['address']}}">
    </div>
  </div>
  <div class="row">
    <div class="col col-md-6 col-sm-6">
      <label for="customer_country">Country * </label>
      <select type="text" class="form-control" name="customer_country" id="customer_country" required>
        {!! $countriesHtml !!}
    </select>
    </div>
    <div class="col col-md-6 col-sm-6">
      <label for="customer_city">City</label>
      <input type="text" class="form-control" name="customer_city" id="customer_city" placeholder="City" value="{{$user['city']}}">
    </div>
    <div class="col col-md-6 col-sm-6">
      <label for="customer_state">State</label>
      <input type="text" class="form-control" name="customer_state" id="customer_state" placeholder="State" value="{{$user['state']}}">
    </div>
   
    <div class="col col-md-6 col-sm-6">
      <label for="post_code">Postal Code</label>
      <input type="text" class="form-control" name="customer_zip" id="post_code" placeholder="Postal Code" value="{{$user['zip']}}">
    </div>
 
  </div>
@else

  <div class="row mt-2">
    <div class="col col-md-4 col-sm-6">
      <label for="name">Name *</label>
      <input type="text" class="form-control" required name="customer_name" id="name" placeholder="Name">
    </div>
    <div class="col col-md-4 col-sm-6">
      <label for="email">Email *</label>
      <input type="text" class="form-control" required name="customer_email" id="email" placeholder="Email">
    </div>
    <div class="col col-md-4 col-sm-6">
      <label for="phone">Phone *</label>
      <input type="text" class="form-control" required name="customer_phone" id="phone" placeholder="Email">
    </div>
    <div class="col col-md-12 col-sm-12">
      <label for="customer_address">Address *</label>
      <input type="text" class="form-control" required name="customer_address" id="customer_address" placeholder="Address">
    </div>
  </div>
  <div class="row">
    <div class="col col-md-6 col-sm-6">
      <label for="customer_country">Country * </label>
      <select  class="form-control" name="customer_country" id="customer_country" required>
        {!! $countriesHtml !!}
    </select>
    </div>
    <div class="col col-md-6 col-sm-6">
      <label for="customer_city">City</label>
      <input type="text" class="form-control" name="customer_city" id="customer_city" placeholder="City">
    </div>
    <div class="col col-md-6 col-sm-6">
      <label for="customer_state">State</label>
      <input type="text" class="form-control" name="customer_state" id="customer_state" placeholder="State">
    </div>
   
    <div class="col col-md-6 col-sm-6">
      <label for="post_code">Postal Code</label>
      <input type="text" class="form-control" name="customer_zip" id="post_code" placeholder="Postal Code">
    </div>
  </div>
@endif
    
@endif
