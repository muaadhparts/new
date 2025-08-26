<div class="container">
    <div class="product-nav-wrapper">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb text-uppercase">
                {{-- Home --}}
                <li class="breadcrumb-item">
                    <a class="text-black" href="{{ route('front.index') }}">Home</a>
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

                {{-- المستوى الحالي --}}
                <li class="breadcrumb-item active text-primary text-uppercase" aria-current="page">
                    {{ strtoupper($category->slug ?? $category->full_code) }}
                </li>
            </ol>
        </nav>
    </div>

    <div class="row gy-4 gy-lg-5 mt-4 mb-10">
        {{-- نمرر الأكواد المحسوبة مسبقاً من الكلاس CatlogTreeLevel2 إلى الكمبوننت للبحث بوضع Section --}}
        <livewire:vehicle-search-box :catalog="$catalog->code" :allowed-codes-override="$allowedCodes" />

        @php
            $categories = collect($categories)->sortBy(function($c) {
                // نتأكد إننا نشتغل مع Object أو Array
                $code = is_array($c) ? ($c['full_code'] ?? '') : ($c->full_code ?? '');

                // نلتقط أول سلسلة أرقام من الكود
                if (preg_match('/\d+/', $code, $m)) {
                    return (int) $m[0];
                }

                // لو ما فيه أرقام نخليها في آخر الترتيب
                return PHP_INT_MAX;
            })->values();
        @endphp

        @foreach ($categories as $cat)
            <div class="col-12 col-sm-6 col-md-4 col-xl-3 text-center">
                <a href="{{ route('tree.level3', [
                    'id' => $brand->name,
                    'data' => $catalog->code,
                    'key1' => $category->full_code,
                    'key2' => $cat->full_code,
                    'vin' => Session::get('vin') // اختياري
                ]) }}">
                    <div class="single-product card border-0 shadow-sm h-100">
                        <div class="img-wrapper position-relative">
                            <img class="img-fluid rounded"
                                 src="{{ $cat->thumbnail ? Storage::url($cat->thumbnail) : asset('assets/images/no-image.png') }}"
                                 alt="{{ $cat->full_code }}"
                                 onerror="this.onerror=null; this.src='{{ asset('assets/images/no-image.png') }}';">
                        </div>
                        <div class="p-3 text-center">
                            <h6 class="product-title text-dark fw-bold text-uppercase">{{ $cat->full_code }}</h6>
                            <p class="text-muted small">{{ $cat->label_ar ?? $cat->label_en }}</p>
                        </div>
                    </div>
                </a>
            </div>
        @endforeach
    </div>
</div>
