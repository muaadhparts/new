<div class="ill-alt p-3">
  @if($alternatives && $alternatives->count() > 0)

    <h5 class="mb-4 border-bottom pb-2 fw-bold text-primary">
      @lang('Product Alternatives'): <span class="text-dark">{{ $sku }}</span>
    </h5>

    <div class="table-responsive">
      <table class="table table-bordered table-striped table-hover align-middle text-center shadow-sm rounded">
        <thead class="table-primary">
          <tr>
            <th>@lang('Part Number')</th>
            <th>@lang('Name')</th>
            <th>@lang('Stock')</th>
            <th>@lang('Price')</th>
            <th>@lang('Quick View')</th>
          </tr>
        </thead>

        <tbody>
        {{-- الآن $alternatives = Collection<MerchantProduct> --}}
        @foreach($alternatives as $mp)
          @php
            /** @var \App\Models\MerchantProduct $mp */
            $product   = $mp->product;
            $vp        = method_exists($mp,'vendorSizePrice') ? (float)$mp->vendorSizePrice() : (float)$mp->price;
            $highlight = ($mp->stock > 0 && $vp > 0);

            $locale = app()->getLocale();
            $name   = $locale === 'ar'
                      ? ($product->label_ar ?: $product->label_en)
                      : ($product->label_en ?: $product->label_ar);
          @endphp

          <tr @if($highlight) style="background-color:#f0fff4" @endif>
            <td class="fw-semibold">{{ $product->sku }}</td>

            <td>{{ e($name) }}</td>

            <td>
              <span class="badge bg-{{ ($mp->stock ?? 0) > 0 ? 'success' : 'secondary' }}">
                {{ $mp->stock ?? 0 }}
              </span>
            </td>

            <td class="fw-bold text-dark">
              {{-- سعر هذا البائع --}}
              {{ method_exists($mp,'showPrice')
                   ? $mp->showPrice()
                   : (\App\Models\Product::convertPrice($vp)) }}
            </td>

            <td>
              {{-- مهم: مرّر user (معرّف البائع) وليس من Product --}}
              {{-- <a href="{{ route('front.product', ['slug' => $product->slug, 'user' => $mp->user_id]) }}"
                 class="btn btn-sm btn-outline-primary quick-view"
                 data-id="{{ $product->id }}"
                 data-sku="{{ $product->sku }}"
                 data-url="{{ route('modal.quickview', ['id' => $product->id]) }}">
                <i class="bi bi-eye"></i> @lang('Quick View') --}}
              <a href="{{ route('front.product', ['slug' => $product->slug, 'user' => $mp->user_id]) }}"
                class="btn btn-sm btn-outline-primary quick-view"
                data-id="{{ $product->id }}"
                data-sku="{{ $product->sku }}"
                data-url="{{ route('modal.quickview', ['id' => $product->id]) }}"
                data-user="{{ $mp->user_id }}">
                <i class="bi bi-eye"></i> @lang('Quick View')
              </a>
            </td>
          </tr>
        @endforeach
        </tbody>
      </table>
    </div>

  @else
    <div class="alert alert-info mb-0">
      <i class="bi bi-info-circle"></i> @lang('No data found')
    </div>
  @endif
</div>

@once
<style>
/* تحسين الجدول للجوال */
@media (max-width: 768px) {
  .ill-alt .table thead {
    display: none;
  }
  .ill-alt .table,
  .ill-alt .table tbody,
  .ill-alt .table tr,
  .ill-alt .table td {
    display: block;
    width: 100%;
  }
  .ill-alt .table tr {
    margin-bottom: 1rem;
    border: 1px solid #ddd;
    border-radius: .5rem;
    box-shadow: 0 2px 5px rgba(0,0,0,.05);
    background: #fff;
    padding: .5rem;
  }
  .ill-alt .table td {
    text-align: right;
    border: none;
    display: flex;
    justify-content: space-between;
    padding: .5rem;
  }
  .ill-alt .table td::before {
    content: attr(data-label);
    font-weight: 600;
    color: #555;
  }
}
</style>
@endonce
