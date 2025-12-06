{{-- resources/views/partials/api/compatibility-tabs.blade.php --}}
{{-- API-based compatibility tabs partial (No Livewire) --}}

@if($results && count($results))
    <div class="table-responsive d-none d-md-block">
        <table class="table table-bordered table-striped text-center align-middle">
            <thead class="table-light">
                <tr>
                    <th>@lang('compatibility.part_number')</th>
                    <th>@lang('compatibility.vehicle_name')</th>
                    <th>@lang('compatibility.catalog_code')</th>
                    <th>@lang('compatibility.from_year')</th>
                    <th>@lang('compatibility.to_year')</th>
                </tr>
            </thead>
            <tbody>
                @foreach($results as $item)
                    @php
                        $row = is_array($item) ? (object) $item : $item;
                    @endphp
                    <tr>
                        <td>{{ $row->part_number ?? '' }}</td>
                        <td>{{ $row->label ?? '' }}</td>
                        <td>{{ $row->catalog_code ?? '' }}</td>
                        <td>{{ $row->begin_year ?? '' }}</td>
                        <td>{{ ($row->end_year ?? 0) != 0 ? $row->end_year : __('compatibility.until_now') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <!-- Mobile Cards -->
    <div class="d-block d-md-none">
        @foreach($results as $item)
            @php
                $row = is_array($item) ? (object) $item : $item;
            @endphp
            <div class="card shadow-sm mb-3">
                <div class="card-body">
                    <p class="mb-1"><strong>@lang('compatibility.part_number'): </strong> {{ $row->part_number ?? '' }}</p>
                    <p class="mb-1"><strong>@lang('compatibility.vehicle'): </strong> {{ $row->label ?? '' }}</p>
                    <p class="mb-1"><strong>@lang('compatibility.catalog'): </strong> {{ $row->catalog_code ?? '' }}</p>
                    <p class="mb-1"><strong>@lang('compatibility.from'): </strong> {{ $row->begin_year ?? '' }}</p>
                    <p class="mb-1"><strong>@lang('compatibility.to'): </strong> {{ ($row->end_year ?? 0) != 0 ? $row->end_year : __('compatibility.until_now') }}</p>
                </div>
            </div>
        @endforeach
    </div>
@else
    <div class="alert alert-info text-center">
        @lang('compatibility.no_results')
    </div>
@endif
