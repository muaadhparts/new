@extends('layouts.front')

@section('content')
    {{-- Breadcrumb --}}
    <section class="gs-breadcrumb-section bg-class"
        data-background="{{ $gs->breadcrumb_banner ? asset('assets/images/' . $gs->breadcrumb_banner) : asset('assets/images/noimage.png') }}">
        <div class="container">
            <div class="row justify-content-center content-wrapper">
                <div class="col-12">
                    <h2 class="breadcrumb-name">{{ $part_number }}</h2>
                    <ul class="bread-menu">
                        <li><a href="{{ route('front.index') }}">@lang('Home')</a></li>
                        @if(count($fitmentBrands) === 1)
                            <li>
                                @if($fitmentBrands[0]['logo'])
                                    <img src="{{ $fitmentBrands[0]['logo'] }}" alt="" class="breadcrumb-brand-logo" style="height: 20px; margin-right: 5px;">
                                @endif
                                {{ $fitmentBrands[0]['name'] }}
                            </li>
                        @elseif(count($fitmentBrands) > 1)
                            <li>
                                <button type="button" class="fitment-brands-btn btn btn-sm btn-outline-light"
                                        data-brands="{{ json_encode($fitmentBrands) }}"
                                        data-part-number="{{ $part_number }}">
                                    <i class="fas fa-car"></i>
                                    @lang('Fits') {{ count($fitmentBrands) }} @lang('brands')
                                </button>
                            </li>
                        @endif
                        <li>{{ $part_number }}</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    {{-- Main Content --}}
    <div class="gs-page-wrapper muaadh-section-gray">
        <div class="container py-4">

            {{-- Part Info Header --}}
            <div class="part-result-header m-card mb-4">
                <div class="m-card__body">
                    <div class="row align-items-center">
                        {{-- Image --}}
                        <div class="col-md-3 col-lg-2 text-center mb-3 mb-md-0">
                            @php
                                $photo = $catalogItem->photo
                                    ? (filter_var($catalogItem->photo, FILTER_VALIDATE_URL) ? $catalogItem->photo : Storage::url($catalogItem->photo))
                                    : asset('assets/images/noimage.png');
                            @endphp
                            <img src="{{ $photo }}"
                                 alt="{{ $catalogItem->showName() }}"
                                 class="part-result-image img-fluid rounded"
                                 style="max-height: 150px; object-fit: contain;">
                        </div>

                        {{-- Info --}}
                        <div class="col-md-9 col-lg-10">
                            <h1 class="part-result-title h4 mb-2">{{ $catalogItem->showName() }}</h1>

                            <div class="part-result-meta d-flex flex-wrap gap-2 mb-3">
                                <span class="badge bg-dark">
                                    <i class="fas fa-barcode me-1"></i>{{ $part_number }}
                                </span>

                                @if($offersData['offers_count'] > 0)
                                    <span class="badge bg-success">
                                        <i class="fas fa-tags me-1"></i>{{ $offersData['offers_count'] }} @lang('offers')
                                    </span>
                                    <span class="badge bg-light text-dark border">
                                        @lang('From') <strong class="text-success">{{ $offersData['lowest_price_formatted'] }}</strong>
                                        @if(($offersData['highest_price_formatted'] ?? null) && ($offersData['lowest_price'] ?? 0) != ($offersData['highest_price'] ?? 0))
                                            @lang('To') <strong class="text-danger">{{ $offersData['highest_price_formatted'] }}</strong>
                                        @endif
                                    </span>
                                @else
                                    <span class="badge bg-secondary">
                                        @lang('No offers available')
                                    </span>
                                @endif

                                {{-- Reviews --}}
                                @if($catalogItem->catalog_reviews_count > 0)
                                    <span class="badge bg-warning text-dark">
                                        <i class="fas fa-star me-1"></i>
                                        {{ number_format($catalogItem->catalog_reviews_avg_rating ?? 0, 1) }}
                                        ({{ $catalogItem->catalog_reviews_count }})
                                    </span>
                                @endif
                            </div>

                            {{-- Fitment Brands --}}
                            @if(count($fitmentBrands) > 0)
                                <div class="part-result-fitment">
                                    <small class="text-muted">@lang('Fits'):</small>
                                    @foreach($fitmentBrands as $brand)
                                        <span class="badge bg-light text-dark border">
                                            @if($brand['logo'])
                                                <img src="{{ $brand['logo'] }}" alt="" style="height: 14px;" class="me-1">
                                            @endif
                                            {{ $brand['name'] }}
                                        </span>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- Offers Section --}}
            @if($offersData['offers_count'] > 0)
                {{-- Sort & Summary Bar --}}
                <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                    <div class="offers-summary">
                        <span class="badge bg-success fs-6">
                            <i class="fas fa-tags me-1"></i>{{ $offersData['offers_count'] }} @lang('offers')
                        </span>
                    </div>

                    @if($offersData['offers_count'] > 1)
                        <div class="offers-sort d-flex align-items-center gap-2">
                            <label for="partResultSort" class="text-muted small mb-0">@lang('Sort by'):</label>
                            <select class="form-select form-select-sm" id="partResultSort" style="min-width: 140px;">
                                <option value="price_asc" {{ ($currentSort ?? 'price_asc') === 'price_asc' ? 'selected' : '' }}>
                                    @lang('Lowest Price')
                                </option>
                                <option value="price_desc" {{ ($currentSort ?? '') === 'price_desc' ? 'selected' : '' }}>
                                    @lang('Highest Price')
                                </option>
                            </select>
                        </div>
                    @endif
                </div>

                <div id="partResultOffersWrapper">
                    @include('frontend.partials.part-result-offers')
                </div>
            @else
                {{-- No Offers --}}
                <div class="m-card">
                    <div class="m-card__body text-center py-5">
                        <i class="fas fa-box-open fa-3x text-muted mb-3"></i>
                        <h5>@lang('No offers available for this part')</h5>
                        <p class="text-muted">@lang('Check back later or search for alternative parts')</p>
                        <a href="{{ route('front.index') }}" class="btn btn-primary">
                            <i class="fas fa-search me-1"></i>@lang('Search Parts')
                        </a>
                    </div>
                </div>
            @endif

        </div>
    </div>
@endsection

@section('script')
<script>
// Sort dropdown handler with AJAX
const sortSelect = document.getElementById('partResultSort');
if (sortSelect) {
    sortSelect.addEventListener('change', function() {
        const sort = this.value;
        const partNumber = '{{ $part_number }}';
        const wrapper = document.getElementById('partResultOffersWrapper');

        if (!wrapper) return;

        // Show loading state
        wrapper.style.opacity = '0.5';
        wrapper.style.pointerEvents = 'none';

        // Fetch sorted offers via AJAX
        fetch(`/result/${encodeURIComponent(partNumber)}?sort=${sort}&ajax=1&_t=${Date.now()}`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Cache-Control': 'no-cache'
            }
        })
        .then(response => {
            if (!response.ok) throw new Error('Network response was not ok');
            return response.text();
        })
        .then(html => {
            wrapper.innerHTML = html;
            wrapper.style.opacity = '1';
            wrapper.style.pointerEvents = 'auto';

            // Reinitialize cart buttons if needed
            if (typeof initCartButtons === 'function') {
                initCartButtons(wrapper);
            }

            // Update URL without reload
            const url = new URL(window.location.href);
            url.searchParams.set('sort', sort);
            history.replaceState(null, '', url.toString());
        })
        .catch(error => {
            console.error('Error loading offers:', error);
            wrapper.style.opacity = '1';
            wrapper.style.pointerEvents = 'auto';
        });
    });
}
</script>
@endsection
