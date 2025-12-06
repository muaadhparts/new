
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
    <style>
        .vehicle-search-ajax-wrapper {
            background: #fff;
            border-radius: 0.75rem;
            padding: 1rem;
            margin: 0.5rem auto;
            max-width: 1200px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.06);
            border: 1px solid #e9ecef;
        }
        .vehicle-search-ajax-wrapper .search-type-btn.active {
            background-color: #0d6efd;
            color: #fff;
            border-color: #0d6efd;
        }
        .vehicle-search-ajax-wrapper .suggestions-dropdown {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            z-index: 1050;
            max-height: 300px;
            overflow-y: auto;
            background: #fff;
            border: 1px solid #dee2e6;
            border-radius: 0 0 0.5rem 0.5rem;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .vehicle-search-ajax-wrapper .suggestion-item {
            padding: 0.75rem 1rem;
            cursor: pointer;
            border-bottom: 1px solid #f0f0f0;
            transition: all 0.2s ease;
        }
        .vehicle-search-ajax-wrapper .suggestion-item:hover {
            background: #e7f3ff;
        }
        .vehicle-search-ajax-wrapper .specs-bar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 0.75rem;
            padding: 0.75rem 1rem;
            margin-bottom: 1rem;
        }
        .vehicle-search-ajax-wrapper .spec-chip {
            background: rgba(255,255,255,0.95);
            border-radius: 1.5rem;
            padding: 0.35rem 0.75rem;
            font-size: 0.8rem;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            margin: 0.25rem;
        }
        .vehicle-search-ajax-wrapper .results-modal {
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
        .vehicle-search-ajax-wrapper .results-modal-content {
            background: #fff;
            border-radius: 1rem;
            max-width: 800px;
            max-height: 80vh;
            width: 95%;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        .vehicle-search-ajax-wrapper .result-card {
            border: 1px solid #e9ecef;
            border-radius: 0.65rem;
            padding: 1rem;
            margin-bottom: 0.75rem;
            transition: all 0.2s ease;
            cursor: pointer;
        }
        .vehicle-search-ajax-wrapper .result-card:hover {
            border-color: #0d6efd;
            box-shadow: 0 3px 10px rgba(13,110,253,0.12);
        }
    </style>

    

    
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

        
        <div class="suggestions-dropdown d-none" id="vehicleSuggestions<?php echo e($uniqueId); ?>"></div>
    </div>

    
    <div class="alert alert-danger d-none" id="vehicleError<?php echo e($uniqueId); ?>" role="alert">
        <i class="fas fa-exclamation-triangle me-2"></i>
        <span class="error-text"></span>
    </div>

    
    <div class="alert alert-info d-none" id="vehicleLoading<?php echo e($uniqueId); ?>" role="status">
        <div class="d-flex align-items-center gap-3">
            <div class="spinner-border text-primary" role="status"></div>
            <div><?php echo e(__('ui.searching_by_number')); ?></div>
        </div>
    </div>

    
    <div class="results-modal d-none" id="vehicleResultsModal<?php echo e($uniqueId); ?>">
        <div class="results-modal-content">
            <div class="modal-header bg-primary text-white p-3">
                <h6 class="modal-title mb-0">
                    <i class="fas fa-list-ul me-2"></i>
                    <?php echo e(__('ui.select_matching_callout')); ?>

                    <span class="badge bg-white text-primary ms-2 results-count">0</span>
                </h6>
                <button type="button" class="btn-close btn-close-white" id="closeResultsModal<?php echo e($uniqueId); ?>"></button>
            </div>
            <div class="modal-body p-3 bg-light" style="max-height: 60vh; overflow-y: auto;">
                <div class="results-container" id="resultsContainer<?php echo e($uniqueId); ?>"></div>
            </div>
            <div class="modal-footer bg-white p-2">
                <button class="btn btn-outline-secondary btn-sm" id="closeResultsBtn<?php echo e($uniqueId); ?>">
                    <i class="fas fa-times me-1"></i>
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
            item.className = 'suggestion-item';
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
            card.className = 'result-card';
            card.innerHTML = `
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <span class="badge bg-primary px-2 py-1">
                            <i class="fas fa-tag me-1"></i>
                            ${escapeHtml(result.callout)}
                        </span>
                        <span class="badge bg-secondary">
                            <?php echo e(__("ui.qty")); ?>: ${result.qty || '—'}
                        </span>
                        ${result.category_code ? `<span class="badge bg-info text-dark">
                            <i class="fas fa-folder me-1"></i>
                            ${escapeHtml(result.category_code)}
                        </span>` : ''}
                    </div>
                    ${result.url ? `<a href="${result.url}" class="btn btn-primary btn-sm">
                        <i class="fas fa-arrow-right me-1"></i>
                        <span class="d-none d-md-inline"><?php echo e(__("ui.open")); ?></span>
                    </a>` : ''}
                </div>
                <h6 class="fw-semibold mb-2">${escapeHtml(getLocalizedLabel(result))}</h6>
                ${result.applicability ? `<div class="text-muted small">${escapeHtml(result.applicability)}</div>` : ''}
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