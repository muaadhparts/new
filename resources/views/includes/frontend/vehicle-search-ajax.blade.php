{{-- Vehicle Search Component - AJAX Based --}}
{{-- Uses catalog-unified.css for styling --}}
@php
    // Handle catalog as object or string code
    if (is_object($catalog)) {
        $catalogCode = $catalog->code ?? '';
        $brandName = $catalog->brand ? $catalog->brand->localized_name : '';
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
    <div class="d-flex gap-2 mb-3 search-type-toggle">
        <button type="button" class="m-btn m-btn--ghost m-btn--sm search-type-btn active" data-type="number" id="typeNumber{{ $uniqueId }}">
            <i class="fas fa-hashtag me-1"></i>
            <span class="search-type-text">{{ __('ui.part_number') }}</span>
        </button>
        <button type="button" class="m-btn m-btn--ghost m-btn--sm search-type-btn" data-type="label" id="typeLabel{{ $uniqueId }}">
            <i class="fas fa-tag me-1"></i>
            <span class="search-type-text">{{ __('ui.part_name') }}</span>
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
            <button class="m-btn m-btn--primary" type="button" id="vehicleSearchBtn{{ $uniqueId }}">
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

</div>

{{-- Results Modal - Bootstrap Modal (outside wrapper for proper z-index) --}}
<div class="modal fade" id="vehicleResultsModal{{ $uniqueId }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-fullscreen-sm-down modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content shadow-lg rounded-0 rounded-md-3 border-0">
            {{-- Header --}}
            <div class="modal-header bg-primary text-white border-0 py-2 py-md-3">
                <h5 class="modal-title fs-6 fs-md-5 mb-0">
                    <i class="fas fa-search me-2"></i>
                    {{ __('ui.select_matching_callout') }}
                    <span class="badge bg-white text-primary ms-2 results-count">0</span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            {{-- Body --}}
            <div class="modal-body p-0 bg-light">
                <div class="p-3" id="resultsContainer{{ $uniqueId }}"></div>
            </div>
        </div>
    </div>
</div>

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

    // Validate critical elements exist
    if (!input || !searchBtn || !typeNumberBtn || !typeLabelBtn) {
        console.error('Vehicle Search: Required elements not found for uniqueId:', uniqueId);
        return;
    }

    // ========== ALL VARIABLES MUST BE DECLARED HERE ==========
    // (Before any function that might use them)
    let suggestionIndex = -1;
    let resultsModalInstance = null;
    let searchTimeout = null;
    let searchAbortController = null;
    let searchFromSuggestion = false;
    let currentFocusIndex = -1;

    // Storage keys
    const SEARCH_TYPE_KEY = 'vehicleSearchType';
    const LAST_SEARCH_KEY = 'vehicleLastSearch_' + catalogCode;

    // Get saved search type from localStorage or default to 'number'
    let searchType = localStorage.getItem(SEARCH_TYPE_KEY) || 'number';

    // Restore last search query (only for number search)
    const lastSearch = localStorage.getItem(LAST_SEARCH_KEY);
    if (lastSearch) {
        try {
            const { query, type } = JSON.parse(lastSearch);
            // Only restore if it was a number search
            if (query && type === 'number') {
                input.value = query;
                searchType = type;
            }
        } catch (e) {}
    }

    function setSearchType(type, clearInput = false) {
        // If changing type, clear the input
        if (clearInput && searchType !== type) {
            input.value = '';
            hideSuggestions();
        }

        searchType = type;

        // Save to localStorage for persistence across pages
        localStorage.setItem(SEARCH_TYPE_KEY, type);

        typeNumberBtn.classList.toggle('active', type === 'number');
        typeLabelBtn.classList.toggle('active', type === 'label');
        searchIcon.className = 'fas ' + (type === 'number' ? 'fa-hashtag' : 'fa-tag') + ' text-muted search-type-icon';
        input.placeholder = type === 'number' ? '{{ __("ui.enter_part_number") }}' : '{{ __("ui.enter_part_name") }}';
        searchHelp.innerHTML = '<i class="fas fa-info-circle"></i> ' + (type === 'number' ? '{{ __("ui.part_number_help") }}' : '{{ __("ui.part_name_help") }}');
        hideSuggestions();
        hideError();
    }

    // Initialize with saved search type (without clearing input)
    setSearchType(searchType, false);

    // Click handlers - clear input when switching types
    typeNumberBtn.addEventListener('click', () => setSearchType('number', true));
    typeLabelBtn.addEventListener('click', () => setSearchType('label', true));

    function doSearch() {
        const query = input.value.trim();

        // For label search, only allow search from suggestions
        if (searchType === 'label' && !searchFromSuggestion) {
            showError('{{ __("Please select from suggestions") }}');
            return;
        }
        searchFromSuggestion = false; // Reset flag

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

        // Cancel any pending search
        if (searchAbortController) {
            searchAbortController.abort();
        }
        searchAbortController = new AbortController();

        fetch('{{ route("api.vehicle.search") }}?query=' + encodeURIComponent(query) + '&catalog=' + encodeURIComponent(catalogCode) + '&type=' + searchType, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            signal: searchAbortController.signal
        })
        .then(response => response.json())
        .then(data => {
            setLoading(false);

            if (!data.success) {
                showError(data.message || '{{ __("No results found") }}');
                return;
            }

            // Save successful search (only for number search)
            if (searchType === 'number') {
                localStorage.setItem(LAST_SEARCH_KEY, JSON.stringify({
                    query: query,
                    type: searchType
                }));
            }

            if (data.single && data.redirect_url) {
                // Store callout info in sessionStorage
                if (data.callout_info) {
                    sessionStorage.setItem('autoOpenCallout', JSON.stringify(data.callout_info));
                }
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
            // Ignore aborted requests
            if (error.name === 'AbortError') return;

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
        suggestionIndex = -1;

        results.slice(0, 20).forEach(function(suggestion, index) {
            const item = document.createElement('div');
            item.className = 'vehicle-search-suggestion-item';
            item.setAttribute('data-index', index);
            item.innerHTML = '<i class="fas fa-search text-primary me-2"></i>' + escapeHtml(suggestion);
            item.addEventListener('click', function() {
                selectSuggestion(suggestion);
            });
            suggestionsDropdown.appendChild(item);
        });
        suggestionsDropdown.classList.remove('d-none');
        suggestionsDropdown.parentElement.classList.add('suggestions-open');
    }

    function selectSuggestion(suggestion) {
        input.value = suggestion;
        hideSuggestions();
        searchFromSuggestion = true;
        doSearch();
    }

    function highlightSuggestion(index) {
        const items = suggestionsDropdown.querySelectorAll('.vehicle-search-suggestion-item');
        items.forEach((item, i) => {
            item.classList.toggle('active', i === index);
        });
        if (items[index]) {
            items[index].scrollIntoView({ block: 'nearest' });
        }
    }

    function hideSuggestions() {
        suggestionsDropdown.classList.add('d-none');
        suggestionsDropdown.parentElement.classList.remove('suggestions-open');
        suggestionIndex = -1;
    }

    function showResultsModal(results) {
        resultsContainer.innerHTML = '';
        resultsModal.querySelector('.results-count').textContent = results.length;

        results.forEach(function(result, index) {
            const card = document.createElement('div');
            card.className = 'card mb-2 border hover-shadow cursor-pointer result-card';
            card.setAttribute('tabindex', '0');
            card.setAttribute('role', 'button');
            card.setAttribute('data-index', index);
            card.innerHTML = `
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div class="d-flex flex-wrap gap-1">
                            <span class="badge bg-primary">
                                <i class="fas fa-tag me-1"></i>${escapeHtml(result.callout)}
                            </span>
                            <span class="badge bg-secondary">
                                {{ __("ui.qty") }}: ${result.qty || '—'}
                            </span>
                            ${result.category_code ? `<span class="badge bg-info">
                                <i class="fas fa-folder me-1"></i>${escapeHtml(result.category_code)}
                            </span>` : ''}
                            ${(result.cat_begin || result.cat_end) ? `<span class="badge bg-warning text-dark">
                                <i class="fas fa-calendar-alt me-1"></i>${result.cat_begin || '?'} - ${result.cat_end || '?'}
                            </span>` : ''}
                        </div>
                        ${result.url ? `<button type="button" class="btn btn-sm btn-primary result-go-btn" data-url="${result.url.split('?')[0]}" data-callout="${escapeHtml(result.callout)}" data-section="${result.section_id || ''}" data-category="${result.category_id || ''}" data-catcode="${escapeHtml(result.category_code || '')}">
                            <i class="fas fa-arrow-right"></i>
                        </button>` : ''}
                    </div>
                    <h6 class="mb-1">${escapeHtml(getLocalizedLabel(result))}</h6>
                    ${result.applicability ? `<small class="text-muted">${escapeHtml(result.applicability)}</small>` : ''}
                </div>
            `;

            if (result.url) {
                card.addEventListener('click', function(e) {
                    // Skip if clicked on the go button (it has its own handler)
                    if (e.target.closest('.result-go-btn')) return;

                    // Store callout intent in sessionStorage
                    sessionStorage.setItem('autoOpenCallout', JSON.stringify({
                        callout: result.callout,
                        section_id: result.section_id,
                        category_id: result.category_id,
                        category_code: result.category_code
                    }));
                    // Navigate to clean URL (remove query params)
                    const cleanUrl = result.url.split('?')[0];
                    window.location.href = cleanUrl;
                });
            }

            resultsContainer.appendChild(card);
        });

        // Handle button clicks (event delegation)
        resultsContainer.querySelectorAll('.result-go-btn').forEach(function(btn) {
            btn.addEventListener('click', function(e) {
                e.stopPropagation();
                sessionStorage.setItem('autoOpenCallout', JSON.stringify({
                    callout: btn.dataset.callout,
                    section_id: btn.dataset.section,
                    category_id: btn.dataset.category,
                    category_code: btn.dataset.catcode
                }));
                window.location.href = btn.dataset.url;
            });
        });

        // Show Bootstrap Modal
        if (!resultsModalInstance) {
            resultsModalInstance = new bootstrap.Modal(resultsModal);
        }
        resultsModalInstance.show();

        // Focus first card after modal is shown
        resultsModal.addEventListener('shown.bs.modal', function onShown() {
            const firstCard = resultsContainer.querySelector('.result-card');
            if (firstCard) firstCard.focus();
            resultsModal.removeEventListener('shown.bs.modal', onShown);
        }, { once: true });
    }

    // Keyboard navigation for results
    function handleResultsKeyboard(e) {
        const cards = resultsContainer.querySelectorAll('.result-card');
        if (!cards.length) return;

        if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
            e.preventDefault();
            const direction = e.key === 'ArrowDown' ? 1 : -1;
            currentFocusIndex = Math.max(0, Math.min(cards.length - 1, currentFocusIndex + direction));
            cards[currentFocusIndex].focus();
            cards[currentFocusIndex].scrollIntoView({ block: 'nearest', behavior: 'smooth' });
        } else if (e.key === 'Enter' && document.activeElement.classList.contains('result-card')) {
            e.preventDefault();
            document.activeElement.click();
        }
    }

    resultsModal.addEventListener('keydown', handleResultsKeyboard);

    // Reset focus index when modal opens
    resultsModal.addEventListener('show.bs.modal', function() {
        currentFocusIndex = 0;
    });

    // Focus trap - cycle through focusable elements
    resultsModal.addEventListener('keydown', function(e) {
        if (e.key !== 'Tab') return;

        const focusableElements = resultsModal.querySelectorAll(
            '.result-card, .btn-close, .result-go-btn, button:not([disabled]), [tabindex]:not([tabindex="-1"])'
        );
        if (!focusableElements.length) return;

        const firstEl = focusableElements[0];
        const lastEl = focusableElements[focusableElements.length - 1];

        if (e.shiftKey && document.activeElement === firstEl) {
            e.preventDefault();
            lastEl.focus();
        } else if (!e.shiftKey && document.activeElement === lastEl) {
            e.preventDefault();
            firstEl.focus();
        }
    });

    // Return focus to search input when modal closes
    resultsModal.addEventListener('hidden.bs.modal', function() {
        input.focus();
    });

    function hideResultsModal() {
        if (resultsModalInstance) {
            resultsModalInstance.hide();
        }
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

    input.addEventListener('keydown', function(e) {
        const items = suggestionsDropdown.querySelectorAll('.vehicle-search-suggestion-item');
        const isOpen = !suggestionsDropdown.classList.contains('d-none');

        if (e.key === 'ArrowDown' && isOpen && items.length) {
            e.preventDefault();
            suggestionIndex = Math.min(suggestionIndex + 1, items.length - 1);
            highlightSuggestion(suggestionIndex);
        } else if (e.key === 'ArrowUp' && isOpen && items.length) {
            e.preventDefault();
            suggestionIndex = Math.max(suggestionIndex - 1, 0);
            highlightSuggestion(suggestionIndex);
        } else if (e.key === 'Enter') {
            e.preventDefault();
            if (isOpen && suggestionIndex >= 0 && items[suggestionIndex]) {
                // Select highlighted suggestion
                const text = items[suggestionIndex].textContent.trim();
                selectSuggestion(text);
            } else {
                doSearch();
            }
        } else if (e.key === 'Escape' && isOpen) {
            hideSuggestions();
        }
    });

    input.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(fetchSuggestions, 300);
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
