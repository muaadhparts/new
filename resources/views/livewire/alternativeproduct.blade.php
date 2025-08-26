<div>
    @if($alternatives && $alternatives->count() > 0)
        <div class="col">
            <button type="button" class="template-btn w-100" data-bs-toggle="modal" data-bs-target="#alternativeModal">
                @lang('Alternatives')
            </button>
        </div>

        <!-- Modal -->
        <div class="modal fade" id="alternativeModal" tabindex="-1" aria-labelledby="alternativeModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-xl">
                <div class="modal-content">
                    <div class="modal-header d-flex justify-content-between align-items-center">
                        <h5 class="modal-title fw-bold">@lang('Product Alternatives'): {{ $sku }}</h5>
                        <button type="button" class="btn btn-light rounded-circle shadow-sm" 
                                style="width: 35px; height: 35px; display: flex; align-items: center; justify-content: center;"
                                data-bs-dismiss="modal" aria-label="Close">
                            <i class="fas fa-times text-danger"></i>
                        </button>
                    </div>
                    <div class="modal-body">

                        <!-- جدول للكمبيوتر -->
                        <div class="container d-none d-md-block">
                            <table class="table table-bordered text-center align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>@lang('Part Number')</th>
                                        <th>@lang('Name')</th>
                                        <th>@lang('Stock')</th>
                                        <th>@lang('Price')</th>
                                        <th>@lang('View')</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($alternatives as $result)
                                        @php
                                            $highlight = ($result->stock > 0 && $result->vendorPrice() > 0);
                                        @endphp
                                        <tr @if($highlight) style="background-color: #f0fff4;" @endif>
                                            <td>{{ $result->sku }}</td>
                                            <td>
                                                @php
                                                    $locale = app()->getLocale();
                                                    echo $locale === 'ar' ? ($result->label_ar ?: $result->label_en) : $result->label_en;
                                                @endphp
                                            </td>
                                            <td>{{ $result->stock ?? 0 }}</td>
                                            <td class="fw-bold {{ $highlight ? 'text-success' : '' }}">{{ $result->showPrice() }}</td>
                                            <td>
                                                <a class="btn btn-outline-primary btn-sm" href="{{ route('front.product', $result->slug) }}">
                                                    @lang('View')
                                                </a>
                                            </td>
                                        </tr>
                                    @empty

                                        <tr>
                                            <td colspan="5" class="text-center">@lang('No data found')</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <!-- كروت للموبايل -->
                        <div class="container d-block d-md-none">
                            <div class="row g-3">
                                @forelse($alternatives as $result)
                                    <div class="col-12">
                                        <div class="card shadow-sm h-100 @if($highlight) border-success @endif">
                                            <div class="row g-0">
                                                <div class="col-4">
                                                    <img src="{{ $result->photo ? \Illuminate\Support\Facades\Storage::url($result->photo) : asset('assets/images/noimage.png') }}"
                                                        class="img-fluid rounded-start" alt="{{ $result->sku }}">
                                                </div>
                                                <div class="col-8">
                                                    <div class="card-body p-2">
                                                        <h6 class="card-title mb-1">
                                                            @php
                                                                $locale = app()->getLocale();
                                                                echo $locale === 'ar' ? ($result->label_ar ?: $result->label_en) : $result->label_en;
                                                            @endphp
                                                        </h6>
                                                        <p class="mb-1 small text-muted"><strong>@lang('Part Number'):</strong> {{ $result->sku }}</p>
                                                        <p class="mb-1 fw-bold {{ $highlight ? 'text-success' : '' }}">{{ $result->showPrice() }}</p>
                                                        <p class="mb-2 small"><strong>@lang('Stock'):</strong> {{ $result->stock ?? 0 }}</p>
                                                        <a href="{{ route('front.product', $result->slug) }}" class="btn btn-primary btn-sm w-100">
                                                            @lang('View')
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @empty
                                    <p class="text-center">@lang('No data found')</p>
                                @endforelse
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
