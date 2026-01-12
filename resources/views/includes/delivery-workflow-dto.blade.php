{{--
    Delivery Workflow Progress Indicator (DTO Version - No Model Calls)
    Usage: @include('includes.delivery-workflow-dto', ['delivery' => $deliveryDto])

    Receives a pure DTO array with pre-computed values.
    NO model methods, NO relationships, NO business logic.
    ONLY rendering.
--}}

@php
    $step = $delivery['workflowStep'] ?? 1;

    // Pre-computed progress percent based on step
    $progressPercent = match(true) {
        $step >= 6 => 100,
        $step >= 5 => 80,
        $step >= 4 => 60,
        $step >= 3 => 40,
        $step >= 2 => 20,
        default => 0,
    };

    // Define steps in order (static data, no computation)
    $steps = [
        ['key' => 'pending_approval', 'label' => __('Approval'), 'icon' => 'fa-clock', 'description' => __('Courier Approval'), 'step' => 1],
        ['key' => 'approved', 'label' => __('Preparing'), 'icon' => 'fa-box-open', 'description' => __('Merchant Preparing'), 'step' => 2],
        ['key' => 'ready_for_pickup', 'label' => __('Ready'), 'icon' => 'fa-box', 'description' => __('Ready for Pickup'), 'step' => 3],
        ['key' => 'picked_up', 'label' => __('Picked Up'), 'icon' => 'fa-handshake', 'description' => __('Courier Picked Up'), 'step' => 4],
        ['key' => 'delivered', 'label' => __('Delivered'), 'icon' => 'fa-truck', 'description' => __('Delivered to Customer'), 'step' => 5],
        ['key' => 'confirmed', 'label' => __('Confirmed'), 'icon' => 'fa-check-double', 'description' => __('Customer Confirmed'), 'step' => 6],
    ];
@endphp

<div class="delivery-workflow-container mb-4">
    @if($delivery['isRejected'])
        {{-- Rejected Status - Show Warning --}}
        <div class="alert alert-danger d-flex align-items-center mb-3">
            <i class="fas fa-exclamation-triangle fa-lg me-3"></i>
            <div>
                <strong>@lang('Courier Rejected')</strong>
                <br>
                <small>@lang('This delivery was rejected by the courier. A new courier needs to be assigned.')</small>
                @if($delivery['rejectionReason'])
                    <br><small class="text-muted">@lang('Reason'): {{ $delivery['rejectionReason'] }}</small>
                @endif
            </div>
        </div>
    @else
        {{-- Progress Steps --}}
        <div class="delivery-workflow-steps d-flex justify-content-between align-items-start position-relative">
            {{-- Progress Line --}}
            <div class="workflow-progress-line position-absolute" style="top: 20px; left: 8%; right: 8%; height: 4px; background: var(--border-default, #e5e7eb); z-index: 0;">
                <div class="workflow-progress-fill" style="width: {{ $progressPercent }}%; height: 100%; background: var(--action-success, #22c55e); transition: width 0.3s ease;"></div>
            </div>

            @foreach($steps as $s)
                @php
                    $isActive = $step >= $s['step'];
                    $isCurrent = $step == $s['step'];
                @endphp
                <div class="workflow-step text-center flex-grow-1 position-relative" style="z-index: 1;">
                    {{-- Step Circle --}}
                    <div class="workflow-step-circle mx-auto mb-2 d-flex align-items-center justify-content-center rounded-circle"
                         style="width: 40px; height: 40px;
                                background: {{ $isCurrent ? 'var(--action-primary, #3b82f6)' : ($isActive ? 'var(--action-success, #22c55e)' : 'var(--surface-secondary, #f3f4f6)') }};
                                color: {{ $isActive ? '#fff' : 'var(--text-tertiary, #9ca3af)' }};
                                border: 3px solid {{ $isCurrent ? 'var(--action-primary, #3b82f6)' : ($isActive ? 'var(--action-success, #22c55e)' : 'var(--border-default, #e5e7eb)') }};
                                transition: all 0.3s ease;">
                        <i class="fas {{ $s['icon'] }}"></i>
                    </div>

                    {{-- Step Label --}}
                    <div class="workflow-step-label">
                        <strong class="d-block" style="font-size: 0.75rem; color: {{ $isActive ? 'var(--text-primary, #111827)' : 'var(--text-tertiary, #9ca3af)' }};">
                            {{ $s['label'] }}
                        </strong>
                        <small class="d-none d-md-block" style="font-size: 0.65rem; color: var(--text-secondary, #6b7280);">
                            {{ $s['description'] }}
                        </small>
                    </div>

                    {{-- Current Indicator --}}
                    @if($isCurrent)
                        <span class="workflow-current-pulse position-absolute"
                              style="top: 0; left: 50%; transform: translateX(-50%); width: 50px; height: 50px; border-radius: 50%; background: var(--action-primary, #3b82f6); opacity: 0.2; animation: pulse 2s infinite;"></span>
                    @endif
                </div>
            @endforeach
        </div>

        {{-- Next Action Info --}}
        @if($delivery['hasNextAction'])
            <div class="mt-3 p-3 border rounded" style="background: var(--surface-secondary, #f9fafb);">
                <div class="d-flex align-items-center">
                    <div class="me-3">
                        @if($delivery['nextActionActor'] === 'courier')
                            <span class="badge bg-warning text-dark p-2">
                                <i class="fas fa-motorcycle"></i> @lang('Courier Action')
                            </span>
                        @elseif($delivery['nextActionActor'] === 'merchant')
                            <span class="badge bg-info p-2">
                                <i class="fas fa-store"></i> @lang('Merchant Action')
                            </span>
                        @elseif($delivery['nextActionActor'] === 'customer')
                            <span class="badge bg-primary p-2">
                                <i class="fas fa-user"></i> @lang('Customer Action')
                            </span>
                        @endif
                    </div>
                    <div>
                        <strong>@lang('Next Step'):</strong> {{ $delivery['nextActionText'] }}
                    </div>
                </div>
            </div>
        @endif
    @endif

    {{-- Timestamps --}}
    <div class="mt-3">
        <div class="row g-2 text-muted" style="font-size: 0.75rem;">
            @if($delivery['approvedAt'])
                <div class="col-auto">
                    <i class="fas fa-check text-success"></i> @lang('Approved'): {{ $delivery['approvedAt'] }}
                </div>
            @endif
            @if($delivery['readyAt'])
                <div class="col-auto">
                    <i class="fas fa-box text-info"></i> @lang('Ready'): {{ $delivery['readyAt'] }}
                </div>
            @endif
            @if($delivery['pickedUpAt'])
                <div class="col-auto">
                    <i class="fas fa-handshake text-primary"></i> @lang('Picked'): {{ $delivery['pickedUpAt'] }}
                </div>
            @endif
            @if($delivery['deliveredAtShort'])
                <div class="col-auto">
                    <i class="fas fa-truck text-success"></i> @lang('Delivered'): {{ $delivery['deliveredAtShort'] }}
                </div>
            @endif
            @if($delivery['confirmedAtShort'])
                <div class="col-auto">
                    <i class="fas fa-check-double text-success"></i> @lang('Confirmed'): {{ $delivery['confirmedAtShort'] }}
                </div>
            @endif
        </div>
    </div>

    {{-- Payment Method Indicator --}}
    @if($delivery['isCod'])
        <div class="mt-2">
            <span class="badge bg-warning text-dark py-2 px-3">
                <i class="fas fa-money-bill-wave me-1"></i>
                @lang('Cash on Delivery'): {{ \PriceHelper::showAdminCurrencyPrice($delivery['codAmount']) }}
            </span>
        </div>
    @endif
</div>

<style>
@keyframes pulse {
    0% {
        transform: translateX(-50%) scale(1);
        opacity: 0.2;
    }
    50% {
        transform: translateX(-50%) scale(1.2);
        opacity: 0.1;
    }
    100% {
        transform: translateX(-50%) scale(1);
        opacity: 0.2;
    }
}
</style>
