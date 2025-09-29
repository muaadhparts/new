@extends('layouts.vendor')

@section('content')
<div class="gs-vendor-outlet">
    <!-- breadcrumb start  -->
    <div class="gs-vendor-breadcrumb has-mb">
        <h4 class="text-capitalize">@lang('Create Offer for Product')</h4>
        <nav style="--bs-breadcrumb-divider: '';" aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('vendor.dashboard') }}">@lang('Dashboard')</a></li>
                <li class="breadcrumb-item"><a href="{{ route('vendor-prod-index') }}">@lang('Products')</a></li>
                <li class="breadcrumb-item"><a href="{{ route('vendor-prod-catalogs') }}">@lang('Catalog')</a></li>
                <li class="breadcrumb-item active" aria-current="page">@lang('Create Offer')</li>
            </ol>
        </nav>
    </div>
    <!-- breadcrumb end -->

    <form id="geniusform" action="{{ route('vendor-prod-store-offer') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <input type="hidden" name="product_id" value="{{ $product->id }}">

        <div class="row">
            <!-- Product Info Preview -->
            <div class="col-12 col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <h5>@lang('Product Information')</h5>
                    </div>
                    <div class="card-body">
                        <div class="text-center mb-3">
                            <img src="{{ asset('assets/images/products/' . $product->photo) }}"
                                 alt="{{ $product->name }}" class="img-fluid" style="max-height: 200px;">
                        </div>
                        <h6>{{ $product->name }}</h6>
                        <p><strong>@lang('SKU'):</strong> {{ $product->sku }}</p>
                        <p><strong>@lang('Type'):</strong> {{ $product->type }}</p>
                        <p><strong>@lang('Weight'):</strong> {{ $product->weight }} kg</p>
                        @if($product->size)
                            <p><strong>@lang('Sizes'):</strong> {{ is_array($product->size) ? implode(', ', $product->size) : $product->size }}</p>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Merchant Offer Form -->
            <div class="col-12 col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h5>@lang('Your Offer Details')</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <!-- Price -->
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">@lang('Price*') ({{ $sign->name }})</label>
                                    <input type="number" step="0.01" class="form-control" name="price"
                                           placeholder="@lang('Enter Price')" required>
                                </div>
                            </div>

                            <!-- Previous Price -->
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">@lang('Previous Price') ({{ $sign->name }})</label>
                                    <input type="number" step="0.01" class="form-control" name="previous_price"
                                           placeholder="@lang('Enter Previous Price')">
                                </div>
                            </div>

                            <!-- Stock -->
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">@lang('Stock Quantity*')</label>
                                    <input type="number" class="form-control" name="stock"
                                           placeholder="@lang('Enter Stock Quantity')" required>
                                </div>
                            </div>

                            <!-- Minimum Quantity -->
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">@lang('Minimum Quantity')</label>
                                    <input type="number" class="form-control" name="minimum_qty"
                                           placeholder="@lang('Enter Minimum Quantity')">
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

                            <!-- Brand Quality -->
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">@lang('Brand Quality*')</label>
                                    <select class="form-control" name="brand_quality_id" required>
                                        <option value="">@lang('Select Brand Quality')</option>
                                        @foreach(\App\Models\QualityBrand::active()->get() as $qualityBrand)
                                            <option value="{{ $qualityBrand->id }}">
                                                {{ $qualityBrand->display_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <!-- Shipping Time -->
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">@lang('Shipping Time')</label>
                                    <input type="text" class="form-control" name="ship"
                                           placeholder="@lang('e.g., 2-3 days')">
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
                                                    <button type="button" class="btn btn-danger btn-sm remove-color">×</button>
                                                </div>
                                            </div>
                                        </div>
                                        <button type="button" class="btn btn-primary btn-sm" id="add_color">@lang('Add Color')</button>
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
                                    <div class="row">
                                        <div class="col-6">
                                            <div class="mb-3">
                                                <label class="form-label">@lang('Wholesale Quantity')</label>
                                                <input type="number" class="form-control" name="whole_sell_qty[]"
                                                       placeholder="@lang('Min Quantity')">
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="mb-3">
                                                <label class="form-label">@lang('Wholesale Discount (%)')</label>
                                                <input type="number" step="0.01" class="form-control" name="whole_sell_discount[]"
                                                       placeholder="@lang('Discount Percentage')">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Preordered -->
                            <div class="col-12">
                                <div class="mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="preordered" name="preordered" value="1">
                                        <label class="form-check-label" for="preordered">
                                            @lang('Allow Preorder')
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <!-- Policy Override -->
                            <div class="col-12">
                                <div class="mb-3">
                                    <label class="form-label">@lang('Policy (Override Product Policy)')</label>
                                    <textarea class="form-control" name="policy" rows="3"
                                              placeholder="@lang('Enter your specific policy for this product')"></textarea>
                                </div>
                            </div>

                            <!-- Features Override -->
                            <div class="col-12">
                                <div class="mb-3">
                                    <label class="form-label">@lang('Features (Override Product Features)')</label>
                                    <textarea class="form-control" name="features" rows="3"
                                              placeholder="@lang('Enter your specific features for this product')"></textarea>
                                </div>
                            </div>

                            <!-- Details -->
                            <div class="col-12">
                                <div class="mb-3">
                                    <label class="form-label">@lang('Additional Details')</label>
                                    <textarea class="form-control" name="details" rows="4"
                                              placeholder="@lang('Enter additional details about your offer')"></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-12 text-end">
                <a href="{{ route('vendor-prod-catalogs') }}" class="btn btn-secondary me-2">@lang('Cancel')</a>
                <button type="submit" class="btn btn-primary">@lang('Create Offer')</button>
            </div>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Color section toggle
    document.getElementById('allow_colors').addEventListener('change', function() {
        document.getElementById('color_section').style.display = this.checked ? 'block' : 'none';
    });

    // Wholesale section toggle
    document.getElementById('allow_wholesale').addEventListener('change', function() {
        document.getElementById('wholesale_section').style.display = this.checked ? 'block' : 'none';
    });

    // Add color functionality
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
                <button type="button" class="btn btn-danger btn-sm remove-color">×</button>
            </div>
        `;
        colorInputs.appendChild(newRow);
    });

    // Remove color functionality
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('remove-color')) {
            e.target.closest('.row').remove();
        }
    });
});
</script>
@endsection