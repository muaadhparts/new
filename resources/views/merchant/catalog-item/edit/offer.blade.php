@extends('layouts.merchant')
@php
    $isDashboard = true;
    $isMerchant = true;
@endphp

@section('content')
<div class="gs-merchant-outlet">
    <!-- breadcrumb start  -->
    <div class="gs-merchant-breadcrumb has-mb">
        <h4 class="text-capitalize">@lang('Edit Offer for CatalogItem')</h4>
        <nav style="--bs-breadcrumb-divider: '';" aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('merchant.dashboard') }}">@lang('Dashboard')</a></li>
                <li class="breadcrumb-item"><a href="{{ route('merchant-catalog-item-index') }}">@lang('CatalogItems')</a></li>
                <li class="breadcrumb-item active" aria-current="page">@lang('Edit Offer')</li>
            </ol>
        </nav>
    </div>
    <!-- breadcrumb end -->

    <form id="muaadhform" action="{{ route('merchant-catalog-item-update-offer', $merchantItem->id) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="row">
            <!-- CatalogItem Info Preview -->
            <div class="col-12 col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <h5>@lang('CatalogItem Information')</h5>
                    </div>
                    <div class="card-body">
                        <div class="text-center mb-3">
                            <img src="{{ filter_var($data->photo, FILTER_VALIDATE_URL) ? $data->photo : ($data->photo ? \Illuminate\Support\Facades\Storage::url($data->photo) : asset('assets/images/noimage.png')) }}"
                                 alt="{{ $data->name }}" class="img-fluid" style="max-height: 200px;">
                            <p class="text-muted small mt-1">@lang('CatalogItem Main Image')</p>
                        </div>
                        <h6>{{ $data->name }}</h6>
                        <p><strong>@lang('PART_NUMBER'):</strong> {{ $data->part_number }}</p>
                        <p><strong>@lang('Type'):</strong> {{ $data->type }}</p>
                        <p><strong>@lang('Weight'):</strong> {{ $data->weight }} kg</p>
                    </div>
                </div>

                <!-- Merchant Gallery Section -->
                <div class="card mt-3">
                    <div class="card-header">
                        <h5>@lang('Your CatalogItem Images')</h5>
                        <small class="text-muted">@lang('Add your own images for this catalogItem')</small>
                    </div>
                    <div class="card-body">
                        <!-- Existing Merchant Images -->
                        <div id="merchant-gallery-list" class="row g-2 mb-3">
                            @php
                                $merchantGalleries = \App\Models\MerchantPhoto::where('catalog_item_id', $data->id)
                                    ->where('user_id', auth()->id())
                                    ->get();
                            @endphp
                            @foreach($merchantGalleries as $gallery)
                                <div class="col-4" id="gallery-item-{{ $gallery->id }}">
                                    <div class="position-relative">
                                        <img src="{{ asset('assets/images/merchant-photos/' . $gallery->photo) }}"
                                             class="img-fluid rounded" style="height: 100px; width: 100%; object-fit: cover;">
                                        <button type="button" class="btn btn-danger btn-sm position-absolute top-0 end-0 m-1 remove-gallery-btn"
                                                data-id="{{ $gallery->id }}" name="@lang('Remove')">
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

                        <input type="hidden" id="catalog_item_id" value="{{ $data->id }}">
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
                            <!-- Branch Selection -->
                            <div class="col-md-12">
                                <div class="mb-3">
                                    <label class="form-label">@lang('Branch / Warehouse*')</label>
                                    @if(isset($branches) && $branches->count() > 0)
                                        <select class="form-control" name="merchant_branch_id" required>
                                            <option value="">@lang('Select Branch')</option>
                                            @foreach($branches as $branch)
                                                <option value="{{ $branch->id }}" {{ $merchantItem->merchant_branch_id == $branch->id ? 'selected' : '' }}>
                                                    {{ $branch->warehouse_name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <small class="text-muted">@lang('Branch where this item will be shipped from')</small>
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

                            <!-- CatalogItem Condition -->
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">@lang('CatalogItem Condition*')</label>
                                    <select class="form-control" name="item_condition" required>
                                        <option value="2" {{ $merchantItem->item_condition == 2 ? 'selected' : '' }}>@lang('New')</option>
                                        <option value="1" {{ $merchantItem->item_condition == 1 ? 'selected' : '' }}>@lang('Used')</option>
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
                                    <label class="form-label">@lang('Policy (Override CatalogItem Policy)')</label>
                                    <textarea class="form-control" name="policy" rows="3">{{ $merchantItem->policy }}</textarea>
                                </div>
                            </div>

                            <!-- Features Override -->
                            <div class="col-12">
                                <div class="mb-3">
                                    <label class="form-label">@lang('Features (Override CatalogItem Features)')</label>
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
    // Wholesale section toggle
    document.getElementById('allow_wholesale').addEventListener('change', function() {
        document.getElementById('wholesale_section').style.display = this.checked ? 'block' : 'none';
    });

    // ============================================
    // Merchant Gallery Upload
    // ============================================
    const galleryUpload = document.getElementById('gallery-upload');
    const galleryPreview = document.getElementById('gallery-preview');
    const catalogItemId = document.getElementById('catalog_item_id').value;

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
            formData.append('catalog_item_id', catalogItemId);
            formData.append('_token', '{{ csrf_token() }}');

            for (let i = 0; i < galleryFiles.length; i++) {
                formData.append('gallery[]', galleryFiles[i]);
            }

            // Upload gallery first
            fetch('{{ route("merchant-gallery-store") }}', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
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
                fetch('{{ route("merchant-gallery-delete") }}?id=' + galleryId, {
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