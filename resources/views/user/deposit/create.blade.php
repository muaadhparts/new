@extends('layouts.front')

@section('content')
    <div class="gs-user-panel-review wow-replaced" data-wow-delay=".1s">
        <div class="container">
            <div class="d-flex">
                <!-- sidebar -->
                <div class=" user-dashboard-sidebar">
                    @include('includes.user.sidebar')
                </div>
                <!-- main content -->
                <div class="gs-dashboard-user-content-wrapper gs-dashboard-outlet">
                    <div class="gs-deposit-section">
                        <div class="gs-deposit-title d-flex align-items-center">
                            <a href="{{ route('user-deposit-index') }}" class="back-btn">
                                <i class="fa-solid fa-arrow-left-long"></i>
                            </a>

                            <h3>@lang('Deposit')</h3>
                        </div>
                        <div class="deposit-area">
                            <div class="deposit-area-title">
                                <h4>@lang('Deposit')</h4>
                            </div>
                            <form action="" class="pay-form" id="deposit-form" method="POST">
                                @csrf
                                <div class="form-group">

                                    <label for="c_balance">@lang('Current Balance')</label>
                                    <input type="text" class="form-control" id="c_balance"
                                        placeholder="{{ App\Models\Product::vendorConvertPrice(Auth::user()->balance) }}"
                                        disabled>

                                    <label for="d_amount">@lang('Deposit Amount')</label>
                                    <input type="text" class="form-control" placeholder="@lang('Amount')"
                                        name="amount">

                                    <label for="method">@lang('Select Payment Method')</label>
                                    <div class="dropdown-container">
                                        <select class="form-control nice-select form__control mb-3" name="method"
                                            id="method">
                                            <option value="" data-form="" data-show="no" data-val=""
                                                data-href="">
                                                {{ __('Select an option') }}
                                            </option>
                                            @foreach ($gateway as $paydata)
                                                @if ($paydata->type == 'manual')
                                                    <option value="{{ $paydata->title }}"
                                                        data-form="{{ $paydata->showDepositLink() }}"
                                                        data-show="{{ $paydata->showForm() }}"
                                                        data-href="{{ route('user.load.payment', ['slug1' => $paydata->showKeyword(), 'slug2' => $paydata->id]) }}"
                                                        data-val="{{ $paydata->keyword }}">
                                                        {{ $paydata->title }}
                                                    </option>
                                                @else
                                                    <option value="{{ $paydata->name }}"
                                                        data-form="{{ $paydata->showDepositLink() }}"
                                                        data-show="{{ $paydata->showForm() }}"
                                                        data-href="{{ route('user.load.payment', ['slug1' => $paydata->showKeyword(), 'slug2' => $paydata->id]) }}"
                                                        data-val="{{ $paydata->keyword }}">
                                                        {{ $paydata->name }}
                                                    </option>
                                                @endif
                                            @endforeach
                                            <!-- Add more options here if needed -->
                                        </select>

                                    </div>

                                    <div id="payments" class="d-none ">
                                    </div>
                                    <input type="hidden" name="sub" id="sub" value="0">

                                    <button type="submit" class="template-btn btn-forms">@lang('Submit')</button>

                                </div>
                            </form>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
    <!-- user dashboard wrapper end -->
@endsection



@section('script')
    <script type="text/javascript" src="{{ asset('assets/front/js/payvalid.js') }}"></script>
    <script type="text/javascript" src="{{ asset('assets/front/js/paymin.js') }}"></script>
    <script type="text/javascript" src="{{ asset('assets/front/js/payform.js') }}"></script>

    <script src="https://js.paystack.co/v1/inline.js"></script>

    <script src="//voguepay.com/js/voguepay.js"></script>


    <script type="text/javascript" src="https://js.stripe.com/v2/"></script>

    <script src="https://sdk.mercadopago.com/js/v2"></script>

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
                    $('#amount').prop('name', 'amount');
                } else if (val == 'voguepay') {
                    $('.pay-form').prop('id', 'voguepay');
                    $('#amount').prop('name', 'amount');
                } else if (val == 'mercadopago') {
                    $('.pay-form').prop('id', 'mercadopago');
                    $('#amount').prop('name', 'deposit_amount');
                } else if (val == '2checkout') {
                    $('.pay-form').prop('id', 'twocheckout');
                    $('#amount').prop('name', 'amount');
                } else {
                    $('.pay-form').prop('id', 'deposit-form');
                    $('#amount').prop('name', 'amount');
                }


                $('#payments').load(href);
                $('.pay-form').attr('action', form);
            });


            $(document).on('submit', '#paystack', function() {
                var val = $('#sub').val();
                if (val == 0) {
                    var total = $('#amount').val();
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

                } else {
                    return true;
                }
            });

        })(jQuery);
    </script>
@endsection
