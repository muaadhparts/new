<div>
<div class="ill-alt">
  <!--[if BLOCK]><![endif]--><?php if($alternatives && $alternatives->count() > 0): ?>

    
    <div class="alt-header d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
      <h5 class="mb-0 fw-bold text-primary">
        <i class="fas fa-exchange-alt me-2"></i>
        <?php echo app('translator')->get('Product Alternatives'); ?>
      </h5>
      <span class="badge bg-secondary"><?php echo e($alternatives->count()); ?> <?php echo app('translator')->get('items'); ?></span>
    </div>

    
    <div class="alt-table-desktop d-none d-md-block">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th class="text-nowrap" style="min-width: 120px;"><?php echo app('translator')->get('Part Number'); ?></th>
              <th><?php echo app('translator')->get('Name'); ?></th>
              <th class="text-nowrap"><?php echo app('translator')->get('Brand'); ?></th>
              <th class="text-nowrap"><?php echo app('translator')->get('Quality'); ?></th>
              <th class="text-nowrap"><?php echo app('translator')->get('Vendor'); ?></th>
              <th class="text-center text-nowrap"><?php echo app('translator')->get('Stock'); ?></th>
              <th class="text-center text-nowrap"><?php echo app('translator')->get('Qty'); ?></th>
              <th class="text-end text-nowrap"><?php echo app('translator')->get('Price'); ?></th>
              <th class="text-center" style="width: 100px;"><?php echo app('translator')->get('Action'); ?></th>
            </tr>
          </thead>
          <tbody>
          <!--[if BLOCK]><![endif]--><?php $__currentLoopData = $alternatives; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $idx => $mp): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <?php
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
            ?>

            <tr class="alt-row <?php echo e($highlight ? 'alt-available' : 'alt-unavailable'); ?>">
              
              <td>
                <code class="fw-bold text-dark"><?php echo e($product->sku); ?></code>
              </td>

              
              <td>
                <span class="alt-name"><?php echo e(getLocalizedProductName($product)); ?></span>
              </td>

              
              <td>
                <!--[if BLOCK]><![endif]--><?php if($product->brand): ?>
                  <span class="badge bg-light text-dark border">
                    <?php echo e(Str::ucfirst(getLocalizedBrandName($product->brand))); ?>

                  </span>
                <?php else: ?>
                  <span class="text-muted">-</span>
                <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
              </td>

              
              <td>
                <!--[if BLOCK]><![endif]--><?php if($qualityBrand): ?>
                  <div class="d-flex align-items-center gap-1">
                    <!--[if BLOCK]><![endif]--><?php if($qualityBrand->logo): ?>
                      <img src="<?php echo e($qualityBrand->logo_url); ?>"
                           alt="<?php echo e(getLocalizedQualityName($qualityBrand)); ?>"
                           class="quality-logo"
                           style="max-height: 22px; max-width: 50px; object-fit: contain;">
                    <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
                    <span class="small"><?php echo e(getLocalizedQualityName($qualityBrand)); ?></span>
                  </div>
                <?php else: ?>
                  <span class="text-muted">-</span>
                <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
              </td>

              
              <td>
                <span class="small"><?php echo e($mp->user ? ($mp->user->shop_name ?: $mp->user->name) : '-'); ?></span>
              </td>

              
              <td class="text-center">
                <!--[if BLOCK]><![endif]--><?php if($inStock): ?>
                  <span class="badge bg-success"><?php echo e($mp->stock); ?></span>
                <?php elseif($preordered): ?>
                  <span class="badge bg-warning text-dark"><?php echo app('translator')->get('Preorder'); ?></span>
                <?php else: ?>
                  <span class="badge bg-secondary">0</span>
                <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
              </td>

              
              <td class="text-center">
                <!--[if BLOCK]><![endif]--><?php if($canBuy): ?>
                  <div class="alt-qty-control d-inline-flex align-items-center">
                    <button type="button" class="alt-qty-btn alt-qtminus"
                            data-target="<?php echo e($uniqueId); ?>" data-min="<?php echo e($minQty); ?>">-</button>
                    <input type="text" class="alt-qty-input" id="qty_<?php echo e($uniqueId); ?>"
                           value="<?php echo e($minQty); ?>" readonly
                           data-min="<?php echo e($minQty); ?>" data-stock="<?php echo e($stock); ?>" data-preordered="<?php echo e($preordered); ?>">
                    <button type="button" class="alt-qty-btn alt-qtplus"
                            data-target="<?php echo e($uniqueId); ?>" data-stock="<?php echo e($stock); ?>" data-preordered="<?php echo e($preordered); ?>">+</button>
                  </div>
                <?php else: ?>
                  <span class="text-muted">-</span>
                <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
              </td>

              
              <td class="text-end">
                <span class="fw-bold <?php echo e($hasPrice ? 'text-success' : 'text-muted'); ?>">
                  <?php echo e($hasPrice ? \App\Models\Product::convertPrice($vp) : '-'); ?>

                </span>
              </td>

              
              <td class="text-center">
                <div class="btn-group btn-group-sm">
                  
                  <button type="button"
                          class="btn btn-outline-primary quick-view"
                          data-id="<?php echo e($product->id); ?>"
                          data-url="<?php echo e(route('modal.quickview', ['id' => $product->id])); ?>?user=<?php echo e($mp->user_id); ?>"
                          title="<?php echo app('translator')->get('Quick View'); ?>">
                    <i class="fas fa-eye"></i>
                  </button>

                  
                  <!--[if BLOCK]><![endif]--><?php if($canBuy): ?>
                    <button type="button"
                            class="btn btn-success alt-add-to-cart"
                            data-id="<?php echo e($product->id); ?>"
                            data-mp-id="<?php echo e($mp->id); ?>"
                            data-user="<?php echo e($mp->user_id); ?>"
                            data-qty-id="<?php echo e($uniqueId); ?>"
                            data-addnum-url="<?php echo e(route('merchant.cart.add', $mp->id)); ?>"
                            title="<?php echo app('translator')->get('Add To Cart'); ?>">
                      <i class="fas fa-cart-plus"></i>
                    </button>
                  <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
                </div>
              </td>
            </tr>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><!--[if ENDBLOCK]><![endif]-->
          </tbody>
        </table>
      </div>
    </div>

    
    <div class="alt-cards-mobile d-md-none">
      <!--[if BLOCK]><![endif]--><?php $__currentLoopData = $alternatives; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $idx => $mp): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <?php
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
        ?>

        <div class="alt-card <?php echo e($highlight ? 'alt-available' : 'alt-unavailable'); ?>">
          
          <div class="alt-card-header">
            <code class="fw-bold"><?php echo e($product->sku); ?></code>
            <!--[if BLOCK]><![endif]--><?php if($inStock): ?>
              <span class="badge bg-success"><?php echo e($mp->stock); ?> <?php echo app('translator')->get('In Stock'); ?></span>
            <?php elseif($preordered): ?>
              <span class="badge bg-warning text-dark"><?php echo app('translator')->get('Preorder'); ?></span>
            <?php else: ?>
              <span class="badge bg-secondary"><?php echo app('translator')->get('Out of Stock'); ?></span>
            <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
          </div>

          
          <div class="alt-card-body">
            <div class="alt-card-name"><?php echo e(getLocalizedProductName($product)); ?></div>

            <div class="alt-card-details">
              
              <!--[if BLOCK]><![endif]--><?php if($product->brand): ?>
                <div class="alt-detail">
                  <span class="alt-label"><?php echo app('translator')->get('Brand'); ?>:</span>
                  <span><?php echo e(getLocalizedBrandName($product->brand)); ?></span>
                </div>
              <?php endif; ?><!--[if ENDBLOCK]><![endif]-->

              
              <!--[if BLOCK]><![endif]--><?php if($qualityBrand): ?>
                <div class="alt-detail">
                  <span class="alt-label"><?php echo app('translator')->get('Quality'); ?>:</span>
                  <div class="d-inline-flex align-items-center gap-1">
                    <!--[if BLOCK]><![endif]--><?php if($qualityBrand->logo): ?>
                      <img src="<?php echo e($qualityBrand->logo_url); ?>"
                           alt="<?php echo e(getLocalizedQualityName($qualityBrand)); ?>"
                           class="quality-logo"
                           style="max-height: 18px; max-width: 40px; object-fit: contain;">
                    <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
                    <span><?php echo e(getLocalizedQualityName($qualityBrand)); ?></span>
                  </div>
                </div>
              <?php endif; ?><!--[if ENDBLOCK]><![endif]-->

              
              <!--[if BLOCK]><![endif]--><?php if($mp->user): ?>
                <div class="alt-detail">
                  <span class="alt-label"><?php echo app('translator')->get('Vendor'); ?>:</span>
                  <span><?php echo e($mp->user->shop_name ?: $mp->user->name); ?></span>
                </div>
              <?php endif; ?><!--[if ENDBLOCK]><![endif]-->

              
              <!--[if BLOCK]><![endif]--><?php if($canBuy): ?>
                <div class="alt-detail">
                  <span class="alt-label"><?php echo app('translator')->get('Qty'); ?>:</span>
                  <div class="alt-qty-control d-inline-flex align-items-center">
                    <button type="button" class="alt-qty-btn alt-qtminus"
                            data-target="<?php echo e($uniqueId); ?>" data-min="<?php echo e($minQty); ?>">-</button>
                    <input type="text" class="alt-qty-input" id="qty_<?php echo e($uniqueId); ?>"
                           value="<?php echo e($minQty); ?>" readonly
                           data-min="<?php echo e($minQty); ?>" data-stock="<?php echo e($stock); ?>" data-preordered="<?php echo e($preordered); ?>">
                    <button type="button" class="alt-qty-btn alt-qtplus"
                            data-target="<?php echo e($uniqueId); ?>" data-stock="<?php echo e($stock); ?>" data-preordered="<?php echo e($preordered); ?>">+</button>
                  </div>
                </div>
              <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
            </div>
          </div>

          
          <div class="alt-card-footer">
            <div class="alt-price <?php echo e($hasPrice ? 'text-success' : 'text-muted'); ?>">
              <?php echo e($hasPrice ? \App\Models\Product::convertPrice($vp) : __('Price not available')); ?>

            </div>

            <div class="alt-actions">
              <button type="button"
                      class="btn btn-sm btn-outline-primary quick-view"
                      data-id="<?php echo e($product->id); ?>"
                      data-url="<?php echo e(route('modal.quickview', ['id' => $product->id])); ?>?user=<?php echo e($mp->user_id); ?>">
                <i class="fas fa-eye"></i>
              </button>

              <!--[if BLOCK]><![endif]--><?php if($canBuy): ?>
                <button type="button"
                        class="btn btn-sm btn-success alt-add-to-cart"
                        data-id="<?php echo e($product->id); ?>"
                        data-mp-id="<?php echo e($mp->id); ?>"
                        data-user="<?php echo e($mp->user_id); ?>"
                        data-qty-id="<?php echo e($uniqueId); ?>"
                        data-addnum-url="<?php echo e(route('merchant.cart.add', $mp->id)); ?>">
                  <i class="fas fa-cart-plus"></i> <?php echo app('translator')->get('Add'); ?>
                </button>
              <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
            </div>
          </div>
        </div>
      <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><!--[if ENDBLOCK]><![endif]-->
    </div>

  <?php else: ?>
    <div class="alt-empty text-center py-4">
      <i class="fas fa-box-open fa-3x text-muted mb-3"></i>
      <p class="text-muted mb-0"><?php echo app('translator')->get('No alternatives found'); ?></p>
    </div>
  <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
</div>

<?php if (! $__env->hasRenderedOnce('d3b1c0c9-43e5-4d4f-8936-e5f848179d24')): $__env->markAsRenderedOnce('d3b1c0c9-43e5-4d4f-8936-e5f848179d24'); ?>
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
  // زيادة الكمية
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
          toastr.warning('<?php echo e(__("Stock limit reached")); ?>: ' + stock);
        }
        return;
      }
      input.value = current + 1;
    });
  });

  // إنقاص الكمية
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
          toastr.warning('<?php echo e(__("Minimum quantity is")); ?> ' + minQty);
        }
        return;
      }
      input.value = current - 1;
    });
  });

  // إضافة للسلة مع الكمية
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
            toastr.success(data.success || '<?php echo e(__("Added to cart")); ?>');
          }
        })
        .catch(function(err) {
          if (typeof toastr !== 'undefined') {
            toastr.error('<?php echo e(__("Error adding to cart")); ?>');
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
<?php endif; ?>
</div>
<?php /**PATH C:\Users\hp\Herd\new\resources\views/livewire/alternative.blade.php ENDPATH**/ ?>