{{-- resources/views/partials/product.blade.php --}}

@php
    /**
     * اختيار البائع لعمليات السلة/العرض في المودال:
     * - أولًا من ?user= في الاستعلام
     * - أو من product->vendor_user_id (إذا حقنه الكنترولر)
     * - أو من product->user_id كـ fallback أخير
     */
    $vendorId = (int) (request()->get('user') ?? ($product->vendor_user_id ?? $product->user_id ?? 0));

    // صورة أساسية
    $mainPhoto = filter_var($product->photo ?? '', FILTER_VALIDATE_URL)
        ? $product->photo
        : (isset($product->photo) ? \Illuminate\Support\Facades\Storage::url($product->photo) : '');

    // حماية في حال عدم تمرير $gs من الـ View الأب
    $gs = $gs ?? (isset($__data['gs']) ? $__data['gs'] : null);

    // دوال عرض السعر قد تعتمد على خصائص المنتج المحقونة (price/previous_price)
    $priceHtml = method_exists($product, 'showPrice') ? $product->showPrice() : (\App\Models\Product::convertPrice($product->price ?? 0));
    $prevHtml  = (method_exists($product, 'showPreviousPrice') && $product->showPreviousPrice()) ? $product->showPreviousPrice() : null;

    // تقييمات (اختياري)
    $avg   = $product->ratings_avg_rating ?? null;
    $count = class_exists('App\\Models\\Rating') && method_exists('App\\Models\\Rating', 'ratingCount')
        ? \App\Models\Rating::ratingCount($product->id)
        : null;

    // اسم عربي/إنجليزي اختياري
    $locale = app()->getLocale();
    $secondaryLabel = $locale === 'ar'
        ? ($product->label_ar ?: $product->label_en)
        : ($product->label_en ?: $product->label_ar);
@endphp

<div class="row gy-4 ill-product" data-product-id="{{ $product->id }}" data-user="{{ $vendorId }}">
    <div class="col-12 col-lg-6">
        {{-- Main image --}}
        @if($mainPhoto)
            <img src="{{ $mainPhoto }}" alt="{{ $product->name ?? $product->sku }}" class="img-fluid rounded w-100 mb-3">
        @endif

        {{-- Gallery --}}
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
        {{-- عنوان المنتج --}}
        <h3 class="fw-bold mb-2">
            {{ $product->name ?? $product->title ?? $product->slug ?? $product->sku }}
        </h3>

        {{-- تسمية ثانوية (اختياري) --}}
        @if(!empty($secondaryLabel))
            <h5 class="text-muted mb-3">{{ $secondaryLabel }}</h5>
        @endif

        {{-- التقييم (اختياري) --}}
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

        {{-- السعر --}}
        <div class="mb-3">
            <span class="h4 text-primary">{!! $priceHtml !!}</span>
            @if($prevHtml)
                <del class="text-muted ms-2">{!! $prevHtml !!}</del>
            @endif
        </div>

        {{-- SKU --}}
        @if(!empty($product->sku))
            <div class="mb-2">
                <strong>@lang('SKU'):</strong> <span>{{ $product->sku }}</span>
            </div>
        @endif

        {{-- المخزون (للمنتجات الفيزيائية) --}}
        @if(($product->type ?? '') === 'Physical')
            <div class="mb-2">
                <strong>@lang('Stock'):</strong>
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

        {{-- الكمية --}}
        <div class="d-flex align-items-center mb-3">
            <button type="button" class="btn btn-outline-secondary btn-sm"
                    onclick="var q=this.parentNode.querySelector('input.ill-qty'); if(parseInt(q.value)>1) q.value=parseInt(q.value)-1;">−</button>
            <input type="number" name="quantity" value="1" min="1" class="form-control mx-2 text-center ill-qty" style="max-width:80px;">
            <button type="button" class="btn btn-outline-secondary btn-sm"
                    onclick="var q=this.parentNode.querySelector('input.ill-qty'); q.value=parseInt(q.value||1)+1;">+</button>
        </div>

        {{-- الأزرار --}}
        <div class="d-flex gap-2">
            {{-- إضافة للسلة (AJAX، يبقى داخل المودال) --}}
            <button
                type="button"
                class="btn btn-primary ill-add-to-cart"
                data-id="{{ $product->id }}"
                data-user="{{ $vendorId }}"
                data-addnum-url="{{ url('/addnumcart') }}"
            >@lang('Add To Cart')</button>

            {{-- شراء الآن: يضيف ثم يوجّه للسلة --}}
            <button
                type="button"
                class="btn btn-success ill-buy-now"
                data-id="{{ $product->id }}"
                data-user="{{ $vendorId }}"
                data-addtonum-url="{{ url('/addtonumcart') }}"
                data-carts-url="{{ url('/carts') }}"
            >@lang('Purchase Now')</button>
        </div>
    </div>
</div>
