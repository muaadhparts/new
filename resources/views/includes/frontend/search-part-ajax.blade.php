{{-- Part Search Component - AJAX Based --}}
{{-- Styles in MUAADH.css Section 32: Search Components --}}
<div class="muaadh-search-wrapper" id="partSearchWrapper{{ $uniqueId ?? 'default' }}">
    <div class="muaadh-search-container">
        <div class="muaadh-search-input-group">
            <span class="muaadh-search-icon">
                <i class="fas fa-search"></i>
            </span>
            <input
                type="text"
                class="muaadh-search-input part-input"
                placeholder="{{ __('Enter part number or name') }}"
                id="partInput{{ $uniqueId ?? 'default' }}"
                autocomplete="off"
                value="{{ $initialValue ?? '' }}"
            >
            <button class="muaadh-search-btn part-search-btn" type="button" id="partSearchBtn{{ $uniqueId ?? 'default' }}">
                <i class="fas fa-search me-2 search-icon"></i>
                <span class="spinner-border spinner-border-sm me-2 d-none loading-spinner" role="status"></span>
                <span class="d-none d-sm-inline">{{ __('Search') }}</span>
            </button>
        </div>

        {{-- Suggestions Dropdown --}}
        <div class="muaadh-suggestions-dropdown d-none" id="partSuggestions{{ $uniqueId ?? 'default' }}">
            <div class="muaadh-suggestions-list suggestions-list"></div>
        </div>
    </div>

    {{-- Search Hint --}}
    <div class="muaadh-search-hint">
        <i class="fas fa-info-circle me-1"></i>
        <span>{{ __('Example :') }}</span>
        <code dir="ltr">1172003JXM</code>
    </div>

    <script>
(function() {
    const uniqueId = '{{ $uniqueId ?? "default" }}';
    const wrapper = document.getElementById('partSearchWrapper' + uniqueId);
    if (!wrapper) return;

    const input = wrapper.querySelector('.part-input');
    const searchBtn = wrapper.querySelector('.part-search-btn');
    const suggestionsDropdown = wrapper.querySelector('.muaadh-suggestions-dropdown');
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
            // عند الضغط على البحث، اذهب لصفحة نتائج البحث الجديدة
            window.location.href = '{{ route("front.search-results") }}?q=' + encodeURIComponent(query);
            return;
        }

        setLoading(true);

        fetch('{{ route("api.search.part") }}?query=' + encodeURIComponent(query), {
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
            item.className = 'muaadh-suggestion-item';
            item.innerHTML = `
                <div class="muaadh-suggestion-icon">
                    <i class="fas fa-cube"></i>
                </div>
                <div class="muaadh-suggestion-content">
                    <div class="muaadh-suggestion-part_number">${escapeHtml(result.part_number)}</div>
                    <div class="muaadh-suggestion-label">${escapeHtml(getLocalizedLabel(result))}</div>
                </div>
                <div class="muaadh-suggestion-arrow">
                    <i class="fas fa-chevron-right"></i>
                </div>
            `;

            item.addEventListener('click', function() {
                // الذهاب لصفحة نتائج البحث الجديدة مع رقم القطعة
                window.location.href = '{{ route("front.search-results") }}?q=' + encodeURIComponent(result.part_number);
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
        if (lang === 'ar') {
            return result.label_ar || result.label_en || '';
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
