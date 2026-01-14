@extends('layouts.operator')

@section('content')
@include('operator.accounts._party-list', [
    'title' => __('Payment Provider Accounts'),
    'icon' => 'fas fa-credit-card',
    'headerColor' => 'success'
])
@endsection
