<div class="vehicle-search-wrapper">
    <style>
        /* ===== Compact Modern Design ===== */
        .vehicle-search-wrapper {
            background: #fff;
            border-radius: 0.75rem;
            padding: 1rem;
            margin: 0.5rem auto;
            max-width: 1200px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.06);
            border: 1px solid #e9ecef;
        }

        @media (min-width: 768px) {
            .vehicle-search-wrapper {
                padding: 1.25rem 1.5rem;
                margin: 0.75rem auto;
            }
        }

        .vehicle-search-wrapper .segmented .btn {
            transition: all .2s ease;
            font-weight: 500;
        }
        .vehicle-search-wrapper .segmented .btn:active {
            transform: translateY(1px);
        }
        .vehicle-search-wrapper .segmented .btn:hover {
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
        }

        .vehicle-search-wrapper .btn-check:checked + .btn {
            background-color: #0d6efd;
            color: #fff;
            border-color: #0d6efd;
            box-shadow: 0 2px 6px rgba(13,110,253,0.25);
        }
        .vehicle-search-wrapper .btn-outline-secondary {
            background: #fff;
            border: 1px solid #dee2e6;
            color: #6c757d;
            font-size: 0.875rem;
        }
        .vehicle-search-wrapper .btn-outline-secondary:hover {
            background: #f8f9fa;
            border-color: #adb5bd;
        }
        .vehicle-search-wrapper .btn-sm {
            padding: 0.375rem 0.75rem;
            font-size: 0.875rem;
        }

        .vehicle-search-wrapper .input-group .input-group-text {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-right: 0;
        }

        .vehicle-search-wrapper .input-group .form-control {
            border: 1px solid #dee2e6;
            border-left: 0;
            border-right: 0;
        }
        .vehicle-search-wrapper .input-group .form-control:focus {
            border-color: #0d6efd;
            box-shadow: none;
        }

        .vehicle-search-wrapper .input-group .btn-primary {
            border-left: 1px solid #0d6efd;
        }

        /* Suggestions */
        .vehicle-search-wrapper .suggestions-dropdown {
            z-index: 1000;
            max-height: min(50vh, 320px);
            overflow: auto;
            overscroll-behavior: contain;
            border-top-left-radius: 0;
            border-top-right-radius: 0;
            border: 1.5px solid #0d6efd;
            border-top: none;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .vehicle-search-wrapper .suggestion-item {
            cursor: pointer;
            transition: all .2s ease;
            padding: 0.75rem 1rem !important;
        }
        .vehicle-search-wrapper .suggestion-item:hover {
            background: #e7f3ff;
            padding-left: 1.25rem !important;
        }

        /* ==== Vertical callout picker ==== */
        .vehicle-search-wrapper .callout-rail {
            display: flex;
            flex-direction: column;
            align-items: stretch;
            gap: 1rem;
            overflow-y: auto;
            max-height: clamp(320px, 60vh, 800px);
            padding: 0.5rem;
            scroll-snap-type: y proximity;
            overscroll-behavior: contain;
            scrollbar-width: thin;
            -webkit-overflow-scrolling: touch;
        }
        .vehicle-search-wrapper .callout-card {
            flex: 0 0 auto;
            width: 100%;
            border: 1.5px solid #e9ecef;
            border-radius: 0.875rem;
            background: #fff;
            box-shadow: 0 2px 6px rgba(0,0,0,.08);
            scroll-snap-align: start;
            transition: all .2s ease;
        }
        .vehicle-search-wrapper .callout-card:hover {
            border-color: #0d6efd;
            box-shadow: 0 4px 12px rgba(13,110,253,0.15);
            transform: translateY(-2px);
        }
        .vehicle-search-wrapper .callout-card .card-body {
            padding: 1rem 1.25rem;
        }
        .vehicle-search-wrapper .callout-card .meta {
            display: flex;
            align-items: center;
            gap: 0.625rem;
            flex-wrap: wrap;
        }
        .vehicle-search-wrapper .badge-soft {
            background: #f1f3f5;
            border: 1px solid #dee2e6;
            color: #495057;
            padding: 0.35rem 0.75rem;
            font-weight: 500;
        }
        .vehicle-search-wrapper .rail-nav {
            display: flex;
            justify-content: space-between;
            gap: 0.75rem;
            margin-top: 0.75rem;
        }
        .vehicle-search-wrapper .rail-nav .btn {
            min-width: 48px;
            border-radius: 0.5rem;
            transition: all .2s ease;
        }
        .vehicle-search-wrapper .rail-nav .btn:hover {
            background: #0d6efd;
            color: #fff;
            transform: translateY(-2px);
        }
        .text-truncate-3{
        display:-webkit-box;
        -webkit-line-clamp:3;
        -webkit-box-orient:vertical;
        overflow:hidden;
        }

        /* Sticky thead inside responsive table */
        .vehicle-search-wrapper .sticky-thead thead th{
            position: sticky; top: 0; background:#f8f9fa; z-index:2;
        }

        /* clamp helper for 2 lines on mobile */
        .text-truncate-2{
          display:-webkit-box;
          -webkit-line-clamp:2;
          -webkit-box-orient:vertical;
          overflow:hidden;
        }

        /* Compact & clean on phones */
        @media (max-width: 576px) {
            .vehicle-search-wrapper {
                padding: 0.85rem;
                margin: 0.35rem;
                border-radius: 0.65rem;
            }

            .vehicle-search-wrapper .btn-group-sm .btn {
                font-size: 0.8rem;
                padding: 0.35rem 0.5rem;
            }

            .vehicle-search-wrapper .input-group .form-control {
                font-size: 14px;
                padding: 0.5rem;
            }

            .vehicle-search-wrapper .input-group .input-group-text {
                font-size: 14px;
                padding: 0.5rem;
            }

            .vehicle-search-wrapper .input-group .btn {
                font-size: 14px;
                padding: 0.5rem 0.75rem;
            }

            .vehicle-search-wrapper #searchHelp {
                font-size: 0.75rem;
                margin-top: 0.35rem;
            }

            .vehicle-search-wrapper .callout-card .card-body {
                padding: 0.75rem 0.85rem;
            }

            .compact-search-controls .btn-group {
                flex-wrap: wrap;
            }
        }
    </style>

    {{-- ===== VIN Specs (from session) ===== --}}
    <style>
    .specs-bar {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border: none;
        border-radius: 0.75rem;
        padding: 0.75rem 1rem;
        box-shadow: 0 2px 8px rgba(102, 126, 234, 0.15);
    }
    .specs-bar strong {
        color: #fff;
        font-size: 0.875rem;
        letter-spacing: 0.3px;
    }
    .specs-bar .badge {
        background: rgba(255,255,255,0.25);
        color: #fff;
        border: 1px solid rgba(255,255,255,0.4);
        font-weight: 600;
        font-size: 0.75rem;
        padding: 0.25rem 0.5rem;
    }
    .specs-rail {
        display: flex;
        flex-wrap: nowrap;
        gap: 0.5rem;
        overflow: auto;
        overscroll-behavior: contain;
        scrollbar-width: thin;
        margin-top: 0.5rem;
    }
    .spec-chip {
        white-space: nowrap;
        border: 1px solid rgba(255,255,255,0.3);
        background: rgba(255,255,255,0.95);
        border-radius: 1.5rem;
        padding: 0.35rem 0.75rem;
        display: flex;
        align-items: center;
        gap: 0.4rem;
        font-size: 0.8rem;
        box-shadow: 0 1px 4px rgba(0,0,0,0.08);
        transition: all .2s ease;
    }
    .spec-chip:hover {
        transform: translateY(-1px);
        box-shadow: 0 2px 8px rgba(0,0,0,0.12);
    }
    .spec-chip .k {
        color: #6c757d;
        font-weight: 500;
        font-size: 0.75rem;
    }
    .spec-chip .v {
        font-weight: 600;
        color: #212529;
        font-size: 0.8rem;
    }
    .spec-chip .src {
        font-size: 0.65rem;
        background: #667eea;
        color: #fff;
        border-radius: 0.75rem;
        padding: 0.1rem 0.4rem;
        font-weight: 600;
    }
    @media (max-width:576px) {
        .spec-chip {
            font-size: 0.75rem;
            padding: 0.3rem 0.6rem;
        }
        .specs-bar {
            padding: 0.65rem 0.85rem;
            border-radius: 0.65rem;
        }
    }
    </style>

    @php
    use Illuminate\Support\Str;

    $filters = session('selected_filters', []);

    if (isset($filters['year']['value_id']) && isset($filters['month']['value_id'])) {
        $yyyy = (string) $filters['year']['value_id'];
        $mm   = str_pad((string) $filters['month']['value_id'], 2, '0', STR_PAD_LEFT);
        $filters = [
            'BUILD_DATE' => [
            'value_id' => "{$yyyy}-{$mm}",
            'source'   => $filters['year']['source'] ?? ($filters['month']['source'] ?? 'vin'),
            ],
        ] + collect($filters)->except(['year','month'])->all();
    }

    $pretty = [
        'BODY'         => __('ui.body'),
        'ENGINE'       => __('ui.engine'),
        'GRADE'        => __('ui.grade'),
        'TRANS'        => __('ui.transmission'),
        'TRIM_COLOUR'  => __('ui.trim_colour'),
        'BODY_COLOR'   => __('ui.body_color'),
        'DRIVE'        => __('ui.drive'),
        'DESTINATION'  => __('ui.destination'),
        'BUILD_DATE'   => __('ui.build_date'),
    ];

    $chips = collect($filters)->map(function($meta, $key) use ($pretty){
        $label = $pretty[$key] ?? Str::title(str_replace('_', ' ', Str::lower($key)));
        $value = is_array($meta) ? ($meta['value_id'] ?? $meta['value'] ?? '—') : (string) $meta;
        $src   = is_array($meta) ? ($meta['source'] ?? null) : null;
        return ['k' => $label, 'v' => $value, 'src' => $src];
    })->values();

    $tooShort = ($searchType === 'number' && strlen(preg_replace('/[^0-9A-Za-z]+/', '', $query)) < 5)
             || ($searchType === 'label'  && mb_strlen($query, 'UTF-8') < 2);
    @endphp

    @if($chips->isNotEmpty())
    <div class="mb-2">
        <div class="specs-bar">
        <div class="d-flex align-items-center mb-2" style="gap:.5rem">
            <strong class="text-muted">{{ __('ui.vin_specs') }}</strong>
            <span class="badge bg-light text-dark border">{{ $chips->count() }}</span>
        </div>

        <div class="specs-rail" role="list" aria-label="{{ __('ui.vin_specs') }}">
            @foreach($chips as $chip)
            <span class="spec-chip" role="listitem">
                <span class="k">{{ $chip['k'] }}:</span>
                <span class="v">{{ $chip['v'] }}</span>
                @if(!empty($chip['src']))
                <span class="src">{{ strtoupper($chip['src']) }}</span>
                @endif
            </span>
            @endforeach
        </div>
        </div>
    </div>
    @endif


    {{-- Attributes Section --}}
    <div class="mb-4">
        <livewire:attributes :catalog="$catalog" />
    </div>

    <script src="{{ asset('js/vehicle-search-optimizations.js') }}"></script>

    {{-- تنبيه الخطأ – وصولية أفضل --}}
    @if($errorMessage)
        <div id="vehicleSearchErrorAlert"
             class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-3"
             role="alert"
             aria-live="assertive"
             aria-atomic="true">
            <div class="d-flex align-items-center">
                <i class="fas fa-exclamation-triangle me-3 fs-5"></i>
                <div class="flex-grow-1">{{ __($errorMessage) }}</div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="{{ __('ui.close') }}"></button>
        </div>
    @endif

    {{-- تلميح اختياري عند انعدام النتائج في Section فقط --}}
    @if($errorMessage && $searchScope === 'section' && str_contains(__($errorMessage), __(\App\Livewire\VehicleSearchBox::ERR_NO_CALLOUT)))
        <div class="alert alert-warning border-0 shadow-sm py-3 mb-3" role="status" aria-live="polite">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div class="d-flex align-items-center">
                    <i class="fas fa-lightbulb me-2 fs-5"></i>
                    <span>{{ __('ui.scope_hint_try_catalog') }}</span>
                </div>
                <button type="button" class="btn btn-sm btn-primary" wire:click="setSearchScope('catalog')">
                    <i class="fas fa-search me-1"></i>
                    {{ __('ui.catalog_all') }}
                </button>
            </div>
        </div>
    @endif

    {{-- Compact Search Controls --}}
    <div class="compact-search-controls mb-3">
        <div class="d-flex align-items-center justify-content-between gap-2 flex-wrap">
            {{-- Search Scope - Compact --}}
            <div class="btn-group btn-group-sm" role="group" aria-label="{{ __('ui.search_scope') }}">
                <button type="button"
                        class="btn btn-sm {{ $searchScope === 'catalog' ? 'btn-primary' : 'btn-outline-secondary' }}"
                        wire:click="setSearchScope('catalog')"
                        title="{{ __('ui.catalog_all') }}">
                    <i class="fas fa-globe"></i>
                    <span class="d-none d-md-inline ms-1">{{ __('ui.catalog_all') }}</span>
                </button>
                <button type="button"
                        class="btn btn-sm {{ $searchScope === 'section' ? 'btn-primary' : 'btn-outline-secondary' }}"
                        wire:click="setSearchScope('section')"
                        title="{{ __('ui.this_section') }}">
                    <i class="fas fa-layer-group"></i>
                    <span class="d-none d-md-inline ms-1">{{ __('ui.this_section') }}</span>
                </button>
            </div>

            {{-- Search Type - Compact --}}
            <div class="btn-group btn-group-sm" role="group" aria-label="{{ __('ui.search_type') }}">
                @foreach ([
                    'number' => ['icon' => 'fa-hashtag', 'label' => __('ui.part_number')],
                    'label'  => ['icon' => 'fa-tag', 'label' => __('ui.part_name')]
                ] as $type => $config)
                    <input
                        type="radio"
                        class="btn-check"
                        name="searchType"
                        id="searchBy{{ ucfirst($type) }}"
                        autocomplete="off"
                        value="{{ $type }}"
                        wire:model="searchType"
                    >
                    <label class="btn btn-sm btn-outline-secondary"
                           for="searchBy{{ ucfirst($type) }}"
                           title="{{ $config['label'] }}">
                        <i class="fas {{ $config['icon'] }}"></i>
                        <span class="d-none d-md-inline ms-1">{{ $config['label'] }}</span>
                    </label>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Compact Search Input --}}
    <div class="mb-3 position-relative">
        <div class="input-group shadow-sm">
            <span class="input-group-text bg-white" aria-hidden="true">
                <i class="fas {{ $searchType === 'number' ? 'fa-hashtag' : 'fa-tag' }} text-muted"></i>
            </span>

            <input
                id="searchInput"
                type="text"
                class="form-control"
                placeholder="{{ $searchType === 'number' ? __('ui.enter_part_number') : __('ui.enter_part_name') }}"
                wire:model.live.debounce.500ms="query"
                wire:keydown.enter="searchFromInput"
                wire:loading.attr="disabled"
                wire:target="searchFromInput"
                autocomplete="off"
                autocapitalize="off"
                autocorrect="off"
                spellcheck="false"
                enterkeyhint="search"
                dir="ltr"
                aria-describedby="searchHelp"
            >

            <button
                class="btn btn-primary"
                type="button"
                wire:click="searchFromInput"
                wire:loading.attr="disabled"
                wire:target="searchFromInput"
                aria-label="{{ __('ui.search') }}"
                @if($searchType === 'number'
                        ? strlen(preg_replace('/[^0-9A-Za-z]+/', '', $query)) < 5
                        : mb_strlen($query, 'UTF-8') < 2) disabled @endif
            >
                <span class="spinner-border spinner-border-sm"
                    role="status"
                    aria-hidden="true"
                    wire:loading
                    wire:target="searchFromInput"></span>
                <i class="fas fa-search" wire:loading.remove wire:target="searchFromInput"></i>
                <span class="d-none d-lg-inline ms-2" wire:loading.remove wire:target="searchFromInput">
                    {{ __('ui.search') }}
                </span>
            </button>
        </div>

        <small id="searchHelp" class="form-text {{ $tooShort ? 'text-danger' : 'text-muted' }}">
            <i class="fas fa-info-circle"></i>
            {{ $searchType === 'number' ? __('ui.part_number_help') : __('ui.part_name_help') }}
        </small>

        {{-- Suggestions Dropdown --}}
        @if($searchType === 'label' && !empty($results) && is_array($results) && !$isLoading && mb_strlen($query, 'UTF-8') >= 2 && isset($results[0]) && is_string($results[0]))
            <div id="suggestionsDropdown"
                 class="position-absolute w-100 bg-white suggestions-dropdown"
                 role="listbox"
                 style="top: 100%; left: 0; z-index: 1050;">
                @foreach($results as $index => $suggestion)
                    <div class="suggestion-item border-bottom"
                         onclick="selectSuggestion('{{ is_string($suggestion) ? addslashes($suggestion) : '' }}')"
                         role="option">
                        <i class="fas fa-search text-primary me-2"></i>
                        <span>{{ is_string($suggestion) ? $suggestion : '' }}</span>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    {{-- Loading State --}}
    @if ($isLoading)
        <div class="alert alert-info border-0 shadow-sm mb-4 d-flex align-items-center gap-3" role="status" aria-live="polite">
            <div class="spinner-border text-primary" role="status" aria-hidden="true"></div>
            <div class="flex-grow-1">
                <div class="fw-semibold">
                    {{ $searchType === 'number' ? __('ui.searching_by_number') : ($searchType === 'label' ? __('ui.searching_by_name') : __('ui.loading_callouts')) }}
                </div>
                <div class="small text-muted">
                    <strong>{{ e($selectedItem) }}</strong>
                </div>
            </div>
        </div>
    @endif

    {{-- Callout Picker Modal --}}
    @if($showCalloutPicker && !empty($calloutOptions))
        <div class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.5); z-index:1050; backdrop-filter: blur(4px);">
            <div class="modal-dialog modal-xl modal-dialog-centered modal-fullscreen-sm-down">
                <div class="modal-content border-0 shadow-lg">
                    <div class="modal-header bg-primary bg-gradient text-white border-0 py-3">
                        <h5 class="modal-title fw-semibold d-flex align-items-center gap-2 mb-0">
                            <i class="fas fa-list-ul"></i>
                            {{ __('ui.select_matching_callout') }}
                            <span class="badge bg-white text-primary ms-2">{{ count($calloutOptions) }}</span>
                        </h5>
                        <button type="button" class="btn-close btn-close-white" wire:click="$set('showCalloutPicker', false)" aria-label="{{ __('ui.close') }}"></button>
                    </div>

                    <div class="modal-body p-3 p-md-4 bg-light">
                                <div id="calloutRail"
                                    class="callout-rail"
                                    role="listbox"
                                    aria-orientation="vertical"
                                    aria-label="{{ __('ui.select_matching_callout') }}">
                                    @foreach($calloutOptions as $opt)
                                        @php
                                            $fromYear = !empty($opt['cat_begin']) ? \Illuminate\Support\Carbon::parse($opt['cat_begin'])->year : null;
                                            $toYear   = !empty($opt['cat_end'])   ? \Illuminate\Support\Carbon::parse($opt['cat_end'])->year   : null;
                                            $canOpen  = !empty($opt['key1']) && !empty($opt['key2']) && !empty($opt['key3']);
                                        @endphp
                                        <article class="card callout-card" role="option" aria-selected="false">
                                            <div class="card-body">
                                                <div class="d-flex justify-content-between align-items-start mb-2">
                                                    <div class="d-flex align-items-center gap-2">
                                                        <span class="badge bg-primary bg-gradient px-3 py-2 fs-6">
                                                            <i class="fas fa-tag me-1"></i>
                                                            {{ $opt['callout'] }}
                                                        </span>
                                                        <span class="badge bg-secondary bg-gradient px-2 py-1">
                                                            {{ __('ui.qty') }}: {{ $opt['qty'] ?? '—' }}
                                                        </span>
                                                    </div>
                                                </div>

                                                <h6 class="fw-semibold mb-2 text-truncate-2 text-dark">
                                                    {{ localizedPartLabel($opt['label_en'] ?? null, $opt['label_ar'] ?? null) }}
                                                </h6>

                                                <div class="meta small mb-2">
                                                    <span class="badge badge-soft">
                                                        <i class="fas fa-calendar-alt me-1"></i>
                                                        {{ formatYearRange($fromYear, $toYear) }}
                                                    </span>
                                                    <span class="badge badge-soft">
                                                        <i class="fas fa-code me-1"></i>
                                                        {{ $opt['key2'] ?? '—' }}
                                                    </span>
                                                </div>

                                                <div class="text-muted small text-truncate-3 mb-3 fst-italic">
                                                    {{ $opt['applicability'] ?? '—' }}
                                                </div>

                                                <div class="d-grid">
                                                    @if($canOpen)
                                                        <a href="{{ route('illustrations', [
                                                            'id'            => $catalog->brand->name,
                                                            'data'          => $catalog->code,
                                                            'key1'          => $opt['key1'],
                                                            'key2'          => $opt['key2'],
                                                            'key3'          => $opt['key3'],
                                                            'vin'           => Session::get('vin'),
                                                            'callout'       => $opt['callout'],
                                                            'auto_open'     => 1,
                                                            'section_id'    => $opt['section_id'],
                                                            'category_code' => $opt['category_code'],
                                                            'catalog_code'  => $catalog->code,
                                                            'category_id'   => $opt['category_id'] ?? null,
                                                        ]) }}" class="btn btn-primary btn-sm shadow-sm">
                                                            <i class="fas fa-arrow-right me-2"></i>
                                                            {{ __('ui.open') }}
                                                        </a>
                                                    @else
                                                        <button class="btn btn-outline-secondary btn-sm" disabled>
                                                            <i class="fas fa-lock me-2"></i>
                                                            {{ __('ui.open') }}
                                                        </button>
                                                    @endif
                                                </div>
                                            </div>
                                        </article>
                                    @endforeach
                                </div>

                                <div class="rail-nav mt-3">
                                    <button class="btn btn-outline-primary btn-sm shadow-sm" type="button" onclick="scrollRail(-1)" aria-label="{{ __('ui.prev') }}">
                                        <i class="fas fa-chevron-up me-1"></i>
                                        <span class="d-none d-md-inline">{{ __('ui.prev') }}</span>
                                    </button>
                                    <button class="btn btn-outline-primary btn-sm shadow-sm" type="button" onclick="scrollRail(1)" aria-label="{{ __('ui.next') }}">
                                        <span class="d-none d-md-inline">{{ __('ui.next') }}</span>
                                        <i class="fas fa-chevron-down ms-1"></i>
                                    </button>
                                </div>

                                <div class="alert alert-info border-0 shadow-sm mt-3 mb-0" role="status" aria-live="polite">
                                    <div class="d-flex align-items-center gap-2">
                                        <i class="fas fa-info-circle fs-5"></i>
                                        <div>{{ __('ui.go_to_part_page_hint') }}</div>
                                    </div>
                                </div>
                            </div>

                            <div class="modal-footer bg-white border-0 py-3">
                                <button class="btn btn-outline-secondary px-4" wire:click="$set('showCalloutPicker', false)">
                                    <i class="fas fa-times me-2"></i>
                                    {{ __('ui.close') }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
    @endif

    {{-- ✅ JavaScript داخل الـ div الجذري لتجنب تحذير Multiple root elements --}}
    <script>
    function selectSuggestion(suggestion) {
    const input = document.getElementById('searchInput');
    input.value = suggestion;
    const dd = document.getElementById('suggestionsDropdown');
    if (dd) dd.style.display = 'none';
    @this.set('query', suggestion);
    @this.call('searchFromInput');
}

const inputEl = document.getElementById('searchInput');
if (inputEl) {
  inputEl.addEventListener('focus', function() {
    const dd = document.getElementById('suggestionsDropdown');
    if (dd) dd.style.display = 'block';
  });
}

// Hide suggestions when clicking outside
document.addEventListener('click', function(event) {
    const input = document.getElementById('searchInput');
    const dd = document.getElementById('suggestionsDropdown');
    if (!dd) return;
    if (!input.contains(event.target) && !dd.contains(event.target)) {
        dd.style.display = 'none';
    }
});

// Show suggestions on input focus (if available)
document.getElementById('searchInput').addEventListener('focus', function() {
    const dd = document.getElementById('suggestionsDropdown');
    if (dd) dd.style.display = 'block';
});

// Livewire redirect (single callout)
document.addEventListener('livewire:load', () => {
    Livewire.on('single-callout-ready', () => {
        const url = @this.get('singleRedirectUrl');
        if (url) window.location.href = url;
    });
});

function scrollRail(dir){
    const rail = document.getElementById('calloutRail');
    if (!rail) return;
    const card = rail.querySelector('.callout-card');
    const step = card ? (card.offsetHeight + 12) : 320;
    const delta = dir * step;
    rail.scrollBy({ top: delta, behavior: 'smooth' });
}

// اختصار لوحة مفاتيح: الأسهم للتنقل عموديًا عندما تكون المودال مفتوحة
document.addEventListener('keydown', function(e){
    const modalOpen = document.querySelector('.modal.show');
    if (!modalOpen) return;
    if (e.key === 'ArrowUp')   { e.preventDefault(); scrollRail(-1); }
    if (e.key === 'ArrowDown') { e.preventDefault(); scrollRail(1); }
    // دعم إضافي لليسار/اليمين إن استُخدمت
    if (e.key === 'ArrowLeft')  { e.preventDefault(); scrollRail(-1); }
    if (e.key === 'ArrowRight') { e.preventDefault(); scrollRail(1); }
});

// ESC to close suggestions
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const dd = document.getElementById('suggestionsDropdown');
        if (dd) dd.style.display = 'none';
    }
});

// Optional: open callout from search
document.addEventListener('open-callout-from-search', function(e) {
    if (window.openCallout && typeof window.openCallout === 'function') {
        window.openCallout(e.detail.part);
    }
});

// تركيز تلقائي على تنبيه الخطأ عند تحديثه لسهولة الوصول
window.addEventListener('vehicle-search-error', (e) => {
    const alertEl = document.getElementById('vehicleSearchErrorAlert');
    if (alertEl) {
        alertEl.setAttribute('tabindex','-1');
        alertEl.focus({preventScroll: false});
        alertEl.scrollIntoView({behavior:'smooth', block:'center'});
    }
    });
    </script>
</div>
{{-- ✅ إغلاق الـ div الجذري هنا - كل المحتوى داخل div واحد --}}
