<div class="container mb-5 text-center">
    <div class="vin-search-wrapper">

        {{-- شريط البحث + الزر داخل فورم --}}
        <form wire:submit.prevent="submitSearch" class="search-row d-flex justify-content-center align-items-center gap-2 flex-wrap">
            <input
                type="text"
                class="form-control rounded-pill px-3"
                placeholder="{{ __('Enter VIN') }}"
                wire:model.lazy="query"
                aria-label="{{ __('Search by VIN') }}"
                dir="ltr"
                style="max-width: 520px;"
            >

            <button
                type="submit"
                class="btn btn-primary rounded-pill px-4"
                wire:loading.attr="disabled"
                wire:loading.class="disabled"
            >
                @if ($isLoading)
                    <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                    {{ __('Searching...') }}
                @else
                    {{ __('Search') }}
                @endif
            </button>
        </form>

        {{-- التلميح --}}
        <p class="search-hint mt-2 text-muted small">
            {{ __('Example:') }} <code dir="ltr">5N1AA0NC7EN603053</code>
        </p>

        {{-- شريط التحميل --}}
        @if ($isLoading && strlen($query) >= 10)
            <div class="mt-2 d-flex flex-column align-items-center gap-1">
                <div class="progress" style="width: 100%; max-width: 520px; height: 6px;">
                    <div class="progress-bar progress-bar-striped progress-bar-animated bg-primary" style="width: 40%"></div>
                </div>
                <span class="text-primary fw-bold small">{{ __('Fetching vehicle information…') }}</span>
            </div>
        @endif
    </div>

    {{-- رسالة التنبيه --}}
    @if ($notFound && $userMessage)
        <div class="alert alert-warning mt-3 fw-bold" style="font-size: 15px;">
            {{ $userMessage }}
        </div>
    @endif

    {{-- نتائج VIN --}}
    @if (!empty($results) && $is_vin)
        <ul class="list-unstyled mt-3">
            <li class="list-group-item d-flex justify-content-between align-items-center" style="cursor: pointer;">
                <span wire:click="selectedVin('{{ json_encode($results) }}')" class="fw-bold" style="font-size: 16px; color: #114488;">
                    {{ $results['vin'] ?? '' }} - {{ $results['label_en'] ?? 'N/A' }}
                </span>
            </li>
        </ul>
    @endif
</div>
