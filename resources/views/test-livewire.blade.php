@extends('layouts.front')

@section('content')
<div class="container py-5">
    <h1>Livewire Test Page</h1>

    <div class="card mt-4">
        <div class="card-header">
            <h5>Test SearchBoxvin Component</h5>
        </div>
        <div class="card-body">
            <livewire:search-boxvin />
        </div>
    </div>

    <div class="card mt-4">
        <div class="card-header">
            <h5>Browser Console Check</h5>
        </div>
        <div class="card-body">
            <p>Open browser console (F12) and check for:</p>
            <ul>
                <li>JavaScript errors</li>
                <li>Network requests to /livewire/</li>
            </ul>
            <button onclick="console.log('Livewire object:', window.Livewire)" class="btn btn-primary">
                Log Livewire Object
            </button>
        </div>
    </div>
</div>
@endsection
