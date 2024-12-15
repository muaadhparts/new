@extends('layouts.front')
@section('css')
    <link rel="stylesheet" href="{{ asset('assets/front/css/datatables.css') }}">
@endsection
@section('content')
    <div class="gs-user-panel-review wow-replaced" data-wow-delay=".1s">
        <div class="container">
            <div class="d-flex">
                <!-- sidebar -->
                @include('includes.user.sidebar')
                <!-- main content -->
                <div class="gs-dashboard-user-content-wrapper gs-dashboard-outlet">
                    <!-- page title -->
                    <div class="ud-page-title-box">
                        <!-- mobile sidebar trigger btn -->
                        <h3 class="ud-page-title">@lang('Purchase Items')</h3>
                    </div>

                    <!--  order status steps -->

                    <div class="user-table table-responsive position-relative">

                        <table class="gs-data-table w-100">
                            <thead>
                                <tr>

                                    <th>{{ __('#Order') }}</th>
                                    <th>{{ __('Date') }}</th>
                                    <th>{{ __('Order Total') }}</th>
                                    <th>{{ __('Order Status') }}</th>
                                    <th>{{ __('View') }}</th>
                                </tr>

                            </thead>
                            @php
                                $orders = App\Models\Order::where('user_id', Auth::user()->id)
                                    ->latest()
                                    ->paginate(12);
                            @endphp
                            <tbody>
                                @foreach ($orders as $order)
                                    <!-- table data row 1 start  -->
                                    <tr>
                                        <td><span class="content">{{ $order->order_number }}</span></td>
                                        <td><span class="content">{{ date('d M Y', strtotime($order->created_at)) }}</span>
                                        </td>
                                        <td class="text-center">
                                            <span
                                                class="content ">{{ \PriceHelper::showAdminCurrencyPrice($order->pay_amount * $order->currency_value, $order->currency_sign) }}
                                            </span>
                                        </td>

                                        <td>
                                            @php
                                                if ($order->status == 'pending') {
                                                    $class = 'yellow-btn';
                                                } elseif ($order->status == 'processing') {
                                                    $class = 'yellow-btn';
                                                } elseif ($order->status == 'completed') {
                                                    $class = 'green-btn';
                                                } elseif ($order->status == 'declined') {
                                                    $class = 'red-btn';
                                                } elseif ($order->status == 'on delivery') {
                                                    $class = 'black-btn';
                                                }
                                            @endphp
                                            <button type="button" disabled class="template-btn md-btn {{ $class }}">
                                                {{ ucwords($order->status) }}
                                            </button>
                                        </td>

                                        <td class="view-btn-wrapper">
                                            <a href="{{ route('user-order', $order->id) }}" class="view-btn">
                                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none"
                                                    xmlns="http://www.w3.org/2000/svg">
                                                    <g clip-path="url(#clip0_548_16589)">
                                                        <path
                                                            d="M12 4.84668C7.41454 4.84668 3.25621 7.35543 0.187788 11.4303C-0.0625959 11.7641 -0.0625959 12.2305 0.187788 12.5644C3.25621 16.6442 7.41454 19.1529 12 19.1529C16.5855 19.1529 20.7438 16.6442 23.8122 12.5693C24.0626 12.2354 24.0626 11.769 23.8122 11.4352C20.7438 7.35543 16.5855 4.84668 12 4.84668ZM12.3289 17.0369C9.28506 17.2284 6.7714 14.7196 6.96287 11.6709C7.11998 9.1572 9.15741 7.11977 11.6711 6.96267C14.7149 6.7712 17.2286 9.27994 17.0371 12.3287C16.8751 14.8375 14.8377 16.8749 12.3289 17.0369ZM12.1767 14.7098C10.537 14.8129 9.18196 13.4628 9.28997 11.8231C9.37343 10.468 10.4732 9.37322 11.8282 9.28485C13.4679 9.18175 14.823 10.5319 14.7149 12.1716C14.6266 13.5316 13.5268 14.6264 12.1767 14.7098Z"
                                                            fill="white" />
                                                    </g>
                                                    <defs>
                                                        <clipPath id="clip6_548_16589">
                                                            <rect width="24" height="24" fill="white" />
                                                        </clipPath>
                                                    </defs>
                                                </svg>
                                            </a>
                                        </td>


                                    </tr>
                                    <!-- table data row 1 end  -->
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    {{ $orders->links('includes.frontend.pagination') }}
                </div>
            </div>
        </div>
    </div>
    <!-- user dashboard wrapper end -->
@endsection
@section('script')
    <script src="{{ asset('assets/front/js/dataTables.min.js') }}" defer></script>
    <script src="{{ asset('assets/front/js/user.js') }}" defer></script>
@endsection
