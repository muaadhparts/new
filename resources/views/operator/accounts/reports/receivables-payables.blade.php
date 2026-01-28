@extends('layouts.operator')

@section('content')
<div class="content-area">
    <div class="mr-breadcrumb">
        <div class="row">
            <div class="col-lg-12">
                <h4 class="heading">{{ __('Receivables & Payables Report') }}</h4>
                <ul class="links">
                    <li><a href="{{ route('operator.dashboard') }}">{{ __('Dashboard') }}</a></li>
                    <li><a href="{{ route('operator.accounts.index') }}">{{ __('Accounts') }}</a></li>
                    <li><a href="javascript:;">{{ __('Receivables/Payables') }}</a></li>
                </ul>
            </div>
        </div>
    </div>

    @include('alerts.operator.form-both')

    {{-- Net Position Summary --}}
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card bg-primary text-white h-100">
                <div class="card-body text-center">
                    <h6>{{ __('Total Receivables') }}</h6>
                    <h2 class="mb-0">{{ $reportDisplay['receivables_total_formatted'] }}</h2>
                    <small>{{ __('Owed to Platform') }}</small>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-danger text-white h-100">
                <div class="card-body text-center">
                    <h6>{{ __('Total Payables') }}</h6>
                    <h2 class="mb-0">{{ $reportDisplay['payables_total_formatted'] }}</h2>
                    <small>{{ __('Platform Owes') }}</small>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card {{ $reportDisplay['net_position'] >= 0 ? 'bg-success' : 'bg-warning' }} text-white h-100">
                <div class="card-body text-center">
                    <h6>{{ __('Net Position') }}</h6>
                    <h2 class="mb-0">{{ $reportDisplay['net_position_formatted'] }}</h2>
                    <small>{{ $reportDisplay['net_position'] >= 0 ? __('Net Receivable') : __('Net Payable') }}</small>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        {{-- Receivables Column --}}
        <div class="col-md-6">
            <div class="card border-primary mb-4">
                <div class="card-header bg-primary text-white">
                    <i class="fas fa-hand-holding-usd me-2"></i>{{ __('Receivables (Owed TO Platform)') }}
                </div>
                <div class="card-body">
                    {{-- From Merchants --}}
                    @if($reportDisplay['receivables']['from_merchants']->count() > 0)
                    <h6 class="text-muted"><i class="fas fa-store me-1"></i> {{ __('From Merchants') }}</h6>
                    <table class="table table-sm mb-4">
                        <thead>
                            <tr>
                                <th>{{ __('Merchant') }}</th>
                                <th class="text-end">{{ __('Amount') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($reportDisplay['receivables']['from_merchants'] as $balance)
                            <tr>
                                <td>{{ $balance->counterparty->name }}</td>
                                <td class="text-end text-primary fw-bold">{{ $balance->pending_amount_formatted }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="table-primary">
                            <tr>
                                <th>{{ __('Subtotal') }}</th>
                                <th class="text-end">{{ $reportDisplay['receivables']['from_merchants_subtotal_formatted'] }}</th>
                            </tr>
                        </tfoot>
                    </table>
                    @endif

                    {{-- From Couriers --}}
                    @if($reportDisplay['receivables']['from_couriers']->count() > 0)
                    <h6 class="text-muted"><i class="fas fa-motorcycle me-1"></i> {{ __('From Couriers') }}</h6>
                    <table class="table table-sm mb-4">
                        <thead>
                            <tr>
                                <th>{{ __('Courier') }}</th>
                                <th class="text-end">{{ __('Amount') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($reportDisplay['receivables']['from_couriers'] as $balance)
                            <tr>
                                <td>{{ $balance->counterparty->name }}</td>
                                <td class="text-end text-primary fw-bold">{{ $balance->pending_amount_formatted }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="table-primary">
                            <tr>
                                <th>{{ __('Subtotal') }}</th>
                                <th class="text-end">{{ $reportDisplay['receivables']['from_couriers_subtotal_formatted'] }}</th>
                            </tr>
                        </tfoot>
                    </table>
                    @endif

                    {{-- From Shipping --}}
                    @if($reportDisplay['receivables']['from_shipping']->count() > 0)
                    <h6 class="text-muted"><i class="fas fa-truck me-1"></i> {{ __('From Shipping Companies') }}</h6>
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>{{ __('Company') }}</th>
                                <th class="text-end">{{ __('Amount') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($reportDisplay['receivables']['from_shipping'] as $balance)
                            <tr>
                                <td>{{ $balance->counterparty->name }}</td>
                                <td class="text-end text-primary fw-bold">{{ $balance->pending_amount_formatted }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="table-primary">
                            <tr>
                                <th>{{ __('Subtotal') }}</th>
                                <th class="text-end">{{ $reportDisplay['receivables']['from_shipping_subtotal_formatted'] }}</th>
                            </tr>
                        </tfoot>
                    </table>
                    @endif

                    @if($reportDisplay['receivables']['total'] == 0)
                    <p class="text-muted text-center py-4">{{ __('No receivables at this time') }}</p>
                    @endif
                </div>
            </div>
        </div>

        {{-- Payables Column --}}
        <div class="col-md-6">
            <div class="card border-danger mb-4">
                <div class="card-header bg-danger text-white">
                    <i class="fas fa-file-invoice-dollar me-2"></i>{{ __('Payables (Platform OWES)') }}
                </div>
                <div class="card-body">
                    {{-- To Merchants --}}
                    @if($reportDisplay['payables']['to_merchants']->count() > 0)
                    <h6 class="text-muted"><i class="fas fa-store me-1"></i> {{ __('To Merchants') }}</h6>
                    <table class="table table-sm mb-4">
                        <thead>
                            <tr>
                                <th>{{ __('Merchant') }}</th>
                                <th class="text-end">{{ __('Amount') }}</th>
                                <th>{{ __('Action') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($reportDisplay['payables']['to_merchants'] as $balance)
                            <tr>
                                <td>{{ $balance->counterparty->name }}</td>
                                <td class="text-end text-danger fw-bold">{{ $balance->pending_amount_formatted }}</td>
                                <td>
                                    <a href="{{ route('operator.accounts.settlements.create', ['party_id' => $balance->counterparty_id]) }}"
                                       class="btn btn-sm btn-outline-success" name="{{ __('Pay') }}">
                                        <i class="fas fa-money-bill"></i>
                                    </a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="table-danger">
                            <tr>
                                <th>{{ __('Subtotal') }}</th>
                                <th class="text-end">{{ $reportDisplay['payables']['to_merchants_subtotal_formatted'] }}</th>
                                <th></th>
                            </tr>
                        </tfoot>
                    </table>
                    @endif

                    {{-- To Tax Authority --}}
                    @if($reportDisplay['payables']['to_tax_authority']->count() > 0)
                    <h6 class="text-muted"><i class="fas fa-receipt me-1"></i> {{ __('To Tax Authority') }}</h6>
                    <table class="table table-sm mb-4">
                        <thead>
                            <tr>
                                <th>{{ __('Authority') }}</th>
                                <th class="text-end">{{ __('Amount') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($reportDisplay['payables']['to_tax_authority'] as $balance)
                            <tr>
                                <td>{{ $balance->counterparty->name }}</td>
                                <td class="text-end text-danger fw-bold">{{ $balance->pending_amount_formatted }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="table-danger">
                            <tr>
                                <th>{{ __('Subtotal') }}</th>
                                <th class="text-end">{{ $reportDisplay['payables']['to_tax_authority_subtotal_formatted'] }}</th>
                            </tr>
                        </tfoot>
                    </table>
                    @endif

                    {{-- To Shipping --}}
                    @if($reportDisplay['payables']['to_shipping']->count() > 0)
                    <h6 class="text-muted"><i class="fas fa-truck me-1"></i> {{ __('To Shipping Companies') }}</h6>
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>{{ __('Company') }}</th>
                                <th class="text-end">{{ __('Amount') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($reportDisplay['payables']['to_shipping'] as $balance)
                            <tr>
                                <td>{{ $balance->counterparty->name }}</td>
                                <td class="text-end text-danger fw-bold">{{ $balance->pending_amount_formatted }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="table-danger">
                            <tr>
                                <th>{{ __('Subtotal') }}</th>
                                <th class="text-end">{{ $reportDisplay['payables']['to_shipping_subtotal_formatted'] }}</th>
                            </tr>
                        </tfoot>
                    </table>
                    @endif

                    @if($reportDisplay['payables']['total'] == 0)
                    <p class="text-muted text-center py-4">{{ __('No payables at this time') }}</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Aging Analysis --}}
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-clock me-2"></i>{{ __('Aging Analysis') }}
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-3">
                    <div class="p-3 bg-success text-white rounded text-center">
                        <h6>{{ __('Current (0-30 days)') }}</h6>
                        <h4 class="mb-0">{{ $reportDisplay['aging']['current_formatted'] }}</h4>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="p-3 bg-info text-white rounded text-center">
                        <h6>{{ __('30-60 days') }}</h6>
                        <h4 class="mb-0">{{ $reportDisplay['aging']['30_60_formatted'] }}</h4>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="p-3 bg-warning text-white rounded text-center">
                        <h6>{{ __('60-90 days') }}</h6>
                        <h4 class="mb-0">{{ $reportDisplay['aging']['60_90_formatted'] }}</h4>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="p-3 bg-danger text-white rounded text-center">
                        <h6>{{ __('Over 90 days') }}</h6>
                        <h4 class="mb-0">{{ $reportDisplay['aging']['over_90_formatted'] }}</h4>
                    </div>
                </div>
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
