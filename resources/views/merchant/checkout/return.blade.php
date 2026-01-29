@extends('layouts.front')

@section('content')
    {{-- Breadcrumb --}}
    <section class="gs-breadcrumb-section">
        <div class="container">
            <div class="row justify-content-center content-wrapper">
                <div class="col-12">
                    <h2 class="breadcrumb-name">@lang('Order Status')</h2>
                    <ul class="bread-menu">
                        <li><a href="{{ route('front.index') }}">@lang('Home')</a></li>
                        <li>@lang('Order Status')</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <div class="gs-checkout-wrapper muaadh-section-gray py-5">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8 col-xl-6">
                    @if($status === 'success')
                        {{-- Success State --}}
                        <div class="m-card text-center">
                            <div class="m-card__body py-5">
                                <div class="mb-4">
                                    <div class="success-checkmark">
                                        <i class="fas fa-check-circle fa-5x text-success"></i>
                                    </div>
                                </div>

                                <h2 class="mb-3">@lang('Order Placed Successfully!')</h2>
                                <p class="text-muted mb-4">@lang('Thank you for your purchase. Your order has been received and is being processed.')</p>

                                @if(!empty($purchase))
                                <div class="bg-light rounded p-4 mb-4">
                                    <div class="row g-3">
                                        <div class="col-sm-6">
                                            <small class="text-muted d-block">@lang('Order Number')</small>
                                            <strong class="text-primary">{{ $purchase->purchase_number ?? '-' }}</strong>
                                        </div>
                                        <div class="col-sm-6">
                                            <small class="text-muted d-block">@lang('Total Amount')</small>
                                            <strong>{{ formatPrice($purchase->pay_amount ?? 0) }}</strong>
                                        </div>
                                        <div class="col-sm-6">
                                            <small class="text-muted d-block">@lang('Payment Method')</small>
                                            <strong>{{ $purchase->method ?? '-' }}</strong>
                                        </div>
                                        <div class="col-sm-6">
                                            <small class="text-muted d-block">@lang('Payment Status')</small>
                                            <span class="m-badge m-badge--{{ $purchase->payment_status === 'Completed' ? 'paid' : 'pending' }}">
                                                {{ $purchase->payment_status ?? '-' }}
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <div class="m-alert m-alert--info mb-4">
                                    <i class="fas fa-envelope me-2"></i>
                                    @lang('A confirmation email has been sent to') <strong>{{ $purchase->customer_email ?? '' }}</strong>
                                </div>
                                @endif

                                <div class="d-flex flex-wrap justify-content-center gap-3">
                                    @if(auth()->check())
                                    <a href="{{ route('user-purchases') }}" class="m-btn m-btn--primary">
                                        <i class="fas fa-box me-2"></i>@lang('View My Orders')
                                    </a>
                                    @endif
                                    <a href="{{ route('front.index') }}" class="m-btn m-btn--outline">
                                        <i class="fas fa-home me-2"></i>@lang('Continue Shopping')
                                    </a>

                                    {{-- Check if there are more branches to checkout --}}
                                    @if($has_more_branches ?? false)
                                    <div class="w-100 mt-3">
                                        <div class="m-alert m-alert--warning mb-3">
                                            <i class="fas fa-info-circle me-2"></i>
                                            @lang('You still have items from other branches in your cart')
                                        </div>
                                        <a href="{{ route('merchant-cart.index') }}" class="m-btn m-btn--warning w-100">
                                            <i class="fas fa-shopping-cart me-2"></i>@lang('Complete Other Orders')
                                        </a>
                                    </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                    @elseif($status === 'failed')
                        {{-- Failed State --}}
                        <div class="m-card text-center">
                            <div class="m-card__body py-5">
                                <div class="mb-4">
                                    <i class="fas fa-times-circle fa-5x text-danger"></i>
                                </div>

                                <h2 class="mb-3 text-danger">@lang('Payment Failed')</h2>
                                <p class="text-muted mb-4">
                                    {{ $error_message ?? __('Unfortunately, your payment could not be processed. Please try again.') }}
                                </p>

                                <div class="d-flex flex-wrap justify-content-center gap-3">
                                    <a href="{{ route('branch.checkout.payment', $branch_id) }}" class="m-btn m-btn--primary">
                                        <i class="fas fa-redo me-2"></i>@lang('Try Again')
                                    </a>
                                    <a href="{{ route('merchant-cart.index') }}" class="m-btn m-btn--outline">
                                        <i class="fas fa-shopping-cart me-2"></i>@lang('Back to Cart')
                                    </a>
                                </div>
                            </div>
                        </div>

                    @elseif($status === 'cancelled')
                        {{-- Cancelled State --}}
                        <div class="m-card text-center">
                            <div class="m-card__body py-5">
                                <div class="mb-4">
                                    <i class="fas fa-ban fa-5x text-warning"></i>
                                </div>

                                <h2 class="mb-3">@lang('Payment Cancelled')</h2>
                                <p class="text-muted mb-4">@lang('You have cancelled the payment process. Your cart items are still saved.')</p>

                                <div class="d-flex flex-wrap justify-content-center gap-3">
                                    <a href="{{ route('branch.checkout.payment', $branch_id) }}" class="m-btn m-btn--primary">
                                        <i class="fas fa-credit-card me-2"></i>@lang('Complete Payment')
                                    </a>
                                    <a href="{{ route('merchant-cart.index') }}" class="m-btn m-btn--outline">
                                        <i class="fas fa-shopping-cart me-2"></i>@lang('Back to Cart')
                                    </a>
                                </div>
                            </div>
                        </div>

                    @else
                        {{-- Pending/Unknown State --}}
                        <div class="m-card text-center">
                            <div class="m-card__body py-5">
                                <div class="mb-4">
                                    <i class="fas fa-clock fa-5x text-info"></i>
                                </div>

                                <h2 class="mb-3">@lang('Processing Your Order')</h2>
                                <p class="text-muted mb-4">@lang('Your order is being processed. Please wait...')</p>

                                <div class="d-flex flex-wrap justify-content-center gap-3">
                                    @if(auth()->check())
                                    <a href="{{ route('user-purchases') }}" class="m-btn m-btn--primary">
                                        <i class="fas fa-box me-2"></i>@lang('View My Orders')
                                    </a>
                                    @endif
                                    <a href="{{ route('front.index') }}" class="m-btn m-btn--outline">
                                        <i class="fas fa-home me-2"></i>@lang('Continue Shopping')
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
<style>
.success-checkmark {
    animation: scaleIn 0.5s ease-in-out;
}

@keyframes scaleIn {
    0% {
        transform: scale(0);
        opacity: 0;
    }
    50% {
        transform: scale(1.2);
    }
    100% {
        transform: scale(1);
        opacity: 1;
    }
}
</style>
@endsection
