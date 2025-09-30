@extends('layouts.vendor')

@section('content')
    @php
        use Carbon\Carbon;

        /** تهيئة المستخدم الحالي */
        $currentUser        = $user ?? auth()->user();

        /** بطاقات المعلومات العلوية */
        $mpCount            = $currentUser->merchantProducts()->count();
        $soldQty            = \App\Models\VendorOrder::where('user_id', $currentUser->id)
                                ->where('status', 'completed')->sum('qty');
        $recentEarningQ     = \App\Models\VendorOrder::with('order')->where('user_id', $currentUser->id);
        $recentEarningSum   = $recentEarningQ->count() > 0 ? $recentEarningQ->sum('price') : 0;
        $pendingCommission  = $currentUser->admin_commission;

        /** مصدر جدول المنتجات الحديثة (يدعم $pproducts | $datas | fallback) */
        $recentSource = $pproducts ?? ($datas ?? null);
        if ($recentSource instanceof \Illuminate\Pagination\LengthAwarePaginator) {
            $recentProducts = collect($recentSource->items());
        } elseif (is_array($recentSource) || $recentSource instanceof \Illuminate\Support\Collection) {
            $recentProducts = collect($recentSource);
        } else {
            // fallback — آخر 10 عروض للبائع
            $recentProducts = $currentUser->merchantProducts()
                ->with(['product.category','product.subcategory','product.childcategory'])
                ->latest('id')->take(10)->get();
        }

        /** مصدر جدول الطلبات الحديثة (يدعم $rorders | $orders | fallback) */
        $recentOrders = $rorders ?? ($orders ?? null);
        if ($recentOrders instanceof \Illuminate\Pagination\LengthAwarePaginator) {
            $recentOrders = collect($recentOrders->items());
        } elseif (is_array($recentOrders)) {
            $recentOrders = collect($recentOrders);
        } elseif ($recentOrders instanceof \Illuminate\Support\Collection) {
            // OK
        } else {
            // fallback — آخر 10 طلبات للبائع
            $recentOrders = \App\Models\VendorOrder::where('user_id', $currentUser->id)
                ->latest('id')->take(10)->get();
        }

        /**
         * بيانات الرسم البياني:
         * إن لم يأتِ $sales/$days من الكنترولر، نحسب آخر 12 شهرًا (اسم الشهر + مجموع price للطلبات المكتملة)
         */
        $hasDaysFromController  = isset($days)  && is_string($days)  && trim($days)  !== '';
        $hasSalesFromController = isset($sales) && is_string($sales) && trim($sales) !== '';

        if ($hasDaysFromController && $hasSalesFromController) {
            $daysJson  = $days;   // يفترض أنها JSON جاهزة من الكنترولر
            $salesJson = $sales;  // يفترض أنها JSON جاهزة من الكنترولر
        } else {
            // نحسب آخر 12 شهرًا
            $months = [];
            $totals = [];

            for ($i = 11; $i >= 0; $i--) {
                $mKey = Carbon::now()->subMonths($i)->format('Y-m');
                $label = Carbon::now()->subMonths($i)->format('M');
                $months[$mKey] = $label;
                $totals[$mKey] = 0;
            }

            $since = Carbon::now()->subMonths(11)->startOfMonth();
            $vendorCompleted = \App\Models\VendorOrder::where('user_id', $currentUser->id)
                                ->where('status', 'completed')
                                ->where('created_at', '>=', $since)
                                ->get(['price','created_at']);

            foreach ($vendorCompleted as $vo) {
                $key = Carbon::parse($vo->created_at)->format('Y-m');
                if (array_key_exists($key, $totals)) {
                    $totals[$key] += (float) $vo->price;
                }
            }

            $daysJson  = json_encode(array_values($months), JSON_UNESCAPED_UNICODE);
            $salesJson = json_encode(array_values($totals));
        }
    @endphp

    <div class="gs-vendor-outlet">
        <!-- breadcrumb start  -->
        <div class="gs-vendor-breadcrumb has-mb">
            <h4 class="text-capitalize">@lang('Dashboard Overview')</h4>
        </div>
        <!-- breadcrumb end -->

        <!-- Info cards area start -->
        <div class="row row-cols-1 row-cols-sm-2 row-cols-xl-3 row-cols-xxl-4 gy-4">
            <div class="col">
                <div class="vendor-panel-info-card order-pending">
                    <img src="{{ asset('assets/front/icons/vendor-dashboard-icon_8.svg') }}" alt="">
                    <div class="title-and-value-wrapper">
                        <p class="title">@lang('Order Pending')</p>
                        <h3 class="value"><span class="counter">{{ count($pending ?? []) }}</span></h3>
                    </div>
                </div>
            </div>

            <div class="col">
                <div class="vendor-panel-info-card order-processing">
                    <img src="{{ asset('assets/front/icons/vendor-dashboard-icon_7.svg') }}" alt="">
                    <div class="title-and-value-wrapper">
                        <p class="title">@lang('Order Processing')</p>
                        <h3 class="value"><span class="counter">{{ count($processing ?? []) }}</span></h3>
                    </div>
                </div>
            </div>

            <div class="col">
                <div class="vendor-panel-info-card order-delivered">
                    <img src="{{ asset('assets/front/icons/vendor-dashboard-icon_6.svg') }}" alt="">
                    <div class="title-and-value-wrapper">
                        <p class="title">@lang('Orders Completed!')</p>
                        <h3 class="value"><span class="counter">{{ count($completed ?? []) }}</span></h3>
                    </div>
                </div>
            </div>

            <div class="col">
                <div class="vendor-panel-info-card total-order">
                    <img src="{{ asset('assets/front/icons/vendor-dashboard-icon_5.svg') }}" alt="">
                    <div class="title-and-value-wrapper">
                        <p class="title">@lang('Total Products!')</p>
                        <h3 class="value"><span class="counter">{{ $mpCount }}</span></h3>
                    </div>
                </div>
            </div>

            <div class="col">
                <div class="vendor-panel-info-card total-item-sold">
                    <img src="{{ asset('assets/front/icons/vendor-dashboard-icon_1.svg') }}" alt="">
                    <div class="title-and-value-wrapper">
                        <p class="title">@lang('Total Item Sold!')</p>
                        <h3 class="value"><span class="counter">{{ $soldQty }}</span></h3>
                    </div>
                </div>
            </div>

            <div class="col">
                <div class="vendor-panel-info-card current-balance">
                    <img src="{{ asset('assets/front/icons/vendor-dashboard-icon_2.svg') }}" alt="">
                    <div class="title-and-value-wrapper">
                        <p class="title">@lang('Current Balance')</p>
                        <h3 class="value">
                            {{ $curr->sign }}
                            <span class="counter">
                                {{ \App\Models\Product::vendorConvertWithoutCurrencyPrice($currentUser->current_balance) }}
                            </span>
                        </h3>
                    </div>
                </div>
            </div>

            <div class="col">
                <div class="vendor-panel-info-card total-earning">
                    <img src="{{ asset('assets/front/icons/vendor-dashboard-icon_3.svg') }}" alt="">
                    <div class="title-and-value-wrapper">
                        <p class="title">@lang('Total Earning')</p>
                        <h3 class="value">
                            {{ $curr->sign }}
                            <span class="counter">
                                {{ \App\Models\Product::vendorConvertWithoutCurrencyPrice($recentEarningSum) }}
                            </span>
                        </h3>
                    </div>
                </div>
            </div>

            <div class="col">
                <div class="vendor-panel-info-card pending-commision">
                    <img src="{{ asset('assets/front/icons/vendor-dashboard-icon_4.svg') }}" alt="">
                    <div class="title-and-value-wrapper">
                        <p class="title">@lang('Pending Commision')</p>
                        <h3 class="value">
                            {{ $curr->sign }}
                            <span class="counter">
                                {{ \App\Models\Product::vendorConvertWithoutCurrencyPrice($pendingCommission) }}
                            </span>
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
                                    <th class="text-center"><span class="header-title text-center">@lang('Image')</span></th>
                                    <th><span class="header-title">@lang('Product Name')</span></th>
                                    <th><span class="header-title">@lang('Category')</span></th>
                                    <th><span class="header-title">@lang('Type')</span></th>
                                    <th><span class="header-title">@lang('price')</span></th>
                                    <th class="text-center"><span class="header-title">@lang('Details')</span></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($recentProducts as $row)
                                    @php
                                        // ندعم حالتين: عنصر من MerchantProduct أو من Product
                                        if ($row instanceof \App\Models\MerchantProduct) {
                                            $mp   = $row;
                                            $prod = $row->relationLoaded('product') ? $row->product : \App\Models\Product::find($row->product_id);
                                        } else {
                                            $prod = $row;
                                            $mp   = \App\Models\MerchantProduct::where('product_id', $prod->id)
                                                    ->where('user_id', $currentUser->id)->first();
                                        }

                                        $imgSrc = \Illuminate\Support\Facades\Storage::url($prod->photo) ?? asset('assets/images/noimage.png');

                                        $catName   = $prod?->category?->name;
                                        $subName   = $prod?->subcategory?->name;
                                        $childName = $prod?->childcategory?->name;

                                        $priceNow  = $mp
                                            ? (method_exists($mp,'showPrice') ? $mp->showPrice() : \App\Models\Product::convertPrice($mp->price))
                                            : ($prod ? $prod->showPrice() : '-');
                                    @endphp

                                    <tr>
                                        <td><img class="table-img" src="{{ $imgSrc }}" alt=""></td>
                                        <td class="text-start">
                                            <div class="product-name">
                                                @if($prod && $mp)
                                                    <x-product-name :product="$prod" :vendor-id="$currentUser->id" :merchant-product-id="$mp->id" target="_blank" />
                                                @elseif($prod)
                                                    <span class="content">{{ $prod->name }}</span>
                                                @else
                                                    <span class="content">—</span>
                                                @endif
                                            </div>
                                        </td>
                                        <td class="text-start">
                                            <div class="category">
                                                <span class="content">
                                                    {{ $catName ?: '—' }}
                                                    @if($subName)<br>{{ $subName }}@endif
                                                    @if($childName)<br>{{ $childName }}@endif
                                                </span>
                                            </div>
                                        </td>
                                        <td><span class="content">{{ $prod?->type ?? '—' }}</span></td>
                                        <td><span class="content">{{ $priceNow }}</span></td>
                                        <td>
                                            <div class="table-icon-btns-wrapper">
                                                <a href="{{ route('vendor-prod-edit', $prod?->id ?? 0) }}" class="view-btn" title="@lang('Edit')">
                                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none"
                                                         xmlns="http://www.w3.org/2000/svg">
                                                        <g clip-path="url(#clip0_548_165892)">
                                                            <path d="M12 4.84668C7.41454 4.84668 3.25621 7.35543 0.187788 11.4303C-0.0625959 11.7641 -0.0625959 12.2305 0.187788 12.5644C3.25621 16.6442 7.41454 19.1529 12 19.1529C16.5855 19.1529 20.7438 16.6442 23.8122 12.5693C24.0626 12.2354 24.0626 11.769 23.8122 11.4352C20.7438 7.35543 16.5855 4.84668 12 4.84668ZM12.3289 17.0369C9.28506 17.2284 6.7714 14.7196 6.96287 11.6709C7.11998 9.1572 9.15741 7.11977 11.6711 6.96267C14.7149 6.7712 17.2286 9.27994 17.0371 12.3287C16.8751 14.8375 14.8377 16.8749 12.3289 17.0369ZM12.1767 14.7098C10.537 14.8129 9.18196 13.4628 9.28997 11.8231C9.37343 10.468 10.4732 9.37322 11.8282 9.28485C13.4679 9.18175 14.823 10.5319 14.7149 12.1716C14.6266 13.5316 13.5268 14.6264 12.1767 14.7098Z"
                                                                  fill="white"/>
                                                        </g>
                                                        <defs><clipPath id="clip0_548_165892"><rect width="24" height="24" fill="white"/></clipPath></defs>
                                                    </svg>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center">@lang('No Data Available')</td>
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
                                    <th><span class="header-title">@lang('Order Number')</span></th>
                                    <th><span class="header-title">@lang('Order Date')</span></th>
                                    <th class="text-center"><span class="header-title">@lang('Details')</span></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($recentOrders as $data)
                                    <tr>
                                        <td><span class="content">{{ $data->order_number }}</span></td>
                                        <td><span class="content">{{ date('Y-m-d', strtotime($data->created_at)) }}</span></td>
                                        <td>
                                            <div class="table-icon-btns-wrapper">
                                                <a href="{{ route('vendor-order-show', $data->order_number) }}" class="view-btn">
                                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none"
                                                         xmlns="http://www.w3.org/2000/svg">
                                                        <g clip-path="url(#clip0_548_165897)">
                                                            <path d="M12 4.84668C7.41454 4.84668 3.25621 7.35543 0.187788 11.4303C-0.0625959 11.7641 -0.0625959 12.2305 0.187788 12.5644C3.25621 16.6442 7.41454 19.1529 12 19.1529C16.5855 19.1529 20.7438 16.6442 23.8122 12.5693C24.0626 12.2354 24.0626 11.769 23.8122 11.4352C20.7438 7.35543 16.5855 4.84668 12 4.84668ZM12.3289 17.0369C9.28506 17.2284 6.7714 14.7196 6.96287 11.6709C7.11998 9.1572 9.15741 7.11977 11.6711 6.96267C14.7149 6.7712 17.2286 9.27994 17.0371 12.3287C16.8751 14.8375 14.8377 16.8749 12.3289 17.0369ZM12.1767 14.7098C10.537 14.8129 9.18196 13.4628 9.28997 11.8231C9.37343 10.468 10.4732 9.37322 11.8282 9.28485C13.4679 9.18175 14.823 10.5319 14.7149 12.1716C14.6266 13.5316 13.5268 14.6264 12.1767 14.7098Z"
                                                                  fill="white"/>
                                                        </g>
                                                        <defs><clipPath id="clip0_548_165897"><rect width="24" height="24" fill="white"/></clipPath></defs>
                                                    </svg>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
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
            <div id="chart"></div>
        </div>
        <!-- Chart area end -->
    </div>
@endsection

@section('script')
    <script type="text/javascript">
        (function($) {
            "use strict";

            // بيانات الرسم محسوبة/قادمة من الكنترولر أو فولباك
            var chartDays  = {!! $daysJson !!};
            var chartSales = {!! $salesJson !!};

            var options = {
                colors: ['#27BE69'],
                series: [{
                    name: 'Net Profit',
                    data: chartSales
                }],
                chart: { type: 'bar', height: 450 },
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
                dataLabels: { enabled: false },
                stroke: { show: true, width: 2, colors: ['transparent'] },
                xaxis: { categories: chartDays },
                fill: { opacity: 1 },
                tooltip: {
                    y: { formatter: function(val) { return "{{ $curr->sign ?? '$' }} " + val } }
                }
            };

            var chart = new ApexCharts(document.getElementById("chart"), options);
            chart.render();
        })(jQuery);
    </script>
@endsection
