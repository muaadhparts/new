<div class="container mb-5 text-center" x-data="{ tab: localStorage.getItem('searchTab') || 'part' }" x-init="$watch('tab', v => localStorage.setItem('searchTab', v))"> 

    {{-- ✅ التبويبات --}}
    <div class="d-flex justify-content-center flex-wrap gap-2 mb-4">
        <button
            class="custom-tab-btn"
            :class="{ 'active-tab': tab === 'part' }"
            @click="tab = 'part'"
        >
            {{ __('Search by part number') }}
        </button>

        <button
            class="custom-tab-btn"
            :class="{ 'active-tab': tab === 'vin' }"
            @click="tab = 'vin'"
        >
            {{ __('Search by VIN') }}
        </button>
    </div>

    {{-- ✅ مكوّن رقم القطعة --}}
    <div x-show="tab === 'part'" x-cloak>
        @livewire('search-box')
    </div>

    {{-- ✅ مكوّن VIN --}}
    <div x-show="tab === 'vin'" x-cloak>
        @livewire('search-boxvin')
    </div>
</div>

<style>
    .custom-tab-btn {
        position: relative;
        padding: 10px 20px;
        font-size: 15px;
        font-weight: bold;
        border: 2px solid #007bff;
        border-radius: 25px;
        background-color: white;
        color: #007bff;
        transition: all 0.3s ease-in-out;
    }

    .custom-tab-btn:hover {
        background-color: #007bff;
        color: white;
    }

    .active-tab {
        background-color: #007bff !important;
        color: white !important;
        box-shadow: 0 4px 10px rgba(0, 123, 255, 0.3);
    }

    .active-tab::after {
        content: '';
        position: absolute;
        bottom: -6px;
        left: 50%;
        transform: translateX(-50%);
        width: 0;
        height: 0;
        border-left: 7px solid transparent;
        border-right: 7px solid transparent;
        border-top: 7px solid #007bff;
    }

    .search-input {
        padding: 12px 18px;
        font-size: 16px;
        border: 2px solid #007bff;
        border-radius: 30px;
        box-shadow: 0 0 6px rgba(0, 123, 255, 0.2);
        outline: none;
        width: 100%;
        max-width: 500px;
        margin: 0 auto;
        transition: all 0.3s ease-in-out;
    }

    .search-input:focus {
        border-color: #0056b3;
        box-shadow: 0 0 8px rgba(0, 86, 179, 0.3);
    }

    [x-cloak] { display: none !important; }

    @media (max-width: 576px) {
        .custom-tab-btn {
            width: 100%;
            margin-bottom: 10px;
        }

        .search-input {
            font-size: 15px;
            padding: 10px 14px;
        }
    }

    .vin-search-button {
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
    }

    .vin-search-button:hover {
        background-color: #0056b3;
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
        max-width: 500px;
        height: 6px;
        background-color: #e9ecef;
        border-radius: 4px;
        overflow: hidden;
    }

    .vin-loading-fill {
        position: absolute;
        top: 0;
        left: 0;
        height: 100%;
        width: 40%;
        background-color: #007bff;
        animation: vin-progress 1.2s infinite ease-in-out;
    }

    .vin-loading-text {
        font-size: 14px;
        font-weight: bold;
        color: #007bff;
    }

    @keyframes vin-progress {
        0% { transform: translateX(-100%); }
        50% { transform: translateX(0%); }
        100% { transform: translateX(100%); }
    }

    .search-loading::before {
        content: '{{ __('Searching...') }}';
        position: fixed;
        top: 20px;
        right: 20px;
        background: #007bff;
        color: white;
        padding: 6px 14px;
        border-radius: 20px;
        font-weight: bold;
        font-size: 14px;
        z-index: 9999;
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.2);
    }
</style>

{{-- ✅ سكربت Livewire لحالة التحميل --}}
@push('scripts')
<script>
    Livewire.hook('message.sent', () => {
        document.body.classList.add('search-loading');
    });

    Livewire.hook('message.processed', () => {
        document.body.classList.remove('search-loading');
    });
</script>
@endpush
