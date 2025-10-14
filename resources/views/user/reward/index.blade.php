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
                        <h3 class="ud-page-title">@lang('Reward Point')</h3>
                        <a class="template-btn md-btn black-btn data-table-btn" href="{{ route('user-reward-convernt') }}">
                            <i class="fas fa-plus"></i> @lang('Convert Point')</a>
                    </div>

                    <!--  order status steps -->

                    <div class="user-table table-responsive position-relative">

                        <table class="gs-data-table w-100">
                            <thead>
                                <tr>

                                    <th>{{ __('Reward') }}</th>
                                    <th>{{ __('Amount') }}</th>
                                    <th>{{ __('Created at') }}</th>
                                </tr>

                            </thead>
                            <tbody>
                                @forelse($datas as $data)
                                    <!-- table data row 1 start  -->
                                    <tr>

                                        <td><span class="content">{{ $data->reward_point }}</span>
                                        </td>
                                        <td><span class="content">${{ $data->reward_dolar }}</span>
                                        </td>
                                        <td class="text-center">
                                            <span class="content ">{{ $data->created_at->diffForHumans() }}
                                            </span>
                                        </td>

                                    </tr>
                                    <!-- table data row 1 end  -->
                                @empty
                                    <tr>
                                        <td colspan="4">{{ __('No Reward Found') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>

                    </div>
                    {{ $datas->links('includes.frontend.pagination') }}
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
