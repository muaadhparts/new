<div class="container mb-5 text-center">
    <div class="vin-search-wrapper">

        {{-- شريط البحث + الزر --}}
        <div class="search-row">
            <input
                type="text"
                class="search-input"
                placeholder="{{ __('Enter VIN (17 characters)') }}"
                wire:model.lazy="query"
                wire:keydown.enter="submitSearch"
                aria-label="{{ __('Search by VIN') }}"
                dir="ltr"
            >
            <button
                type="button"
                class="vin-search-button"
                wire:click="submitSearch"
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
        </div>

        {{-- التلميح --}}
        <p class="search-hint mt-2">
            {{ __('Example:') }} <code dir="ltr">5N1AA0NC7EN603053</code>
        </p>

        {{-- شريط التحميل --}}
        @if ($isLoading && strlen($query) >= 10)
            <div class="vin-loading-bar">
                <div class="vin-loading-track">
                    <div class="vin-loading-fill"></div>
                </div>
                <span class="vin-loading-text">{{ __('Fetching vehicle information…') }}</span>
            </div>
        @endif
    </div>

    {{-- رسالة التنبيه --}}
    @if ($notFound && $userMessage)
        <div class="alert alert-warning mt-3" style="font-weight: bold; font-size: 15px;">
            {{ $userMessage }}
        </div>
    @endif

    {{-- نتائج VIN (تظهر فقط إن قررت عرضها بدلاً من إعادة التوجيه المباشر) --}}
    @if (!empty($results) && $is_vin)
        <ul role="listbox" style="list-style: none; padding: 0; margin-top: 15px;">
            <li class="list-group-item d-flex justify-content-between align-items-center" style="padding: 10px; cursor: pointer;">
                <span wire:click="selectedVin('{{ json_encode($results) }}')" style="font-weight: bold; font-size: 16px; color: #114488;">
                    {{ $results['vin'] ?? '' }} - {{ $results['label_en'] ?? 'N/A' }}
                </span>
            </li>
        </ul>
    @endif
</div>

<style>
    .search-row{
        display:flex; justify-content:center; align-items:center; gap:12px; flex-wrap:wrap;
    }
    .search-input{
        padding: 12px 18px;
        font-size: 16px;
        border: 2px solid #007bff;
        border-radius: 30px;
        box-shadow: 0 0 6px rgba(0,123,255,0.2);
        outline: none;
        width: 100%;
        max-width: 520px;        /* يضمن أن الزر يكون ملاصقًا للحقل في الشاشات الواسعة */
        transition: all 0.3s ease-in-out;
    }
    .search-input:focus{
        border-color:#0056b3;
        box-shadow:0 0 8px rgba(0,86,179,0.3);
    }
    .vin-search-button{
        padding: 10px 20px;
        border: none;
        border-radius: 30px;
        background-color: #007bff;
        color: #fff;
        font-weight: bold;
        font-size: 15px;
        cursor: pointer;
        box-shadow: 0 0 6px rgba(0, 123, 255, 0.2);
        transition: background-color 0.3s ease-in-out;
        height: 48px; /* مواءمة ارتفاع الزر مع الحقل */
    }
    .vin-search-button:hover { background-color: #0056b3; }
    .search-hint{
        font-size: 13px;
        color: #6c757d;
        margin-bottom: 0;
    }

    .vin-loading-bar {
        margin-top: 8px;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 6px;
    }
    .vin-loading-track {
        position: relative;
        width: 100%;
        max-width: 520px;
        height: 6px;
        background-color: #e9ecef;
        border-radius: 4px;
        overflow: hidden;
    }
    .vin-loading-fill {
        position: absolute;
        top: 0; left: 0;
        height: 100%; width: 40%;
        background-color: #007bff;
        animation: vin-progress 1.2s infinite ease-in-out;
    }
    .vin-loading-text { font-size: 14px; font-weight: bold; color: #007bff; }

    @keyframes vin-progress {
        0% { transform: translateX(-100%); }
        50% { transform: translateX(0%); }
        100% { transform: translateX(100%); }
    }

    @media (max-width: 576px){
        .search-row{ flex-direction: column; }
        .search-input{ max-width: 100%; }
        .vin-search-button{ width: 100%; height: 46px; }
    }
</style>
