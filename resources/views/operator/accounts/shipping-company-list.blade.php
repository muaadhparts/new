@extends('layouts.operator')

@section('content')
<div class="content-area">
    <div class="mr-breadcrumb">
        <div class="row">
            <div class="col-lg-12">
                <h4 class="heading">{{ __('Shipping Companies') }}</h4>
                <ul class="links">
                    <li><a href="{{ route('operator.dashboard') }}">{{ __('Dashboard') }}</a></li>
                    <li><a href="{{ route('operator.accounts.index') }}">{{ __('Accounts') }}</a></li>
                    <li><a href="javascript:;">{{ __('Shipping Companies') }}</a></li>
                </ul>
            </div>
        </div>
    </div>

    @include('alerts.operator.form-both')

    {{-- Summary Totals --}}
    @php
        $totalShipments = collect($companies)->sum('shipment_count');
        $totalFees = collect($companies)->sum('total_shipping_fees');
        $totalCod = collect($companies)->sum('total_cod_collected');
        $totalOwesToPlatform = collect($companies)->sum('owes_platform');
        $totalOwesToMerchant = collect($companies)->sum('owes_merchant');
        $totalPending = collect($companies)->sum('pending_count');
    @endphp

    <div class="row mb-4">
        <div class="col-md-2">
            <div class="card bg-primary text-white">
                <div class="card-body text-center py-3">
                    <h6 class="mb-1">{{ __('Companies') }}</h6>
                    <h3 class="mb-0">{{ count($companies) }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card bg-info text-white">
                <div class="card-body text-center py-3">
                    <h6 class="mb-1">{{ __('Shipments') }}</h6>
                    <h3 class="mb-0">{{ number_format($totalShipments) }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card bg-success text-white">
                <div class="card-body text-center py-3">
                    <h6 class="mb-1">{{ __('Fees') }}</h6>
                    <h4 class="mb-0">{{ $currency->sign }}{{ number_format($totalFees, 2) }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card bg-warning text-dark">
                <div class="card-body text-center py-3">
                    <h6 class="mb-1">{{ __('COD') }}</h6>
                    <h4 class="mb-0">{{ $currency->sign }}{{ number_format($totalCod, 2) }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card bg-danger text-white">
                <div class="card-body text-center py-3">
                    <h6 class="mb-1">{{ __('Owes Platform') }}</h6>
                    <h4 class="mb-0">{{ $currency->sign }}{{ number_format($totalOwesToPlatform, 2) }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card bg-secondary text-white">
                <div class="card-body text-center py-3">
                    <h6 class="mb-1">{{ __('Pending') }}</h6>
                    <h3 class="mb-0">{{ number_format($totalPending) }}</h3>
                </div>
            </div>
        </div>
    </div>

    {{-- Companies Table --}}
    <div class="card">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <strong><i class="fas fa-truck me-2"></i>{{ __('All Shipping Companies') }}</strong>
            <a href="{{ route('operator.accounts.reports.shipping-companies') }}" class="btn btn-sm btn-light">
                <i class="fas fa-chart-bar me-1"></i> {{ __('Financial Report') }}
            </a>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0" id="companiesTable">
                    <thead class="table-light">
                        <tr>
                            <th>{{ __('Company') }}</th>
                            <th class="text-center">{{ __('Shipments') }}</th>
                            <th class="text-end">{{ __('Shipping Fees') }}</th>
                            <th class="text-end">{{ __('COD Collected') }}</th>
                            <th class="text-end">{{ __('Owes Platform') }}</th>
                            <th class="text-end">{{ __('Owes Merchants') }}</th>
                            <th class="text-center">{{ __('Pending') }}</th>
                            <th class="text-center">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($companies as $company)
                        <tr>
                            <td>
                                <strong>{{ $company['name'] }}</strong>
                                <br><small class="text-muted">{{ $company['code'] }}</small>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-info">{{ number_format($company['shipment_count']) }}</span>
                            </td>
                            <td class="text-end text-success fw-bold">
                                {{ $currency->sign }}{{ number_format($company['total_shipping_fees'], 2) }}
                            </td>
                            <td class="text-end text-warning fw-bold">
                                {{ $currency->sign }}{{ number_format($company['total_cod_collected'], 2) }}
                            </td>
                            <td class="text-end text-danger">
                                @if($company['owes_platform'] > 0)
                                    {{ $currency->sign }}{{ number_format($company['owes_platform'], 2) }}
                                @else
                                    -
                                @endif
                            </td>
                            <td class="text-end text-info">
                                @if($company['owes_merchant'] > 0)
                                    {{ $currency->sign }}{{ number_format($company['owes_merchant'], 2) }}
                                @else
                                    -
                                @endif
                            </td>
                            <td class="text-center">
                                @if($company['pending_count'] > 0)
                                    <span class="badge bg-warning text-dark">{{ $company['pending_count'] }}</span>
                                @else
                                    <span class="badge bg-success">0</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <div class="btn-group">
                                    <a href="{{ route('operator.accounts.shipping-company.statement', $company['code']) }}"
                                       class="btn btn-sm btn-outline-primary" title="{{ __('View Statement') }}">
                                        <i class="fas fa-file-invoice"></i>
                                    </a>
                                    <a href="{{ route('operator.accounts.settlements.shipping.pending', $company['code']) }}"
                                       class="btn btn-sm btn-outline-warning" title="{{ __('Pending Settlements') }}">
                                        <i class="fas fa-clock"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <i class="fas fa-truck fa-3x mb-3 d-block"></i>
                                <h5>{{ __('No Shipping Companies Found') }}</h5>
                                <p>{{ __('Shipping companies will appear here once orders are processed through them.') }}</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                    @if(count($companies) > 0)
                    <tfoot class="table-dark">
                        <tr class="fw-bold">
                            <td>{{ __('Totals') }}</td>
                            <td class="text-center">{{ number_format($totalShipments) }}</td>
                            <td class="text-end">{{ $currency->sign }}{{ number_format($totalFees, 2) }}</td>
                            <td class="text-end">{{ $currency->sign }}{{ number_format($totalCod, 2) }}</td>
                            <td class="text-end">{{ $currency->sign }}{{ number_format($totalOwesToPlatform, 2) }}</td>
                            <td class="text-end">{{ $currency->sign }}{{ number_format($totalOwesToMerchant, 2) }}</td>
                            <td class="text-center">{{ number_format($totalPending) }}</td>
                            <td></td>
                        </tr>
                    </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>

    {{-- Back Button --}}
    <div class="mt-4">
        <a href="{{ route('operator.accounts.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-1"></i> {{ __('Back to Accounts') }}
        </a>
    </div>
</div>
@endsection

@section('script')
<script type="text/javascript">
    if ($.fn.DataTable) {
        $('#companiesTable').DataTable({
            order: [[1, 'desc']],
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
