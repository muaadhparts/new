@extends('layouts.merchant')
@php
    $isDashboard = true;
    $isMerchant = true;
@endphp

@section('content')
<div class="gs-merchant-outlet">
    <!-- breadcrumb start  -->
    <div class="gs-merchant-breadcrumb has-mb">
        <h4 class="text-capitalize">@lang('Create Offer for CatalogItem')</h4>
        <nav style="--bs-breadcrumb-divider: '';" aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('merchant.dashboard') }}">@lang('Dashboard')</a></li>
                <li class="breadcrumb-item"><a href="{{ route('merchant-catalog-item-index') }}">@lang('CatalogItems')</a></li>
                <li class="breadcrumb-item"><a href="{{ route('merchant-catalog-item-catalogs') }}">@lang('Catalog')</a></li>
                <li class="breadcrumb-item active" aria-current="page">@lang('Create Offer')</li>
            </ol>
        </nav>
    </div>
    <!-- breadcrumb end -->

    <form id="muaadhform" action="{{ route('merchant-catalog-item-store-offer') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <input type="hidden" name="catalog_item_id" value="{{ $catalogItem->id }}">

        <div class="row">
            <!-- CatalogItem Info Preview -->
            <div class="col-12 col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <h5>@lang('CatalogItem Information')</h5>
                    </div>
                    <div class="card-body">
                        <div class="text-center mb-3">
                            <img src="{{ $catalogItem->photo ? \Illuminate\Support\Facades\Storage::url($catalogItem->photo) : asset('assets/images/noimage.png') }}"
                                 alt="{{ $catalogItem->name }}" class="img-fluid" style="max-height: 200px;">
                        </div>
                        <h6>{{ $catalogItem->name }}</h6>
                        <p><strong>@lang('PART_NUMBER'):</strong> {{ $catalogItem->part_number }}</p>
                        <p><strong>@lang('Type'):</strong> {{ $catalogItem->type }}</p>
                        <p><strong>@lang('Weight'):</strong> {{ $catalogItem->weight }} kg</p>
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
                            <!-- Branch Selection (Required) -->
                            <div class="col-md-12">
                                <div class="mb-3">
                                    <label class="form-label">@lang('Branch / Warehouse*')</label>
                                    @if($branches->count() > 0)
                                        <select class="form-control" name="merchant_branch_id" required>
                                            <option value="">@lang('Select Branch')</option>
                                            @foreach($branches as $branch)
                                                <option value="{{ $branch->id }}">{{ $branch->warehouse_name }}</option>
                                            @endforeach
                                        </select>
                                        <small class="text-muted">@lang('Select the branch where this item will be shipped from')</small>
                                    @else
                                        <div class="alert alert-warning">
                                            @lang('You need to create a branch first.')
                                            <a href="{{ route('merchant.branch.create') }}" class="alert-link">@lang('Create Branch')</a>
                                        </div>
                                    @endif
                                </div>
                            </div>

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

                            <!-- CatalogItem Condition -->
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">@lang('CatalogItem Condition*')</label>
                                    <select class="form-control" name="item_condition" required>
                                        <option value="2">@lang('New')</option>
                                        <option value="1">@lang('Used')</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Quality Brand -->
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">@lang('Quality Brand*')</label>
                                    <select class="form-control" name="quality_brand_id" required>
                                        <option value="">@lang('Select Quality Brand')</option>
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
                                    <label class="form-label">@lang('Policy (Override CatalogItem Policy)')</label>
                                    <textarea class="form-control" name="policy" rows="3"
                                              placeholder="@lang('Enter your specific policy for this catalogItem')"></textarea>
                                </div>
                            </div>

                            <!-- Features Override -->
                            <div class="col-12">
                                <div class="mb-3">
                                    <label class="form-label">@lang('Features (Override CatalogItem Features)')</label>
                                    <textarea class="form-control" name="features" rows="3"
                                              placeholder="@lang('Enter your specific features for this catalogItem')"></textarea>
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
                <a href="{{ route('merchant-catalog-item-catalogs') }}" class="btn btn-secondary me-2">@lang('Cancel')</a>
                <button type="submit" class="btn btn-primary">@lang('Create Offer')</button>
            </div>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Wholesale section toggle
    document.getElementById('allow_wholesale').addEventListener('change', function() {
        document.getElementById('wholesale_section').style.display = this.checked ? 'block' : 'none';
    });
});
</script>
@endsection