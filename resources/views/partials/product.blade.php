{{-- resources/views/partials/product.blade.php --}}

@php
    // صورة أساسية
    $mainPhoto = filter_var($product->photo ?? '', FILTER_VALIDATE_URL)
        ? $product->photo
        : (isset($product->photo) ? \Illuminate\Support\Facades\Storage::url($product->photo) : '');

    // حماية في حال عدم تمرير $gs
    $gs = $gs ?? (isset($__data['gs']) ? $__data['gs'] : null);
@endphp

<div class="row gy-4 ill-product" data-product-id="{{ $product->id }}">
    <div class="col-12 col-lg-6">
        {{-- main image --}}
        @if($mainPhoto)
            <img src="{{ $mainPhoto }}" alt="{{ $product->name ?? $product->sku }}" class="img-fluid rounded w-100 mb-3">
        @endif

        {{-- gallery --}}
        @if(!empty($product->galleries))
            <div class="d-flex flex-wrap gap-2">
                @foreach($product->galleries as $gallery)
                    @php
                        $url = filter_var($gallery->photo ?? '', FILTER_VALIDATE_URL)
                            ? $gallery->photo
                            : asset('assets/images/galleries/'.$gallery->photo);
                    @endphp
                    <img src="{{ $url }}" alt="{{ $product->name ?? '' }}" class="img-fluid rounded" style="width:75px;height:75px;object-fit:cover;">
                @endforeach
            </div>
        @endif
    </div>

    <div class="col-12 col-lg-6">
        <h3 class="fw-bold mb-2">{{ $product->name ?? $product->title ?? $product->slug ?? $product->sku }}</h3>
        @if(!empty($product->label_ar))
            <h5 class="text-muted mb-3">{{ $product->label_ar }}</h5>
        @endif

        {{-- rating (اختياري) --}}
        @php
            $avg = $product->ratings_avg_rating ?? null;
            $count = class_exists('App\\Models\\Rating') && method_exists('App\\Models\\Rating', 'ratingCount') ? \App\Models\Rating::ratingCount($product->id) : null;
        @endphp
        @if(!empty($avg))
            <div class="mb-2">
                <span class="me-1 fw-semibold">{{ number_format($avg, 1) }}</span>
                @for($i = 1; $i <= 5; $i++)
                    <i class="fa{{ $i <= round($avg) ? 's' : 'r' }} fa-star text-warning"></i>
                @endfor
                @if($count !== null)
                    <span class="ms-1 text-muted">({{ $count }} @lang('Reviews'))</span>
                @endif
            </div>
        @endif

        {{-- price --}}
        <div class="mb-3">
            <span class="h4 text-primary">
                {{ method_exists($product, 'showPrice') ? $product->showPrice() : ($product->price ?? '-') }}
            </span>
            @if(method_exists($product, 'showPreviousPrice') && $product->showPreviousPrice())
                <del class="text-muted ms-2">{{ $product->showPreviousPrice() }}</del>
            @endif
        </div>

        {{-- SKU --}}
        @if(!empty($product->sku))
            <div class="mb-2">
                <strong></strong> <span>{{ $product->sku }}</span>
            </div>
        @endif

        {{-- stock --}}
        @if(($product->type ?? '') === 'Physical')
            <div class="mb-2">
                <strong> </strong>
                @if(method_exists($product, 'emptyStock') ? $product->emptyStock() : false)
                    <span class="text-danger">@lang('Out Of Stock')</span>
                @else
                    @php $showStock = is_object($gs) && property_exists($gs,'show_stock') ? $gs->show_stock : null; @endphp
                    <span class="text-success">
                        {{ ($showStock === 0 || $showStock === '0') ? '' : ($product->stock ?? '') }} @lang('In Stock')
                    </span>
                @endif
            </div>
        @endif

        {{-- quantity --}}
        <div class="d-flex align-items-center mb-3">
            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="var q=this.parentNode.querySelector('input.ill-qty'); if(parseInt(q.value)>1) q.value=parseInt(q.value)-1;">−</button>
            <input type="number" name="quantity" value="1" min="1" class="form-control mx-2 text-center ill-qty" style="max-width:80px;">
            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="var q=this.parentNode.querySelector('input.ill-qty'); q.value=parseInt(q.value||1)+1;">+</button>
        </div>

        {{-- actions --}}
        <div class="d-flex gap-2">
            {{-- AJAX: يضيف ويُبقي داخل المودال --}}
            <button
                type="button"
                class="btn btn-primary ill-add-to-cart"
                data-id="{{ $product->id }}"
                data-addnum-url="{{ url('/addnumcart') }}"
            >@lang('Add To Cart')</button>

            {{-- BUY NOW: يضيف ثم يوجّه للسلة (POST) --}}
        <button
        type="button"
        class="btn btn-success ill-buy-now"
        data-id="{{ $product->id }}"
        data-addtonum-url="{{ url('/addtonumcart') }}"
        data-carts-url="{{ url('/carts') }}"
        >@lang('Purchase Now')</button>

        </div>
    </div>
</div>
