<div>
    @if ($sku)
        <h5>نتائج البحث لرقم القطعة: {{ $sku }}</h5>

        @if ($prods->count())
            <h6 class="mt-3">المنتجات:</h6>
            <div class="row">
                @foreach ($prods as $product)
                    @include('includes.frontend.list_view_product')
                @endforeach
            </div>
        @else
            <div class="alert alert-warning mt-3">لا توجد منتجات بهذا الرقم</div>
        @endif

        @if ($alternatives && $alternatives->count())
            <h6 class="mt-4">البدائل:</h6>
            <div class="row">
                @foreach ($alternatives as $product)
                    @include('includes.frontend.list_view_product')
                @endforeach
            </div>
        @endif
    @else
        <div class="text-muted">لم يتم تحديد رقم القطعة.</div>
    @endif
</div>

