

<?php
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
?>

<div class="vehicle-search-ajax-wrapper" id="vehicleSearchWrapper<?php echo e($uniqueId); ?>">
    

    
    <div class="d-flex gap-2 mb-3">
        <button type="button" class="btn btn-sm btn-outline-secondary search-type-btn active" data-type="number" id="typeNumber<?php echo e($uniqueId); ?>">
            <i class="fas fa-hashtag me-1"></i>
            <span class="d-none d-md-inline"><?php echo e(__('ui.part_number')); ?></span>
        </button>
        <button type="button" class="btn btn-sm btn-outline-secondary search-type-btn" data-type="label" id="typeLabel<?php echo e($uniqueId); ?>">
            <i class="fas fa-tag me-1"></i>
            <span class="d-none d-md-inline"><?php echo e(__('ui.part_name')); ?></span>
        </button>
    </div>

    
    <div class="position-relative mb-3">
        <div class="input-group shadow-sm">
            <span class="input-group-text bg-white">
                <i class="fas fa-hashtag text-muted search-type-icon" id="searchIcon<?php echo e($uniqueId); ?>"></i>
            </span>
            <input
                type="text"
                class="form-control"
                placeholder="<?php echo e(__('ui.enter_part_number')); ?>"
                id="vehicleSearchInput<?php echo e($uniqueId); ?>"
                autocomplete="off"
                dir="ltr"
            >
            <button class="btn btn-primary" type="button" id="vehicleSearchBtn<?php echo e($uniqueId); ?>">
                <i class="fas fa-search search-icon"></i>
                <span class="spinner-border spinner-border-sm d-none loading-spinner" role="status"></span>
                <span class="d-none d-lg-inline ms-2"><?php echo e(__('ui.search')); ?></span>
            </button>
        </div>
        <small class="form-text text-muted" id="searchHelp<?php echo e($uniqueId); ?>">
            <i class="fas fa-info-circle"></i>
            <?php echo e(__('ui.part_number_help')); ?>

        </small>

        
        <div class="vehicle-search-suggestions d-none" id="vehicleSuggestions<?php echo e($uniqueId); ?>"></div>
    </div>

    
    <div class="alert alert-danger d-none" id="vehicleError<?php echo e($uniqueId); ?>" role="alert">
        <i class="fas fa-exclamation-triangle me-2"></i>
        <span class="error-text"></span>
    </div>

    
    <div class="catalog-loading d-none" id="vehicleLoading<?php echo e($uniqueId); ?>" role="status">
        <div class="spinner-border text-primary" role="status"></div>
        <p><?php echo e(__('ui.searching_by_number')); ?></p>
    </div>

    
    <div class="vehicle-search-modal d-none" id="vehicleResultsModal<?php echo e($uniqueId); ?>">
        <div class="vehicle-search-modal-content">
            <div class="catalog-section-header muaadh-catalog-header-primary">
                <h5>
                    <i class="fas fa-list-ul"></i>
                    <?php echo e(__('ui.select_matching_callout')); ?>

                    <span class="badge bg-white text-primary ms-2 results-count">0</span>
                </h5>
                <button type="button" class="btn-close btn-close-white" id="closeResultsModal<?php echo e($uniqueId); ?>"></button>
            </div>
            <div class="catalog-modal-content muaadh-catalog-modal-body">
                <div class="catalog-cards" id="resultsContainer<?php echo e($uniqueId); ?>"></div>
            </div>
            <div class="catalog-section-header muaadh-catalog-header-footer">
                <span></span>
                <button class="catalog-btn catalog-btn-outline" id="closeResultsBtn<?php echo e($uniqueId); ?>">
                    <i class="fas fa-times"></i>
                    <?php echo e(__('ui.close')); ?>

                </button>
            </div>
        </div>
    </div>

</div>

<?php $__env->startPush('scripts'); ?>
<script>
(function() {
    const uniqueId = '<?php echo e($uniqueId); ?>';
    const catalogCode = '<?php echo e($catalogCode); ?>';
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
        input.placeholder = type === 'number' ? '<?php echo e(__("ui.enter_part_number")); ?>' : '<?php echo e(__("ui.enter_part_name")); ?>';
        searchHelp.innerHTML = '<i class="fas fa-info-circle"></i> ' + (type === 'number' ? '<?php echo e(__("ui.part_number_help")); ?>' : '<?php echo e(__("ui.part_name_help")); ?>');
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
                ? '<?php echo e(__("Part number must be at least 5 characters")); ?>'
                : '<?php echo e(__("Part name must be at least 2 characters")); ?>');
            return;
        }

        hideError();
        hideSuggestions();
        setLoading(true);

        fetch('<?php echo e(route("api.vehicle.search")); ?>?query=' + encodeURIComponent(query) + '&catalog=' + encodeURIComponent(catalogCode) + '&type=' + searchType, {
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
                showError(data.message || '<?php echo e(__("No results found")); ?>');
                return;
            }

            if (data.single && data.redirect_url) {
                window.location.href = data.redirect_url;
                return;
            }

            if (data.results && data.results.length > 0) {
                showResultsModal(data.results);
            } else {
                showError('<?php echo e(__("No results found")); ?>');
            }
        })
        .catch(error => {
            setLoading(false);
            showError('<?php echo e(__("An error occurred. Please try again.")); ?>');
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

        fetch('<?php echo e(route("api.vehicle.suggestions")); ?>?query=' + encodeURIComponent(query) + '&catalog=' + encodeURIComponent(catalogCode) + '&type=' + searchType, {
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
                            <?php echo e(__("ui.qty")); ?>: ${result.qty || '—'}
                        </span>
                        ${result.category_code ? `<span class="catalog-badge muaadh-catalog-badge-info">
                            <i class="fas fa-folder me-1"></i>
                            ${escapeHtml(result.category_code)}
                        </span>` : ''}
                    </div>
                    ${result.url ? `<a href="${result.url}" class="catalog-btn catalog-btn-primary btn-sm">
                        <i class="fas fa-arrow-right me-1"></i>
                        <span class="d-none d-md-inline"><?php echo e(__("ui.open")); ?></span>
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
<?php $__env->stopPush(); ?>
<?php /**PATH C:\Users\hp\Herd\new\resources\views/includes/frontend/vehicle-search-ajax.blade.php ENDPATH**/ ?>