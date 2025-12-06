


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

<div class="catalog-quickview ill-product" data-product-id="<?php echo e($product->id); ?>" data-user="<?php echo e($vendorId); ?>">
    <div class="row g-3 g-md-4">
        
        <div class="col-12 col-md-5">
            <div class="catalog-quickview-image">
                <?php if($mainPhoto): ?>
                    <img src="<?php echo e($mainPhoto); ?>"
                         alt="<?php echo e($product->name ?? $product->sku); ?>"
                         class="catalog-quickview-main-img"
                         loading="lazy">
                <?php endif; ?>

                
                <?php if(!empty($product->galleries) && count($product->galleries) > 0): ?>
                    <div class="catalog-quickview-gallery">
                        <?php $__currentLoopData = $product->galleries->take(4); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $gallery): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <?php
                                $gUrl = filter_var($gallery->photo ?? '', FILTER_VALIDATE_URL)
                                    ? $gallery->photo
                                    : asset('assets/images/galleries/'.$gallery->photo);
                            ?>
                            <img src="<?php echo e($gUrl); ?>"
                                 alt="<?php echo e($product->name ?? ''); ?>"
                                 class="catalog-quickview-thumb"
                                 loading="lazy">
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        
        <div class="col-12 col-md-7">
            
            <h4 class="catalog-quickview-title">
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
                <div class="catalog-quickview-rating">
                    <?php for($i = 1; $i <= 5; $i++): ?>
                        <i class="fa<?php echo e($i <= round($avg) ? 's' : 'r'); ?> fa-star"></i>
                    <?php endfor; ?>
                    <span class="catalog-quickview-rating-text"><?php echo e(number_format($avg, 1)); ?></span>
                    <?php if($count): ?>
                        <span class="catalog-quickview-rating-count">(<?php echo e($count); ?>)</span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            
            <div class="catalog-quickview-price">
                <span class="catalog-quickview-price-current"><?php echo $priceHtml; ?></span>
                <?php if($prevHtml): ?>
                    <del class="catalog-quickview-price-old"><?php echo $prevHtml; ?></del>
                <?php endif; ?>
            </div>

            
            <div class="catalog-quickview-info">
                <table class="catalog-info-table">
                    <tbody>
                        
                        <?php if($product->sku): ?>
                            <tr>
                                <td class="catalog-info-label"><i class="fas fa-barcode"></i> <?php echo app('translator')->get('SKU'); ?></td>
                                <td class="catalog-info-value"><code><?php echo e($product->sku); ?></code></td>
                            </tr>
                        <?php endif; ?>

                        
                        <?php if($product->brand): ?>
                            <tr>
                                <td class="catalog-info-label"><i class="fas fa-tag"></i> <?php echo app('translator')->get('Brand'); ?></td>
                                <td class="catalog-info-value"><?php echo e(getLocalizedBrandName($product->brand)); ?></td>
                            </tr>
                        <?php endif; ?>

                        
                        <?php if($qualityBrand): ?>
                            <tr>
                                <td class="catalog-info-label"><i class="fas fa-certificate"></i> <?php echo app('translator')->get('Quality'); ?></td>
                                <td class="catalog-info-value">
                                    <div class="catalog-quickview-quality">
                                        <?php if($qualityBrand->logo): ?>
                                            <img src="<?php echo e($qualityBrand->logo_url); ?>"
                                                 alt="<?php echo e(getLocalizedQualityName($qualityBrand)); ?>"
                                                 class="catalog-quickview-quality-logo">
                                        <?php endif; ?>
                                        <span><?php echo e(getLocalizedQualityName($qualityBrand)); ?></span>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>

                        
                        <?php if($vendor): ?>
                            <tr>
                                <td class="catalog-info-label"><i class="fas fa-store"></i> <?php echo app('translator')->get('Vendor'); ?></td>
                                <td class="catalog-info-value"><?php echo e($vendor->shop_name ?: $vendor->name); ?></td>
                            </tr>
                        <?php endif; ?>

                        
                        <tr>
                            <td class="catalog-info-label"><i class="fas fa-boxes"></i> <?php echo app('translator')->get('Stock'); ?></td>
                            <td class="catalog-info-value">
                                <?php if($inStock): ?>
                                    <span class="catalog-badge catalog-badge-success"><?php echo e($stock); ?> <?php echo app('translator')->get('Available'); ?></span>
                                <?php elseif($preordered): ?>
                                    <span class="catalog-badge catalog-badge-warning"><?php echo app('translator')->get('Preorder'); ?></span>
                                <?php else: ?>
                                    <span class="catalog-badge catalog-badge-danger"><?php echo app('translator')->get('Out of Stock'); ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            
            <?php if(($product->type ?? 'Physical') === 'Physical' && $canBuy): ?>
                <div class="catalog-quickview-quantity">
                    <label class="catalog-quickview-qty-label"><?php echo app('translator')->get('Quantity'); ?>:</label>
                    <div class="catalog-quickview-qty-control">
                        <button type="button" class="catalog-quickview-qty-btn modal-qtminus" data-min="<?php echo e($minQty); ?>">
                            <i class="fas fa-minus"></i>
                        </button>
                        <input type="number"
                               name="quantity"
                               value="<?php echo e($minQty); ?>"
                               min="<?php echo e($minQty); ?>"
                               class="catalog-quickview-qty-input ill-qty modal-qty-input"
                               data-min="<?php echo e($minQty); ?>"
                               data-stock="<?php echo e($stock); ?>"
                               data-preordered="<?php echo e($preordered); ?>"
                               readonly>
                        <button type="button" class="catalog-quickview-qty-btn modal-qtplus" data-stock="<?php echo e($stock); ?>" data-preordered="<?php echo e($preordered); ?>">
                            <i class="fas fa-plus"></i>
                        </button>
                    </div>
                    <?php if($minQty > 1): ?>
                        <small class="catalog-quickview-qty-hint"><?php echo app('translator')->get('Min'); ?>: <?php echo e($minQty); ?></small>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            
            <div class="catalog-quickview-actions">
                <?php if($canBuy): ?>
                    <?php if($mp): ?>
                        <button type="button"
                                class="catalog-quickview-btn catalog-quickview-btn-cart ill-add-to-cart"
                                data-id="<?php echo e($product->id); ?>"
                                data-mp-id="<?php echo e($mp->id); ?>"
                                data-user="<?php echo e($vendorId); ?>"
                                data-addnum-url="<?php echo e(route('merchant.cart.add', $mp->id)); ?>">
                            <i class="fas fa-cart-plus"></i> <?php echo app('translator')->get('Add To Cart'); ?>
                        </button>

                        <button type="button"
                                class="catalog-quickview-btn catalog-quickview-btn-buy ill-buy-now"
                                data-id="<?php echo e($product->id); ?>"
                                data-mp-id="<?php echo e($mp->id); ?>"
                                data-user="<?php echo e($vendorId); ?>"
                                data-addtonum-url="<?php echo e(route('merchant.cart.add', $mp->id)); ?>"
                                data-carts-url="<?php echo e(url('/carts')); ?>">
                            <i class="fas fa-bolt"></i> <?php echo app('translator')->get('Buy Now'); ?>
                        </button>
                    <?php else: ?>
                        <button type="button"
                                class="catalog-quickview-btn catalog-quickview-btn-cart ill-add-to-cart"
                                data-id="<?php echo e($product->id); ?>"
                                data-user="<?php echo e($vendorId); ?>"
                                data-addnum-url="<?php echo e(url('/addnumcart')); ?>">
                            <i class="fas fa-cart-plus"></i> <?php echo app('translator')->get('Add To Cart'); ?>
                        </button>

                        <button type="button"
                                class="catalog-quickview-btn catalog-quickview-btn-buy ill-buy-now"
                                data-id="<?php echo e($product->id); ?>"
                                data-user="<?php echo e($vendorId); ?>"
                                data-addtonum-url="<?php echo e(url('/addtonumcart')); ?>"
                                data-carts-url="<?php echo e(url('/carts')); ?>">
                            <i class="fas fa-bolt"></i> <?php echo app('translator')->get('Buy Now'); ?>
                        </button>
                    <?php endif; ?>
                <?php else: ?>
                    <button type="button" class="catalog-quickview-btn catalog-quickview-btn-disabled" disabled>
                        <i class="fas fa-times-circle"></i> <?php echo app('translator')->get('Out of Stock'); ?>
                    </button>
                <?php endif; ?>

                
                <?php if($mp): ?>
                    <a href="<?php echo e(route('front.product', ['slug' => $product->slug, 'vendor_id' => $vendorId, 'merchant_product_id' => $mp->id])); ?>"
                       class="catalog-quickview-btn catalog-quickview-btn-details"
                       target="_blank">
                        <i class="fas fa-external-link-alt"></i> <?php echo app('translator')->get('View Details'); ?>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>


<?php /**PATH C:\Users\hp\Herd\new\resources\views/partials/product.blade.php ENDPATH**/ ?>