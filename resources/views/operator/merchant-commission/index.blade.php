@extends('layouts.operator')

@section('content')
<input type="hidden" id="headerdata" value="{{ __('MERCHANT COMMISSIONS') }}">
<div class="content-area">
    <div class="mr-breadcrumb">
        <div class="row">
            <div class="col-lg-12">
                <h4 class="heading">{{ __('Merchant Commissions') }}</h4>
                <ul class="links">
                    <li>
                        <a href="{{ route('operator.dashboard') }}">{{ __('Dashboard') }}</a>
                    </li>
                    <li>
                        <a href="javascript:;">{{ __('Merchants') }}</a>
                    </li>
                    <li>
                        <a href="{{ route('operator-merchant-commission-index') }}">{{ __('Commissions') }}</a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
    <div class="catalogItem-area">
        <div class="row">
            <div class="col-lg-12">
                <div class="heading-area">
                    <h4 class="name">{{ __('Merchant Commission Settings') }}</h4>
                </div>
                <div class="mr-table allproduct">
                    @include('alerts.operator.form-success')
                    <div class="table-responsive">
                        <table id="muaadhtable" class="table table-hover dt-responsive" cellspacing="0" width="100%">
                            <thead>
                                <tr>
                                    <th>{{ __('Merchant') }}</th>
                                    <th>{{ __('Shop Name') }}</th>
                                    <th>{{ __('Fixed Commission') }}</th>
                                    <th>{{ __('Percentage') }}</th>
                                    <th>{{ __('Status') }}</th>
                                    <th>{{ __('Options') }}</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- MODALS --}}
@include('components.operator.modal-form', ['id' => 'modal1'])

@endsection

@section('scripts')
@include('components.operator.datatable-scripts', [
    'tableId' => 'muaadhtable',
    'route' => 'operator-merchant-commission-datatables',
    'columns' => [
        ['data' => 'name', 'name' => 'name'],
        ['data' => 'shop', 'name' => 'shop'],
        ['data' => 'fixed_commission', 'name' => 'fixed_commission'],
        ['data' => 'percentage_commission', 'name' => 'percentage_commission'],
        ['data' => 'status', 'name' => 'status'],
        ['data' => 'action', 'searchable' => false, 'orderable' => false]
    ],
    'modalId' => 'modal1'
])
@endsection
