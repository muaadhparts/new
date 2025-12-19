<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $gs->title }} Deposit</title>
    <!--Essential css files-->
    <link rel="stylesheet" href="{{ asset('assets/front') }}/css/bootstrap.min.css">

    <link rel="stylesheet" href="{{ asset('assets/front') }}/css/style.css">
    <link rel="stylesheet" href="{{ asset('assets/front') }}/css/custom.css">
    <link rel="icon" href="{{ asset('assets/images/' . $gs->favicon) }}">
    @include('includes.frontend.extra_head')
    @yield('css')

</head>

<body>



    <!--  checkout wrapper start-->
    <div class="gs-checkout-wrapper">
        <div class="container">

            <!-- address-->
            <form class="address-wrapper checkoutform" method="post">
                @include('includes.form-success')
                @include('includes.form-error')
                {{ csrf_field() }}
                <div class="row gy-4">
                    <div class="col-lg-7 col-xl-8 wow-replaced" data-wow-delay=".1s">
                        <div class="select-payment-list-wrapper">
                            <h5 class="title">@lang('Select payment Method')</h5>

                            <div class="list-wrapper mb-4">
                                @foreach ($gateways as $gt)
                                    @if ($gt->deposit == 1 && $gt->type != 'manual')
                                        <div class="gs-radio-wrapper payment" data-val="{{ $gt->keyword }}"
                                            data-show="{{ $gt->showForm() }}"
                                            data-form="{{ $gt->ApishowDepositLink() }}"
                                            data-href="{{ route('front.load.payment', ['slug1' => $gt->showKeyword(), 'slug2' => $gt->id]) }}">
                                            <input type="radio" id="pl{{ $gt->id }}" name="payment_1">
                                            <label class="icon-label" for="pl{{ $gt->id }}">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20"
                                                    viewBox="0 0 20 20" fill="none">
                                                    <rect x="0.5" y="0.5" width="19" height="19" rx="9.5"
                                                        fill="#FDFDFD" />
                                                    <rect x="0.5" y="0.5" width="19" height="19" rx="9.5"
                                                        stroke="#EE1243" />
                                                    <circle cx="10" cy="10" r="4" fill="#EE1243" />
                                                </svg>
                                            </label>
                                            <label class="label-wrapper" for="pl{{ $gt->id }}">
                                                <span class="label-title"> {{ $gt->name }}</span>
                                                @if ($gt->information != null)
                                                    <span class="label-subtitle">{{ $gt->getAutoDataText() }}</span>
                                                @endif
                                            </label>
                                        </div>
                                    @endif
                                @endforeach
                            </div>

                            <div class="transection-wrapper pay-area mb-4">
                            </div>

                            <input type="hidden" id="preamount" value="{{ $deposit->amount * $curr->value }}">
                            <input type="hidden" name="deposit_number" value="{{ $deposit->deposit_number }}">
                            <input type="hidden" name="email"
                                value="{{ App\Models\User::findOrFail($deposit->user_id)->email }}">
                            <input type="hidden" name="ref_id" id="ref_id" value="">

                            <button type="submit" class="template-btn inline-block">submit</button>
                        </div>


                    </div>
                    <div class="col-lg-5 col-xl-4 order-0 order-lg-1">
                        <div class="summary-box">
                            <h4 class="form-title">@lang('PRICE DETAILS')</h4>

                            <!-- shipping methods -->
                            <div class="summary-inner-box d-flex gap-4 justify-content-between">
                                <h6 class="summary-title">Amount</h6>

                                @if ($gs->currency_format == 0)
                                    <p>{{ $curr->sign }} {{ $deposit->amount * $curr->value }}</p>
                                @else
                                    <p>{{ $deposit->amount * $curr->value }} {{ $curr->sign }}</p>
                                @endif
                            </div>

                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <!--  checkout wrapper end-->










    <!--Esential Js Files-->
    <script src="{{ asset('assets/front') }}/js/jquery.min.js"></script>
    <script src="{{ asset('assets/front/js/myscript.js') }}"></script>

    <script src="https://js.paystack.co/v1/inline.js"></script>
    <script src="https://sdk.mercadopago.com/js/v2"></script>
    <script src="https://js.stripe.com/v3/"></script>




    <script type="text/javascript">
        // under input field
        $('.payment:first').children('input').prop('checked', true);
        $('.checkoutform').attr('action', $('.payment:first').attr('data-form'));
        $(".pay-area").load($('.payment:first').data('href'));

        var show = $('.payment:first').data('show');
        if (show != 'no') {
            $('.pay-area').removeClass('d-none');
        } else {
            $('.pay-area').addClass('d-none');
        }
    </script>





    <script type="text/javascript">
        var coup = 0;
        var pos = {{ $gs->currency_format }};
        let mship = 0;
        let mpack = 0;


        var ftotal = parseFloat($('#grandtotal').val());
        ftotal = parseFloat(ftotal).toFixed(2);

        if (pos == 0) {
            $('#final-cost').html('{{ $curr->sign }}' + ftotal)
        } else {
            $('#final-cost').html(ftotal + '{{ $curr->sign }}')
        }
        $('#grandtotal').val(ftotal);

        let original_tax = 0;

        $(document).ready(function() {
            let country_id = $('#select_country').val();
            let state_id = $('#state_id').val();
            let is_state = $('#is_state').val();
            let state_url = $('#state_url').val();


            if (is_state == 1) {
                if (is_state == 1) {
                    $('.select_state').removeClass('d-none');
                    $.get(state_url, function(response) {
                        $('#show_state').html(response.data);
                        tax_submit(country_id, response.state);
                    });

                } else {
                    tax_submit(country_id, state_id);
                }
            } else {
                tax_submit(country_id, state_id);
            }
        });


        function tax_submit(country_id, state_id) {

            $('.gocover').show();
            var total = $("#tgrandtotal").val();
            var ship = 0;
            $.ajax({
                type: "GET",
                url: mainurl + "/country/tax/check",

                data: {
                    state_id: state_id,
                    country_id: country_id,
                    total: total,
                    shipping_cost: ship
                },
                success: function(data) {


                    $('.tax_show').removeClass('d-none');
                    $('#input_tax').val(data[11]);
                    $('#input_tax_type').val(data[12]);
                    $('.original_tax').html(parseFloat(data[1]) + "%");
                    var ttotal = parseFloat($('#grandtotal').val());
                    var tttotal = parseFloat($('#grandtotal').val()) + (parseFloat(mship) + parseFloat(mpack));
                    ttotal = parseFloat(ttotal).toFixed(2);
                    tttotal = parseFloat(tttotal).toFixed(2);
                    if (pos == 0) {
                        $('#final-cost').html('{{ $curr->sign }}' + tttotal);
                        $('.total-cost-dum #total-cost').html('{{ $curr->sign }}' + ttotal);
                    } else {
                        $('#total-cost').html('');
                        $('#final-cost').html(tttotal + '{{ $curr->sign }}');
                        $('.total-cost-dum #total-cost').html(ttotal + '{{ $curr->sign }}');
                    }
                    $('.gocover').hide();
                }
            });
        }



        $(document).on("click", "#check_coupon", function() {
            var val = $("#code").val();
            var total = $("#ttotal").val();
            var ship = 0;
            $.ajax({
                type: "GET",
                url: mainurl + "/carts/coupon/check",
                data: {
                    code: val,
                    total: total,
                    shipping_cost: ship
                },
                success: function(data) {
                    if (data == 0) {
                        toastr.error('{{ __('Coupon not found') }}');
                        $("#code").val("");
                    } else if (data == 2) {
                        toastr.error('{{ __('Coupon already have been taken') }}');
                        $("#code").val("");
                    } else {
                        $("#check-coupon-form").toggle();
                        $(".discount-bar").removeClass('d-none');

                        if (pos == 0) {
                            $('.total-cost-dum #total-cost').html('{{ $curr->sign }}' + data[0]);
                            $('#discount').html('{{ $curr->sign }}' + data[2]);
                        } else {
                            $('.total-cost-dum #total-cost').html(data[0]);
                            $('#discount').html(data[2] + '{{ $curr->sign }}');
                        }
                        $("#coupon_id").val(data[3]);
                        $('#grandtotal').val(data[0]);
                        $('#tgrandtotal').val(data[0]);
                        $('#coupon_code').val(data[1]);
                        $('#coupon_discount').val(data[2]);
                        if (data[4] != 0) {
                            $('.dpercent').html('(' + data[4] + ')');
                        } else {
                            $('.dpercent').html('');
                        }


                        var ttotal = data[6] + parseFloat(mship) + parseFloat(mpack);
                        ttotal = parseFloat(ttotal);
                        if (ttotal % 1 != 0) {
                            ttotal = ttotal.toFixed(2);
                        }

                        if (pos == 0) {
                            $('.total-amount').html('{{ $curr->sign }}' + ttotal)
                        } else {
                            $('.total-amount').html(ttotal + '{{ $curr->sign }}')
                        }
                        toastr.success(lang.coupon_found);
                        $("#code").val("");
                    }
                }
            });
            return false;
        });




        $('.payment').on('click', function() {

            if ($(this).data('val') == 'paystack') {
                $('.checkoutform').attr('id', 'step1-form');
            } else if ($(this).data('val') == 'mercadopago') {
                $('.checkoutform').attr('id', 'mercadopago');
                checkONE = 1;
            } else {
                $('.checkoutform').attr('id', '');
            }
            $('.checkoutform').attr('action', $(this).attr('data-form'));
            $('.payment').removeClass('active');

            var show = $(this).attr('data-show');
            if (show != 'no') {
                $('.pay-area').removeClass('d-none');
            } else {
                $('.pay-area').addClass('d-none');
            }
            $($('#v-pills-tabContent .tap-pane').removeClass('active show'));
            $(".pay-area").addClass('active show').load($(this).attr(
                'data-href'));
        })
    </script>






</body>

</html>
