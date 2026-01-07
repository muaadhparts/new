@extends('layouts.merchant')

@section('content')
    <!-- outlet start  -->
    <div class="gs-merchant-outlet">
        <!-- breadcrumb start  -->
        <div class="gs-merchant-breadcrumb has-mb">
            <h4 class="text-capitalize">@lang('Merchant Earning')</h4>
            <ul class="breadcrumb-menu">
                <li>
                    <a href="{{ route('merchant.dashboard') }}" class="text-capitalize">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" class="home-icon-merchant-panel-breadcrumb">
                            <path
                                d="M9 21V13.6C9 13.0399 9 12.7599 9.109 12.546C9.20487 12.3578 9.35785 12.2049 9.54601 12.109C9.75993 12 10.04 12 10.6 12H13.4C13.9601 12 14.2401 12 14.454 12.109C14.6422 12.2049 14.7951 12.3578 14.891 12.546C15 12.7599 15 13.0399 15 13.6V21M2 9.5L11.04 2.72C11.3843 2.46181 11.5564 2.33271 11.7454 2.28294C11.9123 2.23902 12.0877 2.23902 12.2546 2.28295C12.4436 2.33271 12.6157 2.46181 12.96 2.72L22 9.5M4 8V17.8C4 18.9201 4 19.4802 4.21799 19.908C4.40974 20.2843 4.7157 20.5903 5.09202 20.782C5.51985 21 6.0799 21 7.2 21H16.8C17.9201 21 18.4802 21 18.908 20.782C19.2843 20.5903 19.5903 20.2843 19.782 19.908C20 19.4802 20 18.9201 20 17.8V8L13.92 3.44C13.2315 2.92361 12.8872 2.66542 12.5091 2.56589C12.1754 2.47804 11.8246 2.47804 11.4909 2.56589C11.1128 2.66542 10.7685 2.92361 10.08 3.44L4 8Z"
                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </a>
                </li>
                <li>
                    <a href="{{ route('merchant.dashboard') }}" class="text-capitalize">
                        @lang('Dashboard')
                    </a>
                </li>

                <li>
                    <a href="#" class="text-capitalize">
                        @lang('Merchant Earning')
                    </a>
                </li>
            </ul>
        </div>
        <!-- breadcrumb end -->


        <div class="gs-merchant-erning">
            <!-- Summary Cards Start -->
            <div class="row mb-4">
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="m-card bg-white shadow-sm">
                        <div class="m-card__body text-center">
                            <h6 class="text-muted mb-2">@lang('Total Sales')</h6>
                            <h4 class="mb-0" style="color: var(--theme-primary);">{{ $total_sales ?? $total }}</h4>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="m-card bg-white shadow-sm">
                        <div class="m-card__body text-center">
                            <h6 class="text-muted mb-2">@lang('Platform Commission')</h6>
                            <h4 class="mb-0 text-danger">-{{ $total_commission ?? '0' }}</h4>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="m-card bg-white shadow-sm">
                        <div class="m-card__body text-center">
                            <h6 class="text-muted mb-2">@lang('Net Earnings')</h6>
                            <h4 class="mb-0 text-success">{{ $total_net ?? $total }}</h4>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="m-card bg-white shadow-sm">
                        <div class="m-card__body text-center">
                            <h6 class="text-muted mb-2">@lang('Total Orders')</h6>
                            <h4 class="mb-0">{{ $count_orders ?? 0 }}</h4>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Detailed Breakdown -->
            <div class="row mb-4">
                <div class="col-lg-6 mb-3">
                    <div class="m-card bg-white shadow-sm">
                        <div class="m-card__header">
                            <h5 class="mb-0">@lang('Financial Breakdown')</h5>
                        </div>
                        <div class="m-card__body">
                            <table class="table table-borderless">
                                <tr>
                                    <td>@lang('Total Sales')</td>
                                    <td class="text-end"><strong>{{ $total_sales ?? '0' }}</strong></td>
                                </tr>
                                <tr>
                                    <td>@lang('Platform Commission')</td>
                                    <td class="text-end text-danger">-{{ $total_commission ?? '0' }}</td>
                                </tr>
                                <tr>
                                    <td>@lang('Tax Collected')</td>
                                    <td class="text-end">{{ $total_tax ?? '0' }}</td>
                                </tr>
                                <tr>
                                    <td>@lang('Shipping Income')</td>
                                    <td class="text-end">{{ $total_shipping ?? '0' }}</td>
                                </tr>
                                <tr>
                                    <td>@lang('Packing Income')</td>
                                    <td class="text-end">{{ $total_packing ?? '0' }}</td>
                                </tr>
                                <tr>
                                    <td>@lang('Courier Fees')</td>
                                    <td class="text-end">{{ $total_courier_fees ?? '0' }}</td>
                                </tr>
                                <tr class="border-top">
                                    <td><strong>@lang('Net Earnings')</strong></td>
                                    <td class="text-end text-success"><strong>{{ $total_net ?? '0' }}</strong></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6 mb-3">
                    <div class="m-card bg-white shadow-sm">
                        <div class="m-card__header">
                            <h5 class="mb-0">@lang('Payment & Delivery Stats')</h5>
                        </div>
                        <div class="m-card__body">
                            <table class="table table-borderless">
                                <tr>
                                    <td>@lang('Via Merchant Gateway')</td>
                                    <td class="text-end">{{ $merchant_payments ?? '0' }}</td>
                                </tr>
                                <tr>
                                    <td>@lang('Via Platform Gateway')</td>
                                    <td class="text-end">{{ $platform_payments ?? '0' }}</td>
                                </tr>
                                <tr class="border-top">
                                    <td>@lang('Courier Deliveries')</td>
                                    <td class="text-end">{{ $courier_deliveries ?? 0 }}</td>
                                </tr>
                                <tr>
                                    <td>@lang('Shipping Orders')</td>
                                    <td class="text-end">{{ $shipping_deliveries ?? 0 }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Summary Cards End -->

            <!-- Table area start  -->
            <div class="merchant-table-wrapper catalogItem-catalogs-table-wrapper">
                <div class="d-flex justify-content-center">
                    <form class="total-erning-box" action="{{ route('merchant.income') }}" method="GET">
                        <div class="title-wrapper">
                            <h5 class="title">@lang('Filter by Date')
                                @if($start_date && $end_date)
                                    <small>({{ $start_date }} @lang('To') {{ $end_date }})</small>
                                @endif
                            </h5>
                        </div>
                        <div class="form-group filter-box">
                            <input type="text" class="form-control filter-input discount_date" name="start_date"
                                placeholder="@lang('From Date')" value="{{ $start_date ?? '' }}">
                            <input type="text" class="form-control filter-input discount_date" name="end_date"
                                placeholder="@lang('To Date') " value="{{ $end_date ?? '' }}">
                            <div class="fitler-reset-btns-wrapper">
                                <button class="template-btn" type="submit">Filter</button>
                                <button class="template-btn black-btn" id="reset" type="button">@lang('Reset')</button>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="user-table table-responsive position-relative">

                    <table class="gs-data-table w-100">
                        <thead>
                            <tr>
                                <th>{{ __('Purchase Number') }}</th>
                                <th>{{ __('Sales') }}</th>
                                <th>{{ __('Commission') }}</th>
                                <th>{{ __('Net') }}</th>
                                <th>{{ __('Payment') }}</th>
                                <th>{{ __('Delivery') }}</th>
                                <th>{{ __('Date') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($datas as $data)
                                @php
                                    $currSign = $data->purchase->currency_sign ?? 'SAR ';
                                    $currVal = $data->purchase->currency_value ?? 1;
                                @endphp
                                <tr>
                                    <td>
                                        <a class="title-hover-color"
                                            href="{{ route('merchant-purchase-invoice', $data->purchase->purchase_number) }}">{{ $data->purchase->purchase_number }}</a>
                                    </td>
                                    <td>
                                        {{ $currSign . number_format($data->price * $currVal, 2) }}
                                    </td>
                                    <td class="text-danger">
                                        -{{ $currSign . number_format(($data->commission_amount ?? 0) * $currVal, 2) }}
                                    </td>
                                    <td class="text-success">
                                        {{ $currSign . number_format(($data->net_amount ?? $data->price) * $currVal, 2) }}
                                    </td>
                                    <td>
                                        <span class="badge {{ ($data->payment_type ?? 'platform') === 'merchant' ? 'bg-success' : 'bg-info' }}">
                                            {{ ($data->payment_type ?? 'platform') === 'merchant' ? __('Merchant') : __('Platform') }}
                                        </span>
                                    </td>
                                    <td>
                                        @if(($data->shipping_type ?? 'shipping') === 'courier')
                                            <span class="badge bg-warning">@lang('Courier')</span>
                                        @else
                                            <span class="badge bg-primary">@lang('Shipping')</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="content">
                                            {{ $data->purchase->created_at->format('d-m-Y') }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center">
                                        @lang('No Data Found')
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <!-- Table area end  -->
        </div>


    </div>
    <!-- outlet end  -->
@endsection
@section('script')
    {{-- DATA TABLE --}}

    <script type="text/javascript">
        $(document).on('click', '#reset', function() {

            $('.discount_date').val('');
            location.href = '{{ route('merchant.income') }}';
        })

        var dateToday = new Date();
        $(".discount_date").datepicker({
            changeMonth: true,
            changeYear: true,
        });
    </script>
@endsection
