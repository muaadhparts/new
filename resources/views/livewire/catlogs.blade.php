<div class="container">
    @if(session()->has('error'))
        <div class="alert alert-danger">
            {{ session('error') }}
        </div>
    @endif

    <div class="row gy-4 gy-lg-5 mt-4">
        {{-- <livewire:search-box/> --}}
        <livewire:search-boxvin/>
        <div>
            <!-- Search and Filter Row -->
            <div class="row mb-4">
                <!-- Search Input -->
                <div class="col-md-3 mb-3">
                    <input type="text" class="form-control" wire:model.debounce.500ms="searchName"
                           placeholder="{{ __('Search') }}">
                </div>

                <!-- Region Filter -->
                @if($this->regionOptions && count($this->regionOptions) > 0)
                    <div class="col-md-3 mb-3">
                        <select class="form-select" wire:model="region">
                            @foreach ($this->regionOptions as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif

                <!-- Year Filter Dropdown -->
                <div class="col-md-3 mb-3">
                    <select class="form-select" wire:model="searchYear">
                        <option value="">{{ __('Filter by Year') }}</option>
                        @foreach ($years as $year)
                            <option value="{{ $year }}">{{ $year }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        @if($catlogs && $catlogs->count() > 0)
            @foreach ($catlogs->sortby('sort') as $catalog)
                <div class="col-12 col-sm-6 col-md-4 col-xl-3 text-center">
                    @php $vin = session('vin'); @endphp

                    <a href="{{ route('tree.level1', [
                        'id' => $brand->name,
                        'data' => $catalog->code,
                        'vin' => $vin
                    ]) }}">

                        <div class="single-product card border-0 shadow-sm h-100">
                            <div class="img-wrapper position-relative">
                                <img class="xproduct-img img-fluid rounded" 
                                     src="{{ Storage::url($catalog->largeImagePath) }}" 
                                     alt="product img"
                                     onerror="this.onerror=null; this.src='{{ asset('assets/images/no-image.png') }}';">
                            </div>
                            <div class="ccontent-wrapper p-3 text-center">
                                <a href="" class="text-decoration-none">
                                   <h6 class="product-title text-dark fw-bold text-center">
                                        {{ app()->getLocale() == 'ar' ? $catalog->label_ar : $catalog->label_en }}
                                    </h6>
                                </a>
                                <p class="text-muted small">{{ $catalog->code }}</p>
                                <div class="xprice-wrapper mt-2 text-center">
                                    <h6 class="text-muted">
                                        {{ formatYearRange($catalog->beginYear, $catalog->endYear) }}
                                    </h6>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
            @endforeach
        @else
            <div class="col-12">
                <div class="alert alert-info text-center">
                    <h5>{{ __('No catalogs available') }}</h5>
                    <p>{{ __('No catalogs match the selected search criteria.') }}</p>
                </div>
            </div>
        @endif
    </div>

    @if($catlogs && $catlogs->hasPages())
        <div class="d-flex justify-content-center my-5">
            {!! $catlogs->links('includes.frontend.pagination') !!}
        </div>
    @endif
</div>
