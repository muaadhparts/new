<div class="container mb-5 text-center">

    {{-- الحقل --}}
    <div class="search-row">
        <input
            type="text"
            class="search-input"
            placeholder="{{ __('Enter part number or name') }}"
            wire:model.debounce.300ms="query"
            wire:keydown.enter="submitSearch"
            aria-label="{{ __('Search by part number or name') }}"
        >
    </div>

    {{-- التلميح --}}
    <p class="search-hint mt-2">
        {{ __('Example:') }} <code dir="ltr">1520831U0b</code>
    </p>

    {{-- رسالة التنبيه مع مؤقّت إظهار --}}
    <div
        x-data="{ show:false, timer:null, delay: {{ $notFoundDelayMs }}, nf: @entangle('notFound') }"
        x-effect="
            if (nf) {
                clearTimeout(timer);
                show = false;
                timer = setTimeout(() => { show = true }, delay);
            } else {
                show = false;
                clearTimeout(timer);
            }
        "
    >
        <div class="alert alert-warning mt-3" 
             x-show="show" 
             x-transition
             x-cloak
             style="font-weight: bold; font-size: 15px;">
            {{ $userMessage }}
        </div>
    </div>

    {{-- النتائج --}}
    @if (!empty($results))
        <ul role="listbox" style="list-style: none; padding: 0; margin-top: 15px;">
            @foreach ($results as $result)
                <li class="list-group-item" style="padding: 10px; border-bottom: 1px solid #ddd; cursor: pointer;">
                    <div wire:click="selectItem('{{ $result['sku'] }}')"
                         style="font-weight: bold; font-size: 16px; color: #e90b0b;">
                        {{ $result['sku'] }}
                    </div>
                    <div style="font-weight: 600; font-size: 14px; color: #912020; margin-top: 2px;">
                        {{ $result['label_ar'] ?? '' }}
                    </div>
                    <div style="font-weight: 600; font-size: 14px; color: #1f12d3;">
                        {{ $result['label_en'] ?? '' }}
                    </div>
                </li>
            @endforeach
        </ul>
    @endif
</div>

<style>
    [x-cloak] { display: none !important; }

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
        max-width: 520px; 
        transition: all 0.3s ease-in-out;
    }
    .search-input:focus{
        border-color:#0056b3;
        box-shadow:0 0 8px rgba(0,86,179,0.3);
    }
    .search-hint{
        font-size: 13px;
        color: #6c757d;
        margin-bottom: 0;
    }
    @media (max-width: 576px){
        .search-row{ flex-direction: column; }
        .search-input{ max-width: 100%; }
    }
</style>
