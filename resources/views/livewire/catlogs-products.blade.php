<div class="container py-4">
    <div class="row">
        <div class="col-12 mx-auto">

            <!-- المواصفات -->
            <div class="flex-shrink-0 me-3 py-3">
                <livewire:attributes :catalog="$catalog" />
            </div>

            <!-- البحث -->
            <div class="d-flex flex-wrap align-items-start">
                <div class="row mb-3 w-100">
                    <div class="col-md-8 mx-auto">
                        <input
                            type="text"
                            class="form-control"
                            placeholder="Search by Part Number, partCode, Name EN/AR"
                            wire:model.debounce.500ms="query"
                            wire:keydown.enter="$refresh"
                        >
                    </div>
                </div>

                @php
                    $validCategoryCodes = session('preloaded_full_code', []);
                @endphp

                @if (!empty($prods) && count($prods))
                    <div class="table-responsive w-100">
                        <table class="table table-bordered table-striped text-center align-middle">
                            <thead class="table-dark">
                                <tr>
                                    <th>PartCode</th>
                                    <th>Part Number</th>
                                    <th>Label</th>
                                    <th>Quantity</th>
                                    <th>Specifications</th>
                                    <th>Valid From</th>
                                    <th>Valid To</th>
                                    <th>Location</th>
                                    <th>Check Out</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($prods as $result)
                                    @php
                                        $hasLocation = !empty($result->category_code)
                                            && $brand?->name
                                            && in_array($result->category_code, $validCategoryCodes);

                                        $category = $hasLocation
                                            ? \App\Models\NewCategory::where('full_code', $result->category_code)->first()
                                            : null;

                                        $level2 = $category?->parent;
                                        $level1 = $level2?->parent;
                                    @endphp

                                    @if (!$hasLocation || !$category || !$level1 || !$level2)
                                        @continue
                                    @endif

                                    <tr>
                                        <td>{{ $result->part_callout ?? '—' }}</td>
                                        <td>{{ $result->part_number }}</td>
                                        <td>{{ $result->part_label_en ?? $result->part_label_ar ?? '—' }}</td>
                                        <td>{{ $result->part_qty ?? '—' }}</td>
                                        <td>{{ $result->attribute_name ?? '—' }}</td>
                                        <td>{{ $result->part_begin ? \Carbon\Carbon::parse($result->part_begin)->format('Y-m') : '—' }}</td>
                                        <td>{{ $result->part_end ? \Carbon\Carbon::parse($result->part_end)->format('Y-m') : '—' }}</td>
                                        <td>
                                            <a href="{{ route('illustrations', [
                                                    'id'    => $brand->name,
                                                    'data'  => $catalog->code,
                                                    'key1'  => $level1->full_code,
                                                    'key2'  => $level2->full_code,
                                                    'key3'  => $category->formattedCode,
                                                    'key4'  => $category->full_code,
                                                    'vin'   => request('vin')
                                                ]) }}" class="btn btn-outline-secondary btn-sm">
                                                {{ $result->category_code }}
                                            </a>
                                        </td>
                                        <td>
                                            <a href="{{ route('search.result', $result->part_number) }}" target="_blank" class="btn btn-sm btn-primary">
                                                View
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @elseif(strlen($query) > 1)
                    <div class="alert alert-warning text-center w-100">
                        No results found for "{{ $query }}"
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
