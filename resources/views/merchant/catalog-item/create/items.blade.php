@extends('layouts.merchant')

@section('css')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
@endsection

@section('content')
    <div class="gs-merchant-outlet">
        <!-- breadcrumb start -->
        <div class="gs-merchant-breadcrumb has-mb">
            <div class="gs-topup-name ms-0 d-flex align-items-center gap-4">
                <a href="{{ route('merchant-catalog-item-index') }}" class="back-btn">
                    <i class="fa-solid fa-arrow-left-long"></i>
                </a>
                <h4>@lang('Add Merchant Item')</h4>
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
                    <a href="{{ route('merchant.dashboard') }}" class="text-capitalize">@lang('Dashboard')</a>
                </li>
                <li>
                    <a href="{{ route('merchant-catalog-item-index') }}" class="text-capitalize">@lang('My Items')</a>
                </li>
                <li>
                    <a href="#" class="text-capitalize">@lang('Add Merchant Item')</a>
                </li>
            </ul>
        </div>
        <!-- breadcrumb end -->

        <!-- Form start -->
        <form class="row gy-3 gy-lg-4 add-catalogItem-form" id="merchantItemForm" action="{{ route('merchant-catalog-item-store') }}" method="POST">
            @csrf

            <!-- Main Form -->
            <div class="col-12 col-lg-8 items-catalogItem-inputes-wrapper show">
                <div class="form-group">
                    <!-- Select Catalog Item -->
                    <div class="input-label-wrapper">
                        <label>@lang('Select Catalog Item') <span class="text-danger">*</span></label>
                        <small class="text-muted d-block mb-2">@lang('Search by part number to find the catalog item')</small>
                        <select id="catalog_item_select" class="form-control select2-search" required>
                            <option value="">@lang('Search by Part Number...')</option>
                        </select>
                        <input type="hidden" id="selected_catalog_item_id" name="catalog_item_id" value="">
                    </div>

                    <!-- Catalog Item Preview -->
                    <div id="catalogItemPreview" class="card mb-3 hidden">
                        <div class="card-body">
                            <div class="d-flex gap-3 align-items-start">
                                <img id="previewImage" src="{{ asset('assets/images/noimage.png') }}" alt="" class="rounded" width="100">
                                <div>
                                    <h6 id="previewName" class="mb-1">-</h6>
                                    <p class="mb-0 text-muted"><strong>@lang('Part Number'):</strong> <span id="previewPartNumber">-</span></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Merchant Branch -->
                    <div class="input-label-wrapper">
                        <label>@lang('Branch / Warehouse') <span class="text-danger">*</span></label>
                        <div class="dropdown-container">
                            <select class="form-control nice-select form__control" name="merchant_branch_id" required>
                                <option value="">@lang('Select Branch')</option>
                                @foreach($branches as $branch)
                                    <option value="{{ $branch->id }}">{{ $branch->warehouse_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <small class="text-muted">@lang('Branch where this item will be shipped from')</small>
                    </div>

                    <!-- Quality Brand -->
                    <div class="input-label-wrapper">
                        <label>@lang('Quality Brand') <span class="text-danger">*</span></label>
                        <div class="dropdown-container">
                            <select class="form-control nice-select form__control" name="quality_brand_id" required>
                                <option value="">@lang('Select Quality Brand')</option>
                                @foreach($qualityBrands as $qb)
                                    <option value="{{ $qb->id }}">{{ $qb->name_en }} {{ $qb->name_ar ? '- ' . $qb->name_ar : '' }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <!-- Item Condition -->
                    <div class="gs-checkbox-wrapper" data-bs-toggle="collapse" data-bs-target="#show_item-condition">
                        <input type="checkbox" id="allow-item-condition" name="item_condition_check" value="1">
                        <label class="icon-label check-box-label" for="allow-item-condition">
                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 12 12" fill="none">
                                <path d="M10 3L4.5 8.5L2 6" stroke="#EE1243" stroke-width="1.6666" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </label>
                        <label class="check-box-label" for="allow-item-condition">@lang('Allow Item Condition')</label>
                    </div>
                    <div class="input-label-wrapper collapse" id="show_item-condition">
                        <label>@lang('Item Condition')</label>
                        <div class="dropdown-container">
                            <select class="form-control nice-select form__control" name="item_condition">
                                <option value="2">@lang('New')</option>
                                <option value="1">@lang('Used')</option>
                            </select>
                        </div>
                    </div>

                    <!-- Preorder -->
                    <div class="gs-checkbox-wrapper" data-bs-toggle="collapse" data-bs-target="#show_preorder">
                        <input type="checkbox" id="allow-preorder" name="preordered_check" value="1">
                        <label class="icon-label check-box-label" for="allow-preorder">
                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 12 12" fill="none">
                                <path d="M10 3L4.5 8.5L2 6" stroke="#EE1243" stroke-width="1.6666" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </label>
                        <label class="check-box-label" for="allow-preorder">@lang('Allow Preorder')</label>
                    </div>
                    <div class="input-label-wrapper collapse" id="show_preorder">
                        <label>@lang('Preorder Status')</label>
                        <div class="dropdown-container">
                            <select class="form-control nice-select form__control" name="preordered">
                                <option value="0">@lang('Available for Sale')</option>
                                <option value="1">@lang('Preorder Only')</option>
                            </select>
                        </div>
                    </div>

                    <!-- Minimum Quantity -->
                    <div class="gs-checkbox-wrapper" data-bs-toggle="collapse" data-bs-target="#show_minimum-qty">
                        <input type="checkbox" id="allow-minimum-qty" name="minimum_qty_check" value="1">
                        <label class="icon-label check-box-label" for="allow-minimum-qty">
                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 12 12" fill="none">
                                <path d="M10 3L4.5 8.5L2 6" stroke="#EE1243" stroke-width="1.6666" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </label>
                        <label class="check-box-label" for="allow-minimum-qty">@lang('Allow Minimum Purchase Qty')</label>
                    </div>
                    <div class="input-label-wrapper collapse" id="show_minimum-qty">
                        <label>@lang('Minimum Purchase Quantity')</label>
                        <input type="number" class="form-control" name="minimum_qty" min="1" placeholder="@lang('Enter Minimum Quantity')">
                    </div>

                    <!-- Shipping Time -->
                    <div class="gs-checkbox-wrapper" data-bs-toggle="collapse" data-bs-target="#show_shipping-time">
                        <input type="checkbox" id="allow-shipping-time" name="shipping_time_check" value="1">
                        <label class="icon-label check-box-label" for="allow-shipping-time">
                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 12 12" fill="none">
                                <path d="M10 3L4.5 8.5L2 6" stroke="#EE1243" stroke-width="1.6666" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </label>
                        <label class="check-box-label" for="allow-shipping-time">@lang('Allow Estimated Shipping Time')</label>
                    </div>
                    <div class="input-label-wrapper collapse" id="show_shipping-time">
                        <label>@lang('Estimated Shipping Time')</label>
                        <input type="text" class="form-control" name="ship" placeholder="@lang('e.g., 2-3 days')">
                    </div>

                    <!-- Wholesale -->
                    <div class="gs-checkbox-wrapper" data-bs-toggle="collapse" data-bs-target="#show_wholesale">
                        <input type="checkbox" name="whole_check" id="allow-wholesale" value="1">
                        <label class="icon-label check-box-label" for="allow-wholesale">
                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 12 12" fill="none">
                                <path d="M10 3L4.5 8.5L2 6" stroke="#EE1243" stroke-width="1.6666" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </label>
                        <label class="check-box-label" for="allow-wholesale">@lang('Allow Wholesale')</label>
                    </div>
                    <div class="input-label-wrapper collapse" id="show_wholesale">
                        <label>@lang('Wholesale Settings')</label>
                        <div class="d-flex flex-column g-4 gap-4" id="wholesale-section">
                            <div class="row row-cols-1 row-cols-md-2 gy-4 position-relative">
                                <div class="col">
                                    <input type="number" class="form-control" name="whole_sell_qty[]" placeholder="@lang('Enter Quantity')">
                                </div>
                                <div class="col position-relative">
                                    <input type="number" step="0.01" class="form-control" name="whole_sell_discount[]" placeholder="@lang('Discount Percentage')">
                                    <button type="button" class="gallery-extra-remove-btn feature-extra-tags-remove-btn remove_wholesale right-1">
                                        <i class="fa-solid fa-xmark"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-md-12 d-flex justify-content-end mt-4">
                            <button class="template-btn outline-btn" id="add-wholesale-btn" type="button">+ @lang('Add More')</button>
                        </div>
                    </div>

                    <!-- Stock -->
                    <div class="input-label-wrapper">
                        <label>@lang('Stock Quantity') <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" name="stock" min="0" placeholder="@lang('Enter Stock Quantity')" required>
                    </div>

                    <!-- Details -->
                    <div class="input-label-wrapper">
                        <label>@lang('Item Details')</label>
                        <textarea class="form-control w-100 nic-edit" id="details" name="details" rows="6"></textarea>
                    </div>

                    <!-- Policy -->
                    <div class="input-label-wrapper">
                        <label>@lang('Buy/Return Policy')</label>
                        <textarea class="form-control w-100 nic-edit" id="policy" name="policy" rows="6"></textarea>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="col-12 col-lg-4">
                <div class="add-catalogItem-form-sidebar">
                    <div class="form-group">
                        <!-- Price -->
                        <div class="input-label-wrapper">
                            <label>@lang('Price') <span class="text-danger">*</span> ({{ $sign->name }})</label>
                            <input type="number" step="0.01" class="form-control" name="price" placeholder="@lang('Enter Price')" required>
                        </div>

                        <!-- Previous Price -->
                        <div class="input-label-wrapper">
                            <label>@lang('Previous Price') ({{ $sign->name }}) <small class="text-muted">@lang('Optional')</small></label>
                            <input type="number" step="0.01" class="form-control" name="previous_price" placeholder="@lang('Enter Previous Price')">
                        </div>

                        <!-- Item Type -->
                        <div class="input-label-wrapper">
                            <label>@lang('Item Type')</label>
                            <div class="dropdown-container">
                                <select id="item_type" class="form-control nice-select form__control" name="item_type">
                                    <option value="normal">@lang('Normal')</option>
                                    <option value="affiliate">@lang('Affiliate')</option>
                                </select>
                            </div>
                        </div>

                        <!-- Affiliate Link -->
                        <div class="input-label-wrapper hidden" id="affiliate_link_wrapper">
                            <label>@lang('Affiliate Link') <span class="text-danger">*</span></label>
                            <input type="url" class="form-control" name="affiliate_link" placeholder="@lang('Enter Affiliate URL')">
                        </div>

                        <!-- Status -->
                        <div class="input-label-wrapper">
                            <label>@lang('Status')</label>
                            <div class="dropdown-container">
                                <select class="form-control nice-select form__control" name="status">
                                    <option value="1">@lang('Active')</option>
                                    <option value="0">@lang('Inactive')</option>
                                </select>
                            </div>
                        </div>

                        <!-- Info Note -->
                        <div class="alert alert-info mt-3">
                            <strong>@lang('Note'):</strong>
                            <p class="mb-0 small">@lang('This form creates your merchant offer for an existing catalog item. Select a catalog item by part number, then add your pricing and stock details.')</p>
                        </div>

                        <!-- Submit Button -->
                        <button class="template-btn w-100 px-20" type="submit" id="submitBtn" disabled>@lang('Create Merchant Item')</button>
                    </div>
                </div>
            </div>
        </form>
        <!-- Form end -->
    </div>
@endsection

@section('script')
    <script src="{{ asset('assets/operator/js/nicEdit.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script>
        (function($) {
            "use strict";

            // Initialize nicEdit
            document.addEventListener("DOMContentLoaded", function() {
                bkLib.onDomLoaded(function() {
                    var editors = document.getElementsByClassName("nic-edit");
                    for (var i = 0; i < editors.length; i++) {
                        new nicEditor().panelInstance(editors[i]);
                    }
                });
            });

            // Initialize Select2 for catalog item search
            $('#catalog_item_select').select2({
                placeholder: '@lang("Search by Part Number...")',
                allowClear: true,
                minimumInputLength: 3,
                ajax: {
                    url: '{{ route("merchant-catalog-item-search-item") }}',
                    dataType: 'json',
                    delay: 300,
                    data: function(params) {
                        return { part_number: params.term };
                    },
                    processResults: function(data) {
                        if (data.success && !data.already_exists) {
                            return {
                                results: [{
                                    id: data.catalog_item.id,
                                    text: data.catalog_item.part_number + ' - ' + data.catalog_item.name,
                                    item: data.catalog_item
                                }]
                            };
                        } else if (data.success && data.already_exists) {
                            return {
                                results: [{
                                    id: 'exists',
                                    text: '@lang("You already have an offer for this item")',
                                    disabled: true
                                }]
                            };
                        } else {
                            return {
                                results: [{
                                    id: 'not_found',
                                    text: data.message || '@lang("No catalog item found")',
                                    disabled: true
                                }]
                            };
                        }
                    },
                    cache: true
                }
            });

            // Handle catalog item selection
            $('#catalog_item_select').on('select2:select', function(e) {
                var data = e.params.data;
                if (data.id && data.id !== 'exists' && data.id !== 'not_found') {
                    var item = data.item;
                    $('#selected_catalog_item_id').val(item.id);
                    $('#previewName').text(item.name);
                    $('#previewPartNumber').text(item.part_number);
                    $('#previewImage').attr('src', item.photo || '{{ asset("assets/images/noimage.png") }}');
                    $('#catalogItemPreview').removeClass('hidden').show();
                    $('#submitBtn').prop('disabled', false);
                }
            });

            // Handle catalog item clear
            $('#catalog_item_select').on('select2:clear', function() {
                $('#selected_catalog_item_id').val('');
                $('#catalogItemPreview').addClass('hidden').hide();
                $('#submitBtn').prop('disabled', true);
            });

            // Item type toggle
            $('#item_type').on('change', function() {
                if ($(this).val() === 'affiliate') {
                    $('#affiliate_link_wrapper').removeClass('hidden').show();
                    $('#affiliate_link_wrapper input').prop('required', true);
                } else {
                    $('#affiliate_link_wrapper').addClass('hidden').hide();
                    $('#affiliate_link_wrapper input').prop('required', false).val('');
                }
            });

            // Add wholesale row
            $(document).on('click', '#add-wholesale-btn', function() {
                $('#wholesale-section').append(`
                    <div class="row row-cols-1 row-cols-md-2 gy-4 position-relative">
                        <div class="col">
                            <input type="number" class="form-control" name="whole_sell_qty[]" placeholder="@lang('Enter Quantity')">
                        </div>
                        <div class="col position-relative">
                            <input type="number" step="0.01" class="form-control" name="whole_sell_discount[]" placeholder="@lang('Discount Percentage')">
                            <button type="button" class="gallery-extra-remove-btn feature-extra-tags-remove-btn remove_wholesale right-1">
                                <i class="fa-solid fa-xmark"></i>
                            </button>
                        </div>
                    </div>
                `);
            });

            // Remove wholesale row
            $(document).on('click', '.remove_wholesale', function() {
                if ($('.remove_wholesale').length > 1) {
                    $(this).closest('.row').remove();
                }
            });

            // Form submit handler
            $('#merchantItemForm').on('submit', function(e) {
                var editors = document.getElementsByClassName('nic-edit');
                for (var i = 0; i < editors.length; i++) {
                    var editorInstance = nicEditors.findEditor(editors[i].id);
                    if (editorInstance) {
                        editors[i].value = editorInstance.getContent();
                    }
                }

                if (!$('#selected_catalog_item_id').val()) {
                    e.preventDefault();
                    alert('@lang("Please select a catalog item first")');
                    return false;
                }
            });

        })(jQuery);
    </script>
@endsection
