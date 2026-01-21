@extends('layouts.operator')

@section('content')
<input type="hidden" id="headerdata" value="{{ __('ALTERNATIVES') }}">
<div class="content-area">
    <div class="mr-breadcrumb">
        <div class="row">
            <div class="col-lg-12">
                <h4 class="heading">{{ __('Manage Alternatives') }}</h4>
                <ul class="links">
                    <li>
                        <a href="{{ route('operator.dashboard') }}">{{ __('Dashboard') }}</a>
                    </li>
                    <li>
                        <a href="{{ route('operator-alternative-index') }}">{{ __('Alternatives') }}</a>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <div class="catalogItem-area">
        <div class="row">
            <div class="col-lg-12">
                <div class="mr-table allproduct">
                    {{-- Search Form --}}
                    <div class="card mb-4">
                        <div class="card-header bg-primary text-white">
                            <i class="fas fa-search me-2"></i>{{ __('Search for Part Number') }}
                        </div>
                        <div class="card-body">
                            <form action="{{ route('operator-alternative-index') }}" method="GET" class="row g-3">
                                <div class="col-md-8">
                                    <input type="text"
                                           name="q"
                                           class="form-control form-control-lg"
                                           placeholder="{{ __('Enter part number...') }}"
                                           value="{{ $query }}"
                                           autocomplete="off"
                                           id="searchPartNumber">
                                </div>
                                <div class="col-md-4">
                                    <button type="submit" class="btn btn-primary btn-lg w-100">
                                        <i class="fas fa-search me-2"></i>{{ __('Search') }}
                                    </button>
                                </div>
                            </form>
                            <div id="searchSuggestions" class="mt-2"></div>
                        </div>
                    </div>

                    @if($query && !$catalogItem)
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            {{ __('Part number not found in catalog') }}: <strong>{{ $query }}</strong>
                        </div>
                    @endif

                    @if($catalogItem)
                        {{-- Main Item Info --}}
                        <div class="card mb-4">
                            <div class="card-header bg-success text-white">
                                <i class="fas fa-box me-2"></i>{{ __('Catalog Item') }}
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-2">
                                        @if($catalogItem->photo)
                                            <img src="{{ asset('assets/images/products/' . $catalogItem->photo) }}"
                                                 alt="{{ $catalogItem->part_number }}"
                                                 class="img-fluid rounded">
                                        @else
                                            <img src="{{ asset('assets/images/noimage.png') }}"
                                                 alt="No Image"
                                                 class="img-fluid rounded">
                                        @endif
                                    </div>
                                    <div class="col-md-10">
                                        <h4 class="mb-2">{{ $catalogItem->part_number }}</h4>
                                        <p class="mb-1"><strong>{{ __('English') }}:</strong> {{ $catalogItem->label_en ?: '-' }}</p>
                                        <p class="mb-1"><strong>{{ __('Arabic') }}:</strong> {{ $catalogItem->label_ar ?: '-' }}</p>
                                        @if($skuRecord)
                                            <p class="mb-0">
                                                <span class="badge bg-info">{{ __('Group ID') }}: {{ $skuRecord->group_id }}</span>
                                                <span class="badge bg-secondary">{{ __('Alternatives') }}: {{ $alternatives->count() }}</span>
                                            </p>
                                        @else
                                            <p class="mb-0">
                                                <span class="badge bg-warning text-dark">{{ __('Not in alternatives system') }}</span>
                                            </p>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Add Alternative Form --}}
                        <div class="card mb-4">
                            <div class="card-header bg-info text-white">
                                <i class="fas fa-plus me-2"></i>{{ __('Add Alternative') }}
                            </div>
                            <div class="card-body">
                                <div class="row g-3 align-items-end">
                                    <div class="col-md-8">
                                        <label class="form-label">{{ __('Alternative Part Number') }}</label>
                                        <input type="text"
                                               id="alternativePartNumber"
                                               class="form-control"
                                               placeholder="{{ __('Enter alternative part number...') }}">
                                        <div id="altSuggestions" class="mt-2"></div>
                                    </div>
                                    <div class="col-md-4">
                                        <button type="button"
                                                id="addAlternativeBtn"
                                                class="btn btn-success w-100"
                                                data-main="{{ $catalogItem->part_number }}">
                                            <i class="fas fa-plus me-2"></i>{{ __('Add Alternative') }}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Alternatives List --}}
                        <div class="card">
                            <div class="card-header bg-secondary text-white d-flex justify-content-between align-items-center">
                                <span><i class="fas fa-list me-2"></i>{{ __('Alternatives') }} ({{ $alternatives->count() }})</span>
                            </div>
                            <div class="card-body">
                                @if($alternatives->isEmpty())
                                    <div class="alert alert-info mb-0">
                                        <i class="fas fa-info-circle me-2"></i>
                                        {{ __('No alternatives found for this item') }}
                                    </div>
                                @else
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>{{ __('Photo') }}</th>
                                                    <th>{{ __('Part Number') }}</th>
                                                    <th>{{ __('Name (EN)') }}</th>
                                                    <th>{{ __('Name (AR)') }}</th>
                                                    <th>{{ __('Actions') }}</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($alternatives as $alt)
                                                    <tr id="alt-row-{{ $alt->id }}">
                                                        <td>
                                                            @if($alt->catalogItem && $alt->catalogItem->photo)
                                                                <img src="{{ asset('assets/images/products/' . $alt->catalogItem->photo) }}"
                                                                     alt="{{ $alt->part_number }}"
                                                                     style="max-width: 60px;">
                                                            @else
                                                                <img src="{{ asset('assets/images/noimage.png') }}"
                                                                     alt="No Image"
                                                                     style="max-width: 60px;">
                                                            @endif
                                                        </td>
                                                        <td>
                                                            <a href="{{ route('operator-alternative-index', ['q' => $alt->part_number]) }}">
                                                                <strong>{{ $alt->part_number }}</strong>
                                                            </a>
                                                        </td>
                                                        <td>{{ $alt->catalogItem->label_en ?? '-' }}</td>
                                                        <td>{{ $alt->catalogItem->label_ar ?? '-' }}</td>
                                                        <td>
                                                            <button type="button"
                                                                    class="btn btn-sm btn-danger remove-alt-btn"
                                                                    data-part="{{ $alt->part_number }}"
                                                                    data-id="{{ $alt->id }}"
                                                                    title="{{ __('Remove from group') }}">
                                                                <i class="fas fa-unlink"></i>
                                                            </button>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Stats Link --}}
<div class="content-area mt-3">
    <a href="{{ route('operator-alternative-stats') }}" class="btn btn-outline-primary">
        <i class="fas fa-chart-bar me-2"></i>{{ __('View Statistics') }}
    </a>
</div>
@endsection

@section('scripts')
<script>
(function($) {
    "use strict";

    const searchUrl = "{{ route('operator-alternative-search') }}";
    const addUrl = "{{ route('operator-alternative-add') }}";
    const removeUrl = "{{ route('operator-alternative-remove') }}";
    const csrfToken = "{{ csrf_token() }}";

    // Search suggestions for main search
    let searchTimeout;
    $('#searchPartNumber').on('input', function() {
        clearTimeout(searchTimeout);
        const query = $(this).val();
        if (query.length < 2) {
            $('#searchSuggestions').html('');
            return;
        }
        searchTimeout = setTimeout(() => {
            $.get(searchUrl, { q: query }, function(data) {
                let html = '<div class="list-group">';
                data.forEach(item => {
                    html += `<a href="?q=${encodeURIComponent(item.part_number)}" class="list-group-item list-group-item-action">
                        <strong>${item.part_number}</strong> - ${item.label || ''}
                    </a>`;
                });
                html += '</div>';
                $('#searchSuggestions').html(data.length ? html : '');
            });
        }, 300);
    });

    // Search suggestions for alternative input
    let altTimeout;
    $('#alternativePartNumber').on('input', function() {
        clearTimeout(altTimeout);
        const query = $(this).val();
        if (query.length < 2) {
            $('#altSuggestions').html('');
            return;
        }
        altTimeout = setTimeout(() => {
            $.get(searchUrl, { q: query }, function(data) {
                let html = '<div class="list-group">';
                data.forEach(item => {
                    html += `<a href="#" class="list-group-item list-group-item-action alt-suggestion" data-part="${item.part_number}">
                        <strong>${item.part_number}</strong> - ${item.label || ''}
                    </a>`;
                });
                html += '</div>';
                $('#altSuggestions').html(data.length ? html : '');
            });
        }, 300);
    });

    // Select alternative suggestion
    $(document).on('click', '.alt-suggestion', function(e) {
        e.preventDefault();
        $('#alternativePartNumber').val($(this).data('part'));
        $('#altSuggestions').html('');
    });

    // Add alternative
    $('#addAlternativeBtn').on('click', function() {
        const mainPart = $(this).data('main');
        const altPart = $('#alternativePartNumber').val().trim();

        if (!altPart) {
            alert("{{ __('Please enter alternative part number') }}");
            return;
        }

        $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');

        $.ajax({
            url: addUrl,
            method: 'POST',
            data: {
                _token: csrfToken,
                main_part_number: mainPart,
                alternative_part_number: altPart
            },
            success: function(response) {
                alert(response.message);
                location.reload();
            },
            error: function(xhr) {
                const msg = xhr.responseJSON?.message || "{{ __('Error occurred') }}";
                alert(msg);
                $('#addAlternativeBtn').prop('disabled', false)
                    .html('<i class="fas fa-plus me-2"></i>{{ __("Add Alternative") }}');
            }
        });
    });

    // Remove alternative (move to new group)
    $(document).on('click', '.remove-alt-btn', function() {
        if (!confirm("{{ __('Are you sure you want to remove this item from the group?') }}")) {
            return;
        }

        const btn = $(this);
        const partNumber = btn.data('part');
        const rowId = btn.data('id');

        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');

        $.ajax({
            url: removeUrl,
            method: 'POST',
            data: {
                _token: csrfToken,
                part_number: partNumber
            },
            success: function(response) {
                $('#alt-row-' + rowId).fadeOut(300, function() {
                    $(this).remove();
                });
            },
            error: function(xhr) {
                const msg = xhr.responseJSON?.message || "{{ __('Error occurred') }}";
                alert(msg);
                btn.prop('disabled', false).html('<i class="fas fa-unlink"></i>');
            }
        });
    });

})(jQuery);
</script>
@endsection
