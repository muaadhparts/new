<div class="container mb-5 text-center" style="position: relative;" x-data="{ vinModal: false }">

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

        {{-- قائمة التلميحات --}}
        @if (!empty($results))
            <div class="suggestion-box">
                @foreach ($results as $result)
                    <div class="suggestion-item" wire:click="selectItem('{{ $result['sku'] }}')">
                        <div class="result-sku">{{ $result['sku'] }}</div>
                        <div class="result-label">
                            {{ getLocalizedLabel($result) }}
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    {{-- التلميح --}}
    <p class="search-hint mt-2">
        {{ __('Example :') }} <code dir="ltr">1520831U0B</code>
    </p>
    <!-- Modal -->
    <div class="modal fade" id="vinSearchModal" tabindex="-1" aria-labelledby="vinSearchModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg"> <!-- modal-lg لو حابب يكون واسع -->
        <div class="modal-content">
        <div class="modal-header">
            <h5 class="modal-title" id="vinSearchModalLabel">@lang('Search by VIN')</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="@lang('Close')"></button>
        </div>
        <div class="modal-body">
            <livewire:search-boxvin/>
        </div>
        </div>
    </div>
    </div>

<style>
    [x-cloak] { display: none !important; }

    /* حاوية البحث */
    .search-row{
        position: relative;
        display:flex; 
        justify-content:center; 
        align-items:center; 
        flex-wrap:wrap;
        width: 100%;
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
        margin: auto;
        transition: all 0.3s ease-in-out;
    }
    .search-input:focus{
        border-color:#0056b3;
        box-shadow:0 0 8px rgba(1, 21, 43, 0.3);
        max-width: 650px;
    }

    .search-hint{
        font-size: 13px;
        color: #6c757d;
        margin-bottom: 0;
    }

    /* صندوق التلميحات */
    .suggestion-box{
        position: absolute;
        top: 100%;
        left: 50%;
        transform: translateX(-50%);
        width: 100%;
        max-width: 520px;
        background: #fff;
        border: 1px solid #ddd;
        border-radius: 10px;
        margin-top: 6px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        max-height: 350px;
        overflow-y: auto;
        z-index: 1000;
    }
    .suggestion-item{
        padding: 10px 14px;
        text-align: left;
        cursor: pointer;
        transition: background 0.2s ease-in-out;
    }
    .suggestion-item:hover{ background: #f8f9fa; }

    .result-sku{
        font-weight: bold;
        font-size: 15px;
        color: rgb(73, 103, 236);
    }
    .result-label-ar{
        font-weight: 600;
        font-size: 10px;
        color: #333;
    }
    .result-label-en{
        font-weight: 500;
        font-size: 10px;
        color: #004085;
    }

    @media (max-width: 576px){
        .search-input{ max-width: 100%; }
        .suggestion-box{ max-width: 100%; }
    }
</style>
