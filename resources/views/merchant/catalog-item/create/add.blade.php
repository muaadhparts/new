@extends('layouts.merchant')
@php
    $isDashboard = true;
    $isVendor = true;
@endphp

@section('content')
<div class="gs-vendor-outlet">
    <!-- breadcrumb start  -->
    <div class="gs-vendor-breadcrumb has-mb">
        <div class="gs-deposit-title ms-0 d-flex align-items-center gap-4">
            <a href="{{ route('merchant-catalog-item-index') }}" class="back-btn">
                <i class="fa-solid fa-arrow-left-long"></i>
            </a>
            <h4>@lang('Add Product')</h4>
        </div>
        <ul class="breadcrumb-menu">
            <li>
                <a href="{{ route('merchant.dashboard') }}">@lang('Dashboard')</a>
            </li>
            <li>
                <a href="{{ route('merchant-catalog-item-index') }}">@lang('Products')</a>
            </li>
            <li>
                <a href="#">@lang('Add Product')</a>
            </li>
        </ul>
    </div>
    <!-- breadcrumb end -->

    <!-- Search Product Section -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="fas fa-search me-2"></i>@lang('Search Product by SKU / Part Number')</h5>
        </div>
        <div class="card-body">
            <div class="row align-items-end">
                <div class="col-md-8">
                    <label class="form-label">@lang('Enter Product SKU / Part Number')</label>
                    <input type="text" class="form-control form-control-lg" id="search_sku"
                           placeholder="@lang('Enter SKU or Part Number and press Enter or click Search')">
                </div>
                <div class="col-md-4">
                    <button type="button" class="btn btn-primary btn-lg w-100" id="search_btn">
                        <i class="fas fa-search me-2"></i>@lang('Search')
                    </button>
                </div>
            </div>
            <div id="search_result" class="mt-3"></div>
        </div>
    </div>

    <!-- Product Info & Form (Hidden until product is found) -->
    <div id="product_form_section" style="display: none;">
        <form id="addProductForm" action="{{ route('merchant-catalog-item-store-offer') }}" method="POST">
            @csrf
            <input type="hidden" name="product_id" id="product_id" value="">

            <div class="row">
                <!-- Product Info Preview -->
                <div class="col-12 col-lg-4">
                    <div class="card">
                        <div class="card-header">
                            <h5>@lang('Product Information')</h5>
                            <small class="text-muted">@lang('This information is from the catalog and cannot be changed')</small>
                        </div>
                        <div class="card-body">
                            <div class="text-center mb-3">
                                <img id="product_image" src="{{ asset('assets/images/noimage.png') }}"
                                     alt="" class="img-fluid rounded" style="max-height: 200px;">
                            </div>
                            <h6 id="product_name" class="text-center mb-3"></h6>
                            <table class="table table-sm">
                                <tr>
                                    <th>@lang('SKU'):</th>
                                    <td id="product_sku"></td>
                                </tr>
                                <tr>
                                    <th>@lang('Brand'):</th>
                                    <td id="product_brand"></td>
                                </tr>
                                <tr>
                                    <th>@lang('Category'):</th>
                                    <td id="product_category"></td>
                                </tr>
                                <tr>
                                    <th>@lang('Type'):</th>
                                    <td id="product_type"></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Merchant Offer Form -->
                <div class="col-12 col-lg-8">
                    <div class="card">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0"><i class="fas fa-edit me-2"></i>@lang('Your Offer Details')</h5>
                            <small>@lang('Enter your pricing, stock, and other details')</small>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <!-- Quality Brand -->
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">@lang('Quality Brand*')</label>
                                        <select class="form-control" name="brand_quality_id" required>
                                            <option value="">@lang('Select Quality Brand')</option>
                                            @foreach(\App\Models\QualityBrand::where('is_active', 1)->get() as $qb)
                                                <option value="{{ $qb->id }}">
                                                    {{ $qb->name_en }} {{ $qb->name_ar ? '- ' . $qb->name_ar : '' }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <!-- Product Condition -->
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">@lang('Product Condition*')</label>
                                        <select class="form-control" name="product_condition" required>
                                            <option value="2">@lang('New')</option>
                                            <option value="1">@lang('Used')</option>
                                        </select>
                                    </div>
                                </div>

                                <!-- Price -->
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">@lang('Price*') ({{ $sign->name }})</label>
                                        <input type="number" step="0.01" class="form-control" name="price" required
                                               placeholder="@lang('Enter your selling price')">
                                    </div>
                                </div>

                                <!-- Previous Price -->
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">@lang('Previous Price') ({{ $sign->name }})</label>
                                        <input type="number" step="0.01" class="form-control" name="previous_price"
                                               placeholder="@lang('Optional - for showing discount')">
                                    </div>
                                </div>

                                <!-- Stock -->
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">@lang('Stock Quantity*')</label>
                                        <input type="number" class="form-control" name="stock" required
                                               placeholder="@lang('Enter available quantity')">
                                    </div>
                                </div>

                                <!-- Minimum Quantity -->
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">@lang('Minimum Order Quantity')</label>
                                        <input type="number" class="form-control" name="minimum_qty"
                                               placeholder="@lang('Optional - minimum qty per order')">
                                    </div>
                                </div>

                                <!-- Shipping Time -->
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">@lang('Estimated Shipping Time')</label>
                                        <input type="text" class="form-control" name="ship"
                                               placeholder="@lang('e.g. 3-5 days')">
                                    </div>
                                </div>

                                <!-- Preorder -->
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">&nbsp;</label>
                                        <div class="form-check mt-2">
                                            <input class="form-check-input" type="checkbox" id="preordered" name="preordered" value="1">
                                            <label class="form-check-label" for="preordered">
                                                @lang('Allow Preorder')
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                <!-- Colors -->
                                <div class="col-12">
                                    <div class="mb-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="allow_colors" name="color_check" value="1">
                                            <label class="form-check-label" for="allow_colors">
                                                @lang('Allow Product Colors')
                                            </label>
                                        </div>
                                    </div>

                                    <div id="color_section" style="display: none;">
                                        <div class="mb-3">
                                            <label class="form-label">@lang('Available Colors')</label>
                                            <div id="color_inputs">
                                                <div class="row mb-2">
                                                    <div class="col-6">
                                                        <input type="color" class="form-control" name="color_all[]" value="#ffffff">
                                                    </div>
                                                    <div class="col-5">
                                                        <input type="number" step="0.01" class="form-control" name="color_price[]"
                                                               placeholder="@lang('Additional Price')">
                                                    </div>
                                                    <div class="col-1">
                                                        <button type="button" class="btn btn-danger btn-sm remove-color">&times;</button>
                                                    </div>
                                                </div>
                                            </div>
                                            <button type="button" class="btn btn-primary btn-sm" id="add_color">
                                                <i class="fas fa-plus me-1"></i>@lang('Add Color')
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <!-- Wholesale -->
                                <div class="col-12">
                                    <div class="mb-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="allow_wholesale" name="whole_check" value="1">
                                            <label class="form-check-label" for="allow_wholesale">
                                                @lang('Allow Wholesale')
                                            </label>
                                        </div>
                                    </div>

                                    <div id="wholesale_section" style="display: none;">
                                        <div id="wholesale_inputs">
                                            <div class="row mb-2">
                                                <div class="col-5">
                                                    <label class="form-label">@lang('Quantity')</label>
                                                    <input type="number" class="form-control" name="whole_sell_qty[]"
                                                           placeholder="@lang('Min quantity')">
                                                </div>
                                                <div class="col-5">
                                                    <label class="form-label">@lang('Discount %')</label>
                                                    <input type="number" step="0.01" class="form-control" name="whole_sell_discount[]"
                                                           placeholder="@lang('Discount percentage')">
                                                </div>
                                                <div class="col-2 d-flex align-items-end">
                                                    <button type="button" class="btn btn-danger btn-sm remove-wholesale">&times;</button>
                                                </div>
                                            </div>
                                        </div>
                                        <button type="button" class="btn btn-primary btn-sm" id="add_wholesale">
                                            <i class="fas fa-plus me-1"></i>@lang('Add Tier')
                                        </button>
                                    </div>
                                </div>

                                <!-- Policy Override -->
                                <div class="col-12 mt-3">
                                    <div class="mb-3">
                                        <label class="form-label">@lang('Your Return/Exchange Policy')</label>
                                        <textarea class="form-control" name="policy" rows="3"
                                                  placeholder="@lang('Optional - Your specific policy for this product')"></textarea>
                                    </div>
                                </div>

                                <!-- Additional Details -->
                                <div class="col-12">
                                    <div class="mb-3">
                                        <label class="form-label">@lang('Additional Details')</label>
                                        <textarea class="form-control" name="details" rows="3"
                                                  placeholder="@lang('Optional - Additional information about your offer')"></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mt-4">
                <div class="col-12 text-end">
                    <a href="{{ route('merchant-catalog-item-index') }}" class="btn btn-secondary me-2">@lang('Cancel')</a>
                    <button type="submit" class="btn btn-success btn-lg">
                        <i class="fas fa-plus me-2"></i>@lang('Add Product')
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('search_sku');
    const searchBtn = document.getElementById('search_btn');
    const searchResult = document.getElementById('search_result');
    const productFormSection = document.getElementById('product_form_section');

    // Search on Enter key
    searchInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            searchProduct();
        }
    });

    // Search on button click
    searchBtn.addEventListener('click', searchProduct);

    function searchProduct() {
        const sku = searchInput.value.trim();
        if (!sku) {
            searchResult.innerHTML = '<div class="alert alert-warning">@lang("Please enter a SKU or Part Number")</div>';
            return;
        }

        searchResult.innerHTML = '<div class="text-center"><i class="fas fa-spinner fa-spin fa-2x"></i><br>@lang("Searching...")</div>';
        productFormSection.style.display = 'none';

        fetch('{{ route("vendor-prod-search-sku") }}?sku=' + encodeURIComponent(sku))
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const product = data.product;

                    // Check if vendor already has this product
                    if (data.already_exists) {
                        searchResult.innerHTML = `
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                @lang("You already have an offer for this product.")
                                <a href="${data.edit_url}" class="btn btn-sm btn-primary ms-2">@lang("Edit Offer")</a>
                            </div>
                        `;
                        return;
                    }

                    // Fill product info
                    document.getElementById('product_id').value = product.id;
                    document.getElementById('product_name').textContent = product.name;
                    document.getElementById('product_sku').textContent = product.sku;
                    document.getElementById('product_brand').textContent = product.brand || '@lang("N/A")';
                    document.getElementById('product_category').textContent = product.category || '@lang("N/A")';
                    document.getElementById('product_type').textContent = product.type;

                    if (product.photo) {
                        document.getElementById('product_image').src = product.photo;
                    }

                    searchResult.innerHTML = '<div class="alert alert-success"><i class="fas fa-check-circle me-2"></i>@lang("Product found! Fill in your offer details below.")</div>';
                    productFormSection.style.display = 'block';
                } else {
                    searchResult.innerHTML = `<div class="alert alert-danger"><i class="fas fa-times-circle me-2"></i>${data.message}</div>`;
                }
            })
            .catch(error => {
                searchResult.innerHTML = '<div class="alert alert-danger">@lang("An error occurred. Please try again.")</div>';
                console.error('Error:', error);
            });
    }

    // Color section toggle
    document.getElementById('allow_colors').addEventListener('change', function() {
        document.getElementById('color_section').style.display = this.checked ? 'block' : 'none';
    });

    // Wholesale section toggle
    document.getElementById('allow_wholesale').addEventListener('change', function() {
        document.getElementById('wholesale_section').style.display = this.checked ? 'block' : 'none';
    });

    // Add color
    document.getElementById('add_color').addEventListener('click', function() {
        const colorInputs = document.getElementById('color_inputs');
        const newRow = document.createElement('div');
        newRow.className = 'row mb-2';
        newRow.innerHTML = `
            <div class="col-6">
                <input type="color" class="form-control" name="color_all[]" value="#ffffff">
            </div>
            <div class="col-5">
                <input type="number" step="0.01" class="form-control" name="color_price[]" placeholder="@lang('Additional Price')">
            </div>
            <div class="col-1">
                <button type="button" class="btn btn-danger btn-sm remove-color">&times;</button>
            </div>
        `;
        colorInputs.appendChild(newRow);
    });

    // Add wholesale tier
    document.getElementById('add_wholesale').addEventListener('click', function() {
        const wholesaleInputs = document.getElementById('wholesale_inputs');
        const newRow = document.createElement('div');
        newRow.className = 'row mb-2';
        newRow.innerHTML = `
            <div class="col-5">
                <input type="number" class="form-control" name="whole_sell_qty[]" placeholder="@lang('Min quantity')">
            </div>
            <div class="col-5">
                <input type="number" step="0.01" class="form-control" name="whole_sell_discount[]" placeholder="@lang('Discount percentage')">
            </div>
            <div class="col-2 d-flex align-items-end">
                <button type="button" class="btn btn-danger btn-sm remove-wholesale">&times;</button>
            </div>
        `;
        wholesaleInputs.appendChild(newRow);
    });

    // Remove color/wholesale handlers
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('remove-color')) {
            const colorInputs = document.getElementById('color_inputs');
            if (colorInputs.querySelectorAll('.row').length > 1) {
                e.target.closest('.row').remove();
            }
        }
        if (e.target.classList.contains('remove-wholesale')) {
            const wholesaleInputs = document.getElementById('wholesale_inputs');
            if (wholesaleInputs.querySelectorAll('.row').length > 1) {
                e.target.closest('.row').remove();
            }
        }
    });
});
</script>
@endsection
