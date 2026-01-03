@extends('layouts.admin')

@section('content')
<div class="content-area">
    @include('alerts.form-success')

    @if($activation_notify != "")
    <div class="alert alert-danger validation">
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"><span
                aria-hidden="true">×</span></button>
        <h3 class="text-center">{!! clean($activation_notify, array('Attr.EnableID' => true)) !!}</h3>
        
    </div>
    @endif

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
                    <h5 class="title">{{ __('Purchases Pending!') }} </h5>
                    <span class="number">{{count($pending)}}</span>
                    <a href="{{ route('admin-purchases-all') }}?status=pending" class="link">{{ __('View All') }}</a>
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
                    <h5 class="title">{{ __('Purchases Processing!') }}</h5>
                    <span class="number">{{count($processing)}}</span>
                    <a href="{{ route('admin-purchases-all') }}?status=processing" class="link">{{ __('View All') }}</a>
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
                    <h5 class="title">{{ __('Purchases Completed!') }}</h5>
                    <span class="number">{{count($completed)}}</span>
                    <a href="{{ route('admin-purchases-all') }}?status=completed" class="link">{{ __('View All') }}</a>
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
                    <h5 class="title">{{ __('Total Catalog Items!') }}</h5>
                    <span class="number">{{$catalogItems}}</span>
                    <a href="{{route('admin-catalog-item-index')}}" class="link">{{ __('View All') }}</a>
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
                    <h5 class="title">{{ __('Total Customers!') }}</h5>
                    <span class="number">{{$users}}</span>
                    <a href="{{route('admin-user-index')}}" class="link">{{ __('View All') }}</a>
                </div>
                <div class="right d-flex align-self-center">
                    <div class="icon">
                        <i class="icofont-users-alt-5"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-12 col-lg-6 col-xl-4">
            <div class="mycard bg6">
                <div class="left">
                    <h5 class="title">{{ __('Total Posts!') }}</h5>
                    <span class="number">{{$blogs}}</span>
                    <a href="{{ route('admin-blog-index') }}" class="link">{{ __('View All') }}</a>
                </div>
                <div class="right d-flex align-self-center">
                    <div class="icon">
                        <i class="icofont-newspaper"></i>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <div class="row row-cards-one">
        <div class="col-md-6 col-xl-3">
            <div class="card c-info-box-area">
                <div class="c-info-box box1">
                    <p>{{ App\Models\User::where( 'created_at', '>', Carbon\Carbon::now()->subDays(30))->get()->count()  }}</p>
                </div>
                <div class="c-info-box-content">
                    <h6 class="title">{{ __('New Customers') }}</h6>
                    <p class="text">{{ __('Last 30 Days') }}</p>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="card c-info-box-area">
                <div class="c-info-box box2">
                    <p>{{ App\Models\User::count() }}</p>
                </div>
                <div class="c-info-box-content">
                    <h6 class="title">{{ __('Total Customers') }}</h6>
                    <p class="text">{{ __('All Time') }}</p>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="card c-info-box-area">
                <div class="c-info-box box3">
                    <p>{{ App\Models\Purchase::where('status','=','completed')->where( 'created_at', '>', Carbon\Carbon::now()->subDays(30))->get()->count()  }}</p>
                </div>
                <div class="c-info-box-content">
                    <h6 class="title">{{ __('Total Sales') }}</h6>
                    <p class="text">{{ __('Last 30 days') }}</p>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="card c-info-box-area">
                <div class="c-info-box box4">
                     <p>{{ App\Models\Purchase::where('status','=','completed')->get()->count() }}</p>
                </div>
                <div class="c-info-box-content">
                    <h6 class="title">{{ __('Total Sales') }}</h6>
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
                                    <table id="popularMerchantItems" class="table table-hover dt-responsive" cellspacing="0" width="100%">
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
                                        <div class="action-list"><a href="{{ route('admin-purchase-show',$data->id) }}"><i
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
                                    <table id="popularMerchantItems" class="table table-hover dt-responsive" cellspacing="0" width="100%">
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
                                                <div class="action-list"><a href="{{ route('admin-user-show',$data->id) }}"><i
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
                                    <table id="popularMerchantItems" class="table table-hover dt-responsive" cellspacing="0" width="100%">
                                        <thead>
                                            <tr>
                                                <th>{{ __('Featured Image') }}</th>
                                                <th>{{ __('Name') }}</th>
                                                <th>{{ __('Category') }}</th>
                                                <th>{{ __('Type') }}</th>
                                                <th>{{ __('Price') }}</th>
                                                <th></th>
                                                
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($popularMerchantItems as $data)
                                            <tr>
                                            <td><img src="{{filter_var($data->catalogItem->photo ?? '', FILTER_VALIDATE_URL) ? $data->catalogItem->photo : ($data->catalogItem->photo ?? null ? \Illuminate\Support\Facades\Storage::url($data->catalogItem->photo) : asset('assets/images/noimage.png'))}}"></td>
                                            <td>{{ $data->catalogItem ? getLocalizedCatalogItemName($data->catalogItem, 50) : 'N/A' }}</td>
                                            <td>{{ $data->catalogItem && $data->catalogItem->brand ? $data->catalogItem->brand->localized_name : 'N/A' }}</td>
                                                <td>{{ $data->catalogItem->type ?? 'N/A' }}</td>

                                                <td> {{ $data->showPrice() }} </td>

                                                <td>
                                                    <div class="action-list"><a href="{{ route('admin-catalog-item-edit',$data->id) }}"><i
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
                                    <table id="latestMerchantItems" class="table table-hover dt-responsive" cellspacing="0" width="100%">
                                            <thead>
                                                    <tr>
                                                        <th>{{ __('Featured Image') }}</th>
                                                        <th>{{ __('Name') }}</th>
                                                        <th>{{ __('Category') }}</th>
                                                        <th>{{ __('Type') }}</th>
                                                        <th>{{ __('Price') }}</th>
                                                        <th></th>
                                                        
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($latestMerchantItems as $data)
                                                    <tr>
                                                    <td><img src="{{filter_var($data->catalogItem->photo ?? '', FILTER_VALIDATE_URL) ? $data->catalogItem->photo : ($data->catalogItem->photo ?? null ? \Illuminate\Support\Facades\Storage::url($data->catalogItem->photo) : asset('assets/images/noimage.png'))}}"></td>
                                                    <td>{{ $data->catalogItem ? getLocalizedCatalogItemName($data->catalogItem, 50) : 'N/A' }}</td>
                                                    <td>{{ $data->catalogItem && $data->catalogItem->brand ? $data->catalogItem->brand->localized_name : 'N/A' }}</td>
                                                        <td>{{ $data->catalogItem->type ?? 'N/A' }}</td>
                                                        <td> {{ $data->showPrice() }} </td>
                                                        <td>
                                                            <div class="action-list"><a href="{{ route('admin-catalog-item-edit',$data->catalogItem->id ?? $data->id) }}"><i
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




    <div class="row row-cards-one">

        <div class="col-md-12 col-sm-12 col-lg-6 col-xl-6">
            <div class="card">
                <h5 class="card-header">{{ __('Top Referrals') }}</h5>
                <div class="card-body">
                    <div class="admin-fix-height-card">
                         <div id="chartContainer-topReference"></div>
                    </div>
                       
                </div>
            </div>

        </div>

        <div class="col-md-12 col-lg-6 col-sm-12 col-xl-6">
                <div class="card">
                        <h5 class="card-header">{{ __('Most Used OS') }}</h5>
                        <div class="card-body">
                        <div class="admin-fix-height-card">
                            <div id="chartContainer-os"></div>
                        </div>
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

    $('#popularMerchantItems').dataTable( {
      "ordering": false,
          'lengthChange': false,
          'searching'   : false,
          'ordering'    : false,
          'info'        : false,
          'autoWidth'   : false,
          'responsive'  : true,
          'paging'  : false
    } );

    $('#latestMerchantItems').dataTable( {
      "ordering": false,
      'lengthChange': false,
          'searching'   : false,
          'ordering'    : false,
          'info'        : false,
          'autoWidth'   : false,
          'responsive'  : true,
          'paging'  : false
    } );

        var chart1 = new CanvasJS.Chart("chartContainer-topReference",
            {
                exportEnabled: true,
                animationEnabled: true,

                legend: {
                    cursor: "pointer",
                    horizontalAlign: "right",
                    verticalAlign: "center",
                    fontSize: 16,
                    padding: {
                        top: 20,
                        bottom: 2,
                        right: 20,
                    },
                },
                data: [
                    {
                        type: "pie",
                        showInLegend: true,
                        legendText: "",
                        toolTipContent: "{name}: <strong>{#percent%} (#percent%)</strong>",
                        indexLabel: "#percent%",
                        indexLabelFontColor: "white",
                        indexLabelPlacement: "inside",
                        dataPoints: [
                                @foreach($referrals as $browser)
                                    {y:{{$browser->total_count}}, name: "{{$browser->referral}}"},
                                @endforeach
                        ]
                    }
                ]
            });
        chart1.render();

        var chart = new CanvasJS.Chart("chartContainer-os",
            {
                exportEnabled: true,
                animationEnabled: true,
                legend: {
                    cursor: "pointer",
                    horizontalAlign: "right",
                    verticalAlign: "center",
                    fontSize: 16,
                    padding: {
                        top: 20,
                        bottom: 2,
                        right: 20,
                    },
                },
                data: [
                    {
                        type: "pie",
                        showInLegend: true,
                        legendText: "",
                        toolTipContent: "{name}: <strong>{#percent%} (#percent%)</strong>",
                        indexLabel: "#percent%",
                        indexLabelFontColor: "white",
                        indexLabelPlacement: "inside",
                        dataPoints: [
                            @foreach($browsers as $browser)
                                {y:{{$browser->total_count}}, name: "{{$browser->referral}}"},
                            @endforeach
                        ]
                    }
                ]
            });
        chart.render();    

    })(jQuery);
    
</script>

@endsection