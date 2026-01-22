@extends('layouts.operator')

@section('content')
    <div class="content-area">
        <div class="mr-breadcrumb">
            <div class="row">
                <div class="col-lg-12">
                    <h4 class="heading">{{ __('Merchant Item Images') }}
                        <a class="add-btn" href="{{ route('operator-catalog-item-index') }}">
                            <i class="fas fa-arrow-left"></i> {{ __('Back') }}
                        </a>
                    </h4>
                    <ul class="links">
                        <li>
                            <a href="{{ route('operator.dashboard') }}">{{ __('Dashboard') }}</a>
                        </li>
                        <li>
                            <a href="{{ route('operator-catalog-item-index') }}">{{ __('Catalog Items') }}</a>
                        </li>
                        <li>
                            <a href="javascript:;">{{ __('Merchant Item Images') }}</a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        @include('alerts.operator.form-both')

        <div class="row">
            <div class="col-lg-12">
                <div class="add-catalogItem-content">
                    <div class="catalogItem-description">
                        <div class="body-area">
                            {{-- Cascading Search Form --}}
                            <div class="row">
                                <div class="col-lg-12">
                                    <div class="left-area">
                                        <h4 class="heading">{{ __('Search Merchant Item') }}</h4>
                                        <p class="sub-heading">{{ __('Select step by step to find the merchant item') }}</p>
                                    </div>
                                </div>
                            </div>

                            <div class="row mt-3">
                                {{-- Step 1: Part Number with Autocomplete --}}
                                <div class="col-lg-3">
                                    <label>{{ __('Part Number') }}</label>
                                    <select class="form-control" id="partNumberSelect" style="width: 100%;">
                                        <option value="">{{ __('Type to search...') }}</option>
                                    </select>
                                </div>

                                {{-- Step 2: Merchant --}}
                                <div class="col-lg-3">
                                    <label>{{ __('Merchant') }}</label>
                                    <select class="form-control" id="merchantSelect" disabled>
                                        <option value="">{{ __('-- Select Merchant --') }}</option>
                                    </select>
                                </div>

                                {{-- Step 3: Branch --}}
                                <div class="col-lg-3">
                                    <label>{{ __('Branch') }}</label>
                                    <select class="form-control" id="branchSelect" disabled>
                                        <option value="">{{ __('-- Select Branch --') }}</option>
                                    </select>
                                </div>

                                {{-- Step 4: Quality Brand --}}
                                <div class="col-lg-3">
                                    <label>{{ __('Quality Brand') }}</label>
                                    <select class="form-control" id="qualityBrandSelect" disabled>
                                        <option value="">{{ __('-- Select Quality Brand --') }}</option>
                                    </select>
                                </div>
                            </div>

                            {{-- Loading indicator --}}
                            <div class="row mt-4" id="loadingIndicator" style="display: none;">
                                <div class="col-lg-12 text-center">
                                    <i class="fas fa-spinner fa-spin fa-2x"></i>
                                    <p>{{ __('Loading...') }}</p>
                                </div>
                            </div>

                            {{-- Not Found Message --}}
                            <div class="row mt-4" id="notFoundMessage" style="display: none;">
                                <div class="col-lg-12">
                                    <div class="alert alert-warning">
                                        <i class="fas fa-exclamation-triangle"></i>
                                        <span id="notFoundText">{{ __('No results found.') }}</span>
                                    </div>
                                </div>
                            </div>

                            {{-- Catalog Item Info --}}
                            <div class="row mt-4" id="catalogItemInfo" style="display: none;">
                                <div class="col-lg-12">
                                    <div class="alert alert-info">
                                        <strong>{{ __('Catalog Item') }}:</strong> <span id="catalogItemName"></span>
                                    </div>
                                </div>
                            </div>

                            <hr class="my-4">

                            {{-- Photo Management Section --}}
                            <div id="photoSection" style="display: none;">
                                {{-- Merchant Item Info --}}
                                <div class="row">
                                    <div class="col-lg-12">
                                        <div class="alert alert-secondary">
                                            <h5 id="merchantItemInfo"></h5>
                                            <p class="mb-0" id="merchantItemDetails"></p>
                                        </div>
                                    </div>
                                </div>

                                {{-- Upload Section --}}
                                <div class="row mt-4">
                                    <div class="col-lg-12">
                                        <div class="left-area">
                                            <h4 class="heading">{{ __('Upload New Photos') }}</h4>
                                        </div>
                                    </div>
                                    <div class="col-lg-8">
                                        <input type="file" class="form-control" id="photoUpload" multiple
                                            accept="image/jpeg,image/png,image/jpg,image/webp">
                                        <small class="text-muted">{{ __('You can select multiple images. Max 5MB each.') }}</small>
                                    </div>
                                    <div class="col-lg-4">
                                        <button class="btn btn-success btn-block" id="uploadBtn" disabled>
                                            <i class="fas fa-upload"></i> {{ __('Upload Photos') }}
                                        </button>
                                    </div>
                                </div>

                                {{-- Current Photos --}}
                                <div class="row mt-4">
                                    <div class="col-lg-12">
                                        <div class="left-area">
                                            <h4 class="heading">{{ __('Current Photos') }}</h4>
                                            <p class="sub-heading">{{ __('Drag to reorder. Click delete to remove.') }}</p>
                                        </div>
                                    </div>
                                    <div class="col-lg-12">
                                        <div id="photoGrid" class="row" style="min-height: 100px;">
                                            {{-- Photos will be loaded here --}}
                                        </div>
                                        <div id="noPhotosMessage" class="text-center text-muted py-4" style="display: none;">
                                            <i class="fas fa-images fa-3x mb-2"></i>
                                            <p>{{ __('No photos yet. Upload some photos above.') }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('styles')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        .select2-container--default .select2-selection--single {
            height: 38px;
            padding: 5px 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 36px;
        }
        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 26px;
        }
        .select2-results__option {
            padding: 8px 12px;
        }
        .select2-container--default .select2-results__option--highlighted[aria-selected] {
            background-color: #007bff;
        }
        .photo-card {
            position: relative;
            margin-bottom: 20px;
            cursor: move;
        }
        .photo-card img {
            width: 100%;
            height: 150px;
            object-fit: cover;
            border-radius: 4px;
        }
        .photo-card .photo-actions {
            position: absolute;
            top: 5px;
            right: 20px;
            display: flex;
            gap: 5px;
        }
        .photo-card .btn-delete {
            background: rgba(220, 53, 69, 0.9);
            color: white;
            border: none;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }
        .photo-card .btn-delete:hover {
            background: rgba(220, 53, 69, 1);
        }
        .photo-card .primary-badge {
            position: absolute;
            bottom: 5px;
            left: 20px;
            background: rgba(40, 167, 69, 0.9);
            color: white;
            padding: 2px 8px;
            border-radius: 3px;
            font-size: 11px;
        }
        .photo-card.sortable-ghost {
            opacity: 0.4;
        }
        .photo-card .photo-order {
            position: absolute;
            top: 5px;
            left: 20px;
            background: rgba(0, 0, 0, 0.6);
            color: white;
            padding: 2px 8px;
            border-radius: 3px;
            font-size: 11px;
        }
    </style>
@endsection

@section('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(document).ready(function() {
            const baseUrl = '{{ url("operator/merchant-items/images") }}';
            const autocompleteUrl = '{{ route("operator-merchant-item-images-autocomplete") }}';
            let currentPartNumber = '';
            let currentMerchantItemId = null;

            // Initialize Select2 for Part Number
            $('#partNumberSelect').select2({
                placeholder: '{{ __("Type to search part number...") }}',
                allowClear: true,
                minimumInputLength: 2,
                ajax: {
                    url: autocompleteUrl,
                    dataType: 'json',
                    delay: 300,
                    data: function(params) {
                        return {
                            q: params.term
                        };
                    },
                    processResults: function(data) {
                        return {
                            results: data.map(function(item) {
                                return {
                                    id: item.part_number,
                                    text: item.text,
                                    part_number: item.part_number,
                                    name: item.name
                                };
                            })
                        };
                    },
                    cache: true
                },
                language: {
                    inputTooShort: function() {
                        return '{{ __("Please enter 2 or more characters") }}';
                    },
                    noResults: function() {
                        return '{{ __("No results found") }}';
                    },
                    searching: function() {
                        return '{{ __("Searching...") }}';
                    }
                }
            });

            // On Part Number select
            $('#partNumberSelect').on('select2:select', function(e) {
                const data = e.params.data;
                currentPartNumber = data.part_number;
                loadMerchants(data.part_number, data.name);
            });

            // On Part Number clear
            $('#partNumberSelect').on('select2:clear', function() {
                resetSelects();
                $('#catalogItemInfo').hide();
            });

            function loadMerchants(partNumber, itemName) {
                resetSelects();
                showLoading();

                $.ajax({
                    url: baseUrl + '/merchants',
                    type: 'GET',
                    data: { part_number: partNumber },
                    success: function(response) {
                        hideLoading();

                        if (response.success && response.data.length > 0) {
                            $('#catalogItemInfo').show();
                            $('#catalogItemName').text(response.catalog_item_name || itemName);

                            const $select = $('#merchantSelect');
                            $select.empty().append('<option value="">{{ __("-- Select Merchant --") }}</option>');
                            response.data.forEach(function(merchant) {
                                $select.append('<option value="' + merchant.id + '">' + merchant.name + '</option>');
                            });
                            $select.prop('disabled', false);
                            hideNotFound();
                        } else {
                            showNotFound('{{ __("No merchants found with this part number.") }}');
                            $('#catalogItemInfo').hide();
                        }
                    },
                    error: function(xhr) {
                        hideLoading();
                        showNotFound('{{ __("CatalogItem not found. Please check the part number.") }}');
                        $('#catalogItemInfo').hide();
                    }
                });
            }

            // Step 2: Merchant selected
            $('#merchantSelect').change(function() {
                const merchantId = $(this).val();
                resetSelectsAfter('merchant');

                if (!merchantId) return;

                showLoading();

                $.ajax({
                    url: baseUrl + '/branches',
                    type: 'GET',
                    data: { part_number: currentPartNumber, merchant_id: merchantId },
                    success: function(response) {
                        hideLoading();

                        if (response.success && response.data.length > 0) {
                            const $select = $('#branchSelect');
                            $select.empty().append('<option value="">{{ __("-- Select Branch --") }}</option>');
                            response.data.forEach(function(branch) {
                                $select.append('<option value="' + branch.id + '">' + branch.name + '</option>');
                            });
                            $select.prop('disabled', false);
                            hideNotFound();
                        } else {
                            showNotFound('{{ __("No branches found for this merchant.") }}');
                        }
                    },
                    error: function() {
                        hideLoading();
                        showNotFound('{{ __("Error loading branches.") }}');
                    }
                });
            });

            // Step 3: Branch selected
            $('#branchSelect').change(function() {
                const branchId = $(this).val();
                const merchantId = $('#merchantSelect').val();
                resetSelectsAfter('branch');

                if (!branchId) return;

                showLoading();

                $.ajax({
                    url: baseUrl + '/quality-brands',
                    type: 'GET',
                    data: { part_number: currentPartNumber, merchant_id: merchantId, branch_id: branchId },
                    success: function(response) {
                        hideLoading();

                        if (response.success && response.data.length > 0) {
                            const $select = $('#qualityBrandSelect');
                            $select.empty().append('<option value="">{{ __("-- Select Quality Brand --") }}</option>');
                            response.data.forEach(function(qb) {
                                $select.append('<option value="' + qb.merchant_item_id + '">' + qb.name + '</option>');
                            });
                            $select.prop('disabled', false);
                            hideNotFound();
                        } else {
                            showNotFound('{{ __("No quality brands found.") }}');
                        }
                    },
                    error: function() {
                        hideLoading();
                        showNotFound('{{ __("Error loading quality brands.") }}');
                    }
                });
            });

            // Step 4: Quality Brand selected - Load Photos
            $('#qualityBrandSelect').change(function() {
                const merchantItemId = $(this).val();
                if (!merchantItemId) {
                    $('#photoSection').hide();
                    return;
                }

                currentMerchantItemId = merchantItemId;
                loadPhotos(merchantItemId);
            });

            function loadPhotos(merchantItemId) {
                showLoading();

                $.ajax({
                    url: baseUrl + '/photos/' + merchantItemId,
                    type: 'GET',
                    success: function(response) {
                        hideLoading();

                        if (response.success) {
                            // Show merchant item info
                            const mi = response.merchant_item;
                            $('#merchantItemInfo').text(mi.catalog_item_name);
                            $('#merchantItemDetails').html(
                                '<strong>{{ __("Merchant") }}:</strong> ' + mi.merchant_name +
                                ' | <strong>{{ __("Branch") }}:</strong> ' + mi.branch_name +
                                ' | <strong>{{ __("Quality Brand") }}:</strong> ' + mi.quality_brand_name
                            );

                            // Display photos
                            displayPhotos(response.data);

                            $('#photoSection').show();
                            $('#uploadBtn').prop('disabled', false);
                            hideNotFound();
                        }
                    },
                    error: function() {
                        hideLoading();
                        showNotFound('{{ __("Error loading photos.") }}');
                    }
                });
            }

            function displayPhotos(photos) {
                const $grid = $('#photoGrid');
                $grid.empty();

                if (photos.length === 0) {
                    $('#noPhotosMessage').show();
                    return;
                }

                $('#noPhotosMessage').hide();

                photos.forEach(function(photo, index) {
                    const primaryBadge = photo.is_primary ? '<span class="primary-badge">{{ __("Primary") }}</span>' : '';
                    const html = `
                        <div class="col-lg-3 col-md-4 col-sm-6 photo-card" data-id="${photo.id}">
                            <span class="photo-order">#${index + 1}</span>
                            <img src="${photo.photo_url}" alt="Photo">
                            <div class="photo-actions">
                                <button type="button" class="btn-delete" data-id="${photo.id}" title="{{ __('Delete') }}">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                            ${primaryBadge}
                        </div>
                    `;
                    $grid.append(html);
                });

                // Initialize sortable
                initSortable();
            }

            function initSortable() {
                const grid = document.getElementById('photoGrid');
                if (grid) {
                    new Sortable(grid, {
                        animation: 150,
                        ghostClass: 'sortable-ghost',
                        onEnd: function() {
                            updatePhotoOrder();
                        }
                    });
                }
            }

            function updatePhotoOrder() {
                const photos = [];
                $('#photoGrid .photo-card').each(function(index) {
                    const id = $(this).data('id');
                    photos.push({ id: id, sort_order: index + 1 });

                    // Update order badge
                    $(this).find('.photo-order').text('#' + (index + 1));
                });

                $.ajax({
                    url: baseUrl + '/order',
                    type: 'POST',
                    data: {
                        photos: photos,
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        if (!response.success) {
                            $.notify('{{ __("Failed to update order") }}', 'error');
                        }
                    },
                    error: function() {
                        $.notify('{{ __("Error updating order") }}', 'error');
                    }
                });
            }

            // Delete photo
            $(document).on('click', '.btn-delete', function() {
                const $btn = $(this);
                const id = $btn.data('id');

                if (!confirm('{{ __("Are you sure you want to delete this photo?") }}')) {
                    return;
                }

                $.ajax({
                    url: baseUrl + '/' + id,
                    type: 'DELETE',
                    data: { _token: '{{ csrf_token() }}' },
                    success: function(response) {
                        if (response.success) {
                            $btn.closest('.photo-card').fadeOut(300, function() {
                                $(this).remove();
                                updatePhotoOrder();

                                if ($('#photoGrid .photo-card').length === 0) {
                                    $('#noPhotosMessage').show();
                                }
                            });
                        } else {
                            $.notify(response.message || '{{ __("Failed to delete photo") }}', 'error');
                        }
                    },
                    error: function() {
                        $.notify('{{ __("Error deleting photo") }}', 'error');
                    }
                });
            });

            // Upload photos
            $('#photoUpload').change(function() {
                $('#uploadBtn').prop('disabled', !this.files.length);
            });

            $('#uploadBtn').click(function() {
                const files = $('#photoUpload')[0].files;
                if (!files.length) {
                    $.notify('{{ __("Please select photos to upload") }}', 'error');
                    return;
                }

                if (!currentMerchantItemId) {
                    $.notify('{{ __("Please select a merchant item first") }}', 'error');
                    return;
                }

                const formData = new FormData();
                formData.append('merchant_item_id', currentMerchantItemId);
                formData.append('_token', '{{ csrf_token() }}');

                for (let i = 0; i < files.length; i++) {
                    formData.append('photos[]', files[i]);
                }

                const $btn = $(this);
                $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> {{ __("Uploading...") }}');

                $.ajax({
                    url: baseUrl + '/store',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        $btn.prop('disabled', false).html('<i class="fas fa-upload"></i> {{ __("Upload Photos") }}');

                        if (response.success) {
                            $('#photoUpload').val('');
                            loadPhotos(currentMerchantItemId);
                        } else {
                            $.notify(response.message || '{{ __("Upload failed") }}', 'error');
                        }
                    },
                    error: function(xhr) {
                        $btn.prop('disabled', false).html('<i class="fas fa-upload"></i> {{ __("Upload Photos") }}');
                        let message = '{{ __("Upload failed") }}';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            message = xhr.responseJSON.message;
                        }
                        $.notify(message, 'error');
                    }
                });
            });

            // Helper functions
            function resetSelects() {
                $('#merchantSelect').empty().append('<option value="">{{ __("-- Select Merchant --") }}</option>').prop('disabled', true);
                $('#branchSelect').empty().append('<option value="">{{ __("-- Select Branch --") }}</option>').prop('disabled', true);
                $('#qualityBrandSelect').empty().append('<option value="">{{ __("-- Select Quality Brand --") }}</option>').prop('disabled', true);
                $('#photoSection').hide();
                $('#catalogItemInfo').hide();
                currentMerchantItemId = null;
            }

            function resetSelectsAfter(level) {
                if (level === 'merchant') {
                    $('#branchSelect').empty().append('<option value="">{{ __("-- Select Branch --") }}</option>').prop('disabled', true);
                    $('#qualityBrandSelect').empty().append('<option value="">{{ __("-- Select Quality Brand --") }}</option>').prop('disabled', true);
                    $('#photoSection').hide();
                    currentMerchantItemId = null;
                } else if (level === 'branch') {
                    $('#qualityBrandSelect').empty().append('<option value="">{{ __("-- Select Quality Brand --") }}</option>').prop('disabled', true);
                    $('#photoSection').hide();
                    currentMerchantItemId = null;
                }
            }

            function showLoading() {
                $('#loadingIndicator').show();
            }

            function hideLoading() {
                $('#loadingIndicator').hide();
            }

            function showNotFound(message) {
                $('#notFoundText').text(message);
                $('#notFoundMessage').show();
            }

            function hideNotFound() {
                $('#notFoundMessage').hide();
            }
        });
    </script>
@endsection
