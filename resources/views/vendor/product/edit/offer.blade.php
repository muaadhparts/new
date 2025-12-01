@extends('layouts.vendor')
@php
    $isDashboard = true;
    $isVendor = true;
@endphp

@section('content')
<div class="gs-vendor-outlet">
    <!-- breadcrumb start  -->
    <div class="gs-vendor-breadcrumb has-mb">
        <h4 class="text-capitalize">@lang('Edit Offer for Product')</h4>
        <nav style="--bs-breadcrumb-divider: '';" aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('vendor.dashboard') }}">@lang('Dashboard')</a></li>
                <li class="breadcrumb-item"><a href="{{ route('vendor-prod-index') }}">@lang('Products')</a></li>
                <li class="breadcrumb-item active" aria-current="page">@lang('Edit Offer')</li>
            </ol>
        </nav>
    </div>
    <!-- breadcrumb end -->

    <form id="muaadhform" action="{{ route('vendor-prod-update-offer', $merchantProduct->id) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="row">
            <!-- Product Info Preview -->
            <div class="col-12 col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <h5>@lang('Product Information')</h5>
                    </div>
                    <div class="card-body">
                        <div class="text-center mb-3">
                            <img src="{{ filter_var($data->photo, FILTER_VALIDATE_URL) ? $data->photo : ($data->photo ? \Illuminate\Support\Facades\Storage::url($data->photo) : asset('assets/images/noimage.png')) }}"
                                 alt="{{ $data->name }}" class="img-fluid" style="max-height: 200px;">
                        </div>
                        <h6>{{ $data->name }}</h6>
                        <p><strong>@lang('SKU'):</strong> {{ $data->sku }}</p>
                        <p><strong>@lang('Type'):</strong> {{ $data->type }}</p>
                        <p><strong>@lang('Weight'):</strong> {{ $data->weight }} kg</p>
                        @if($data->size)
                            <p><strong>@lang('Sizes'):</strong> {{ is_array($data->size) ? implode(', ', $data->size) : $data->size }}</p>
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
                                           value="{{ $merchantProduct->price * $sign->value }}" required>
                                </div>
                            </div>

                            <!-- Previous Price -->
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">@lang('Previous Price') ({{ $sign->name }})</label>
                                    <input type="number" step="0.01" class="form-control" name="previous_price"
                                           value="{{ $merchantProduct->previous_price ? ($merchantProduct->previous_price * $sign->value) : '' }}">
                                </div>
                            </div>

                            <!-- Stock -->
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">@lang('Stock Quantity*')</label>
                                    <input type="number" class="form-control" name="stock"
                                           value="{{ $merchantProduct->stock }}" required>
                                </div>
                            </div>

                            <!-- Minimum Quantity -->
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">@lang('Minimum Quantity')</label>
                                    <input type="number" class="form-control" name="minimum_qty"
                                           value="{{ $merchantProduct->minimum_qty }}">
                                </div>
                            </div>

                            <!-- Product Condition -->
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">@lang('Product Condition*')</label>
                                    <select class="form-control" name="product_condition" required>
                                        <option value="2" {{ $merchantProduct->product_condition == 2 ? 'selected' : '' }}>@lang('New')</option>
                                        <option value="1" {{ $merchantProduct->product_condition == 1 ? 'selected' : '' }}>@lang('Used')</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Quality Brand -->
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">@lang('Quality Brand*')</label>
                                    <select class="form-control" name="brand_quality_id" required>
                                        <option value="">@lang('Select Quality Brand')</option>
                                        @foreach(\App\Models\QualityBrand::where('is_active', 1)->get() as $qb)
                                            <option value="{{ $qb->id }}" {{ $merchantProduct->brand_quality_id == $qb->id ? 'selected' : '' }}>
                                                {{ $qb->name_en }} {{ $qb->name_ar ? '- ' . $qb->name_ar : '' }}
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
                                           value="{{ $merchantProduct->ship }}">
                                </div>
                            </div>

                            <!-- Colors -->
                            <div class="col-12">
                                <div class="mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="allow_colors" name="color_check"
                                               value="1" {{ !empty($merchantProduct->color_all) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="allow_colors">
                                            @lang('Allow Product Colors')
                                        </label>
                                    </div>
                                </div>

                                <div id="color_section" style="display: {{ !empty($merchantProduct->color_all) ? 'block' : 'none' }};">
                                    <div class="mb-3">
                                        <label class="form-label">@lang('Available Colors')</label>
                                        <div id="color_inputs">
                                            @if(!empty($merchantProduct->color_all))
                                                @php
                                                    $colors = is_array($merchantProduct->color_all) ? $merchantProduct->color_all : explode(',', $merchantProduct->color_all);
                                                    $colorPrices = !empty($merchantProduct->color_price)
                                                        ? (is_array($merchantProduct->color_price) ? $merchantProduct->color_price : explode(',', $merchantProduct->color_price))
                                                        : [];
                                                @endphp
                                                @foreach($colors as $index => $color)
                                                    <div class="row mb-2">
                                                        <div class="col-6">
                                                            <input type="color" class="form-control" name="color_all[]" value="{{ $color }}">
                                                        </div>
                                                        <div class="col-5">
                                                            <input type="number" step="0.01" class="form-control" name="color_price[]"
                                                                   value="{{ isset($colorPrices[$index]) ? $colorPrices[$index] : '' }}"
                                                                   placeholder="@lang('Additional Price')">
                                                        </div>
                                                        <div class="col-1">
                                                            <button type="button" class="btn btn-danger btn-sm remove-color">×</button>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            @else
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
                                            @endif
                                        </div>
                                        <button type="button" class="btn btn-primary btn-sm" id="add_color">@lang('Add Color')</button>
                                    </div>
                                </div>
                            </div>

                            <!-- Wholesale -->
                            <div class="col-12">
                                <div class="mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="allow_wholesale" name="whole_check"
                                               value="1" {{ !empty($merchantProduct->whole_sell_qty) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="allow_wholesale">
                                            @lang('Allow Wholesale')
                                        </label>
                                    </div>
                                </div>

                                <div id="wholesale_section" style="display: {{ !empty($merchantProduct->whole_sell_qty) ? 'block' : 'none' }};">
                                    <div class="row">
                                        <div class="col-6">
                                            <div class="mb-3">
                                                <label class="form-label">@lang('Wholesale Quantity')</label>
                                                <input type="number" class="form-control" name="whole_sell_qty[]"
                                                       value="{{ !empty($merchantProduct->whole_sell_qty) ? (is_array($merchantProduct->whole_sell_qty) ? implode(',', $merchantProduct->whole_sell_qty) : $merchantProduct->whole_sell_qty) : '' }}">
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="mb-3">
                                                <label class="form-label">@lang('Wholesale Discount (%)')</label>
                                                <input type="number" step="0.01" class="form-control" name="whole_sell_discount[]"
                                                       value="{{ !empty($merchantProduct->whole_sell_discount) ? (is_array($merchantProduct->whole_sell_discount) ? implode(',', $merchantProduct->whole_sell_discount) : $merchantProduct->whole_sell_discount) : '' }}">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Preordered -->
                            <div class="col-12">
                                <div class="mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="preordered" name="preordered"
                                               value="1" {{ $merchantProduct->preordered ? 'checked' : '' }}>
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
                                    <textarea class="form-control" name="policy" rows="3">{{ $merchantProduct->policy }}</textarea>
                                </div>
                            </div>

                            <!-- Features Override -->
                            <div class="col-12">
                                <div class="mb-3">
                                    <label class="form-label">@lang('Features (Override Product Features)')</label>
                                    <textarea class="form-control" name="features" rows="3">{{ $merchantProduct->features }}</textarea>
                                </div>
                            </div>

                            <!-- Details -->
                            <div class="col-12">
                                <div class="mb-3">
                                    <label class="form-label">@lang('Additional Details')</label>
                                    <textarea class="form-control" name="details" rows="4">{{ $merchantProduct->details }}</textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-12 text-end">
                <a href="{{ route('vendor-prod-index') }}" class="btn btn-secondary me-2">@lang('Cancel')</a>
                <button type="submit" class="btn btn-primary">@lang('Update Offer')</button>
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