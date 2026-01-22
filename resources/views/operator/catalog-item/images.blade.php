@extends('layouts.operator')

@section('content')
    <div class="content-area">
        <div class="mr-breadcrumb">
            <div class="row">
                <div class="col-lg-12">
                    <h4 class="heading">{{ __('CatalogItem Images') }}
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
                            <a href="javascript:;">{{ __('Images') }}</a>
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
                            {{-- Search Form --}}
                            <div class="row">
                                <div class="col-lg-12">
                                    <div class="left-area">
                                        <h4 class="heading">{{ __('Search by Part Number') }}</h4>
                                    </div>
                                </div>
                                <div class="col-lg-8">
                                    <select class="form-control" id="partNumberSelect" style="width: 100%;">
                                        <option value="">{{ __('Type to search part number...') }}</option>
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

                            {{-- Item Details & Images --}}
                            <div id="itemDetails" style="display: none;">
                                <hr class="my-4">

                                {{-- Item Info --}}
                                <div class="row">
                                    <div class="col-lg-12">
                                        <div class="alert alert-info">
                                            <h5 id="itemName"></h5>
                                            <p class="mb-0">
                                                <strong>{{ __('Part Number') }}:</strong>
                                                <span id="itemPartNumber"></span>
                                                |
                                                <strong>{{ __('ID') }}:</strong>
                                                <span id="itemId"></span>
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                {{-- Images Form --}}
                                <form id="imageForm" enctype="multipart/form-data">
                                    @csrf
                                    <input type="hidden" id="catalogItemId" name="catalog_item_id">

                                    <div class="row">
                                        {{-- Main Photo --}}
                                        <div class="col-lg-6">
                                            <div class="card">
                                                <div class="card-header">
                                                    <h5>{{ __('Main Photo') }}</h5>
                                                </div>
                                                <div class="card-body text-center">
                                                    <div class="image-preview mb-3" id="photoPreview">
                                                        <img src="{{ asset('assets/images/noimage.png') }}"
                                                            id="photoImg"
                                                            style="max-width: 200px; max-height: 200px; object-fit: contain;">
                                                    </div>
                                                    <input type="file" class="form-control mb-2" name="photo"
                                                        id="photoInput" accept="image/*">
                                                    <div class="form-check" id="deletePhotoWrapper" style="display: none;">
                                                        <input type="checkbox" class="form-check-input" name="delete_photo"
                                                            id="deletePhoto" value="1">
                                                        <label class="form-check-label" for="deletePhoto">
                                                            {{ __('Delete current photo') }}
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        {{-- Thumbnail --}}
                                        <div class="col-lg-6">
                                            <div class="card">
                                                <div class="card-header">
                                                    <h5>{{ __('Thumbnail') }}</h5>
                                                </div>
                                                <div class="card-body text-center">
                                                    <div class="image-preview mb-3" id="thumbnailPreview">
                                                        <img src="{{ asset('assets/images/noimage.png') }}"
                                                            id="thumbnailImg"
                                                            style="max-width: 200px; max-height: 200px; object-fit: contain;">
                                                    </div>
                                                    <input type="file" class="form-control mb-2" name="thumbnail"
                                                        id="thumbnailInput" accept="image/*">
                                                    <div class="form-check" id="deleteThumbnailWrapper" style="display: none;">
                                                        <input type="checkbox" class="form-check-input" name="delete_thumbnail"
                                                            id="deleteThumbnail" value="1">
                                                        <label class="form-check-label" for="deleteThumbnail">
                                                            {{ __('Delete current thumbnail') }}
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Submit Button --}}
                                    <div class="row mt-4">
                                        <div class="col-lg-12 text-center">
                                            <button type="submit" class="btn btn-primary btn-lg" id="saveBtn">
                                                <i class="fas fa-save"></i> {{ __('Save Images') }}
                                            </button>
                                        </div>
                                    </div>
                                </form>
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
            height: 45px;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 43px;
        }
        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 27px;
        }
        .select2-results__option {
            padding: 10px 12px;
        }
        .select2-container--default .select2-results__option--highlighted[aria-selected] {
            background-color: #007bff;
        }
    </style>
@endsection

@section('scripts')
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(document).ready(function() {
            const autocompleteUrl = '{{ route("operator-catalog-item-images-autocomplete") }}';
            const showUrl = '{{ url("operator/catalog-items/images") }}';
            const updateUrl = '{{ url("operator/catalog-items/images") }}';
            const noImage = '{{ asset("assets/images/noimage.png") }}';

            // Initialize Select2
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
                                    id: item.id,
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

            // On select change
            $('#partNumberSelect').on('select2:select', function(e) {
                const data = e.params.data;
                loadCatalogItem(data.id);
            });

            // On clear
            $('#partNumberSelect').on('select2:clear', function() {
                $('#itemDetails').hide();
            });

            function loadCatalogItem(id) {
                $('#loadingIndicator').show();
                $('#itemDetails').hide();

                $.ajax({
                    url: showUrl + '/' + id,
                    type: 'GET',
                    success: function(response) {
                        $('#loadingIndicator').hide();

                        if (response.success) {
                            displayItemDetails(response.data);
                        } else {
                            $.notify(response.message || '{{ __("Error loading item") }}', 'error');
                        }
                    },
                    error: function(xhr) {
                        $('#loadingIndicator').hide();
                        $.notify('{{ __("Error loading item") }}', 'error');
                    }
                });
            }

            function displayItemDetails(data) {
                $('#catalogItemId').val(data.id);
                $('#itemName').text(data.name);
                $('#itemPartNumber').text(data.part_number);
                $('#itemId').text(data.id);

                // Photo
                if (data.photo_url) {
                    $('#photoImg').attr('src', data.photo_url);
                    $('#deletePhotoWrapper').show();
                } else {
                    $('#photoImg').attr('src', noImage);
                    $('#deletePhotoWrapper').hide();
                }

                // Thumbnail
                if (data.thumbnail_url) {
                    $('#thumbnailImg').attr('src', data.thumbnail_url);
                    $('#deleteThumbnailWrapper').show();
                } else {
                    $('#thumbnailImg').attr('src', noImage);
                    $('#deleteThumbnailWrapper').hide();
                }

                // Reset form inputs
                $('#photoInput').val('');
                $('#thumbnailInput').val('');
                $('#deletePhoto').prop('checked', false);
                $('#deleteThumbnail').prop('checked', false);

                $('#itemDetails').show();
            }

            // Preview image on file select
            $('#photoInput').change(function() {
                previewImage(this, '#photoImg');
            });

            $('#thumbnailInput').change(function() {
                previewImage(this, '#thumbnailImg');
            });

            function previewImage(input, target) {
                if (input.files && input.files[0]) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        $(target).attr('src', e.target.result);
                    };
                    reader.readAsDataURL(input.files[0]);
                }
            }

            // Form submit
            $('#imageForm').submit(function(e) {
                e.preventDefault();

                const catalogItemId = $('#catalogItemId').val();
                if (!catalogItemId) {
                    $.notify('{{ __("Please search for a catalog item first") }}', 'error');
                    return;
                }

                const formData = new FormData(this);
                const $btn = $('#saveBtn');
                $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> {{ __("Saving...") }}');

                $.ajax({
                    url: updateUrl + '/' + catalogItemId,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        $btn.prop('disabled', false).html('<i class="fas fa-save"></i> {{ __("Save Images") }}');

                        if (response.success) {
                            $.notify('{{ __("Images updated successfully") }}', 'success');

                            // Update previews if new images were uploaded
                            if (response.data.photo !== undefined) {
                                if (response.data.photo) {
                                    $('#photoImg').attr('src', response.data.photo);
                                    $('#deletePhotoWrapper').show();
                                } else {
                                    $('#photoImg').attr('src', noImage);
                                    $('#deletePhotoWrapper').hide();
                                }
                            }

                            if (response.data.thumbnail !== undefined) {
                                if (response.data.thumbnail) {
                                    $('#thumbnailImg').attr('src', response.data.thumbnail);
                                    $('#deleteThumbnailWrapper').show();
                                } else {
                                    $('#thumbnailImg').attr('src', noImage);
                                    $('#deleteThumbnailWrapper').hide();
                                }
                            }

                            // Reset inputs
                            $('#photoInput').val('');
                            $('#thumbnailInput').val('');
                            $('#deletePhoto').prop('checked', false);
                            $('#deleteThumbnail').prop('checked', false);
                        } else {
                            $.notify(response.message || '{{ __("An error occurred") }}', 'error');
                        }
                    },
                    error: function(xhr) {
                        $btn.prop('disabled', false).html('<i class="fas fa-save"></i> {{ __("Save Images") }}');
                        let message = '{{ __("An error occurred") }}';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            message = xhr.responseJSON.message;
                        }
                        $.notify(message, 'error');
                    }
                });
            });
        });
    </script>
@endsection
