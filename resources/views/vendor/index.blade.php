@extends('layouts.unified')
@php
    $isDashboard = true;
    $isVendor = true;
@endphp

@section('content')
    <style>
        /* Modern Teal/Cyan Vendor Dashboard Theme */
        .gs-vendor-outlet {
            background: linear-gradient(135deg, #f0fdfa 0%, #ffffff 100%);
            min-height: 100vh;
            padding: 2rem;
        }

        .gs-vendor-breadcrumb {
            background: linear-gradient(135deg, #0d9488 0%, #14b8a6 100%);
            padding: 2rem;
            border-radius: 18px;
            margin-bottom: 2.5rem;
            box-shadow: 0 8px 24px rgba(13, 148, 136, 0.2);
        }

        .gs-vendor-breadcrumb h4 {
            color: #ffffff !important;
            font-size: 1.8rem;
            font-weight: 700;
            letter-spacing: 0.5px;
            margin: 0;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        /* Info Cards - Teal Theme */
        .vendor-panel-info-card {
            background: linear-gradient(135deg, #ffffff 0%, #f0fdfa 100%);
            border-radius: 18px;
            padding: 1.8rem;
            display: flex;
            align-items: center;
            gap: 1.5rem;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            border: 2px solid #e0f2fe;
            box-shadow: 0 4px 12px rgba(13, 148, 136, 0.08);
        }

        .vendor-panel-info-card:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: 0 12px 32px rgba(13, 148, 136, 0.2);
            border-color: #14b8a6;
        }

        .vendor-panel-info-card img {
            width: 60px;
            height: 60px;
            filter: drop-shadow(0 4px 8px rgba(13, 148, 136, 0.2));
            transition: transform 0.3s ease;
        }

        .vendor-panel-info-card:hover img {
            transform: scale(1.15) rotate(8deg);
        }

        .vendor-panel-info-card .title {
            color: #64748b;
            font-size: 0.9rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            letter-spacing: 0.3px;
        }

        .vendor-panel-info-card .value {
            background: linear-gradient(135deg, #0d9488 0%, #14b8a6 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-size: 2rem;
            font-weight: 800;
            margin: 0;
        }

        /* Alert Styling */
        .alert-warning {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            border: 2px solid #fbbf24;
            border-radius: 14px;
            color: #92400e;
            font-weight: 500;
        }

        .alert-warning .alert-link {
            color: #0d9488;
            font-weight: 700;
            text-decoration: underline;
        }

        .alert-warning .alert-link:hover {
            color: #14b8a6;
        }

        /* Table Styling - Modern Teal */
        .vendor-table-wrapper {
            background: #ffffff;
            border-radius: 20px;
            padding: 2rem;
            margin-top: 2.5rem;
            box-shadow: 0 4px 16px rgba(13, 148, 136, 0.1);
            border: 2px solid #e0f2fe;
        }

        .table-title {
            color: #0f172a;
            font-size: 1.4rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 3px solid;
            border-image: linear-gradient(90deg, #0d9488 0%, #14b8a6 50%, #2dd4bf 100%) 1;
        }

        .gs-data-table thead {
            background: linear-gradient(135deg, #0d9488 0%, #14b8a6 100%);
        }

        .gs-data-table thead th {
            border: none !important;
            padding: 1.2rem 1rem;
            color: #ffffff !important;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
        }

        .gs-data-table thead th:first-child {
            border-radius: 12px 0 0 0;
        }

        .gs-data-table thead th:last-child {
            border-radius: 0 12px 0 0;
        }

        .gs-data-table tbody tr {
            transition: all 0.3s ease;
            border-bottom: 1px solid #e0f2fe;
        }

        .gs-data-table tbody tr:hover {
            background: linear-gradient(135deg, #f0fdfa 0%, #e0f2fe 100%);
            transform: scale(1.01);
            box-shadow: 0 2px 8px rgba(13, 148, 136, 0.1);
        }

        .gs-data-table tbody td {
            padding: 1.2rem 1rem;
            vertical-align: middle;
            color: #334155;
            font-weight: 500;
        }

        .view-btn {
            background: linear-gradient(135deg, #0d9488 0%, #14b8a6 100%);
            width: 40px;
            height: 40px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 4px 12px rgba(13, 148, 136, 0.3);
        }

        .view-btn:hover {
            transform: translateY(-4px) rotate(8deg) scale(1.1);
            box-shadow: 0 8px 20px rgba(13, 148, 136, 0.4);
            background: linear-gradient(135deg, #14b8a6 0%, #2dd4bf 100%);
        }

        /* Chart Styling */
        .gs-chart-wrapper {
            background: #ffffff;
            border-radius: 20px;
            padding: 2rem;
            margin-top: 2.5rem;
            box-shadow: 0 4px 16px rgba(13, 148, 136, 0.1);
            border: 2px solid #e0f2fe;
        }

        .chart-title {
            color: #0f172a;
            font-size: 1.4rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            background: linear-gradient(135deg, #0d9488 0%, #14b8a6 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .table-img {
            border-radius: 12px;
            border: 2px solid #e0f2fe;
            transition: all 0.3s ease;
        }

        .table-img:hover {
            border-color: #14b8a6;
            transform: scale(1.1);
        }
    </style>

    <div class="gs-vendor-outlet">
        <!-- breadcrumb start  -->
        <div class="gs-vendor-breadcrumb has-mb">
            <h4 class="text-capitalize">@lang('Dashboard Overview')</h4>
        </div>
        <!-- breadcrumb end -->

        <!-- Warehouse Alert start -->
        @if(empty(auth()->user()->warehouse_city))
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <strong>@lang('Warehouse Location Not Set!')</strong>
            @lang('Please set your warehouse location for accurate shipping calculations.')
            <a href="{{ route('vendor-warehouse-index') }}" class="alert-link">@lang('Set Location Now')</a>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        @endif
        <!-- Warehouse Alert end -->

        <!-- Info cards area start -->
        <div class="row row-cols-1 row-cols-sm-2 row-cols-xl-3 row-cols-xxl-4 gy-4">
            <div class="col">
                <div class="vendor-panel-info-card order-pending">
                    <img src="{{ asset('assets/front') }}/icons/vendor-dashboard-icon_8.svg" alt="">
                    <div class="title-and-value-wrapper">
                        <p class="title">@lang('Order Pending')</p>
                        <h3 class="value">
                            <span class="counter">{{ count($pending) }}</span>
                        </h3>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="vendor-panel-info-card order-processing">
                    <img src="{{ asset('assets/front') }}/icons/vendor-dashboard-icon_7.svg" alt="">
                    <div class="title-and-value-wrapper">
                        <p class="title">@lang('Order Processing')</p>
                        <h3 class="value">
                            <span class="counter">{{ count($processing) }}</span>
                        </h3>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="vendor-panel-info-card order-delivered">
                    <img src="{{ asset('assets/front') }}/icons/vendor-dashboard-icon_6.svg" alt="">
                    <div class="title-and-value-wrapper">
                        <p class="title">@lang('Orders Completed!')</p>
                        <h3 class="value">
                            <span class="counter">{{ count($completed) }}</span>
                        </h3>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="vendor-panel-info-card total-order">
                    <img src="{{ asset('assets/front') }}/icons/vendor-dashboard-icon_5.svg" alt="">
                    <div class="title-and-value-wrapper">
                        <p class="title">@lang('Total Products!')</p>
                    <h3 class="value">
                            {{-- In the new architecture the user (vendor) no longer owns products directly via a user_id column.
                                 Use the merchantProducts relationship to count how many product listings the vendor has. --}}
                            <span class="counter">{{ count($user->merchantProducts) }}</span>
                        </h3>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="vendor-panel-info-card total-item-sold">
                    <img src="{{ asset('assets/front') }}/icons/vendor-dashboard-icon_1.svg" alt="">
                    <div class="title-and-value-wrapper">
                        <p class="title">@lang('Total Item Sold!')</p>
                        <h3 class="value">
                            <span
                                class="counter">{{ App\Models\VendorOrder::where('user_id', '=', $user->id)->where('status', '=', 'completed')->sum('qty') }}</span>
                        </h3>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="vendor-panel-info-card current-balance">
                    <img src="{{ asset('assets/front') }}/icons/vendor-dashboard-icon_2.svg" alt="">
                    <div class="title-and-value-wrapper">
                        <p class="title">@lang('Current Balance')</p>
                        <h3 class="value">{{$curr->sign}}<span class="counter">
                                {{ App\Models\Product::vendorConvertWithoutCurrencyPrice(auth()->user()->current_balance) }}</span></h3>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="vendor-panel-info-card total-earning">
                    <img src="{{ asset('assets/front') }}/icons/vendor-dashboard-icon_3.svg" alt="">
                    <div class="title-and-value-wrapper">
                        <p class="title">@lang('Total Earning')</p>
                        @php
                            $datas = App\Models\VendorOrder::with('order')->where('user_id', Auth::user()->id);
                            $totalPrice = $datas->count() > 0 ? $datas->sum('price') : 0;
                        @endphp
                        <h3 class="value">{{$curr->sign}}<span
                                class="counter">{{ App\Models\Product::vendorConvertWithoutCurrencyPrice($totalPrice) }}</span></h3>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="vendor-panel-info-card pending-commision">
                    <img src="{{ asset('assets/front') }}/icons/vendor-dashboard-icon_4.svg" alt="">
                    <div class="title-and-value-wrapper">
                        <p class="title">@lang('Pending Commision')</p>
                        @php
                            $totalPrice = $user->vendororders->where('status', 'completed')->sum(function ($order) {
                                return $order->price * $order->qty;
                            });
                        @endphp
                        <h3 class="value">{{$curr->sign}}<span
                                class="counter">{{ App\Models\Product::vendorConvertWithoutCurrencyPrice($user->admin_commission) }}</span>
                        </h3>
                    </div>
                </div>
            </div>
        </div>
        <!-- Info cards area end -->

        <!-- Table area start  -->
        <div class="row gy-4 table-area">
            <!-- Recent Product(s) Table -->
            <div class="col-xxl-8">
                <div class="vendor-table-wrapper recent-products-table-wrapper">
                    <h4 class="table-title">@lang('Recent Product(s)')</h4>
                    <div class="user-table table-responsive">
                        <table id="recent-product-table" class="gs-data-table w-100">
                            <thead>
                                <tr>
                                    <th class="text-center">
                                        <span class="header-title text-center">@lang('Image')</span>
                                    </th>
                                    <th>
                                        <span class="header-title">@lang('Product Name')</span>
                                    </th>
                                    <th><span class="header-title">@lang('Part Number')</span></th>
                                    <th><span class="header-title">@lang('Shop Name')</span></th>
                                    <th><span class="header-title">@lang('Brand')</span></th>
                                    <th><span class="header-title">@lang('Brand Quality')</span></th>
                                    <th><span class="header-title">@lang('Price')</span></th>
                                    <th class="text-center">
                                        <span class="header-title">@lang('Details')</span>
                                    </th>
                                </tr>
                            </thead>
                            <tbody>

                                @forelse ($pproducts as $data)
                                    @php
                                        $vendorMp = \App\Models\MerchantProduct::where('product_id', $data->id)
                                            ->where('user_id', auth()->user()->id)
                                            ->where('status', 1)
                                            ->first();

                                        // متغيرات للرابط الأمامي
                                        $slug = $data->slug ?? null;
                                        $vendorId = auth()->user()->id;
                                        $merchantProductId = $vendorMp ? $vendorMp->id : null;
                                    @endphp
                                    <!-- table data row 1 start  -->
                                    <tr>
                                        <td>
                                            <img class="table-img"
                                                src="{{ \Illuminate\Support\Facades\Storage::url($data->photo) ?? asset('assets/images/noimage.png') }}"
                                                alt="">
                                        </td>
                                        <td class="text-start">
                                            <div class="product-name">
                                                <span class="content">
                                                    @php
                                                        // القاعدة: إذا عربي -> label_ar أولاً، ثم name | إذا إنجليزي -> name
                                                        if (app()->getLocale() == 'ar') {
                                                            $productName = !empty($data->label_ar) ? $data->label_ar : ($data->name ?? '-');
                                                        } else {
                                                            $productName = $data->name ?? '-';
                                                        }
                                                    @endphp
                                                    {{ $productName }}
                                                </span>
                                            </div>
                                        </td>
                                        <td><span class="content">{{ $data->sku ?? '-' }}</span></td>
                                        <td><span class="content">{{ auth()->user()->shop_name ?? '-' }}</span></td>
                                        <td><span class="content">{{ $data->brand ? $data->brand->name : '-' }}</span></td>
                                        <td><span class="content">
                                            {{ $vendorMp && $vendorMp->qualityBrand
                                                ? (app()->getLocale() == 'ar' && $vendorMp->qualityBrand->name_ar
                                                    ? $vendorMp->qualityBrand->name_ar
                                                    : $vendorMp->qualityBrand->name_en)
                                                : '-' }}
                                        </span></td>
                                        <td><span class="content">{{ $data->showPrice() }}</span></td>
                                        <td>
                                            <div class="table-icon-btns-wrapper">
                                                @if($slug && $vendorId && $merchantProductId)
                                                    <a href="{{ route('front.product', ['slug' => $slug, 'vendor_id' => $vendorId, 'merchant_product_id' => $merchantProductId]) }}" target="_blank" class="view-btn">
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
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                    <!-- table data row 1 end  -->
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center">@lang('No Data Available')</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <!-- Recent Order(s) Table  -->
            <div class="col-xxl-4">
                <div class="vendor-table-wrapper recent-orders-table-wrapper">
                    <h4 class="table-title">@lang('Recent Order(s)')</h4>
                    <div class="user-table table-responsive">
                        <table id="recent-order-table" class="gs-data-table w-100">
                            <thead>
                                <tr>
                                    <th>
                                        <span class="header-title">@lang('Order Number')</span>
                                    </th>
                                    <th><span class="header-title">@lang('Order Date')</span></th>
                                    <th class="text-center">
                                        <span class="header-title">@lang('Details')</span>
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($rorders as $data)
                                    <!-- table data row 1 start  -->
                                    <tr>
                                        <td><span class="content">{{ $data->order_number }}</span></td>
                                        <td><span class="content">{{ date('Y-m-d', strtotime($data->created_at)) }}</span>
                                        </td>
                                        <td>
                                            <div class="table-icon-btns-wrapper">
                                                <a href="{{ route('vendor-order-show', $data->order_number) }}"
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
        <div class="gs-chart-wrapper vendor-monthly-sales-chart d-none d-md-block">
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
                colors: ['#14b8a6'],
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
                        borderRadius: 12,
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
