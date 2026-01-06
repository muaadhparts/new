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
                        <h3 class="ud-page-title">@lang('Settlements')</h3>
                    </div>

                    <!-- Current Settlement Status -->
                    <div class="row g-4 mb-4">
                        <div class="col-lg-6">
                            <div class="account-info-box">
                                <h5>@lang('Current Settlement Status')</h5>
                                <div class="account-info">
                                    <div class="account-info-item d-flex justify-content-between">
                                        <span class="info-title">@lang('COD Amount (You Owe)')</span>
                                        <span class="text-danger">{{ $currency->sign }}{{ number_format($settlementCalc['cod_amount'] ?? 0, 2) }}</span>
                                    </div>
                                    <div class="account-info-item d-flex justify-content-between">
                                        <span class="info-title">@lang('Fees Earned (Online)')</span>
                                        <span class="text-success">{{ $currency->sign }}{{ number_format($settlementCalc['fees_earned_online'] ?? 0, 2) }}</span>
                                    </div>
                                    <div class="account-info-item d-flex justify-content-between">
                                        <span class="info-title">@lang('Fees Earned (COD)')</span>
                                        <span class="text-success">{{ $currency->sign }}{{ number_format($settlementCalc['fees_earned_cod'] ?? 0, 2) }}</span>
                                    </div>
                                    <hr>
                                    <div class="account-info-item d-flex justify-content-between">
                                        <span class="info-title"><strong>@lang('Net Amount')</strong></span>
                                        @php
                                            $netAmount = $settlementCalc['net_amount'] ?? 0;
                                        @endphp
                                        <span class="{{ $netAmount >= 0 ? 'text-success' : 'text-danger' }}">
                                            <strong>{{ $currency->sign }}{{ number_format(abs($netAmount), 2) }}</strong>
                                            @if($netAmount >= 0)
                                                <small>(@lang('Platform owes you'))</small>
                                            @else
                                                <small>(@lang('You owe to platform'))</small>
                                            @endif
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="account-info-box">
                                <h5>@lang('How It Works')</h5>
                                <div class="account-info">
                                    <p class="mb-2">
                                        <i class="fas fa-money-bill-wave text-warning me-2"></i>
                                        @lang('When you collect COD, you owe that amount to the platform.')
                                    </p>
                                    <p class="mb-2">
                                        <i class="fas fa-truck text-success me-2"></i>
                                        @lang('You earn delivery fees for every successful delivery.')
                                    </p>
                                    <p class="mb-2">
                                        <i class="fas fa-balance-scale text-info me-2"></i>
                                        @lang('Net amount = Your earnings - COD collected.')
                                    </p>
                                    <p class="mb-0">
                                        <i class="fas fa-hand-holding-usd text-primary me-2"></i>
                                        @lang('Settlements are processed by the admin.')
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Settlements History -->
                    <h4 class="table-title mt-4">@lang('Settlement History')</h4>
                    <div class="user-table recent-orders-table table-responsive wow-replaced" data-wow-delay=".1s">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th><span class="header-title">{{ __('#') }}</span></th>
                                    <th><span class="header-title">{{ __('Type') }}</span></th>
                                    <th><span class="header-title">{{ __('Amount') }}</span></th>
                                    <th><span class="header-title">{{ __('Status') }}</span></th>
                                    <th><span class="header-title">{{ __('Payment Method') }}</span></th>
                                    <th><span class="header-title">{{ __('Reference') }}</span></th>
                                    <th><span class="header-title">{{ __('Date') }}</span></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($settlements as $key => $settlement)
                                    <tr>
                                        <td data-label="{{ __('#') }}">
                                            {{ $settlements->firstItem() + $key }}
                                        </td>
                                        <td data-label="{{ __('Type') }}">
                                            @if($settlement->type == 'pay_to_courier')
                                                <span class="badge bg-success">@lang('Received')</span>
                                            @else
                                                <span class="badge bg-warning">@lang('Paid')</span>
                                            @endif
                                        </td>
                                        <td data-label="{{ __('Amount') }}">
                                            <strong>{{ $currency->sign }}{{ number_format($settlement->amount, 2) }}</strong>
                                        </td>
                                        <td data-label="{{ __('Status') }}">
                                            @switch($settlement->status)
                                                @case('pending')
                                                    <span class="badge bg-warning">@lang('Pending')</span>
                                                    @break
                                                @case('completed')
                                                    <span class="badge bg-success">@lang('Completed')</span>
                                                    @break
                                                @case('cancelled')
                                                    <span class="badge bg-danger">@lang('Cancelled')</span>
                                                    @break
                                            @endswitch
                                        </td>
                                        <td data-label="{{ __('Payment Method') }}">
                                            {{ $settlement->payment_method ?? '-' }}
                                        </td>
                                        <td data-label="{{ __('Reference') }}">
                                            {{ $settlement->reference_number ?? '-' }}
                                        </td>
                                        <td data-label="{{ __('Date') }}">
                                            {{ $settlement->created_at->format('d-m-Y') }}
                                            @if($settlement->processed_at)
                                                <br><small class="text-muted">@lang('Processed'): {{ $settlement->processed_at->format('d-m-Y') }}</small>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center">{{ __('No settlements found') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="d-flex justify-content-center mt-4">
                        {{ $settlements->links() }}
                    </div>

                </div>
            </div>
        </div>
    </div>
@endsection
