@extends('layouts.operator')

@section('content')
<div class="content-area">
    @include('alerts.form-success')

    @if(Session::has('cache'))

    <div class="alert alert-success validation">
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"><span
                aria-hidden="true">×</span></button>
        <h3 class="text-center">{{ Session::get("cache") }}</h3>
    </div>

    @endif

    <div class="row row-cards-one">
        <div class="col-md-12 col-lg-6 col-xl-4">
            <div class="mycard bg1">
                <div class="left">
                    <h5 class="name">{{ __('Purchases Pending!') }} </h5>
                    <span class="number">{{ $pending }}</span>
                    <a href="{{ route('operator-purchases-all') }}?status=pending" class="link">{{ __('View All') }}</a>
                </div>
                <div class="right d-flex align-self-center">
                    <div class="icon">
                        <i class="icofont-dollar"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-12 col-lg-6 col-xl-4">
            <div class="mycard bg2">
                <div class="left">
                    <h5 class="name">{{ __('Purchases Processing!') }}</h5>
                    <span class="number">{{ $processing }}</span>
                    <a href="{{ route('operator-purchases-all') }}?status=processing" class="link">{{ __('View All') }}</a>
                </div>
                <div class="right d-flex align-self-center">
                    <div class="icon">
                        <i class="icofont-truck-alt"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-12 col-lg-6 col-xl-4">
            <div class="mycard bg3">
                <div class="left">
                    <h5 class="name">{{ __('Purchases Completed!') }}</h5>
                    <span class="number">{{ $completed }}</span>
                    <a href="{{ route('operator-purchases-all') }}?status=completed" class="link">{{ __('View All') }}</a>
                </div>
                <div class="right d-flex align-self-center">
                    <div class="icon">
                        <i class="icofont-check-circled"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-12 col-lg-6 col-xl-4">
            <div class="mycard bg4">
                <div class="left">
                    <h5 class="name">{{ __('Total Catalog Items!') }}</h5>
                    <span class="number">{{$catalogItems}}</span>
                    <a href="{{route('operator-catalog-item-index')}}" class="link">{{ __('View All') }}</a>
                </div>
                <div class="right d-flex align-self-center">
                    <div class="icon">
                        <i class="icofont-cart-alt"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-12 col-lg-6 col-xl-4">
            <div class="mycard bg5">
                <div class="left">
                    <h5 class="name">{{ __('Total Customers!') }}</h5>
                    <span class="number">{{$users}}</span>
                    <a href="{{route('operator-user-index')}}" class="link">{{ __('View All') }}</a>
                </div>
                <div class="right d-flex align-self-center">
                    <div class="icon">
                        <i class="icofont-users-alt-5"></i>
                    </div>
                </div>
            </div>
        </div>
        {{-- PUBLICATIONS SECTION REMOVED - Feature deleted --}}

    </div>

    {{-- Statistics cards - Pre-computed in DashboardStatisticsService (DATA_FLOW_POLICY) --}}
    <div class="row row-cards-one">
        <div class="col-md-6 col-xl-3">
            <div class="card c-info-box-area">
                <div class="c-info-box box1">
                    <p>{{ $newCustomersLast30Days }}</p>
                </div>
                <div class="c-info-box-content">
                    <h6 class="name">{{ __('New Customers') }}</h6>
                    <p class="text">{{ __('Last 30 Days') }}</p>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="card c-info-box-area">
                <div class="c-info-box box2">
                    <p>{{ $users }}</p>
                </div>
                <div class="c-info-box-content">
                    <h6 class="name">{{ __('Total Customers') }}</h6>
                    <p class="text">{{ __('All Time') }}</p>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="card c-info-box-area">
                <div class="c-info-box box3">
                    <p>{{ $totalSalesLast30Days }}</p>
                </div>
                <div class="c-info-box-content">
                    <h6 class="name">{{ __('Total Sales') }}</h6>
                    <p class="text">{{ __('Last 30 days') }}</p>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="card c-info-box-area">
                <div class="c-info-box box4">
                     <p>{{ $totalSalesAllTime }}</p>
                </div>
                <div class="c-info-box-content">
                    <h6 class="name">{{ __('Total Sales') }}</h6>
                    <p class="text">{{ __('All Time') }}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row row-cards-one">

        <div class="col-md-12 col-lg-6 col-sm-12 col-xl-6">
            <div class="card">
                <h5 class="card-header">{{ __('Recent Purchase(s)') }}</h5>
                <div class="card-body">

                <div class="table-responsive  dashboard-home-table">
                                    <table id="recentPurchases" class="table table-hover dt-responsive" cellspacing="0" width="100%">
                            <thead>
                                <tr>

                                    <th>{{ __('Purchase Number') }}</th>
                                    <th>{{ __('Purchase Date') }}</th>
                                </tr>
                                @foreach($recentPurchases as $data)
                                <tr>
                                    <td>{{ $data->purchase_number }}</td>
                                    <td>{{ date('Y-m-d',strtotime($data->created_at)) }}</td>
                                    <td>
                                        <div class="action-list"><a href="{{ route('operator-purchase-show',$data->id) }}"><i
                                                    class="fas fa-eye"></i> {{ __('Details') }}</a>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </thead>
                        </table>
                    </div>

                </div>
            </div>

        </div>

        <div class="col-md-12 col-lg-6 col-sm-12 col-xl-6">
                <div class="card">
                        <h5 class="card-header">{{ __('Recent Customer(s)') }}</h5>
                        <div class="card-body">
        
                             <div class="table-responsive  dashboard-home-table">
                                    <table id="recentUsers" class="table table-hover dt-responsive" cellspacing="0" width="100%">
                                    <thead>
                                        <tr>
                                            <th>{{ __('Customer Email') }}</th>
                                            <th>{{ __('Joined') }}</th>
                                        </tr>
                                        @foreach($recentUsers as $data)
                                        <tr>
                                            <td>{{ $data->email }}</td>
                                            <td>{{ $data->created_at }}</td>
                                            <td>
                                                <div class="action-list"><a href="{{ route('operator-user-show',$data->id) }}"><i
                                                            class="fas fa-eye"></i> {{ __('Details') }}</a>
                                                </div>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </thead>
                                </table>
                            </div>
        
                        </div>
                    </div>
        </div>
    </div>

    <div class="row row-cards-one">

            <div class="col-md-12 col-lg-12 col-sm-12 col-xl-12">
                    <div class="card">
                            <h5 class="card-header">{{ __('Popular CatalogItem(s)') }}</h5>
                            <div class="card-body">

                                <div class="table-responsive  dashboard-home-table">
                                    <table id="popularCatalogItems" class="table table-hover dt-responsive" cellspacing="0" width="100%">
                                        <thead>
                                            <tr>
                                                <th>{{ __('Featured Image') }}</th>
                                                <th>{{ __('Name') }}</th>
                                                <th>{{ __('Brand') }}</th>
                                                <th>{{ __('Views') }}</th>
                                                <th>{{ __('Price') }}</th>
                                                <th></th>

                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($popularCatalogItems as $catalogItem)
                                            <tr>
                                            <td><img src="{{ $catalogItem->photo_url }}"></td>
                                            <td>{{ $catalogItem->localized_name }}</td>
                                            <td>{{ $catalogItem->first_brand_name }}</td>
                                                <td>{{ $catalogItem->views ?? 0 }}</td>

                                                <td>{{ $catalogItem->best_merchant_item?->showPrice() ?? 'N/A' }}</td>

                                                <td>
                                                    <div class="action-list"><a href="{{ $catalogItem->best_merchant_item ? route('operator-catalog-item-edit', $catalogItem->best_merchant_item->id) : '#' }}"><i
                                                                class="fas fa-eye"></i> {{ __('Details') }}</a>
                                                    </div>
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

    <div class="row row-cards-one">

            <div class="col-md-12 col-lg-12 col-sm-12 col-xl-12">
                    <div class="card">
                            <h5 class="card-header">{{ __('Recent CatalogItem(s)') }}</h5>
                            <div class="card-body">

                                <div class="table-responsive dashboard-home-table">
                                    <table id="latestCatalogItems" class="table table-hover dt-responsive" cellspacing="0" width="100%">
                                            <thead>
                                                    <tr>
                                                        <th>{{ __('Featured Image') }}</th>
                                                        <th>{{ __('Name') }}</th>
                                                        <th>{{ __('Brand') }}</th>
                                                        <th>{{ __('Part Number') }}</th>
                                                        <th>{{ __('Price') }}</th>
                                                        <th></th>

                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($latestCatalogItems as $catalogItem)
                                                    <tr>
                                                    <td><img src="{{ $catalogItem->photo_url }}"></td>
                                                    <td>{{ $catalogItem->localized_name }}</td>
                                                    <td>{{ $catalogItem->first_brand_name }}</td>
                                                        <td>{{ $catalogItem->part_number ?? 'N/A' }}</td>
                                                        <td>{{ $catalogItem->best_merchant_item?->showPrice() ?? 'N/A' }}</td>
                                                        <td>
                                                            <div class="action-list"><a href="{{ $catalogItem->best_merchant_item ? route('operator-catalog-item-edit', $catalogItem->best_merchant_item->id) : '#' }}"><i
                                                                        class="fas fa-eye"></i> {{ __('Details') }}</a>
                                                            </div>
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

    <div class="row row-cards-one">

        <div class="col-md-12 col-lg-12 col-sm-12 col-xl-12">
            <div class="card">
                <h5 class="card-header">{{ __('Total Sales in Last 30 Days') }}</h5>
                <div class="card-body">

                    <canvas  id="lineChart"></canvas>

                </div>
            </div>

        </div>

    </div>




</div>

@endsection

@section('scripts')

<script type="text/javascript">
    
    (function($) {
		"use strict";

    displayLineChart();

    function displayLineChart() {
        var data = {
            labels: [
            {!!$days!!}
            ],
            datasets: [{
                label: "Prime and Fibonacci",
                fillColor: "#3dbcff",
                strokeColor: "#0099ff",
                pointColor: "rgba(220,220,220,1)",
                pointStrokeColor: "#fff",
                pointHighlightFill: "#fff",
                pointHighlightStroke: "rgba(220,220,220,1)",
                data: [
                {!!$sales!!}
                ]
            }]
        };
        var ctx = document.getElementById("lineChart").getContext("2d");
        var options = {
            responsive: true
        };
        var lineChart = new Chart(ctx).Line(data, options);
    }

    $('#popularCatalogItems').dataTable( {
      "ordering": false,
          'lengthChange': false,
          'searching'   : false,
          'ordering'    : false,
          'info'        : false,
          'autoWidth'   : false,
          'responsive'  : true,
          'paging'  : false
    } );

    $('#latestCatalogItems').dataTable( {
      "ordering": false,
      'lengthChange': false,
          'searching'   : false,
          'ordering'    : false,
          'info'        : false,
          'autoWidth'   : false,
          'responsive'  : true,
          'paging'  : false
    } );

    })(jQuery);
    
</script>

@endsection