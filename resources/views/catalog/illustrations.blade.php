@extends('layouts.front3')

@section('title', ($level3Category->label ?? $level3Category->full_code) . ' - ' . __('Illustrations'))

@section('content')
<div class="container-fluid py-3">
    {{-- Breadcrumb --}}
    <div class="product-nav-wrapper mb-3 px-2">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb text-uppercase mb-0 flex-wrap">
                <li class="breadcrumb-item">
                    <a class="text-black text-decoration-none" href="{{ route('front.index') }}">
                        <i class="fas fa-home d-md-none"></i>
                        <span class="d-none d-md-inline">{{ __('Home') }}</span>
                    </a>
                </li>
                <li class="breadcrumb-item">
                    <a class="text-black text-decoration-none" href="{{ route('catalog.index', $brand->name) }}">
                        {{ strtoupper($brand->name) }}
                    </a>
                </li>
                <li class="breadcrumb-item">
                    <a class="text-black text-decoration-none" href="{{ route('catalog.level1', [
                        'brand' => $brand->name,
                        'catalog' => $catalog->code,
                        'vin' => $vin
                    ]) }}">
                        {{ strtoupper($catalog->shortName ?? $catalog->name ?? $catalog->code) }}
                    </a>
                </li>
                <li class="breadcrumb-item">
                    <a class="text-black text-decoration-none" href="{{ route('catalog.level2', [
                        'brand' => $brand->name,
                        'catalog' => $catalog->code,
                        'key1' => $key1,
                        'vin' => $vin
                    ]) }}">
                        {{ strtoupper($key1) }}
                    </a>
                </li>
                <li class="breadcrumb-item">
                    <a class="text-black text-decoration-none" href="{{ route('catalog.level3', [
                        'brand' => $brand->name,
                        'catalog' => $catalog->code,
                        'key1' => $key1,
                        'key2' => $key2,
                        'vin' => $vin
                    ]) }}">
                        {{ strtoupper($key2) }}
                    </a>
                </li>
                @if($vin)
                    <li class="breadcrumb-item">
                        <span class="text-muted">
                            <i class="fas fa-car me-1"></i>
                            <span class="d-none d-md-inline">{{ $vin }}</span>
                            <span class="d-md-none">VIN</span>
                        </span>
                    </li>
                @endif
                <li class="breadcrumb-item active text-primary" aria-current="page">
                    <strong>{{ strtoupper($key3) }}</strong>
                </li>
            </ol>
        </nav>
    </div>

    {{-- Chips Bar --}}
    <div class="px-2 mb-3">
        @include('catalog.partials.chips-bar', ['chips' => $chips])
    </div>

    {{-- Main Content --}}
    <div class="row g-3">
        {{-- Left Side: Illustration Image --}}
        <div class="col-lg-6 col-md-6">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-primary text-white py-2">
                    <h6 class="mb-0">
                        <i class="fas fa-image me-1"></i>
                        {{ __('Illustration') }}
                        @if($illustration)
                            <span class="badge bg-light text-primary ms-1">{{ $illustration->code ?? $section->full_code ?? '' }}</span>
                        @endif
                    </h6>
                </div>
                <div class="card-body p-2 text-center" style="min-height: 500px;">
                    @if($illustration && $illustration->image_path)
                        <div id="imageContainer" class="position-relative h-100">
                            <img id="mainImage"
                                 src="{{ Storage::url($illustration->image_path) }}"
                                 alt="{{ $illustration->code ?? 'Illustration' }}"
                                 class="img-fluid h-100"
                                 style="object-fit: contain; max-width: 100%; cursor: zoom-in;"
                                 onclick="openImageModal(this.src)"
                                 onerror="this.onerror=null; this.src='{{ asset('assets/images/no-image.png') }}';">
                        </div>
                    @else
                        <div class="d-flex align-items-center justify-content-center h-100">
                            <div class="text-muted">
                                <i class="fas fa-image fa-3x mb-2"></i>
                                <p>{{ __('No illustration available') }}</p>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Right Side: Parts Table --}}
        <div class="col-lg-6 col-md-6">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white py-2 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">
                        <i class="fas fa-list me-1"></i>
                        {{ __('Parts List') }}
                        <span class="badge bg-primary ms-1">{{ $callouts->count() }}</span>
                    </h6>
                    <div class="input-group input-group-sm" style="width: 200px;">
                        <input type="text" class="form-control" id="partsSearch" placeholder="{{ __('Search parts...') }}">
                        <button class="btn btn-outline-secondary" type="button">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive" style="max-height: 500px; overflow-y: auto;">
                        <table class="table table-hover table-striped mb-0" id="partsTable">
                            <thead class="table-dark sticky-top">
                                <tr>
                                    <th style="width: 60px;">#</th>
                                    <th>{{ __('Part Number') }}</th>
                                    <th>{{ __('Description') }}</th>
                                    <th style="width: 80px;">{{ __('Qty') }}</th>
                                    <th style="width: 120px;">{{ __('Actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($callouts as $index => $callout)
                                    <tr class="part-row"
                                        data-part-number="{{ $callout->partNumber ?? $callout->part_number ?? '' }}"
                                        data-callout="{{ $callout->callout ?? ($index + 1) }}">
                                        <td class="text-center">
                                            <span class="badge bg-secondary callout-badge">
                                                {{ $callout->callout ?? ($index + 1) }}
                                            </span>
                                        </td>
                                        <td>
                                            @php
                                                $partNum = $callout->partNumber ?? $callout->part_number ?? '';
                                            @endphp
                                            @if($partNum)
                                                <a href="{{ route('front.catalog', $partNum) }}"
                                                   class="text-decoration-none fw-bold text-primary">
                                                    {{ $partNum }}
                                                </a>
                                            @else
                                                <span class="text-muted">N/A</span>
                                            @endif
                                        </td>
                                        <td>{{ $callout->description ?? $callout->label ?? '-' }}</td>
                                        <td class="text-center">{{ $callout->quantity ?? 1 }}</td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                @if($partNum)
                                                    <a href="{{ route('front.catalog', $partNum) }}"
                                                       class="btn btn-outline-primary" title="{{ __('View') }}">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <button type="button" class="btn btn-outline-success btn-add-to-cart"
                                                            data-part-number="{{ $partNum }}"
                                                            title="{{ __('Add to Cart') }}">
                                                        <i class="fas fa-cart-plus"></i>
                                                    </button>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center py-4 text-muted">
                                            <i class="fas fa-box-open fa-2x mb-2"></i>
                                            <p class="mb-0">{{ __('No parts available') }}</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Image Modal --}}
<div class="modal fade" id="imageModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-fullscreen">
        <div class="modal-content bg-dark">
            <div class="modal-header border-0">
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body d-flex align-items-center justify-content-center">
                <img id="modalImage" src="" alt="Full Size" class="img-fluid" style="max-height: 90vh;">
            </div>
        </div>
    </div>
</div>

<style>
#imageContainer {
    background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
    border-radius: 0.5rem;
}
.part-row:hover {
    background-color: #e3f2fd !important;
    cursor: pointer;
}
.callout-badge {
    min-width: 30px;
}
.sticky-top {
    position: sticky;
    top: 0;
    z-index: 10;
}
@media (max-width: 768px) {
    #imageContainer {
        min-height: 300px !important;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Parts search
    const partsSearch = document.getElementById('partsSearch');
    const partRows = document.querySelectorAll('.part-row');

    partsSearch?.addEventListener('input', function() {
        const query = this.value.toLowerCase();
        partRows.forEach(row => {
            const partNumber = row.dataset.partNumber?.toLowerCase() || '';
            const text = row.textContent.toLowerCase();
            row.style.display = (partNumber.includes(query) || text.includes(query)) ? '' : 'none';
        });
    });

    // Add to cart buttons
    document.querySelectorAll('.btn-add-to-cart').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.stopPropagation();
            const partNumber = this.dataset.partNumber;
            // Implement add to cart via AJAX
            addToCart(partNumber);
        });
    });

    // Highlight callout on row click
    partRows.forEach(row => {
        row.addEventListener('click', function() {
            // Remove previous highlight
            partRows.forEach(r => r.classList.remove('table-warning'));
            // Add highlight to clicked row
            this.classList.add('table-warning');
        });
    });
});

function openImageModal(src) {
    const modal = new bootstrap.Modal(document.getElementById('imageModal'));
    document.getElementById('modalImage').src = src;
    modal.show();
}

function addToCart(partNumber) {
    // TODO: Implement add to cart AJAX logic
    fetch('/cart/add', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        },
        body: JSON.stringify({ part_number: partNumber, quantity: 1 })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Show success toast or update cart count
            alert('Added to cart: ' + partNumber);
        }
    })
    .catch(error => {
        console.error('Error:', error);
    });
}

// Auto-open callout modal if specified in URL
@if($autoOpen && $highlightCallout)
document.addEventListener('DOMContentLoaded', function() {
    const calloutRow = document.querySelector('[data-callout="{{ $highlightCallout }}"]');
    if (calloutRow) {
        calloutRow.classList.add('table-warning');
        calloutRow.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
});
@endif
</script>
@endsection
