@extends('layouts.front')
@section('css')
    <link rel="stylesheet" href="{{ asset('assets/front/css/datatables.css') }}">
@endsection
@section('content')
    <div class="gs-user-panel-review wow-replaced" data-wow-delay=".1s">
        <div class="container">
            <div class="d-flex">
                <!-- sidebar -->
                @include('includes.rider.sidebar')
                <!-- main content -->
                <div class="gs-dashboard-user-content-wrapper gs-dashboard-outlet">
                    <!-- page title -->
                    <div class="ud-page-title-box d-flex justify-content-between align-items-center flex-wrap gap-3">
                        <!-- mobile sidebar trigger btn -->

                        <h3 class="ud-page-title">@lang('Service Area')</h3>
                        <a class="template-btn md-btn black-btn data-table-btn"
                            href="{{ route('rider-service-area-create') }}">@lang('Add New Service Area')</a>
                    </div>

                    <!--  order status steps -->

                    <div class="user-table table-responsive position-relative">

                        <table class="gs-data-table w-100">
                            <thead>
                                <tr>
                                    <th>{{ __('Service Area') }}</th>
                                    <th>{{ __('Delivery Cost') }}</th>
                                    <th>{{ __('Action') }}</th>
                                </tr>

                            </thead>
                            @php
                                $orders = App\Models\Order::where('user_id', Auth::user()->id)
                                    ->latest()
                                    ->paginate(12);
                            @endphp
                            <tbody>

                                @forelse($service_area as $area)
                                    <tr>
                                        <td data-label="{{ __('#Service Area') }}">
                                            {{ $area->city->city_name }}
                                        </td>

                                        <td data-label="{{ __('Delivery Cost') }}">
                                            <p>
                                                {{ $curr->sign }}{{ round($area->price * $curr->value, 2) }}
                                            </p>
                                        </td>

                                        <td>
                                            <div class="table-icon-btns-wrapper">
                                                <a href="{{ route('rider-service-area-edit', $area->id) }}"
                                                    class="view-btn edit-btn">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                                        viewBox="0 0 24 24" fill="none">
                                                        <g clip-path="url(#clip0_910_50031)">
                                                            <path
                                                                d="M18.9999 12.0469C18.447 12.0469 18 12.495 18 13.0469V21.0469C18 21.5979 17.5519 22.0469 17.0001 22.0469H3C2.44794 22.0469 2.00006 21.5979 2.00006 21.0469V7.04688C2.00006 6.49591 2.44794 6.04694 3 6.04694H11.0001C11.553 6.04694 12 5.59888 12 5.047C12 4.49493 11.553 4.04688 11.0001 4.04688H3C1.34601 4.04688 0 5.39288 0 7.04688V21.0469C0 22.7009 1.34601 24.0469 3 24.0469H17.0001C18.6541 24.0469 20.0001 22.7009 20.0001 21.0469V13.0469C20.0001 12.4939 19.5529 12.0469 18.9999 12.0469Z"
                                                                fill="white"></path>
                                                            <path
                                                                d="M9.37515 11.1346C9.3052 11.2046 9.25815 11.2936 9.23819 11.3895L8.53122 14.9257C8.49826 15.0895 8.55026 15.2585 8.66818 15.3776C8.76321 15.4726 8.8912 15.5235 9.02231 15.5235C9.05417 15.5235 9.08731 15.5206 9.12027 15.5136L12.6553 14.8066C12.7533 14.7865 12.8423 14.7396 12.9113 14.6695L20.8233 6.75751L17.2882 3.22266L9.37515 11.1346Z"
                                                                fill="white"></path>
                                                            <path
                                                                d="M23.2686 0.778152C22.2937 -0.196884 20.7076 -0.196884 19.7335 0.778152L18.3496 2.16206L21.8846 5.6971L23.2686 4.313C23.7406 3.84206 24.0006 3.214 24.0006 2.54604C24.0006 1.87807 23.7406 1.25002 23.2686 0.778152Z"
                                                                fill="white"></path>
                                                        </g>
                                                        <defs>
                                                            <clipPath id="clip0_910_50031">
                                                                <rect width="24" height="24" fill="white"></rect>
                                                            </clipPath>
                                                        </defs>
                                                    </svg>
                                                </a>

                                                <a href="{{ route('rider-service-area-delete', $area->id) }}"
                                                    class="view-btn delete-btn">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                                        viewBox="0 0 24 24" fill="none">
                                                        <path
                                                            d="M16 6V5.2C16 4.0799 16 3.51984 15.782 3.09202C15.5903 2.71569 15.2843 2.40973 14.908 2.21799C14.4802 2 13.9201 2 12.8 2H11.2C10.0799 2 9.51984 2 9.09202 2.21799C8.71569 2.40973 8.40973 2.71569 8.21799 3.09202C8 3.51984 8 4.0799 8 5.2V6M10 11.5V16.5M14 11.5V16.5M3 6H21M19 6V17.2C19 18.8802 19 19.7202 18.673 20.362C18.3854 20.9265 17.9265 21.3854 17.362 21.673C16.7202 22 15.8802 22 14.2 22H9.8C8.11984 22 7.27976 22 6.63803 21.673C6.07354 21.3854 5.6146 20.9265 5.32698 20.362C5 19.7202 5 18.8802 5 17.2V6"
                                                            stroke="white" stroke-width="2" stroke-linecap="round"
                                                            stroke-linejoin="round"></path>
                                                    </svg>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center">{{ __('No Orders Found.') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    {{ $orders->links('includes.frontend.pagination') }}
                </div>
            </div>
        </div>
    </div>
@endsection
@section('script')
    <script src="{{ asset('assets/front/js/dataTables.min.js') }}" defer></script>
    <script src="{{ asset('assets/front/js/user.js') }}" defer></script>
@endsection
