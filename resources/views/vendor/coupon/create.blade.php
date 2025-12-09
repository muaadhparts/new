@extends('layouts.vendor')

@section('css')
<link href="{{ asset('assets/admin/css/jquery-ui.css') }}" rel="stylesheet" type="text/css">
@endsection

@section('content')
    <div class="gs-vendor-outlet">
        <!-- breadcrumb start  -->
        <div class="gs-vendor-breadcrumb has-mb">
            <div class="d-flex gap-4 flex-wrap align-items-center">
                <a class="back-btn" href="{{ route('vendor-coupon-index') }}">
                    <i class="fa-solid fa-arrow-left"></i>
                </a>
                <h4 class="text-capitalize">@lang('Add New Coupon')</h4>
            </div>
            <ul class="breadcrumb-menu">
                <li>
                    <a href="{{ route('vendor.dashboard') }}" class="text-capitalize">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" class="home-icon-vendor-panel-breadcrumb">
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
                    <a href="{{ route('vendor-coupon-index') }}" class="text-capitalize">@lang('Coupons')</a>
                </li>
                <li>
                    <a href="javascript:;" class="text-capitalize">@lang('Add New')</a>
                </li>
            </ul>
        </div>
        <!-- breadcrumb end -->

        <!-- Form area start  -->
        <div class="vendor-edit-profile-section-wrapper">
            <div class="gs-edit-profile-section">
                <form class="edit-profile-area" id="couponForm" action="{{ route('vendor-coupon-store') }}" method="POST">
                    @csrf
                    <div class="row">
                        <div class="col-lg-6">
                            <div class="form-group">
                                <label for="code">@lang('Coupon Code') <span class="text-danger">*</span></label>
                                <input type="text" id="code" class="form-control" placeholder="@lang('Enter Coupon Code')"
                                    name="code" required>
                                <small class="form-text text-muted">@lang('Unique code that customers will use')</small>
                            </div>
                        </div>

                        <div class="col-lg-6">
                            <div class="form-group">
                                <label for="coupon_type">@lang('Apply to') <span class="text-danger">*</span></label>
                                <select id="coupon_type" class="form-control" name="coupon_type" required>
                                    <option value="">@lang('Select Type')</option>
                                    <option value="category">@lang('Category')</option>
                                    <option value="sub_category">@lang('Sub Category')</option>
                                    <option value="child_category">@lang('Child Category')</option>
                                </select>
                            </div>
                        </div>

                        <div class="col-lg-6 d-none" id="categoryDiv">
                            <div class="form-group">
                                <label for="category">@lang('Category') <span class="text-danger">*</span></label>
                                <select id="categorySelect" class="form-control" name="category">
                                    <option value="">@lang('Select Category')</option>
                                </select>
                            </div>
                        </div>

                        <div class="col-lg-6 d-none" id="subCategoryDiv">
                            <div class="form-group">
                                <label for="sub_category">@lang('Sub Category') <span class="text-danger">*</span></label>
                                <select id="subCategorySelect" class="form-control" name="sub_category">
                                    <option value="">@lang('Select Sub Category')</option>
                                </select>
                            </div>
                        </div>

                        <div class="col-lg-6 d-none" id="childCategoryDiv">
                            <div class="form-group">
                                <label for="child_category">@lang('Child Category') <span class="text-danger">*</span></label>
                                <select id="childCategorySelect" class="form-control" name="child_category">
                                    <option value="">@lang('Select Child Category')</option>
                                </select>
                            </div>
                        </div>

                        <div class="col-lg-6">
                            <div class="form-group">
                                <label for="type">@lang('Discount Type') <span class="text-danger">*</span></label>
                                <select id="type" class="form-control" name="type" required>
                                    <option value="">@lang('Select Type')</option>
                                    <option value="0">@lang('Percentage Discount')</option>
                                    <option value="1">@lang('Fixed Amount Discount')</option>
                                </select>
                            </div>
                        </div>

                        <div class="col-lg-6 d-none" id="priceDiv">
                            <div class="form-group">
                                <label for="price" id="priceLabel">@lang('Value') <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="number" step="0.01" id="price" class="form-control"
                                        placeholder="@lang('Enter Value')" name="price">
                                    <span class="input-group-text" id="priceSuffix">%</span>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-6">
                            <div class="form-group">
                                <label for="quantity_type">@lang('Usage Limit') <span class="text-danger">*</span></label>
                                <select id="quantity_type" class="form-control" name="quantity_type" required>
                                    <option value="0">@lang('Unlimited')</option>
                                    <option value="1">@lang('Limited')</option>
                                </select>
                            </div>
                        </div>

                        <div class="col-lg-6 d-none" id="timesDiv">
                            <div class="form-group">
                                <label for="times">@lang('Number of Uses') <span class="text-danger">*</span></label>
                                <input type="number" id="times" class="form-control"
                                    placeholder="@lang('Enter number of uses')" name="times">
                            </div>
                        </div>

                        <div class="col-lg-6">
                            <div class="form-group">
                                <label for="start_date">@lang('Start Date') <span class="text-danger">*</span></label>
                                <input type="text" id="start_date" class="form-control datepicker"
                                    placeholder="@lang('Select Start Date')" name="start_date" autocomplete="off" required>
                            </div>
                        </div>

                        <div class="col-lg-6">
                            <div class="form-group">
                                <label for="end_date">@lang('End Date') <span class="text-danger">*</span></label>
                                <input type="text" id="end_date" class="form-control datepicker"
                                    placeholder="@lang('Select End Date')" name="end_date" autocomplete="off" required>
                            </div>
                        </div>

                        <div class="col-12">
                            <button class="template-btn btn-forms" type="submit">
                                <i class="fas fa-save"></i> @lang('Create Coupon')
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        <!-- Form area end  -->
    </div>
@endsection

@section('script')
<script type="text/javascript">
    "use strict";

    // Date Picker
    var dateToday = new Date();
    $("#start_date, #end_date").datepicker({
        defaultDate: "+1w",
        changeMonth: true,
        changeYear: true,
        minDate: dateToday,
        dateFormat: 'yy-mm-dd'
    });

    // Discount Type Change
    $('#type').on('change', function() {
        var val = $(this).val();
        if (val === '') {
            $('#priceDiv').addClass('d-none');
        } else {
            $('#priceDiv').removeClass('d-none');
            if (val == '0') {
                $('#priceLabel').html('@lang("Percentage") <span class="text-danger">*</span>');
                $('#priceSuffix').text('%');
                $('#price').attr('max', 100);
            } else {
                $('#priceLabel').html('@lang("Amount") <span class="text-danger">*</span>');
                $('#priceSuffix').text('{{ $curr->sign ?? "$" }}');
                $('#price').removeAttr('max');
            }
        }
    });

    // Quantity Type Change
    $('#quantity_type').on('change', function() {
        var val = $(this).val();
        if (val == '1') {
            $('#timesDiv').removeClass('d-none');
        } else {
            $('#timesDiv').addClass('d-none');
            $('#times').val('');
        }
    });

    // Coupon Type Change - Load categories dynamically
    function loadCategories(type, targetSelect, defaultOption) {
        $(targetSelect).html('<option value="">@lang("Loading...")</option>');

        $.ajax({
            url: '{{ route("vendor-coupon-get-categories") }}',
            type: 'GET',
            data: { type: type },
            success: function(response) {
                var options = '<option value="">' + defaultOption + '</option>';
                $.each(response, function(index, item) {
                    options += '<option value="' + item.id + '">' + item.name + '</option>';
                });
                $(targetSelect).html(options);
            },
            error: function() {
                $(targetSelect).html('<option value="">@lang("Error loading data")</option>');
            }
        });
    }

    $('#coupon_type').on('change', function() {
        var val = $(this).val();
        $('#categoryDiv, #subCategoryDiv, #childCategoryDiv').addClass('d-none');

        if (val === 'category') {
            $('#categoryDiv').removeClass('d-none');
            loadCategories('category', '#categorySelect', '@lang("Select Category")');
        } else if (val === 'sub_category') {
            $('#subCategoryDiv').removeClass('d-none');
            loadCategories('sub_category', '#subCategorySelect', '@lang("Select Sub Category")');
        } else if (val === 'child_category') {
            $('#childCategoryDiv').removeClass('d-none');
            loadCategories('child_category', '#childCategorySelect', '@lang("Select Child Category")');
        }
    });

    // Form Submit
    $('#couponForm').on('submit', function(e) {
        e.preventDefault();
        var form = $(this);
        var btn = form.find('button[type="submit"]');
        var btnText = btn.html();

        btn.html('<i class="fas fa-spinner fa-spin"></i> @lang("Saving...")');
        btn.prop('disabled', true);

        $.ajax({
            url: form.attr('action'),
            type: 'POST',
            data: form.serialize(),
            success: function(response) {
                if (typeof response === 'object' && response.errors) {
                    var errors = '';
                    $.each(response.errors, function(key, value) {
                        errors += value + '<br>';
                    });
                    toastr.error(errors);
                } else {
                    toastr.success(response);
                    setTimeout(function() {
                        window.location.href = '{{ route("vendor-coupon-index") }}';
                    }, 1500);
                }
            },
            error: function(xhr) {
                toastr.error('@lang("Something went wrong!")');
            },
            complete: function() {
                btn.html(btnText);
                btn.prop('disabled', false);
            }
        });
    });
</script>
@endsection
