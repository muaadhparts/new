{{-- Empty state for offers when part not found --}}
<div class="text-center py-5">
    <i class="fas fa-box-open fa-3x text-muted mb-3"></i>
    <h5 class="text-muted">{{ $message ?? __('No offers available') }}</h5>
    @if(isset($part_number))
        <p class="text-muted mb-0">
            <small>@lang('Part Number'): <code>{{ $part_number }}</code></small>
        </p>
    @endif
</div>
