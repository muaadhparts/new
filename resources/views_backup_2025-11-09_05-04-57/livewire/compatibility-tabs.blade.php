<div>
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
                        <tr>
                            <td>{{ $item->part_number }}</td>
                            <td>{{ $item->label }}</td>
                            <td>{{ $item->catalog_code }}</td>
                            <td>{{ $item->begin_year }}</td>
                            <td>{{ $item->end_year && $item->end_year != 0 ? $item->end_year : 'حتى الآن' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- نسخة الجوال -->
        <div class="d-block d-md-none">
            @foreach($results as $item)
                <div class="card shadow-sm mb-3">
                    <div class="card-body">
                        <p class="mb-1"><strong>@lang('compatibility.part_number:') </strong> {{ $item->part_number }}</p>
                        <p class="mb-1"><strong>@lang('compatibility.vehicle:') </strong> {{ $item->label }}</p>
                        <p class="mb-1"><strong>@lang('compatibility.catalog:') </strong> {{ $item->catalog_code }}</p>
                        <p class="mb-1"><strong>@lang('compatibility.from:') </strong> {{ $item->begin_year }}</p>
                        <p class="mb-1"><strong>@lang('compatibility.to:') </strong> {{ $item->end_year && $item->end_year != 0 ? $item->end_year : __('compatibility.until_now') }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="alert alert-info text-center">
            @lang('compatibility.no_results')
        </div>
    @endif
</div>
