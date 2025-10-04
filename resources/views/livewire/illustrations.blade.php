<div>
    @php use Illuminate\Support\Str; @endphp

    <style>
        /* ⚙️ الهيكل الأساسي للـ landmarks - لا تغيير */
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

        /* ⚙️ تنسيق العناصر داخل landmarks - يجب أن يكون position:absolute فقط */
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
            /* ❌ لا transform أو scale هنا - smoothZoom يتحكم في التحجيم */
        }

        /* ⚙️ تحسين القابلية للنقر دون تعارض مع smoothZoom */
        .callout-label,
        .correct-callout,
        .bbdover {
            cursor: pointer !important;
            -webkit-tap-highlight-color: rgba(0, 123, 255, 0.2);
        }

        .callout-label:hover .bbdover,
        .bbdover:hover {
            background-color: rgba(0, 123, 255, 0.3) !important;
            opacity: 1 !important;
        }

        .inner-card { height: 20px; background-color: #eee; }
        .card-1 { height: 200px; background-color: #eee; }
        .card-2 { height: 130px; }
        .h-screen { height: 100vh; }

        .animate-pulse { animation: pulse 2s cubic-bezier(.4, 0, .6, 1) infinite; }
        @keyframes pulse { 50% { opacity: .2; } }

        /* ⚙️ Mobile adjustments - لا transform على #zoom_container أو الآباء */
        @media (max-width: 768px) {
            /* ✅ تصغير أزرار التكبير/التصغير فقط - بدون تأثير على الحاوية */
            .smoothZoom_controls {
                transform: scale(0.6) !important;
                transform-origin: top right !important;
            }

            .smoothZoom_controls a {
                width: 24px !important;
                height: 24px !important;
                font-size: 16px !important;
                line-height: 24px !important;
            }

            /* ❌ إزالة أي transform/padding/border قد يؤثر على التموضع */
            .vehicle-search-wrapper,
            .container,
            main,
            body {
                transform: none !important;
                zoom: 1 !important;
            }

            /* ✅ حاوية محايدة بدون padding */
            #zoom_container {
                margin: 0 auto !important;
                padding: 0 !important;
                border: 0 !important;
                transform: none !important;
            }

            /* ✅ الصورة block بدون margins */
            #zoom_container img#image {
                display: block;
                margin: 0;
            }

            body { overscroll-behavior-y: contain; }

            /* تحسين القيم القابلة للضغط على الجوال */
            .callout-label {
                cursor: pointer !important;
                -webkit-tap-highlight-color: rgba(0, 123, 255, 0.3);
            }

            .bbdover {
                cursor: pointer !important;
                min-width: 40px !important;
                min-height: 40px !important;
            }

            /* ✅ إزالة padding من card-body حول الصورة على الموبايل */
            .card-body .products-view,
            .card-body .view-options__body {
                padding: 0 !important;
            }

            /* ✅ التأكد من عدم وجود margins على الحاويات الوسيطة */
            .products-view__options {
                margin: 0 !important;
            }
        }
    </style>

    <div class="container py-3">
        <div class="row">
            <div class="col-12">
                <div class="product-nav-wrapper mb-3">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb text-uppercase mb-0 flex-wrap">
                            {{-- Home --}}
                            <li class="breadcrumb-item">
                                <a class="text-black text-decoration-none" href="{{ route('front.index') }}">
                                    <i class="fas fa-home d-md-none"></i>
                                    <span class="d-none d-md-inline">Home</span>
                                </a>
                            </li>

                            {{-- Brand --}}
                            @if($brand)
                                <li class="breadcrumb-item">
                                    <a class="text-black text-decoration-none" href="{{ route('catlogs.index', ['id' => $brand->name]) }}">
                                        {{ strtoupper($brand->name) }}
                                    </a>
                                </li>
                            @endif

                            {{-- VIN --}}
                            @if(Session::get('vin'))
                                <li class="breadcrumb-item">
                                    <a class="text-black text-decoration-none" href="{{ route('tree.level1', [
                                        'id'   => $brand->name,
                                        'data' => $catalog->code,
                                        'vin'  => Session::get('vin')
                                    ]) }}">
                                        <i class="fas fa-car d-md-none"></i>
                                        <span class="d-none d-md-inline">{{ Session::get('vin') }}</span>
                                        <span class="d-md-none">VIN</span>
                                    </a>
                                </li>
                            @endif

                            {{-- Catalog --}}
                            @if($catalog)
                                <li class="breadcrumb-item d-none d-sm-block">
                                    <a class="text-black text-decoration-none" href="{{ route('tree.level1', [
                                        'id'   => $brand->name,
                                        'data' => $catalog->code
                                    ]) }}">
                                        {{ strtoupper($catalog->shortName ?? $catalog->name ?? $catalog->code) }}
                                    </a>
                                </li>
                            @endif

                            {{-- Level 1 --}}
                            @if($parentCategory1)
                                <li class="breadcrumb-item d-none d-md-block">
                                    <a class="text-black text-decoration-none" href="{{ route('tree.level2', [
                                        'id'   => $brand->name,
                                        'data' => $catalog->code,
                                        'key1' => $parentCategory1->full_code
                                    ]) }}">
                                        {{ strtoupper($parentCategory1->slug ?? $parentCategory1->full_code) }}
                                    </a>
                                </li>
                            @endif

                            {{-- Level 2 --}}
                            @if($parentCategory2 && $parentCategory1)
                                <li class="breadcrumb-item d-none d-lg-block">
                                    <a class="text-black text-decoration-none" href="{{ route('tree.level3', [
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
                                <li class="breadcrumb-item active text-primary" aria-current="page">
                                    <strong>{{ strtoupper($parentCategory3->Applicability ?? $parentCategory3->full_code) }}</strong>
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

    {{-- Illustration Image Container - Responsive --}}
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-12 col-md-10 col-lg-8">
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white border-0 py-3">
                        <h5 class="mb-0 text-center text-md-start">
                            <i class="fas fa-image me-2 d-none d-md-inline"></i>
                            {{ $category->localized_name }}
                        </h5>
                    </div>
                    <div class="card-body p-2 p-md-3">
                        <div class="products-view">
                            <div class="products-view__options view-options">
                                <div class="view-options__body">
                                    <div id="zoom_container">
                                        <img id="image"
                                             src="{{ Storage::url($category->images) }}"
                                             alt="{{ $category->localized_name }}" />
                                        <div class="landmarks" data-show-at-zoom="0" data-allow-drag="false"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

@push('scripts')
    {{-- ✅ الطريقة الجديدة المحسّنة: تمرير IDs فقط (150 bytes بدلاً من 7-12KB) --}}
    <script>
        window.catalogContext = {
            sectionId:   {{ $section->id ?? 'null' }},
            categoryId:  {{ $category->id ?? 'null' }},
            catalogCode: '{{ $catalog->code ?? '' }}',
            brandName:   '{{ optional($brand)->name ?? '' }}',
            parentKey1:  '{{ optional($parentCategory1)->full_code ?? '' }}',
            parentKey2:  '{{ optional($parentCategory2)->full_code ?? '' }}'
        };
        let csrf = "{{ csrf_token() }}";

        console.log('=== Illustration Context Loaded ===');
        console.log('  Section ID:', window.catalogContext.sectionId);
        console.log('  Category ID:', window.catalogContext.categoryId);
        console.log('  Catalog Code:', window.catalogContext.catalogCode);
        console.log('  Brand:', window.catalogContext.brandName);
    </script>

    {{-- سكربتات التكبير ثم سكربتنا --}}
    <script src="{{ asset('assets/front/js/jq-zoom.js') }}"></script>
    <script src="{{ asset('assets/front/js/preview.js') }}"></script>
    <script src="{{ asset('assets/front/js/ill/illustrated.js') }}?v={{ time() }}"></script>
@endpush

    <livewire:callout-modal />
</div>
