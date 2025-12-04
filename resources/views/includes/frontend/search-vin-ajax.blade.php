{{-- VIN Search Component - AJAX Based --}}
<div class="vin-search-ajax-wrapper" id="vinSearchWrapper{{ $uniqueId ?? 'default' }}">
    <style>
        .vin-search-ajax-wrapper .vin-form-wrapper .input-group {
            border-radius: 0.5rem;
            overflow: hidden;
            border: 2px solid var(--bs-primary, #0d6efd);
            background: #fff;
            transition: all 0.3s ease;
        }
        .vin-search-ajax-wrapper .vin-form-wrapper .input-group:focus-within {
            border-color: #0b5ed7;
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.15);
        }
        .vin-search-ajax-wrapper .vin-form-wrapper .input-group-text {
            border: none;
            background: transparent;
        }
        .vin-search-ajax-wrapper .vin-form-wrapper .form-control {
            border: none;
            font-size: 1.1rem;
            font-weight: 600;
            letter-spacing: 0.5px;
            font-family: 'Courier New', monospace;
        }
        .vin-search-ajax-wrapper .vin-form-wrapper .form-control:focus {
            box-shadow: none;
        }
        .vin-search-ajax-wrapper .vin-result-card {
            cursor: pointer;
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }
        .vin-search-ajax-wrapper .vin-result-card:hover {
            border-color: var(--bs-primary, #0d6efd);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        .vin-search-ajax-wrapper .vin-icon-wrapper {
            width: 60px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 0.5rem;
        }
    </style>

    <div class="vin-form-wrapper">
        <div class="input-group input-group-lg shadow-sm">
            <span class="input-group-text bg-white border-end-0 ps-4">
                <i class="fas fa-car text-primary"></i>
            </span>
            <input
                type="text"
                class="form-control form-control-lg border-start-0 border-end-0 ps-0 vin-input"
                placeholder="{{ __('Enter VIN') }}"
                id="vinInput{{ $uniqueId ?? 'default' }}"
                dir="ltr"
                maxlength="17"
                autocomplete="off"
            >
            <button
                type="button"
                class="btn btn-primary px-4 vin-search-btn"
                id="vinSearchBtn{{ $uniqueId ?? 'default' }}"
            >
                <i class="fas fa-search me-2 search-icon"></i>
                <span class="spinner-border spinner-border-sm me-2 d-none loading-spinner" role="status"></span>
                <span class="btn-text d-none d-sm-inline">{{ __('Search') }}</span>
            </button>
        </div>
    </div>

    {{-- Search Hint --}}
    <div class="text-center mt-3">
        <p class="mb-0 text-muted small">
            <i class="fas fa-info-circle me-1"></i>
            <span>{{ __('Example :') }}</span>
            <code class="bg-light px-2 py-1 rounded ms-1" dir="ltr">5N1AA0NC7EN603053</code>
        </p>
    </div>

    {{-- Loading State --}}
    <div class="mt-4 d-none loading-state" id="vinLoading{{ $uniqueId ?? 'default' }}">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center py-4">
                <div class="spinner-border text-primary mb-3" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <div class="progress mb-3" style="height: 8px;">
                    <div class="progress-bar progress-bar-striped progress-bar-animated"
                         style="width: 60%; background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);">
                    </div>
                </div>
                <p class="text-primary fw-bold mb-0">
                    <i class="fas fa-sync fa-spin me-2"></i>
                    {{ __('Fetching vehicle info...') }}
                </p>
            </div>
        </div>
    </div>

    {{-- Error Message --}}
    <div class="alert alert-warning alert-dismissible fade show mt-4 shadow-sm d-none error-message" id="vinError{{ $uniqueId ?? 'default' }}" role="alert">
        <div class="d-flex align-items-center">
            <i class="fas fa-exclamation-triangle me-3 fs-4"></i>
            <div class="flex-grow-1">
                <strong>{{ __('Warning') }}</strong>
                <p class="mb-0 error-text"></p>
            </div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>

    {{-- VIN Result Card --}}
    <div class="mt-4 d-none result-container" id="vinResult{{ $uniqueId ?? 'default' }}">
        <div class="card border-0 shadow-sm vin-result-card" role="button">
            <div class="card-body p-4">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0 me-4">
                        <div class="vin-icon-wrapper">
                            <i class="fas fa-car-side text-white fs-3"></i>
                        </div>
                    </div>
                    <div class="flex-grow-1">
                        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center">
                            <div>
                                <h5 class="mb-2 fw-bold text-primary result-vin">
                                    <i class="fas fa-barcode me-2"></i>
                                    <span class="vin-number"></span>
                                </h5>
                                <p class="mb-0 text-muted result-label">
                                    <i class="fas fa-tag me-2"></i>
                                    <span class="label-text"></span>
                                </p>
                            </div>
                            <div class="mt-3 mt-md-0">
                                <span class="badge bg-success bg-gradient px-3 py-2">
                                    <i class="fas fa-check-circle me-1"></i>
                                    {{ __('Click to Select') }}
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="flex-shrink-0 ms-3 d-none d-md-block">
                        <i class="fas fa-chevron-right text-muted fs-4"></i>
                    </div>
                </div>
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
