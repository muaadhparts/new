@extends('layouts.merchant')

@section('content')
<div class="gs-merchant-outlet">
    {{-- Breadcrumb --}}
    <div class="gs-merchant-breadcrumb has-mb">
        <h4 class="text-capitalize">@lang('Payouts')</h4>
        <ul class="breadcrumb-menu">
            <li><a href="{{ route('merchant.dashboard') }}">@lang('Dashboard')</a></li>
            <li><a href="{{ route('merchant.income') }}">@lang('Financial Dashboard')</a></li>
            <li><a href="#">@lang('Payouts')</a></li>
        </ul>
    </div>

    <div class="gs-merchant-erning">
        {{-- Summary Cards --}}
        <div class="row mb-4">
            <div class="col-md-6 mb-3">
                <div class="card bg-warning text-dark h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-uppercase opacity-75">@lang('Pending Amount')</h6>
                                <h2 class="mb-0">{{ $pending_amount }}</h2>
                                <small>@lang('Awaiting settlement from platform')</small>
                            </div>
                            <div class="opacity-50">
                                <i class="fas fa-clock fa-3x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 mb-3">
                <div class="card bg-success text-white h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-uppercase opacity-75">@lang('Total Received')</h6>
                                <h2 class="mb-0">{{ $total_received }}</h2>
                                <small>@lang('All time settlements')</small>
                            </div>
                            <div class="opacity-50">
                                <i class="fas fa-check-circle fa-3x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Info Alert --}}
        @if($pending_raw > 0)
        <div class="alert alert-info d-flex align-items-center mb-4">
            <i class="fas fa-info-circle me-3 fa-2x"></i>
            <div>
                <strong>@lang('Settlement Information')</strong>
                <p class="mb-0">
                    @lang('You have :amount pending settlement. Settlements are processed periodically by the platform admin.', ['amount' => $pending_amount])
                </p>
            </div>
        </div>
        @endif

        {{-- Payouts Table --}}
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-money-bill-wave me-2"></i>
                    @lang('Settlement History')
                </h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0" id="payoutsTable">
                        <thead class="table-light">
                            <tr>
                                <th>@lang('Reference')</th>
                                <th>@lang('Date')</th>
                                <th>@lang('Amount')</th>
                                <th>@lang('Payment Method')</th>
                                <th class="text-center">@lang('Status')</th>
                                <th>@lang('Notes')</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($payouts as $payout)
                            <tr>
                                <td>
                                    <strong>{{ $payout->batch_ref }}</strong>
                                </td>
                                <td>
                                    {{ $payout->date_formatted }}
                                </td>
                                <td>
                                    <span class="text-success fw-bold">
                                        {{ $payout->amount_formatted }}
                                    </span>
                                </td>
                                <td>
                                    @switch($payout->payment_method)
                                        @case('bank_transfer')
                                            <i class="fas fa-university me-1"></i> @lang('Bank Transfer')
                                            @break
                                        @case('cash')
                                            <i class="fas fa-money-bill me-1"></i> @lang('Cash')
                                            @break
                                        @case('cheque')
                                            <i class="fas fa-money-check me-1"></i> @lang('Cheque')
                                            @break
                                        @default
                                            {{ $payout->payment_method ?? '-' }}
                                    @endswitch
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-{{ $payout->status_color }}">
                                        {{ $payout->status_name_ar }}
                                    </span>
                                </td>
                                <td>
                                    @if($payout->payment_reference)
                                        <small class="text-muted">
                                            <i class="fas fa-hashtag"></i> {{ $payout->payment_reference }}
                                        </small>
                                    @endif
                                    @if($payout->notes)
                                        <br><small class="text-muted">{{ Str::limit($payout->notes, 50) }}</small>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="text-center py-5">
                                    <div class="text-muted">
                                        <i class="fas fa-inbox fa-3x mb-3 d-block"></i>
                                        <h5>@lang('No Payouts Yet')</h5>
                                        <p>@lang('Settlement payments will appear here once processed.')</p>
                                    </div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if($payouts->hasPages())
            <div class="card-footer">
                {{ $payouts->links() }}
            </div>
            @endif
        </div>

        {{-- How it works --}}
        <div class="card mt-4">
            <div class="card-header">
                <h6 class="mb-0"><i class="fas fa-question-circle me-2"></i>@lang('How Settlements Work')</h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4 text-center mb-3">
                        <div class="rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">
                            <i class="fas fa-shopping-cart fa-lg"></i>
                        </div>
                        <h6>@lang('1. Orders Received')</h6>
                        <small class="text-muted">@lang('Customers place orders through the platform')</small>
                    </div>
                    <div class="col-md-4 text-center mb-3">
                        <div class="rounded-circle bg-warning text-dark d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">
                            <i class="fas fa-clock fa-lg"></i>
                        </div>
                        <h6>@lang('2. Balance Accumulates')</h6>
                        <small class="text-muted">@lang('Your net earnings accumulate in pending balance')</small>
                    </div>
                    <div class="col-md-4 text-center mb-3">
                        <div class="rounded-circle bg-success text-white d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">
                            <i class="fas fa-money-bill-wave fa-lg"></i>
                        </div>
                        <h6>@lang('3. Settlement Processed')</h6>
                        <small class="text-muted">@lang('Platform processes payouts periodically')</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
<script type="text/javascript">
    if ($.fn.DataTable) {
        $('#payoutsTable').DataTable({
            order: [[1, 'desc']],
            pageLength: 10,
            language: {
                lengthMenu: "@lang('Show _MENU_ entries')",
                info: "@lang('Showing _START_ to _END_ of _TOTAL_ entries')",
                search: "@lang('Search:')",
                paginate: {
                    first: "@lang('First')",
                    last: "@lang('Last')",
                    next: "@lang('Next')",
                    previous: "@lang('Previous')"
                },
                emptyTable: "@lang('No data available')"
            }
        });
    }
</script>
@endsection
