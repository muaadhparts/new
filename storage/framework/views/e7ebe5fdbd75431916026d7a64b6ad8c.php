
<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag; ?>
<?php foreach($attributes->onlyProps([
    'product' => null,
    'item' => null, // for cart items format
    'vendorId' => null,
    'merchantProductId' => null,
    'showSku' => false, // SKU يعرض في product-info component
    'target' => '_self',
    'class' => '',
    'nameClass' => '',
    'skuClass' => 'text-muted small',
    'useSearchRoute' => null // null = auto detect, true = force search route, false = force product route
]) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $attributes = $attributes->exceptProps([
    'product' => null,
    'item' => null, // for cart items format
    'vendorId' => null,
    'merchantProductId' => null,
    'showSku' => false, // SKU يعرض في product-info component
    'target' => '_self',
    'class' => '',
    'nameClass' => '',
    'skuClass' => 'text-muted small',
    'useSearchRoute' => null // null = auto detect, true = force search route, false = force product route
]); ?>
<?php foreach (array_filter(([
    'product' => null,
    'item' => null, // for cart items format
    'vendorId' => null,
    'merchantProductId' => null,
    'showSku' => false, // SKU يعرض في product-info component
    'target' => '_self',
    'class' => '',
    'nameClass' => '',
    'skuClass' => 'text-muted small',
    'useSearchRoute' => null // null = auto detect, true = force search route, false = force product route
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $__defined_vars = get_defined_vars(); ?>
<?php foreach ($attributes as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
} ?>
<?php unset($__defined_vars); ?>

<?php
    // Handle different data formats (direct product object vs cart item format)
    $productData = $product ?? $item;

    if (!$productData) {
        return;
    }

    // Extract data based on format
    if (isset($productData['item'])) {
        // Cart item format: $product['item']['name']
        $sku = $productData['item']['sku'] ?? '';
        $slug = $productData['item']['slug'] ?? '';
        $userId = $vendorId ?? $productData['item']['user_id'] ?? $productData['user_id'] ?? 0;
        // For merchant_product_id: prioritize explicit prop, then try to infer
        $mpId = $merchantProductId;
        if (!$mpId) {
            // Try to get MP ID if productData is actually a MerchantProduct
            $mpId = ($productData instanceof \App\Models\MerchantProduct)
                ? $productData->id
                : ($productData['item']['id'] ?? $productData['id'] ?? null);
        }
        // Use centralized helper for localized name
        $displayName = getLocalizedProductName($productData['item']);
    } else {
        // Direct product object format: $product->name
        $sku = $productData->sku ?? $productData['sku'] ?? '';
        $slug = $productData->slug ?? $productData['slug'] ?? '';
        $userId = $vendorId ?? $productData->user_id ?? $productData['user_id'] ?? 0;
        // For merchant_product_id: prioritize explicit prop
        $mpId = $merchantProductId;
        if (!$mpId) {
            // Check if this is a MerchantProduct or need to fetch one
            if ($productData instanceof \App\Models\MerchantProduct) {
                $mpId = $productData->id;
            } elseif ($productData instanceof \App\Models\Product && $userId) {
                // Fetch first active MP for this product and vendor
                $mp = $productData->merchantProducts()->where('user_id', $userId)->where('status', 1)->first();
                $mpId = $mp->id ?? null;
            }
        }
        // Use centralized helper for localized name
        $displayName = getLocalizedProductName($productData);
    }

    // SKU display
    $displaySku = !empty($sku) ? $sku : '-';

    // Determine which route to use
    $shouldUseSearchRoute = $useSearchRoute;
    if ($shouldUseSearchRoute === null) {
        // Auto-detect: use search route unless we're in specific contexts
        $currentRouteName = request()->route() ? request()->route()->getName() : '';
        $currentPath = request()->path();

        // Use search route (result/{sku}) for admin pages EXCEPT orders/invoices
        $isAdminPage = str_starts_with($currentPath, 'admin/');
        $isAdminOrderOrInvoice = $isAdminPage && (
            str_contains($currentPath, '/order') ||
            str_contains($currentPath, '/invoice')
        );

        // Keep product route for:
        // 1. search-results-page and category pages (front.category)
        // 2. vendor dashboard (all vendor pages)
        // 3. admin orders and invoices (merchant-specific pages)
        // 4. cart, checkout, and order pages (user)
        $keepProductRoute =
            in_array($currentRouteName, ['search.result', 'front.category']) ||
            str_starts_with($currentPath, 'vendor/') ||
            $isAdminOrderOrInvoice ||
            str_starts_with($currentPath, 'user/order') ||
            str_contains($currentPath, '/cart') ||
            str_contains($currentPath, '/checkout');

        $shouldUseSearchRoute = !$keepProductRoute;
    }

    // Route generation
    if ($shouldUseSearchRoute && !empty($sku)) {
        // Use search route: result/{sku}
        $productRoute = route('search.result', $sku);
    } else {
        // Use product details route
        $productRoute = !empty($slug) && $userId && $mpId
            ? route('front.product', ['slug' => $slug, 'vendor_id' => $userId, 'merchant_product_id' => $mpId])
            : '#';
    }
?>

<div class="<?php echo e($class); ?>">
    <?php if(!empty($slug)): ?>
        <a href="<?php echo e($productRoute); ?>" target="<?php echo e($target); ?>" class="<?php echo e($nameClass); ?>">
            <?php echo e($displayName); ?>

        </a>
    <?php else: ?>
        <span class="<?php echo e($nameClass); ?>"><?php echo e($displayName); ?></span>
    <?php endif; ?>

    <?php if($showSku): ?>
        <br>
        <small class="<?php echo e($skuClass); ?>">
            <?php if(!empty($slug)): ?>
                <a href="<?php echo e($productRoute); ?>" target="<?php echo e($target); ?>">
                    <?php echo app('translator')->get('SKU'); ?>: <?php echo e($displaySku); ?>

                </a>
            <?php else: ?>
                <?php echo app('translator')->get('SKU'); ?>: <?php echo e($displaySku); ?>

            <?php endif; ?>
        </small>
    <?php endif; ?>
</div>

<style>
/* Product Name Component Styles */
.product-name-component a {
    color: var(--dark-color);
    text-decoration: none;
    transition: color var(--transition-fast);
    font-weight: 600;
}

.product-name-component a:hover {
    color: var(--primary-color);
}

.product-name-component small a {
    color: #6c757d;
    font-weight: 500;
}

.product-name-component small a:hover {
    color: var(--primary-color);
    text-decoration: underline;
}

/* RTL Support */
[dir="rtl"] .product-name-component {
    text-align: right;
}
</style><?php /**PATH C:\Users\hp\Herd\new\resources\views/components/product-name.blade.php ENDPATH**/ ?>