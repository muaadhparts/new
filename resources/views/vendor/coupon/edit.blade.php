@extends('layouts.vendor')

@section('styles')
<link href="{{asset('assets/admin/css/jquery-ui.css')}}" rel="stylesheet" type="text/css">
@endsection

@section('content')
    <div class="gs-vendor-outlet">
        <!-- breadcrumb start  -->
        <div class="gs-vendor-breadcrumb has-mb">
            <div class="d-flex gap-4 flex-wrap align-items-center custom-gap-sm-2">
                <h4 class="text-capitalize">@lang('Edit Coupon')</h4>
                <div class="d-flex gap-2 flex-wrap">
                    <a href="{{ route('vendor-coupon-index') }}"
                        class="template-btn md-btn black-btn data-table-btn">
                        <i class="fas fa-arrow-left"></i> @lang('Back')
                    </a>
                </div>
            </div>
            <ul class="breadcrumb-menu">
                <li>
                    <a href="{{ route('vendor.dashboard') }}" class="text-capitalize">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                            fill="none" stroke="#4C3533" class="home-icon-vendor-panel-breadcrumb">
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
                    <a href="{{ route('vendor-coupon-index') }}" class="text-capitalize"> @lang('Coupons') </a>
                </li>
                <li>
                    <a href="#" class="text-capitalize"> @lang('Edit') </a>
                </li>
            </ul>
        </div>
        <!-- breadcrumb end -->

        <!-- Form area start  -->
        <div class="vendor-form-wrapper">
            <div class="card">
                <div class="card-body">
                    @include('alerts.admin.form-both')

                    <form id="couponForm" action="{{ route('vendor-coupon-update', $data->id) }}" method="POST">
                        @csrf

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">{{ __('Coupon Code') }} <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="code" placeholder="{{ __('Enter Coupon Code') }}" value="{{ $data->code }}" required>
                                <small class="text-muted">{{ __('Unique code that customers will use') }}</small>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">{{ __('Discount Type') }} <span class="text-danger">*</span></label>
                                <select class="form-select" name="type" id="discountType" required>
                                    <option value="">{{ __('Select Type') }}</option>
                                    <option value="0" {{ $data->type == 0 ? 'selected' : '' }}>{{ __('Percentage Discount') }}</option>
                                    <option value="1" {{ $data->type == 1 ? 'selected' : '' }}>{{ __('Fixed Amount Discount') }}</option>
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3" id="discountValueDiv" style="{{ $data->type !== null ? '' : 'display: none;' }}">
                                <label class="form-label" id="discountValueLabel">{{ $data->type == 0 ? __('Percentage') : __('Amount') }} <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="number" step="0.01" class="form-control" name="price" id="discountValue" placeholder="0" value="{{ $data->price }}">
                                    <span class="input-group-text" id="discountSuffix">{{ $data->type == 0 ? '%' : ($curr->sign ?? '$') }}</span>
                                </div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">{{ __('Usage Limit') }} <span class="text-danger">*</span></label>
                                <select class="form-select" name="quantity_type" id="quantityType" required>
                                    <option value="0" {{ $data->times === null ? 'selected' : '' }}>{{ __('Unlimited') }}</option>
                                    <option value="1" {{ $data->times !== null ? 'selected' : '' }}>{{ __('Limited') }}</option>
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3" id="timesDiv" style="{{ $data->times !== null ? '' : 'display: none;' }}">
                                <label class="form-label">{{ __('Number of Uses') }} <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" name="times" placeholder="{{ __('Enter number of uses') }}" value="{{ $data->times }}">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">{{ __('Apply to') }} <span class="text-danger">*</span></label>
                                <select class="form-select" name="coupon_type" id="couponType" required>
                                    <option value="">{{ __('Select Product Type') }}</option>
                                    <option value="category" {{ $data->coupon_type == 'category' ? 'selected' : '' }}>{{ __('Category') }}</option>
                                    <option value="sub_category" {{ $data->coupon_type == 'sub_category' ? 'selected' : '' }}>{{ __('Sub Category') }}</option>
                                    <option value="child_category" {{ $data->coupon_type == 'child_category' ? 'selected' : '' }}>{{ __('Child Category') }}</option>
                                </select>
                            </div>

                            <div class="col-md-6 mb-3" id="categoryDiv" style="{{ $data->coupon_type == 'category' ? '' : 'display: none;' }}">
                                <label class="form-label">{{ __('Category') }} <span class="text-danger">*</span></label>
                                <select class="form-select" name="category" id="categorySelect">
                                    <option value="">{{ __('Select Category') }}</option>
                                </select>
                            </div>

                            <div class="col-md-6 mb-3" id="subCategoryDiv" style="{{ $data->coupon_type == 'sub_category' ? '' : 'display: none;' }}">
                                <label class="form-label">{{ __('Sub Category') }} <span class="text-danger">*</span></label>
                                <select class="form-select" name="sub_category" id="subCategorySelect">
                                    <option value="">{{ __('Select Sub Category') }}</option>
                                </select>
                            </div>

                            <div class="col-md-6 mb-3" id="childCategoryDiv" style="{{ $data->coupon_type == 'child_category' ? '' : 'display: none;' }}">
                                <label class="form-label">{{ __('Child Category') }} <span class="text-danger">*</span></label>
                                <select class="form-select" name="child_category" id="childCategorySelect">
                                    <option value="">{{ __('Select Child Category') }}</option>
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">{{ __('Start Date') }} <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" name="start_date" value="{{ $data->start_date }}" required>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">{{ __('End Date') }} <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" name="end_date" value="{{ $data->end_date }}" required>
                            </div>
                        </div>

                        <div class="mt-4">
                            <button type="submit" class="template-btn primary-btn">
                                <i class="fas fa-save"></i> {{ __('Update Coupon') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <!-- Form area end  -->
    </div>
@endsection

@section('scripts')
<script type="text/javascript">
    $(document).ready(function() {
        // Discount Type Change
        $('#discountType').on('change', function() {
            var val = $(this).val();
            if (val === '') {
                $('#discountValueDiv').hide();
            } else {
                $('#discountValueDiv').show();
                if (val == '0') {
                    $('#discountValueLabel').text('{{ __("Percentage") }} *');
                    $('#discountSuffix').text('%');
                    $('#discountValue').attr('max', 100);
                } else {
                    $('#discountValueLabel').text('{{ __("Amount") }} *');
                    $('#discountSuffix').text('{{ $curr->sign ?? "$" }}');
                    $('#discountValue').removeAttr('max');
                }
            }
        });

        // Quantity Type Change
        $('#quantityType').on('change', function() {
            var val = $(this).val();
            if (val == '1') {
                $('#timesDiv').show();
            } else {
                $('#timesDiv').hide();
                $('input[name="times"]').val('');
            }
        });

        // Function to load categories via AJAX
        function loadCategories(type, selectedValue) {
            if (!type) return;

            var targetDiv, targetSelect, defaultOption;
            if (type === 'category') {
                targetDiv = '#categoryDiv';
                targetSelect = '#categorySelect';
                defaultOption = '{{ __("Select Category") }}';
            } else if (type === 'sub_category') {
                targetDiv = '#subCategoryDiv';
                targetSelect = '#subCategorySelect';
                defaultOption = '{{ __("Select Sub Category") }}';
            } else if (type === 'child_category') {
                targetDiv = '#childCategoryDiv';
                targetSelect = '#childCategorySelect';
                defaultOption = '{{ __("Select Child Category") }}';
            }

            $(targetSelect).html('<option value="">{{ __("Loading...") }}</option>');

            $.ajax({
                url: '{{ route("vendor-coupon-get-categories") }}',
                type: 'GET',
                data: { type: type },
                success: function(response) {
                    var options = '<option value="">' + defaultOption + '</option>';
                    $.each(response, function(index, item) {
                        var selected = (selectedValue && item.id == selectedValue) ? 'selected' : '';
                        options += '<option value="' + item.id + '" ' + selected + '>' + item.name + '</option>';
                    });
                    $(targetSelect).html(options);
                },
                error: function() {
                    $(targetSelect).html('<option value="">{{ __("Error loading data") }}</option>');
                }
            });
        }

        // Load existing category on page load
        var initialType = '{{ $data->coupon_type }}';
        var initialCategory = '{{ $data->category }}';
        var initialSubCategory = '{{ $data->sub_category }}';
        var initialChildCategory = '{{ $data->child_category }}';

        if (initialType === 'category' && initialCategory) {
            loadCategories('category', initialCategory);
        } else if (initialType === 'sub_category' && initialSubCategory) {
            loadCategories('sub_category', initialSubCategory);
        } else if (initialType === 'child_category' && initialChildCategory) {
            loadCategories('child_category', initialChildCategory);
        }

        // Coupon Type Change - Load categories dynamically
        $('#couponType').on('change', function() {
            var val = $(this).val();
            $('#categoryDiv, #subCategoryDiv, #childCategoryDiv').hide();

            // Reset all selects
            $('#categorySelect, #subCategorySelect, #childCategorySelect').html('<option value="">{{ __("Loading...") }}</option>');

            if (val === '') return;

            if (val === 'category') {
                $('#categoryDiv').show();
            } else if (val === 'sub_category') {
                $('#subCategoryDiv').show();
            } else if (val === 'child_category') {
                $('#childCategoryDiv').show();
            }

            loadCategories(val, null);
        });

        // Form Submit
        $('#couponForm').on('submit', function(e) {
            e.preventDefault();
            var form = $(this);
            var btn = form.find('button[type="submit"]');
            var btnText = btn.html();

            btn.html('<i class="fas fa-spinner fa-spin"></i> {{ __("Saving...") }}');
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
                    toastr.error('{{ __("Something went wrong!") }}');
                },
                complete: function() {
                    btn.html(btnText);
                    btn.prop('disabled', false);
                }
            });
        });
    });
</script>
@endsection
