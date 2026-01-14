@extends('layouts.operator')

@section('content')
@include('operator.accounts._party-list', [
    'title' => __('Shipping Company Accounts'),
    'icon' => 'fas fa-truck',
    'headerColor' => 'warning'
])
@endsection
