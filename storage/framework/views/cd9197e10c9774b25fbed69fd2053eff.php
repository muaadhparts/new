



<div class="catalog-modal-content ill-alt">
    <?php if($alternatives && $alternatives->count() > 0): ?>
        
        <div class="catalog-section-header">
            <h5>
                <i class="fas fa-exchange-alt"></i>
                <?php echo app('translator')->get('labels.substitutions'); ?>
            </h5>
            <span class="badge bg-secondary"><?php echo e($alternatives->count()); ?> <?php echo app('translator')->get('items'); ?></span>
        </div>

        
        <div class="d-none d-md-block">
            <div class="table-responsive">
                <table class="table table-hover align-middle catalog-table">
                    <thead>
                        <tr>
                            <th><?php echo app('translator')->get('Part Number'); ?></th>
                            <th><?php echo app('translator')->get('Name'); ?></th>
                            <th><?php echo app('translator')->get('Brand'); ?></th>
                            <th><?php echo app('translator')->get('Quality'); ?></th>
                            <th><?php echo app('translator')->get('Vendor'); ?></th>
                            <th class="text-center"><?php echo app('translator')->get('Stock'); ?></th>
                            <th class="text-center"><?php echo app('translator')->get('Qty'); ?></th>
                            <th class="text-end"><?php echo app('translator')->get('Price'); ?></th>
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

                            <tr class="<?php echo e($highlight ? 'row-available' : 'row-unavailable'); ?>">
                                <td><code class="fw-bold text-dark"><?php echo e($product->sku); ?></code></td>
                                <td class="text-truncate" style="max-width: 200px;"><?php echo e(getLocalizedProductName($product)); ?></td>
                                <td>
                                    <?php if($product->brand): ?>
                                        <span class="catalog-badge catalog-badge-light"><?php echo e(Str::ucfirst(getLocalizedBrandName($product->brand))); ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if($qualityBrand): ?>
                                        <div class="d-flex align-items-center gap-1">
                                            <?php if($qualityBrand->logo): ?>
                                                <img src="<?php echo e($qualityBrand->logo_url); ?>" alt="<?php echo e(getLocalizedQualityName($qualityBrand)); ?>" class="catalog-quality-logo">
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
                                        <span class="catalog-badge catalog-badge-success"><?php echo e($mp->stock); ?></span>
                                    <?php elseif($preordered): ?>
                                        <span class="catalog-badge catalog-badge-warning"><?php echo app('translator')->get('Preorder'); ?></span>
                                    <?php else: ?>
                                        <span class="catalog-badge catalog-badge-secondary">0</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php if($canBuy): ?>
                                        <div class="catalog-qty-control">
                                            <button type="button" class="catalog-qty-btn qty-minus" data-target="<?php echo e($uniqueId); ?>" data-min="<?php echo e($minQty); ?>">-</button>
                                            <input type="text" class="catalog-qty-input qty-input" id="qty_<?php echo e($uniqueId); ?>" value="<?php echo e($minQty); ?>" readonly data-min="<?php echo e($minQty); ?>" data-stock="<?php echo e($stock); ?>" data-preordered="<?php echo e($preordered); ?>">
                                            <button type="button" class="catalog-qty-btn qty-plus" data-target="<?php echo e($uniqueId); ?>" data-stock="<?php echo e($stock); ?>" data-preordered="<?php echo e($preordered); ?>">+</button>
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

        
        <div class="d-block d-md-none catalog-cards">
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

                <div class="catalog-card <?php echo e($highlight ? 'card-available' : 'card-unavailable'); ?>">
                    <div class="catalog-card-header">
                        <code class="fw-bold"><?php echo e($product->sku); ?></code>
                        <?php if($inStock): ?>
                            <span class="catalog-badge catalog-badge-success"><?php echo e($mp->stock); ?> <?php echo app('translator')->get('In Stock'); ?></span>
                        <?php elseif($preordered): ?>
                            <span class="catalog-badge catalog-badge-warning"><?php echo app('translator')->get('Preorder'); ?></span>
                        <?php else: ?>
                            <span class="catalog-badge catalog-badge-secondary"><?php echo app('translator')->get('Out of Stock'); ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="catalog-card-body">
                        <div class="catalog-card-title"><?php echo e(getLocalizedProductName($product)); ?></div>

                        <div class="catalog-card-details">
                            <?php if($product->brand): ?>
                                <div class="catalog-card-detail">
                                    <span class="catalog-card-label"><?php echo app('translator')->get('Brand'); ?>:</span>
                                    <span><?php echo e(getLocalizedBrandName($product->brand)); ?></span>
                                </div>
                            <?php endif; ?>

                            <?php if($qualityBrand): ?>
                                <div class="catalog-card-detail">
                                    <span class="catalog-card-label"><?php echo app('translator')->get('Quality'); ?>:</span>
                                    <div class="d-inline-flex align-items-center gap-1">
                                        <?php if($qualityBrand->logo): ?>
                                            <img src="<?php echo e($qualityBrand->logo_url); ?>" alt="<?php echo e(getLocalizedQualityName($qualityBrand)); ?>" class="catalog-quality-logo" style="max-height: 18px; max-width: 40px;">
                                        <?php endif; ?>
                                        <span><?php echo e(getLocalizedQualityName($qualityBrand)); ?></span>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <?php if($mp->user): ?>
                                <div class="catalog-card-detail">
                                    <span class="catalog-card-label"><?php echo app('translator')->get('Vendor'); ?>:</span>
                                    <span><?php echo e($mp->user->shop_name ?: $mp->user->name); ?></span>
                                </div>
                            <?php endif; ?>

                            <?php if($canBuy): ?>
                                <div class="catalog-card-detail">
                                    <span class="catalog-card-label"><?php echo app('translator')->get('Qty'); ?>:</span>
                                    <div class="catalog-qty-control">
                                        <button type="button" class="catalog-qty-btn qty-minus" data-target="<?php echo e($uniqueId); ?>" data-min="<?php echo e($minQty); ?>">-</button>
                                        <input type="text" class="catalog-qty-input qty-input" id="qty_<?php echo e($uniqueId); ?>" value="<?php echo e($minQty); ?>" readonly data-min="<?php echo e($minQty); ?>" data-stock="<?php echo e($stock); ?>" data-preordered="<?php echo e($preordered); ?>">
                                        <button type="button" class="catalog-qty-btn qty-plus" data-target="<?php echo e($uniqueId); ?>" data-stock="<?php echo e($stock); ?>" data-preordered="<?php echo e($preordered); ?>">+</button>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="catalog-card-footer">
                        <div class="catalog-card-price <?php echo e($hasPrice ? 'text-success' : 'text-muted'); ?>">
                            <?php echo e($hasPrice ? \App\Models\Product::convertPrice($vp) : __('Price not available')); ?>

                        </div>
                        <div class="catalog-card-actions">
                            <button type="button" class="catalog-btn catalog-btn-outline quick-view" data-id="<?php echo e($product->id); ?>" data-url="<?php echo e(route('modal.quickview', ['id' => $product->id])); ?>?user=<?php echo e($mp->user_id); ?>">
                                <i class="fas fa-eye"></i>
                            </button>
                            <?php if($canBuy): ?>
                                <button type="button" class="catalog-btn catalog-btn-success alt-add-to-cart" data-id="<?php echo e($product->id); ?>" data-mp-id="<?php echo e($mp->id); ?>" data-user="<?php echo e($mp->user_id); ?>" data-qty-id="<?php echo e($uniqueId); ?>" data-addnum-url="<?php echo e(route('merchant.cart.add', $mp->id)); ?>">
                                    <i class="fas fa-cart-plus"></i> <?php echo app('translator')->get('Add'); ?>
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>

    <?php else: ?>
        <div class="catalog-empty">
            <i class="fas fa-box-open"></i>
            <p><?php echo app('translator')->get('No alternatives found'); ?></p>
        </div>
    <?php endif; ?>
</div>


<?php /**PATH C:\Users\hp\Herd\new\resources\views/partials/api/alternatives.blade.php ENDPATH**/ ?>