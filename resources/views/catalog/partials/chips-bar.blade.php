{{--
    Chips Bar - Display selected filters
    Uses catalog-unified.css for styling

    Required variables:
    - $chips: Array of chips [['key' => '', 'label' => '', 'value' => '', 'source' => 'vin|manual'], ...]
--}}

<div id="specsChipsBar">
    @if(!empty($chips) && count($chips) > 0)
        {{-- $hasVinSource pre-computed in VehicleCatalogController (DATA_FLOW_POLICY) --}}

        <div class="catalog-chips-bar">
            <div class="catalog-chips-header">
                <strong class="catalog-chips-name">
                    <i class="fas {{ ($hasVinSource ?? false) ? 'fa-car' : 'fa-filter' }}"></i>
                    {{ ($hasVinSource ?? false) ? __('catalog.vin_specs') : __('catalog.selected_specs') }}
                </strong>
                <span class="catalog-chips-count">{{ count($chips) }}</span>
            </div>

            <div class="catalog-chips-container">
                @foreach($chips as $chip)
                    <span class="catalog-chip" data-key="{{ $chip['key'] ?? '' }}">
                        <span class="catalog-chip-label">{{ $chip['label'] }}:</span>
                        <span class="catalog-chip-value">{{ $chip['value'] }}</span>
                        @if(!empty($chip['source']))
                            @if($chip['source'] === 'vin')
                                <span class="catalog-chip-source catalog-chip-source-vin">{{ __('catalog.vin') }}</span>
                            @else
                                <span class="catalog-chip-source catalog-chip-source-manual">{{ __('catalog.manual') }}</span>
                            @endif
                        @endif
                    </span>
                @endforeach
            </div>
        </div>
    @endif
</div>
