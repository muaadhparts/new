@extends('layouts.admin')

@section('content')

<div class="content-area">
    <div class="mr-breadcrumb">
        <div class="row">
            <div class="col-lg-12">
                <h4 class="heading">{{ __('Featured Products') }}</h4>
                <ul class="links">
                    <li><a href="{{ route('admin.dashboard') }}">{{ __('Dashboard') }}</a></li>
                    <li><a href="javascript:;">{{ __('Home Page Settings') }}</a></li>
                    <li><a href="{{ route('admin-ps-featured') }}">{{ __('Featured Products') }}</a></li>
                </ul>
            </div>
        </div>
    </div>

    <div class="add-product-content1 add-product-content2 mb-4">
        <div class="row">
            <div class="col-lg-12">
                <div class="product-description">
                    <div class="body-area">
                        <div class="row mb-4">
                            <div class="col-md-8">
                                <h5 class="mb-3">{{ __('Current Featured Products') }}</h5>
                            </div>
                            <div class="col-md-4">
                                <button type="button" class="btn btn-success float-end" data-bs-toggle="modal" data-bs-target="#addModal">
                                    <i class="fas fa-plus"></i> {{ __('Add Product') }}
                                </button>
                            </div>
                        </div>

                        <div class="table-responsive" id="productsTable">
                            @if($products->count() > 0)
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
                                    @foreach($products as $mp)
                                    @php
                                        $productName = app()->getLocale() == 'ar'
                                            ? ($mp->product->label_ar ?: $mp->product->label_en ?: $mp->product->name)
                                            : ($mp->product->label_en ?: $mp->product->name);
                                        $photo = $mp->product->photo
                                            ? (filter_var($mp->product->photo, FILTER_VALIDATE_URL) ? $mp->product->photo : Storage::url($mp->product->photo))
                                            : asset('assets/images/noimage.png');
                                        $brandName = $mp->product->brand?->localized_name;
                                        $brandLogo = $mp->product->brand?->photo_url;
                                        $qualityName = $mp->qualityBrand?->localized_name;
                                        $qualityLogo = $mp->qualityBrand?->logo_url;
                                        $vendorName = app()->getLocale() == 'ar' ? ($mp->user->shop_name_ar ?: $mp->user->shop_name) : $mp->user->shop_name;
                                    @endphp
                                    <tr id="row-{{ $mp->id }}">
                                        <td><img src="{{ $photo }}" alt="" style="width: 60px; height: 60px; object-fit: contain; border-radius: 8px; border: 1px solid #eee;"></td>
                                        <td><strong>{{ $productName }}</strong><br><small class="text-muted">SKU: {{ $mp->product->sku }}</small></td>
                                        <td>
                                            <div class="d-flex flex-wrap gap-2">
                                                @if($brandName)<span class="badge bg-primary d-inline-flex align-items-center gap-1">@if($brandLogo)<img src="{{ $brandLogo }}" alt="" style="width: 16px; height: 16px; object-fit: contain;">@endif {{ $brandName }}</span>@endif
                                                @if($qualityName)<span class="badge bg-info d-inline-flex align-items-center gap-1">@if($qualityLogo)<img src="{{ $qualityLogo }}" alt="" style="width: 16px; height: 16px; object-fit: contain;">@endif {{ $qualityName }}</span>@endif
                                                <span class="badge bg-secondary d-inline-flex align-items-center gap-1"><i class="fas fa-store"></i> {{ $vendorName }}</span>
                                            </div>
                                        </td>
                                        <td><strong>{{ \App\Models\Product::convertPrice($mp->price) }}</strong>@if($mp->previous_price && $mp->previous_price > $mp->price)<br><small class="text-muted text-decoration-line-through">{{ \App\Models\Product::convertPrice($mp->previous_price) }}</small>@endif</td>
                                        <td><span class="badge {{ $mp->stock > 0 ? 'bg-success' : 'bg-danger' }}">{{ $mp->stock ?? 0 }}</span></td>
                                        <td><button type="button" class="btn btn-danger btn-sm remove-item" data-id="{{ $mp->id }}"><i class="fas fa-trash-alt"></i></button></td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                            @else
                            <div class="alert alert-info">{{ __('No products configured. Click "Add Product" to add products.') }}</div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="addModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('Add Product to Featured') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="step1-search">
                    <div class="mb-3">
                        <label class="form-label">{{ __('Search by SKU or Product Name') }}</label>
                        <input type="text" id="productSearch" class="form-control" placeholder="{{ __('Enter SKU or product name...') }}">
                    </div>
                    <div id="searchResults" class="mt-3"><p class="text-muted">{{ __('Start typing to search...') }}</p></div>
                </div>
                <div id="step2-merchants" style="display: none;">
                    <div class="d-flex align-items-center mb-3">
                        <button type="button" class="btn btn-outline-secondary btn-sm me-3" id="backToSearch"><i class="fas fa-arrow-left"></i> {{ __('Back') }}</button>
                        <img id="selectedProductImg" src="" alt="" style="width: 50px; height: 50px; object-fit: contain; border-radius: 8px; margin-right: 10px;">
                        <div><strong id="selectedProductName"></strong><small class="text-muted d-block" id="selectedProductSku"></small></div>
                    </div>
                    <h6>{{ __('Select Vendor & Quality Brand') }}:</h6>
                    <div id="merchantsList" class="mt-3"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.merchant-card { border: 1px solid #e0e0e0; border-radius: 8px; padding: 12px; margin-bottom: 10px; transition: all 0.2s; }
.merchant-card:hover { border-color: #007bff; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
.merchant-card.added { background-color: #d4edda; border-color: #28a745; }
.brand-badge { display: inline-flex; align-items: center; gap: 4px; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: 500; }
.brand-badge img { width: 18px; height: 18px; object-fit: contain; }
.brand-badge.brand { background: #e3f2fd; color: #1565c0; }
.brand-badge.quality { background: #e8f5e9; color: #2e7d32; }
.brand-badge.vendor { background: #fce4ec; color: #c2185b; }
</style>
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    let searchTimeout, selectedProductId = null;

    $('#productSearch').on('keyup', function() {
        clearTimeout(searchTimeout);
        const query = $(this).val();
        if (query.length < 2) { $('#searchResults').html('<p class="text-muted">{{ __("Start typing to search...") }}</p>'); return; }
        searchTimeout = setTimeout(function() {
            $('#searchResults').html('<div class="text-center py-3"><i class="fas fa-spinner fa-spin fa-2x"></i></div>');
            $.get('{{ route("admin-ps-featured-search") }}', { q: query }, function(data) {
                if (data.length === 0) { $('#searchResults').html('<p class="text-muted">{{ __("No products found") }}</p>'); return; }
                let html = '<div class="list-group">';
                data.forEach(function(p) {
                    html += `<a href="javascript:;" class="list-group-item list-group-item-action d-flex align-items-center select-product" data-id="${p.product_id}" data-name="${p.name}" data-sku="${p.sku}" data-photo="${p.photo}">
                        <img src="${p.photo}" alt="" style="width: 50px; height: 50px; object-fit: contain; border-radius: 8px;" class="me-3" onerror="this.src='{{ asset('assets/images/noimage.png') }}'">
                        <div class="flex-grow-1"><strong>${p.name}</strong><br><small class="text-muted">SKU: ${p.sku}</small></div>
                        <div class="text-end"><span class="badge bg-info">${p.merchants_count} {{ __("vendors") }}</span><i class="fas fa-chevron-right ms-2"></i></div></a>`;
                });
                $('#searchResults').html(html + '</div>');
            });
        }, 300);
    });

    $(document).on('click', '.select-product', function() {
        selectedProductId = $(this).data('id');
        $('#selectedProductName').text($(this).data('name'));
        $('#selectedProductSku').text('SKU: ' + $(this).data('sku'));
        $('#selectedProductImg').attr('src', $(this).data('photo'));
        $('#step1-search').hide(); $('#step2-merchants').show();
        $('#merchantsList').html('<div class="text-center py-3"><i class="fas fa-spinner fa-spin fa-2x"></i></div>');
        $.get('{{ route("admin-ps-featured-merchants") }}', { product_id: selectedProductId }, function(merchants) {
            if (merchants.length === 0) { $('#merchantsList').html('<p class="text-muted">{{ __("No vendors found") }}</p>'); return; }
            let html = '';
            merchants.forEach(function(mp) {
                const brandBadge = mp.brand_name ? `<span class="brand-badge brand">${mp.brand_logo ? `<img src="${mp.brand_logo}" onerror="this.style.display='none'">` : ''}${mp.brand_name}</span>` : '';
                const qualityBadge = mp.quality_brand ? `<span class="brand-badge quality">${mp.quality_brand_logo ? `<img src="${mp.quality_brand_logo}" onerror="this.style.display='none'">` : ''}${mp.quality_brand}</span>` : '';
                const vendorBadge = `<span class="brand-badge vendor"><i class="fas fa-store"></i> ${mp.vendor_name}</span>`;
                html += `<div class="merchant-card ${mp.is_flagged ? 'added' : ''}" data-id="${mp.id}">
                    <div class="d-flex justify-content-between align-items-start">
                        <div><div class="d-flex flex-wrap gap-2 mb-2">${brandBadge}${qualityBadge}${vendorBadge}</div><small class="text-muted">{{ __("Stock") }}: ${mp.stock || 0}</small></div>
                        <div class="text-end"><div class="mb-2"><strong>${parseFloat(mp.price).toFixed(2)}</strong></div>
                        ${mp.is_flagged ? '<span class="badge bg-success">{{ __("Already Added") }}</span>' : `<button class="btn btn-primary btn-sm add-item" data-id="${mp.id}"><i class="fas fa-plus"></i> {{ __("Add") }}</button>`}</div>
                    </div></div>`;
            });
            $('#merchantsList').html(html);
        });
    });

    $('#backToSearch').on('click', function() { $('#step2-merchants').hide(); $('#step1-search').show(); });
    $('#addModal').on('hidden.bs.modal', function() { $('#step2-merchants').hide(); $('#step1-search').show(); $('#productSearch').val(''); $('#searchResults').html('<p class="text-muted">{{ __("Start typing to search...") }}</p>'); });

    $(document).on('click', '.add-item', function() {
        const btn = $(this), card = btn.closest('.merchant-card');
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');
        $.ajax({ url: '{{ route("admin-ps-featured-toggle") }}', type: 'POST', data: { _token: '{{ csrf_token() }}', merchant_product_id: btn.data('id'), flag: 1 },
            success: function(r) { if (r.success) { btn.replaceWith('<span class="badge bg-success">{{ __("Already Added") }}</span>'); card.addClass('added'); } },
            error: function() { btn.prop('disabled', false).html('<i class="fas fa-plus"></i> {{ __("Add") }}'); }
        });
    });

    $(document).on('click', '.remove-item', function() {
        const btn = $(this), row = $('#row-' + btn.data('id'));
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');
        $.ajax({ url: '{{ route("admin-ps-featured-toggle") }}', type: 'POST', data: { _token: '{{ csrf_token() }}', merchant_product_id: btn.data('id'), flag: 0 },
            success: function(r) { if (r.success) { row.fadeOut(300, function() { $(this).remove(); if ($('#productsTable tbody tr').length === 0) { $('#productsTable').html('<div class="alert alert-info">{{ __("No products configured.") }}</div>'); } }); } },
            error: function() { btn.prop('disabled', false).html('<i class="fas fa-trash-alt"></i>'); }
        });
    });
});
</script>
@endsection
