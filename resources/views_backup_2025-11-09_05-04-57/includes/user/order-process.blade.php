


  @if ($order->status == 'pending')
  <div class="gs-checkout-wrapper">
    <div class="checkout-step-wrapper">
      <span class="line"></span>
      <span class="line-2 d-none"></span>
      <span class="line-3 d-none"></span>
      <div class="single-step active">
        <span class="step-btn">1</span>
        <span class="step-txt">@lang('Order Placed')</span>
      </div>
      <div class="single-step">
        <span class="step-btn">2</span>
        <span class="step-txt">@lang('On Review')</span>
      </div>
      <div class="single-step">
        <span class="step-btn">3</span>
        <span class="step-txt">@lang('On Delivery')</span>
      </div>
      <div class="single-step">
        <span class="step-btn">4</span>
        <span class="step-txt">@lang('Delivered')</span>
      </div>
    </div>
  </div>
@elseif($order->status == 'processing')
<div class="gs-checkout-wrapper">
    <div class="checkout-step-wrapper">
      <span class="line"></span>
      <span class="line-2 d-none"></span>
      <span class="line-3 d-none"></span>
      <div class="single-step active">
        <span class="step-btn">1</span>
        <span class="step-txt">@lang('Order Placed')</span>
      </div>
      <div class="single-step">
        <span class="step-btn active">2</span>
        <span class="step-txt">@lang('On Review')</span>
      </div>
      <div class="single-step">
        <span class="step-btn">3</span>
        <span class="step-txt">@lang('On Delivery')</span>
      </div>
      <div class="single-step">
        <span class="step-btn">4</span>
        <span class="step-txt">@lang('Delivered')</span>
      </div>
    </div>
  </div>
@elseif($order->status == 'on delivery')
<div class="gs-checkout-wrapper">
    <div class="checkout-step-wrapper">
      <span class="line"></span>
      <span class="line-2 d-none"></span>
      <span class="line-3 d-none"></span>
      <div class="single-step active">
        <span class="step-btn">1</span>
        <span class="step-txt">@lang('Order Placed')</span>
      </div>
      <div class="single-step active">
        <span class="step-btn">2</span>
        <span class="step-txt">@lang('On Review')</span>
      </div>
      <div class="single-step active">
        <span class="step-btn">3</span>
        <span class="step-txt">@lang('On Delivery')</span>
      </div>
      <div class="single-step">
        <span class="step-btn">4</span>
        <span class="step-txt">@lang('Delivered')</span>
      </div>
    </div>
  </div>
@elseif($order->status == 'completed')
<div class="gs-checkout-wrapper">
    <div class="checkout-step-wrapper">
      <span class="line"></span>
      <span class="line-2 d-none"></span>
      <span class="line-3 d-none"></span>
      <div class="single-step active">
        <span class="step-btn">1</span>
        <span class="step-txt">@lang('Order Placed')</span>
      </div>
      <div class="single-step active">
        <span class="step-btn">2</span>
        <span class="step-txt">@lang('On Review')</span>
      </div>
      <div class="single-step active">
        <span class="step-btn">3</span>
        <span class="step-txt">@lang('On Delivery')</span>
      </div>
      <div class="single-step active">
        <span class="step-btn">4</span>
        <span class="step-txt">@lang('Delivered')</span>
      </div>
    </div>
  </div>
@endif
