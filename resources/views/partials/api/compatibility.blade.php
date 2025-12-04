{{-- resources/views/partials/api/compatibility.blade.php --}}
{{-- API-based compatibility partial (No Livewire) --}}

@if($results && count($results))
    <div class="table-responsive">
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
                    <tr>
                        <td>{{ $item->part_number ?? $item['part_number'] ?? '' }}</td>
                        <td>{{ $item->label ?? $item['label'] ?? '' }}</td>
                        <td>{{ $item->catalog_code ?? $item['catalog_code'] ?? '' }}</td>
                        <td>{{ $item->begin_year ?? $item['begin_year'] ?? '' }}</td>
                        <td>{{ ($item->end_year ?? $item['end_year'] ?? 0) != 0 ? ($item->end_year ?? $item['end_year']) : __('compatibility.until_now') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@else
    <div class="alert alert-info text-center">
        @lang('compatibility.no_results')
    </div>
@endif
