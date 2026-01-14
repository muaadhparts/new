@extends('layouts.operator')

@section('content')
@include('operator.accounts._party-list', [
    'title' => __('Merchant Accounts'),
    'icon' => 'fas fa-store',
    'headerColor' => 'primary'
])
@endsection
