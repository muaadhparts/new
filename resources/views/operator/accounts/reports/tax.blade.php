@extends('layouts.operator')

@section('content')
<div class="content-area">
    <div class="mr-breadcrumb">
        <div class="row">
            <div class="col-lg-12">
                <h4 class="heading">{{ __('Tax Report') }}</h4>
                <ul class="links">
                    <li><a href="{{ route('operator.dashboard') }}">{{ __('Dashboard') }}</a></li>
                    <li><a href="{{ route('operator.accounts.index') }}">{{ __('Accounts') }}</a></li>
                    <li><a href="javascript:;">{{ __('Tax Report') }}</a></li>
                </ul>
            </div>
        </div>
    </div>

    @include('alerts.operator.form-both')

    {{-- Filter --}}
    <div class="card mb-4">
        <div class="card-body">
            <form action="{{ route('operator.accounts.reports.tax') }}" method="GET" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label">{{ __('From Date') }}</label>
                    <input type="date" name="start_date" class="form-control" value="{{ $startDate }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">{{ __('To Date') }}</label>
                    <input type="date" name="end_date" class="form-control" value="{{ $endDate }}">
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-primary">{{ __('Filter') }}</button>
                    <a href="{{ route('operator.accounts.reports.tax') }}" class="btn btn-secondary">{{ __('Reset') }}</a>
                </div>
            </form>
        </div>
    </div>

    {{-- Summary Cards --}}
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card bg-info text-white">
                <div class="card-body text-center">
                    <h6>{{ __('Total Tax Collected') }}</h6>
                    <h2>{{ $currency->sign }}{{ number_format($totalTax, 2) }}</h2>
                    <small>{{ $taxEntries->count() }} {{ __('Transaction') }}</small>
                </div>
            </div>
        </div>
        <div class="col-md-8">
            <div class="card h-100">
                <div class="card-header">
                    <i class="fas fa-map-marker-alt me-2"></i>{{ __('Tax by Location') }}
                </div>
                <div class="card-body">
                    @if($byLocation->count() > 0)
                    <div class="row">
                        @foreach($byLocation as $location => $data)
                        <div class="col-md-4 mb-2">
                            <div class="d-flex justify-content-between align-items-center p-2 bg-light rounded">
                                <span>{{ $location }}</span>
                                <span class="fw-bold">{{ $currency->sign }}{{ number_format($data['total'], 2) }}</span>
                            </div>
                            <small class="text-muted">{{ $data['count'] }} {{ __('orders') }}</small>
                        </div>
                        @endforeach
                    </div>
                    @else
                    <p class="text-muted mb-0">{{ __('No tax data available') }}</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Tax Entries Table --}}
    <div class="card">
        <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
            <strong><i class="fas fa-receipt me-2"></i>{{ __('Tax Collection Details') }}</strong>
            <span class="badge bg-light text-dark">{{ $taxEntries->count() }} {{ __('Entry') }}</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>{{ __('Date') }}</th>
                            <th>{{ __('Purchase #') }}</th>
                            <th>{{ __('Merchant') }}</th>
                            <th>{{ __('Location') }}</th>
                            <th class="text-end">{{ __('Tax Rate') }}</th>
                            <th class="text-end">{{ __('Tax Amount') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($taxEntries as $entry)
                        <tr>
                            <td>{{ $entry->transaction_date->format('Y-m-d') }}</td>
                            <td>
                                @if($entry->purchase)
                                    <a href="{{ route('operator-purchase-show', $entry->purchase_id) }}">
                                        {{ $entry->purchase->purchase_number }}
                                    </a>
                                @else
                                    {{ $entry->merchantPurchase->purchase_number ?? 'N/A' }}
                                @endif
                            </td>
                            <td>
                                @if($entry->merchantPurchase && $entry->merchantPurchase->merchant)
                                    {{ $entry->merchantPurchase->merchant->shop_name ?? $entry->merchantPurchase->merchant->name }}
                                @else
                                    N/A
                                @endif
                            </td>
                            <td>{{ $entry->metadata['tax_location'] ?? '-' }}</td>
                            <td class="text-end">
                                @if(isset($entry->metadata['tax_rate']))
                                    {{ $entry->metadata['tax_rate'] }}%
                                @else
                                    -
                                @endif
                            </td>
                            <td class="text-end fw-bold text-info">
                                {{ $currency->sign }}{{ number_format($entry->amount, 2) }}
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">
                                {{ __('No tax entries found') }}
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                    @if($taxEntries->count() > 0)
                    <tfoot class="table-info">
                        <tr class="fw-bold">
                            <td colspan="5" class="text-end">{{ __('Total') }}:</td>
                            <td class="text-end">{{ $currency->sign }}{{ number_format($totalTax, 2) }}</td>
                        </tr>
                    </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>

    {{-- Back Button --}}
    <div class="mt-3">
        <a href="{{ route('operator.accounts.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-1"></i> {{ __('Back to Accounts') }}
        </a>
    </div>
</div>
@endsection
