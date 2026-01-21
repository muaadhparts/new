@extends('layouts.operator')

@section('content')
<input type="hidden" id="headerdata" value="{{ __('ALTERNATIVES STATISTICS') }}">
<div class="content-area">
    <div class="mr-breadcrumb">
        <div class="row">
            <div class="col-lg-12">
                <h4 class="heading">{{ __('Alternatives Statistics') }}</h4>
                <ul class="links">
                    <li>
                        <a href="{{ route('operator.dashboard') }}">{{ __('Dashboard') }}</a>
                    </li>
                    <li>
                        <a href="{{ route('operator-alternative-index') }}">{{ __('Alternatives') }}</a>
                    </li>
                    <li>
                        <a href="{{ route('operator-alternative-stats') }}">{{ __('Statistics') }}</a>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <div class="catalogItem-area">
        <div class="row">
            {{-- Summary Cards --}}
            <div class="col-md-3 mb-4">
                <div class="card bg-primary text-white h-100">
                    <div class="card-body text-center">
                        <h1 class="display-4">{{ number_format($totalRecords) }}</h1>
                        <h5>{{ __('Total SKUs') }}</h5>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-4">
                <div class="card bg-info text-white h-100">
                    <div class="card-body text-center">
                        <h1 class="display-4">{{ number_format($totalGroups) }}</h1>
                        <h5>{{ __('Total Groups') }}</h5>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-4">
                <div class="card bg-success text-white h-100">
                    <div class="card-body text-center">
                        <h1 class="display-4">{{ number_format($itemsWithAlternatives) }}</h1>
                        <h5>{{ __('Items with Alternatives') }}</h5>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-4">
                <div class="card bg-warning text-dark h-100">
                    <div class="card-body text-center">
                        <h1 class="display-4">{{ number_format($itemsWithoutAlternatives) }}</h1>
                        <h5>{{ __('Items without Alternatives') }}</h5>
                    </div>
                </div>
            </div>

            {{-- Top Items --}}
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <i class="fas fa-trophy me-2"></i>{{ __('Top 10 Items with Most Alternatives') }}
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>{{ __('Group ID') }}</th>
                                        <th>{{ __('Sample Part Number') }}</th>
                                        <th>{{ __('Alternatives Count') }}</th>
                                        <th>{{ __('Actions') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($topItems as $index => $item)
                                        <tr>
                                            <td>{{ $index + 1 }}</td>
                                            <td><span class="badge bg-secondary">{{ $item->group_id }}</span></td>
                                            <td><strong>{{ $item->part_number }}</strong></td>
                                            <td><span class="badge bg-primary">{{ $item->cnt }}</span></td>
                                            <td>
                                                <a href="{{ route('operator-alternative-index', ['q' => $item->part_number]) }}"
                                                   class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-eye"></i> {{ __('View') }}
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="content-area mt-3">
    <a href="{{ route('operator-alternative-index') }}" class="btn btn-outline-secondary">
        <i class="fas fa-arrow-left me-2"></i>{{ __('Back to Alternatives') }}
    </a>
</div>
@endsection
