<div class="container mx-auto  pl-5">
    <div class="row">
        <div class="col-12  mx-auto">
            <div class="flex-shrink-0 me-3 py-3">
                <livewire:attributes :vehicle="$vehicle" />
            </div>
            <div class="d-flex flex-wrap align-items-start">
                <!-- Attributes Section -->


                <!-- Search Input Section -->
                <div class="flex-grow-1">
                    <div class="autoComplete_wrapper">
                        <input
                                type="text"
                                id="autoComplete"
                                class="form-control"
                                placeholder="Name / Part Number / Part Code"
                                wire:model.debounce.600ms="query"
                                wire:keydown.enter="search2('{{$query}}')"
                        >

                        @if (!empty($results))
                            <ul id="autoComplete_list_1" role="listbox" class="mt-2">
                                @foreach ($results as $result)
                                    <li class="list-group-item d-flex justify-content-between">
                                        <span
                                                wire:click="selectItem('{{ $result->partnumber }}')"
                                                style="text-overflow: ellipsis; white-space: nowrap; overflow: hidden;"
                                        >

                                                    {{ $result->partnumber }}



                                        </span>
                                        <small class="text-black" style="font-size: 13px; font-weight: 100; text-transform: uppercase;">
                                            {{ $result->label_en }} - {{ $result->label_ar }}
                                        </small>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
