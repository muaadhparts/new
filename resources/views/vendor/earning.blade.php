@extends('layouts.vendor')

@section('content')
    <!-- outlet start  -->
    <div class="gs-vendor-outlet">
        <!-- breadcrumb start  -->
        <div class="gs-vendor-breadcrumb has-mb">
            <h4 class="text-capitalize">@lang('Vendor Earning')</h4>
            <ul class="breadcrumb-menu">
                <li>
                    <a href="{{ route('vendor.dashboard') }}" class="text-capitalize">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                            stroke="#4C3533" class="home-icon-vendor-panel-breadcrumb">
                            <path
                                d="M9 21V13.6C9 13.0399 9 12.7599 9.109 12.546C9.20487 12.3578 9.35785 12.2049 9.54601 12.109C9.75993 12 10.04 12 10.6 12H13.4C13.9601 12 14.2401 12 14.454 12.109C14.6422 12.2049 14.7951 12.3578 14.891 12.546C15 12.7599 15 13.0399 15 13.6V21M2 9.5L11.04 2.72C11.3843 2.46181 11.5564 2.33271 11.7454 2.28294C11.9123 2.23902 12.0877 2.23902 12.2546 2.28295C12.4436 2.33271 12.6157 2.46181 12.96 2.72L22 9.5M4 8V17.8C4 18.9201 4 19.4802 4.21799 19.908C4.40974 20.2843 4.7157 20.5903 5.09202 20.782C5.51985 21 6.0799 21 7.2 21H16.8C17.9201 21 18.4802 21 18.908 20.782C19.2843 20.5903 19.5903 20.2843 19.782 19.908C20 19.4802 20 18.9201 20 17.8V8L13.92 3.44C13.2315 2.92361 12.8872 2.66542 12.5091 2.56589C12.1754 2.47804 11.8246 2.47804 11.4909 2.56589C11.1128 2.66542 10.7685 2.92361 10.08 3.44L4 8Z"
                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </a>
                </li>
                <li>
                    <a href="{{ route('vendor.dashboard') }}" class="text-capitalize">
                        @lang('Dashboard')
                    </a>
                </li>

                <li>
                    <a href="#" class="text-capitalize">
                        @lang('Vendor Earning')
                    </a>
                </li>
            </ul>
        </div>
        <!-- breadcrumb end -->


        <div class="gs-vendor-erning">
            <!-- Table area start  -->
            <div class="vendor-table-wrapper product-catalogs-table-wrapper">
                <div class="d-flex justify-content-center">
                    <form class="total-erning-box" action="{{ route('vendor.income') }}" method="GET">
                        <div class="title-wrapper">
                            <h5 class="title">@lang('Total Earning :')
                                <small>{{ $start_date != '' ? $start_date->format('d-m-Y') : '' }}
                                    {{ $start_date != '' && $end_date != '' ? 'To' : '' }}
                                    {{ $end_date != '' ? $end_date->format('d-m-Y') : '' }}</small> :
                                {{ $total }}
                            </h5>
                        </div>
                        <div class="form-group filter-box">
                            <input type="text" class="form-control filter-input discount_date" name="start_date"
                                placeholder="@lang('From Date')" value="{{ $start_date != '' ? $start_date->format('d-m-Y') : '' }}">
                            <input type="text" class="form-control filter-input discount_date" name="end_date"
                                placeholder="@lang('To Date') " value="{{ $end_date != '' ? $end_date->format('d-m-Y') : '' }}">
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
                                <th>{{ __('Order Number') }}</th>
                                <th>{{ __('Total Earning') }}</th>
                                <th>{{ __('Payment Method') }}</th>
                                <th>{{ __('Txn Id') }}</th>
                                <th>{{ __('Order Date') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($datas as $data)
                                <tr>
                                    <td>
                                        <a class="title-hover-color"
                                            href="{{ route('vendor-order-invoice', $data->order->order_number) }}">{{ $data->order->order_number }}</a>
                                    </td>
                                    <td>
                                        {{ $data->order->currency_sign . round($data->price * $data->order->currency_value, 2) }}
                                    </td>
                                    <td>
                                        <span class="content">
                                            {{ $data->order->method }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="content">
                                            {{ $data->order->txnid }}
                                        </span>

                                    </td>
                                    <td>
                                        <span class="content">
                                            {{ $data->order->created_at->format('d-m-Y') }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center">
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
            location.href = '{{ route('vendor.income') }}';
        })

        var dateToday = new Date();
        $(".discount_date").datepicker({
            changeMonth: true,
            changeYear: true,
        });
    </script>
@endsection
