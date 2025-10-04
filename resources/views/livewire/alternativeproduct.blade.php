<div>
    @if($alternatives && $alternatives->count() > 0)

        @if(!isset($displayMode) || $displayMode !== 'inline')
            {{-- Button Mode (Default) --}}
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
                            <h5 class="modal-title fw-bold" id="alternativeModalLabel">@lang('Product Alternatives'): {{ $sku }}</h5>
                            <button type="button" class="btn btn-light rounded-circle shadow-sm"
                                    style="width: 35px; height: 35px; display: flex; align-items: center; justify-content: center;"
                                    data-bs-dismiss="modal" aria-label="Close">
                                <i class="fas fa-times text-danger"></i>
                            </button>
                        </div>
                        <div class="modal-body">
        @endif

                        @php
                            // نوع العناصر: MerchantProduct أو Product
                            $first   = $alternatives->first();
                            $isMpSet = $first instanceof \App\Models\MerchantProduct;

                            // // dd(['alts' => $alternatives->count(), 'isMpSet' => $isMpSet]); // اختباري
                        @endphp

                        <!-- جدول (ديسكتوب) -->
                        <div class="container d-none d-md-block">
                            <table class="table table-bordered text-center align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>@lang('Part Number')</th>
                                        <th>@lang('Name')</th>
                                        <th>@lang('Brand:')</th>
                                        <th>@lang('Brand qualities:')</th>
                                        <th>@lang('Stock')</th>
                                        <th>@lang('Price')</th>
                                        <th>@lang('View')</th>
                                    </tr>
                                </thead>
                                <tbody>

                                    @if($isMpSet)
                                        {{-- Collection<MerchantProduct> --}}
                                        @forelse($alternatives as $mp)
                                            @php
                                                $product   = $mp->product;
                                                $vp        = method_exists($mp, 'vendorSizePrice') ? (float)$mp->vendorSizePrice() : (float)$mp->price;
                                                $highlight = ($mp->stock > 0 && $vp > 0);
                                                $locale    = app()->getLocale();
                                                $name      = $locale === 'ar'
                                                             ? ($product->label_ar ?: $product->label_en)
                                                             : ($product->label_en ?: $product->label_ar);
                                            @endphp
                                            <tr @if($highlight) style="background-color:#f0fff4;" @endif>
                                                <td>{{ $product->sku }}</td>
                                                <td>{{ e($name) }}</td>
                                                <td>{{ $product->brand ? Str::ucfirst($product->brand->name) : '-' }}</td>
                                                <td>{{ $mp->qualityBrand ? (app()->getLocale() == 'ar' && $mp->qualityBrand->name_ar ? $mp->qualityBrand->name_ar : $mp->qualityBrand->name_en) : '-' }}</td>
                                                <td>{{ (int)($mp->stock ?? 0) }}</td>
                                                <td class="fw-bold {{ $highlight ? 'text-success' : '' }}">
                                                    {{ method_exists($mp, 'showPrice') ? $mp->showPrice() : \App\Models\Product::convertPrice($vp) }}
                                                </td>
                                                <td>
                                                    <a class="btn btn-outline-primary btn-sm"
                                                       href="{{ route('front.product', ['slug' => $product->slug, 'vendor_id' => $mp->user_id, 'merchant_product_id' => $mp->id]) }}">
                                                        @lang('View')
                                                    </a>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="7" class="text-center">@lang('No data found')</td>
                                            </tr>
                                        @endforelse

                                    @else
                                        {{-- Collection<Product> --}}
                                        @foreach($alternatives as $product)
                                            @php
                                                $merchants = $product->merchantProducts()
                                                    ->where('status', 1)
                                                    ->with('user:id,is_vendor')
                                                    ->get();
                                            @endphp

                                            @forelse($merchants as $mp)
                                                @php
                                                    $vp        = method_exists($mp, 'vendorSizePrice') ? (float)$mp->vendorSizePrice() : (float)$mp->price;
                                                    $highlight = ($mp->stock > 0 && $vp > 0);
                                                    $locale    = app()->getLocale();
                                                    $name      = $locale === 'ar'
                                                                 ? ($product->label_ar ?: $product->label_en)
                                                                 : ($product->label_en ?: $product->label_ar);
                                                @endphp
                                                <tr @if($highlight) style="background-color:#f0fff4;" @endif>
                                                    <td>{{ $product->sku }}</td>
                                                    <td>{{ e($name) }}</td>
                                                    <td>{{ $product->brand ? Str::ucfirst($product->brand->name) : '-' }}</td>
                                                    <td>{{ $mp->qualityBrand ? (app()->getLocale() == 'ar' && $mp->qualityBrand->name_ar ? $mp->qualityBrand->name_ar : $mp->qualityBrand->name_en) : '-' }}</td>
                                                    <td>{{ (int)($mp->stock ?? 0) }}</td>
                                                    <td class="fw-bold {{ $highlight ? 'text-success' : '' }}">
                                                        {{ method_exists($mp, 'showPrice') ? $mp->showPrice() : \App\Models\Product::convertPrice($vp) }}
                                                    </td>
                                                    <td>
                                                        <a class="btn btn-outline-primary btn-sm"
                                                           href="{{ route('front.product', ['slug' => $product->slug, 'vendor_id' => $mp->user_id, 'merchant_product_id' => $mp->id]) }}">
                                                            @lang('View')
                                                        </a>
                                                    </td>
                                                </tr>
                                            @empty
                                                {{-- لا يوجد بائعون لهذا البديل --}}
                                            @endforelse
                                        @endforeach

                                        @if(
                                            $alternatives->every(function($p){
                                                return $p->merchantProducts()->where('status',1)->count() === 0;
                                            })
                                        )
                                            <tr>
                                                <td colspan="7" class="text-center">@lang('No data found')</td>
                                            </tr>
                                        @endif
                                    @endif

                                </tbody>
                            </table>
                        </div>

                        <!-- كروت (موبايل) -->
                        <div class="container d-block d-md-none">
                            <div class="row g-3">

                                @if($isMpSet)
                                    {{-- Collection<MerchantProduct> --}}
                                    @forelse($alternatives as $mp)
                                        @php
                                            $product   = $mp->product;
                                            $vp        = method_exists($mp, 'vendorSizePrice') ? (float)$mp->vendorSizePrice() : (float)$mp->price;
                                            $highlight = ($mp->stock > 0 && $vp > 0);
                                            $locale    = app()->getLocale();
                                            $name      = $locale === 'ar'
                                                         ? ($product->label_ar ?: $product->label_en)
                                                         : ($product->label_en ?: $product->label_ar);
                                        @endphp
                                        <div class="col-12">
                                            <div class="card shadow-sm h-100 @if($highlight) border-success @endif">
                                                <div class="row g-0">
                                                    <div class="col-4">
                                                        <img src="{{ $product->photo ? \Illuminate\Support\Facades\Storage::url($product->photo) : asset('assets/images/noimage.png') }}"
                                                             class="img-fluid rounded-start" alt="{{ $product->sku }}">
                                                    </div>
                                                    <div class="col-8">
                                                        <div class="card-body p-2">
                                                            <h6 class="card-title mb-1">{{ e($name) }}</h6>
                                                            <p class="mb-1 small text-muted"><strong>@lang('Part Number'):</strong> {{ $product->sku }}</p>
                                                            @if($product->brand)
                                                                <p class="mb-1 small"><strong>@lang('Brand:'):</strong> {{ Str::ucfirst($product->brand->name) }}</p>
                                                            @endif
                                                            @if($mp->qualityBrand)
                                                                <p class="mb-1 small"><strong>@lang('Brand qualities:'):</strong> {{ app()->getLocale() == 'ar' && $mp->qualityBrand->name_ar ? $mp->qualityBrand->name_ar : $mp->qualityBrand->name_en }}</p>
                                                            @endif
                                                            <p class="mb-1 fw-bold {{ $highlight ? 'text-success' : '' }}">
                                                                {{ method_exists($mp, 'showPrice') ? $mp->showPrice() : \App\Models\Product::convertPrice($vp) }}
                                                            </p>
                                                            <p class="mb-2 small"><strong>@lang('Stock'):</strong> {{ (int)($mp->stock ?? 0) }}</p>
                                                            <a href="{{ route('front.product', ['slug' => $product->slug, 'vendor_id' => $mp->user_id, 'merchant_product_id' => $mp->id]) }}"
                                                               class="btn btn-primary btn-sm w-100">
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

                                @else
                                    {{-- Collection<Product> --}}
                                    @foreach($alternatives as $product)
                                        @php
                                            $merchants = $product->merchantProducts()
                                                ->where('status', 1)
                                                ->with('user:id,is_vendor')
                                                ->get();
                                        @endphp

                                        @forelse($merchants as $mp)
                                            @php
                                                $vp        = method_exists($mp, 'vendorSizePrice') ? (float)$mp->vendorSizePrice() : (float)$mp->price;
                                                $highlight = ($mp->stock > 0 && $vp > 0);
                                                $locale    = app()->getLocale();
                                                $name      = $locale === 'ar'
                                                             ? ($product->label_ar ?: $product->label_en)
                                                             : ($product->label_en ?: $product->label_ar);
                                            @endphp
                                            <div class="col-12">
                                                <div class="card shadow-sm h-100 @if($highlight) border-success @endif">
                                                    <div class="row g-0">
                                                        <div class="col-4">
                                                            <img src="{{ $product->photo ? \Illuminate\Support\Facades\Storage::url($product->photo) : asset('assets/images/noimage.png') }}"
                                                                 class="img-fluid rounded-start" alt="{{ $product->sku }}">
                                                        </div>
                                                        <div class="col-8">
                                                            <div class="card-body p-2">
                                                                <h6 class="card-title mb-1">{{ e($name) }}</h6>
                                                                <p class="mb-1 small text-muted"><strong>@lang('Part Number'):</strong> {{ $product->sku }}</p>
                                                                @if($product->brand)
                                                                    <p class="mb-1 small"><strong>@lang('Brand:'):</strong> {{ Str::ucfirst($product->brand->name) }}</p>
                                                                @endif
                                                                @if($mp->qualityBrand)
                                                                    <p class="mb-1 small"><strong>@lang('Brand qualities:'):</strong> {{ app()->getLocale() == 'ar' && $mp->qualityBrand->name_ar ? $mp->qualityBrand->name_ar : $mp->qualityBrand->name_en }}</p>
                                                                @endif
                                                                <p class="mb-1 fw-bold {{ $highlight ? 'text-success' : '' }}">
                                                                    {{ method_exists($mp, 'showPrice') ? $mp->showPrice() : \App\Models\Product::convertPrice($vp) }}
                                                                </p>
                                                                <p class="mb-2 small"><strong>@lang('Stock'):</strong> {{ (int)($mp->stock ?? 0) }}</p>
                                                                <a href="{{ route('front.product', ['slug' => $product->slug, 'vendor_id' => $mp->user_id, 'merchant_product_id' => $mp->id]) }}"
                                                                   class="btn btn-primary btn-sm w-100">
                                                                    @lang('View')
                                                                </a>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @empty
                                            {{-- لا يوجد بائعون لهذا المنتج --}}
                                        @endforelse
                                    @endforeach

                                    @if(
                                        $alternatives->every(function($p){
                                            return $p->merchantProducts()->where('status',1)->count() === 0;
                                        })
                                    )
                                        <p class="text-center">@lang('No data found')</p>
                                    @endif

                                @endif

                            </div>
                        </div>

        @if(!isset($displayMode) || $displayMode !== 'inline')
                        </div>
                    </div>
                </div>
            </div>
        @endif

    @else
        {{-- No Alternatives Found (Only for inline mode) --}}
        @if(isset($displayMode) && $displayMode === 'inline')
            <div class="alert alert-info text-center" role="alert">
                <i class="fas fa-info-circle me-2"></i>
                @lang('No alternative products found for this item.')
            </div>
        @endif
    @endif
</div>
