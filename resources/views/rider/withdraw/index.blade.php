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
                        <h3 class="ud-page-title">@lang('My Withdraws')</h3>
                        <a class="template-btn md-btn black-btn data-table-btn" href="{{ route('rider-wwt-create') }}"> <i
                                class="fas fa-plus"></i> @lang('Withdraw Now')</a>
                    </div>

                    <!--  order status steps -->

                    <div class="user-table table-responsive position-relative">

                        <table class="gs-data-table w-100">
                            <thead>
                                <tr>
                                    <th>{{ __('Withdraw Date') }}</th>
                                    <th>{{ __('Method') }}</th>
                                    <th>{{ __('Account') }}</th>
                                    <th>{{ __('Amount') }}</th>
                                    <th>{{ __('Status') }}</th>
                                </tr>

                            </thead>
                            <tbody>
                                @forelse ($withdraws as $withdraw)
                                    <!-- table data row 1 start  -->
                                    <tr>

                                        <td><span
                                                class="content">{{ date('d-M-Y', strtotime($withdraw->created_at)) }}</span>
                                        </td>
                                        <td><span class="content">{{ $withdraw->method }}</span>
                                        </td>

                                        @if ($withdraw->method != 'Bank')
                                            <td data-label="{{ __('Account') }}">
                                                <span class="content">
                                                    {{ $withdraw->acc_email }}
                                                </span>
                                            </td>
                                        @else
                                            <td data-label="{{ __('Account') }}">
                                                <span class="content">
                                                    {{ $withdraw->iban }}
                                                </span>
                                            </td>
                                        @endif

                                        <td class="text-center">
                                            <span
                                                class="content ">{{ $sign->sign }}{{ round($withdraw->amount * $sign->value, 2) }}
                                            </span>
                                        </td>


                                        <td>

                                            @php
                                                if ($withdraw->status == 'pending') {
                                                    $class = 'yellow-btn';
                                                } elseif ($withdraw->status == 'completed') {
                                                    $class = 'green-btn';
                                                } else {
                                                    $class = 'red-btn';
                                                }
                                            @endphp
                                            <button type="button" disabled
                                                class="template-btn md-btn {{ $class }}">
                                                {{ ucfirst($withdraw->status) }}
                                            </button>
                                        </td>
                                    </tr>
                                    <!-- table data row 1 end  -->
                                @empty
                                    <tr>
                                        <td colspan="5">{{ __('No Withdraw Found') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>

                    </div>

                    {{ $withdraws->links('includes.frontend.pagination') }}

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
