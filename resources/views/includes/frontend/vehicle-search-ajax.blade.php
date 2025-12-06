{{-- Vehicle Search Component - AJAX Based --}}
{{-- Uses catalog-unified.css for styling --}}
@php
    // Handle catalog as object or string code
    if (is_object($catalog)) {
        $catalogCode = $catalog->code ?? '';
        $brandName = $catalog->brand->name ?? '';
        $catalogObject = $catalog;
    } else {
        $catalogCode = $catalog ?? '';
        $brandName = '';
        $catalogObject = null;
    }
    $uniqueId = $uniqueId ?? 'default';
    $showAttributes = $showAttributes ?? true;
@endphp

<div class="vehicle-search-ajax-wrapper" id="vehicleSearchWrapper{{ $uniqueId }}">
    {{-- Specifications Bar removed - now displayed via chips-bar.blade.php in parent views --}}

    {{-- Search Type Toggle --}}
    <div class="d-flex gap-2 mb-3">
        <button type="button" class="btn btn-sm btn-outline-secondary search-type-btn active" data-type="number" id="typeNumber{{ $uniqueId }}">
            <i class="fas fa-hashtag me-1"></i>
            <span class="d-none d-md-inline">{{ __('ui.part_number') }}</span>
        </button>
        <button type="button" class="btn btn-sm btn-outline-secondary search-type-btn" data-type="label" id="typeLabel{{ $uniqueId }}">
            <i class="fas fa-tag me-1"></i>
            <span class="d-none d-md-inline">{{ __('ui.part_name') }}</span>
        </button>
    </div>

    {{-- Search Input --}}
    <div class="position-relative mb-3">
        <div class="input-group shadow-sm">
            <span class="input-group-text bg-white">
                <i class="fas fa-hashtag text-muted search-type-icon" id="searchIcon{{ $uniqueId }}"></i>
            </span>
            <input
                type="text"
                class="form-control"
                placeholder="{{ __('ui.enter_part_number') }}"
                id="vehicleSearchInput{{ $uniqueId }}"
                autocomplete="off"
                dir="ltr"
            >
            <button class="btn btn-primary" type="button" id="vehicleSearchBtn{{ $uniqueId }}">
                <i class="fas fa-search search-icon"></i>
                <span class="spinner-border spinner-border-sm d-none loading-spinner" role="status"></span>
                <span class="d-none d-lg-inline ms-2">{{ __('ui.search') }}</span>
            </button>
        </div>
        <small class="form-text text-muted" id="searchHelp{{ $uniqueId }}">
            <i class="fas fa-info-circle"></i>
            {{ __('ui.part_number_help') }}
        </small>

        {{-- Suggestions Dropdown --}}
        <div class="vehicle-search-suggestions d-none" id="vehicleSuggestions{{ $uniqueId }}"></div>
    </div>

    {{-- Error Message --}}
    <div class="alert alert-danger d-none" id="vehicleError{{ $uniqueId }}" role="alert">
        <i class="fas fa-exclamation-triangle me-2"></i>
        <span class="error-text"></span>
    </div>

    {{-- Loading State --}}
    <div class="catalog-loading d-none" id="vehicleLoading{{ $uniqueId }}" role="status">
        <div class="spinner-border text-primary" role="status"></div>
        <p>{{ __('ui.searching_by_number') }}</p>
    </div>

    {{-- Results Modal --}}
    <div class="vehicle-search-modal d-none" id="vehicleResultsModal{{ $uniqueId }}">
        <div class="vehicle-search-modal-content">
            <div class="catalog-section-header" style="background: var(--catalog-primary); color: #fff; border-radius: 1rem 1rem 0 0;">
                <h5 style="color: #fff;">
                    <i class="fas fa-list-ul"></i>
                    {{ __('ui.select_matching_callout') }}
                    <span class="badge bg-white text-primary ms-2 results-count">0</span>
                </h5>
                <button type="button" class="btn-close btn-close-white" id="closeResultsModal{{ $uniqueId }}"></button>
            </div>
            <div class="catalog-modal-content" style="max-height: 60vh;">
                <div class="catalog-cards" id="resultsContainer{{ $uniqueId }}"></div>
            </div>
            <div class="catalog-section-header" style="border-top: 1px solid var(--catalog-border); border-bottom: none; border-radius: 0 0 1rem 1rem;">
                <span></span>
                <button class="catalog-btn catalog-btn-outline" id="closeResultsBtn{{ $uniqueId }}">
                    <i class="fas fa-times"></i>
                    {{ __('ui.close') }}
                </button>
            </div>
        </div>
    </div>

</div>

<style>
/* Vehicle Search Wrapper */
.vehicle-search-ajax-wrapper {
    background: #fff;
    border-radius: 0.75rem;
    padding: 1rem;
    margin: 0.5rem auto;
    max-width: 1200px;
    box-shadow: var(--catalog-shadow, 0 1px 3px rgba(0,0,0,0.06));
    border: 1px solid #e9ecef;
}

.vehicle-search-ajax-wrapper .search-type-btn.active {
    background-color: var(--catalog-primary, #0d6efd);
    color: #fff;
    border-color: var(--catalog-primary, #0d6efd);
}

/* Suggestions Dropdown */
.vehicle-search-suggestions {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    z-index: 1050;
    max-height: 300px;
    overflow-y: auto;
    background: #fff;
    border: 1px solid var(--catalog-border, #dee2e6);
    border-radius: 0 0 var(--catalog-radius, 0.5rem) var(--catalog-radius, 0.5rem);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.vehicle-search-suggestion-item {
    padding: 0.75rem 1rem;
    cursor: pointer;
    border-bottom: 1px solid #f0f0f0;
    transition: all 0.2s ease;
}

.vehicle-search-suggestion-item:hover {
    background: #e7f3ff;
}

/* Results Modal */
.vehicle-search-modal {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.6);
    z-index: 1050;
    display: flex;
    align-items: center;
    justify-content: center;
    backdrop-filter: blur(3px);
}

.vehicle-search-modal-content {
    background: #fff;
    border-radius: 1rem;
    max-width: 800px;
    max-height: 80vh;
    width: 95%;
    overflow: hidden;
    box-shadow: 0 20px 60px rgba(0,0,0,0.3);
}

/* Result Card */
.vehicle-search-result-card {
    background: #fff;
    border: 1px solid var(--catalog-border, #e9ecef);
    border-radius: var(--catalog-radius, 0.65rem);
    padding: 1rem;
    margin-bottom: 0.75rem;
    transition: all 0.2s ease;
    cursor: pointer;
}

.vehicle-search-result-card:hover {
    border-color: var(--catalog-primary, #0d6efd);
    box-shadow: 0 3px 10px rgba(13,110,253,0.12);
}

.vehicle-search-result-card .result-badges {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    flex-wrap: wrap;
    margin-bottom: 0.5rem;
}

.vehicle-search-result-card .result-title {
    font-weight: 600;
    font-size: 0.95rem;
    margin-bottom: 0.25rem;
    color: var(--catalog-text, #333);
}

.vehicle-search-result-card .result-applicability {
    font-size: 0.85rem;
    color: var(--catalog-text-muted, #666);
}
</style>

@push('scripts')
<script>
(function() {
    const uniqueId = '{{ $uniqueId }}';
    const catalogCode = '{{ $catalogCode }}';
    const wrapper = document.getElementById('vehicleSearchWrapper' + uniqueId);
    if (!wrapper) return;

    const input = document.getElementById('vehicleSearchInput' + uniqueId);
    const searchBtn = document.getElementById('vehicleSearchBtn' + uniqueId);
    const suggestionsDropdown = document.getElementById('vehicleSuggestions' + uniqueId);
    const errorDiv = document.getElementById('vehicleError' + uniqueId);
    const loadingDiv = document.getElementById('vehicleLoading' + uniqueId);
    const resultsModal = document.getElementById('vehicleResultsModal' + uniqueId);
    const resultsContainer = document.getElementById('resultsContainer' + uniqueId);
    const typeNumberBtn = document.getElementById('typeNumber' + uniqueId);
    const typeLabelBtn = document.getElementById('typeLabel' + uniqueId);
    const searchIcon = document.getElementById('searchIcon' + uniqueId);
    const searchHelp = document.getElementById('searchHelp' + uniqueId);
    const closeModalBtn = document.getElementById('closeResultsModal' + uniqueId);
    const closeResultsBtn = document.getElementById('closeResultsBtn' + uniqueId);

    let searchType = 'number';
    let searchTimeout = null;

    function setSearchType(type) {
        searchType = type;
        typeNumberBtn.classList.toggle('active', type === 'number');
        typeLabelBtn.classList.toggle('active', type === 'label');
        searchIcon.className = 'fas ' + (type === 'number' ? 'fa-hashtag' : 'fa-tag') + ' text-muted search-type-icon';
        input.placeholder = type === 'number' ? '{{ __("ui.enter_part_number") }}' : '{{ __("ui.enter_part_name") }}';
        searchHelp.innerHTML = '<i class="fas fa-info-circle"></i> ' + (type === 'number' ? '{{ __("ui.part_number_help") }}' : '{{ __("ui.part_name_help") }}');
        hideSuggestions();
        hideError();
    }

    typeNumberBtn.addEventListener('click', () => setSearchType('number'));
    typeLabelBtn.addEventListener('click', () => setSearchType('label'));

    function doSearch() {
        const query = input.value.trim();
        const minLength = searchType === 'number' ? 5 : 2;

        if (query.length < minLength) {
            showError(searchType === 'number'
                ? '{{ __("Part number must be at least 5 characters") }}'
                : '{{ __("Part name must be at least 2 characters") }}');
            return;
        }

        hideError();
        hideSuggestions();
        setLoading(true);

        fetch('{{ route("api.vehicle.search") }}?query=' + encodeURIComponent(query) + '&catalog=' + encodeURIComponent(catalogCode) + '&type=' + searchType, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            setLoading(false);

            if (!data.success) {
                showError(data.message || '{{ __("No results found") }}');
                return;
            }

            if (data.single && data.redirect_url) {
                window.location.href = data.redirect_url;
                return;
            }

            if (data.results && data.results.length > 0) {
                showResultsModal(data.results);
            } else {
                showError('{{ __("No results found") }}');
            }
        })
        .catch(error => {
            setLoading(false);
            showError('{{ __("An error occurred. Please try again.") }}');
            console.error('Vehicle Search Error:', error);
        });
    }

    function fetchSuggestions() {
        if (searchType !== 'label') return;

        const query = input.value.trim();
        if (query.length < 2) {
            hideSuggestions();
            return;
        }

        fetch('{{ route("api.vehicle.suggestions") }}?query=' + encodeURIComponent(query) + '&catalog=' + encodeURIComponent(catalogCode) + '&type=' + searchType, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.results && data.results.length > 0) {
                showSuggestions(data.results);
            } else {
                hideSuggestions();
            }
        })
        .catch(error => {
            console.error('Suggestions Error:', error);
        });
    }

    function showSuggestions(results) {
        suggestionsDropdown.innerHTML = '';
        results.slice(0, 20).forEach(function(suggestion) {
            const item = document.createElement('div');
            item.className = 'vehicle-search-suggestion-item';
            item.innerHTML = '<i class="fas fa-search text-primary me-2"></i>' + escapeHtml(suggestion);
            item.addEventListener('click', function() {
                input.value = suggestion;
                hideSuggestions();
                doSearch();
            });
            suggestionsDropdown.appendChild(item);
        });
        suggestionsDropdown.classList.remove('d-none');
    }

    function hideSuggestions() {
        suggestionsDropdown.classList.add('d-none');
    }

    function showResultsModal(results) {
        resultsContainer.innerHTML = '';
        wrapper.querySelector('.results-count').textContent = results.length;

        results.forEach(function(result) {
            const card = document.createElement('div');
            card.className = 'vehicle-search-result-card';
            card.innerHTML = `
                <div class="d-flex justify-content-between align-items-start">
                    <div class="result-badges">
                        <span class="catalog-badge catalog-badge-light">
                            <i class="fas fa-tag me-1"></i>
                            ${escapeHtml(result.callout)}
                        </span>
                        <span class="catalog-badge catalog-badge-secondary">
                            {{ __("ui.qty") }}: ${result.qty || '—'}
                        </span>
                        ${result.category_code ? `<span class="catalog-badge" style="background: #d1ecf1; color: #0c5460;">
                            <i class="fas fa-folder me-1"></i>
                            ${escapeHtml(result.category_code)}
                        </span>` : ''}
                    </div>
                    ${result.url ? `<a href="${result.url}" class="catalog-btn catalog-btn-primary btn-sm">
                        <i class="fas fa-arrow-right me-1"></i>
                        <span class="d-none d-md-inline">{{ __("ui.open") }}</span>
                    </a>` : ''}
                </div>
                <div class="result-title">${escapeHtml(getLocalizedLabel(result))}</div>
                ${result.applicability ? `<div class="result-applicability">${escapeHtml(result.applicability)}</div>` : ''}
            `;

            if (result.url) {
                card.addEventListener('click', function(e) {
                    if (e.target.tagName !== 'A' && !e.target.closest('a')) {
                        window.location.href = result.url;
                    }
                });
            }

            resultsContainer.appendChild(card);
        });

        resultsModal.classList.remove('d-none');
    }

    function hideResultsModal() {
        resultsModal.classList.add('d-none');
    }

    function showError(message) {
        errorDiv.querySelector('.error-text').textContent = message;
        errorDiv.classList.remove('d-none');
    }

    function hideError() {
        errorDiv.classList.add('d-none');
    }

    function setLoading(show) {
        if (show) {
            loadingDiv.classList.remove('d-none');
            searchBtn.querySelector('.search-icon').classList.add('d-none');
            searchBtn.querySelector('.loading-spinner').classList.remove('d-none');
            searchBtn.disabled = true;
        } else {
            loadingDiv.classList.add('d-none');
            searchBtn.querySelector('.search-icon').classList.remove('d-none');
            searchBtn.querySelector('.loading-spinner').classList.add('d-none');
            searchBtn.disabled = false;
        }
    }

    function getLocalizedLabel(result) {
        const lang = document.documentElement.lang || 'en';
        if (lang === 'ar' && result.label_ar) {
            return result.label_ar;
        }
        return result.label_en || result.label_ar || '';
    }

    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    searchBtn.addEventListener('click', doSearch);

    input.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            doSearch();
        }
    });

    input.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(fetchSuggestions, 300);
    });

    closeModalBtn.addEventListener('click', hideResultsModal);
    closeResultsBtn.addEventListener('click', hideResultsModal);

    resultsModal.addEventListener('click', function(e) {
        if (e.target === resultsModal) {
            hideResultsModal();
        }
    });

    document.addEventListener('click', function(e) {
        if (!wrapper.contains(e.target)) {
            hideSuggestions();
        }
    });

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            hideSuggestions();
            hideResultsModal();
        }
    });
})();
</script>
@endpush
