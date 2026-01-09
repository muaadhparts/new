@extends('layouts.operator')

@section('content')
<div class="content-area">
    <div class="mr-breadcrumb">
        <div class="row">
            <div class="col-lg-12">
                <h4 class="heading">
                    {{ __('Merchant Settlements') }}
                    <a class="add-btn" href="{{ route('operator.settlement.index') }}">
                        <i class="fas fa-arrow-left"></i> {{ __('Back') }}
                    </a>
                </h4>
                <ul class="links">
                    <li><a href="{{ route('operator.dashboard') }}">{{ __('Dashboard') }}</a></li>
                    <li><a href="{{ route('operator.settlement.index') }}">{{ __('Settlements') }}</a></li>
                    <li><a href="javascript:;">{{ __('Merchant Settlements') }}</a></li>
                </ul>
            </div>
        </div>
    </div>

    @include('alerts.operator.form-both')

    {{-- Filters --}}
    <div class="card mb-4">
        <div class="card-body">
            <form action="{{ route('operator.settlement.merchants') }}" method="GET" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label">{{ __('Merchant') }}</label>
                    <select name="merchant_id" class="form-control">
                        <option value="">{{ __('All Merchants') }}</option>
                        @foreach($merchants as $merchant)
                            <option value="{{ $merchant->id }}" {{ request('merchant_id') == $merchant->id ? 'selected' : '' }}>
                                {{ $merchant->shop_name ?? $merchant->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">{{ __('Status') }}</label>
                    <select name="status" class="form-control">
                        <option value="">{{ __('All Statuses') }}</option>
                        <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>{{ __('Draft') }}</option>
                        <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>{{ __('Pending') }}</option>
                        <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>{{ __('Approved') }}</option>
                        <option value="paid" {{ request('status') == 'paid' ? 'selected' : '' }}>{{ __('Paid') }}</option>
                        <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>{{ __('Cancelled') }}</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-primary">{{ __('Filter') }}</button>
                    <a href="{{ route('operator.settlement.merchants') }}" class="btn btn-secondary">{{ __('Reset') }}</a>
                </div>
            </form>
        </div>
    </div>

    {{-- Settlements Table --}}
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>{{ __('Settlement #') }}</th>
                            <th>{{ __('Merchant') }}</th>
                            <th>{{ __('Period') }}</th>
                            <th class="text-center">{{ __('Orders') }}</th>
                            <th class="text-end">{{ __('Total Sales') }}</th>
                            <th class="text-center">{{ __('Direction') }}</th>
                            <th class="text-end">{{ __('Amount') }}</th>
                            <th class="text-center">{{ __('Status') }}</th>
                            <th>{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($settlements as $settlement)
                        <tr>
                            <td>
                                <a href="{{ route('operator.settlement.merchant.show', $settlement->id) }}">
                                    {{ $settlement->settlement_number }}
                                </a>
                            </td>
                            <td>{{ $settlement->merchant->shop_name ?? $settlement->merchant->name ?? 'N/A' }}</td>
                            <td>
                                <small>{{ $settlement->period_start->format('d M Y') }} - {{ $settlement->period_end->format('d M Y') }}</small>
                            </td>
                            <td class="text-center">{{ $settlement->orders_count }}</td>
                            <td class="text-end">{{ $currency->sign }}{{ number_format($settlement->total_sales, 2) }}</td>
                            <td class="text-center">
                                @if($settlement->isPlatformPaysMerchant())
                                    <span class="badge bg-success" title="{{ __('Platform Pays Merchant') }}">
                                        <i class="fas fa-arrow-right"></i> {{ __('Pay') }}
                                    </span>
                                @else
                                    <span class="badge bg-warning" title="{{ __('Merchant Pays Platform') }}">
                                        <i class="fas fa-arrow-left"></i> {{ __('Collect') }}
                                    </span>
                                @endif
                            </td>
                            <td class="text-end">
                                <strong class="{{ $settlement->isPlatformPaysMerchant() ? 'text-success' : 'text-warning' }}">
                                    {{ $currency->sign }}{{ number_format($settlement->net_payable, 2) }}
                                </strong>
                            </td>
                            <td class="text-center">
                                <span class="badge {{ $settlement->getStatusBadgeClass() }}">
                                    {{ $settlement->getStatusLabel() }}
                                </span>
                            </td>
                            <td>
                                <a href="{{ route('operator.settlement.merchant.show', $settlement->id) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted py-4">
                                {{ __('No settlements found') }}
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($settlements->hasPages())
        <div class="card-footer">
            {{ $settlements->appends(request()->query())->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
