{{--
    Chips Bar - Display selected filters
    Uses catalog-unified.css for styling

    Required variables:
    - $chips: Array of chips [['key' => '', 'label' => '', 'value' => '', 'source' => 'vin|manual'], ...]
--}}

@if(!empty($chips) && count($chips) > 0)
    @php
        $hasVinSource = collect($chips)->contains(fn($c) => ($c['source'] ?? '') === 'vin');
    @endphp

    <div class="catalog-chips-bar">
        <div class="catalog-chips-header">
            <strong class="catalog-chips-title">
                <i class="fas {{ $hasVinSource ? 'fa-car' : 'fa-sliders-h' }}"></i>
                {{ $hasVinSource ? __('ui.vin_specs') : __('ui.selected_specs') }}
            </strong>
            <span class="catalog-chips-count">{{ count($chips) }}</span>
        </div>

        <div class="catalog-chips-container">
            @foreach($chips as $chip)
                <span class="catalog-chip">
                    <span class="catalog-chip-label">{{ $chip['label'] }}:</span>
                    <span class="catalog-chip-value">{{ $chip['value'] }}</span>
                    @if(!empty($chip['source']))
                        @if($chip['source'] === 'vin')
                            <span class="catalog-chip-source catalog-chip-source-vin">VIN</span>
                        @else
                            <span class="catalog-chip-source catalog-chip-source-manual">MANUAL</span>
                        @endif
                    @endif
                </span>
            @endforeach
        </div>
    </div>
@endif
