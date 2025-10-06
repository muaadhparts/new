/**
 * تحسينات الواجهة الأمامية لمكون البحث عن المركبات
 * يتضمن Debouncing، التحميل التدريجي، وتحسينات UX
 */

class VehicleSearchOptimizer {
    constructor(componentId = 'vehicle-search-box') {
        this.componentId = componentId;
        this.debounceTimer = null;
        this.debounceDelay = 300; // 300ms
        this.isSearching = false;
        this.lastQuery = '';
        this.intersectionObserver = null;
        
        this.init();
    }

    /**
     * تهيئة المحسن
     */
    init() {
        this.setupDebouncing();
        this.setupInfiniteScroll();
        this.setupKeyboardNavigation();
        this.setupLoadingStates();
        this.setupErrorHandling();
    }

    /**
     * إعداد Debouncing للبحث
     */
    setupDebouncing() {
        const searchInput = document.querySelector(`#${this.componentId} input[wire\\:model="query"]`);
        
        if (searchInput) {
            // إزالة المستمع الافتراضي لـ Livewire
            searchInput.removeEventListener('input', this.handleLivewireInput);
            
            // إضافة مستمع مخصص مع Debouncing
            searchInput.addEventListener('input', (e) => {
                this.handleDebouncedInput(e);
            });

            // إضافة مستمع للضغط على Enter
            searchInput.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    this.performImmediateSearch();
                }
            });
        }
    }

    /**
     * معالجة الإدخال مع Debouncing
     */
    handleDebouncedInput(event) {
        const query = event.target.value.trim();
        
        // إلغاء المؤقت السابق
        if (this.debounceTimer) {
            clearTimeout(this.debounceTimer);
        }

        // إذا كان النص قصيراً، امسح النتائج فوراً
        if (query.length < 2) {
            this.clearResults();
            return;
        }

        // إذا كان النص مختلفاً عن البحث السابق
        if (query !== this.lastQuery) {
            this.showSearchingState();
            
            // تعيين مؤقت جديد
            this.debounceTimer = setTimeout(() => {
                this.performSearch(query);
                this.lastQuery = query;
            }, this.debounceDelay);
        }
    }

    /**
     * تنفيذ البحث الفوري
     */
    performImmediateSearch() {
        if (this.debounceTimer) {
            clearTimeout(this.debounceTimer);
        }
        
        const searchInput = document.querySelector(`#${this.componentId} input[wire\\:model="query"]`);
        if (searchInput) {
            const query = searchInput.value.trim();
            if (query.length >= 2) {
                this.performSearch(query);
            }
        }
    }

    /**
     * تنفيذ البحث
     */
    performSearch(query) {
        this.isSearching = true;
        
        // استدعاء دالة Livewire
        if (window.Livewire) {
            const component = window.Livewire.find(this.componentId);
            if (component) {
                component.call('searchFromInput');
            }
        }
    }

    /**
     * إعداد التمرير اللانهائي
     */
    setupInfiniteScroll() {
        const loadMoreTrigger = document.querySelector(`#${this.componentId} .load-more-trigger`);
        
        if (loadMoreTrigger && 'IntersectionObserver' in window) {
            this.intersectionObserver = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting && !this.isLoadingMore()) {
                        this.loadMoreResults();
                    }
                });
            }, {
                rootMargin: '100px' // تحميل قبل 100px من الوصول للعنصر
            });

            this.intersectionObserver.observe(loadMoreTrigger);
        }
    }

    /**
     * تحميل المزيد من النتائج
     */
    loadMoreResults() {
        if (window.Livewire) {
            const component = window.Livewire.find(this.componentId);
            if (component && component.get('hasMoreResults') && !component.get('isLoadingMore')) {
                component.call('loadMore');
            }
        }
    }

    /**
     * التحقق من حالة التحميل
     */
    isLoadingMore() {
        if (window.Livewire) {
            const component = window.Livewire.find(this.componentId);
            return component ? component.get('isLoadingMore') : false;
        }
        return false;
    }

    /**
     * إعداد التنقل بلوحة المفاتيح
     */
    setupKeyboardNavigation() {
        const searchInput = document.querySelector(`#${this.componentId} input[wire\\:model="query"]`);
        
        if (searchInput) {
            searchInput.addEventListener('keydown', (e) => {
                const suggestions = document.querySelectorAll(`#${this.componentId} .suggestion-item`);
                
                if (suggestions.length > 0) {
                    switch (e.key) {
                        case 'ArrowDown':
                            e.preventDefault();
                            this.navigateSuggestions(suggestions, 'down');
                            break;
                        case 'ArrowUp':
                            e.preventDefault();
                            this.navigateSuggestions(suggestions, 'up');
                            break;
                        case 'Escape':
                            this.hideSuggestions();
                            break;
                    }
                }
            });
        }
    }

    /**
     * التنقل في التلميحات
     */
    navigateSuggestions(suggestions, direction) {
        const active = document.querySelector(`#${this.componentId} .suggestion-item.active`);
        let nextIndex = 0;

        if (active) {
            const currentIndex = Array.from(suggestions).indexOf(active);
            nextIndex = direction === 'down' 
                ? (currentIndex + 1) % suggestions.length
                : (currentIndex - 1 + suggestions.length) % suggestions.length;
            
            active.classList.remove('active');
        }

        suggestions[nextIndex].classList.add('active');
        suggestions[nextIndex].scrollIntoView({ block: 'nearest' });
    }

    /**
     * إخفاء التلميحات
     */
    hideSuggestions() {
        const suggestionsContainer = document.querySelector(`#${this.componentId} .suggestions-container`);
        if (suggestionsContainer) {
            suggestionsContainer.style.display = 'none';
        }
    }

    /**
     * إعداد حالات التحميل
     */
    setupLoadingStates() {
        // مراقبة تغييرات حالة التحميل
        if (window.Livewire) {
            window.Livewire.on('loading', () => {
                this.showLoadingState();
            });

            window.Livewire.on('loaded', () => {
                this.hideLoadingState();
            });
        }
    }

    /**
     * عرض حالة البحث
     */
    showSearchingState() {
        const searchContainer = document.querySelector(`#${this.componentId} .search-container`);
        if (searchContainer) {
            searchContainer.classList.add('searching');
        }

        // إضافة مؤشر تحميل صغير
        this.addSearchingIndicator();
    }

    /**
     * إضافة مؤشر البحث
     */
    addSearchingIndicator() {
        const searchInput = document.querySelector(`#${this.componentId} input[wire\\:model="query"]`);
        if (searchInput && !searchInput.parentNode.querySelector('.search-indicator')) {
            const indicator = document.createElement('div');
            indicator.className = 'search-indicator';
            indicator.innerHTML = '<div class="spinner"></div>';
            searchInput.parentNode.appendChild(indicator);
        }
    }

    /**
     * إزالة مؤشر البحث
     */
    removeSearchingIndicator() {
        const indicator = document.querySelector(`#${this.componentId} .search-indicator`);
        if (indicator) {
            indicator.remove();
        }
    }

    /**
     * عرض حالة التحميل
     */
    showLoadingState() {
        const resultsContainer = document.querySelector(`#${this.componentId} .results-container`);
        if (resultsContainer) {
            resultsContainer.classList.add('loading');
        }
    }

    /**
     * إخفاء حالة التحميل
     */
    hideLoadingState() {
        const searchContainer = document.querySelector(`#${this.componentId} .search-container`);
        const resultsContainer = document.querySelector(`#${this.componentId} .results-container`);
        
        if (searchContainer) {
            searchContainer.classList.remove('searching');
        }
        
        if (resultsContainer) {
            resultsContainer.classList.remove('loading');
        }

        this.removeSearchingIndicator();
        this.isSearching = false;
    }

    /**
     * مسح النتائج
     */
    clearResults() {
        if (window.Livewire) {
            const component = window.Livewire.find(this.componentId);
            if (component) {
                component.set('suggestions', []);
                component.set('results', []);
            }
        }
    }

    /**
     * إعداد معالجة الأخطاء
     */
    setupErrorHandling() {
        // مراقبة أخطاء Livewire
        if (window.Livewire) {
            window.Livewire.on('error', (error) => {
                this.handleError(error);
            });
        }

        // مراقبة أخطاء الشبكة
        window.addEventListener('online', () => {
            this.handleNetworkRestore();
        });

        window.addEventListener('offline', () => {
            this.handleNetworkError();
        });
    }

    /**
     * معالجة الأخطاء
     */
    handleError(error) {
        console.error('Vehicle Search Error:', error);
        this.showErrorMessage('حدث خطأ أثناء البحث. يرجى المحاولة مرة أخرى.');
        this.hideLoadingState();
    }

    /**
     * معالجة خطأ الشبكة
     */
    handleNetworkError() {
        this.showErrorMessage('لا يوجد اتصال بالإنترنت. يرجى التحقق من الاتصال.');
    }

    /**
     * معالجة استعادة الشبكة
     */
    handleNetworkRestore() {
        this.hideErrorMessage();
    }

    /**
     * عرض رسالة خطأ
     */
    showErrorMessage(message) {
        const errorContainer = document.querySelector(`#${this.componentId} .error-container`);
        if (errorContainer) {
            errorContainer.textContent = message;
            errorContainer.style.display = 'block';
        }
    }

    /**
     * إخفاء رسالة الخطأ
     */
    hideErrorMessage() {
        const errorContainer = document.querySelector(`#${this.componentId} .error-container`);
        if (errorContainer) {
            errorContainer.style.display = 'none';
        }
    }

    /**
     * تدمير المحسن
     */
    destroy() {
        if (this.debounceTimer) {
            clearTimeout(this.debounceTimer);
        }

        if (this.intersectionObserver) {
            this.intersectionObserver.disconnect();
        }

        // إزالة مستمعي الأحداث
        const searchInput = document.querySelector(`#${this.componentId} input[wire\\:model="query"]`);
        if (searchInput) {
            searchInput.removeEventListener('input', this.handleDebouncedInput);
            searchInput.removeEventListener('keydown', this.handleKeyboardNavigation);
        }
    }
}

/**
 * CSS للتحسينات
 */
const optimizationStyles = `
<style>
/* مؤشر البحث */
.search-indicator {
    position: absolute;
    right: 10px;
    top: 50%;
    transform: translateY(-50%);
    z-index: 10;
}

.spinner {
    width: 16px;
    height: 16px;
    border: 2px solid #f3f3f3;
    border-top: 2px solid #3498db;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* حالات التحميل */
.search-container.searching input {
    padding-right: 40px;
}

.results-container.loading {
    opacity: 0.6;
    pointer-events: none;
}

/* التلميحات */
.suggestion-item {
    padding: 8px 12px;
    cursor: pointer;
    transition: background-color 0.2s;
}

.suggestion-item:hover,
.suggestion-item.active {
    background-color: #f8f9fa;
}

/* التمرير اللانهائي */
.load-more-trigger {
    height: 20px;
    margin: 10px 0;
}

.load-more-spinner {
    text-align: center;
    padding: 20px;
}

/* رسائل الخطأ */
.error-container {
    background-color: #f8d7da;
    color: #721c24;
    padding: 10px;
    border-radius: 4px;
    margin: 10px 0;
    display: none;
}

/* تحسينات الاستجابة */
@media (max-width: 768px) {
    .suggestion-item {
        padding: 12px;
        font-size: 16px; /* منع التكبير في iOS */
    }
}

/* تحسينات الوصولية */
.suggestion-item:focus {
    outline: 2px solid #007bff;
    outline-offset: -2px;
}

/* تحسينات الأداء */
.results-container {
    contain: layout style paint;
}

.suggestion-item {
    will-change: background-color;
}
</style>
`;

// إضافة الأنماط إلى الصفحة
if (!document.querySelector('#vehicle-search-optimizations-styles')) {
    const styleElement = document.createElement('div');
    styleElement.id = 'vehicle-search-optimizations-styles';
    styleElement.innerHTML = optimizationStyles;
    document.head.appendChild(styleElement);
}

// تهيئة المحسن عند تحميل الصفحة
document.addEventListener('DOMContentLoaded', () => {
    window.vehicleSearchOptimizer = new VehicleSearchOptimizer();
});

// إعادة تهيئة المحسن عند تحديث Livewire
if (window.Livewire) {
    window.Livewire.on('component:updated', () => {
        if (window.vehicleSearchOptimizer) {
            window.vehicleSearchOptimizer.destroy();
        }
        window.vehicleSearchOptimizer = new VehicleSearchOptimizer();
    });
}

