

<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag; ?>
<?php foreach($attributes->onlyProps([
    'product' => null,
    'mp' => null,
    'displayMode' => 'inline',
    'showSku' => true,
    'showBrand' => true,
    'showQualityBrand' => true,
    'showVendor' => true,
    'showStock' => true
]) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $attributes = $attributes->exceptProps([
    'product' => null,
    'mp' => null,
    'displayMode' => 'inline',
    'showSku' => true,
    'showBrand' => true,
    'showQualityBrand' => true,
    'showVendor' => true,
    'showStock' => true
]); ?>
<?php foreach (array_filter(([
    'product' => null,
    'mp' => null,
    'displayMode' => 'inline',
    'showSku' => true,
    'showBrand' => true,
    'showQualityBrand' => true,
    'showVendor' => true,
    'showStock' => true
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $__defined_vars = get_defined_vars(); ?>
<?php foreach ($attributes as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
} ?>
<?php unset($__defined_vars); ?>

<?php
    // Safety check: ensure product exists
    if (!$product) {
        return;
    }

    // Get merchant product if not provided
    if (!$mp) {
        $mp = $product->merchantProducts()
            ->where('status', 1)
            ->whereHas('user', function ($user) {
                $user->where('is_vendor', 2);
            })
            ->orderByRaw('CASE WHEN (stock IS NULL OR stock = 0) THEN 1 ELSE 0 END ASC')
            ->orderBy('price')
            ->first();
    }

    // Extract all display values (using localized names)
    $sku = $product->sku ?? null;
    $brandName = $product->brand ? $product->brand->localized_name : null;
    $qualityBrand = ($mp && $mp->qualityBrand) ? $mp->qualityBrand : null;
    $qualityBrandName = $qualityBrand ? $qualityBrand->localized_name : null;
    $qualityBrandLogo = $qualityBrand ? $qualityBrand->logo_url : null;
    $vendorName = ($mp && $mp->user) ? $mp->user->shop_name : null;
    $stock = $mp ? $mp->stock : null;

    // Format stock display with colors
    if ($stock === null || $stock === '') {
        $stockText = __('Unlimited');
        $stockClass = 'text-success';
        $stockBadgeClass = 'bg-success';
    } elseif ($stock == 0) {
        $stockText = __('Out Of Stock');
        $stockClass = 'text-danger';
        $stockBadgeClass = 'bg-danger';
    } else {
        $stockText = $stock . ' ' . __('Available');
        $stockClass = 'text-primary';
        $stockBadgeClass = 'bg-primary';
    }
?>

<?php if($displayMode === 'badges'): ?>
    
    <div class="product-info-badges d-flex flex-wrap gap-2 mb-2">
        <?php if($showSku && $sku): ?>
            <span class="badge bg-secondary text-white">
                <i class="fas fa-barcode me-1"></i><?php echo e($sku); ?>

            </span>
        <?php endif; ?>

        <?php if($showBrand && $brandName): ?>
            <span class="badge bg-light text-dark">
                <i class="fas fa-tag me-1"></i><?php echo e($brandName); ?>

            </span>
        <?php endif; ?>

        <?php if($showQualityBrand && $qualityBrandName): ?>
            <span class="badge bg-light text-dark d-inline-flex align-items-center">
                <?php if($qualityBrandLogo): ?>
                    <img src="<?php echo e($qualityBrandLogo); ?>" alt="<?php echo e($qualityBrandName); ?>" class="quality-brand-logo me-1" style="max-height: 20px; max-width: 50px; object-fit: contain;">
                <?php else: ?>
                    <i class="fas fa-certificate me-1"></i>
                <?php endif; ?>
                <?php echo e($qualityBrandName); ?>

            </span>
        <?php endif; ?>

        <?php if($showVendor && $vendorName): ?>
            <span class="badge bg-light text-dark">
                <i class="fas fa-store me-1"></i><?php echo e($vendorName); ?>

            </span>
        <?php endif; ?>

        <?php if($showStock): ?>
            <span class="badge <?php echo e($stockBadgeClass); ?> text-white">
                <i class="fas fa-boxes me-1"></i><?php echo e($stockText); ?>

            </span>
        <?php endif; ?>
    </div>

<?php elseif($displayMode === 'list'): ?>
    
    <ul class="product-info-list list-unstyled mb-2">
        <?php if($showSku && $sku): ?>
            <li class="small text-muted">
                <strong><?php echo e(__('SKU')); ?>:</strong> <span class="font-monospace"><?php echo e($sku); ?></span>
            </li>
        <?php endif; ?>

        <?php if($showBrand && $brandName): ?>
            <li class="small">
                <strong><?php echo e(__('Brand')); ?>:</strong> <?php echo e($brandName); ?>

            </li>
        <?php endif; ?>

        <?php if($showQualityBrand && $qualityBrandName): ?>
            <li class="small d-flex align-items-center">
                <strong><?php echo e(__('Quality Brand')); ?>:</strong>
                <?php if($qualityBrandLogo): ?>
                    <img src="<?php echo e($qualityBrandLogo); ?>" alt="<?php echo e($qualityBrandName); ?>" class="quality-brand-logo mx-1" style="max-height: 22px; max-width: 60px; object-fit: contain;">
                <?php endif; ?>
                <span class="ms-1"><?php echo e($qualityBrandName); ?></span>
            </li>
        <?php endif; ?>

        <?php if($showVendor && $vendorName): ?>
            <li class="small">
                <strong><?php echo e(__('Vendor')); ?>:</strong> <?php echo e($vendorName); ?>

            </li>
        <?php endif; ?>

        <?php if($showStock): ?>
            <li class="small <?php echo e($stockClass); ?>">
                <strong><?php echo e(__('Stock')); ?>:</strong> <?php echo e($stockText); ?>

            </li>
        <?php endif; ?>
    </ul>

<?php elseif($displayMode === 'modal'): ?>
    
    <div class="product-info-modal mb-3">
        <table class="table table-sm table-borderless mb-0">
            <tbody>
                <?php if($showSku && $sku): ?>
                    <tr>
                        <td class="text-muted" style="width: 100px;"><i class="fas fa-barcode me-1"></i><?php echo e(__('SKU')); ?></td>
                        <td><code><?php echo e($sku); ?></code></td>
                    </tr>
                <?php endif; ?>

                <?php if($showBrand && $brandName): ?>
                    <tr>
                        <td class="text-muted"><i class="fas fa-tag me-1"></i><?php echo e(__('Brand')); ?></td>
                        <td><?php echo e($brandName); ?></td>
                    </tr>
                <?php endif; ?>

                <?php if($showQualityBrand && $qualityBrandName): ?>
                    <tr>
                        <td class="text-muted"><i class="fas fa-certificate me-1"></i><?php echo e(__('Quality')); ?></td>
                        <td class="d-flex align-items-center">
                            <?php if($qualityBrandLogo): ?>
                                <img src="<?php echo e($qualityBrandLogo); ?>" alt="<?php echo e($qualityBrandName); ?>" class="quality-brand-logo me-2" style="max-height: 22px; max-width: 60px; object-fit: contain;">
                            <?php endif; ?>
                            <?php echo e($qualityBrandName); ?>

                        </td>
                    </tr>
                <?php endif; ?>

                <?php if($showVendor && $vendorName): ?>
                    <tr>
                        <td class="text-muted"><i class="fas fa-store me-1"></i><?php echo e(__('Vendor')); ?></td>
                        <td><?php echo e($vendorName); ?></td>
                    </tr>
                <?php endif; ?>

                <?php if($showStock): ?>
                    <tr>
                        <td class="text-muted"><i class="fas fa-boxes me-1"></i><?php echo e(__('Stock')); ?></td>
                        <td class="<?php echo e($stockClass); ?>"><?php echo e($stockText); ?></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

<?php else: ?>
    
    <div class="product-info-inline small text-muted mb-1">
        <?php if($showSku && $sku): ?>
            <span class="me-2">
                <i class="fas fa-barcode me-1"></i><span class="font-monospace"><?php echo e($sku); ?></span>
            </span>
        <?php endif; ?>

        <?php if($showBrand && $brandName): ?>
            <span class="me-2">
                <i class="fas fa-tag me-1"></i><?php echo e($brandName); ?>

            </span>
        <?php endif; ?>

        <?php if($showQualityBrand && $qualityBrandName): ?>
            <span class="me-2 d-inline-flex align-items-center">
                <?php if($qualityBrandLogo): ?>
                    <img src="<?php echo e($qualityBrandLogo); ?>" alt="<?php echo e($qualityBrandName); ?>" class="quality-brand-logo me-1" style="max-height: 18px; max-width: 45px; object-fit: contain;">
                <?php else: ?>
                    <i class="fas fa-certificate me-1"></i>
                <?php endif; ?>
                <?php echo e($qualityBrandName); ?>

            </span>
        <?php endif; ?>

        <?php if($showVendor && $vendorName): ?>
            <span class="me-2">
                <i class="fas fa-store me-1"></i><?php echo e($vendorName); ?>

            </span>
        <?php endif; ?>

        <?php if($showStock): ?>
            <span class="<?php echo e($stockClass); ?>">
                <i class="fas fa-boxes me-1"></i><?php echo e($stockText); ?>

            </span>
        <?php endif; ?>
    </div>
<?php endif; ?>
<?php /**PATH C:\Users\hp\Herd\new\resources\views/components/product-info.blade.php ENDPATH**/ ?>