@extends('layouts.operator')
@section('content')
<div class="content-area">
    <div class="mr-breadcrumb">
        <div class="row">
            <div class="col-lg-12">
                <h4 class="heading">{{ __('Courier Settlements') }}</h4>
                <ul class="links">
                    <li><a href="{{ route('operator.dashboard') }}">{{ __('Dashboard') }}</a></li>
                    <li><a href="{{ route('operator-courier-index') }}">{{ __('Couriers') }}</a></li>
                    <li><a href="{{ route('operator-courier-settlements') }}">{{ __('Settlements') }}</a></li>
                </ul>
            </div>
        </div>
    </div>

    <div class="catalogItem-area">
        <!-- Filter -->
        <div class="row mb-4">
            <div class="col-12">
                <form action="{{ route('operator-courier-settlements') }}" method="GET" class="d-flex gap-2 flex-wrap">
                    <select name="status" class="form-select" style="max-width: 150px;">
                        <option value="">{{ __('All Status') }}</option>
                        <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>{{ __('Pending') }}</option>
                        <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>{{ __('Completed') }}</option>
                        <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>{{ __('Cancelled') }}</option>
                    </select>
                    <select name="courier_id" class="form-select" style="max-width: 200px;">
                        <option value="">{{ __('All Couriers') }}</option>
                        @foreach($couriers as $courier)
                            <option value="{{ $courier->id }}" {{ request('courier_id') == $courier->id ? 'selected' : '' }}>
                                {{ $courier->name }}
                            </option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn btn-primary">{{ __('Filter') }}</button>
                    <a href="{{ route('operator-courier-settlements') }}" class="btn btn-secondary">{{ __('Reset') }}</a>
                </form>
            </div>
        </div>

        <!-- Quick Links -->
        <div class="mb-4">
            <a href="{{ route('operator-courier-balances') }}" class="btn btn-info">
                <i class="fas fa-balance-scale"></i> {{ __('View Courier Balances') }}
            </a>
        </div>

        @include('alerts.operator.form-both')

        <!-- Settlements Table -->
        <div class="mr-table allproduct">
            <div class="table-responsive">
                <table class="table table-hover dt-responsive" cellspacing="0" width="100%">
                    <thead>
                        <tr>
                            <th>{{ __('#') }}</th>
                            <th>{{ __('Courier') }}</th>
                            <th>{{ __('Type') }}</th>
                            <th>{{ __('Amount') }}</th>
                            <th>{{ __('Status') }}</th>
                            <th>{{ __('Payment Method') }}</th>
                            <th>{{ __('Reference') }}</th>
                            <th>{{ __('Date') }}</th>
                            <th>{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($settlements as $key => $settlement)
                        <tr>
                            <td>{{ $settlements->firstItem() + $key }}</td>
                            <td>
                                <strong>{{ $settlement->courier->name ?? 'Unknown' }}</strong>
                            </td>
                            <td>
                                @if($settlement->type == 'pay_to_courier')
                                    <span class="badge bg-success">{{ __('Pay to Courier') }}</span>
                                @else
                                    <span class="badge bg-warning">{{ __('Receive from Courier') }}</span>
                                @endif
                            </td>
                            <td>
                                <strong>{{ $currency->sign }}{{ number_format($settlement->amount, 2) }}</strong>
                            </td>
                            <td>
                                @switch($settlement->status)
                                    @case('pending')
                                        <span class="badge bg-warning">{{ __('Pending') }}</span>
                                        @break
                                    @case('completed')
                                        <span class="badge bg-success">{{ __('Completed') }}</span>
                                        @break
                                    @case('cancelled')
                                        <span class="badge bg-danger">{{ __('Cancelled') }}</span>
                                        @break
                                @endswitch
                            </td>
                            <td>{{ $settlement->payment_method ?? '-' }}</td>
                            <td>{{ $settlement->reference_number ?? '-' }}</td>
                            <td>
                                {{ $settlement->created_at->format('d-m-Y') }}
                                @if($settlement->processed_at)
                                    <br><small class="text-muted">{{ __('Processed') }}: {{ $settlement->processed_at->format('d-m-Y') }}</small>
                                @endif
                            </td>
                            <td>
                                @if($settlement->status == 'pending')
                                    <form action="{{ route('operator-settlement-process', $settlement->id) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-success" title="{{ __('Approve') }}"
                                                onclick="return confirm('{{ __('Are you sure you want to approve this settlement?') }}')">
                                            <i class="fas fa-check"></i>
                                        </button>
                                    </form>
                                    <form action="{{ route('operator-settlement-cancel', $settlement->id) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-danger" title="{{ __('Cancel') }}"
                                                onclick="return confirm('{{ __('Are you sure you want to cancel this settlement?') }}')">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </form>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="text-center">{{ __('No settlements found') }}</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="d-flex justify-content-center mt-4">
                {{ $settlements->appends(request()->query())->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
