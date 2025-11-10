@extends('layouts.vendor')

@section('content')
    <!-- outlet start  -->
    <div class="gs-vendor-outlet">
        <!-- breadcrumb start  -->
        <div class="gs-vendor-breadcrumb has-mb">

                <div class="gs-deposit-title d-flex align-items-center gap-4 ms-0">
                    <a href="{{route("vendor-wt-index")}}" class="back-btn">
                        <i class="fa-solid fa-arrow-left-long"></i>
                    </a>
                    <h4>@lang('Withdraw Now')</h4>
                </div>

            <ul class="breadcrumb-menu">
                <li>
                    <a href="{{ route('vendor.dashboard') }}" class="text-capitalize">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                            stroke="#4C3533" class="home-icon-vendor-panel-breadcrumb">
                            <path
                                d="M9 21V13.6C9 13.0399 9 12.7599 9.109 12.546C9.20487 12.3578 9.35785 12.2049 9.54601 12.109C9.75993 12 10.04 12 10.6 12H13.4C13.9601 12 14.2401 12 14.454 12.109C14.6422 12.2049 14.7951 12.3578 14.891 12.546C15 12.7599 15 13.0399 15 13.6V21M2 9.5L11.04 2.72C11.3843 2.46181 11.5564 2.33271 11.7454 2.28294C11.9123 2.23902 12.0877 2.23902 12.2546 2.28295C12.4436 2.33271 12.6157 2.46181 12.96 2.72L22 9.5M4 8V17.8C4 18.9201 4 19.4802 4.21799 19.908C4.40974 20.2843 4.7157 20.5903 5.09202 20.782C5.51985 21 6.0799 21 7.2 21H16.8C17.9201 21 18.4802 21 18.908 20.782C19.2843 20.5903 19.5903 20.2843 19.782 19.908C20 19.4802 20 18.9201 20 17.8V8L13.92 3.44C13.2315 2.92361 12.8872 2.66542 12.5091 2.56589C12.1754 2.47804 11.8246 2.47804 11.4909 2.56589C11.1128 2.66542 10.7685 2.92361 10.08 3.44L4 8Z"
                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </a>
                </li>
                <li>
                    <a href="{{ route('vendor.dashboard') }}" class="text-capitalize">
                        @lang('Dashboard')
                    </a>
                </li>
                <li>
                    <a href="{{ route('vendor-wt-index') }}" class="text-capitalize"> @lang('My Withdraws') </a>
                </li>
                <li>
                    <a href="{{ route('vendor-wt-create') }}" class="text-capitalize"> @lang('Create Withdraw') </a>
                </li>
            </ul>
        </div>

        <div class="deposit-area vendor-deposit-area mx-auto">
            <div class="deposit-area-title">
                <h4>@lang('Withdraw Now')</h4>
            </div>
            <form action="{{route('vendor-wt-store')}}" method="POST">
                @csrf
                <div class="form-group">
                    <label for="c_balance">@lang('Current Balance')</label>
                    <input type="text" class="form-control" id="c_balance"
                        placeholder="{{ App\Models\Product::vendorConvertPrice(Auth::user()->current_balance) }}"
                        disabled />

                    <label for="withmethod">@lang('Withdraw Method')</label>
                    <div class="dropdown-container">
                        <select class="form-control nice-select form__control" name="methods" id="withmethod" required>
                            <option value="">{{ __('Select Withdraw Method') }}</option>
                            <option value="Paypal">{{ __('Paypal') }}</option>
                            <option value="Skrill">{{ __('Skrill') }}</option>
                            <option value="Payoneer">{{ __('Payoneer') }}</option>
                            <option value="Bank">{{ __('Bank') }}</option>
                            <!-- Add more options here if needed -->
                        </select>
                    </div>

                    <label for="amount_amount">@lang('Withdraw Amount')</label>
                    <input type="text" class="form-control" name="amount" id="amount_amount"
                        placeholder="@lang('Withdraw Amount')" />

                    <div id="paypal" style="display: none;">
                        <label for="acc_email">@lang('Enter Account Email')</label>
                        <input type="text" class="form-control" name="acc_email" id="acc_email"
                            placeholder="@lang('Enter Account Email')" />
                    </div>



                    <div id="bank" style="display: none;">
                        <label for="iban">@lang('Enter IBAN/Account No')</label>
                        <input type="text" class="form-control" name="iban" id="iban"
                            placeholder="@lang('Enter IBAN/Account No')" />

                        <label for="acc_name">@lang('Enter Account Name')</label>
                        <input type="text" class="form-control" name="acc_name" id="acc_name"
                            placeholder="@lang('Enter Account Name')" />

                        <label for="address">@lang('Enter Address')</label>
                        <input type="text" class="form-control" name="address" id="address"
                            placeholder="@lang('Enter Address')" />


                        <label for="swift">@lang('Enter Swift Code')</label>
                        <input type="text" class="form-control" name="swift" id="swift"
                            placeholder="@lang('Enter Swift Code')" />

                    </div>



                    <label for="email">@lang('Additional Reference(Optional)')</label>
                    <textarea class="form-control" id="c_balance" placeholder="@lang('Additional Reference(Optional)')" style="height: 122px"></textarea>
                    <p class="withdraw-fee">
                        @lang('Withdraw Fee') {{ $sign->sign }}{{ $gs->withdraw_fee }} and {{ $gs->withdraw_charge }}% @lang('will deduct from your account.')
                    </p>

                    <button type="submit" class="template-btn btn-forms">
                        @lang('Submit')
                    </button>
                </div>
            </form>
        </div>
    </div>
    <!-- outlet end  -->
@endsection


@section('script')
    <script type="text/javascript">
        (function($) {
            "use strict";

            $("#withmethod").change(function() {
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
                    $("#bank").find('input, select').attr('required', false);
                    $("#paypal").find('input').attr('required', false);
                }
            });

        })(jQuery);
    </script>
@endsection
