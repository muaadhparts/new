@extends('layouts.front')
@section('content')
<div class="gs-user-panel-review wow-replaced" data-wow-delay=".1s">
    <div class="container">
        <div class="d-flex">
            <!-- sidebar -->
            @include('includes.rider.sidebar')
            <!-- main content -->
            <div class="gs-dashboard-user-content-wrapper gs-dashboard-outlet">
                <div class="gs-deposit-section">
                    <div class="d-flex align-items-center gap-4">
                        <a href="{{ route('rider-wwt-index') }}" class="back-btn">
                            <i class="fa-solid fa-arrow-left-long"></i>
                        </a>

                        <h3>@lang('My Withdraws')</h3>
                    </div>
                    <div class="deposit-area">
                        <div class="deposit-area-title">
                            <h4>@lang('Withdraw Now')</h4>
                        </div>
                        <form action="{{route('rider-wwt-store')}}" method="POST">
                            @csrf
                            <div class="form-group">
                                <label for="c_balance">@lang('Current Balance')</label>
                                <input type="text" class="form-control" id="c_balance"
                                    placeholder="{{ App\Models\Product::vendorConvertPrice(Auth::user()->affilate_income) }}"
                                    disabled>

                                <label for="w_method">@lang('Withdraw Method *')</label>
                                <div class="dropdown-container">
                                    <select class="form-control nice-select form__control" name="methods"
                                        id="withmethod" required>

                                        <option value="">{{ __('Select Withdraw Method') }}</option>
                                        <option value="Paypal">{{ __('Paypal') }}</option>
                                        <option value="Skrill">{{ __('Skrill') }}</option>
                                        <option value="Payoneer">{{ __('Payoneer') }}</option>
                                        <option value="Bank">{{ __('Bank') }}</option>
                                        <!-- Add more options here if needed -->
                                    </select>
                                </div>

                                <div>
                                    <label for="w_amount">@lang('Withdraw Amount *')</label>
                                    <input type="text" class="form-control" id="w_amount" name="amount"
                                        placeholder="@lang('Withdraw Amount')" required>
                                </div>


                                <div class="" id="paypal" style="display: none;">
                                    <div>
                                        <label for="name">{{ __('Enter Account Email') }} *</label>
                                        <input name="acc_email" placeholder="{{ __('Enter Account Email') }}"
                                            class="form-control" value="" type="email">
                                    </div>
                                </div>

                                <div id="bank" style="display: none;">
                                    <div>
                                        <label for="name">{{ __('Enter IBAN/Account No') }}
                                            *
                                        </label>
                                        <div class="">
                                            <input name="iban" value="" placeholder="{{ __('Enter IBAN/Account No') }}"
                                                class="form-control" type="text">
                                        </div>
                                    </div>
                                    <div>
                                        <label for="name">{{ __('Enter Account Name') }} *
                                        </label>
                                        <div class="">
                                            <input name="acc_name" value="" placeholder="{{ __('Enter Account Name') }}"
                                                class="form-control" type="text">
                                        </div>
                                    </div>
                                    <div>
                                        <label for="name">{{ __('Enter Address') }} *
                                        </label>
                                        <div class="">
                                            <input name="address" value="" placeholder="{{ __('Enter Address') }}"
                                                class="form-control" type="text">
                                        </div>
                                    </div>
                                    <div>
                                        <label for="name">{{ __('Enter Swift Code') }} *
                                        </label>
                                        <div class="">
                                            <input name="swift" value="" placeholder="{{ __('Enter Swift Code') }}"
                                                class="form-control" type="text">
                                        </div>
                                    </div>
                                </div>




                                <label for="additional">@lang('Additional Reference(Optional)')</label>
                                <textarea class="form-control" id="additional"
                                    placeholder="@lang('Additional Reference(Optional)')"
                                    style="height: 122px"></textarea>
                                <p class="withdraw-fee">
                                    @lang('Withdraw Fee') {{ $sign->sign }}{{ $gs->withdraw_fee }} @lang('and')
                                    {{ $gs->withdraw_charge }}% @lang('will deduct from your account.')
                                </p>

                                <button type="submit" class="template-btn btn-forms">
                                    @lang('Submit')
                                </button>
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
    (function ($) {
        "use strict";

        $("#withmethod").change(function () {
            var method = $(this).val();
            if (method == "Bank") {

                $("#bank").show();
                $("#bank").find('input, select').attr('required', true);

                $("#paypal").hide();
                $("#paypal").find('input').attr('required', false);

            }
            if (method != "Bank") {
                $("#bank").hide();
                $("#bank").find('input, select').attr('required', false);

                $("#paypal").show();
                $("#paypal").find('input').attr('required', true);
            }
            if (method == "") {
                $("#bank").hide();
                $("#paypal").hide();
            }

        })

    })(jQuery);
</script>
@endsection
