@extends('layouts.front')
@section('content')
    <div class="gs-user-panel-review wow-replaced" data-wow-delay=".1s">
        <div class="container">
            <div class="d-flex">
                <!-- sidebar -->
                @include('includes.user.sidebar')
                <!-- main content -->
                <div class="gs-dashboard-user-content-wrapper gs-dashboard-outlet">
                    <div class="gs-deposit-section">

                        <div class="gs-deposit-title d-flex align-items-center gap-4 ms-0 mb-5">
                            <h4>@lang('Pricing Plans')</h4>
                        </div>

                        <div class="row gy-4">
                            @foreach ($subs as $sub)
                                <div class="col-md-6">
                                    <div class="gs-extra-basic-plan">
                                        <h4 class="title">{{ $sub->title }}</h4>

                                        <div class="circle-wrapper">
                                            @if ($sub->price == 0)
                                                <h4 class="cr-title">@lang('Free')
                                                </h4>
                                            @else
                                                <h4 class="cr-title">
                                                    {{ $curr->sign }}{{ round($sub->price * $curr->value, 2) }}
                                                </h4>
                                                <span class="cr-sm-title">{{ $sub->days }} @lang('Days')</span>
                                            @endif

                                        </div>

                                        <p>
                                            @php
                                                echo $sub->details;
                                            @endphp
                                        </p>


                                        @if (!empty($package))
                                            @if ($package->subscription_id == $sub->id && $sub->price != 0)
                                                <a href="javascript:;"
                                                    class="template-btn outline-btn w-100">{{ __('Current Plan') }}</a>
                                                <br>
                                                @if (Carbon\Carbon::now()->format('Y-m-d') > $user->date)
                                                    <small class="hover-white">{{ __('Expired on:') }}
                                                        {{ date('d/m/Y', strtotime($user->date)) }}</small>
                                                @else
                                                    <small class="hover-white">{{ __('Ends on:') }}
                                                        {{ date('d/m/Y', strtotime($user->date)) }}</small>
                                                @endif
                                                <a href="{{ route('user-vendor-request', $sub->id) }}"
                                                    class=""><u>{{ __('Renew') }}</u></a>
                                            @else
                                                <a href="{{ route('user-vendor-request', $sub->id) }}"
                                                    class="template-btn outline-btn w-100">{{ __('Get Started') }}</a>
                                                <br><small>&nbsp;</small>
                                            @endif
                                        @else
                                            <a href="{{ route('user-vendor-request', $sub->id) }}"
                                                class="template-btn outline-btn w-100">{{ __('Get Started') }}</a>
                                            <br><small>&nbsp;</small>
                                        @endif

                                    </div>
                                </div>
                            @endforeach
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
