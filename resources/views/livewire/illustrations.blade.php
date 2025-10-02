<div>
    @php use Illuminate\Support\Str; @endphp

    <style>
        #zoom_container .landmarks {
            position: absolute;
            z-index: 10;
            top: 0;
            left: 0;
        }

        #zoom_container .landmarks .item {
            position: absolute;
            text-align: center;
            display: none;
        }

        .hovered {
            border: 2px solid rgb(219, 16, 16) !important;
            background-color: #bce8f1 !important;
        }

        div[id*='zoom_container'] .landmarks .lable div {
            z-index: 19999;
            text-align: center;
            vertical-align: middle;
            border: 2px solid blue;
            background-color: transparent;
            display: table-cell;
            cursor: pointer;
            padding-left: 4px !important;
            padding-right: 4px !important;
            position: absolute;
            border-radius: 999px;
            font: bold 15px tahoma, arial, verdana, sans-serif;
        }

        .inner-card { height: 20px; background-color: #eee; }
        .card-1 { height: 200px; background-color: #eee; }
        .card-2 { height: 130px; }
        .h-screen { height: 100vh; }

        .animate-pulse { animation: pulse 2s cubic-bezier(.4, 0, .6, 1) infinite; }
        @keyframes pulse { 50% { opacity: .2; } }

        /* Mobile polish */
        @media (max-width: 768px) {
            .smoothZoom_controls { display: none !important; }
            body { overscroll-behavior-y: contain; }
            #zoom_container img { touch-action: pan-x pan-y; max-width: 100%; height: auto; }
        }
    </style>

    <div class="container">
        <div class="row">
            <div class="col-12">
                <div class="product-nav-wrapper">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb text-uppercase">
                            {{-- Home --}}
                            <li class="breadcrumb-item">
                                <a class="text-black" href="{{ route('front.index') }}">Home</a>
                            </li>

                            {{-- Brand --}}
                            @if($brand)
                                <li class="breadcrumb-item">
                                    <a class="text-black" href="{{ route('catlogs.index', ['id' => $brand->name]) }}">
                                        {{ strtoupper($brand->name) }}
                                    </a>
                                </li>
                            @endif

                            {{-- VIN --}}
                            @if(Session::get('vin'))
                                <li class="breadcrumb-item">
                                    <a class="text-black" href="{{ route('tree.level1', [
                                        'id'   => $brand->name,
                                        'data' => $catalog->code,
                                        'vin'  => Session::get('vin')
                                    ]) }}">
                                        {{ Session::get('vin') }}
                                    </a>
                                </li>
                            @endif

                            {{-- Catalog --}}
                            @if($catalog)
                                <li class="breadcrumb-item">
                                    <a class="text-black" href="{{ route('tree.level1', [
                                        'id'   => $brand->name,
                                        'data' => $catalog->code
                                    ]) }}">
                                        {{ strtoupper($catalog->shortName ?? $catalog->name ?? $catalog->code) }}
                                    </a>
                                </li>
                            @endif

                            {{-- Level 1 --}}
                            @if($parentCategory1)
                                <li class="breadcrumb-item">
                                    <a class="text-black" href="{{ route('tree.level2', [
                                        'id'   => $brand->name,
                                        'data' => $catalog->code,
                                        'key1' => $parentCategory1->full_code
                                    ]) }}">
                                        {{ strtoupper($parentCategory1->slug ?? $parentCategory1->full_code) }}
                                    </a>
                                </li>
                            @endif

                            {{-- Level 2 --}}
                            @if($parentCategory2)
                                <li class="breadcrumb-item">
                                    <a class="text-black" href="{{ route('tree.level3', [
                                        'id'   => $brand->name,
                                        'data' => $catalog->code,
                                        'key1' => $parentCategory1->full_code,
                                        'key2' => $parentCategory2->full_code
                                    ]) }}">
                                        {{ strtoupper($parentCategory2->slug ?? $parentCategory2->full_code) }}
                                    </a>
                                </li>
                            @endif

                            {{-- Level 3 (current) --}}
                            @if($parentCategory3)
                                <li class="breadcrumb-item active text-primary text-uppercase" aria-current="page">
                                    {{ strtoupper($parentCategory3->Applicability ?? $parentCategory3->full_code) }}
                                </li>
                            @endif
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>

    {{-- Search box (tقييد السيكشن الحالي) --}}
    <livewire:vehicle-search-box
        :catalog="$catalog->code"
        :allowed-codes-override="[$category->full_code]" />

    <div class="container m-md-2 d-flex justify-content-center">
        <div class="col-md-8 bg-white panel-body text-center">
            <div class="products-view">
                <div class="products-view__options view-options view-options--offcanvas--mobile">
                    <div class="view-options2 xview-options__body--filters">
                        <div class="view-options__label" style="top:-10px">{{ $category->localized_name }}</div>
                        <div id="zoom_container">
                            <img id="image"
                                 src="{{ Storage::url($category->images) }}"
                                 alt="{{ $category->localized_name }}"
                                 width="100%" height="100%" />
                            <div class="landmarks" data-show-at-zoom="0" data-allow-drag="false"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

@push('scripts')
    {{-- مرّر الداتا للواجهة --}}
    <script>
        window.sectionData    = @json($section);
        window.categoryData   = @json($category->loadMissing('catalog')); // يضمن وجود catalog->code
        window.calloutsFromDB = @json($callouts);
        window.brandName      = @json(optional($brand)->name);
        const callouts = window.calloutsFromDB ?? [];
        let csrf = "{{ csrf_token() }}";
        // dd() // للتحقق لاحقًا:
        // console.log({section: window.sectionData, category: window.categoryData, brand: window.brandName, calloutsCount: (callouts||[]).length});
    </script>

    {{-- جهّز نسخة آمنة من رقم الإصدار للسكربت --}}
    @php
        $jsRel  = 'assets/front/js/ill/illustrated.js';
        $jsPath = public_path($jsRel);
        $jsVer  = file_exists($jsPath) ? filemtime($jsPath) : time();
    @endphp

    {{-- سكربتات التكبير ثم سكربتنا مع ?v= --}}
    <script src="{{ asset('assets/front/js/jq-zoom.js') }}"></script>
    <script src="{{ asset('assets/front/js/preview.js') }}"></script>
    <script src="{{ asset($jsRel) }}?v={{ $jsVer }}"></script>
@endpush

    <livewire:callout-modal />
</div>
