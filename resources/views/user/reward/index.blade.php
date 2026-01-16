@extends('layouts.front')
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
                        <h3 class="ud-page-name">@lang('Reward Points')</h3>
                    </div>

                    <!-- Points Balance Card -->
                    <div class="row mb-4">
                        <div class="col-lg-6">
                            <div class="m-card">
                                <div class="m-card__body text-center py-4">
                                    <h6 class="text-muted mb-2">@lang('Your Points Balance')</h6>
                                    <h2 class="mb-3" style="color: var(--theme-primary); font-size: 48px; font-weight: 700;">
                                        {{ number_format($user->reward ?? 0) }}
                                    </h2>
                                    <p class="text-muted mb-0">
                                        @lang('Points Value'):
                                        <strong>{{ $curr->sign }}{{ number_format($pointsValue, 2) }}</strong>
                                    </p>
                                    <small class="text-muted">
                                        (1 @lang('point') = {{ $curr->sign }}{{ number_format($pointValue, 2) }})
                                    </small>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-6">
                            <div class="m-card h-100">
                                <div class="m-card__body py-4">
                                    <h6 class="mb-3">@lang('How to Earn Points')</h6>
                                    <ul class="list-unstyled mb-0">
                                        <li class="mb-2">
                                            <i class="fas fa-check-circle text-success me-2"></i>
                                            @lang('Make purchases from our merchants')
                                        </li>
                                        <li class="mb-2">
                                            <i class="fas fa-check-circle text-success me-2"></i>
                                            @lang('Points are calculated from subtotal before tax and shipping')
                                        </li>
                                        <li class="mb-2">
                                            <i class="fas fa-check-circle text-success me-2"></i>
                                            @lang('Different merchants may offer different point rates')
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Points Usage Info -->
                    <div class="m-card">
                        <div class="m-card__header">
                            <h6 class="mb-0">@lang('Using Your Points')</h6>
                        </div>
                        <div class="m-card__body">
                            <p class="mb-0">
                                @lang('You can use your reward points as a payment method during checkout. Points will be deducted from your total purchase amount.')
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- user dashboard wrapper end -->
@endsection
