@extends('layouts.operator')

@section('content')
@include('operator.accounts._party-list', [
    'name' => __('Shipping Company Accounts'),
    'icon' => 'fas fa-truck',
    'headerColor' => 'warning'
])
@endsection
