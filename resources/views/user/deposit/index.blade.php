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
                <div class="ud-page-title-box d-flex justify-content-between align-items-center flex-wrap gap-3">
                    <!-- mobile sidebar trigger btn -->
                    <h3 class="ud-page-title">@lang('Deposite')</h3>
                    <a class="template-btn md-btn black-btn data-table-btn mb-0"
                        href="{{ route('user-deposit-create') }}">
                        <i class="fas fa-plus"></i> @lang('Add Deposit')</a>

                </div>

                <!--  order status steps -->

                <div class="user-table table-responsive position-relative">

                    <table class="gs-data-table w-100">
                        <thead>
                            <tr>

                                <th>{{ __('Deposit Date') }}</th>
                                <th>{{ __('Method') }}</th>
                                <th>{{ __('Amount') }}</th>
                                <th>{{ __('Status') }}</th>
                            </tr>

                        </thead>
                        @php
                        $deposites = App\Models\Deposit::where('user_id', Auth::user()->id)->latest()->paginate(12);
                        @endphp
                        <tbody>
                            @forelse ($deposites as $data)
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
                                <td colspan="4">{{ __('No Deposit Found') }}</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>

                </div>
                {{ $deposites->links('includes.frontend.pagination') }}
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