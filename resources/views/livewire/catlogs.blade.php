<div class="container  ">




    <div class="row gy-4 gy-lg-5 mt-4">
        <livewire:search-box/>
        <div>

            <!-- Search and Filter Row -->
            <div class="row mb-4">
                <!-- Search Input -->
                <div class="col-md-3 mb-3">
                    <input type="text" class="form-control" wire:model.debounce.500ms="searchName"
                           placeholder="Search">
                </div>


                <div class="col-md-3 mb-3">
                    <select class="form-select" wire:model="region">
                        <option value="GL"   > [GL] General Market - Left-Hand Drive </option>
                         <option value="AR">[AR] Australia </option>
                        <option value="CA">[CA] Canada </option>
                        <option value="EL"> [EL] Europe - Left-Hand Drive </option>
                        <option value="EL"> [EL] Europe - Right-Hand Drive </option>

                        <option value="GR"> [GR] General Market - Right-Hand Drive </option>
                        <option value="JP"> [JP] Japan </option>
                        <option value="US"> [US] USA </option>

                    </select>
                </div>

                <!-- Year Filter Dropdown -->
                <div class="col-md-3 mb-3">
                    <select class="form-select" wire:model="searchYear">
                        <option value="">Filter by Year</option>
                        @foreach ($years as $year)
                            <option value="{{ $year }}">{{ $year }}</option>
                        @endforeach
                    </select>
                </div>


            </div>
        </div>

        @foreach ($catlogs->sortby('sort') as $catalog)
             <div class="col-12 col-sm-6 col-md-4 col-xl-3 text-center">
                <a href="{{route('tree.level1',['id'=> $brand->name ,'data'=> $catalog->data ])}}">
                <div class="single-product card border-0 shadow-sm h-100  ">

                    <div class="img-wrapper position-relative">
                        <img class="xproduct-img img-fluid rounded" src="{{ Storage::url($catalog->largeImagePath) }}" alt="product img">
                    </div>
                    <div class="ccontent-wrapper p-3 text-center">
                        <a href="" class="text-decoration-none">
                            <h6 class="product-title text-dark fw-bold text-center">{{ $catalog->shortName }}</h6>
                        </a>
                        <div class="xprice-wrapper mt-2 text-center">
                            <h6 class="text-muted">
                                {{ $catalog->beginYear }}
                                @if ($catalog->endYear != 0)
                                    - {{ $catalog->endYear }}
                                @endif
                            </h6>
                        </div>
                    </div>
                </div>
                </a>
            </div>
        @endforeach
    </div>


    <div class="d-flex justify-content-center my-5">
        {!! $catlogs->links() !!}
    </div>


</div>
