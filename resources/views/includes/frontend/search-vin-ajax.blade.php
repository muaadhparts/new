{{-- VIN Search Component - AJAX Based --}}
{{-- Styles in MUAADH.css Section 32: Search Components --}}
<div class="muaadh-search-wrapper muaadh-vin-search" id="vinSearchWrapper{{ $uniqueId ?? 'default' }}">
    <div class="muaadh-search-container">
        <div class="muaadh-search-input-group">
            <span class="muaadh-search-icon">
                <i class="fas fa-car"></i>
            </span>
            <input
                type="text"
                class="muaadh-search-input vin-input"
                placeholder="{{ __('Enter VIN') }}"
                id="vinInput{{ $uniqueId ?? 'default' }}"
                dir="ltr"
                maxlength="17"
                autocomplete="off"
            >
            <button type="button" class="muaadh-search-btn vin-search-btn" id="vinSearchBtn{{ $uniqueId ?? 'default' }}">
                <i class="fas fa-search me-2 search-icon"></i>
                <span class="spinner-border spinner-border-sm me-2 d-none loading-spinner" role="status"></span>
                <span class="d-none d-sm-inline">{{ __('Search') }}</span>
            </button>
        </div>
    </div>

    {{-- Search Hint --}}
    <div class="muaadh-search-hint">
        <i class="fas fa-info-circle me-1"></i>
        <span>{{ __('Example :') }}</span>
        <code dir="ltr">5N1AA0NC7EN603053</code>
    </div>

    {{-- Loading State --}}
    <div class="muaadh-vin-loading d-none loading-state" id="vinLoading{{ $uniqueId ?? 'default' }}">
        <div class="muaadh-vin-loading-content">
            <div class="spinner-border mb-3" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <div class="muaadh-vin-progress">
                <div class="muaadh-vin-progress-bar"></div>
            </div>
            <p>
                <i class="fas fa-sync fa-spin me-2"></i>
                {{ __('Fetching vehicle info...') }}
            </p>
        </div>
    </div>

    {{-- Error Message --}}
    <div class="muaadh-vin-error d-none error-message" id="vinError{{ $uniqueId ?? 'default' }}">
        <i class="fas fa-exclamation-triangle"></i>
        <div class="muaadh-vin-error-content">
            <strong>{{ __('Warning') }}</strong>
            <p class="error-text"></p>
        </div>
        <button type="button" class="muaadh-vin-error-close" onclick="this.parentElement.classList.add('d-none')">
            <i class="fas fa-times"></i>
        </button>
    </div>

    {{-- VIN Result Card --}}
    <div class="muaadh-vin-result d-none result-container" id="vinResult{{ $uniqueId ?? 'default' }}">
        <div class="muaadh-vin-result-card vin-result-card" role="button">
            <div class="muaadh-vin-result-icon">
                <i class="fas fa-car-side"></i>
            </div>
            <div class="muaadh-vin-result-info">
                <div class="muaadh-vin-result-vin">
                    <i class="fas fa-barcode me-2"></i>
                    <span class="vin-number"></span>
                </div>
                <div class="muaadh-vin-result-label">
                    <i class="fas fa-tag me-2"></i>
                    <span class="label-text"></span>
                </div>
            </div>
            <div class="muaadh-vin-result-action">
                <span class="muaadh-vin-select-badge">
                    <i class="fas fa-check-circle me-1"></i>
                    {{ __('Click to Select') }}
                </span>
            </div>
            <div class="muaadh-vin-result-arrow">
                <i class="fas fa-chevron-right"></i>
            </div>
        </div>
    </div>

    <script>
(function() {
    const uniqueId = '{{ $uniqueId ?? "default" }}';
    const wrapper = document.getElementById('vinSearchWrapper' + uniqueId);
    if (!wrapper) return;

    const input = wrapper.querySelector('.vin-input');
    const searchBtn = wrapper.querySelector('.vin-search-btn');
    const loadingState = wrapper.querySelector('.loading-state');
    const errorMessage = wrapper.querySelector('.error-message');
    const resultContainer = wrapper.querySelector('.result-container');
    const resultCard = wrapper.querySelector('.vin-result-card');

    let currentVinData = null;

    // دالة البحث
    function searchVin() {
        const query = input.value.trim();

        if (query.length < 10) {
            showError('{{ __("VIN must be at least 10 characters.") }}');
            return;
        }

        // إظهار التحميل
        setLoading(true);
        hideError();
        hideResult();

        // إرسال الطلب
        fetch('{{ route("api.search.vin") }}?query=' + encodeURIComponent(query), {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            setLoading(false);

            if (data.success && data.result) {
                currentVinData = data.result;
                showResult(data.result);
            } else {
                showError(data.message || '{{ __("VIN not found.") }}');
            }
        })
        .catch(error => {
            setLoading(false);
            showError('{{ __("An error occurred. Please try again.") }}');
            console.error('VIN Search Error:', error);
        });
    }

    // اختيار VIN والتوجيه
    function selectVin() {
        if (!currentVinData) return;

        setLoading(true);

        fetch('{{ route("api.search.vin.select") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ vin: currentVinData.vin })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success && data.redirect_url) {
                window.location.href = data.redirect_url;
            } else {
                setLoading(false);
                showError(data.message || '{{ __("Failed to select VIN.") }}');
            }
        })
        .catch(error => {
            setLoading(false);
            showError('{{ __("An error occurred. Please try again.") }}');
            console.error('VIN Select Error:', error);
        });
    }

    // دوال مساعدة
    function setLoading(show) {
        if (show) {
            loadingState.classList.remove('d-none');
            searchBtn.querySelector('.search-icon').classList.add('d-none');
            searchBtn.querySelector('.loading-spinner').classList.remove('d-none');
            searchBtn.disabled = true;
        } else {
            loadingState.classList.add('d-none');
            searchBtn.querySelector('.search-icon').classList.remove('d-none');
            searchBtn.querySelector('.loading-spinner').classList.add('d-none');
            searchBtn.disabled = false;
        }
    }

    function showError(message) {
        errorMessage.querySelector('.error-text').textContent = message;
        errorMessage.classList.remove('d-none');
    }

    function hideError() {
        errorMessage.classList.add('d-none');
    }

    function showResult(data) {
        resultContainer.querySelector('.vin-number').textContent = data.vin;
        resultContainer.querySelector('.label-text').textContent = data.label_en || 'N/A';
        resultContainer.classList.remove('d-none');
    }

    function hideResult() {
        resultContainer.classList.add('d-none');
        currentVinData = null;
    }

    // Event Listeners
    searchBtn.addEventListener('click', searchVin);

    input.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            searchVin();
        }
    });

    resultCard.addEventListener('click', selectVin);

    // تحويل النص للأحرف الكبيرة
    input.addEventListener('input', function() {
        this.value = this.value.toUpperCase().replace(/[^A-Z0-9]/g, '');
    });
})();
    </script>
</div>
