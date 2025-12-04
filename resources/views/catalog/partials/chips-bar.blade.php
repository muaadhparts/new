{{--
    Chips Bar - Display selected filters

    Required variables:
    - $chips: Array of chips [['key' => '', 'label' => '', 'value' => '', 'source' => 'vin|manual'], ...]
--}}

@if(!empty($chips) && count($chips) > 0)
    @php
        $hasVinSource = collect($chips)->contains(fn($c) => ($c['source'] ?? '') === 'vin');
    @endphp

    <div class="specs-bar mb-3 p-3 rounded" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
        <div class="d-flex align-items-center mb-2" style="gap: 0.5rem;">
            <strong class="text-white">
                <i class="fas {{ $hasVinSource ? 'fa-car' : 'fa-sliders-h' }} me-1"></i>
                {{ $hasVinSource ? __('ui.vin_specs') : __('ui.selected_specs') }}
            </strong>
            <span class="badge bg-light text-dark">{{ count($chips) }}</span>
        </div>

        <div class="d-flex flex-wrap" style="gap: 0.5rem;">
            @foreach($chips as $chip)
                <span class="spec-chip d-inline-flex align-items-center px-2 py-1 rounded-pill"
                      style="background: #fff; font-size: 0.85rem;">
                    <span style="color: #6c757d; font-weight: 500;">{{ $chip['label'] }}:</span>
                    <span style="font-weight: 600; color: #212529; margin-left: 0.25rem;">{{ $chip['value'] }}</span>
                    @if(!empty($chip['source']))
                        @if($chip['source'] === 'vin')
                            <span class="ms-1" style="font-size: 0.65rem; background: #0d6efd; color: #fff; border-radius: 0.75rem; padding: 0.1rem 0.4rem;">VIN</span>
                        @else
                            <span class="ms-1" style="font-size: 0.65rem; background: #198754; color: #fff; border-radius: 0.75rem; padding: 0.1rem 0.4rem;">MANUAL</span>
                        @endif
                    @endif
                </span>
            @endforeach
        </div>
    </div>
@endif
