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

                        <div class="gs-deposit-title d-flex align-items-center gap-4 ms-0 mb-5">
                            <a href="{{ route('user-package') }}" class="back-btn">
                                <i class="fa-solid fa-arrow-left-long"></i>
                            </a>
                            <h4>@lang('Plans Details')</h4>
                        </div>

                        <div class="row gy-4">
                            <div class="deposit-area vendor-deposit-area mx-auto">

                                <form id="subscribe-form" class="pay-form"
                                    action="{{ $subs->price == 0 ? route('user-vendor-request-submit') : '' }}"
                                    method="POST">
                                    @csrf
                                    <div>
                                        <ul class="pricing-details-extra-ul">
                                            <li>
                                                <span class="title">@lang('Plan:')</span>
                                                <span class="content">{{ $subs->title }}</span>
                                            </li>
                                            <li>
                                                <span class="title">@lang('Price:') </span>
                                                <span
                                                    class="content">{{ round($subs->price * $curr->value, 2) }}{{ $curr->sign }}</span>
                                            </li>
                                            <li>
                                                <span class="title">@lang('Durations:') </span>
                                                <span class="content">{{ $subs->days }} Day(s)</span>
                                            </li>
                                            <li>
                                                <span class="title">@lang('Product(s) Allowed:') </span>
                                                <span
                                                    class="content">{{ $subs->allowed_products == 0 ? 'Unlimited' : $subs->allowed_products }}</span>
                                            </li>






                                            @if (!empty($package))
                                                @if ($package->subscription_id != $subs->id)
                                                    <li>
                                                        <span class="title">@lang('Note'): </span>
                                                        <span class="content">@lang('Your Previous Plan will be deactivated!')</span>
                                                    </li>
                                                @endif
                                            @endif
                                        </ul>
                                    </div>

                                    <input type="hidden" name="subs_id" value="{{ $subs->id }}">

                                    @if ($user->is_vendor == 0)
                                        <div class="form-group mb-4">
                                            <label for="shop_name">@lang('Shop Name')</label>
                                            <input type="text" class="form-control" id="shop_name" name="shop_name"
                                                placeholder="@lang('Shop Name')" required>
                                        </div>


                                        <div class="form-group mb-4">
                                            <label for="owner_name">@lang('Owner Name')</label>
                                            <input type="text" class="form-control" id="owner_name" name="owner_name"
                                                placeholder="@lang('Owner Name')" required>
                                        </div>

                                        <div class="form-group mb-4">
                                            <label for="shop_number">@lang('Shop Number')</label>
                                            <input type="text" class="form-control" id="shop_number" name="shop_number"
                                                placeholder="@lang('Shop Number')" required>
                                        </div>

                                        <div class="form-group mb-4">
                                            <label for="shop_address">@lang('Shop Address')</label>
                                            <input type="text" class="form-control" id="shop_address" name="shop_address"
                                                placeholder="@lang('Shop Address')" required>
                                        </div>


                                        <div class="form-group mb-4">
                                            <label for="reg_number">@lang('Registration Number')
                                                <small>{{ __('(Optional)') }}</small></label>
                                            <input type="text" class="form-control" id="reg_number" name="reg_number"
                                                placeholder="@lang('Registration Number')" required>
                                        </div>

                                        <div class="form-group mb-4">
                                            <label for="shop_message">@lang('Message')
                                                <small>{{ __('(Optional)') }}</small></label>
                                            <textarea type="text" class="form-control" id="shop_message" name="shop_message" placeholder="@lang('Message')"
                                                ></textarea>
                                        </div>
                                    @endif

                                    @if ($subs->price != 0)
                                        <div class="form-group mb-4">
                                            <label>Select Payment Method</label>
                                            <div class="dropdown-container mb-4">
                                                <select class="form-control nice-select form__control" id="method">
                                                    <option value="" data-form="" data-show="no" data-val=""
                                                        data-href="">{{ __('Select an option') }}</option>
                                                    @foreach ($gateway as $paydata)
                                                        @if ($paydata->type == 'manual')
                                                            <option value="{{ $paydata->title }}"
                                                                data-form="{{ $paydata->showSubscriptionLink() }}"
                                                                data-show="{{ $paydata->showForm() }}"
                                                                data-href="{{ route('user.load.payment', ['slug1' => $paydata->showKeyword(), 'slug2' => $paydata->id]) }}"
                                                                data-val="{{ $paydata->title }}">
                                                                {{ $paydata->title }}
                                                            </option>
                                                        @else
                                                            <option value="{{ $paydata->name }}"
                                                                data-form="{{ $paydata->showSubscriptionLink() }}"
                                                                data-show="{{ $paydata->showForm() }}"
                                                                data-href="{{ route('user.load.payment', ['slug1' => $paydata->showKeyword(), 'slug2' => $paydata->id]) }}"
                                                                data-val="{{ $paydata->keyword }}">
                                                                {{ $paydata->name }}
                                                            </option>
                                                        @endif
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="py-2"></div>

                                        </div>


                                        <div id="payments" class="d-none">

                                        </div>
                                    @endif



                                    <input type="hidden" id="ck" value="0">
                                    <input type="hidden" name="sub" id="sub" value="0">

                                    <button type="submit" id="final-btn" class="template-btn btn-forms w-100 mt-4">
                                        Submit
                                    </button>
                                </form>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection


@section('script')
    <script type="text/javascript" src="{{ asset('assets/front/js/payvalid.js') }}"></script>
    <script type="text/javascript" src="{{ asset('assets/front/js/paymin.js') }}"></script>
    <script type="text/javascript" src="https://js.stripe.com/v2/"></script>
    <script type="text/javascript" src="{{ asset('assets/front/js/payform.js') }}"></script>
    <script src="https://sdk.mercadopago.com/js/v2"></script>
    <script src="https://js.paystack.co/v1/inline.js"></script>
    <script type="text/javascript">
        (function($) {
            "use strict";

            $('#method').on('change', function() {
                var val = $(this).find(':selected').attr('data-val');
                var form = $(this).find(':selected').attr('data-form');
                var show = $(this).find(':selected').attr('data-show');
                var href = $(this).find(':selected').attr('data-href');

                if (show == "yes") {
                    $('#payments').removeClass('d-none');
                } else {
                    $('#payments').addClass('d-none');
                }

                if (val == 'paystack') {
                    $('.pay-form').prop('id', 'paystack');
                } else if (val == 'voguepay') {
                    $('.pay-form').prop('id', 'voguepay');
                } else if (val == 'mercadopago') {
                    $('.pay-form').prop('id', 'mercadopago');
                } else if (val == '2checkout') {
                    $('.pay-form').prop('id', 'twocheckout');
                } else {
                    $('.pay-form').prop('id', 'subscribe-form');
                }


                $('#payments').load(href);
                $('.pay-form').attr('action', form);
            });


            $(document).on('submit', '#paystack', function() {
                var val = $('#sub').val();
                if (val == 0) {
                    if ($('#shop-name').length > 0) {

                        $.get('{{ route('user.shop.check') . '?shop_name=' }}' + $('#shop-name').val(),
                            function(
                                data, status) {
                                if ((data.errors)) {

                                    $('.alert-danger').show();
                                    $('.alert-danger ul').html('');
                                    for (var error in data.errors) {
                                        $('.alert-danger ul').append('<li>' + data.errors[error] + '</li>');
                                        $('#sub').val('0');
                                        $('#ck').val('1');
                                    }
                                } else {
                                    $('#ck').val('0');
                                }
                            });

                    }

                    setTimeout(function() {
                        if ($('#ck').val() == '0') {
                            $('#paystack').removeAttr('id');
                            var total = {{ $subs->price * $curr->value }};
                            total = Math.round(total);

                            var handler = PaystackPop.setup({
                                key: '{{ $paystack['key'] }}',
                                email: '{{ Auth::user()->email }}',
                                amount: total * 100,
                                currency: "{{ $curr->name }}",
                                ref: '' + Math.floor((Math.random() * 1000000000) + 1),
                                callback: function(response) {
                                    $('#ref_id').val(response.reference);
                                    $('#sub').val('1');
                                    $('#final-btn').click();
                                },
                                onClose: function() {
                                    window.location.reload();
                                }
                            });
                            handler.openIframe();
                            return false;
                        }

                    }, 1000);
                    return false;
                } else {
                    return true;
                }
            });

        })(jQuery);
    </script>
@endsection
