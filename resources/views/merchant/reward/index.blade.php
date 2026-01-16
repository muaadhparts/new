@extends('layouts.merchant')

@section('content')
    <!-- outlet start  -->
    <div class="gs-merchant-outlet">
        <!-- breadcrumb start  -->
        <div class="gs-merchant-breadcrumb has-mb">
            <div class="d-flex gap-4 custom-gap-sm-2 flex-wrap align-items-center">
                <h4 class="text-capitalize">@lang('Reward Points')</h4>
            </div>
            <ul class="breadcrumb-menu">
                <li>
                    <a href="{{ route('merchant.dashboard') }}" class="text-capitalize">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                            fill="none" stroke="currentColor" class="home-icon-merchant-panel-breadcrumb">
                            <path
                                d="M9 21V13.6C9 13.0399 9 12.7599 9.109 12.546C9.20487 12.3578 9.35785 12.2049 9.54601 12.109C9.75993 12 10.04 12 10.6 12H13.4C13.9601 12 14.2401 12 14.454 12.109C14.6422 12.2049 14.7951 12.3578 14.891 12.546C15 12.7599 15 13.0399 15 13.6V21M2 9.5L11.04 2.72C11.3843 2.46181 11.5564 2.33271 11.7454 2.28294C11.9123 2.23902 12.0877 2.23902 12.2546 2.28295C12.4436 2.33271 12.6157 2.46181 12.96 2.72L22 9.5M4 8V17.8C4 18.9201 4 19.4802 4.21799 19.908C4.40974 20.2843 4.7157 20.5903 5.09202 20.782C5.51985 21 6.0799 21 7.2 21H16.8C17.9201 21 18.4802 21 18.908 20.782C19.2843 20.5903 19.5903 20.2843 19.782 19.908C20 19.4802 20 18.9201 20 17.8V8L13.92 3.44C13.2315 2.92361 12.8872 2.66542 12.5091 2.56589C12.1754 2.47804 11.8246 2.47804 11.4909 2.56589C11.1128 2.66542 10.7685 2.92361 10.08 3.44L4 8Z"
                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </a>
                </li>
                <li>
                    <a href="{{ route('merchant.dashboard') }}" class="text-capitalize">
                        @lang('Dashboard')
                    </a>
                </li>
                <li>
                    <a href="#" class="text-capitalize">
                        @lang('Reward Points')
                    </a>
                </li>
            </ul>
        </div>
        <!-- breadcrumb end -->

        <div class="gs-merchant-erning">
            <div class="merchant-table-wrapper">
                <div class="p-4 bg-white rounded">

                    @include('alerts.operator.form-both')

                    <div class="mb-4">
                        <h5>@lang('Reward Points Configuration')</h5>
                        <p class="text-muted">@lang('Configure how customers earn reward points from purchases in your store.')</p>
                    </div>

                    @php
                        $config = $datas->first();
                    @endphp

                    <form id="muaadhform" action="{{ route('merchant-reward-update') }}" method="POST">
                        @csrf

                        <div class="row">
                            {{-- Points Earning Ratio --}}
                            <div class="col-lg-6 mb-4">
                                <div class="card h-100">
                                    <div class="card-header bg-light">
                                        <h6 class="mb-0"><i class="fas fa-coins me-2"></i>@lang('Points Earning')</h6>
                                    </div>
                                    <div class="card-body">
                                        <p class="text-muted mb-3">@lang('For every X amount spent, customer earns Y points')</p>

                                        <div class="row align-items-end">
                                            <div class="col-5">
                                                <label class="form-label">@lang('For every') ({{ $sign->sign }})</label>
                                                <input type="number" name="purchase_amount[]" class="form-control form-control-lg"
                                                    value="{{ $config->purchase_amount ?? 100 }}" min="1" required>
                                            </div>
                                            <div class="col-2 text-center pb-2">
                                                <i class="fas fa-arrow-right fa-lg text-muted"></i>
                                            </div>
                                            <div class="col-5">
                                                <label class="form-label">@lang('Points')</label>
                                                <input type="number" name="reward[]" class="form-control form-control-lg"
                                                    value="{{ $config->reward ?? 1 }}" min="1" required>
                                            </div>
                                        </div>

                                        <div class="alert alert-info mt-3 mb-0">
                                            <small>
                                                <i class="fas fa-info-circle me-1"></i>
                                                @lang('Example'): @lang('Order') 500{{ $sign->sign }} =
                                                <strong id="example-points">{{ $config ? floor(500 / $config->purchase_amount) * $config->reward : 5 }}</strong> @lang('points')
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Point Value --}}
                            <div class="col-lg-6 mb-4">
                                <div class="card h-100">
                                    <div class="card-header bg-light">
                                        <h6 class="mb-0"><i class="fas fa-tag me-2"></i>@lang('Point Value')</h6>
                                    </div>
                                    <div class="card-body">
                                        <p class="text-muted mb-3">@lang('How much is each point worth when used as payment?')</p>

                                        <div class="row align-items-end">
                                            <div class="col-5">
                                                <label class="form-label">1 @lang('Point')</label>
                                                <input type="text" class="form-control form-control-lg bg-light" value="1" disabled>
                                            </div>
                                            <div class="col-2 text-center pb-2">
                                                <i class="fas fa-equals fa-lg text-muted"></i>
                                            </div>
                                            <div class="col-5">
                                                <label class="form-label">@lang('Value') ({{ $sign->sign }})</label>
                                                <input type="number" step="0.01" min="0.01" name="point_value" class="form-control form-control-lg"
                                                    value="{{ $config->point_value ?? 1.00 }}" required>
                                            </div>
                                        </div>

                                        <div class="alert alert-success mt-3 mb-0">
                                            <small>
                                                <i class="fas fa-calculator me-1"></i>
                                                10 @lang('points') = <strong id="example-value">{{ $config ? number_format(10 * $config->point_value, 2) : '10.00' }}</strong>{{ $sign->sign }}
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-2">
                            <button class="template-btn" type="submit">
                                <i class="fas fa-save me-2"></i>@lang('Save Settings')
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <!-- outlet end  -->
@endsection

@section('script')
<script type="text/javascript">
    "use strict";

    // AJAX Form Submit
    $('#muaadhform').on('submit', function(e) {
        e.preventDefault();
        var form = $(this);
        var submitBtn = form.find('button[type="submit"]');
        var originalText = submitBtn.html();

        submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>@lang("Saving...")');

        $.ajax({
            url: form.attr('action'),
            type: 'POST',
            data: form.serialize(),
            success: function(response) {
                toastr.success(response);
                submitBtn.prop('disabled', false).html(originalText);
                updateExamples();
            },
            error: function(xhr) {
                var errorMsg = '@lang("Something went wrong!")';
                if (xhr.responseJSON && xhr.responseJSON.errors) {
                    var errors = xhr.responseJSON.errors;
                    errorMsg = Object.values(errors).flat().join('<br>');
                }
                toastr.error(errorMsg);
                submitBtn.prop('disabled', false).html(originalText);
            }
        });
    });

    // Update examples on input change
    $('input[name="purchase_amount[]"], input[name="reward[]"], input[name="point_value"]').on('input', function() {
        updateExamples();
    });

    function updateExamples() {
        var purchaseAmount = parseFloat($('input[name="purchase_amount[]"]').val()) || 100;
        var reward = parseFloat($('input[name="reward[]"]').val()) || 1;
        var pointValue = parseFloat($('input[name="point_value"]').val()) || 1;

        // Example: 500 SAR order
        var examplePoints = Math.floor(500 / purchaseAmount) * reward;
        $('#example-points').text(examplePoints);

        // Example: 10 points value
        var exampleValue = (10 * pointValue).toFixed(2);
        $('#example-value').text(exampleValue);
    }
</script>
@endsection
