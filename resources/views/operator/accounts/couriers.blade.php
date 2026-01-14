@extends('layouts.operator')

@section('content')
@include('operator.accounts._party-list', [
    'title' => __('Courier Accounts'),
    'icon' => 'fas fa-motorcycle',
    'headerColor' => 'info'
])
@endsection
