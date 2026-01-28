@extends('layouts.operator')

@section('content')
<div class="content-area">
    <div class="mr-breadcrumb">
        <div class="row">
            <div class="col-lg-12">
                <h4 class="heading">{{ __('Shipping Company Statement') }} - {{ $companyName }}</h4>
                <ul class="links">
                    <li><a href="{{ route('operator.dashboard') }}">{{ __('Dashboard') }}</a></li>
                    <li><a href="{{ route('operator.accounts.index') }}">{{ __('Accounts') }}</a></li>
                    <li><a href="{{ route('operator.accounts.shipping-companies') }}">{{ __('Shipping Companies') }}</a></li>
                    <li><a href="javascript:;">{{ $companyName }}</a></li>
                </ul>
            </div>
        </div>
    </div>

    @include('alerts.operator.form-both')

    {{-- Date Filter --}}
    <div class="card mb-4">
        <div class="card-body">
            <form action="{{ route('operator.accounts.shipping-company.statement', $providerCode) }}" method="GET" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">{{ __('From Date') }}</label>
                    <input type="date" name="start_date" class="form-control" value="{{ $startDate }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">{{ __('To Date') }}</label>
                    <input type="date" name="end_date" class="form-control" value="{{ $endDate }}">
                </div>
                <div class="col-md-6">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-filter"></i> {{ __('Filter') }}
                    </button>
                    <a href="{{ route('operator.accounts.shipping-company.statement', $providerCode) }}" class="btn btn-secondary">
                        {{ __('Reset') }}
                    </a>
                    <a href="{{ route('operator.accounts.shipping-company.statement.pdf', ['providerCode' => $providerCode, 'start_date' => $startDate, 'end_date' => $endDate]) }}" class="btn btn-success">
                        <i class="fas fa-file-pdf"></i> {{ __('Export PDF') }}
                    </a>
                </div>
            </form>
        </div>
    </div>

    {{-- Company Header --}}
    <div class="card mb-4 bg-primary text-white">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h3 class="mb-1"><i class="fas fa-truck me-2"></i>{{ $companyName }}</h3>
                    <small>{{ __('Provider Code') }}: {{ $providerCode }}</small>
                </div>
                <div class="text-end">
                    <h5 class="mb-0">{{ $totalShipments }} {{ __('Shipments') }}</h5>
                    <small>{{ $startDate }} - {{ $endDate }}</small>
                </div>
            </div>
        </div>
    </div>

    {{-- Summary Cards Row 1 - Financial Overview --}}
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="card h-100 bg-info text-white">
                <div class="card-body text-center">
                    <h6>{{ __('Shipping Fees') }}</h6>
                    <h3 class="mb-0">{{ $summaryDisplay['totalShippingFees_formatted'] }}</h3>
                    <small>{{ __('Due to company') }}</small>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card h-100 bg-warning text-dark">
                <div class="card-body text-center">
                    <h6>{{ __('COD Collected') }}</h6>
                    <h3 class="mb-0">{{ $summaryDisplay['totalCodCollected_formatted'] }}</h3>
                    <small>{{ __('Cash on Delivery') }}</small>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card h-100 bg-danger text-white">
                <div class="card-body text-center">
                    <h6>{{ __('Owes Platform') }}</h6>
                    <h3 class="mb-0">{{ $summaryDisplay['owesToPlatform_formatted'] }}</h3>
                    <small>{{ __('Company owes platform') }}</small>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card h-100 bg-success text-white">
                <div class="card-body text-center">
                    <h6>{{ __('Owes Merchants') }}</h6>
                    <h3 class="mb-0">{{ $summaryDisplay['owesToMerchant_formatted'] }}</h3>
                    <small>{{ __('Company owes merchants') }}</small>
                </div>
            </div>
        </div>
    </div>

    {{-- Summary Cards Row 2 - Settlement Status --}}
    <div class="row mb-4">
        <div class="col-md-4 mb-3">
            <div class="card h-100">
                <div class="card-header bg-light">
                    <strong><i class="fas fa-clock me-1"></i>{{ __('Pending Settlements') }}</strong>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span>{{ __('To Platform') }}:</span>
                        <span class="text-danger fw-bold">{{ $summaryDisplay['pendingToPlatform_formatted'] }}</span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span>{{ __('To Merchants') }}:</span>
                        <span class="text-warning fw-bold">{{ $summaryDisplay['pendingToMerchant_formatted'] }}</span>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between">
                        <strong>{{ __('Total Pending') }}:</strong>
                        <strong class="text-danger">{{ $summaryDisplay['totalPending_formatted'] }}</strong>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card h-100">
                <div class="card-header bg-light">
                    <strong><i class="fas fa-check-circle me-1"></i>{{ __('Settled') }}</strong>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span>{{ __('To Platform') }}:</span>
                        <span class="text-success fw-bold">{{ $summaryDisplay['settledToPlatform_formatted'] }}</span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span>{{ __('To Merchants') }}:</span>
                        <span class="text-success fw-bold">{{ $summaryDisplay['settledToMerchant_formatted'] }}</span>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between">
                        <strong>{{ __('Total Settled') }}:</strong>
                        <strong class="text-success">{{ $summaryDisplay['totalSettled_formatted'] }}</strong>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card h-100">
                <div class="card-header bg-light">
                    <strong><i class="fas fa-balance-scale me-1"></i>{{ __('Net Balance') }}</strong>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span>{{ __('Shipping Fees') }}:</span>
                        <span class="text-success">{{ $summaryDisplay['shippingFees_plus_formatted'] }}</span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span>{{ __('COD Collected') }}:</span>
                        <span class="text-danger">{{ $summaryDisplay['codCollected_minus_formatted'] }}</span>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between">
                        <strong>{{ __('Net') }}:</strong>
                        <strong class="{{ $summaryDisplay['netBalance'] >= 0 ? 'text-success' : 'text-danger' }}">
                            {{ $summaryDisplay['netBalance_formatted'] }}
                            @if($summaryDisplay['netBalance'] >= 0)
                                <small class="badge bg-success">{{ __('Platform owes company') }}</small>
                            @else
                                <small class="badge bg-danger">{{ __('Company owes platform') }}</small>
                            @endif
                        </strong>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Delivery Status Breakdown --}}
    <div class="card mb-4">
        <div class="card-header bg-light">
            <strong><i class="fas fa-chart-pie me-2"></i>{{ __('Delivery Status Breakdown') }}</strong>
        </div>
        <div class="card-body">
            <div class="row text-center">
                <div class="col-md-3">
                    <div class="p-3 bg-success bg-opacity-10 rounded">
                        <i class="fas fa-check-circle fa-2x text-success mb-2"></i>
                        <h4 class="mb-0">{{ $statusBreakdown['delivered'] ?? 0 }}</h4>
                        <small class="text-muted">{{ __('Delivered') }}</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="p-3 bg-info bg-opacity-10 rounded">
                        <i class="fas fa-shipping-fast fa-2x text-info mb-2"></i>
                        <h4 class="mb-0">{{ $statusBreakdown['in_transit'] ?? 0 }}</h4>
                        <small class="text-muted">{{ __('In Transit') }}</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="p-3 bg-warning bg-opacity-10 rounded">
                        <i class="fas fa-undo fa-2x text-warning mb-2"></i>
                        <h4 class="mb-0">{{ $statusBreakdown['returned'] ?? 0 }}</h4>
                        <small class="text-muted">{{ __('Returned') }}</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="p-3 bg-danger bg-opacity-10 rounded">
                        <i class="fas fa-times-circle fa-2x text-danger mb-2"></i>
                        <h4 class="mb-0">{{ $statusBreakdown['failed'] ?? 0 }}</h4>
                        <small class="text-muted">{{ __('Failed') }}</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Statement Table --}}
    <div class="card">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <strong><i class="fas fa-list me-2"></i>{{ __('Detailed Statement') }}</strong>
            <span class="badge bg-light text-dark">{{ count($statement) }} {{ __('entries') }}</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0" id="statementTable">
                    <thead class="table-light">
                        <tr>
                            <th>{{ __('Date') }}</th>
                            <th>{{ __('Order #') }}</th>
                            <th>{{ __('Merchant') }}</th>
                            <th>{{ __('Status') }}</th>
                            <th class="text-end">{{ __('Shipping Fee') }}</th>
                            <th class="text-end">{{ __('COD') }}</th>
                            <th class="text-end">{{ __('Owes Platform') }}</th>
                            <th class="text-end">{{ __('Owes Merchant') }}</th>
                            <th class="text-center">{{ __('Settlement') }}</th>
                            <th class="text-end">{{ __('Balance') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($entriesDisplay as $entry)
                        <tr>
                            <td>{{ $entry['date']->format('d-m-Y') }}</td>
                            <td>
                                <a href="{{ route('operator-purchase-invoice', $entry['purchase_number']) }}" class="text-primary">
                                    {{ $entry['purchase_number'] }}
                                </a>
                            </td>
                            <td>{{ $entry['merchant_name'] }}</td>
                            <td>
                                @switch($entry['delivery_status'])
                                    @case('delivered')
                                        <span class="badge bg-success">{{ __('Delivered') }}</span>
                                        @break
                                    @case('shipped')
                                    @case('in_transit')
                                        <span class="badge bg-info">{{ __('In Transit') }}</span>
                                        @break
                                    @case('returned')
                                        <span class="badge bg-warning">{{ __('Returned') }}</span>
                                        @break
                                    @case('failed')
                                        <span class="badge bg-danger">{{ __('Failed') }}</span>
                                        @break
                                    @default
                                        <span class="badge bg-secondary">{{ $entry['delivery_status'] }}</span>
                                @endswitch
                                @if($entry['collection_status'] === 'collected')
                                    <br><small class="badge bg-success mt-1">{{ __('COD Collected') }}</small>
                                @endif
                            </td>
                            <td class="text-end text-success">{{ $entry['shipping_fee_formatted'] }}</td>
                            <td class="text-end text-warning">{{ $entry['cod_collected_formatted'] }}</td>
                            <td class="text-end text-danger">{{ $entry['owes_platform_formatted'] }}</td>
                            <td class="text-end text-info">{{ $entry['owes_merchant_formatted'] }}</td>
                            <td class="text-center">
                                @if($entry['settlement_status'] === 'settled')
                                    <span class="badge bg-success">{{ __('Settled') }}</span>
                                @else
                                    <span class="badge bg-warning">{{ __('Pending') }}</span>
                                @endif
                            </td>
                            <td class="text-end {{ $entry['balance'] >= 0 ? 'text-success' : 'text-danger' }}">
                                <strong>{{ $entry['balance_formatted'] }}</strong>
                                @if($entry['balance'] >= 0)
                                    <small class="badge bg-success">CR</small>
                                @else
                                    <small class="badge bg-danger">DR</small>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="10" class="text-center py-4 text-muted">
                                <i class="fas fa-inbox fa-3x mb-3 d-block"></i>
                                {{ __('No shipments found for this period') }}
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                    @if(count($entriesDisplay) > 0)
                    <tfoot class="table-dark">
                        <tr class="fw-bold">
                            <td colspan="4">{{ __('Totals') }}</td>
                            <td class="text-end">{{ $summaryDisplay['totalShippingFees_formatted'] }}</td>
                            <td class="text-end">{{ $summaryDisplay['totalCodCollected_formatted'] }}</td>
                            <td class="text-end">{{ $summaryDisplay['owesToPlatform_formatted'] }}</td>
                            <td class="text-end">{{ $summaryDisplay['owesToMerchant_formatted'] }}</td>
                            <td></td>
                            <td class="text-end">
                                {{ $summaryDisplay['netBalance_formatted'] }}
                                @if($summaryDisplay['netBalance'] >= 0)
                                    <small class="badge bg-success">CR</small>
                                @else
                                    <small class="badge bg-danger">DR</small>
                                @endif
                            </td>
                        </tr>
                    </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>

    {{-- Legend --}}
    <div class="card mt-4">
        <div class="card-body">
            <h6><i class="fas fa-info-circle me-2"></i>{{ __('Understanding This Statement') }}</h6>
            <div class="row">
                <div class="col-md-4">
                    <p class="text-success mb-1"><strong>{{ __('Shipping Fee (Credit)') }}</strong></p>
                    <ul class="small text-muted">
                        <li>{{ __('Amount due to the shipping company for delivery service') }}</li>
                        <li>{{ __('Platform owes this to the company') }}</li>
                    </ul>
                </div>
                <div class="col-md-4">
                    <p class="text-warning mb-1"><strong>{{ __('COD Collected (Debit)') }}</strong></p>
                    <ul class="small text-muted">
                        <li>{{ __('Cash collected by shipping company on delivery') }}</li>
                        <li>{{ __('Company must remit this to platform/merchant') }}</li>
                    </ul>
                </div>
                <div class="col-md-4">
                    <p class="text-info mb-1"><strong>{{ __('Net Balance') }}</strong></p>
                    <ul class="small text-muted">
                        <li><span class="badge bg-success">CR</span> {{ __('Platform owes company') }}</li>
                        <li><span class="badge bg-danger">DR</span> {{ __('Company owes platform') }}</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    {{-- Action Buttons --}}
    <div class="mt-4 d-flex gap-2">
        <a href="{{ route('operator.accounts.shipping-companies') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-1"></i> {{ __('Back to Companies') }}
        </a>
        <a href="{{ route('operator.accounts.settlements.shipping.pending', $providerCode) }}" class="btn btn-warning">
            <i class="fas fa-clock me-1"></i> {{ __('View Pending Settlements') }}
        </a>
        @if($summaryDisplay['pendingToPlatform'] > 0 || $summaryDisplay['pendingToMerchant'] > 0)
        <a href="{{ route('operator.accounts.settlements.create', ['party_type' => 'shipping', 'provider_code' => $providerCode]) }}" class="btn btn-success">
            <i class="fas fa-money-bill-wave me-1"></i> {{ __('Record Settlement') }}
        </a>
        @endif
    </div>
</div>
@endsection

@section('script')
<script type="text/javascript">
    if ($.fn.DataTable) {
        $('#statementTable').DataTable({
            order: [[0, 'desc']],
            pageLength: 25,
            language: {
                lengthMenu: "{{ __('Show _MENU_ entries') }}",
                info: "{{ __('Showing _START_ to _END_ of _TOTAL_ entries') }}",
                search: "{{ __('Search:') }}",
                paginate: {
                    first: "{{ __('First') }}",
                    last: "{{ __('Last') }}",
                    next: "{{ __('Next') }}",
                    previous: "{{ __('Previous') }}"
                },
                emptyTable: "{{ __('No data available') }}"
            }
        });
    }
</script>
@endsection
