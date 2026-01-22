@extends('layouts.merchant')

@section('content')
    <div class="gs-merchant-outlet">
        <!-- breadcrumb start -->
        <div class="gs-merchant-breadcrumb has-mb">
            <div class="d-flex gap-4 flex-wrap align-items-center custom-gap-sm-2">
                <h4 class="text-capitalize">@lang('My Item Images')</h4>
            </div>
            <ul class="breadcrumb-menu">
                <li>
                    <a href="{{ route('merchant.dashboard') }}" class="text-capitalize">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                            fill="none" stroke="currentColor" class="home-icon-merchant-panel-breadcrumb">
                            <path
                                d="M9 21V13.6C9 13.0399 9 12.7599 9.109 12.546C9.20487 12.3578 9.35785 12.2049 9.54601 12.109C9.75993 12 10.04 12 10.6 12H13.4C13.9601 12 14.2401 12 14.454 12.109C14.6422 12.2049 14.7951 12.3578 14.891 12.546C15 12.7599 15 13.0399 15 13.6V21M2 9.5L11.04 2.72C11.3843 2.46181 11.5564 2.33271 11.7454 2.28294C11.9123 2.23902 12.0877 2.23902 12.2546 2.28295C12.4436 2.33271 12.6157 2.46181 12.96 2.72L22 9.5M4 8V17.8C4 18.9201 4 19.4802 4.21799 19.908C4.40974 20.2843 4.7157 20.5903 5.09202 20.782C5.51985 21 6.0799 21 7.2 21H16.8C17.9201 21 18.4802 21 18.908 20.782C19.2843 20.5903 19.5903 20.2843 19.782 19.908C20 19.4802 20 18.9201 20 17.8V8L13.92 3.44C13.2315 2.92361 12.8872 2.66542 12.5091 2.56589C12.1754 2.47804 11.8246 2.47804 11.4909 2.56589C11.1128 2.66542 10.7685 2.92361 10.08 3.44L4 8Z"
                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </a>
                </li>
                <li>
                    <a href="{{ route('merchant.dashboard') }}" class="text-capitalize">@lang('Dashboard')</a>
                </li>
                <li>
                    <a href="{{ route('merchant-catalog-item-index') }}" class="text-capitalize">@lang('CatalogItems')</a>
                </li>
                <li>
                    <a href="#" class="text-capitalize">@lang('My Item Images')</a>
                </li>
            </ul>
        </div>
        <!-- breadcrumb end -->

        <!-- Search Box -->
        <div class="merchant-table-wrapper mb-3">
            <div class="row align-items-end">
                <div class="col-md-6">
                    <label class="form-label">@lang('Search by Part Number')</label>
                    <div class="input-group">
                        <input type="text" class="form-control" id="partNumberSearch"
                            placeholder="@lang('Type to search part number...')">
                        <button class="btn btn-outline-secondary" type="button" id="clearSearchBtn">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Table area start -->
        <div class="merchant-table-wrapper">
            <div class="user-table table-responsive position-relative">
                <table class="gs-data-table w-100" id="itemsTable">
                    <thead>
                        <tr>
                            <th>{{ __('Part Number') }}</th>
                            <th>{{ __('Name') }}</th>
                            <th>{{ __('Branch') }}</th>
                            <th>{{ __('Quality Brand') }}</th>
                            <th class="text-center">{{ __('Photos') }}</th>
                            <th class="text-center">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        {{-- DataTables will populate this --}}
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Photo Management Modal -->
    <div class="modal fade" id="photoModal" tabindex="-1" aria-labelledby="photoModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="photoModalLabel">@lang('Manage Photos')</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Item Info -->
                    <div class="alert alert-info mb-3" id="modalItemInfo">
                        <strong id="modalItemName"></strong>
                        <p class="mb-0 small" id="modalItemDetails"></p>
                    </div>

                    <!-- Upload Section -->
                    <div class="mb-4">
                        <h6>@lang('Upload New Photos')</h6>
                        <div class="d-flex gap-2 flex-wrap">
                            <input type="file" class="form-control" id="modalPhotoUpload" multiple
                                accept="image/jpeg,image/png,image/jpg,image/webp" style="flex: 1;">
                            <button class="btn btn-success" id="modalUploadBtn" disabled>
                                <i class="fas fa-upload"></i> @lang('Upload')
                            </button>
                        </div>
                        <small class="text-muted">@lang('You can select multiple images. Max 5MB each.')</small>
                    </div>

                    <!-- Loading -->
                    <div class="text-center py-4" id="modalLoading" style="display: none;">
                        <i class="fas fa-spinner fa-spin fa-2x"></i>
                        <p>@lang('Loading...')</p>
                    </div>

                    <!-- Photos Grid -->
                    <div id="modalPhotoGrid" class="row g-3">
                        {{-- Photos will be loaded here --}}
                    </div>

                    <!-- No Photos -->
                    <div class="text-center py-4" id="modalNoPhotos" style="display: none;">
                        <i class="fas fa-images fa-3x text-muted mb-2"></i>
                        <p class="text-muted">@lang('No photos yet. Upload some photos above.')</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('styles')
    <style>
        .modal-photo-card {
            position: relative;
        }
        .modal-photo-card img {
            width: 100%;
            height: 120px;
            object-fit: cover;
            border-radius: 8px;
            border: 2px solid transparent;
            cursor: move;
        }
        .modal-photo-card.is-primary img {
            border-color: var(--action-primary, #28a745);
        }
        .modal-photo-card .photo-actions {
            position: absolute;
            top: 5px;
            right: 5px;
            display: flex;
            gap: 5px;
        }
        .modal-photo-card .btn-action {
            background: rgba(0, 0, 0, 0.6);
            color: white;
            border: none;
            border-radius: 50%;
            width: 28px;
            height: 28px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 12px;
        }
        .modal-photo-card .btn-action:hover {
            background: rgba(0, 0, 0, 0.8);
        }
        .modal-photo-card .btn-delete:hover {
            background: rgba(220, 53, 69, 0.9);
        }
        .modal-photo-card .btn-primary-photo:hover {
            background: rgba(40, 167, 69, 0.9);
        }
        .modal-photo-card .primary-badge {
            position: absolute;
            bottom: 5px;
            left: 5px;
            background: var(--action-primary, #28a745);
            color: white;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 10px;
        }
        .modal-photo-card .order-badge {
            position: absolute;
            top: 5px;
            left: 5px;
            background: rgba(0, 0, 0, 0.6);
            color: white;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 10px;
        }
        .modal-photo-card.sortable-ghost {
            opacity: 0.4;
        }
        .photos-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 500;
        }
        .photos-badge.has-photos {
            background: var(--action-primary-light, #d4edda);
            color: var(--action-primary, #28a745);
        }
        .photos-badge.no-photos {
            background: var(--bg-secondary, #f0f0f0);
            color: var(--text-secondary, #6c757d);
        }
    </style>
@endsection

@section('script')
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
    <script>
        "use strict";

        $(document).ready(function() {
            const baseUrl = '{{ url("merchant/my-items/images") }}';
            let currentMerchantItemId = null;
            let dataTable = null;

            // Initialize DataTable
            dataTable = $('#itemsTable').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: '{{ route("merchant-my-item-images-datatables") }}',
                    type: 'GET',
                    data: function(d) {
                        d._token = '{{ csrf_token() }}';
                        d.part_number_search = $('#partNumberSearch').val();
                    }
                },
                columns: [
                    { data: 'part_number', name: 'part_number' },
                    { data: 'name', name: 'name' },
                    { data: 'branch', name: 'branch' },
                    { data: 'quality_brand', name: 'quality_brand' },
                    {
                        data: 'photos_count',
                        name: 'photos_count',
                        className: 'text-center',
                        render: function(data) {
                            if (data > 0) {
                                return '<span class="photos-badge has-photos"><i class="fas fa-images"></i> ' + data + '</span>';
                            }
                            return '<span class="photos-badge no-photos"><i class="fas fa-image"></i> 0</span>';
                        }
                    },
                    {
                        data: 'id',
                        name: 'actions',
                        className: 'text-center',
                        orderable: false,
                        render: function(data) {
                            return '<button class="btn btn-sm btn-primary manage-photos-btn" data-id="' + data + '">' +
                                '<i class="fas fa-camera"></i> @lang("Manage Photos")</button>';
                        }
                    }
                ],
                language: {
                    processing: "@lang('Processing...')",
                    search: "@lang('Search:')",
                    lengthMenu: "@lang('Show _MENU_ entries')",
                    info: "@lang('Showing _START_ to _END_ of _TOTAL_ entries')",
                    infoEmpty: "@lang('Showing 0 to 0 of 0 entries')",
                    infoFiltered: "@lang('(filtered from _MAX_ total entries)')",
                    loadingRecords: "@lang('Loading...')",
                    zeroRecords: "@lang('No matching records found')",
                    emptyTable: "@lang('No data available in table')",
                    paginate: {
                        first: "@lang('First')",
                        previous: "@lang('Previous')",
                        next: "@lang('Next')",
                        last: "@lang('Last')"
                    }
                },
                order: [[0, 'asc']]
            });

            // Part Number Search
            let searchTimeout = null;
            $('#partNumberSearch').on('keyup', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(function() {
                    dataTable.ajax.reload();
                }, 400);
            });

            $('#clearSearchBtn').click(function() {
                $('#partNumberSearch').val('');
                dataTable.ajax.reload();
            });

            // Open photo modal
            $(document).on('click', '.manage-photos-btn', function() {
                const itemId = $(this).data('id');
                currentMerchantItemId = itemId;
                openPhotoModal(itemId);
            });

            function openPhotoModal(itemId) {
                $('#modalLoading').show();
                $('#modalPhotoGrid').empty();
                $('#modalNoPhotos').hide();
                $('#modalItemInfo').hide();
                $('#photoModal').modal('show');

                $.ajax({
                    url: baseUrl + '/' + itemId,
                    type: 'GET',
                    success: function(response) {
                        $('#modalLoading').hide();

                        if (response.success) {
                            // Show item info
                            $('#modalItemName').text(response.item.name);
                            $('#modalItemDetails').html(
                                '<strong>@lang("Part Number"):</strong> ' + response.item.part_number +
                                ' | <strong>@lang("Branch"):</strong> ' + response.item.branch +
                                ' | <strong>@lang("Quality Brand"):</strong> ' + response.item.quality_brand
                            );
                            $('#modalItemInfo').show();

                            displayModalPhotos(response.photos);
                        }
                    },
                    error: function() {
                        $('#modalLoading').hide();
                        toastr.error('@lang("Error loading photos")');
                    }
                });
            }

            function displayModalPhotos(photos) {
                const $grid = $('#modalPhotoGrid');
                $grid.empty();

                if (photos.length === 0) {
                    $('#modalNoPhotos').show();
                    return;
                }

                $('#modalNoPhotos').hide();

                photos.forEach(function(photo, index) {
                    const primaryClass = photo.is_primary ? 'is-primary' : '';
                    const primaryBadge = photo.is_primary ? '<span class="primary-badge">@lang("Primary")</span>' : '';
                    const setPrimaryBtn = !photo.is_primary ?
                        '<button type="button" class="btn-action btn-primary-photo" data-id="' + photo.id + '" title="@lang("Set as Primary")"><i class="fas fa-star"></i></button>' : '';

                    const html = `
                        <div class="col-6 col-md-4 col-lg-3 modal-photo-card ${primaryClass}" data-id="${photo.id}">
                            <span class="order-badge">#${index + 1}</span>
                            <img src="${photo.photo_url}" alt="Photo">
                            <div class="photo-actions">
                                ${setPrimaryBtn}
                                <button type="button" class="btn-action btn-delete" data-id="${photo.id}" title="@lang('Delete')">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                            ${primaryBadge}
                        </div>
                    `;
                    $grid.append(html);
                });

                initModalSortable();
            }

            function initModalSortable() {
                const grid = document.getElementById('modalPhotoGrid');
                if (grid) {
                    new Sortable(grid, {
                        animation: 150,
                        ghostClass: 'sortable-ghost',
                        onEnd: function() {
                            updateModalPhotoOrder();
                        }
                    });
                }
            }

            function updateModalPhotoOrder() {
                const photos = [];
                $('#modalPhotoGrid .modal-photo-card').each(function(index) {
                    const id = $(this).data('id');
                    photos.push({ id: id, sort_order: index + 1 });
                    $(this).find('.order-badge').text('#' + (index + 1));
                });

                $.ajax({
                    url: baseUrl + '/' + currentMerchantItemId,
                    type: 'POST',
                    data: {
                        photos: photos,
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        if (!response.success) {
                            toastr.error('@lang("Failed to update order")');
                        }
                    }
                });
            }

            // Set as primary
            $(document).on('click', '.btn-primary-photo', function() {
                const photoId = $(this).data('id');

                $.ajax({
                    url: baseUrl + '/' + photoId,
                    type: 'POST',
                    data: {
                        is_primary: true,
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        if (response.success) {
                            openPhotoModal(currentMerchantItemId);
                        } else {
                            toastr.error(response.message || '@lang("Failed to set as primary")');
                        }
                    }
                });
            });

            // Delete photo
            $(document).on('click', '.btn-delete', function() {
                const $btn = $(this);
                const photoId = $btn.data('id');

                if (!confirm('@lang("Are you sure you want to delete this photo?")')) {
                    return;
                }

                $.ajax({
                    url: baseUrl + '/' + photoId,
                    type: 'DELETE',
                    data: { _token: '{{ csrf_token() }}' },
                    success: function(response) {
                        if (response.success) {
                            $btn.closest('.modal-photo-card').fadeOut(300, function() {
                                $(this).remove();
                                updateModalPhotoOrder();

                                if ($('#modalPhotoGrid .modal-photo-card').length === 0) {
                                    $('#modalNoPhotos').show();
                                }

                                // Refresh table
                                dataTable.ajax.reload(null, false);
                            });
                        } else {
                            toastr.error(response.message || '@lang("Failed to delete photo")');
                        }
                    }
                });
            });

            // Upload photos
            $('#modalPhotoUpload').change(function() {
                $('#modalUploadBtn').prop('disabled', !this.files.length);
            });

            $('#modalUploadBtn').click(function() {
                const files = $('#modalPhotoUpload')[0].files;
                if (!files.length || !currentMerchantItemId) return;

                const formData = new FormData();
                formData.append('merchant_item_id', currentMerchantItemId);
                formData.append('_token', '{{ csrf_token() }}');

                for (let i = 0; i < files.length; i++) {
                    formData.append('photos[]', files[i]);
                }

                const $btn = $(this);
                $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');

                $.ajax({
                    url: baseUrl,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        $btn.prop('disabled', false).html('<i class="fas fa-upload"></i> @lang("Upload")');
                        $('#modalPhotoUpload').val('');

                        if (response.success) {
                            openPhotoModal(currentMerchantItemId);
                            dataTable.ajax.reload(null, false);
                        } else {
                            toastr.error(response.message || '@lang("Upload failed")');
                        }
                    },
                    error: function(xhr) {
                        $btn.prop('disabled', false).html('<i class="fas fa-upload"></i> @lang("Upload")');
                        let message = '@lang("Upload failed")';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            message = xhr.responseJSON.message;
                        }
                        toastr.error(message);
                    }
                });
            });

            // Reset modal on close
            $('#photoModal').on('hidden.bs.modal', function() {
                currentMerchantItemId = null;
                $('#modalPhotoUpload').val('');
                $('#modalUploadBtn').prop('disabled', true);
            });
        });
    </script>
@endsection
