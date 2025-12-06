


<div class="modal-content-wrapper ill-alt">
    <?php if($alternatives && $alternatives->count() > 0): ?>
        
        <div class="modal-section-header">
            <h5 class="mb-0 fw-bold text-primary">
                <i class="fas fa-exchange-alt me-2"></i>
                <?php echo app('translator')->get('labels.substitutions'); ?>
            </h5>
            <span class="badge bg-secondary"><?php echo e($alternatives->count()); ?> <?php echo app('translator')->get('items'); ?></span>
        </div>

        
        <div class="d-none d-md-block">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 modal-table">
                    <thead class="table-light">
                        <tr>
                            <th class="text-nowrap"><?php echo app('translator')->get('Part Number'); ?></th>
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
                        <?php $__currentLoopData = $alternatives; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $idx => $mp): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
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
                                $uniqueId = 'alt_' . $mp->id . '_' . $idx;
                            ?>

                            <tr class="<?php echo e($highlight ? 'table-row-available' : 'table-row-unavailable'); ?>">
                                <td><code class="fw-bold text-dark"><?php echo e($product->sku); ?></code></td>
                                <td class="text-truncate" style="max-width: 200px;"><?php echo e(getLocalizedProductName($product)); ?></td>
                                <td>
                                    <?php if($product->brand): ?>
                                        <span class="badge bg-light text-dark border"><?php echo e(Str::ucfirst(getLocalizedBrandName($product->brand))); ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if($qualityBrand): ?>
                                        <div class="d-flex align-items-center gap-1">
                                            <?php if($qualityBrand->logo): ?>
                                                <img src="<?php echo e($qualityBrand->logo_url); ?>" alt="<?php echo e(getLocalizedQualityName($qualityBrand)); ?>" class="quality-logo" style="max-height: 22px; max-width: 50px; object-fit: contain;">
                                            <?php endif; ?>
                                            <span class="small"><?php echo e(getLocalizedQualityName($qualityBrand)); ?></span>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td><span class="small"><?php echo e($mp->user ? ($mp->user->shop_name ?: $mp->user->name) : '-'); ?></span></td>
                                <td class="text-center">
                                    <?php if($inStock): ?>
                                        <span class="badge bg-success"><?php echo e($mp->stock); ?></span>
                                    <?php elseif($preordered): ?>
                                        <span class="badge bg-warning text-dark"><?php echo app('translator')->get('Preorder'); ?></span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">0</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php if($canBuy): ?>
                                        <div class="qty-control d-inline-flex align-items-center">
                                            <button type="button" class="qty-btn qty-minus" data-target="<?php echo e($uniqueId); ?>" data-min="<?php echo e($minQty); ?>">-</button>
                                            <input type="text" class="qty-input" id="qty_<?php echo e($uniqueId); ?>" value="<?php echo e($minQty); ?>" readonly data-min="<?php echo e($minQty); ?>" data-stock="<?php echo e($stock); ?>" data-preordered="<?php echo e($preordered); ?>">
                                            <button type="button" class="qty-btn qty-plus" data-target="<?php echo e($uniqueId); ?>" data-stock="<?php echo e($stock); ?>" data-preordered="<?php echo e($preordered); ?>">+</button>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <span class="fw-bold <?php echo e($hasPrice ? 'text-success' : 'text-muted'); ?>">
                                        <?php echo e($hasPrice ? \App\Models\Product::convertPrice($vp) : '-'); ?>

                                    </span>
                                </td>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm">
                                        <button type="button" class="btn btn-outline-primary quick-view" data-id="<?php echo e($product->id); ?>" data-url="<?php echo e(route('modal.quickview', ['id' => $product->id])); ?>?user=<?php echo e($mp->user_id); ?>" title="<?php echo app('translator')->get('Quick View'); ?>">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <?php if($canBuy): ?>
                                            <button type="button" class="btn btn-success alt-add-to-cart" data-id="<?php echo e($product->id); ?>" data-mp-id="<?php echo e($mp->id); ?>" data-user="<?php echo e($mp->user_id); ?>" data-qty-id="<?php echo e($uniqueId); ?>" data-addnum-url="<?php echo e(route('merchant.cart.add', $mp->id)); ?>" title="<?php echo app('translator')->get('Add To Cart'); ?>">
                                                <i class="fas fa-cart-plus"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </tbody>
                </table>
            </div>
        </div>

        
        <div class="d-block d-md-none modal-cards">
            <?php $__currentLoopData = $alternatives; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $idx => $mp): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
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

                <div class="modal-card <?php echo e($highlight ? 'card-available' : 'card-unavailable'); ?>">
                    <div class="modal-card-header">
                        <code class="fw-bold"><?php echo e($product->sku); ?></code>
                        <?php if($inStock): ?>
                            <span class="badge bg-success"><?php echo e($mp->stock); ?> <?php echo app('translator')->get('In Stock'); ?></span>
                        <?php elseif($preordered): ?>
                            <span class="badge bg-warning text-dark"><?php echo app('translator')->get('Preorder'); ?></span>
                        <?php else: ?>
                            <span class="badge bg-secondary"><?php echo app('translator')->get('Out of Stock'); ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="modal-card-body">
                        <div class="modal-card-title"><?php echo e(getLocalizedProductName($product)); ?></div>

                        <div class="modal-card-details">
                            <?php if($product->brand): ?>
                                <div class="modal-detail">
                                    <span class="modal-label"><?php echo app('translator')->get('Brand'); ?>:</span>
                                    <span><?php echo e(getLocalizedBrandName($product->brand)); ?></span>
                                </div>
                            <?php endif; ?>

                            <?php if($qualityBrand): ?>
                                <div class="modal-detail">
                                    <span class="modal-label"><?php echo app('translator')->get('Quality'); ?>:</span>
                                    <div class="d-inline-flex align-items-center gap-1">
                                        <?php if($qualityBrand->logo): ?>
                                            <img src="<?php echo e($qualityBrand->logo_url); ?>" alt="<?php echo e(getLocalizedQualityName($qualityBrand)); ?>" class="quality-logo" style="max-height: 18px; max-width: 40px; object-fit: contain;">
                                        <?php endif; ?>
                                        <span><?php echo e(getLocalizedQualityName($qualityBrand)); ?></span>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <?php if($mp->user): ?>
                                <div class="modal-detail">
                                    <span class="modal-label"><?php echo app('translator')->get('Vendor'); ?>:</span>
                                    <span><?php echo e($mp->user->shop_name ?: $mp->user->name); ?></span>
                                </div>
                            <?php endif; ?>

                            <?php if($canBuy): ?>
                                <div class="modal-detail">
                                    <span class="modal-label"><?php echo app('translator')->get('Qty'); ?>:</span>
                                    <div class="qty-control d-inline-flex align-items-center">
                                        <button type="button" class="qty-btn qty-minus" data-target="<?php echo e($uniqueId); ?>" data-min="<?php echo e($minQty); ?>">-</button>
                                        <input type="text" class="qty-input" id="qty_<?php echo e($uniqueId); ?>" value="<?php echo e($minQty); ?>" readonly data-min="<?php echo e($minQty); ?>" data-stock="<?php echo e($stock); ?>" data-preordered="<?php echo e($preordered); ?>">
                                        <button type="button" class="qty-btn qty-plus" data-target="<?php echo e($uniqueId); ?>" data-stock="<?php echo e($stock); ?>" data-preordered="<?php echo e($preordered); ?>">+</button>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="modal-card-footer">
                        <div class="card-price <?php echo e($hasPrice ? 'text-success' : 'text-muted'); ?>">
                            <?php echo e($hasPrice ? \App\Models\Product::convertPrice($vp) : __('Price not available')); ?>

                        </div>
                        <div class="card-actions">
                            <button type="button" class="btn btn-sm btn-outline-primary quick-view" data-id="<?php echo e($product->id); ?>" data-url="<?php echo e(route('modal.quickview', ['id' => $product->id])); ?>?user=<?php echo e($mp->user_id); ?>">
                                <i class="fas fa-eye"></i>
                            </button>
                            <?php if($canBuy): ?>
                                <button type="button" class="btn btn-sm btn-success alt-add-to-cart" data-id="<?php echo e($product->id); ?>" data-mp-id="<?php echo e($mp->id); ?>" data-user="<?php echo e($mp->user_id); ?>" data-qty-id="<?php echo e($uniqueId); ?>" data-addnum-url="<?php echo e(route('merchant.cart.add', $mp->id)); ?>">
                                    <i class="fas fa-cart-plus"></i> <?php echo app('translator')->get('Add'); ?>
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>

    <?php else: ?>
        <div class="modal-empty">
            <i class="fas fa-box-open fa-3x text-muted mb-3"></i>
            <p class="text-muted mb-0"><?php echo app('translator')->get('No alternatives found'); ?></p>
        </div>
    <?php endif; ?>
</div>

<style>
/* ========== Unified Modal Styles ========== */
.modal-content-wrapper {
    max-height: 70vh;
    overflow-y: auto;
    padding: 0;
}

.modal-section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 15px;
    background: #f8f9fa;
    border-bottom: 1px solid #e0e0e0;
    margin-bottom: 0;
    position: sticky;
    top: 0;
    z-index: 10;
}

/* Desktop Table */
.modal-table {
    font-size: 0.9rem;
    margin-bottom: 0;
}

.modal-table th {
    font-weight: 600;
    font-size: 0.8rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: #555;
    border-bottom: 2px solid #dee2e6;
    padding: 10px 12px;
    background: #f8f9fa;
}

.modal-table td {
    padding: 10px 12px;
    vertical-align: middle;
}

.modal-table tbody tr {
    transition: background-color 0.15s ease;
}

.modal-table tbody tr:hover {
    background-color: #f8f9fa;
}

.table-row-available {
    background-color: #f0fff4;
}

.table-row-available:hover {
    background-color: #e6ffed !important;
}

.table-row-unavailable {
    opacity: 0.7;
}

/* Quantity Control */
.qty-control {
    border: 1px solid #ddd;
    border-radius: 4px;
    overflow: hidden;
}

.qty-btn {
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

.qty-btn:hover {
    background: #e0e0e0;
}

.qty-input {
    width: 36px;
    height: 26px;
    text-align: center;
    border: none;
    font-weight: 600;
    font-size: 13px;
    background: #fff;
}

/* Mobile Cards */
.modal-cards {
    padding: 10px;
}

.modal-card {
    background: #fff;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    margin-bottom: 10px;
    overflow: hidden;
    transition: box-shadow 0.15s ease;
}

.modal-card:active {
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.modal-card.card-available {
    border-color: #28a745;
    border-width: 1px 1px 1px 4px;
}

.modal-card.card-unavailable {
    opacity: 0.8;
    background: #f9f9f9;
}

.modal-card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px 12px;
    background: #f8f9fa;
    border-bottom: 1px solid #e0e0e0;
}

.modal-card-body {
    padding: 12px;
}

.modal-card-title {
    font-weight: 600;
    font-size: 0.95rem;
    margin-bottom: 8px;
    color: #333;
}

.modal-card-details {
    font-size: 0.85rem;
}

.modal-detail {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 4px 0;
    border-bottom: 1px dashed #eee;
}

.modal-detail:last-child {
    border-bottom: none;
}

.modal-label {
    color: #666;
    font-weight: 500;
}

.modal-card-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px 12px;
    background: #f8f9fa;
    border-top: 1px solid #e0e0e0;
}

.card-price {
    font-weight: 700;
    font-size: 1.1rem;
}

.card-actions {
    display: flex;
    gap: 6px;
}

/* Empty State */
.modal-empty {
    text-align: center;
    padding: 40px 20px;
    color: #999;
}

/* Scrollbar */
.modal-content-wrapper::-webkit-scrollbar {
    width: 6px;
}

.modal-content-wrapper::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 3px;
}

.modal-content-wrapper::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 3px;
}

.modal-content-wrapper::-webkit-scrollbar-thumb:hover {
    background: #a1a1a1;
}

/* RTL Support */
[dir="rtl"] .modal-detail {
    flex-direction: row-reverse;
}

[dir="rtl"] .modal-section-header {
    flex-direction: row-reverse;
}

[dir="rtl"] .modal-card.card-available {
    border-width: 1px 4px 1px 1px;
}
</style>


<?php /**PATH C:\Users\hp\Herd\new\resources\views/partials/api/alternatives.blade.php ENDPATH**/ ?>