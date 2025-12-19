@extends('layouts.admin')

@section('content')
<input type="hidden" id="headerdata" value="{{ __('CURRENCY') }}">
<div class="content-area">
    <div class="mr-breadcrumb">
        <div class="row">
            <div class="col-lg-12">
                <h4 class="heading">{{ __('Currencies') }}</h4>
                <ul class="links">
                    <li>
                        <a href="{{ route('admin.dashboard') }}">{{ __('Dashboard') }} </a>
                    </li>
                    <li>
                        <a href="javascript:;">{{ __('Payment Settings') }}</a>
                    </li>
                    <li>
                        <a href="{{ route('admin-currency-index') }}">{{ __('Currencies') }}</a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
    <div class="product-area">
        <div class="row">
            <div class="col-lg-12">
                <div class="heading-area">
                    <h4 class="title">{{ __('Currency') }} :</h4>
                    <div class="action-list">
                        <select class="process select droplinks {{ $gs->is_currency == 1 ? 'drop-success' : 'drop-danger' }}">
                            <option data-val="1" value="{{ route('admin-gs-status', ['is_currency', 1]) }}" {{ $gs->is_currency == 1 ? 'selected' : '' }}>{{ __('Activated') }}</option>
                            <option data-val="0" value="{{ route('admin-gs-status', ['is_currency', 0]) }}" {{ $gs->is_currency == 0 ? 'selected' : '' }}>{{ __('Deactivated') }}</option>
                        </select>
                    </div>
                </div>
                <div class="mr-table allproduct">
                    @include('alerts.admin.form-success')
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
@include('components.admin.modal-form', ['id' => 'modal1'])
@include('components.admin.modal-delete', ['message' => __('You are about to delete this Currency.')])

@endsection

@section('scripts')
@include('components.admin.datatable-scripts', [
    'tableId' => 'muaadhtable',
    'route' => 'admin-currency-datatables',
    'columns' => [
        ['data' => 'name', 'name' => 'name'],
        ['data' => 'sign', 'name' => 'sign'],
        ['data' => 'value', 'name' => 'value'],
        ['data' => 'action', 'searchable' => false, 'orderable' => false]
    ],
    'addRoute' => 'admin-currency-create',
    'addLabel' => __('Add New Currency'),
    'modalId' => 'modal1'
])
@endsection
