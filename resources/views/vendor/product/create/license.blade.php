@extends('layouts.unified')
@php
    $isDashboard = true;
    $isVendor = true;
@endphp
@section('css')
    <link href="{{ asset('assets/admin/css/jquery.Jcrop.css') }}" rel="stylesheet" />
    <link href="{{ asset('assets/admin/css/Jcrop-style.css') }}" rel="stylesheet" />
@endsection

@section('content')
    <div class="gs-vendor-outlet">
        <!-- breadcrumb start  -->
        <div class="gs-vendor-breadcrumb has-mb">
            <div class="gs-deposit-title ms-0 d-flex align-items-center gap-4">
                <a href="{{ route('vendor-prod-index') }}" class="back-btn">
                    <i class="fa-solid fa-arrow-left-long"></i>
                </a>
                <h4>@lang('Add License Product')</h4>
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
                    <a href="{{ route('vendor-prod-index') }}" class="text-capitalize"> @lang('Products') </a>
                </li>
                <li>
                    <a href="#" class="text-capitalize"> @lang('Add License Product') </a>
                </li>
            </ul>
        </div>
        <!-- breadcrumb end -->

        <!-- add product form start  -->
        <form class="row gy-3 gy-lg-4 add-product-form" id="myForm" action="{{ route('vendor-prod-store') }}"
            method="POST" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="type" value="License">
            <!-- inputes of license product start  -->
            <div class="col-12 col-lg-8 license-product-inputes-wrapper show">
                <div class="form-group">
                    <!-- Part Number -->
                    <div class="input-label-wrapper">
                        <label>@lang('Product Part Number* (SKU)')</label>
                        <input type="text" class="form-control" name="part_number" placeholder="@lang('Enter Product Part Number')" required>
                        <small class="text-muted">@lang('Enter the part number of the license product you want to sell')</small>
                    </div>

                    <!-- License Type -->
                    <div class="input-label-wrapper">
                        <label>@lang('License Type')</label>
                        <input type="text" class="form-control" name="licence_type" placeholder="@lang('Enter License Type (e.g., Personal, Commercial)')">
                    </div>

                    <!-- License Keys/Quantities -->
                    <div class="input-label-wrapper">
                        <label>@lang('License Keys & Quantities')</label>
                        <div id="license-section">
                            <div class="row gy-3 mb-3">
                                <div class="col-md-6">
                                    <input type="text" class="form-control" name="license[]" placeholder="@lang('Enter License Key')">
                                </div>
                                <div class="col-md-4">
                                    <input type="number" class="form-control" name="license_qty[]" placeholder="@lang('Quantity')">
                                </div>
                                <div class="col-md-2">
                                    <button type="button" class="btn btn-danger remove_license text-white w-100">
                                        <i class="fa fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <button class="template-btn outline-btn" id="license-btn" type="button">+@lang('Add More License')</button>
                    </div>

                    <!-- Allow Product Condition Checkbox -->
                    <div class="gs-checkbox-wrapper" aria-controls="show_child-category" role="region"
                        data-bs-toggle="collapse" data-bs-target="#show_child-category">
                        <input type="checkbox" id="allow-product-condition" name="product_condition_check" value="1">
                        <label class="icon-label check-box-label" for="allow-product-condition">
                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 12 12"
                                fill="none">
                                <path d="M10 3L4.5 8.5L2 6" stroke="#EE1243" stroke-width="1.6666" stroke-linecap="round"
                                    stroke-linejoin="round" />
                            </svg>
                        </label>
                        <label class="check-box-label" for="allow-product-condition">@lang('Allow Product Condition')</label>
                    </div>
                    <!-- Product Condition -->
                    <div class="input-label-wrapper collapse" id="show_child-category">
                        <label for="child-category-select">@lang('Product Condition*')</label>
                        <div class="dropdown-container">
                            <select id="child-category-select" class="form-control nice-select form__control"
                                name="product_condition">
                                <option value="2">{{ __('New') }}</option>
                                <option value="1">{{ __('Used') }}</option>
                            </select>
                        </div>
                    </div>

                    <!-- Allow Product Preorder Checkbox -->
                    <div class="gs-checkbox-wrapper" aria-controls="show_product-preorder" role="region"
                        data-bs-toggle="collapse" data-bs-target="#show_product-preorder">
                        <input type="checkbox" id="allow-product-preorder" name="preordered_check" value="1">
                        <label class="icon-label check-box-label" for="allow-product-preorder">
                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 12 12"
                                fill="none">
                                <path d="M10 3L4.5 8.5L2 6" stroke="#EE1243" stroke-width="1.6666" stroke-linecap="round"
                                    stroke-linejoin="round" />
                            </svg>
                        </label>
                        <label class="check-box-label" for="allow-product-preorder">@lang('Allow Product Preorder')</label>
                    </div>
                    <!-- Product Preorder -->
                    <div class="input-label-wrapper collapse" id="show_product-preorder">
                        <label for="product-preorder-select">@lang('Product Preorder*')</label>
                        <div class="dropdown-container">
                            <select id="product-preorder-select" class="form-control nice-select form__control"
                                name="preordered">
                                <option value="1">{{ __('Sale') }}</option>
                                <option value="2">{{ __('Preordered') }}</option>
                            </select>
                        </div>
                    </div>

                    <!-- Allow Minimum Order Qty Checkbox -->
                    <div class="gs-checkbox-wrapper" aria-controls="show_minimum-order" role="region"
                        data-bs-toggle="collapse" data-bs-target="#show_minimum-order">
                        <input type="checkbox" id="allow-minimum-order" name="minimum_qty_check" value="1">
                        <label class="icon-label check-box-label" for="allow-minimum-order">
                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 12 12"
                                fill="none">
                                <path d="M10 3L4.5 8.5L2 6" stroke="#EE1243" stroke-width="1.6666" stroke-linecap="round"
                                    stroke-linejoin="round" />
                            </svg>
                        </label>
                        <label class="check-box-label" for="allow-minimum-order">@lang('Allow Minimum Order Qty')</label>
                    </div>
                    <!-- Product Minimum Order Qty -->
                    <div class="input-label-wrapper collapse" id="show_minimum-order">
                        <label>@lang('Product Minimum Order Qty*')</label>
                        <input type="text" class="form-control" name="minimum_qty" placeholder="@lang('Minimum Order Qty ')">
                    </div>

                    <!-- Allow Estimated Shipping Time Checkbox -->
                    <div class="gs-checkbox-wrapper" aria-controls="show_estimated-shipping-time" role="region"
                        data-bs-toggle="collapse" data-bs-target="#show_estimated-shipping-time">
                        <input type="checkbox" id="allow-estimated-shipping-time" name="shipping_time_check"
                            value="1">
                        <label class="icon-label check-box-label" for="allow-estimated-shipping-time">
                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 12 12"
                                fill="none">
                                <path d="M10 3L4.5 8.5L2 6" stroke="#EE1243" stroke-width="1.6666" stroke-linecap="round"
                                    stroke-linejoin="round" />
                            </svg>
                        </label>
                        <label class="check-box-label" for="allow-estimated-shipping-time">@lang('Allow Estimated Shipping Time')</label>
                    </div>
                    <!-- Estimated Shipping Time -->
                    <div class="input-label-wrapper collapse" id="show_estimated-shipping-time">
                        <label>@lang('Estimated Shipping Time*')</label>
                        <input type="text" class="form-control" name="ship" placeholder=" @lang('Estimated Shipping Time') ">
                    </div>

                    <!-- Allow Product Whole Sell Checkbox -->
                    <div class="gs-checkbox-wrapper" aria-controls="show_product-whole-sell" role="region"
                        data-bs-toggle="collapse" data-bs-target="#show_product-whole-sell">
                        <input type="checkbox" name="whole_check" id="allow-product-whole-sell">
                        <label class="icon-label check-box-label" for="allow-product-whole-sell">
                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 12 12"
                                fill="none">
                                <path d="M10 3L4.5 8.5L2 6" stroke="#EE1243" stroke-width="1.6666" stroke-linecap="round"
                                    stroke-linejoin="round" />
                            </svg>
                        </label>
                        <label class="check-box-label" for="allow-product-whole-sell">@lang('Allow Product Whole Sell')</label>
                    </div>
                    <!-- Product Whole Sell -->
                    <div class="input-label-wrapper collapse" id="show_product-whole-sell">
                        <label>@lang('Allow Product Whole Sell')</label>

                        <div class="d-flex flex-column g-4 gap-4" id="whole-section">
                            <div class="row row-cols-1 row-cols-md-2 gy-4 postion-relative">
                                <div class="col">
                                    <input type="text" class="form-control" name="whole_sell_qty[]"
                                        placeholder="@lang('Enter Quantity') ">
                                </div>
                                <div class="col position-relative">
                                    <input type="text" class="form-control" name="whole_sell_discount[]"
                                        placeholder="@lang('Enter Discount Percentage') ">
                                    <button type="button"
                                        class="gallery-extra-remove-btn feature-extra-tags-remove-btn remove_whole_sell right-1"><i
                                            class="fa-solid fa-xmark"></i></button>
                                </div>
                            </div>
                        </div>

                        <div class="col-12 col-md-12 d-flex justify-content-end mt-4">
                            <button class="template-btn outline-btn" id="whole-btn"
                                type="button">+@lang('Add More Field')</button>
                        </div>
                    </div>

                    <div class="input-label-wrapper" id="default_stock">
                        <label>@lang('Product Stock')</label>
                        <input type="number" class="form-control" name="stock" placeholder="@lang('Enter Product Stock') " required>
                    </div>

                    <!-- Product Description Override (Optional) -->
                    <div class="input-label-wrapper">
                        <label>@lang('Product Description Override (Optional)')</label>
                        <textarea style="width: 100%;" class="form-control w-100 nic-edit" id="details" name="details" rows="6" placeholder="@lang('Leave empty to use catalog description')"></textarea>
                        <small class="text-muted">@lang('Override the catalog product description with your own')</small>
                    </div>
                    <!-- Product Buy/Return Policy Override (Optional) -->
                    <div class="input-label-wrapper">
                        <label>@lang('Product Buy/Return Policy Override (Optional)')</label>
                        <textarea class="form-control w-100 nic-edit" name="policy" id="policy" rows="6" placeholder="@lang('Leave empty to use catalog policy')"></textarea>
                        <small class="text-muted">@lang('Override the catalog product policy with your own')</small>
                    </div>
                </div>
            </div>

            <!-- form sidebar start  -->
            <div class="col-12 col-lg-4">
                <div class="add-product-form-sidebar">
                    <div class="form-group">
                        <!-- Product Current Price -->
                        <div class="input-label-wrapper">
                            <label>@lang('Product Current Price') ({{ $sign->name }})</label>
                            <input type="text" class="form-control" name="price" placeholder="e.g 20" required>
                        </div>
                        <!-- Product Discount Price -->
                        <div class="input-label-wrapper">
                            <label>@lang('Product Discount Price* (Optional)')</label>
                            <input type="text" class="form-control" name="previous_price" placeholder="e.g 20">
                        </div>
                        <!-- Create Merchant Product Button  -->
                        <button class="template-btn w-100 px-20" type="submit">@lang('Create Merchant Product')</button>
                    </div>
                </div>
            </div>
            <!-- form sidebar end  -->
        </form>
        <!-- add product form end -->

    </div>
@endsection

@section('script')
    <script src="{{ asset('assets/admin/js/nicEdit.js') }}"></script>
    <script src="{{ asset('assets/admin/js/jquery.Jcrop.js') }}"></script>
    <script src="{{ asset('assets/admin/js/jquery.SimpleCropper.js') }}"></script>
    <script src="{{ asset('assets/admin/js/select2.js') }}"></script>

    <script>
        (function($) {
            "use strict";

            document.addEventListener("DOMContentLoaded", function() {
                bkLib.onDomLoaded(function() {
                    var editors = document.getElementsByClassName("nic-edit");
                    for (var i = 0; i < editors.length; i++) {
                        new nicEditor().panelInstance(editors[i]);
                    }
                });
            });

            $(document).on('click', "#license-btn", function() {
                $("#license-section").append(
                    `<div class="row gy-3 mb-3">
                        <div class="col-md-6">
                            <input type="text" class="form-control" name="license[]" placeholder="@lang('Enter License Key')">
                        </div>
                        <div class="col-md-4">
                            <input type="number" class="form-control" name="license_qty[]" placeholder="@lang('Quantity')">
                        </div>
                        <div class="col-md-2">
                            <button type="button" class="btn btn-danger remove_license text-white w-100">
                                <i class="fa fa-trash"></i>
                            </button>
                        </div>
                    </div>`
                );
            });

            $(document).on('click', ".remove_license", function() {
                if ($('.remove_license').length > 1) {
                    $(this).parent().parent().remove();
                }
            });

            $(document).on('click', "#whole-btn", function() {
                $("#whole-section").append(
                    `  <div class="row row-cols-1 row-cols-md-2 gy-4 postion-relative">
                                <div class="col">
                                    <input type="text" class="form-control" name="whole_sell_qty[]"
                                        placeholder="@lang('Enter Quantity') ">
                                </div>
                                <div class="col position-relative">
                                    <input type="text" class="form-control" name="whole_sell_discount[]"
                                        placeholder="@lang('Enter Discount Percentage') ">
                                    <button type="button" class="gallery-extra-remove-btn feature-extra-tags-remove-btn remove_whole_sell right-1"><i
                                            class="fa-solid fa-xmark"></i></button>
                                </div>
                            </div>`
                );
            });

            $(document).on('click', ".remove_whole_sell", function() {
                if ($('.remove_whole_sell').length > 1) {
                    $(this).parent().parent().remove();
                }
            });

            $('#myForm').on('submit', function(e) {
                e.preventDefault();

                var editors = document.getElementsByClassName('nic-edit');
                for (var i = 0; i < editors.length; i++) {
                    var editorInstance = nicEditors.findEditor(editors[i].id);
                    if (editorInstance) {
                        editors[i].value = editorInstance.getContent();
                    }
                }

                this.submit();
            });

        })(jQuery);
    </script>
@endsection