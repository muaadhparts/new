<div class="container mb-5" style="position: relative;">
  <style>
[x-cloak] { display: none !important; }

/* Enhanced Search Wrapper */
.enhanced-search-wrapper {
  max-width: 800px;
  margin: 0 auto;
}

.search-container {
  position: relative;
}

/* Enhanced Input Group */
.enhanced-search-wrapper .input-group {
  border-radius: var(--border-radius-lg);
  overflow: hidden;
  border: 2px solid var(--primary-color);
  background: #fff;
  transition: all var(--transition);
}

.enhanced-search-wrapper .input-group:focus-within {
  border-color: #0b5ed7;
  box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.15);
  transform: translateY(-2px);
}

.enhanced-search-wrapper .input-group-text {
  border: none;
  background: transparent;
}

.enhanced-search-wrapper .form-control {
  border: none;
  font-size: 1.1rem;
  font-weight: 500;
}

.enhanced-search-wrapper .form-control:focus {
  box-shadow: none;
  border: none;
}

.enhanced-search-wrapper .btn-primary {
  border: none;
  font-weight: 600;
  border-radius: 0;
  padding: 0.75rem 2rem;
}

/* Suggestions Dropdown */
.suggestions-dropdown {
  position: absolute;
  top: calc(100% + 0.75rem);
  left: 0;
  right: 0;
  z-index: 1050;
  max-height: 400px;
  overflow-y: auto;
  border: none;
  border-radius: var(--border-radius);
  animation: slideDown 0.2s ease;
}

@keyframes slideDown {
  from {
    opacity: 0;
    transform: translateY(-10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.suggestion-item-enhanced {
  padding: 1rem;
  cursor: pointer;
  transition: all var(--transition-fast);
  border-bottom: 1px solid #f0f0f0;
}

.suggestion-item-enhanced:last-child {
  border-bottom: none;
}

.suggestion-item-enhanced:hover {
  background: linear-gradient(to right, rgba(13, 110, 253, 0.05), transparent);
  padding-left: 1.25rem;
}

.suggestion-icon {
  width: 40px;
  height: 40px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(13, 110, 253, 0.1);
  border-radius: var(--border-radius-sm);
}

.suggestion-sku {
  font-size: 1rem;
  letter-spacing: 0.5px;
}

.suggestion-label {
  font-size: 0.875rem;
  line-height: 1.4;
}

/* Search Hint */
.search-hint-enhanced {
  font-size: 0.9rem;
}

.search-hint-enhanced code {
  font-size: 0.95rem;
  font-weight: 600;
  color: var(--primary-color);
  border: 1px solid var(--border-color);
}

/* Responsive Adjustments */
@media (max-width: 768px) {
  .enhanced-search-wrapper {
    padding: 0 0.5rem;
    max-width: 100%;
  }

  .enhanced-search-wrapper .input-group {
    flex-wrap: nowrap;
    border-radius: var(--border-radius);
  }

  .enhanced-search-wrapper .form-control {
    font-size: 0.95rem;
    padding: 0.625rem 0.5rem;
  }

  .enhanced-search-wrapper .btn-primary {
    padding: 0.625rem 1rem;
    font-size: 0.9rem;
  }

  .enhanced-search-wrapper .input-group-text {
    padding-left: 0.75rem;
    padding-right: 0.5rem;
  }

  .suggestion-item-enhanced {
    padding: 0.75rem;
  }

  .suggestion-icon {
    width: 32px;
    height: 32px;
  }

  .suggestion-sku {
    font-size: 0.9rem;
  }

  .suggestion-label {
    font-size: 0.8rem;
  }
}

@media (max-width: 576px) {
  .enhanced-search-wrapper {
    padding: 0;
  }

  .enhanced-search-wrapper .input-group {
    border-width: 1.5px;
  }

  .enhanced-search-wrapper .input-group-text {
    padding-left: 0.5rem;
    padding-right: 0.25rem;
  }

  .enhanced-search-wrapper .form-control {
    font-size: 0.875rem;
    padding: 0.5rem 0.25rem;
  }

  .enhanced-search-wrapper .btn-primary {
    padding: 0.5rem 0.875rem;
  }

  .enhanced-search-wrapper .btn-primary .me-2 {
    margin-right: 0 !important;
  }

  .enhanced-search-wrapper .btn-primary span {
    display: none !important;
  }

  .search-hint-enhanced {
    font-size: 0.8rem;
  }

  .search-hint-enhanced code {
    font-size: 0.8rem;
    padding: 0.25rem 0.5rem !important;
  }

  .suggestion-item-enhanced {
    padding: 0.625rem 0.5rem;
  }

  .suggestion-icon {
    width: 28px;
    height: 28px;
    margin-right: 0.5rem !important;
  }

  .suggestion-icon i {
    font-size: 0.875rem;
  }
}

@media (max-width: 375px) {
  .enhanced-search-wrapper .form-control {
    font-size: 0.8rem;
  }

  .enhanced-search-wrapper .btn-primary {
    padding: 0.5rem 0.75rem;
  }

  .enhanced-search-wrapper .input-group-text i {
    font-size: 0.9rem;
  }
}

/* Custom Scrollbar for Suggestions */
.suggestions-dropdown::-webkit-scrollbar {
  width: 8px;
}

.suggestions-dropdown::-webkit-scrollbar-track {
  background: #f1f1f1;
  border-radius: 10px;
}

.suggestions-dropdown::-webkit-scrollbar-thumb {
  background: var(--primary-color);
  border-radius: 10px;
}

.suggestions-dropdown::-webkit-scrollbar-thumb:hover {
  background: #0b5ed7;
}

/* VIN Modal Mobile Optimization */
@media (max-width: 576px) {
  #vinSearchModal .modal-dialog {
    margin: 0.5rem;
    max-width: calc(100% - 1rem);
  }

  #vinSearchModal .modal-header {
    padding: 0.75rem 1rem;
  }

  #vinSearchModal .modal-title {
    font-size: 1rem;
  }

  #vinSearchModal .modal-body {
    padding: 1rem 0.5rem !important;
  }
}
  </style>

  {{-- Enhanced Search Box --}}
  <div class="enhanced-search-wrapper">
    <div class="search-container">
      <div class="input-group input-group-lg shadow-sm">
        <span class="input-group-text bg-white border-end-0 ps-4">
          <i class="fas fa-search text-primary"></i>
        </span>
        <input
          type="text"
          class="form-control form-control-lg border-start-0 ps-0"
          placeholder="{{ __('Enter part number or name') }}"
          wire:model.live.debounce.300ms="query"
          wire:keydown.enter="submitSearch"
          aria-label="{{ __('Search by part number or name') }}"
        >
        <button class="btn btn-primary px-4" wire:click="submitSearch" type="button">
          <i class="fas fa-search me-2"></i>
          <span class="d-none d-sm-inline">{{ __('Search') }}</span>
        </button>
      </div>

      {{-- Suggestions Dropdown --}}
      @if (!empty($results) && !empty($query))
        <div class="suggestions-dropdown card shadow-lg">
          <div class="card-body p-0">
            <div class="list-group list-group-flush">
              @foreach ($results as $result)
                <div class="list-group-item list-group-item-action border-0 suggestion-item-enhanced"
                     wire:click="selectItem('{{ $result['sku'] }}')"
                     role="button">
                  <div class="d-flex align-items-center">
                    <div class="flex-shrink-0 me-3">
                      <div class="suggestion-icon">
                        <i class="fas fa-cube text-primary"></i>
                      </div>
                    </div>
                    <div class="flex-grow-1">
                      <div class="suggestion-sku fw-bold text-primary mb-1">
                        {{ $result['sku'] }}
                      </div>
                      <div class="suggestion-label text-muted small">
                        {{ getLocalizedLabel($result) }}
                      </div>
                    </div>
                    <div class="flex-shrink-0">
                      <i class="fas fa-chevron-right text-muted"></i>
                    </div>
                  </div>
                </div>
              @endforeach
            </div>
          </div>
        </div>
      @endif
    </div>

    {{-- Search Hint --}}
    <div class="text-center mt-3">
      <p class="search-hint-enhanced mb-0">
        <i class="fas fa-info-circle me-1"></i>
        <span class="text-muted">{{ __('Example :') }}</span>
        <code class="bg-light px-2 py-1 rounded ms-1" dir="ltr">1172003JXM</code>
      </p>
    </div>
  </div>

  {{-- VIN Search Modal --}}
  <div class="modal fade" id="vinSearchModal" tabindex="-1" aria-labelledby="vinSearchModalLabel" aria-hidden="true" wire:ignore.self>
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
      <div class="modal-content modern-modal">
        <div class="modal-header modern-modal-header">
          <h5 class="modal-title" id="vinSearchModalLabel">
            <i class="fas fa-car me-2"></i>
            <span class="d-none d-sm-inline">@lang('Search by VIN')</span>
            <span class="d-sm-none">VIN</span>
          </h5>
          <button type="button" class="btn-close modern-close" data-bs-dismiss="modal" aria-label="@lang('Close')"></button>
        </div>
        <div class="modal-body modern-modal-body p-4">
          @include('includes.frontend.search-vin-ajax', ['uniqueId' => 'searchBoxModal'])
        </div>
      </div>
    </div>
  </div>

  <style>
  /* Modern Modal Styles */
  .modern-modal {
    border: none;
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
  }

  .modern-modal-header {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    color: #fff;
    padding: 1.5rem 2rem;
    border: none;
  }

  .modern-modal-header .modal-title {
    font-weight: 700;
    font-size: 1.25rem;
  }

  .modern-close {
    background: rgba(255, 255, 255, 0.2);
    border-radius: 50%;
    width: 35px;
    height: 35px;
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 1;
    transition: all 0.3s ease;
  }

  .modern-close:hover {
    background: rgba(255, 255, 255, 0.3);
    transform: rotate(90deg);
  }

  .modern-modal-body {
    background: #f8fafc;
  }

  @media (max-width: 576px) {
    .modern-modal {
      border-radius: 15px;
    }

    .modern-modal-header {
      padding: 1rem 1.5rem;
    }

    .modern-modal-body {
      padding: 1.5rem !important;
    }
  }
  </style>

</div>
