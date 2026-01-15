@extends('layouts.front')

@section('content')
    <div class="gs-user-panel-review wow-replaced" data-wow-delay=".1s">
        <div class="container">
            <div class="d-flex">
                <!-- sidebar -->
                @include('includes.user.sidebar')
                <!-- main content -->
                <div class="gs-dashboard-user-content-wrapper gs-dashboard-outlet">
                    <!-- page name -->
                    <div class="gs-topup-name ms-0 mb-4">
                        <h3 class="ud-page-name">@lang('Purchase Tracking')</h3>
                    </div>

                    <!-- Search Box -->
                    <div class="m-card mb-4">
                        <div class="m-card__body">
                            <form id="trackingSearchForm" class="row g-3">
                                <div class="col-md-9">
                                    <input type="text"
                                           id="trackingSearchInput"
                                           class="form-control"
                                           placeholder="@lang('Enter Purchase Number or Tracking Number')"
                                           autocomplete="off">
                                </div>
                                <div class="col-md-3">
                                    <button type="submit" class="m-btn m-btn--primary w-100">
                                        <i class="fas fa-search me-2"></i>@lang('Search')
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Tracking Results Container (populated via AJAX) -->
                    <div id="trackingResults" class="d-none mb-4"></div>

                    <!-- All Purchases List -->
                    <div class="m-card">
                        <div class="m-card__header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">@lang('My Purchases')</h5>
                            <span class="badge bg-secondary">{{ count($purchasesData) }}</span>
                        </div>
                        <div class="m-card__body p-0">
                            @if(count($purchasesData) > 0)
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>@lang('Purchase #')</th>
                                                <th>@lang('Date')</th>
                                                <th>@lang('Amount')</th>
                                                <th>@lang('Shipment Status')</th>
                                                <th>@lang('Actions')</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {{-- Pure DTO rendering - no model calls --}}
                                            @foreach($purchasesData as $purchaseId => $data)
                                                <tr>
                                                    <td>
                                                        <strong>{{ $data['purchaseNumber'] }}</strong>
                                                        <br>
                                                        <small class="text-muted">
                                                            <span class="badge bg-{{ $data['statusColor'] }}">
                                                                {{ $data['statusLabel'] }}
                                                            </span>
                                                        </small>
                                                    </td>
                                                    <td>
                                                        {{ $data['createdAtFormatted'] }}
                                                        <br>
                                                        <small class="text-muted">{{ $data['createdAtTime'] }}</small>
                                                    </td>
                                                    <td>
                                                        <strong>{{ $data['currencySign'] }}{{ $data['payAmount'] }}</strong>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-{{ $data['trackingStatusColor'] }}">
                                                            <i class="{{ $data['trackingStatusIcon'] }} me-1"></i>
                                                            {{ $data['trackingStatusAr'] }}
                                                        </span>
                                                        @if($data['trackingNumber'])
                                                            <br>
                                                            <small class="text-muted">
                                                                <i class="fas fa-barcode"></i>
                                                                {{ $data['trackingNumber'] }}
                                                            </small>
                                                        @endif
                                                        @if($data['trackingOccurredAtHuman'])
                                                            <br>
                                                            <small class="text-muted">
                                                                {{ $data['trackingOccurredAtHuman'] }}
                                                            </small>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        <button type="button"
                                                                class="m-btn m-btn--primary m-btn--sm track-purchase-btn"
                                                                data-purchase-number="{{ $data['purchaseNumber'] }}">
                                                            <i class="fas fa-truck me-1"></i>@lang('Track')
                                                        </button>
                                                        <a href="{{ route('user-purchase', $data['purchaseId']) }}"
                                                           class="m-btn m-btn--outline m-btn--sm">
                                                            <i class="fas fa-eye me-1"></i>@lang('Details')
                                                        </a>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <div class="text-center py-5">
                                    <i class="fas fa-box-open fa-4x text-muted mb-4"></i>
                                    <h5>@lang('No Purchases Yet')</h5>
                                    <p class="text-muted">
                                        @lang('Your purchase tracking will appear here once you make a purchase.')
                                    </p>
                                    <a href="{{ route('front.index') }}" class="m-btn m-btn--primary">
                                        <i class="fas fa-shopping-bag me-2"></i>@lang('Start Shopping')
                                    </a>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchForm = document.getElementById('trackingSearchForm');
    const searchInput = document.getElementById('trackingSearchInput');
    const resultsContainer = document.getElementById('trackingResults');
    const trackButtons = document.querySelectorAll('.track-purchase-btn');

    // Handle search form submission
    searchForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const searchValue = searchInput.value.trim();
        if (searchValue) {
            loadTracking(searchValue);
        }
    });

    // Handle track button clicks
    trackButtons.forEach(function(btn) {
        btn.addEventListener('click', function() {
            const purchaseNumber = this.dataset.purchaseNumber;
            searchInput.value = purchaseNumber;
            loadTracking(purchaseNumber);
        });
    });

    // Load tracking via AJAX
    function loadTracking(searchValue) {
        resultsContainer.innerHTML = `
            <div class="text-center py-4">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2">@lang('Loading tracking information...')</p>
            </div>
        `;
        resultsContainer.classList.remove('d-none');

        fetch("{{ url('user/purchase/trackings') }}/" + encodeURIComponent(searchValue))
            .then(response => response.text())
            .then(html => {
                resultsContainer.innerHTML = html;
                // Scroll to results
                resultsContainer.scrollIntoView({ behavior: 'smooth', block: 'start' });
            })
            .catch(error => {
                resultsContainer.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        @lang('Error loading tracking information. Please try again.')
                    </div>
                `;
            });
    }
});
</script>
@endpush
