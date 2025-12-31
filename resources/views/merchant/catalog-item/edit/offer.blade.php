@extends('layouts.merchant')
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
                <li class="breadcrumb-item"><a href="{{ route('merchant.dashboard') }}">@lang('Dashboard')</a></li>
                <li class="breadcrumb-item"><a href="{{ route('merchant-catalog-item-index') }}">@lang('Products')</a></li>
                <li class="breadcrumb-item active" aria-current="page">@lang('Edit Offer')</li>
            </ol>
        </nav>
    </div>
    <!-- breadcrumb end -->

    <form id="muaadhform" action="{{ route('merchant-catalog-item-update-offer', $merchantItem->id) }}" method="POST" enctype="multipart/form-data">
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
                            <p class="text-muted small mt-1">@lang('Product Main Image')</p>
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

                <!-- Vendor Gallery Section -->
                <div class="card mt-3">
                    <div class="card-header">
                        <h5>@lang('Your Product Images')</h5>
                        <small class="text-muted">@lang('Add your own images for this product')</small>
                    </div>
                    <div class="card-body">
                        <!-- Existing Vendor Images -->
                        <div id="vendor-gallery-list" class="row g-2 mb-3">
                            @php
                                $vendorGalleries = \App\Models\Gallery::where('product_id', $data->id)
                                    ->where('user_id', auth()->id())
                                    ->get();
                            @endphp
                            @foreach($vendorGalleries as $gallery)
                                <div class="col-4" id="gallery-item-{{ $gallery->id }}">
                                    <div class="position-relative">
                                        <img src="{{ asset('assets/images/galleries/' . $gallery->photo) }}"
                                             class="img-fluid rounded" style="height: 100px; width: 100%; object-fit: cover;">
                                        <button type="button" class="btn btn-danger btn-sm position-absolute top-0 end-0 m-1 remove-gallery-btn"
                                                data-id="{{ $gallery->id }}" title="@lang('Remove')">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <!-- Upload New Images -->
                        <div class="mb-3">
                            <label class="form-label">@lang('Add Images')</label>
                            <input type="file" class="form-control" id="gallery-upload" name="gallery[]"
                                   multiple accept="image/jpeg,image/png,image/jpg,image/webp">
                            <small class="text-muted">@lang('Max 3 images. Allowed: JPG, PNG, WEBP')</small>
                        </div>

                        <!-- Preview New Uploads -->
                        <div id="gallery-preview" class="row g-2"></div>

                        <input type="hidden" id="product_id" value="{{ $data->id }}">
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
                                           value="{{ $merchantItem->price * $sign->value }}" required>
                                </div>
                            </div>

                            <!-- Previous Price -->
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">@lang('Previous Price') ({{ $sign->name }})</label>
                                    <input type="number" step="0.01" class="form-control" name="previous_price"
                                           value="{{ $merchantItem->previous_price ? ($merchantItem->previous_price * $sign->value) : '' }}">
                                </div>
                            </div>

                            <!-- Stock -->
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">@lang('Stock Quantity*')</label>
                                    <input type="number" class="form-control" name="stock"
                                           value="{{ $merchantItem->stock }}" required>
                                </div>
                            </div>

                            <!-- Minimum Quantity -->
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">@lang('Minimum Quantity')</label>
                                    <input type="number" class="form-control" name="minimum_qty"
                                           value="{{ $merchantItem->minimum_qty }}">
                                </div>
                            </div>

                            <!-- Product Condition -->
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">@lang('Product Condition*')</label>
                                    <select class="form-control" name="product_condition" required>
                                        <option value="2" {{ $merchantItem->product_condition == 2 ? 'selected' : '' }}>@lang('New')</option>
                                        <option value="1" {{ $merchantItem->product_condition == 1 ? 'selected' : '' }}>@lang('Used')</option>
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
                                            <option value="{{ $qb->id }}" {{ $merchantItem->brand_quality_id == $qb->id ? 'selected' : '' }}>
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
                                           value="{{ $merchantItem->ship }}">
                                </div>
                            </div>

                            <!-- Colors -->
                            <div class="col-12">
                                <div class="mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="allow_colors" name="color_check"
                                               value="1" {{ !empty($merchantItem->color_all) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="allow_colors">
                                            @lang('Allow Product Colors')
                                        </label>
                                    </div>
                                </div>

                                <div id="color_section" style="display: {{ !empty($merchantItem->color_all) ? 'block' : 'none' }};">
                                    <div class="mb-3">
                                        <label class="form-label">@lang('Available Colors')</label>
                                        <div id="color_inputs">
                                            @if(!empty($merchantItem->color_all))
                                                @php
                                                    $colors = is_array($merchantItem->color_all) ? $merchantItem->color_all : explode(',', $merchantItem->color_all);
                                                    $colorPrices = !empty($merchantItem->color_price)
                                                        ? (is_array($merchantItem->color_price) ? $merchantItem->color_price : explode(',', $merchantItem->color_price))
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
                                               value="1" {{ !empty($merchantItem->whole_sell_qty) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="allow_wholesale">
                                            @lang('Allow Wholesale')
                                        </label>
                                    </div>
                                </div>

                                <div id="wholesale_section" style="display: {{ !empty($merchantItem->whole_sell_qty) ? 'block' : 'none' }};">
                                    <div class="row">
                                        <div class="col-6">
                                            <div class="mb-3">
                                                <label class="form-label">@lang('Wholesale Quantity')</label>
                                                <input type="number" class="form-control" name="whole_sell_qty[]"
                                                       value="{{ !empty($merchantItem->whole_sell_qty) ? (is_array($merchantItem->whole_sell_qty) ? implode(',', $merchantItem->whole_sell_qty) : $merchantItem->whole_sell_qty) : '' }}">
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="mb-3">
                                                <label class="form-label">@lang('Wholesale Discount (%)')</label>
                                                <input type="number" step="0.01" class="form-control" name="whole_sell_discount[]"
                                                       value="{{ !empty($merchantItem->whole_sell_discount) ? (is_array($merchantItem->whole_sell_discount) ? implode(',', $merchantItem->whole_sell_discount) : $merchantItem->whole_sell_discount) : '' }}">
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
                                               value="1" {{ $merchantItem->preordered ? 'checked' : '' }}>
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
                                    <textarea class="form-control" name="policy" rows="3">{{ $merchantItem->policy }}</textarea>
                                </div>
                            </div>

                            <!-- Features Override -->
                            <div class="col-12">
                                <div class="mb-3">
                                    <label class="form-label">@lang('Features (Override Product Features)')</label>
                                    <textarea class="form-control" name="features" rows="3">{{ $merchantItem->features }}</textarea>
                                </div>
                            </div>

                            <!-- Details -->
                            <div class="col-12">
                                <div class="mb-3">
                                    <label class="form-label">@lang('Additional Details')</label>
                                    <textarea class="form-control" name="details" rows="4">{{ $merchantItem->details }}</textarea>
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

    // ============================================
    // Vendor Gallery Upload
    // ============================================
    const galleryUpload = document.getElementById('gallery-upload');
    const galleryPreview = document.getElementById('gallery-preview');
    const productId = document.getElementById('product_id').value;

    // Preview selected images before upload
    galleryUpload.addEventListener('change', function() {
        galleryPreview.innerHTML = '';
        const files = this.files;

        if (files.length > 3) {
            alert('@lang("Maximum 3 images allowed")');
            this.value = '';
            return;
        }

        for (let i = 0; i < files.length; i++) {
            const file = files[i];
            const reader = new FileReader();

            reader.onload = function(e) {
                const col = document.createElement('div');
                col.className = 'col-4';
                col.innerHTML = `
                    <div class="position-relative">
                        <img src="${e.target.result}" class="img-fluid rounded" style="height: 100px; width: 100%; object-fit: cover;">
                        <span class="badge bg-warning position-absolute top-0 start-0 m-1">@lang('New')</span>
                    </div>
                `;
                galleryPreview.appendChild(col);
            };

            reader.readAsDataURL(file);
        }
    });

    // Upload gallery images via AJAX
    const mainForm = document.getElementById('muaadhform');
    mainForm.addEventListener('submit', function(e) {
        const galleryFiles = galleryUpload.files;

        if (galleryFiles.length > 0) {
            e.preventDefault();

            const formData = new FormData();
            formData.append('product_id', productId);
            formData.append('_token', '{{ csrf_token() }}');

            for (let i = 0; i < galleryFiles.length; i++) {
                formData.append('gallery[]', galleryFiles[i]);
            }

            // Upload gallery first
            fetch('{{ route("vendor-gallery-store") }}', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                console.log('Gallery uploaded:', data);
                // Clear file input and submit main form
                galleryUpload.value = '';
                mainForm.submit();
            })
            .catch(error => {
                console.error('Gallery upload error:', error);
                // Submit form anyway
                mainForm.submit();
            });
        }
    });

    // Remove gallery image
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('remove-gallery-btn') || e.target.closest('.remove-gallery-btn')) {
            const btn = e.target.classList.contains('remove-gallery-btn') ? e.target : e.target.closest('.remove-gallery-btn');
            const galleryId = btn.dataset.id;

            if (confirm('@lang("Are you sure you want to delete this image?")')) {
                fetch('{{ route("vendor-gallery-delete") }}?id=' + galleryId, {
                    method: 'GET',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    document.getElementById('gallery-item-' + galleryId).remove();
                })
                .catch(error => {
                    console.error('Delete error:', error);
                });
            }
        }
    });
});
</script>
@endsection