<div>
    @php
        $filtersLabeled = Session::get('selected_filters_labeled');
        $isFromVin = Session::has('vin');
    @endphp

    <!-- زر فتح النافذة -->
    <button class="btn btn-primary" type="button"
            data-bs-toggle="offcanvas" data-bs-target="#offcanvasForm"
            aria-controls="offcanvasForm">
        Specifications
    </button>
    <!-- عرض المواصفات -->
    {{-- @if(is_array($filtersLabeled) && count($filtersLabeled))
        <div class="alert alert-warning mt-3">
            <strong>Selectd Specifications:</strong>
            <ul class="mb-0">
                @foreach($filtersLabeled as $name => $value)
                    <li>
                        {{ $value['label'] }}:
                        <span class="text-primary">{{ $value['value'] ?? $value['value_id'] }}</span>
                        @if(isset($value['source']) && $value['source'] === 'vin')
                            <small class="text-muted">(Vin)</small>
                        @endif
                    </li>
                @endforeach
            </ul>
        </div>
    @endif --}}

    <!-- نموذج المواصفات -->
    <form wire:submit.prevent="save" class="row g-1 p-1">
        <div class="offcanvas offcanvas-start" tabindex="-1" id="offcanvasForm"
             aria-labelledby="offcanvasFormLabel" data-bs-backdrop="static">

            <div class="offcanvas-header">
                <h5 id="offcanvasFormLabel" class="mb-0">
                    Specifications {{ $catalogName ?? $shortName ?? $catalogCode ?? 'Unknown' }}
                    @if(isset($source)) <small class="text-muted"> ({{ $source }})</small> @endif
                </h5>

                <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas"
                        aria-label="Close"></button>
            </div>

            <div class="offcanvas-body">

                <!-- التاريخ -->
                @if(isset($filters['year']) || isset($filters['month']))
                    <div class="mb-3">
                        <label>Build Date:</label>
                        <div class="input-group">
                            @if(isset($filters['month']))
                                <select class="form-select me-2"
                                        wire:model="data.month.value_id"
                                        name="data[month][value_id]"
                                        @if($isFromVin) disabled @endif>
                                    <option value="">Month</option>
                                    @foreach($filters['month']['items'] ?? [] as $item)
                                        <option value="{{ is_object($item) ? $item->value_id : $item['value_id'] }}">
                                            {{ is_object($item) ? $item->label : $item['label'] }}
                                        </option>
                                    @endforeach
                                </select>
                            @endif

                            @if(isset($filters['year']))
                                <select class="form-select"
                                        wire:model="data.year.value_id"
                                        name="data[year][value_id]"
                                        @if($isFromVin) disabled @endif>
                                    <option value="">Year</option>
                                    @foreach($filters['year']['items'] ?? [] as $item)
                                        <option value="{{ is_object($item) ? $item->value_id : $item['value_id'] }}">
                                            {{ is_object($item) ? $item->label : $item['label'] }}
                                        </option>
                                    @endforeach
                                </select>
                            @endif
                        </div>
                    </div>
                @endif

                <!-- باقي الخصائص -->
                @forelse ($filters as $index => $attribute)
                    @if(!in_array($index, ['year', 'month']))
                        <div class="mb-3">
                            <label for="select-{{ $index }}" class="form-label">
                                {{ $attribute['label'] ?? $index }}
                            </label>
                            <select class="form-select"
                                    wire:model="data.{{ $index }}.value_id"
                                    name="data[{{ $index }}][value_id]"
                                    @if($isFromVin) disabled @endif>
                                <option value="">-- Choose --</option>
                                @foreach ($attribute['items'] ?? [] as $item)
                                    <option value="{{ is_object($item) ? $item->value_id : $item['value_id'] }}">
                                        {{ is_object($item) ? $item->label : $item['label'] }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                @empty
                    <div class="alert alert-warning mt-2">
                        No specifications available to display.
                    </div>
                @endforelse

                <!-- زر الحفظ -->
                @unless($isFromVin)
                    <div class="d-grid mt-4">
                        <button type="submit" class="btn btn-success">
                            Save Specifications
                        </button>
                    </div>
                @endunless

     <!-- زر الإزالة -->
@unless($isFromVin)
    <div class="d-grid mt-2">
        <button type="button" class="btn btn-outline-secondary" wire:click="resetFilters">
            Clear Entries
        </button>
    </div>
@endunless

</div> <!-- /offcanvas-body -->
</div> <!-- /offcanvas -->
</form>

<script>
(function() {
    document.addEventListener('livewire:init', function() {
        Livewire.on('filtersSelected', function() {
            console.log('Filters saved - reloading');
            setTimeout(function() {
                window.location.reload();
            }, 300);
        });

        Livewire.on('filtersCleared', function() {
            console.log('Filters cleared - reloading');
            setTimeout(function() {
                window.location.reload();
            }, 300);
        });
    });
})();
</script>
</div> <!-- /wrapper div -->
