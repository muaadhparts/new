@extends('layouts.front')

@section('content')
    <div class="gs-user-panel-review wow-replaced" data-wow-delay=".1s">
        <div class="container">
            <div class="roww g-0w d-flex">
                <!-- sidebar -->
                @include('includes.user.sidebar')
                <!-- main content -->
                <div class="gs-dashboard-outlet gs-user-affiliate-content-wrapper">
                    <!-- Affiliate Program Start  -->
                    <div class="affiliate-program-wrapper">
                        <h3>@lang('Affiliate Program')</h3>
                        <!-- copy box 1  -->
                        <div class="copy-box-wrapper w-100">
                            <div class="title-des-wrapper">
                                <h5 class="fw-medium">@lang('Your Affiliate Link')</h5>
                                <p class="text-xs">
                                    @lang('This is your affilate link just copy the link and paste anywhere you want.')
                                </p>
                            </div>
                            <div class="copy-box">
                                <p class="copy-text" id="text-to-copy">
                                    {{ url('/') . '/?reff=' . $user->affilate_code }}
                                </p>
                                <button class="copy-btn" id="copy-button">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                        viewBox="0 0 24 24" fill="none">
                                        <path
                                            d="M8 16V18.8C8 19.9201 8 20.4802 8.21799 20.908C8.40973 21.2843 8.71569 21.5903 9.09202 21.782C9.51984 22 10.0799 22 11.2 22H18.8C19.9201 22 20.4802 22 20.908 21.782C21.2843 21.5903 21.5903 21.2843 21.782 20.908C22 20.4802 22 19.9201 22 18.8V11.2C22 10.0799 22 9.51984 21.782 9.09202C21.5903 8.71569 21.2843 8.40973 20.908 8.21799C20.4802 8 19.9201 8 18.8 8H16M5.2 16H12.8C13.9201 16 14.4802 16 14.908 15.782C15.2843 15.5903 15.5903 15.2843 15.782 14.908C16 14.4802 16 13.9201 16 12.8V5.2C16 4.0799 16 3.51984 15.782 3.09202C15.5903 2.71569 15.2843 2.40973 14.908 2.21799C14.4802 2 13.9201 2 12.8 2H5.2C4.0799 2 3.51984 2 3.09202 2.21799C2.71569 2.40973 2.40973 2.71569 2.21799 3.09202C2 3.51984 2 4.07989 2 5.2V12.8C2 13.9201 2 14.4802 2.21799 14.908C2.40973 15.2843 2.71569 15.5903 3.09202 15.782C3.51984 16 4.07989 16 5.2 16Z"
                                            stroke="white" stroke-width="2" stroke-linecap="round"
                                            stroke-linejoin="round" />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <!-- affiliate banner  -->
                        <div class="affiliate-banner-wrapper">
                            <div class="title-des-wrapper">
                                <h5 class="fw-medium">@lang('Affiliate Banner')</h5>
                                <p class="text-xs">@lang('This is your affilate banner Preview.')</p>
                            </div>
                            <div class="img-wrapper">
                                <img class="w-100" src="{{ asset('assets/images/' . $gs->affilate_banner) }}"
                                    alt="">
                            </div>
                        </div>
                        <!-- copy box 2  -->
                        <div class="copy-box-wrapper w-100">
                            <div class="title-des-wrapper">
                                <h5 class="fw-medium">@lang('Affiliate Banner HTML Code')</h5>
                                <p class="text-xs">
                                    @lang('This is your affiliate banner html code just copy the code and paste anywhere you want.')
                                </p>
                            </div>
                            <div class="copy-box">
                                <textarea id="banner_code" class="affiliate-text w-100 from-control" name="address" readonly=""><a href="{{ url('/') . '/?reff=' . $user->affilate_code }}" target="_blank"><img src="{{ asset('assets/images/' . $gs->affilate_banner) }}"></a></textarea>
                                <button class="copy-btn" id="banner_code_copy">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                        viewBox="0 0 24 24" fill="none">
                                        <path
                                            d="M8 16V18.8C8 19.9201 8 20.4802 8.21799 20.908C8.40973 21.2843 8.71569 21.5903 9.09202 21.782C9.51984 22 10.0799 22 11.2 22H18.8C19.9201 22 20.4802 22 20.908 21.782C21.2843 21.5903 21.5903 21.2843 21.782 20.908C22 20.4802 22 19.9201 22 18.8V11.2C22 10.0799 22 9.51984 21.782 9.09202C21.5903 8.71569 21.2843 8.40973 20.908 8.21799C20.4802 8 19.9201 8 18.8 8H16M5.2 16H12.8C13.9201 16 14.4802 16 14.908 15.782C15.2843 15.5903 15.5903 15.2843 15.782 14.908C16 14.4802 16 13.9201 16 12.8V5.2C16 4.0799 16 3.51984 15.782 3.09202C15.5903 2.71569 15.2843 2.40973 14.908 2.21799C14.4802 2 13.9201 2 12.8 2H5.2C4.0799 2 3.51984 2 3.09202 2.21799C2.71569 2.40973 2.40973 2.71569 2.21799 3.09202C2 3.51984 2 4.07989 2 5.2V12.8C2 13.9201 2 14.4802 2.21799 14.908C2.40973 15.2843 2.71569 15.5903 3.09202 15.782C3.51984 16 4.07989 16 5.2 16Z"
                                            stroke="white" stroke-width="2" stroke-linecap="round"
                                            stroke-linejoin="round" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                    <!-- Affiliate Program End  -->

                    <!-- Table  -->
                    <div>
                        <div class="user-table table-responsive">
                            <h4 class="table-title">@lang('Your Affiliate History')</h4>
                            <table class="table table-bordered">
                                <tr>

                                    <th class="affiliate-bonus">
                                        <span class="header-title">@lang('Customer Email')</span>
                                    </th>
                                    <th class="affiliate-bonus">
                                        <span class="header-title">@lang('Affiliate Bonus')</span>
                                    </th>

                                    <th class="affiliate-bonus">
                                        <span class="header-title">@lang('Created At')</span>
                                    </th>
                                </tr>

                                @forelse($final_affilate_users as $key => $fuser)
                                    <tr>

                                        <td><span class="content"> {{ $fuser->customer_email }}</span></td>

                                        <td><span
                                                class="content">{{ PriceHelper::showCurrencyPrice($fuser->bonus * $curr->value) }}</span>
                                        </td>

                                        <td><span
                                                class="content">{{ Carbon\Carbon::parse($fuser->created_at)->format('d-m-Y') }}</span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center">@lang('No Data Found')</td>
                                    </tr>
                                @endforelse

                            </table>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script type="text/javascript">
        (function($) {
            "use strict";
            $('#copy-button').click(function() {
                var textToCopy = $('#text-to-copy').text();
                $('#copy-target').val(textToCopy);
                var tempInput = $('<input>');
                $('body').append(tempInput);
                tempInput.val(textToCopy).select();
                document.execCommand('copy');
                tempInput.remove();
                toastr.success("Affiliate Link Copied Successfully");

            });

            $('#banner_code_copy').on('click', function() {
                var copyText = document.getElementById("banner_code");
                copyText.select();
                copyText.setSelectionRange(0, 99999);
                document.execCommand("copy");
                toastr.success("Affiliate Banner Copied Successfully");

            });


        })(jQuery);
    </script>
@endsection
