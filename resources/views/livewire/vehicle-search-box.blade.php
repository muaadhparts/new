<div class="container py-4 vehicle-search-wrapper"> 
    <style>
        /* ===== Mobile-first polish (scoped) ===== */
        .vehicle-search-wrapper .segmented .btn { transition: transform .06s ease, box-shadow .12s ease; }
        .vehicle-search-wrapper .segmented .btn:active { transform: translateY(1px); }

        .vehicle-search-wrapper .btn-check:checked + .btn {
            background-color: #0d6efd;
            color: #fff;
            border-color: #0d6efd;
        }
        .vehicle-search-wrapper .btn-outline-primary { background: #f8f9ff; }

        .vehicle-search-wrapper .input-group-lg .input-group-text {
            min-width: 48px;
            display: flex; align-items: center; justify-content: center;
        }

        /* Suggestions */
        .vehicle-search-wrapper .suggestions-dropdown{
            z-index:1000;
            max-height: min(50vh, 320px);
            overflow: auto;
            overscroll-behavior: contain;
            border-top-left-radius: 0;
            border-top-right-radius: 0;
        }
        .vehicle-search-wrapper .suggestion-item{ cursor:pointer; transition:background-color .15s ease; }
        .vehicle-search-wrapper .suggestion-item:hover{ background:#f8f9fa; }

        /* ==== Vertical callout picker ==== */
        .vehicle-search-wrapper .callout-rail{
            display:flex;
            flex-direction: column;
            align-items: stretch;
            gap:12px;
            overflow-y:auto;
            max-height: clamp(320px, 60vh, 800px);
            padding:.25rem;
            scroll-snap-type:y proximity;
            overscroll-behavior:contain;
            scrollbar-width:thin;
            -webkit-overflow-scrolling: touch;
        }
        .vehicle-search-wrapper .callout-card{
            flex:0 0 auto;
            width:100%;
            border:1px solid #e9ecef;
            border-radius:.75rem;
            background:#fff;
            box-shadow:0 1px 2px rgba(0,0,0,.05);
            scroll-snap-align:start;
        }
        .vehicle-search-wrapper .callout-card .card-body{
            padding:.75rem .9rem;
        }
        .vehicle-search-wrapper .callout-card .meta{
            display:flex;align-items:center;gap:.5rem;flex-wrap:wrap;
        }
        .vehicle-search-wrapper .badge-soft{
            background:#f8f9fa;border:1px solid #e9ecef;color:#495057;
        }
        .vehicle-search-wrapper .rail-nav{
            display:flex;justify-content:space-between;gap:.5rem;margin-top:.25rem;
        }
        .vehicle-search-wrapper .rail-nav .btn{
            min-width:40px;
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
        @media (max-width: 576px){
            .vehicle-search-wrapper .segmented{
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 6px;
            }
            .vehicle-search-wrapper .segmented .btn{
                border-radius: .75rem !important;
            }
            .vehicle-search-wrapper .input-group-lg .form-control,
            .vehicle-search-wrapper .input-group-lg .input-group-text,
            .vehicle-search-wrapper .input-group-lg .btn{
                height: 44px;
                font-size: 16px;
            }
            .vehicle-search-wrapper #searchHelp{
                font-size: .85rem; color:#6c757d;
            }
            .vehicle-search-wrapper .callout-table{ font-size: .9rem; }
            .vehicle-search-wrapper .callout-table th,
            .vehicle-search-wrapper .callout-table td{ white-space: nowrap; }
        }
    </style>

    {{-- ===== VIN Specs (from session) ===== --}}
    <style>
    .specs-bar{background:#f8f9fa;border:1px solid #e9ecef;border-radius:.75rem;padding:.5rem .75rem;}
    .specs-rail{display:flex;flex-wrap:nowrap;gap:.5rem;overflow:auto;overscroll-behavior:contain;scrollbar-width:thin}
    .spec-chip{white-space:nowrap;border:1px solid #dee2e6;background:#fff;border-radius:999px;padding:.35rem .6rem;display:flex;align-items:center;gap:.4rem;font-size:.9rem}
    .spec-chip .k{color:#6c757d}
    .spec-chip .v{font-weight:600}
    .spec-chip .src{font-size:.75rem;background:#e9ecef;border-radius:999px;padding:.1rem .45rem;color:#495057}
    @media (max-width:576px){.spec-chip{font-size:.88rem}}
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


    <div class="row">
        <div class="col-12 mx-auto">

            <div class="py-3">
                <livewire:attributes :catalog="$catalog" />
            </div>

            <div class="mb-2" aria-live="polite">
                @if($searchScope === 'section')
                    <span class="badge bg-info text-dark">{{ __('ui.section_only') }}</span>
                @else
                    <span class="badge bg-warning text-dark">{{ __('ui.catalog_wide') }}</span>
                @endif
            </div>

            <script src="{{ asset('js/vehicle-search-optimizations.js') }}"></script>

            {{-- تنبيه الخطأ – وصولية أفضل --}}
            @if($errorMessage)
                <div id="vehicleSearchErrorAlert"
                     class="alert alert-danger alert-dismissible fade show"
                     role="alert"
                     aria-live="assertive"
                     aria-atomic="true">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    {{ __($errorMessage) }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="{{ __('ui.close') }}"></button>
                </div>
            @endif

            {{-- تلميح اختياري عند انعدام النتائج في Section فقط --}}
            @if($errorMessage && $searchScope === 'section' && str_contains(__($errorMessage), __(\App\Livewire\VehicleSearchBox::ERR_NO_CALLOUT)))
                <div class="alert alert-warning py-2" role="status" aria-live="polite">
                    <i class="fas fa-lightbulb me-1"></i>
                    {{ __('ui.scope_hint_try_catalog') }}
                    <button type="button" class="btn btn-sm btn-outline-primary ms-1" wire:click="setSearchScope('catalog')">
                        {{ __('ui.catalog_all') }}
                    </button>
                </div>
            @endif

            <div class="btn-group btn-group-sm" role="group" aria-label="{{ __('ui.search_scope') }}">
                <button type="button"
                        class="btn {{ $searchScope === 'catalog' ? 'btn-primary' : 'btn-outline-secondary' }}"
                        wire:click="setSearchScope('catalog')">
                    {{ __('ui.catalog_all') }}
                </button>
                <button type="button"
                        class="btn {{ $searchScope === 'section' ? 'btn-primary' : 'btn-outline-secondary' }}"
                        wire:click="setSearchScope('section')">
                    {{ __('ui.this_section') }}
                </button>
            </div>

            <!-- Segmented toggle -->
            <div class="mb-3">
                <label class="form-label fw-bold">{{ __('ui.search_type') }}</label>
                <div class="btn-group w-100 d-flex segmented" role="group" aria-label="{{ __('ui.search_type') }}">
                    @foreach ([
                        'number' => ['icon' => '🔢', 'label' => __('ui.part_number'), 'desc' => __('ui.search_by_part_number')],
                        'label'  => ['icon' => '📄', 'label' => __('ui.part_name'),   'desc' => __('ui.search_by_part_name')]
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
                        <label class="btn btn-outline-primary flex-fill" for="searchBy{{ ucfirst($type) }}" title="{{ $config['desc'] }}">
                            <span class="d-none d-sm-inline">{{ $config['icon'] }}</span>
                            <span class="ms-1">{{ $config['label'] }}</span>
                        </label>
                    @endforeach
                </div>
            </div>

            <!-- Search input -->
            <div class="mb-4 position-relative">
                <label for="searchInput" class="form-label visually-hidden">
                    {{ $searchType === 'number' ? __('ui.enter_part_number') : __('ui.enter_part_name') }}
                </label>

                {{-- <div class="input-group input-group-lg">
                    <span class="input-group-text" aria-hidden="true">{{ $searchType === 'number' ? '🔢' : '📄' }}</span>
                    <input
                        id="searchInput"
                        type="text"
                        class="form-control"
                        placeholder="{{ $searchType === 'number' ? __('ui.enter_part_number') : __('ui.enter_part_name') }}"
                        wire:model.debounce.500ms="query"
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
                    @if(!empty($query))
                        <button
                            class="btn btn-outline-secondary"
                            type="button"
                            aria-label="{{ __('ui.clear') }}"
                            onclick="@this.set('query',''); @this.call('clearSearch'); document.getElementById('searchInput').focus();"
                            wire:loading.attr="disabled"
                            wire:target="searchFromInput"
                        >
                            <i class="fas fa-times"></i>
                        </button>
                    @endif
                    <button
                        class="btn btn-primary"
                        type="button"
                        wire:click="searchFromInput"
                        wire:loading.attr="disabled"
                        wire:target="searchFromInput"
                        aria-label="{{ __('ui.search') }}"
                        @if($searchType === 'number' ? strlen(preg_replace('/[^0-9A-Za-z]+/', '', $query)) < 5 : mb_strlen($query, 'UTF-8') < 2) disabled @endif
                    >
                        <span class="spinner-border spinner-border-sm me-1"
                              role="status"
                              aria-hidden="true"
                              wire:loading
                              wire:target="searchFromInput"></span>
                        <i class="fas fa-search"></i>
                    </button>
                </div> --}}
                <div class="input-group input-group-lg">
                    <span class="input-group-text" aria-hidden="true">
                        {{ $searchType === 'number' ? '🔢' : '📄' }}
                    </span>

                    <input
                        id="searchInput"
                        type="text"
                        class="form-control"
                        placeholder="{{ $searchType === 'number' ? __('ui.enter_part_number') : __('ui.enter_part_name') }}"
                        wire:model.debounce.500ms="query"
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
                        <span class="spinner-border spinner-border-sm me-1"
                            role="status"
                            aria-hidden="true"
                            wire:loading
                            wire:target="searchFromInput"></span>
                        <i class="fas fa-search"></i>
                    </button>
                </div>


                <div id="searchHelp" class="form-text {{ $tooShort ? 'text-danger' : '' }}">
                    {{ $searchType === 'number' ? __('ui.part_number_help') : __('ui.part_name_help') }}
                </div>

                @if($searchType === 'label' && !empty($results) && is_array($results) && !$isLoading && mb_strlen($query, 'UTF-8') >= 2 && isset($results[0]) && is_string($results[0]))
                    <div id="suggestionsDropdown"
                         class="position-absolute w-100 bg-white border border-top-0 rounded-bottom shadow-sm suggestions-dropdown"
                         role="listbox">
                        @foreach($results as $suggestion)
                            <div class="p-2 border-bottom suggestion-item"
                                 onclick="selectSuggestion('{{ is_string($suggestion) ? addslashes($suggestion) : '' }}')">
                                <i class="fas fa-search text-muted me-2"></i>
                                {{ is_string($suggestion) ? $suggestion : '' }}
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            @if ($isLoading)
                <div class="alert alert-info text-center d-flex justify-content-center align-items-center gap-2" role="status" aria-live="polite">
                    <div class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></div>
                    <span>
                        {{ $searchType === 'number' ? __('ui.searching_by_number') : ($searchType === 'label' ? __('ui.searching_by_name') : __('ui.loading_callouts')) }}:
                        <strong>{{ e($selectedItem) }}</strong>
                    </span>
                </div>
            @endif

            @if($showCalloutPicker && !empty($calloutOptions))
                <div class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.4); z-index:1050;">
                    <div class="modal-dialog modal-xl modal-dialog-centered modal-fullscreen-sm-down">
                        <div class="modal-content shadow">
                            <div class="modal-header">
                                <h5 class="modal-title">{{ __('ui.select_matching_callout') }}</h5>
                                <button type="button" class="btn-close" wire:click="$set('showCalloutPicker', false)" aria-label="{{ __('ui.close') }}"></button>
                            </div>

                            <div class="modal-body">
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
                                                <div class="d-flex justify-content-between align-items-center mb-1">
                                                    <div class="fw-bold">
                                                        {{ __('ui.callout') }}
                                                        <span class="badge bg-primary">{{ $opt['callout'] }}</span>
                                                    </div>
                                                    <span class="badge bg-secondary">{{ __('ui.qty') }} {{ $opt['qty'] ?? '—' }}</span>
                                                </div>

                                                <div class="fw-semibold mb-1 text-truncate-2">
                                                    {{ localizedPartLabel($opt['label_en'] ?? null, $opt['label_ar'] ?? null) }}
                                                </div>

                                                <div class="meta small mb-1">
                                                    <span class="badge badge-soft">{{ formatYearRange($fromYear, $toYear) }}</span>
                                                    <span class="badge badge-soft"><code class="small">{{ $opt['key2'] ?? '—' }}</code></span>
                                                </div>

                                                <div class="text-muted small text-truncate-3 mb-2">
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
                                                        ]) }}" class="btn btn-primary btn-sm">
                                                            {{ __('ui.open') }}
                                                        </a>
                                                    @else
                                                        <button class="btn btn-outline-secondary btn-sm" disabled>{{ __('ui.open') }}</button>
                                                    @endif
                                                </div>
                                            </div>
                                        </article>
                                    @endforeach
                                </div>

                                <div class="rail-nav">
                                    <button class="btn btn-outline-secondary btn-sm" type="button" onclick="scrollRail(-1)" aria-label="{{ __('ui.prev') }}">
                                        <i class="fas fa-chevron-up"></i>
                                    </button>
                                    <button class="btn btn-outline-secondary btn-sm" type="button" onclick="scrollRail(1)" aria-label="{{ __('ui.next') }}">
                                        <i class="fas fa-chevron-down"></i>
                                    </button>
                                </div>

                                <div class="alert alert-info mt-3" role="status" aria-live="polite">
                                    <i class="fas fa-info-circle me-1"></i>
                                    {{ __('ui.go_to_part_page_hint') }}
                                </div>
                            </div>

                            <div class="modal-footer">
                                <button class="btn btn-outline-secondary" wire:click="$set('showCalloutPicker', false)">{{ __('ui.close') }}</button>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

                                {{-- </div>

                                <div class="alert alert-info mt-3" role="status" aria-live="polite">
                                    <i class="fas fa-info-circle me-1"></i>
                                    {{ __('ui.go_to_part_page_hint') }}
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button class="btn btn-outline-secondary" wire:click="$set('showCalloutPicker', false)">{{ __('ui.close') }}</button>
                            </div>
                        </div>
                    </div>
                </div>
            @endif --}}
        </div>
    </div>
</div>

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
