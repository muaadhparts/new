@extends('layouts.merchant')

@section('content')
    <div class="gs-merchant-outlet">
        <!-- breadcrumb start  -->
        <div class="gs-merchant-breadcrumb has-mb">
            <h4 class="text-capitalize">@lang('Dashboard Overview')</h4>
        </div>
        <!-- breadcrumb end -->

        <!-- Info cards area start -->
        <div class="row row-cols-1 row-cols-sm-2 row-cols-xl-3 row-cols-xxl-4 gy-4">
            <div class="col">
                <div class="merchant-panel-info-card order-pending">
                    <img src="{{ asset('assets/front') }}/icons/merchant-dashboard-icon_8.svg" alt="">
                    <div class="title-and-value-wrapper">
                        <p class="title">@lang('Purchases Pending')</p>
                        <h3 class="value">
                            <span class="counter">{{ count($pending) }}</span>
                        </h3>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="merchant-panel-info-card order-processing">
                    <img src="{{ asset('assets/front') }}/icons/merchant-dashboard-icon_7.svg" alt="">
                    <div class="title-and-value-wrapper">
                        <p class="title">@lang('Purchases Processing')</p>
                        <h3 class="value">
                            <span class="counter">{{ count($processing) }}</span>
                        </h3>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="merchant-panel-info-card order-delivered">
                    <img src="{{ asset('assets/front') }}/icons/merchant-dashboard-icon_6.svg" alt="">
                    <div class="title-and-value-wrapper">
                        <p class="title">@lang('Purchases Completed!')</p>
                        <h3 class="value">
                            <span class="counter">{{ count($completed) }}</span>
                        </h3>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="merchant-panel-info-card total-order">
                    <img src="{{ asset('assets/front') }}/icons/merchant-dashboard-icon_5.svg" alt="">
                    <div class="title-and-value-wrapper">
                        <p class="title">@lang('Total CatalogItems!')</p>
                        <h3 class="value">
                            <span class="counter">{{ $user->merchantItems()->count() }}</span>
                        </h3>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="merchant-panel-info-card total-item-sold">
                    <img src="{{ asset('assets/front') }}/icons/merchant-dashboard-icon_1.svg" alt="">
                    <div class="title-and-value-wrapper">
                        <p class="title">@lang('Total Item Sold!')</p>
                        <h3 class="value">
                            <span
                                class="counter">{{ App\Models\MerchantPurchase::where('user_id', '=', $user->id)->where('status', '=', 'completed')->sum('qty') }}</span>
                        </h3>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="merchant-panel-info-card current-balance">
                    <img src="{{ asset('assets/front') }}/icons/merchant-dashboard-icon_2.svg" alt="">
                    <div class="title-and-value-wrapper">
                        <p class="title">@lang('Current Balance')</p>
                        <h3 class="value">{{$curr->sign}}<span class="counter">
                                {{ App\Models\CatalogItem::merchantConvertWithoutCurrencyPrice(auth()->user()->current_balance) }}</span></h3>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="merchant-panel-info-card total-earning">
                    <img src="{{ asset('assets/front') }}/icons/merchant-dashboard-icon_3.svg" alt="">
                    <div class="title-and-value-wrapper">
                        <p class="title">@lang('Total Earning')</p>
                        @php
                            $datas = App\Models\MerchantPurchase::with('purchase')->where('user_id', Auth::user()->id);
                            $totalPrice = $datas->count() > 0 ? $datas->sum('price') : 0;
                        @endphp
                        <h3 class="value">{{$curr->sign}}<span
                                class="counter">{{ App\Models\CatalogItem::merchantConvertWithoutCurrencyPrice($totalPrice) }}</span></h3>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="merchant-panel-info-card pending-commision">
                    <img src="{{ asset('assets/front') }}/icons/merchant-dashboard-icon_4.svg" alt="">
                    <div class="title-and-value-wrapper">
                        <p class="title">@lang('Pending Commision')</p>
                        @php
                            $totalPrice = $user->merchantPurchases->where('status', 'completed')->sum(function ($merchantPurchase) {
                                return $merchantPurchase->price * $merchantPurchase->qty;
                            });
                        @endphp
                        <h3 class="value">{{$curr->sign}}<span
                                class="counter">{{ App\Models\CatalogItem::merchantConvertWithoutCurrencyPrice($user->admin_commission) }}</span>
                        </h3>
                    </div>
                </div>
            </div>
        </div>
        <!-- Info cards area end -->

        <!-- Table area start  -->
        <div class="row gy-4 table-area">
            <!-- Recent CatalogItem(s) Table -->
            <div class="col-xxl-8">
                <div class="merchant-table-wrapper recent-catalogItems-table-wrapper">
                    <h4 class="table-title">@lang('Recent CatalogItem(s)')</h4>
                    <div class="user-table table-responsive">
                        <table id="recent-catalogItem-table" class="gs-data-table w-100">
                            <thead>
                                <tr>
                                    <th class="text-center">
                                        <span class="header-title text-center">@lang('Image')</span>
                                    </th>
                                    <th>
                                        <span class="header-title">@lang('CatalogItem Name')</span>
                                    </th>
                                    <th><span class="header-title">@lang('Brand')</span></th>
                                    <th><span class="header-title">@lang('Quality Brand')</span></th>
                                    <th><span class="header-title">@lang('Price')</span></th>
                                    <th class="text-center">
                                        <span class="header-title">@lang('Details')</span>
                                    </th>
                                </tr>
                            </thead>
                            <tbody>

                                @forelse ($catalogItems as $data)
                                    @php
                                        $merchantItem = $data->merchantItems->first();
                                    @endphp
                                    <!-- table data row 1 start  -->
                                    <tr>
                                        <td>
                                            <img class="table-img"
                                                src="{{ filter_var($data->photo, FILTER_VALIDATE_URL) ? $data->photo : ($data->photo ? \Illuminate\Support\Facades\Storage::url($data->photo) : asset('assets/images/noimage.png')) }}"
                                                alt="">
                                        </td>
                                        <td class="text-start">
                                            <div class="catalogItem-name">
                                                <span class="content">
                                                    {{ getLocalizedCatalogItemName($data, 50) }}
                                                </span>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="content">
                                                {{ $data->brand ? getLocalizedBrandName($data->brand) : __('N/A') }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="content">
                                                {{ $merchantItem && $merchantItem->qualityBrand ? getLocalizedQualityName($merchantItem->qualityBrand) : __('N/A') }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="content">
                                                {{ $merchantItem ? \App\Models\CatalogItem::convertPrice($merchantItem->price) : $data->showPrice() }}
                                            </span>
                                        </td>
                                        <td>
                                            <div class="table-icon-btns-wrapper">
                                                <a href="{{ route('merchant-catalog-item-edit', $merchantItem ? $merchantItem->id : $data->id) }}" class="view-btn">
                                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none"
                                                        xmlns="http://www.w3.org/2000/svg">
                                                        <g clip-path="url(#clip0_548_165892)">
                                                            <path
                                                                d="M12 4.84668C7.41454 4.84668 3.25621 7.35543 0.187788 11.4303C-0.0625959 11.7641 -0.0625959 12.2305 0.187788 12.5644C3.25621 16.6442 7.41454 19.1529 12 19.1529C16.5855 19.1529 20.7438 16.6442 23.8122 12.5693C24.0626 12.2354 24.0626 11.769 23.8122 11.4352C20.7438 7.35543 16.5855 4.84668 12 4.84668ZM12.3289 17.0369C9.28506 17.2284 6.7714 14.7196 6.96287 11.6709C7.11998 9.1572 9.15741 7.11977 11.6711 6.96267C14.7149 6.7712 17.2286 9.27994 17.0371 12.3287C16.8751 14.8375 14.8377 16.8749 12.3289 17.0369ZM12.1767 14.7098C10.537 14.8129 9.18196 13.4628 9.28997 11.8231C9.37343 10.468 10.4732 9.37322 11.8282 9.28485C13.4679 9.18175 14.823 10.5319 14.7149 12.1716C14.6266 13.5316 13.5268 14.6264 12.1767 14.7098Z"
                                                                fill="white" />
                                                        </g>
                                                        <defs>
                                                            <clipPath id="clip0_548_165892">
                                                                <rect width="24" height="24" fill="white" />
                                                            </clipPath>
                                                        </defs>
                                                    </svg>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                    <!-- table data row 1 end  -->
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center">@lang('No CatalogItems Found')</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <!-- Recent Purchase(s) Table  -->
            <div class="col-xxl-4">
                <div class="merchant-table-wrapper recent-orders-table-wrapper">
                    <h4 class="table-title">@lang('Recent Purchase(s)')</h4>
                    <div class="user-table table-responsive">
                        <table id="recent-order-table" class="gs-data-table w-100">
                            <thead>
                                <tr>
                                    <th>
                                        <span class="header-title">@lang('Purchase Number')</span>
                                    </th>
                                    <th><span class="header-title">@lang('Purchase Date')</span></th>
                                    <th class="text-center">
                                        <span class="header-title">@lang('Details')</span>
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($recentMerchantPurchases as $data)
                                    <!-- table data row 1 start  -->
                                    <tr>
                                        <td><span class="content">{{ $data->purchase_number }}</span></td>
                                        <td><span class="content">{{ date('Y-m-d', strtotime($data->created_at)) }}</span>
                                        </td>
                                        <td>
                                            <div class="table-icon-btns-wrapper">
                                                <a href="{{ route('merchant-purchase-show', $data->purchase_number) }}"
                                                    class="view-btn">
                                                    <svg width="24" height="24" viewBox="0 0 24 24"
                                                        fill="none" xmlns="http://www.w3.org/2000/svg">
                                                        <g clip-path="url(#clip0_548_165897)">
                                                            <path
                                                                d="M12 4.84668C7.41454 4.84668 3.25621 7.35543 0.187788 11.4303C-0.0625959 11.7641 -0.0625959 12.2305 0.187788 12.5644C3.25621 16.6442 7.41454 19.1529 12 19.1529C16.5855 19.1529 20.7438 16.6442 23.8122 12.5693C24.0626 12.2354 24.0626 11.769 23.8122 11.4352C20.7438 7.35543 16.5855 4.84668 12 4.84668ZM12.3289 17.0369C9.28506 17.2284 6.7714 14.7196 6.96287 11.6709C7.11998 9.1572 9.15741 7.11977 11.6711 6.96267C14.7149 6.7712 17.2286 9.27994 17.0371 12.3287C16.8751 14.8375 14.8377 16.8749 12.3289 17.0369ZM12.1767 14.7098C10.537 14.8129 9.18196 13.4628 9.28997 11.8231C9.37343 10.468 10.4732 9.37322 11.8282 9.28485C13.4679 9.18175 14.823 10.5319 14.7149 12.1716C14.6266 13.5316 13.5268 14.6264 12.1767 14.7098Z"
                                                                fill="white" />
                                                        </g>
                                                        <defs>
                                                            <clipPath id="clip0_548_165897">
                                                                <rect width="24" height="24" fill="white" />
                                                            </clipPath>
                                                        </defs>
                                                    </svg>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                    <!-- table data row 1 end  -->
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center">@lang('No Data Available')</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <!-- Table area end  -->

        <!-- Chart area start -->
        <div class="gs-chart-wrapper merchant-monthly-sales-chart d-none d-md-block">
            <div class="chart-title-dropdown-wrapper">
                <h4 class="chart-title">@lang('Monthly Sales Overview')</h4>
            </div>
            <div id="chart">
            </div>
        </div>
        <!-- Chart area end -->
    </div>
@endsection

@section('script')
    <script type="text/javascript">
        (function($) {
            "use strict";

            var options = {
                colors: ['#27BE69'],
                series: [{
                        name: 'Net Profit',
                        data: [{!! $sales !!}]
                    },

                ],
                chart: {
                    type: 'bar',
                    height: 450
                },
                plotOptions: {
                    bar: {
                        horizontal: false,
                        columnWidth: '20%',
                        endingShape: 'rounded',
                        borderRadius: 8,
                        borderRadiusApplication: 'end',
                        borderRadiusWhenStacked: 'last'
                    },
                },
                dataLabels: {
                    enabled: false
                },
                stroke: {
                    show: true,
                    width: 2,
                    colors: ['transparent']
                },
                xaxis: {
                    categories: [{!! $days !!}],
                },
                fill: {
                    opacity: 1
                },
                tooltip: {
                    y: {
                        formatter: function(val) {
                            return "$ " + val
                        }
                    }
                }
            };
            var chart = new ApexCharts($("#chart")[0], options);
            chart.render();


        })(jQuery);
    </script>
@endsection
