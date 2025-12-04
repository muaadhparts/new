{{-- resources/views/partials/api/alternatives.blade.php --}}
{{-- API-based alternatives partial (No Livewire) --}}

<div class="ill-alt">
  @if($alternatives && $alternatives->count() > 0)

    {{-- Header --}}
    <div class="alt-header d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
      <h5 class="mb-0 fw-bold text-primary">
        <i class="fas fa-exchange-alt me-2"></i>
        @lang('Product Alternatives')
      </h5>
      <span class="badge bg-secondary">{{ $alternatives->count() }} @lang('items')</span>
    </div>

    {{-- Desktop Table --}}
    <div class="alt-table-desktop d-none d-md-block">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th class="text-nowrap" style="min-width: 120px;">@lang('Part Number')</th>
              <th>@lang('Name')</th>
              <th class="text-nowrap">@lang('Brand')</th>
              <th class="text-nowrap">@lang('Quality')</th>
              <th class="text-nowrap">@lang('Vendor')</th>
              <th class="text-center text-nowrap">@lang('Stock')</th>
              <th class="text-center text-nowrap">@lang('Qty')</th>
              <th class="text-end text-nowrap">@lang('Price')</th>
              <th class="text-center" style="width: 100px;">@lang('Action')</th>
            </tr>
          </thead>
          <tbody>
          @foreach($alternatives as $idx => $mp)
            @php
              /** @var \App\Models\MerchantProduct $mp */
              $product = $mp->product;
              $vp = method_exists($mp,'vendorSizePrice') ? (float)$mp->vendorSizePrice() : (float)$mp->price;
              $inStock = ($mp->stock ?? 0) > 0;
              $hasPrice = $vp > 0;
              $highlight = ($inStock || $mp->preordered) && $hasPrice;
              $qualityBrand = $mp->qualityBrand;
              $minQty = (int)($mp->minimum_qty ?? 1);
              if ($minQty < 1) $minQty = 1;
              $stock = (int)($mp->stock ?? 0);
              $preordered = (int)($mp->preordered ?? 0);
              $canBuy = ($inStock || $preordered) && $hasPrice;
              $uniqueId = 'alt_' . $mp->id . '_' . $idx;
            @endphp

            <tr class="alt-row {{ $highlight ? 'alt-available' : 'alt-unavailable' }}">
              {{-- Part Number --}}
              <td>
                <code class="fw-bold text-dark">{{ $product->sku }}</code>
              </td>

              {{-- Name --}}
              <td>
                <span class="alt-name">{{ getLocalizedProductName($product) }}</span>
              </td>

              {{-- Brand --}}
              <td>
                @if($product->brand)
                  <span class="badge bg-light text-dark border">
                    {{ Str::ucfirst(getLocalizedBrandName($product->brand)) }}
                  </span>
                @else
                  <span class="text-muted">-</span>
                @endif
              </td>

              {{-- Quality Brand with Logo --}}
              <td>
                @if($qualityBrand)
                  <div class="d-flex align-items-center gap-1">
                    @if($qualityBrand->logo)
                      <img src="{{ $qualityBrand->logo_url }}"
                           alt="{{ getLocalizedQualityName($qualityBrand) }}"
                           class="quality-logo"
                           style="max-height: 22px; max-width: 50px; object-fit: contain;">
                    @endif
                    <span class="small">{{ getLocalizedQualityName($qualityBrand) }}</span>
                  </div>
                @else
                  <span class="text-muted">-</span>
                @endif
              </td>

              {{-- Vendor --}}
              <td>
                <span class="small">{{ $mp->user ? ($mp->user->shop_name ?: $mp->user->name) : '-' }}</span>
              </td>

              {{-- Stock --}}
              <td class="text-center">
                @if($inStock)
                  <span class="badge bg-success">{{ $mp->stock }}</span>
                @elseif($preordered)
                  <span class="badge bg-warning text-dark">@lang('Preorder')</span>
                @else
                  <span class="badge bg-secondary">0</span>
                @endif
              </td>

              {{-- Quantity Selector --}}
              <td class="text-center">
                @if($canBuy)
                  <div class="alt-qty-control d-inline-flex align-items-center">
                    <button type="button" class="alt-qty-btn alt-qtminus"
                            data-target="{{ $uniqueId }}" data-min="{{ $minQty }}">-</button>
                    <input type="text" class="alt-qty-input" id="qty_{{ $uniqueId }}"
                           value="{{ $minQty }}" readonly
                           data-min="{{ $minQty }}" data-stock="{{ $stock }}" data-preordered="{{ $preordered }}">
                    <button type="button" class="alt-qty-btn alt-qtplus"
                            data-target="{{ $uniqueId }}" data-stock="{{ $stock }}" data-preordered="{{ $preordered }}">+</button>
                  </div>
                @else
                  <span class="text-muted">-</span>
                @endif
              </td>

              {{-- Price --}}
              <td class="text-end">
                <span class="fw-bold {{ $hasPrice ? 'text-success' : 'text-muted' }}">
                  {{ $hasPrice ? \App\Models\Product::convertPrice($vp) : '-' }}
                </span>
              </td>

              {{-- Actions --}}
              <td class="text-center">
                <div class="btn-group btn-group-sm">
                  {{-- Quick View --}}
                  <button type="button"
                          class="btn btn-outline-primary quick-view"
                          data-id="{{ $product->id }}"
                          data-url="{{ route('modal.quickview', ['id' => $product->id]) }}?user={{ $mp->user_id }}"
                          title="@lang('Quick View')">
                    <i class="fas fa-eye"></i>
                  </button>

                  {{-- Add to Cart --}}
                  @if($canBuy)
                    <button type="button"
                            class="btn btn-success alt-add-to-cart"
                            data-id="{{ $product->id }}"
                            data-mp-id="{{ $mp->id }}"
                            data-user="{{ $mp->user_id }}"
                            data-qty-id="{{ $uniqueId }}"
                            data-addnum-url="{{ route('merchant.cart.add', $mp->id) }}"
                            title="@lang('Add To Cart')">
                      <i class="fas fa-cart-plus"></i>
                    </button>
                  @endif
                </div>
              </td>
            </tr>
          @endforeach
          </tbody>
        </table>
      </div>
    </div>

    {{-- Mobile Cards --}}
    <div class="alt-cards-mobile d-md-none">
      @foreach($alternatives as $idx => $mp)
        @php
          $product = $mp->product;
          $vp = method_exists($mp,'vendorSizePrice') ? (float)$mp->vendorSizePrice() : (float)$mp->price;
          $inStock = ($mp->stock ?? 0) > 0;
          $hasPrice = $vp > 0;
          $highlight = ($inStock || $mp->preordered) && $hasPrice;
          $qualityBrand = $mp->qualityBrand;
          $minQty = (int)($mp->minimum_qty ?? 1);
          if ($minQty < 1) $minQty = 1;
          $stock = (int)($mp->stock ?? 0);
          $preordered = (int)($mp->preordered ?? 0);
          $canBuy = ($inStock || $preordered) && $hasPrice;
          $uniqueId = 'altm_' . $mp->id . '_' . $idx;
        @endphp

        <div class="alt-card {{ $highlight ? 'alt-available' : 'alt-unavailable' }}">
          {{-- Card Header --}}
          <div class="alt-card-header">
            <code class="fw-bold">{{ $product->sku }}</code>
            @if($inStock)
              <span class="badge bg-success">{{ $mp->stock }} @lang('In Stock')</span>
            @elseif($preordered)
              <span class="badge bg-warning text-dark">@lang('Preorder')</span>
            @else
              <span class="badge bg-secondary">@lang('Out of Stock')</span>
            @endif
          </div>

          {{-- Card Body --}}
          <div class="alt-card-body">
            <div class="alt-card-name">{{ getLocalizedProductName($product) }}</div>

            <div class="alt-card-details">
              {{-- Brand --}}
              @if($product->brand)
                <div class="alt-detail">
                  <span class="alt-label">@lang('Brand'):</span>
                  <span>{{ getLocalizedBrandName($product->brand) }}</span>
                </div>
              @endif

              {{-- Quality with Logo --}}
              @if($qualityBrand)
                <div class="alt-detail">
                  <span class="alt-label">@lang('Quality'):</span>
                  <div class="d-inline-flex align-items-center gap-1">
                    @if($qualityBrand->logo)
                      <img src="{{ $qualityBrand->logo_url }}"
                           alt="{{ getLocalizedQualityName($qualityBrand) }}"
                           class="quality-logo"
                           style="max-height: 18px; max-width: 40px; object-fit: contain;">
                    @endif
                    <span>{{ getLocalizedQualityName($qualityBrand) }}</span>
                  </div>
                </div>
              @endif

              {{-- Vendor --}}
              @if($mp->user)
                <div class="alt-detail">
                  <span class="alt-label">@lang('Vendor'):</span>
                  <span>{{ $mp->user->shop_name ?: $mp->user->name }}</span>
                </div>
              @endif

              {{-- Quantity --}}
              @if($canBuy)
                <div class="alt-detail">
                  <span class="alt-label">@lang('Qty'):</span>
                  <div class="alt-qty-control d-inline-flex align-items-center">
                    <button type="button" class="alt-qty-btn alt-qtminus"
                            data-target="{{ $uniqueId }}" data-min="{{ $minQty }}">-</button>
                    <input type="text" class="alt-qty-input" id="qty_{{ $uniqueId }}"
                           value="{{ $minQty }}" readonly
                           data-min="{{ $minQty }}" data-stock="{{ $stock }}" data-preordered="{{ $preordered }}">
                    <button type="button" class="alt-qty-btn alt-qtplus"
                            data-target="{{ $uniqueId }}" data-stock="{{ $stock }}" data-preordered="{{ $preordered }}">+</button>
                  </div>
                </div>
              @endif
            </div>
          </div>

          {{-- Card Footer --}}
          <div class="alt-card-footer">
            <div class="alt-price {{ $hasPrice ? 'text-success' : 'text-muted' }}">
              {{ $hasPrice ? \App\Models\Product::convertPrice($vp) : __('Price not available') }}
            </div>

            <div class="alt-actions">
              <button type="button"
                      class="btn btn-sm btn-outline-primary quick-view"
                      data-id="{{ $product->id }}"
                      data-url="{{ route('modal.quickview', ['id' => $product->id]) }}?user={{ $mp->user_id }}">
                <i class="fas fa-eye"></i>
              </button>

              @if($canBuy)
                <button type="button"
                        class="btn btn-sm btn-success alt-add-to-cart"
                        data-id="{{ $product->id }}"
                        data-mp-id="{{ $mp->id }}"
                        data-user="{{ $mp->user_id }}"
                        data-qty-id="{{ $uniqueId }}"
                        data-addnum-url="{{ route('merchant.cart.add', $mp->id) }}">
                  <i class="fas fa-cart-plus"></i> @lang('Add')
                </button>
              @endif
            </div>
          </div>
        </div>
      @endforeach
    </div>

  @else
    <div class="alt-empty text-center py-4">
      <i class="fas fa-box-open fa-3x text-muted mb-3"></i>
      <p class="text-muted mb-0">@lang('No alternatives found')</p>
    </div>
  @endif
</div>

<style>
/* ========== Alternative Modal Styles ========== */
.ill-alt {
  max-height: 70vh;
  overflow-y: auto;
}

/* Desktop Table */
.alt-table-desktop .table {
  font-size: 0.9rem;
}

.alt-table-desktop .table th {
  font-weight: 600;
  font-size: 0.8rem;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  color: #555;
  border-bottom: 2px solid #dee2e6;
}

.alt-row {
  transition: background-color 0.15s ease;
}

.alt-row:hover {
  background-color: #f8f9fa;
}

.alt-row.alt-available {
  background-color: #f0fff4;
}

.alt-row.alt-available:hover {
  background-color: #e6ffed;
}

.alt-row.alt-unavailable {
  opacity: 0.7;
}

.alt-name {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
  font-size: 0.85rem;
}

.quality-logo {
  border-radius: 2px;
}

/* Quantity Control */
.alt-qty-control {
  border: 1px solid #ddd;
  border-radius: 4px;
  overflow: hidden;
}

.alt-qty-btn {
  width: 26px;
  height: 26px;
  border: none;
  background: #f5f5f5;
  cursor: pointer;
  font-weight: bold;
  font-size: 14px;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: background 0.2s;
}

.alt-qty-btn:hover {
  background: #e0e0e0;
}

.alt-qty-input {
  width: 36px;
  height: 26px;
  text-align: center;
  border: none;
  font-weight: 600;
  font-size: 13px;
  background: #fff;
}

/* Mobile Cards */
.alt-card {
  background: #fff;
  border: 1px solid #e0e0e0;
  border-radius: 8px;
  margin-bottom: 12px;
  overflow: hidden;
  transition: box-shadow 0.15s ease;
}

.alt-card:active {
  box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.alt-card.alt-available {
  border-color: #28a745;
  border-width: 1px 1px 1px 4px;
}

.alt-card.alt-unavailable {
  opacity: 0.8;
  background: #f9f9f9;
}

.alt-card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 10px 12px;
  background: #f8f9fa;
  border-bottom: 1px solid #e0e0e0;
}

.alt-card-body {
  padding: 12px;
}

.alt-card-name {
  font-weight: 600;
  font-size: 0.95rem;
  margin-bottom: 8px;
  color: #333;
}

.alt-card-details {
  font-size: 0.85rem;
}

.alt-detail {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 4px 0;
  border-bottom: 1px dashed #eee;
}

.alt-detail:last-child {
  border-bottom: none;
}

.alt-label {
  color: #666;
  font-weight: 500;
}

.alt-card-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 10px 12px;
  background: #f8f9fa;
  border-top: 1px solid #e0e0e0;
}

.alt-price {
  font-weight: 700;
  font-size: 1.1rem;
}

.alt-actions {
  display: flex;
  gap: 6px;
}

/* Empty State */
.alt-empty {
  color: #999;
}

/* RTL Support */
[dir="rtl"] .alt-card.alt-available {
  border-width: 1px 4px 1px 1px;
}

[dir="rtl"] .alt-detail {
  flex-direction: row-reverse;
}

/* Scrollbar */
.ill-alt::-webkit-scrollbar {
  width: 6px;
}

.ill-alt::-webkit-scrollbar-track {
  background: #f1f1f1;
  border-radius: 3px;
}

.ill-alt::-webkit-scrollbar-thumb {
  background: #c1c1c1;
  border-radius: 3px;
}

.ill-alt::-webkit-scrollbar-thumb:hover {
  background: #a1a1a1;
}
</style>

<script>
(function() {
  // Quantity Plus
  document.querySelectorAll('.alt-qtplus').forEach(function(btn) {
    btn.addEventListener('click', function(e) {
      e.preventDefault();
      e.stopPropagation();
      var targetId = this.dataset.target;
      var input = document.getElementById('qty_' + targetId);
      if (!input) return;

      var stock = parseInt(this.dataset.stock) || 999;
      var preordered = parseInt(this.dataset.preordered) || 0;
      var current = parseInt(input.value) || 1;

      if (stock > 0 && current >= stock && preordered == 0) {
        if (typeof toastr !== 'undefined') {
          toastr.warning('{{ __("Stock limit reached") }}: ' + stock);
        }
        return;
      }
      input.value = current + 1;
    });
  });

  // Quantity Minus
  document.querySelectorAll('.alt-qtminus').forEach(function(btn) {
    btn.addEventListener('click', function(e) {
      e.preventDefault();
      e.stopPropagation();
      var targetId = this.dataset.target;
      var input = document.getElementById('qty_' + targetId);
      if (!input) return;

      var minQty = parseInt(this.dataset.min) || 1;
      var current = parseInt(input.value) || 1;

      if (current <= minQty) {
        if (typeof toastr !== 'undefined') {
          toastr.warning('{{ __("Minimum quantity is") }} ' + minQty);
        }
        return;
      }
      input.value = current - 1;
    });
  });

  // Add to Cart
  document.querySelectorAll('.alt-add-to-cart').forEach(function(btn) {
    btn.addEventListener('click', function(e) {
      e.preventDefault();
      e.stopPropagation();

      var qtyId = this.dataset.qtyId;
      var input = document.getElementById('qty_' + qtyId);
      var qty = input ? parseInt(input.value) || 1 : 1;

      var addUrl = this.dataset.addnumUrl;
      var user = this.dataset.user;

      var url = addUrl + '?qty=' + qty;
      if (user) url += '&user=' + user;

      var button = this;
      button.disabled = true;

      fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(function(r) {
          return r.ok ? r.json() : Promise.reject(new Error('HTTP ' + r.status));
        })
        .then(function(data) {
          if (typeof window.applyCartState === 'function') {
            window.applyCartState(data);
          }
          if (typeof toastr !== 'undefined') {
            toastr.success(data.success || '{{ __("Added to cart") }}');
          }
        })
        .catch(function(err) {
          if (typeof toastr !== 'undefined') {
            toastr.error('{{ __("Error adding to cart") }}');
          }
          console.error(err);
        })
        .finally(function() {
          button.disabled = false;
        });
    });
  });
})();
</script>
