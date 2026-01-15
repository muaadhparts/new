@extends('layouts.operator')

@section('content')
@include('operator.accounts._party-list', [
    'name' => __('Merchant Accounts'),
    'icon' => 'fas fa-store',
    'headerColor' => 'primary'
])
@endsection
