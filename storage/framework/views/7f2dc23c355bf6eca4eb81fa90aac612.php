

<?php
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
        : (($product->photo ?? null) ? \Illuminate\Support\Facades\Storage::url($product->photo) : asset('assets/images/noimage.png'));

    // MerchantProduct من الكنترولر
    $mp = $mp ?? null;
    $brand = $brand ?? null;

    // السعر
    $rawPrice = $product->price ?? null;
    $rawPrev  = $product->previous_price ?? null;

    $forceVendor = request()->has('user') || isset($product->vendor_user_id);
    if ($forceVendor) {
        $priceHtml = $rawPrice !== null ? \App\Models\Product::convertPrice($rawPrice) : '-';
        $prevHtml  = $rawPrev  !== null ? \App\Models\Product::convertPrice($rawPrev)  : null;
    } else {
        $priceHtml = method_exists($product, 'showPrice')
            ? $product->showPrice()
            : (\App\Models\Product::convertPrice($rawPrice ?? 0));
        $prevHtml  = (method_exists($product, 'showPreviousPrice') && $product->showPreviousPrice())
            ? $product->showPreviousPrice()
            : ($rawPrev !== null ? \App\Models\Product::convertPrice($rawPrev) : null);
    }

    // تقييمات
    $avg   = $product->ratings_avg_rating ?? null;
    $count = class_exists('App\\Models\\Rating') && method_exists('App\\Models\\Rating', 'ratingCount')
        ? \App\Models\Rating::ratingCount($product->id)
        : null;

    // Quality Brand
    $qualityBrand = $mp?->qualityBrand;

    // Vendor
    $vendor = $mp?->user;

    // الحد الأدنى للكمية
    $minQty = $mp ? (int)($mp->minimum_qty ?? 1) : 1;
    if ($minQty < 1) $minQty = 1;

    // المخزون
    $stock = $mp ? (int)($mp->stock ?? 999) : (int)($product->stock ?? 999);
    $inStock = $stock > 0;

    // Preorder
    $preordered = $mp ? (int)($mp->preordered ?? 0) : 0;

    // حالة التوفر
    $canBuy = $inStock || $preordered;
?>

<div class="qv-modal ill-product" data-product-id="<?php echo e($product->id); ?>" data-user="<?php echo e($vendorId); ?>">
    <div class="row g-3 g-md-4">
        
        <div class="col-12 col-md-5">
            <div class="qv-image-wrapper">
                <?php if($mainPhoto): ?>
                    <img src="<?php echo e($mainPhoto); ?>"
                         alt="<?php echo e($product->name ?? $product->sku); ?>"
                         class="qv-main-image"
                         loading="lazy">
                <?php endif; ?>

                
                <?php if(!empty($product->galleries) && count($product->galleries) > 0): ?>
                    <div class="qv-gallery">
                        <?php $__currentLoopData = $product->galleries->take(4); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $gallery): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <?php
                                $gUrl = filter_var($gallery->photo ?? '', FILTER_VALIDATE_URL)
                                    ? $gallery->photo
                                    : asset('assets/images/galleries/'.$gallery->photo);
                            ?>
                            <img src="<?php echo e($gUrl); ?>"
                                 alt="<?php echo e($product->name ?? ''); ?>"
                                 class="qv-thumb"
                                 loading="lazy">
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        
        <div class="col-12 col-md-7">
            
            <h4 class="qv-title">
                <?php if (isset($component)) { $__componentOriginal762fb9c7d6956b7627f3c9570bd39396 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal762fb9c7d6956b7627f3c9570bd39396 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.product-name','data' => ['product' => $product,'vendorId' => $vendorId,'target' => '_blank']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('product-name'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['product' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($product),'vendor-id' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($vendorId),'target' => '_blank']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal762fb9c7d6956b7627f3c9570bd39396)): ?>
<?php $attributes = $__attributesOriginal762fb9c7d6956b7627f3c9570bd39396; ?>
<?php unset($__attributesOriginal762fb9c7d6956b7627f3c9570bd39396); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal762fb9c7d6956b7627f3c9570bd39396)): ?>
<?php $component = $__componentOriginal762fb9c7d6956b7627f3c9570bd39396; ?>
<?php unset($__componentOriginal762fb9c7d6956b7627f3c9570bd39396); ?>
<?php endif; ?>
            </h4>

            
            <?php if(!empty($avg)): ?>
                <div class="qv-rating">
                    <?php for($i = 1; $i <= 5; $i++): ?>
                        <i class="fa<?php echo e($i <= round($avg) ? 's' : 'r'); ?> fa-star"></i>
                    <?php endfor; ?>
                    <span class="qv-rating-text"><?php echo e(number_format($avg, 1)); ?></span>
                    <?php if($count): ?>
                        <span class="qv-rating-count">(<?php echo e($count); ?>)</span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            
            <div class="qv-price">
                <span class="qv-price-current"><?php echo $priceHtml; ?></span>
                <?php if($prevHtml): ?>
                    <del class="qv-price-old"><?php echo $prevHtml; ?></del>
                <?php endif; ?>
            </div>

            
            <div class="qv-info">
                <table class="qv-info-table">
                    <tbody>
                        
                        <?php if($product->sku): ?>
                            <tr>
                                <td class="qv-info-label"><i class="fas fa-barcode"></i> <?php echo app('translator')->get('SKU'); ?></td>
                                <td class="qv-info-value"><code><?php echo e($product->sku); ?></code></td>
                            </tr>
                        <?php endif; ?>

                        
                        <?php if($product->brand): ?>
                            <tr>
                                <td class="qv-info-label"><i class="fas fa-tag"></i> <?php echo app('translator')->get('Brand'); ?></td>
                                <td class="qv-info-value"><?php echo e(getLocalizedBrandName($product->brand)); ?></td>
                            </tr>
                        <?php endif; ?>

                        
                        <?php if($qualityBrand): ?>
                            <tr>
                                <td class="qv-info-label"><i class="fas fa-certificate"></i> <?php echo app('translator')->get('Quality'); ?></td>
                                <td class="qv-info-value">
                                    <div class="qv-quality">
                                        <?php if($qualityBrand->logo): ?>
                                            <img src="<?php echo e($qualityBrand->logo_url); ?>"
                                                 alt="<?php echo e(getLocalizedQualityName($qualityBrand)); ?>"
                                                 class="qv-quality-logo">
                                        <?php endif; ?>
                                        <span><?php echo e(getLocalizedQualityName($qualityBrand)); ?></span>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>

                        
                        <?php if($vendor): ?>
                            <tr>
                                <td class="qv-info-label"><i class="fas fa-store"></i> <?php echo app('translator')->get('Vendor'); ?></td>
                                <td class="qv-info-value"><?php echo e($vendor->shop_name ?: $vendor->name); ?></td>
                            </tr>
                        <?php endif; ?>

                        
                        <tr>
                            <td class="qv-info-label"><i class="fas fa-boxes"></i> <?php echo app('translator')->get('Stock'); ?></td>
                            <td class="qv-info-value">
                                <?php if($inStock): ?>
                                    <span class="badge bg-success"><?php echo e($stock); ?> <?php echo app('translator')->get('Available'); ?></span>
                                <?php elseif($preordered): ?>
                                    <span class="badge bg-warning text-dark"><?php echo app('translator')->get('Preorder'); ?></span>
                                <?php else: ?>
                                    <span class="badge bg-danger"><?php echo app('translator')->get('Out of Stock'); ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            
            <?php if(($product->type ?? 'Physical') === 'Physical' && $canBuy): ?>
                <div class="qv-quantity">
                    <label class="qv-qty-label"><?php echo app('translator')->get('Quantity'); ?>:</label>
                    <div class="qv-qty-control">
                        <button type="button" class="qv-qty-btn modal-qtminus" data-min="<?php echo e($minQty); ?>">
                            <i class="fas fa-minus"></i>
                        </button>
                        <input type="number"
                               name="quantity"
                               value="<?php echo e($minQty); ?>"
                               min="<?php echo e($minQty); ?>"
                               class="qv-qty-input ill-qty modal-qty-input"
                               data-min="<?php echo e($minQty); ?>"
                               data-stock="<?php echo e($stock); ?>"
                               data-preordered="<?php echo e($preordered); ?>"
                               readonly>
                        <button type="button" class="qv-qty-btn modal-qtplus" data-stock="<?php echo e($stock); ?>" data-preordered="<?php echo e($preordered); ?>">
                            <i class="fas fa-plus"></i>
                        </button>
                    </div>
                    <?php if($minQty > 1): ?>
                        <small class="qv-qty-hint"><?php echo app('translator')->get('Min'); ?>: <?php echo e($minQty); ?></small>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            
            <div class="qv-actions">
                <?php if($canBuy): ?>
                    <?php if($mp): ?>
                        <button type="button"
                                class="btn qv-btn-cart ill-add-to-cart"
                                data-id="<?php echo e($product->id); ?>"
                                data-mp-id="<?php echo e($mp->id); ?>"
                                data-user="<?php echo e($vendorId); ?>"
                                data-addnum-url="<?php echo e(route('merchant.cart.add', $mp->id)); ?>">
                            <i class="fas fa-cart-plus"></i> <?php echo app('translator')->get('Add To Cart'); ?>
                        </button>

                        <button type="button"
                                class="btn qv-btn-buy ill-buy-now"
                                data-id="<?php echo e($product->id); ?>"
                                data-mp-id="<?php echo e($mp->id); ?>"
                                data-user="<?php echo e($vendorId); ?>"
                                data-addtonum-url="<?php echo e(route('merchant.cart.add', $mp->id)); ?>"
                                data-carts-url="<?php echo e(url('/carts')); ?>">
                            <i class="fas fa-bolt"></i> <?php echo app('translator')->get('Buy Now'); ?>
                        </button>
                    <?php else: ?>
                        <button type="button"
                                class="btn qv-btn-cart ill-add-to-cart"
                                data-id="<?php echo e($product->id); ?>"
                                data-user="<?php echo e($vendorId); ?>"
                                data-addnum-url="<?php echo e(url('/addnumcart')); ?>">
                            <i class="fas fa-cart-plus"></i> <?php echo app('translator')->get('Add To Cart'); ?>
                        </button>

                        <button type="button"
                                class="btn qv-btn-buy ill-buy-now"
                                data-id="<?php echo e($product->id); ?>"
                                data-user="<?php echo e($vendorId); ?>"
                                data-addtonum-url="<?php echo e(url('/addtonumcart')); ?>"
                                data-carts-url="<?php echo e(url('/carts')); ?>">
                            <i class="fas fa-bolt"></i> <?php echo app('translator')->get('Buy Now'); ?>
                        </button>
                    <?php endif; ?>
                <?php else: ?>
                    <button type="button" class="btn qv-btn-disabled" disabled>
                        <i class="fas fa-times-circle"></i> <?php echo app('translator')->get('Out of Stock'); ?>
                    </button>
                <?php endif; ?>

                
                <?php if($mp): ?>
                    <a href="<?php echo e(route('front.product', ['slug' => $product->slug, 'vendor_id' => $vendorId, 'merchant_product_id' => $mp->id])); ?>"
                       class="btn qv-btn-details"
                       target="_blank">
                        <i class="fas fa-external-link-alt"></i> <?php echo app('translator')->get('View Details'); ?>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>


<style>
/* ========== Quick View Modal Styles ========== */
.qv-modal {
    padding: 0;
}

/* Image Section */
.qv-image-wrapper {
    text-align: center;
}

.qv-main-image {
    width: 100%;
    max-height: 300px;
    object-fit: contain;
    border-radius: 8px;
    background: #f8f9fa;
    padding: 10px;
}

.qv-gallery {
    display: flex;
    gap: 8px;
    margin-top: 10px;
    justify-content: center;
    flex-wrap: wrap;
}

.qv-thumb {
    width: 60px;
    height: 60px;
    object-fit: cover;
    border-radius: 4px;
    border: 2px solid #e0e0e0;
    cursor: pointer;
    transition: border-color 0.2s;
}

.qv-thumb:hover {
    border-color: var(--primary-color, #007bff);
}

/* Title */
.qv-title {
    font-size: 1.2rem;
    font-weight: 600;
    margin-bottom: 8px;
    line-height: 1.4;
}

/* Rating */
.qv-rating {
    margin-bottom: 10px;
    color: #f5a623;
    font-size: 0.9rem;
}

.qv-rating i {
    margin-right: 2px;
}

.qv-rating-text {
    color: #333;
    font-weight: 600;
    margin-left: 5px;
}

.qv-rating-count {
    color: #888;
    font-size: 0.85rem;
}

/* Price */
.qv-price {
    margin-bottom: 15px;
}

.qv-price-current {
    font-size: 1.5rem;
    font-weight: 700;
    color: #28a745;
}

.qv-price-old {
    font-size: 1rem;
    color: #999;
    margin-left: 10px;
}

/* Info Table */
.qv-info {
    margin-bottom: 15px;
}

.qv-info-table {
    width: 100%;
    font-size: 0.9rem;
}

.qv-info-table tr {
    border-bottom: 1px solid #f0f0f0;
}

.qv-info-table tr:last-child {
    border-bottom: none;
}

.qv-info-label {
    padding: 8px 10px 8px 0;
    color: #666;
    width: 100px;
    white-space: nowrap;
}

.qv-info-label i {
    width: 16px;
    text-align: center;
    margin-right: 6px;
    color: #888;
}

.qv-info-value {
    padding: 8px 0;
    color: #333;
}

.qv-quality {
    display: flex;
    align-items: center;
    gap: 8px;
}

.qv-quality-logo {
    max-height: 24px;
    max-width: 60px;
    object-fit: contain;
}

/* Quantity */
.qv-quantity {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 15px;
    flex-wrap: wrap;
}

.qv-qty-label {
    font-weight: 500;
    color: #555;
}

.qv-qty-control {
    display: flex;
    align-items: center;
    border: 1px solid #ddd;
    border-radius: 6px;
    overflow: hidden;
}

.qv-qty-btn {
    width: 36px;
    height: 36px;
    border: none;
    background: #f5f5f5;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: background 0.2s;
}

.qv-qty-btn:hover {
    background: #e0e0e0;
}

.qv-qty-input {
    width: 50px;
    height: 36px;
    text-align: center;
    border: none;
    font-weight: 600;
    font-size: 1rem;
}

.qv-qty-hint {
    color: #888;
    font-size: 0.8rem;
}

/* Actions */
.qv-actions {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.qv-btn-cart,
.qv-btn-buy,
.qv-btn-details,
.qv-btn-disabled {
    flex: 1;
    min-width: 120px;
    padding: 10px 15px;
    border-radius: 6px;
    font-weight: 600;
    font-size: 0.9rem;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    transition: all 0.2s;
}

.qv-btn-cart {
    background: #007bff;
    color: #fff;
    border: none;
}

.qv-btn-cart:hover {
    background: #0056b3;
    color: #fff;
}

.qv-btn-buy {
    background: #28a745;
    color: #fff;
    border: none;
}

.qv-btn-buy:hover {
    background: #1e7e34;
    color: #fff;
}

.qv-btn-details {
    background: #f8f9fa;
    color: #333;
    border: 1px solid #ddd;
    text-decoration: none;
}

.qv-btn-details:hover {
    background: #e9ecef;
    color: #333;
}

.qv-btn-disabled {
    background: #e9ecef;
    color: #999;
    border: none;
    cursor: not-allowed;
}

/* Mobile Responsive */
@media (max-width: 767px) {
    .qv-main-image {
        max-height: 200px;
    }

    .qv-title {
        font-size: 1.1rem;
    }

    .qv-price-current {
        font-size: 1.3rem;
    }

    .qv-info-label {
        width: 80px;
        font-size: 0.85rem;
    }

    .qv-actions {
        flex-direction: column;
    }

    .qv-btn-cart,
    .qv-btn-buy,
    .qv-btn-details {
        min-width: 100%;
    }
}

/* RTL Support */
[dir="rtl"] .qv-info-label {
    padding: 8px 0 8px 10px;
}

[dir="rtl"] .qv-info-label i {
    margin-right: 0;
    margin-left: 6px;
}

[dir="rtl"] .qv-price-old {
    margin-left: 0;
    margin-right: 10px;
}

[dir="rtl"] .qv-rating-text {
    margin-left: 0;
    margin-right: 5px;
}
</style>


<script>
(function() {
    // زيادة الكمية
    document.querySelectorAll('.modal-qtplus').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            var input = this.closest('.qv-qty-control').querySelector('.modal-qty-input');
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
    document.querySelectorAll('.modal-qtminus').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            var input = this.closest('.qv-qty-control').querySelector('.modal-qty-input');
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
})();
</script>
<?php /**PATH C:\Users\hp\Herd\new\resources\views/partials/product.blade.php ENDPATH**/ ?>