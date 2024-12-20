<div class="p-5">

    {{ $this->attributes}}

{{--    @dd($this)--}}

    <button class="btn btn-primary" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasForm" aria-controls="offcanvasForm">
        Toggle Form
    </button>

    <!-- Offcanvas Component -->
    <div class="offcanvas offcanvas-start" tabindex="-1" id="offcanvasForm" aria-labelledby="offcanvasFormLabel">
        <div class="offcanvas-header">
            <h5 id="offcanvasFormLabel" class="mb-0">Form Details</h5>
            <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body">
            <!-- Responsive Form Container -->

            @php
                 $currentYear = date('Y');
                     $years = range($currentYear+1 ,1975);
             @endphp

            <form wire:submit.prevent="save" method="get" class="row g-1 p-1">
                <!-- Loop through attributes to generate form groups -->


                <label for="date-select">Select Date:</label>
                <div class="input-group">
                    <!-- Month Select -->
                    <select class="form-select me-2" id="month-select" wire:model.defer="data.month">
                        <option value="">Select Month</option>
                        @foreach (range(1, 12) as $month)
                            <option value="{{ str_pad($month, 2, '0', STR_PAD_LEFT) }}">
                                {{ str_pad($month, 2, '0', STR_PAD_LEFT) }}
                            </option>
                        @endforeach
                    </select>

                    <!-- Year Select -->
                    <select class="form-select" id="year-select" wire:model.defer="data.year">
                        <option value="">Select Year</option>
                        @foreach ($years as $year)
                            <option value="{{ $year }}">{{ $year }}</option>
                        @endforeach
                    </select>
                </div>

                @foreach ($catalog->attributes as $attribute)
                    <div class="col-md-12 col-lg-12">
                        <div class="form-group">
                            <label for="select-{{ $attribute['id'] }}" class="form-label">
                                {{ $attribute['label'] }}
                            </label>
{{--                            wire:model.debounce="data.{{ $attribute['name']  }}"--}}
                            <select  class="form-select" id="select-{{ $attribute['id'] }}" wire:model.defer="data.{{ $attribute['name']  }}"  name="{{ $attribute['name'] }}">
                                <!-- Loop through subitems to create options -->
                                @foreach ($attribute['items'] as $subitem)
                                    <option value="{{ $subitem['id'] }}" @if($subitem['disabled']) disabled @endif>
                                        {{ $subitem['label'] }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                @endforeach
                <!-- Submit Button -->
                <div class="col-12 mt-3">
                    <button type="submit" class="btn btn-success w-100">Submit</button>
                </div>
            </form>
        </div>
    </div>

@push('scripts')
        <script>
            $(function () {
                $("#datepicker").datepicker({
                    autoclose: true,
                    todayHighlight: true
                }).datepicker('update', new Date());
            });

        </script>



@endpush
</div>
