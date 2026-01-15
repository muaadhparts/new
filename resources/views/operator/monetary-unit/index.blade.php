@extends('layouts.operator')

@section('content')
<input type="hidden" id="headerdata" value="{{ __('MONETARY UNIT') }}">
<div class="content-area">
    <div class="mr-breadcrumb">
        <div class="row">
            <div class="col-lg-12">
                <h4 class="heading">{{ __('Monetary Units') }}</h4>
                <ul class="links">
                    <li>
                        <a href="{{ route('operator.dashboard') }}">{{ __('Dashboard') }} </a>
                    </li>
                    <li>
                        <a href="javascript:;">{{ __('Payment Settings') }}</a>
                    </li>
                    <li>
                        <a href="{{ route('operator-monetary-unit-index') }}">{{ __('Monetary Units') }}</a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
    <div class="catalogItem-area">
        <div class="row">
            <div class="col-lg-12">
                <div class="heading-area">
                    <h4 class="name">{{ __('Monetary Unit') }} :</h4>
                    <div class="action-list">
                        <select class="process select droplinks {{ $gs->is_currency == 1 ? 'drop-success' : 'drop-danger' }}">
                            <option data-val="1" value="{{ route('operator-gs-status', ['is_currency', 1]) }}" {{ $gs->is_currency == 1 ? 'selected' : '' }}>{{ __('Activated') }}</option>
                            <option data-val="0" value="{{ route('operator-gs-status', ['is_currency', 0]) }}" {{ $gs->is_currency == 0 ? 'selected' : '' }}>{{ __('Deactivated') }}</option>
                        </select>
                    </div>
                </div>
                <div class="mr-table allproduct">
                    @include('alerts.operator.form-success')
                    <div class="table-responsive">
                        <table id="muaadhtable" class="table table-hover dt-responsive" cellspacing="0" width="100%">
                            <thead>
                                <tr>
                                    <th>{{ __('Name') }}</th>
                                    <th>{{ __('Sign') }}</th>
                                    <th>{{ __('Value') }}</th>
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
@include('components.operator.modal-delete', ['message' => __('You are about to delete this Monetary Unit.')])

@endsection

@section('scripts')
@include('components.operator.datatable-scripts', [
    'tableId' => 'muaadhtable',
    'route' => 'operator-monetary-unit-datatables',
    'columns' => [
        ['data' => 'name', 'name' => 'name'],
        ['data' => 'sign', 'name' => 'sign'],
        ['data' => 'value', 'name' => 'value'],
        ['data' => 'action', 'searchable' => false, 'orderable' => false]
    ],
    'addRoute' => 'operator-monetary-unit-create',
    'addLabel' => __('Add New Monetary Unit'),
    'modalId' => 'modal1'
])
@endsection
