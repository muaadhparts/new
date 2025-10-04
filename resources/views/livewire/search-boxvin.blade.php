<div>
  <style>
  /* VIN Search Container */
  .vin-search-container {
    width: 100%;
    padding: 0 1rem;
  }

  /* Enhanced VIN Search Wrapper */
  .vin-search-enhanced {
    max-width: 800px;
    margin: 0 auto;
  }

  .vin-form-wrapper .input-group {
    border-radius: var(--border-radius-lg);
    overflow: hidden;
    border: 2px solid var(--primary-color);
    background: #fff;
    transition: all var(--transition);
  }

  .vin-form-wrapper .input-group:focus-within {
    border-color: #0b5ed7;
    box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.15);
    transform: translateY(-2px);
  }

  .vin-form-wrapper .input-group-text {
    border: none;
    background: transparent;
  }

  .vin-form-wrapper .form-control {
    border: none;
    font-size: 1.1rem;
    font-weight: 600;
    letter-spacing: 0.5px;
    font-family: 'Courier New', monospace;
  }

  .vin-form-wrapper .form-control:focus {
    box-shadow: none;
    border: none;
  }

  .vin-form-wrapper .btn-primary {
    border: none;
    font-weight: 600;
    border-radius: 0;
    padding: 0.75rem 2rem;
  }

  /* VIN Result Card */
  .vin-result-card {
    border-radius: var(--border-radius);
    cursor: pointer;
    transition: all var(--transition);
    border: 2px solid transparent;
  }

  .vin-result-card:hover {
    border-color: var(--primary-color);
    box-shadow: var(--box-shadow-lg) !important;
    transform: translateY(-4px);
  }

  .vin-icon-wrapper {
    width: 70px;
    height: 70px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: var(--border-radius);
    box-shadow: var(--box-shadow);
  }

  /* Enhanced Alert */
  .vin-search-enhanced .alert {
    border-radius: var(--border-radius);
    border: none;
  }

  /* Loading Animation */
  @keyframes pulse {
    0%, 100% {
      opacity: 1;
    }
    50% {
      opacity: 0.6;
    }
  }

  .vin-search-enhanced .spinner-border {
    animation: pulse 1.5s ease-in-out infinite;
  }

  /* Responsive Adjustments */
  @media (max-width: 768px) {
    .vin-search-container {
      padding: 0 0.5rem;
    }

    .vin-search-enhanced {
      padding: 0;
      max-width: 100%;
    }

    .vin-form-wrapper .input-group {
      border-radius: var(--border-radius);
    }

    .vin-form-wrapper .form-control {
      font-size: 0.95rem;
      padding: 0.625rem 0.5rem;
    }

    .vin-form-wrapper .btn-primary {
      padding: 0.625rem 1rem;
      font-size: 0.9rem;
    }

    .vin-form-wrapper .input-group-text {
      padding-left: 0.75rem;
      padding-right: 0.5rem;
    }

    .vin-icon-wrapper {
      width: 55px;
      height: 55px;
    }

    .vin-icon-wrapper i {
      font-size: 1.5rem !important;
    }

    .vin-result-card .card-body {
      padding: 1rem !important;
    }

    .vin-result-card h5 {
      font-size: 1rem;
    }
  }

  @media (max-width: 576px) {
    .vin-search-container {
      padding: 0;
    }

    .vin-search-enhanced {
      padding: 0;
    }

    .vin-form-wrapper .input-group {
      border-width: 1.5px;
    }

    .vin-form-wrapper .input-group-text {
      padding-left: 0.5rem;
      padding-right: 0.25rem;
    }

    .vin-form-wrapper .form-control {
      font-size: 0.875rem;
      padding: 0.5rem 0.25rem;
    }

    .vin-form-wrapper .btn-primary {
      padding: 0.5rem 0.875rem;
    }

    .vin-form-wrapper .btn-primary .me-2 {
      margin-right: 0 !important;
    }

    .vin-form-wrapper .btn-primary span {
      display: none !important;
    }

    .vin-icon-wrapper {
      width: 45px;
      height: 45px;
    }

    .vin-icon-wrapper i {
      font-size: 1.1rem !important;
    }

    .vin-result-card .card-body {
      padding: 0.875rem !important;
    }

    .vin-result-card h5 {
      font-size: 0.9rem;
    }

    .vin-result-card p {
      font-size: 0.8rem;
    }

    .vin-result-card .badge {
      font-size: 0.75rem;
      padding: 0.35rem 0.6rem !important;
    }

    .vin-search-enhanced .alert {
      font-size: 0.875rem;
    }

    .vin-search-enhanced .alert i {
      font-size: 1rem !important;
    }
  }

  @media (max-width: 375px) {
    .vin-form-wrapper .form-control {
      font-size: 0.8rem;
    }

    .vin-form-wrapper .btn-primary {
      padding: 0.5rem 0.75rem;
    }

    .vin-form-wrapper .input-group-text i {
      font-size: 0.9rem;
    }

    .vin-icon-wrapper {
      width: 40px;
      height: 40px;
    }

    .vin-icon-wrapper i {
      font-size: 1rem !important;
    }
  }
  </style>

  <div class="vin-search-container">
    <div class="vin-search-enhanced">

      {{-- Enhanced VIN Search Form --}}
      <form wire:submit.prevent="submitSearch" class="vin-form-wrapper">
      <div class="input-group input-group-lg shadow-sm">
        <span class="input-group-text bg-white border-end-0 ps-4">
          <i class="fas fa-car text-primary"></i>
        </span>
        <input
          type="text"
          class="form-control form-control-lg border-start-0 border-end-0 ps-0"
          placeholder="{{ __('Enter VIN') }}"
          wire:model.lazy="query"
          aria-label="{{ __('Search by VIN') }}"
          dir="ltr"
        >
        <button
          type="submit"
          class="btn btn-primary px-4"
          wire:loading.attr="disabled"
          wire:loading.class="disabled"
        >
          @if ($isLoading)
            <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
            <span class="d-none d-sm-inline">{{ __('Searching...') }}</span>
          @else
            <i class="fas fa-search me-2"></i>
            <span class="d-none d-sm-inline">{{ __('Search') }}</span>
          @endif
        </button>
      </div>
    </form>

    {{-- Search Hint --}}
    <div class="text-center mt-3">
      <p class="mb-0 text-muted small">
        <i class="fas fa-info-circle me-1"></i>
        <span>{{ __('Example :') }}</span>
        <code class="bg-light px-2 py-1 rounded ms-1" dir="ltr">5N1AA0NC7EN603053</code>
      </p>
    </div>

    {{-- Loading Progress Bar --}}
    @if ($isLoading && strlen($query) >= 10)
      <div class="mt-4">
        <div class="card border-0 shadow-sm">
          <div class="card-body text-center py-4">
            <div class="spinner-border text-primary mb-3" role="status">
              <span class="visually-hidden">Loading...</span>
            </div>
            <div class="progress mb-3" style="height: 8px;">
              <div class="progress-bar progress-bar-striped progress-bar-animated bg-gradient"
                   style="width: 60%; background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);">
              </div>
            </div>
            <p class="text-primary fw-bold mb-0">
              <i class="fas fa-sync fa-spin me-2"></i>
              {{ __('search.fetching_vehicle_info') }}
            </p>
          </div>
        </div>
      </div>
    @endif

    {{-- Alert Message --}}
    @if ($notFound && $userMessage)
      <div class="alert alert-warning alert-dismissible fade show mt-4 shadow-sm" role="alert">
        <div class="d-flex align-items-center">
          <i class="fas fa-exclamation-triangle me-3 fs-4"></i>
          <div class="flex-grow-1">
            <strong>{{ __('Warning') }}</strong>
            <p class="mb-0">{{ $userMessage }}</p>
          </div>
        </div>
      </div>
    @endif

    {{-- VIN Results Card --}}
    @if (!empty($results) && $is_vin)
      <div class="mt-4">
        <div class="card border-0 shadow-sm vin-result-card" wire:click="selectedVin('{{ json_encode($results) }}')" role="button">
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
                    <h5 class="mb-2 fw-bold text-primary">
                      <i class="fas fa-barcode me-2"></i>
                      {{ $results['vin'] ?? '' }}
                    </h5>
                    <p class="mb-0 text-muted">
                      <i class="fas fa-tag me-2"></i>
                      {{ $results['label_en'] ?? 'N/A' }}
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
    @endif

    </div>
  </div>
</div>
