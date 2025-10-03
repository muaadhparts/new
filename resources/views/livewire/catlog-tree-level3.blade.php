<div class="container py-3">
    {{-- Breadcrumb - Responsive --}}
    <div class="product-nav-wrapper mb-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb text-uppercase mb-0 flex-wrap">
                {{-- Home --}}
                <li class="breadcrumb-item">
                    <a class="text-black text-decoration-none" href="{{ route('front.index') }}">
                        <i class="fas fa-home d-md-none"></i>
                        <span class="d-none d-md-inline">{{ __('Home') }}</span>
                    </a>
                </li>

                {{-- Brand --}}
                <li class="breadcrumb-item">
                    <a class="text-black text-decoration-none" href="{{ route('catlogs.index', $brand->name) }}">
                        {{ strtoupper($brand->name) }}
                    </a>
                </li>

                {{-- VIN --}}
                @if(Session::get('vin'))
                    <li class="breadcrumb-item">
                        <a class="text-black text-decoration-none" href="{{ route('tree.level1', [
                            'id' => $brand->name,
                            'data' => $catalog->code,
                            'vin' => Session::get('vin')
                        ]) }}">
                            <i class="fas fa-car d-md-none"></i>
                            <span class="d-none d-md-inline">{{ Session::get('vin') }}</span>
                            <span class="d-md-none">VIN</span>
                        </a>
                    </li>
                @endif

                {{-- Catalog --}}
                <li class="breadcrumb-item d-none d-md-block">
                    <a class="text-black text-decoration-none" href="{{ route('tree.level1', [
                        'id' => $brand->name,
                        'data' => $catalog->code
                    ]) }}">
                        {{ strtoupper($catalog->shortName ?? $catalog->name ?? $catalog->code) }}
                    </a>
                </li>

                {{-- Level 1 --}}
                <li class="breadcrumb-item d-none d-lg-block">
                    <a class="text-black text-decoration-none" href="{{ route('tree.level2', [
                        'id' => $brand->name,
                        'data' => $catalog->code,
                        'key1' => $parentCategory1->full_code
                    ]) }}">
                        {{ strtoupper($parentCategory1->slug ?? $parentCategory1->full_code) }}
                    </a>
                </li>

                {{-- Level 2 - Current --}}
                <li class="breadcrumb-item active text-primary" aria-current="page">
                    <strong>{{ strtoupper($parentCategory2->slug ?? $parentCategory2->full_code) }}</strong>
                </li>
            </ol>
        </nav>
    </div>

    {{-- Search Box - Full Width on Mobile --}}
    <div class="row mb-4">
        <div class="col-12">
            @php
                $allowedCodes = collect($categories ?? [])
                    ->map(function($item) {
                        return is_array($item) ? ($item['full_code'] ?? null) : ($item->full_code ?? null);
                    })
                    ->filter()
                    ->map(fn($v) => (string) $v)
                    ->values()
                    ->all();
            @endphp
            <livewire:vehicle-search-box :catalog="$catalog->code" :allowed-codes-override="$allowedCodes" />
        </div>
    </div>

    {{-- Categories Grid - Responsive --}}
    @if($categories && $categories->count() > 0)
        @if($categories->count() === 1)
            {{-- Auto redirect if only one category --}}
            @php
                $category = $categories->first();
                $redirectUrl = route('illustrations', [
                    'id' => $brand->name,
                    'data' => $catalog->code,
                    'key1' => $parentCategory1->full_code,
                    'key2' => $parentCategory2->full_code,
                    'key3' => $category->full_code,
                    'vin' => Session::get('vin')
                ]);
            @endphp
            <script>window.location.href = "{{ $redirectUrl }}";</script>
        @else
            <div class="row g-3 g-md-4 mb-5">
                @foreach ($categories as $cat)
                    <div class="col-6 col-sm-6 col-md-4 col-lg-3">
                        <a href="{{ route('illustrations', [
                            'id' => $brand->name,
                            'data' => $catalog->code,
                            'key1' => $parentCategory1->full_code,
                            'key2' => $parentCategory2->full_code,
                            'key3' => $cat->full_code,
                            'vin' => Session::get('vin')
                        ]) }}" class="text-decoration-none">
                            <div class="card border-0 shadow-sm h-100 hover-lift transition">
                                {{-- Image Container - Maintain Aspect Ratio --}}
                                <div class="position-relative overflow-hidden rounded-top" style="padding-top: 75%;">
                                    <img class="position-absolute top-0 start-0 w-100 h-100 object-fit-cover"
                                         src="{{ $cat->thumbnail ? Storage::url($cat->thumbnail) : asset('assets/images/no-image.png') }}"
                                         alt="{{ $cat->full_code }}"
                                         loading="lazy"
                                         onerror="this.onerror=null; this.src='{{ asset('assets/images/no-image.png') }}';">
                                </div>

                                {{-- Card Body - Responsive Text --}}
                                <div class="card-body p-2 p-md-3 text-center">
                                    <h6 class="product-title text-dark fw-bold text-uppercase mb-1 fs-6 fs-md-5">
                                        {{ $cat->full_code }}
                                    </h6>
                                    @if($cat->label_ar || $cat->label_en)
                                        <p class="text-muted small mb-0 d-none d-md-block">{{ $cat->label_ar ?? $cat->label_en }}</p>
                                    @endif
                                </div>
                            </div>
                        </a>
                    </div>
                @endforeach
            </div>
        @endif
    @else
        <div class="row">
            <div class="col-12">
                <div class="alert alert-info text-center">
                    <i class="fas fa-info-circle me-2"></i>
                    <h5 class="mb-2">{{ __('No categories available') }}</h5>
                    <p class="mb-0">{{ __('There are no categories in this level.') }}</p>
                </div>
            </div>
        </div>
    @endif
</div>

{{-- Responsive Utilities CSS --}}
@push('styles')
<style>
    .hover-lift {
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .hover-lift:hover {
        transform: translateY(-5px);
        box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.15) !important;
    }
    .object-fit-cover {
        object-fit: cover;
    }
    @media (max-width: 576px) {
        .breadcrumb-item + .breadcrumb-item::before {
            padding: 0 0.25rem;
        }
    }
</style>
@endpush
