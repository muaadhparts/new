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
        @foreach($alternatives as $p)
          <tr>
            <td class="fw-semibold">{{ $p->sku }}</td>

            <td>
              @if(app()->getLocale() === 'ar')
                  {{ $p->label_ar ?? $p->label_en }}
              @else
                  {{ $p->label_en ?? $p->label_ar }}
              @endif
            </td>

            <td>
              <span class="badge bg-{{ ($p->stock ?? 0) > 0 ? 'success' : 'secondary' }}">
                {{ $p->stock ?? 0 }}
              </span>
            </td>

            <td class="fw-bold text-dark">
              {{ method_exists($p,'showPrice')
                    ? $p->showPrice()
                    : (\App\Models\Product::convertPrice($p->price ?? 0)) }}
            </td>

            <td>
              <a href="{{ route('front.product', $p->slug) }}"
                 class="btn btn-sm btn-outline-primary quick-view"
                 data-id="{{ $p->id }}"
                 data-sku="{{ $p->sku }}"
                 data-url="{{ route('modal.quickview', ['id' => $p->id]) }}">
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
    display: none; /* نخفي رأس الجدول */
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
