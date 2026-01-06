@extends('layouts.front')

@section('content')
    <div class="gs-user-panel-review wow-replaced" data-wow-delay=".1s">
        <div class="container">
            <div class="d-flex">
                <!-- sidebar -->
                @include('includes.courier.sidebar')
                <!-- main content -->
                <div class="gs-dashboard-user-content-wrapper gs-dashboard-outlet">
                    <div class="ud-page-title-box">
                        <h3 class="ud-page-title">@lang('Transaction History')</h3>
                    </div>

                    <!-- Filter -->
                    <div class="mb-4">
                        <form action="{{ route('courier-transactions') }}" method="GET" class="d-flex gap-2 flex-wrap">
                            <select name="type" class="form-select" style="max-width: 200px;">
                                <option value="">@lang('All Types')</option>
                                <option value="cod_collected" {{ request('type') == 'cod_collected' ? 'selected' : '' }}>@lang('COD Collected')</option>
                                <option value="fee_earned" {{ request('type') == 'fee_earned' ? 'selected' : '' }}>@lang('Fee Earned')</option>
                                <option value="settlement_paid" {{ request('type') == 'settlement_paid' ? 'selected' : '' }}>@lang('Settlement Paid')</option>
                                <option value="settlement_received" {{ request('type') == 'settlement_received' ? 'selected' : '' }}>@lang('Settlement Received')</option>
                            </select>
                            <button type="submit" class="btn btn-primary">@lang('Filter')</button>
                            <a href="{{ route('courier-transactions') }}" class="btn btn-secondary">@lang('Reset')</a>
                        </form>
                    </div>

                    <!-- Transactions Table -->
                    <div class="user-table recent-orders-table table-responsive wow-replaced" data-wow-delay=".1s">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th><span class="header-title">{{ __('#') }}</span></th>
                                    <th><span class="header-title">{{ __('Type') }}</span></th>
                                    <th><span class="header-title">{{ __('Amount') }}</span></th>
                                    <th><span class="header-title">{{ __('Balance Before') }}</span></th>
                                    <th><span class="header-title">{{ __('Balance After') }}</span></th>
                                    <th><span class="header-title">{{ __('Notes') }}</span></th>
                                    <th><span class="header-title">{{ __('Date') }}</span></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($transactions as $key => $transaction)
                                    <tr>
                                        <td data-label="{{ __('#') }}">
                                            {{ $transactions->firstItem() + $key }}
                                        </td>
                                        <td data-label="{{ __('Type') }}">
                                            @switch($transaction->type)
                                                @case('cod_collected')
                                                    <span class="badge bg-warning">@lang('COD Collected')</span>
                                                    @break
                                                @case('fee_earned')
                                                    <span class="badge bg-success">@lang('Fee Earned')</span>
                                                    @break
                                                @case('settlement_paid')
                                                    <span class="badge bg-info">@lang('Settlement Paid')</span>
                                                    @break
                                                @case('settlement_received')
                                                    <span class="badge bg-primary">@lang('Settlement Received')</span>
                                                    @break
                                                @default
                                                    <span class="badge bg-secondary">{{ $transaction->type }}</span>
                                            @endswitch
                                        </td>
                                        <td data-label="{{ __('Amount') }}">
                                            @if($transaction->type == 'cod_collected')
                                                <span class="text-danger">-{{ $currency->sign }}{{ number_format($transaction->amount, 2) }}</span>
                                            @else
                                                <span class="text-success">+{{ $currency->sign }}{{ number_format($transaction->amount, 2) }}</span>
                                            @endif
                                        </td>
                                        <td data-label="{{ __('Balance Before') }}">
                                            {{ $currency->sign }}{{ number_format($transaction->balance_before, 2) }}
                                        </td>
                                        <td data-label="{{ __('Balance After') }}">
                                            <strong class="{{ $transaction->balance_after < 0 ? 'text-danger' : 'text-success' }}">
                                                {{ $currency->sign }}{{ number_format($transaction->balance_after, 2) }}
                                            </strong>
                                        </td>
                                        <td data-label="{{ __('Notes') }}">
                                            {{ $transaction->notes ?? '-' }}
                                        </td>
                                        <td data-label="{{ __('Date') }}">
                                            {{ $transaction->created_at->format('d-m-Y H:i') }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center">{{ __('No transactions found') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="d-flex justify-content-center mt-4">
                        {{ $transactions->links() }}
                    </div>

                </div>
            </div>
        </div>
    </div>
@endsection
