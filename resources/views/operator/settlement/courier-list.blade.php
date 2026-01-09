@extends('layouts.operator')

@section('content')
<div class="content-area">
    <div class="mr-breadcrumb">
        <div class="row">
            <div class="col-lg-12">
                <h4 class="heading">
                    {{ __('Courier Settlements') }}
                    <a class="add-btn" href="{{ route('operator.settlement.index') }}">
                        <i class="fas fa-arrow-left"></i> {{ __('Back') }}
                    </a>
                </h4>
                <ul class="links">
                    <li><a href="{{ route('operator.dashboard') }}">{{ __('Dashboard') }}</a></li>
                    <li><a href="{{ route('operator.settlement.index') }}">{{ __('Settlements') }}</a></li>
                    <li><a href="javascript:;">{{ __('Courier Settlements') }}</a></li>
                </ul>
            </div>
        </div>
    </div>

    @include('alerts.operator.form-both')

    {{-- Filters --}}
    <div class="card mb-4">
        <div class="card-body">
            <form action="{{ route('operator.settlement.couriers') }}" method="GET" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label">{{ __('Courier') }}</label>
                    <select name="courier_id" class="form-control">
                        <option value="">{{ __('All Couriers') }}</option>
                        @foreach($couriers as $courier)
                            <option value="{{ $courier->id }}" {{ request('courier_id') == $courier->id ? 'selected' : '' }}>
                                {{ $courier->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">{{ __('Status') }}</label>
                    <select name="status" class="form-control">
                        <option value="">{{ __('All Statuses') }}</option>
                        <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>{{ __('Pending') }}</option>
                        <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>{{ __('Completed') }}</option>
                        <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>{{ __('Cancelled') }}</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-primary">{{ __('Filter') }}</button>
                    <a href="{{ route('operator.settlement.couriers') }}" class="btn btn-secondary">{{ __('Reset') }}</a>
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
                            <th>{{ __('ID') }}</th>
                            <th>{{ __('Courier') }}</th>
                            <th>{{ __('Type') }}</th>
                            <th class="text-end">{{ __('Amount') }}</th>
                            <th>{{ __('Payment Method') }}</th>
                            <th class="text-center">{{ __('Status') }}</th>
                            <th>{{ __('Date') }}</th>
                            <th>{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($settlements as $settlement)
                        <tr>
                            <td>#{{ $settlement->id }}</td>
                            <td>{{ $settlement->courier->name ?? 'N/A' }}</td>
                            <td>
                                @if($settlement->isPaymentToCourier())
                                    <span class="badge bg-success">{{ __('Pay to Courier') }}</span>
                                @else
                                    <span class="badge bg-warning">{{ __('Receive from Courier') }}</span>
                                @endif
                            </td>
                            <td class="text-end">{{ $currency->sign }}{{ number_format($settlement->amount, 2) }}</td>
                            <td>{{ $settlement->payment_method ?? '-' }}</td>
                            <td class="text-center">
                                @if($settlement->isPending())
                                    <span class="badge bg-warning">{{ __('Pending') }}</span>
                                @elseif($settlement->isCompleted())
                                    <span class="badge bg-success">{{ __('Completed') }}</span>
                                @else
                                    <span class="badge bg-danger">{{ __('Cancelled') }}</span>
                                @endif
                            </td>
                            <td>{{ $settlement->created_at->format('d M Y') }}</td>
                            <td>
                                <a href="{{ route('operator.settlement.courier.show', $settlement->id) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">
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
