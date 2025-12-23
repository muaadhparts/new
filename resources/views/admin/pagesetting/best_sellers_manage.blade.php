@extends('layouts.admin')

@section('content')

<div class="content-area">
    <div class="mr-breadcrumb">
        <div class="row">
            <div class="col-lg-12">
                <h4 class="heading">{{ __('Best Sellers') }}</h4>
                <ul class="links">
                    <li>
                        <a href="{{ route('admin.dashboard') }}">{{ __('Dashboard') }} </a>
                    </li>
                    <li>
                        <a href="javascript:;">{{ __('Home Page Settings') }}</a>
                    </li>
                    <li>
                        <a href="{{ route('admin-ps-best-sellers') }}">{{ __('Best Sellers') }}</a>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    {{-- Current Best Month Products Section --}}
    <div class="add-product-content1 add-product-content2 mb-4">
        <div class="row">
            <div class="col-lg-12">
                <div class="product-description">
                    <div class="body-area">
                        <div class="row mb-4">
                            <div class="col-md-8">
                                <h5 class="mb-3">{{ __('Current Best Sellers') }}</h5>
                            </div>
                            <div class="col-md-4">
                                <button type="button" class="btn btn-success float-end" data-bs-toggle="modal" data-bs-target="#addBestModal">
                                    <i class="fas fa-plus"></i> {{ __('Add Product') }}
                                </button>
                            </div>
                        </div>

                        <div class="table-responsive" id="bestProductsTable">
                            @if($bestProducts->count() > 0)
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th style="width: 80px;">{{ __('Image') }}</th>
                                        <th>{{ __('Product') }}</th>
                                        <th>{{ __('Brand') }} / {{ __('Quality') }} / {{ __('Vendor') }}</th>
                                        <th>{{ __('Price') }}</th>
                                        <th>{{ __('Stock') }}</th>
                                        <th style="width: 60px;"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($bestProducts as $mp)
                                    @php
                                        // Get localized name
                                        $productName = app()->getLocale() == 'ar'
                                            ? ($mp->product->label_ar ?: $mp->product->label_en ?: $mp->product->name)
                                            : ($mp->product->label_en ?: $mp->product->name);

                                        // Get photo URL like homepage
                                        $photo = $mp->product->photo
                                            ? (filter_var($mp->product->photo, FILTER_VALIDATE_URL)
                                                ? $mp->product->photo
                                                : Storage::url($mp->product->photo))
                                            : asset('assets/images/noimage.png');

                                        // Brand (Toyota, Nissan, etc.)
                                        $brandName = $mp->product->brand?->localized_name;
                                        $brandLogo = $mp->product->brand?->photo_url;

                                        // Quality brand (OEM, Aftermarket, etc.)
                                        $qualityName = $mp->qualityBrand?->localized_name;
                                        $qualityLogo = $mp->qualityBrand?->logo_url;

                                        // Vendor
                                        $vendorName = app()->getLocale() == 'ar'
                                            ? ($mp->user->shop_name_ar ?: $mp->user->shop_name)
                                            : $mp->user->shop_name;
                                    @endphp
                                    <tr id="best-row-{{ $mp->id }}">
                                        <td>
                                            <img src="{{ $photo }}"
                                                 alt="{{ $productName }}"
                                                 style="width: 60px; height: 60px; object-fit: contain; border-radius: 8px; border: 1px solid #eee;">
                                        </td>
                                        <td>
                                            <strong>{{ $productName }}</strong>
                                            <br><small class="text-muted">SKU: {{ $mp->product->sku }}</small>
                                        </td>
                                        <td>
                                            <div class="d-flex flex-wrap gap-2">
                                                {{-- Brand --}}
                                                @if($brandName)
                                                <span class="badge bg-primary d-inline-flex align-items-center gap-1">
                                                    @if($brandLogo)
                                                        <img src="{{ $brandLogo }}" alt="" style="width: 16px; height: 16px; object-fit: contain;">
                                                    @endif
                                                    {{ $brandName }}
                                                </span>
                                                @endif

                                                {{-- Quality Brand --}}
                                                @if($qualityName)
                                                <span class="badge bg-info d-inline-flex align-items-center gap-1">
                                                    @if($qualityLogo)
                                                        <img src="{{ $qualityLogo }}" alt="" style="width: 16px; height: 16px; object-fit: contain;">
                                                    @endif
                                                    {{ $qualityName }}
                                                </span>
                                                @endif

                                                {{-- Vendor --}}
                                                <span class="badge bg-secondary d-inline-flex align-items-center gap-1">
                                                    <i class="fas fa-store"></i>
                                                    {{ $vendorName }}
                                                </span>
                                            </div>
                                        </td>
                                        <td>
                                            <strong>{{ \App\Models\Product::convertPrice($mp->price) }}</strong>
                                            @if($mp->previous_price && $mp->previous_price > $mp->price)
                                                <br><small class="text-muted text-decoration-line-through">{{ \App\Models\Product::convertPrice($mp->previous_price) }}</small>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge {{ $mp->stock > 0 ? 'bg-success' : 'bg-danger' }}">
                                                {{ $mp->stock ?? 0 }}
                                            </span>
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-danger btn-sm remove-best"
                                                    data-id="{{ $mp->id }}" title="{{ __('Remove') }}">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                            @else
                            <div class="alert alert-info" id="noBestAlert">
                                {{ __('No best sellers configured. Click "Add Product" to add products.') }}
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Add Best Seller Product Modal --}}
<div class="modal fade" id="addBestModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('Add Product to Best Sellers') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                {{-- Step 1: Search Products --}}
                <div id="step1-search">
                    <div class="mb-3">
                        <label class="form-label">{{ __('Search by SKU or Product Name') }}</label>
                        <input type="text" id="bestProductSearch" class="form-control" placeholder="{{ __('Enter SKU or product name...') }}">
                    </div>

                    <div id="searchResults" class="mt-3">
                        <p class="text-muted">{{ __('Start typing to search for products...') }}</p>
                    </div>
                </div>

                {{-- Step 2: Select Merchant --}}
                <div id="step2-merchants" style="display: none;">
                    <div class="d-flex align-items-center mb-3">
                        <button type="button" class="btn btn-outline-secondary btn-sm me-3" id="backToSearch">
                            <i class="fas fa-arrow-left"></i> {{ __('Back') }}
                        </button>
                        <div class="d-flex align-items-center">
                            <img id="selectedProductImg" src="" alt="" style="width: 50px; height: 50px; object-fit: contain; border-radius: 8px; margin-right: 10px;">
                            <div>
                                <strong id="selectedProductName"></strong>
                                <small class="text-muted d-block" id="selectedProductSku"></small>
                            </div>
                        </div>
                    </div>

                    <h6>{{ __('Select Vendor & Quality Brand') }}:</h6>
                    <div id="merchantsList" class="mt-3">
                        <p class="text-muted">{{ __('Loading...') }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.merchant-card {
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    padding: 12px;
    margin-bottom: 10px;
    transition: all 0.2s;
}
.merchant-card:hover {
    border-color: #007bff;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}
.merchant-card.added {
    background-color: #d4edda;
    border-color: #28a745;
}
.brand-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: 500;
}
.brand-badge img {
    width: 18px;
    height: 18px;
    object-fit: contain;
}
.brand-badge.brand { background: #e3f2fd; color: #1565c0; }
.brand-badge.quality { background: #e8f5e9; color: #2e7d32; }
.brand-badge.vendor { background: #fce4ec; color: #c2185b; }
</style>

@endsection

@section('scripts')
<script>
$(document).ready(function() {
    let searchTimeout;
    let selectedProductId = null;
    let selectedProductPhoto = '';

    // Step 1: Search products
    $('#bestProductSearch').on('keyup', function() {
        clearTimeout(searchTimeout);
        const query = $(this).val();

        if (query.length < 2) {
            $('#searchResults').html('<p class="text-muted">{{ __("Start typing to search for products...") }}</p>');
            return;
        }

        searchTimeout = setTimeout(function() {
            $('#searchResults').html('<div class="text-center py-3"><i class="fas fa-spinner fa-spin fa-2x"></i></div>');

            $.get('{{ route("admin-ps-best-sellers-search") }}', { q: query }, function(data) {
                if (data.length === 0) {
                    $('#searchResults').html('<p class="text-muted">{{ __("No products found") }}</p>');
                    return;
                }

                let html = '<div class="list-group">';
                data.forEach(function(product) {
                    html += `
                        <a href="javascript:;" class="list-group-item list-group-item-action d-flex align-items-center select-product"
                           data-id="${product.product_id}"
                           data-name="${product.name}"
                           data-sku="${product.sku}"
                           data-photo="${product.photo}">
                            <img src="${product.photo}" alt="" style="width: 50px; height: 50px; object-fit: contain; border-radius: 8px;" class="me-3"
                                 onerror="this.src='{{ asset('assets/images/noimage.png') }}'">
                            <div class="flex-grow-1">
                                <strong>${product.name}</strong><br>
                                <small class="text-muted">SKU: ${product.sku}</small>
                            </div>
                            <div class="text-end">
                                <span class="badge bg-info">${product.merchants_count} {{ __("vendors") }}</span>
                                <i class="fas fa-chevron-right ms-2"></i>
                            </div>
                        </a>
                    `;
                });
                html += '</div>';
                $('#searchResults').html(html);
            });
        }, 300);
    });

    // Step 2: Select product and show merchants
    $(document).on('click', '.select-product', function() {
        selectedProductId = $(this).data('id');
        const name = $(this).data('name');
        const sku = $(this).data('sku');
        selectedProductPhoto = $(this).data('photo');

        $('#selectedProductName').text(name);
        $('#selectedProductSku').text('SKU: ' + sku);
        $('#selectedProductImg').attr('src', selectedProductPhoto);

        $('#step1-search').hide();
        $('#step2-merchants').show();
        $('#merchantsList').html('<div class="text-center py-3"><i class="fas fa-spinner fa-spin fa-2x"></i></div>');

        // Load merchants for this product
        $.get('{{ route("admin-ps-best-sellers-merchants") }}', { product_id: selectedProductId }, function(merchants) {
            if (merchants.length === 0) {
                $('#merchantsList').html('<p class="text-muted">{{ __("No vendors found for this product") }}</p>');
                return;
            }

            let html = '';
            merchants.forEach(function(mp) {
                const isAdded = mp.best;

                // Brand badge
                const brandBadge = mp.brand_name
                    ? `<span class="brand-badge brand">
                        ${mp.brand_logo ? `<img src="${mp.brand_logo}" alt="" onerror="this.style.display='none'">` : ''}
                        ${mp.brand_name}
                       </span>`
                    : '';

                // Quality badge
                const qualityBadge = mp.quality_brand
                    ? `<span class="brand-badge quality">
                        ${mp.quality_brand_logo ? `<img src="${mp.quality_brand_logo}" alt="" onerror="this.style.display='none'">` : ''}
                        ${mp.quality_brand}
                       </span>`
                    : '';

                // Vendor badge
                const vendorBadge = `<span class="brand-badge vendor">
                    <i class="fas fa-store"></i>
                    ${mp.vendor_name}
                   </span>`;

                // Price
                const priceHtml = mp.previous_price && mp.previous_price > mp.price
                    ? `<strong>${parseFloat(mp.price).toFixed(2)}</strong>
                       <small class="text-muted text-decoration-line-through ms-1">${parseFloat(mp.previous_price).toFixed(2)}</small>`
                    : `<strong>${parseFloat(mp.price).toFixed(2)}</strong>`;

                html += `
                    <div class="merchant-card ${isAdded ? 'added' : ''}" data-id="${mp.id}">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div class="d-flex flex-wrap gap-2 mb-2">
                                    ${brandBadge}
                                    ${qualityBadge}
                                    ${vendorBadge}
                                </div>
                                <small class="text-muted">{{ __("Stock") }}: ${mp.stock || 0}</small>
                            </div>
                            <div class="text-end">
                                <div class="mb-2">${priceHtml}</div>
                                ${isAdded
                                    ? '<span class="badge bg-success">{{ __("Already Added") }}</span>'
                                    : `<button class="btn btn-primary btn-sm add-to-best" data-id="${mp.id}">
                                        <i class="fas fa-plus"></i> {{ __("Add") }}
                                       </button>`
                                }
                            </div>
                        </div>
                    </div>
                `;
            });
            $('#merchantsList').html(html);
        });
    });

    // Back to search
    $('#backToSearch').on('click', function() {
        $('#step2-merchants').hide();
        $('#step1-search').show();
        selectedProductId = null;
    });

    // Reset modal on close
    $('#addBestModal').on('hidden.bs.modal', function() {
        $('#step2-merchants').hide();
        $('#step1-search').show();
        $('#bestProductSearch').val('');
        $('#searchResults').html('<p class="text-muted">{{ __("Start typing to search for products...") }}</p>');
        selectedProductId = null;
    });

    // Add product to best month (silent)
    $(document).on('click', '.add-to-best', function() {
        const btn = $(this);
        const card = btn.closest('.merchant-card');
        const merchantProductId = btn.data('id');

        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');

        $.ajax({
            url: '{{ route("admin-ps-best-sellers-toggle") }}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                merchant_product_id: merchantProductId,
                best: 1
            },
            success: function(response) {
                if (response.success) {
                    btn.replaceWith('<span class="badge bg-success">{{ __("Already Added") }}</span>');
                    card.addClass('added');
                }
            },
            error: function() {
                btn.prop('disabled', false).html('<i class="fas fa-plus"></i> {{ __("Add") }}');
            }
        });
    });

    // Remove product from best month (silent)
    $(document).on('click', '.remove-best', function() {
        const btn = $(this);
        const productId = btn.data('id');
        const row = $('#best-row-' + productId);

        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');

        $.ajax({
            url: '{{ route("admin-ps-best-sellers-toggle") }}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                merchant_product_id: productId,
                best: 0
            },
            success: function(response) {
                if (response.success) {
                    row.fadeOut(300, function() {
                        $(this).remove();
                        if ($('#bestProductsTable tbody tr').length === 0) {
                            $('#bestProductsTable').html(`
                                <div class="alert alert-info">
                                    {{ __('No best sellers configured. Click "Add Product" to add products.') }}
                                </div>
                            `);
                        }
                    });
                }
            },
            error: function() {
                btn.prop('disabled', false).html('<i class="fas fa-trash-alt"></i>');
            }
        });
    });
});
</script>
@endsection
