
<div class="part-search-ajax-wrapper" id="partSearchWrapper<?php echo e($uniqueId ?? 'default'); ?>">
    <style>
        .part-search-ajax-wrapper .search-container {
            position: relative;
        }
        .part-search-ajax-wrapper .input-group {
            border-radius: 0.5rem;
            overflow: hidden;
            border: 2px solid var(--bs-primary, #0d6efd);
            background: #fff;
            transition: all 0.3s ease;
        }
        .part-search-ajax-wrapper .input-group:focus-within {
            border-color: #0b5ed7;
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.15);
        }
        .part-search-ajax-wrapper .input-group-text {
            border: none;
            background: transparent;
        }
        .part-search-ajax-wrapper .form-control {
            border: none;
            font-size: 1.1rem;
            font-weight: 500;
        }
        .part-search-ajax-wrapper .form-control:focus {
            box-shadow: none;
        }
        .part-search-ajax-wrapper .suggestions-dropdown {
            position: absolute;
            top: calc(100% + 0.5rem);
            left: 0;
            right: 0;
            z-index: 1050;
            max-height: 400px;
            overflow-y: auto;
            border-radius: 0.5rem;
            animation: slideDown 0.2s ease;
        }
        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .part-search-ajax-wrapper .suggestion-item {
            padding: 1rem;
            cursor: pointer;
            transition: all 0.2s ease;
            border-bottom: 1px solid #f0f0f0;
        }
        .part-search-ajax-wrapper .suggestion-item:last-child {
            border-bottom: none;
        }
        .part-search-ajax-wrapper .suggestion-item:hover {
            background: linear-gradient(to right, rgba(13, 110, 253, 0.05), transparent);
            padding-left: 1.25rem;
        }
        .part-search-ajax-wrapper .suggestion-icon {
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(13, 110, 253, 0.1);
            border-radius: 0.375rem;
        }
    </style>

    <div class="search-container">
        <div class="input-group input-group-lg shadow-sm">
            <span class="input-group-text bg-white border-end-0 ps-4">
                <i class="fas fa-search text-primary"></i>
            </span>
            <input
                type="text"
                class="form-control form-control-lg border-start-0 ps-0 part-input"
                placeholder="<?php echo e(__('Enter part number or name')); ?>"
                id="partInput<?php echo e($uniqueId ?? 'default'); ?>"
                autocomplete="off"
            >
            <button class="btn btn-primary px-4 part-search-btn" type="button" id="partSearchBtn<?php echo e($uniqueId ?? 'default'); ?>">
                <i class="fas fa-search me-2 search-icon"></i>
                <span class="spinner-border spinner-border-sm me-2 d-none loading-spinner" role="status"></span>
                <span class="d-none d-sm-inline"><?php echo e(__('Search')); ?></span>
            </button>
        </div>

        
        <div class="suggestions-dropdown card shadow-lg d-none" id="partSuggestions<?php echo e($uniqueId ?? 'default'); ?>">
            <div class="card-body p-0">
                <div class="list-group list-group-flush suggestions-list"></div>
            </div>
        </div>
    </div>

    
    <div class="text-center mt-3">
        <p class="mb-0">
            <i class="fas fa-info-circle me-1"></i>
            <span class="text-muted"><?php echo e(__('Example :')); ?></span>
            <code class="bg-light px-2 py-1 rounded ms-1" dir="ltr">1172003JXM</code>
        </p>
    </div>

    <script>
(function() {
    const uniqueId = '<?php echo e($uniqueId ?? "default"); ?>';
    const wrapper = document.getElementById('partSearchWrapper' + uniqueId);
    if (!wrapper) return;

    const input = wrapper.querySelector('.part-input');
    const searchBtn = wrapper.querySelector('.part-search-btn');
    const suggestionsDropdown = wrapper.querySelector('.suggestions-dropdown');
    const suggestionsList = wrapper.querySelector('.suggestions-list');

    let searchTimeout = null;
    let currentResults = [];

    // دالة البحث
    function searchPart(autoSearch = false) {
        const query = input.value.trim();

        if (query.length < 2) {
            hideSuggestions();
            return;
        }

        if (!autoSearch) {
            // عند الضغط على البحث، اذهب لصفحة النتائج مباشرة
            if (currentResults.length > 0) {
                window.location.href = '<?php echo e(url("result")); ?>/' + encodeURIComponent(currentResults[0].sku);
                return;
            }
        }

        setLoading(true);

        fetch('<?php echo e(route("api.search.part")); ?>?query=' + encodeURIComponent(query), {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            setLoading(false);
            currentResults = data.results || [];

            if (currentResults.length > 0) {
                showSuggestions(currentResults);
            } else {
                hideSuggestions();
            }
        })
        .catch(error => {
            setLoading(false);
            console.error('Part Search Error:', error);
        });
    }

    // عرض الاقتراحات
    function showSuggestions(results) {
        suggestionsList.innerHTML = '';

        results.forEach(function(result) {
            const item = document.createElement('div');
            item.className = 'list-group-item list-group-item-action border-0 suggestion-item';
            item.innerHTML = `
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0 me-3">
                        <div class="suggestion-icon">
                            <i class="fas fa-cube text-primary"></i>
                        </div>
                    </div>
                    <div class="flex-grow-1">
                        <div class="fw-bold text-primary mb-1">${escapeHtml(result.sku)}</div>
                        <div class="text-muted small">${escapeHtml(getLocalizedLabel(result))}</div>
                    </div>
                    <div class="flex-shrink-0">
                        <i class="fas fa-chevron-right text-muted"></i>
                    </div>
                </div>
            `;

            item.addEventListener('click', function() {
                window.location.href = '<?php echo e(url("result")); ?>/' + encodeURIComponent(result.sku);
            });

            suggestionsList.appendChild(item);
        });

        suggestionsDropdown.classList.remove('d-none');
    }

    function hideSuggestions() {
        suggestionsDropdown.classList.add('d-none');
        suggestionsList.innerHTML = '';
    }

    function setLoading(show) {
        if (show) {
            searchBtn.querySelector('.search-icon').classList.add('d-none');
            searchBtn.querySelector('.loading-spinner').classList.remove('d-none');
        } else {
            searchBtn.querySelector('.search-icon').classList.remove('d-none');
            searchBtn.querySelector('.loading-spinner').classList.add('d-none');
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

    // Event Listeners
    searchBtn.addEventListener('click', function() {
        searchPart(false);
    });

    input.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            searchPart(false);
        }
    });

    // البحث التلقائي أثناء الكتابة
    input.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(function() {
            searchPart(true);
        }, 300);
    });

    // إخفاء الاقتراحات عند النقر خارجها
    document.addEventListener('click', function(e) {
        if (!wrapper.contains(e.target)) {
            hideSuggestions();
        }
    });

    // إظهار الاقتراحات عند التركيز
    input.addEventListener('focus', function() {
        if (currentResults.length > 0) {
            showSuggestions(currentResults);
        }
    });
})();
    </script>
</div>
<?php /**PATH C:\Users\hp\Herd\new\resources\views/includes/frontend/search-part-ajax.blade.php ENDPATH**/ ?>