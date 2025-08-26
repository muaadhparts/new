<div class="container">
    <div class="product-nav-wrapper">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb text-uppercase">
                {{-- Home --}}
                <li class="breadcrumb-item">
                    <a class="text-black" href="{{ route('front.index') }}">{{ __('Home') }}</a>
                </li>

                {{-- Brand --}}
                <li class="breadcrumb-item">
                    <a class="text-black" href="{{ route('catlogs.index', $brand->name) }}">
                        {{ strtoupper($brand->name) }}
                    </a>
                </li>

                {{-- VIN --}}
                @if(Session::get('vin'))
                    <li class="breadcrumb-item">
                        <a class="text-black" href="{{ route('tree.level1', [
                            'id' => $brand->name,
                            'data' => $catalog->code,
                            'vin' => Session::get('vin')
                        ]) }}">
                            {{ Session::get('vin') }}
                        </a>
                    </li>
                @endif

                {{-- Catalog --}}
                <li class="breadcrumb-item">
                    <a class="text-black" href="{{ route('tree.level1', [
                        'id' => $brand->name,
                        'data' => $catalog->code
                    ]) }}">
                        {{ strtoupper($catalog->shortName ?? $catalog->name ?? $catalog->code) }}
                    </a>
                </li>

                {{-- المستوى الأول --}}
                <li class="breadcrumb-item">
                    <a class="text-black" href="{{ route('tree.level2', [
                        'id' => $brand->name,
                        'data' => $catalog->code,
                        'key1' => $parentCategory1->full_code
                    ]) }}">
                        {{ strtoupper($parentCategory1->slug ?? $parentCategory1->full_code) }}
                    </a>
                </li>

                {{-- المستوى الثاني --}}
                <li class="breadcrumb-item active text-primary text-uppercase" aria-current="page">
                    {{ strtoupper($parentCategory2->slug ?? $parentCategory2->full_code) }}
                </li>
            </ol>
        </nav>
    </div>

    <div class="row gy-4 gy-lg-5 mt-4 mb-10">
        @php
            // Prepare allowed codes
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

        {{-- Categories --}}
        @if($categories && $categories->count() > 0)
            @if($categories->count() === 1)
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
                <script>
                    window.location.href = "{{ $redirectUrl }}";
                </script>
            @else
                @foreach ($categories as $cat)
                    <div class="col-12 col-sm-6 col-md-4 col-xl-3 text-center">
                        <a href="{{ route('illustrations', [
                            'id' => $brand->name,
                            'data' => $catalog->code,
                            'key1' => $parentCategory1->full_code,
                            'key2' => $parentCategory2->full_code,
                            'key3' => $cat->full_code,
                            'vin' => Session::get('vin')
                        ]) }}">
                            <div class="single-product card border-0 shadow-sm h-100">
                                <div class="img-wrapper position-relative">
                                    <img class="img-fluid rounded"
                                        src="{{ $cat->thumbnail ? Storage::url($cat->thumbnail) : asset('assets/images/no-image.png') }}"
                                        alt="{{ $cat->full_code }}"
                                        onerror="this.onerror=null; this.src='{{ asset('assets/images/no-image.png') }}';">
                                </div>
                                <div class="p-3 text-center">
                                    <h6 class="product-title text-dark fw-bold text-center text-uppercase">
                                        {{ $cat->full_code }}
                                    </h6>
                                    @if($cat->label_ar || $cat->label_en)
                                        <p class="text-muted small">{{ $cat->label_ar ?? $cat->label_en }}</p>
                                    @endif
                                </div>
                            </div>
                        </a>
                    </div>
                @endforeach
            @endif
        @else
            <div class="col-12">
                <div class="alert alert-info text-center">
                    <h5>{{ __('No categories available') }}</h5>
                    <p>{{ __('There are no categories in this level.') }}</p>
                </div>
            </div>
        @endif
    </div>
</div>
