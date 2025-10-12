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
        padding: 12px 30px;
        font-size: 1rem;
        font-weight: 600;
        border: 2px solid transparent;
        border-radius: 50px;
        background-color: rgba(255, 255, 255, 0.9);
        color: #667eea;
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
    }

    .custom-tab-btn:hover {
        background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1));
        color: #667eea;
        border-color: #667eea;
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(102, 126, 234, 0.2);
    }

    .active-tab {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
        color: white !important;
        border-color: transparent !important;
        box-shadow: 0 8px 25px rgba(102, 126, 234, 0.35) !important;
        transform: translateY(-2px);
    }

    .active-tab::after {
        content: '';
        position: absolute;
        bottom: -8px;
        left: 50%;
        transform: translateX(-50%);
        width: 0;
        height: 0;
        border-left: 8px solid transparent;
        border-right: 8px solid transparent;
        border-top: 8px solid #764ba2;
    }

    .search-input {
        padding: 14px 20px;
        font-size: 1rem;
        border: 2px solid #667eea;
        border-radius: 50px;
        box-shadow: 0 4px 15px rgba(102, 126, 234, 0.15);
        outline: none;
        width: 100%;
        max-width: 600px;
        margin: 0 auto;
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        background: #fff;
    }

    .search-input:focus {
        border-color: #764ba2;
        box-shadow: 0 6px 25px rgba(102, 126, 234, 0.25);
        transform: translateY(-2px);
    }

    [x-cloak] { display: none !important; }

    @media (max-width: 768px) {
        .custom-tab-btn {
            padding: 10px 24px;
            font-size: 0.9rem;
        }

        .search-input {
            max-width: 100%;
        }
    }

    @media (max-width: 576px) {
        .custom-tab-btn {
            width: 100%;
            margin-bottom: 10px;
            padding: 10px 20px;
            font-size: 0.875rem;
        }

        .search-input {
            font-size: 0.875rem;
            padding: 12px 16px;
        }

        .vin-search-button {
            padding: 10px 24px;
            font-size: 0.9rem;
        }
    }

    .vin-search-button {
        padding: 12px 30px;
        border: none;
        border-radius: 50px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: #fff;
        font-weight: 600;
        font-size: 1rem;
        cursor: pointer;
        box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .vin-search-button:hover {
        transform: translateY(-3px);
        box-shadow: 0 12px 30px rgba(102, 126, 234, 0.4);
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
        background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
        animation: vin-progress 1.2s infinite ease-in-out;
    }

    .vin-loading-text {
        font-size: 0.875rem;
        font-weight: 600;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
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
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 10px 20px;
        border-radius: 50px;
        font-weight: 600;
        font-size: 0.875rem;
        z-index: 9999;
        box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
        animation: pulse 1.5s ease-in-out infinite;
    }

    @keyframes pulse {
        0%, 100% {
            opacity: 1;
            transform: scale(1);
        }
        50% {
            opacity: 0.8;
            transform: scale(1.05);
        }
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
