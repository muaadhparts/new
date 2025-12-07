

<?php
    use App\Models\Product;
    use App\Models\Brand;
    use App\Models\MerchantProduct;
    use App\Helpers\CartHelper;
    use Illuminate\Support\Facades\Storage;

    // التحقق من وجود سلة في النظام الجديد (v2)
    $hasV2Cart = CartHelper::hasCart();
?>


<?php if($hasV2Cart): ?>
    <?php echo $__env->make('frontend.ajax.cart-page-v2', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
<?php else: ?>
<div class="container gs-cart-container">
    <div class="row gs-cart-row">

        <?php if(Session::has('cart') && isset($productsByVendor) && !empty($productsByVendor)): ?>

            <div class="col-lg-12">
                
                <?php $__currentLoopData = $productsByVendor; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $vendorId => $vendorData): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div class="vendor-cart-section muaadh-vendor-cart-section mb-5">
                    
                    <div class="vendor-header muaadh-vendor-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h4 class="mb-1 muaadh-vendor-title">
                                    <i class="fas fa-store me-2"></i><?php echo e($vendorData['vendor_name']); ?>

                                </h4>
                                <p class="mb-0 muaadh-vendor-subtitle">
                                    <i class="fas fa-box me-1"></i><?php echo e($vendorData['count']); ?> <?php echo app('translator')->get('Items'); ?>
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="row g-0">
                        
                        <div class="col-lg-8">
                            <div class="cart-table table-responsive muaadh-cart-table-container">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th scope="col"><?php echo app('translator')->get('Image'); ?></th>
                                            <th scope="col"><?php echo app('translator')->get('Name'); ?></th>
                                            <th scope="col"><?php echo app('translator')->get('SKU'); ?></th>
                                            <th scope="col"><?php echo app('translator')->get('Brand'); ?></th>
                                            <th scope="col"><?php echo app('translator')->get('Quality'); ?></th>
                                            <th scope="col"><?php echo app('translator')->get('Weight'); ?></th>
                                            <th scope="col"><?php echo app('translator')->get('Price'); ?></th>
                                            <th scope="col"><?php echo app('translator')->get('Quantity'); ?></th>
                                            <th scope="col"><?php echo app('translator')->get('Subtotal'); ?></th>
                                            <th scope="col"><?php echo app('translator')->get('Action'); ?></th>
                                        </tr>
                                    </thead>

                                    <tbody class="t_body">
                                        <?php $__currentLoopData = $vendorData['products']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $rowKey => $product): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <?php
                                                // معلومات أساسية
                                                $currentVendorId = data_get($product, 'item.user_id') ?? data_get($product, 'user_id') ?? 0;
                                                $slug     = data_get($product, 'item.slug');
                                                $name     = data_get($product, 'item.name');
                                                $sku      = data_get($product, 'item.sku');
                                                $photo    = data_get($product, 'item.photo');

                                                // المفتاح الحقيقي للسلة كما هو (Vendor-aware)
                                                $row    = (string) $rowKey;
                                                // نسخة آمنة للـ DOM
                                                $domKey = str_replace([':', '#', '.', ' ', '/', '\\'], '_', $row);

                                                // Fetch merchant data
                                                $mpId = $product['merchant_product_id'] ?? null;
                                                $itemProduct = \App\Models\Product::where('slug', $slug)->first();

                                                // جلب MerchantProduct
                                                $itemMerchant = null;
                                                if ($mpId) {
                                                    $itemMerchant = MerchantProduct::with(['qualityBrand'])->find($mpId);
                                                } elseif ($itemProduct && $currentVendorId) {
                                                    $itemMerchant = $itemProduct->getMerchantProduct($currentVendorId);
                                                }
                                                $itemMerchantId = $itemMerchant->id ?? null;

                                                // جلب Brand
                                                $brand = null;
                                                $brandId = data_get($product, 'item.brand_id');
                                                if ($brandId) {
                                                    $brand = Brand::find($brandId);
                                                } elseif ($itemProduct && $itemProduct->brand) {
                                                    $brand = $itemProduct->brand;
                                                }

                                                // Quality Brand
                                                $qualityBrand = $itemMerchant?->qualityBrand;

                                                // Stock & Minimum Qty
                                                $stock = (int)($product['stock'] ?? $itemMerchant->stock ?? 999);
                                                if (!empty($product['size_qty'])) {
                                                    $stock = (int)$product['size_qty'];
                                                }
                                                $minQty = (int)(data_get($product, 'item.minimum_qty') ?? $itemMerchant->minimum_qty ?? 1);
                                                if ($minQty < 1) $minQty = 1;

                                                $preordered = $product['preordered'] ?? $itemMerchant->preordered ?? 0;

                                                // رابط تفاصيل المنتج
                                                $productUrl = ($currentVendorId && $itemMerchantId)
                                                    ? route('front.product', ['slug' => $slug, 'vendor_id' => $currentVendorId, 'merchant_product_id' => $itemMerchantId])
                                                    : 'javascript:;';
                                            ?>

                                            <tr>
                                                
                                                <td class="cart-image">
                                                    <img src="<?php echo e($photo ? Storage::url($photo) : asset('assets/images/noimage.png')); ?>"
                                                         alt="" class="muaadh-cart-product-img">
                                                </td>

                                                
                                                <td class="cart-name">
                                                    <?php if (isset($component)) { $__componentOriginal762fb9c7d6956b7627f3c9570bd39396 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal762fb9c7d6956b7627f3c9570bd39396 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.product-name','data' => ['item' => $product['item'],'vendorId' => $currentVendorId,'merchantProductId' => $itemMerchantId,'showSku' => false,'target' => '_blank']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('product-name'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['item' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($product['item']),'vendor-id' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($currentVendorId),'merchant-product-id' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($itemMerchantId),'showSku' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(false),'target' => '_blank']); ?>
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
                                                    <?php if(!empty($product['color']) || !empty($product['size'])): ?>
                                                        <div class="d-flex align-items-center gap-2 mt-2">
                                                            <?php if(!empty($product['color'])): ?>
                                                                <span class="text-muted small"><?php echo app('translator')->get('Color'); ?>: </span>
                                                                <span class="cart-color muaadh-cart-color-swatch" style="--swatch-color: #<?php echo e($product['color']==''?'white':$product['color']); ?>;"></span>
                                                            <?php endif; ?>
                                                            <?php if(!empty($product['size'])): ?>
                                                                <span class="text-muted small"><?php echo app('translator')->get('Size'); ?>: <?php echo e($product['size']); ?></span>
                                                            <?php endif; ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </td>

                                                
                                                <td class="cart-sku">
                                                    <code class="small fw-bold"><?php echo e($sku ?? '-'); ?></code>
                                                </td>

                                                
                                                <td class="cart-brand">
                                                    <span><?php echo e($brand ? Str::ucfirst(getLocalizedBrandName($brand)) : '-'); ?></span>
                                                </td>

                                                
                                                <td class="cart-quality">
                                                    <span><?php echo e($qualityBrand ? getLocalizedQualityName($qualityBrand) : '-'); ?></span>
                                                </td>

                                                
                                                <td class="cart-weight">
                                                    <?php
                                                        // جلب الوزن من بيانات المنتج المحسوبة
                                                        $rowWeight = $product['row_weight'] ?? null;
                                                        $dimensions = $product['dimensions'] ?? null;
                                                        $unitWeight = $dimensions['weight'] ?? null;
                                                    ?>
                                                    <?php if($rowWeight !== null): ?>
                                                        <span class="fw-bold"><?php echo e(number_format($rowWeight, 2)); ?> kg</span>
                                                        <?php if($unitWeight && (int)$product['qty'] > 1): ?>
                                                            <br><small class="text-muted">(<?php echo e(number_format($unitWeight, 2)); ?> × <?php echo e($product['qty']); ?>)</small>
                                                        <?php endif; ?>
                                                    <?php elseif($dimensions && isset($dimensions['is_complete']) && !$dimensions['is_complete']): ?>
                                                        <span class="text-warning" title="<?php echo e(implode(', ', $dimensions['missing_fields'] ?? [])); ?>">
                                                            <i class="fas fa-exclamation-triangle"></i> <?php echo app('translator')->get('Incomplete'); ?>
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </td>

                                                
                                                <td class="cart-price">
                                                    <?php echo e(Product::convertPrice($product['item_price'])); ?>

                                                </td>

                                                
                                                <?php if(data_get($product,'item.type') == 'Physical'): ?>
                                                    <td>
                                                        <div class="cart-quantity">
                                                            <button class="cart-quantity-btn quantity-down"
                                                                    data-min-qty="<?php echo e($minQty); ?>">-</button>

                                                            <input type="text" id="qty<?php echo e($domKey); ?>" value="<?php echo e($product['qty']); ?>"
                                                                   class="borderless" readonly>

                                                            
                                                            <input type="hidden" class="prodid"   value="<?php echo e(data_get($product,'item.id')); ?>">
                                                            <input type="hidden" class="itemid"   value="<?php echo e($row); ?>">
                                                            <input type="hidden" class="domkey"   value="<?php echo e($domKey); ?>">
                                                            <input type="hidden" class="size_qty" value="<?php echo e($product['size_qty'] ?? ''); ?>">
                                                            <input type="hidden" class="size_price" value="<?php echo e($product['size_price'] ?? 0); ?>">
                                                            <input type="hidden" class="minimum_qty" value="<?php echo e($minQty); ?>">
                                                            <input type="hidden" class="stock_val" value="<?php echo e($stock); ?>">
                                                            <input type="hidden" class="preordered_val" value="<?php echo e($preordered); ?>">

                                                            <button class="cart-quantity-btn quantity-up"
                                                                    data-stock="<?php echo e($stock); ?>"
                                                                    data-preordered="<?php echo e($preordered); ?>">+</button>
                                                        </div>
                                                    </td>
                                                <?php else: ?>
                                                    <td class="product-quantity">1</td>
                                                <?php endif; ?>

                                                
                                                <?php if(!empty($product['size_qty'])): ?>
                                                    <input type="hidden" id="stock<?php echo e($domKey); ?>" value="<?php echo e($product['size_qty']); ?>">
                                                <?php elseif(data_get($product,'item.type') != 'Physical'): ?>
                                                    <input type="hidden" id="stock<?php echo e($domKey); ?>" value="1">
                                                <?php else: ?>
                                                    <input type="hidden" id="stock<?php echo e($domKey); ?>" value="<?php echo e($stock); ?>">
                                                <?php endif; ?>

                                                
                                                <td class="cart-price" id="prc<?php echo e($domKey); ?>">
                                                    <?php echo e(Product::convertPrice($product['price'])); ?>

                                                    <?php if(!empty($product['discount'])): ?>
                                                        <br><small class="text-success"><?php echo e($product['discount']); ?>% <?php echo app('translator')->get('off'); ?></small>
                                                    <?php endif; ?>
                                                </td>

                                                
                                                <td>
                                                    <a class="cart-remove-btn btn btn-sm btn-outline-danger"
                                                       data-class="cremove<?php echo e($domKey); ?>"
                                                       href="<?php echo e(route('product.cart.remove', $row)); ?>"
                                                       title="<?php echo app('translator')->get('Remove'); ?>">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        
                        <div class="col-lg-4">
                            <div class="cart-summary muaadh-cart-summary">
                                <h5 class="cart-summary-title muaadh-cart-summary-title">
                                    <?php echo app('translator')->get('Cart Summary'); ?>
                                </h5>
                                <div class="cart-summary-content">
                                    <?php
                                        // Calculate discount for THIS vendor only (not global)
                                        $vendorDiscount = 0;
                                        $vendorTotalWeight = 0;
                                        $hasMissingWeight = false;
                                        foreach ($vendorData['products'] as $product) {
                                            if (!empty($product['discount'])) {
                                                $total_itemprice = (float)($product['item_price'] ?? 0) * (int)($product['qty'] ?? 1);
                                                $tdiscount = ($total_itemprice * (float)$product['discount']) / 100;
                                                $vendorDiscount += $tdiscount;
                                            }
                                            // حساب الوزن الإجمالي
                                            if (isset($product['row_weight']) && $product['row_weight'] !== null) {
                                                $vendorTotalWeight += (float)$product['row_weight'];
                                            } else {
                                                $hasMissingWeight = true;
                                            }
                                        }
                                        $vendorSubtotal = $vendorData['total'] + $vendorDiscount;

                                        // بيانات الشحن من VendorCartService
                                        $shippingData = $vendorData['shipping_data'] ?? null;
                                        $hasCompleteShippingData = $vendorData['has_complete_data'] ?? false;
                                    ?>

                                    <div class="cart-summary-item muaadh-cart-summary-item d-flex justify-content-between">
                                        <p class="cart-summary-subtitle text-muted fw-semibold mb-0">
                                            <?php echo app('translator')->get('Subtotal'); ?> (<?php echo e($vendorData['count']); ?> <?php echo app('translator')->get('Items'); ?>)
                                        </p>
                                        <p class="cart-summary-price text-primary fw-bold mb-0">
                                            <?php echo e(Product::convertPrice($vendorSubtotal)); ?>

                                        </p>
                                    </div>

                                    
                                    <div class="cart-summary-item muaadh-cart-summary-item d-flex justify-content-between">
                                        <p class="cart-summary-subtitle text-muted fw-semibold mb-0">
                                            <i class="fas fa-weight-hanging me-1"></i> <?php echo app('translator')->get('Total Weight'); ?>
                                        </p>
                                        <p class="cart-summary-price fw-semibold mb-0">
                                            <?php if($vendorTotalWeight > 0): ?>
                                                <?php echo e(number_format($vendorTotalWeight, 2)); ?> kg
                                                <?php if($hasMissingWeight): ?>
                                                    <i class="fas fa-exclamation-triangle text-warning ms-1" title="<?php echo app('translator')->get('Some products are missing weight data'); ?>"></i>
                                                <?php endif; ?>
                                            <?php elseif($hasMissingWeight): ?>
                                                <span class="text-warning"><?php echo app('translator')->get('Incomplete'); ?></span>
                                            <?php else: ?>
                                                -
                                            <?php endif; ?>
                                        </p>
                                    </div>

                                    <?php if($vendorDiscount > 0): ?>
                                    <div class="cart-summary-item muaadh-cart-summary-item d-flex justify-content-between">
                                        <p class="cart-summary-subtitle text-muted fw-semibold mb-0">
                                            <?php echo app('translator')->get('Discount'); ?>
                                        </p>
                                        <p class="cart-summary-price text-danger fw-bold mb-0">
                                            - <?php echo e(Product::convertPrice($vendorDiscount)); ?>

                                        </p>
                                    </div>
                                    <?php endif; ?>

                                    <div class="cart-summary-item muaadh-cart-summary-total d-flex justify-content-between">
                                        <p class="cart-summary-subtitle muaadh-cart-summary-total-label mb-0">
                                            <?php echo app('translator')->get('Total'); ?>
                                        </p>
                                        <p class="cart-summary-price total-cart-price muaadh-cart-summary-total-value mb-0">
                                            <?php echo e(Product::convertPrice($vendorData['total'])); ?>

                                        </p>
                                    </div>

                                    <div class="cart-summary-btn">
                                        
                                        <?php if(auth()->guard()->check()): ?>
                                            <a href="<?php echo e(route('front.checkout.vendor', $vendorId)); ?>" class="template-btn muaadh-checkout-btn">
                                                <i class="fas fa-shopping-cart"></i><?php echo app('translator')->get('Checkout This Vendor'); ?>
                                            </a>
                                        <?php else: ?>
                                            <a href="<?php echo e(route('user.login', ['redirect' => 'cart'])); ?>" class="template-btn muaadh-checkout-btn">
                                                <i class="fas fa-shopping-cart"></i><?php echo app('translator')->get('Checkout This Vendor'); ?>
                                            </a>
                                        <?php endif; ?>

                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>

        <?php else: ?>
            
            <div class="col-xl-12 col-lg-12 col-md-12 col-12">
                <div class="card border py-4">
                    <div class="card-body text-center">
                        <i class="fas fa-shopping-cart fa-4x text-muted mb-3"></i>
                        <h4><?php echo app('translator')->get('Your cart is empty'); ?></h4>
                        <p class="text-muted"><?php echo app('translator')->get('Add some products to your cart'); ?></p>
                        <a href="<?php echo e(route('front.categories')); ?>" class="btn btn-primary mt-3">
                            <?php echo app('translator')->get('Start Shopping'); ?>
                        </a>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>


<script>
$(document).ready(function() {
    // زيادة الكمية
    $(document).on('click', '.quantity-up', function() {
        var $btn = $(this);
        var $wrapper = $btn.closest('.cart-quantity');
        var $qtyInput = $wrapper.find('input[type="text"]');
        var domKey = $wrapper.find('.domkey').val();
        var stock = parseInt($btn.data('stock')) || 999;
        var preordered = parseInt($btn.data('preordered')) || 0;
        var currentQty = parseInt($qtyInput.val()) || 1;

        // التحقق من المخزون
        if (stock > 0 && currentQty >= stock && preordered == 0) {
            if (typeof toastr !== 'undefined') {
                toastr.warning('<?php echo e(__("Stock limit reached")); ?>: ' + stock);
            }
            return;
        }

        var prodId = $wrapper.find('.prodid').val();
        var itemId = $wrapper.find('.itemid').val();
        var sizeQty = $wrapper.find('.size_qty').val() || '';
        var sizePrice = $wrapper.find('.size_price').val() || 0;

        $.ajax({
            url: '/addbyone',
            type: 'GET',
            dataType: 'json',
            data: {
                id: prodId,
                itemid: itemId,
                size_qty: sizeQty,
                size_price: sizePrice
            },
            success: function(resp) {
                if (resp === 0 || resp === '0') {
                    if (typeof toastr !== 'undefined') {
                        toastr.error('<?php echo e(__("Cannot increase quantity")); ?>');
                    }
                    return;
                }
                $qtyInput.val(resp[1]);
                $('#prc' + domKey).html(resp[2]);
                $('.total-cart-price').html(resp[0]);
                if (typeof toastr !== 'undefined') {
                    toastr.success('<?php echo e(__("Quantity updated")); ?>');
                }
            },
            error: function() {
                if (typeof toastr !== 'undefined') {
                    toastr.error('<?php echo e(__("Error occurred")); ?>');
                }
            }
        });
    });

    // إنقاص الكمية
    $(document).on('click', '.quantity-down', function() {
        var $btn = $(this);
        var $wrapper = $btn.closest('.cart-quantity');
        var $qtyInput = $wrapper.find('input[type="text"]');
        var domKey = $wrapper.find('.domkey').val();
        var minQty = parseInt($wrapper.find('.minimum_qty').val()) || 1;
        var currentQty = parseInt($qtyInput.val()) || 1;

        // التحقق من الحد الأدنى
        if (currentQty <= minQty) {
            if (typeof toastr !== 'undefined') {
                toastr.warning('<?php echo e(__("Minimum quantity is")); ?> ' + minQty);
            }
            return;
        }

        var prodId = $wrapper.find('.prodid').val();
        var itemId = $wrapper.find('.itemid').val();
        var sizeQty = $wrapper.find('.size_qty').val() || '';
        var sizePrice = $wrapper.find('.size_price').val() || 0;

        $.ajax({
            url: '/reducebyone',
            type: 'GET',
            dataType: 'json',
            data: {
                id: prodId,
                itemid: itemId,
                size_qty: sizeQty,
                size_price: sizePrice
            },
            success: function(resp) {
                if (resp === 0 || resp === '0') {
                    if (typeof toastr !== 'undefined') {
                        toastr.warning('<?php echo e(__("Cannot decrease quantity")); ?>');
                    }
                    return;
                }
                $qtyInput.val(resp[1]);
                $('#prc' + domKey).html(resp[2]);
                $('.total-cart-price').html(resp[0]);
                if (typeof toastr !== 'undefined') {
                    toastr.success('<?php echo e(__("Quantity updated")); ?>');
                }
            },
            error: function() {
                if (typeof toastr !== 'undefined') {
                    toastr.error('<?php echo e(__("Error occurred")); ?>');
                }
            }
        });
    });
});
</script>
<?php endif; ?> 
<?php /**PATH C:\Users\hp\Herd\new\resources\views/frontend/ajax/cart-page.blade.php ENDPATH**/ ?>