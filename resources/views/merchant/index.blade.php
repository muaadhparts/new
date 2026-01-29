@extends('layouts.merchant')

@section('content')
    <div class="gs-merchant-outlet">
        <!-- breadcrumb start  -->
        <div class="gs-merchant-breadcrumb has-mb">
            <h4 class="text-capitalize">@lang('Dashboard Overview')</h4>
        </div>
        <!-- breadcrumb end -->

        {{-- تنبيه التاجر تحت التحقق --}}
        @if(auth()->user()->is_merchant == 1)
            @if($hasPendingVerification)
                {{-- تم إرسال المستندات - في انتظار المراجعة --}}
                <div class="m-alert m-alert--info mb-4">
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-clock me-3 fs-4"></i>
                            <div>
                                <h5 class="mb-1">@lang('Verification Under Review')</h5>
                                <p class="mb-0">@lang('Your documents have been submitted and are being reviewed by our team. You will be notified once approved.')</p>
                            </div>
                        </div>
                    </div>
                </div>
            @else
                {{-- لم يتم إرسال المستندات بعد --}}
                <div class="m-alert m-alert--warning mb-4">
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-exclamation-triangle me-3 fs-4"></i>
                            <div>
                                <h5 class="mb-1">@lang('Account Pending Verification')</h5>
                                <p class="mb-0">@lang('Please submit your business documents to verify your merchant account and start selling.')</p>
                            </div>
                        </div>
                        <a href="{{ route('merchant-trust-badge') }}" class="m-btn m-btn--primary">
                            <i class="fas fa-file-upload me-2"></i>@lang('Submit Documents')
                        </a>
                    </div>
                </div>
            @endif
        @endif

        <!-- Info cards area start -->
        <div class="row row-cols-1 row-cols-sm-2 row-cols-xl-3 row-cols-xxl-4 gy-4">
            <div class="col">
                <div class="merchant-panel-info-card purchase-pending">
                    <img src="{{ asset('assets/front') }}/icons/merchant-dashboard-icon_8.svg" alt="">
                    <div class="name-and-value-wrapper">
                        <p class="name">@lang('Purchases Pending')</p>
                        <h3 class="value">
                            <span class="counter">{{ $statistics['pending'] }}</span>
                        </h3>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="merchant-panel-info-card purchase-processing">
                    <img src="{{ asset('assets/front') }}/icons/merchant-dashboard-icon_7.svg" alt="">
                    <div class="name-and-value-wrapper">
                        <p class="name">@lang('Purchases Processing')</p>
                        <h3 class="value">
                            <span class="counter">{{ $statistics['processing'] }}</span>
                        </h3>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="merchant-panel-info-card purchase-delivered">
                    <img src="{{ asset('assets/front') }}/icons/merchant-dashboard-icon_6.svg" alt="">
                    <div class="name-and-value-wrapper">
                        <p class="name">@lang('Purchases Completed!')</p>
                        <h3 class="value">
                            <span class="counter">{{ $statistics['completed'] }}</span>
                        </h3>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="merchant-panel-info-card total-purchase">
                    <img src="{{ asset('assets/front') }}/icons/merchant-dashboard-icon_5.svg" alt="">
                    <div class="name-and-value-wrapper">
                        <p class="name">@lang('Total CatalogItems!')</p>
                        <h3 class="value">
                            <span class="counter">{{ $statistics['totalItems'] }}</span>
                        </h3>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="merchant-panel-info-card total-item-sold">
                    <img src="{{ asset('assets/front') }}/icons/merchant-dashboard-icon_1.svg" alt="">
                    <div class="name-and-value-wrapper">
                        <p class="name">@lang('Total Item Sold!')</p>
                        <h3 class="value">
                            <span class="counter">{{ $statistics['totalItemsSold'] }}</span>
                        </h3>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="merchant-panel-info-card current-balance">
                    <img src="{{ asset('assets/front') }}/icons/merchant-dashboard-icon_2.svg" alt="">
                    <div class="name-and-value-wrapper">
                        <p class="name">@lang('Current Balance')</p>
                        <h3 class="value">{{ $statistics['currentBalance'] }}</h3>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="merchant-panel-info-card total-earning">
                    <img src="{{ asset('assets/front') }}/icons/merchant-dashboard-icon_3.svg" alt="">
                    <div class="name-and-value-wrapper">
                        <p class="name">@lang('Total Earning')</p>
                        <h3 class="value">{{ $statistics['totalSales'] }}</h3>
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
                    <h4 class="table-name">@lang('Recent CatalogItem(s)')</h4>
                    <div class="user-table table-responsive">
                        <table id="recent-catalogItem-table" class="gs-data-table w-100">
                            <thead>
                                <tr>
                                    <th class="text-center">
                                        <span class="header-name text-center">@lang('Image')</span>
                                    </th>
                                    <th>
                                        <span class="header-name">@lang('Part Number')</span>
                                    </th>
                                    <th>
                                        <span class="header-name">@lang('Name')</span>
                                    </th>
                                    <th><span class="header-name">@lang('Brand')</span></th>
                                    <th><span class="header-name">@lang('Quality Brand')</span></th>
                                    <th><span class="header-name">@lang('Branch')</span></th>
                                    <th><span class="header-name">@lang('Price')</span></th>
                                    <th class="text-center">
                                        <span class="header-name">@lang('Details')</span>
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($recentItems as $item)
                                    <tr>
                                        <td>
                                            <img class="table-img" src="{{ $item['photoUrl'] }}" alt="">
                                        </td>
                                        <td>
                                            <span class="content"><code>{{ $item['partNumber'] ?? __('N/A') }}</code></span>
                                        </td>
                                        <td class="text-start">
                                            <div class="catalogItem-name">
                                                <span class="content">{{ $item['name'] }}</span>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="content">{{ $item['brandName'] }}</span>
                                        </td>
                                        <td>
                                            <span class="content">{{ $item['qualityBrandName'] }}</span>
                                        </td>
                                        <td>
                                            <span class="content">{{ $item['branchName'] }}</span>
                                        </td>
                                        <td>
                                            <span class="content">{{ $item['price'] }}</span>
                                        </td>
                                        <td>
                                            <div class="table-icon-btns-wrapper">
                                                <a href="{{ $item['viewUrl'] }}" target="_blank" class="view-btn" title="@lang('View')">
                                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                        <g clip-path="url(#clip0_548_165892)">
                                                            <path d="M12 4.84668C7.41454 4.84668 3.25621 7.35543 0.187788 11.4303C-0.0625959 11.7641 -0.0625959 12.2305 0.187788 12.5644C3.25621 16.6442 7.41454 19.1529 12 19.1529C16.5855 19.1529 20.7438 16.6442 23.8122 12.5693C24.0626 12.2354 24.0626 11.769 23.8122 11.4352C20.7438 7.35543 16.5855 4.84668 12 4.84668ZM12.3289 17.0369C9.28506 17.2284 6.7714 14.7196 6.96287 11.6709C7.11998 9.1572 9.15741 7.11977 11.6711 6.96267C14.7149 6.7712 17.2286 9.27994 17.0371 12.3287C16.8751 14.8375 14.8377 16.8749 12.3289 17.0369ZM12.1767 14.7098C10.537 14.8129 9.18196 13.4628 9.28997 11.8231C9.37343 10.468 10.4732 9.37322 11.8282 9.28485C13.4679 9.18175 14.823 10.5319 14.7149 12.1716C14.6266 13.5316 13.5268 14.6264 12.1767 14.7098Z" fill="white" />
                                                        </g>
                                                        <defs><clipPath id="clip0_548_165892"><rect width="24" height="24" fill="white" /></clipPath></defs>
                                                    </svg>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center">@lang('No Merchant Items Found')</td>
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
                    <h4 class="table-name">@lang('Recent Purchase(s)')</h4>
                    <div class="user-table table-responsive">
                        <table id="recent-purchase-table" class="gs-data-table w-100">
                            <thead>
                                <tr>
                                    <th><span class="header-name">@lang('Purchase Number')</span></th>
                                    <th><span class="header-name">@lang('Total')</span></th>
                                    <th><span class="header-name">@lang('Status')</span></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($recentPurchases as $purchase)
                                    <tr>
                                        <td>
                                            <a href="{{ route('merchant-purchase-details', $purchase->id) }}" class="content">
                                                #{{ $purchase->purchase_number }}
                                            </a>
                                        </td>
                                        <td>
                                            <span class="content">{{ $purchase->price }}</span>
                                        </td>
                                        <td>
                                            <span class="badge badge-{{ $purchase->status }}">
                                                {{ __(ucfirst($purchase->status)) }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center">@lang('No Recent Purchases')</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <!-- Table area end -->

        <!-- Sales Chart -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="merchant-table-wrapper">
                    <h4 class="table-name">@lang('Sales Overview (Last 30 Days)')</h4>
                    <div class="chart-container p-4">
                        <canvas id="salesChart" height="80"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        const ctx = document.getElementById('salesChart').getContext('2d');
        const salesChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: [{{ $salesChart['daysFormatted'] }}],
                datasets: [{
                    label: '@lang("Sales")',
                    data: [{{ $salesChart['salesFormatted'] }}],
                    borderColor: 'rgb(75, 192, 192)',
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top',
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    </script>
    @endpush
@endsection
