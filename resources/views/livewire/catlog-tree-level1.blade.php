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
                            'data' => $catalog,
                            'vin' => Session::get('vin')
                        ]) }}">
                            {{ Session::get('vin') }}
                        </a>
                    </li>
                @endif

                {{-- الكتالوج (مستوى حالي بدون رابط) --}}
                <li class="breadcrumb-item active text-primary text-uppercase" aria-current="page">
                    {{ strtoupper($catalog->shortName ?? $catalog->name ?? $catalog->code) }}
                </li>

            </ol>
        </nav>
    </div>
    {{-- <div class="row gy-4 gy-lg-5 mt-4">
        <livewire:search-box/>
        <div> --}}
            {{-- @dd(Session::get('vin')) --}}
        {{-- @dd(Session::get('preloaded_full_code')) --}}
       {{-- @dd(Session::get('model_begin_date')) --}}
       {{-- @dd(Session::get('model_end_date')) --}}
        {{-- @dd(Session::get('selected_filters')) --}}
            {{-- @dd(Session::get('selected_filters') ,Session::get('selected_filters_labeled')) --}}

    <div class="row gy-4 gy-lg-5 mt-4 mb-10">

        <livewire:vehicle-search-box :catalog="$catalog->code" :spec="Session::get('attributes')" />

        @foreach ($categories as $cat)
            <div class="col-12 col-sm-6 col-md-4 col-xl-3 text-center">
                <a href="{{ route('tree.level2', [
                    'id' => $brand->name,
                    'data' => $catalog->code,
                    'key1' => $cat->full_code,
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
                            <h6 class="product-title text-dark fw-bold text-uppercase">{{ $cat->full_code }}</h6>
                            @if($cat->label)
                                <p class="text-muted small">{{ $cat->label }}</p>
                            @endif
                        </div>
                    </div>
                </a>
            </div>
        @endforeach

    </div>
</div>
