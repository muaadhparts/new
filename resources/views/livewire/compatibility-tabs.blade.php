<div>
    @if($results && count($results))
        <div class="table-responsive d-none d-md-block">
            <table class="table table-bordered table-striped text-center align-middle">
                <thead class="table-light">
                    <tr>
                        <th>🔢 Part Number</th>
                        <th>🚗 Vehicle Name</th>
                        <th>📚 Catalog Code</th>
                        <th>📅 From Year</th>
                        <th>📅 To Year</th>
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
                        <p class="mb-1"><strong>🔢 Part Number:</strong> {{ $item->part_number }}</p>
                        <p class="mb-1"><strong>🚗 Vehicle:</strong> {{ $item->label }}</p>
                        <p class="mb-1"><strong>📚 Catalog:</strong> {{ $item->catalog_code }}</p>
                        <p class="mb-1"><strong>📅 From:</strong> {{ $item->begin_year }}</p>
                        <p class="mb-1"><strong>📅 To:</strong> {{ $item->end_year && $item->end_year != 0 ? $item->end_year : 'حتى الآن' }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="alert alert-info text-center">
            لا توجد نتائج لرقم القطعة هذا.
        </div>
    @endif
</div>
