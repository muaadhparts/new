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
                <!-- page name -->
                <div class="ud-page-name-box d-flex justify-content-between align-items-center flex-wrap gap-3">
                    <!-- mobile sidebar trigger btn -->
                    <h3 class="ud-page-name">@lang('Top Up')</h3>
                    <a class="template-btn md-btn black-btn data-table-btn mb-0"
                        href="{{ route('user-top-up-create') }}">
                        <i class="fas fa-plus"></i> @lang('Add Top Up')</a>

                </div>

                <!--  purchase status steps -->

                <div class="user-table table-responsive position-relative">

                    <table class="gs-data-table w-100">
                        <thead>
                            <tr>

                                <th>{{ __('Top Up Date') }}</th>
                                <th>{{ __('Method') }}</th>
                                <th>{{ __('Amount') }}</th>
                                <th>{{ __('Status') }}</th>
                            </tr>

                        </thead>
                        {{-- ✅ البيانات محملة من الـ Controller --}}
                        <tbody>
                            @forelse ($topUps as $data)
                            <!-- table data row 1 start  -->
                            <tr>

                                <td><span class="content">{{ date('d-M-Y', strtotime($data->created_at)) }}</span>
                                </td>
                                <td><span class="content">{{ $data->method }}</span>
                                </td>
                                <td class="text-center">
                                    <span class="content ">{{ \PriceHelper::showOrderCurrencyPrice($data->amount *
                                        $data->currency_value, $data->currency_code) }}
                                    </span>
                                </td>

                                <td>
                                    @php
                                    if ($data->status == 0) {
                                    $class = 'yellow-btn';
                                    $status = 'Pending';
                                    } else {
                                    $class = 'green-btn';
                                    $status = 'Completed';
                                    }
                                    @endphp
                                    <button type="button" disabled class="template-btn md-btn {{ $class }}">
                                        {{ $status }}
                                    </button>
                                </td>


                            </tr>
                            <!-- table data row 1 end  -->
                            @empty
                            <tr>
                                <td colspan="4">{{ __('No Top Up Found') }}</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>

                </div>
                {{ $topUps->links('includes.frontend.pagination') }}
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