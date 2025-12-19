{{-- resources/views/partials/api/tryoto-error.blade.php --}}
{{-- API-based Tryoto error partial (No Livewire) --}}

<div class="tryoto-error">
    <div class="alert alert-warning d-flex align-items-center" role="alert">
        <i class="fas fa-exclamation-triangle me-3" style="font-size: 24px;"></i>
        <div>
            <strong>@lang('shipping.smart_shipping_unavailable')</strong>
            @if(isset($error) && $error)
                <p class="mb-0 mt-1">{{ $error }}</p>
            @else
                <p class="mb-0 mt-1">@lang('shipping.default_error_message')</p>
            @endif
        </div>
    </div>
</div>
