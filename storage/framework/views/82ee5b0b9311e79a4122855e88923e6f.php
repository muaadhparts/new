<?php $__env->startSection('title', ($parentCategory->label ?? $parentCategory->full_code) . ' - ' . __('Subcategories')); ?>

<?php $__env->startSection('content'); ?>
<div class="container py-3">
    
    <div class="product-nav-wrapper mb-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb text-uppercase mb-0 flex-wrap">
                <li class="breadcrumb-item">
                    <a class="text-black text-decoration-none" href="<?php echo e(route('front.index')); ?>">
                        <i class="fas fa-home d-md-none"></i>
                        <span class="d-none d-md-inline"><?php echo e(__('Home')); ?></span>
                    </a>
                </li>
                <li class="breadcrumb-item">
                    <a class="text-black text-decoration-none" href="<?php echo e(route('catlogs.index', $brand->name)); ?>">
                        <?php echo e(strtoupper($brand->name)); ?>

                    </a>
                </li>
                <li class="breadcrumb-item">
                    <a class="text-black text-decoration-none" href="<?php echo e(route('tree.level1', [
                        'brand' => $brand->name,
                        'catalog' => $catalog->code,
                        'vin' => $vin
                    ])); ?>">
                        <?php echo e(strtoupper($catalog->shortName ?? $catalog->name ?? $catalog->code)); ?>

                    </a>
                </li>
                <?php if($vin): ?>
                    <li class="breadcrumb-item">
                        <span class="text-muted">
                            <i class="fas fa-car me-1"></i>
                            <span class="d-none d-md-inline"><?php echo e($vin); ?></span>
                            <span class="d-md-none">VIN</span>
                        </span>
                    </li>
                <?php endif; ?>
                <li class="breadcrumb-item active text-primary" aria-current="page">
                    <strong><?php echo e(strtoupper($parentCategory->full_code)); ?></strong>
                </li>
            </ol>
        </nav>
    </div>

    
    <div class="row mb-4">
        <div class="col-12">
            
            <div class="mb-3">
                <?php echo $__env->make('catalog.partials.specs-modal', [
                    'catalog' => $catalog,
                    'filters' => $filters,
                    'selectedFilters' => $selectedFilters,
                    'isVinMode' => $isVinMode
                ], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
            </div>

            
            <?php echo $__env->make('catalog.partials.chips-bar', ['chips' => $chips], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

            
            <?php echo $__env->make('includes.frontend.vehicle-search-ajax', [
                'catalog' => $catalog,
                'uniqueId' => 'level2',
                'showAttributes' => false
            ], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
        </div>
    </div>

    
    <div class="row g-3 g-md-4 mb-5">
        <?php $__empty_1 = true; $__currentLoopData = $categories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $subcat): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <div class="col-6 col-sm-6 col-md-4 col-lg-3">
                <a href="<?php echo e(route('tree.level3', [
                    'brand' => $brand->name,
                    'catalog' => $catalog->code,
                    'key1' => $key1,
                    'key2' => $subcat->full_code,
                    'vin' => $vin
                ])); ?>" class="text-decoration-none">
                    <div class="card border-0 shadow-sm h-100 hover-lift transition">
                        <div class="position-relative overflow-hidden rounded-top" style="padding-top: 75%;">
                            <img class="position-absolute top-0 start-0 w-100 h-100 object-fit-cover"
                                 src="<?php echo e($subcat->thumbnail ? Storage::url($subcat->thumbnail) : asset('assets/images/no-image.png')); ?>"
                                 alt="<?php echo e($subcat->full_code); ?>"
                                 loading="lazy"
                                 onerror="this.onerror=null; this.src='<?php echo e(asset('assets/images/no-image.png')); ?>';">
                        </div>
                        <div class="card-body p-2 p-md-3 text-center">
                            <h6 class="product-title text-dark fw-bold text-uppercase mb-1 fs-6 fs-md-5">
                                <?php echo e($subcat->full_code); ?>

                            </h6>
                            <?php if($subcat->label): ?>
                                <p class="text-muted small mb-0 d-none d-md-block"><?php echo e($subcat->label); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                </a>
            </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <div class="col-12">
                <div class="alert alert-info text-center">
                    <i class="fas fa-info-circle me-2"></i>
                    <?php echo e(__('No subcategories available')); ?>

                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.hover-lift {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}
.hover-lift:hover {
    transform: translateY(-5px);
    box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.15) !important;
}
.object-fit-cover {
    object-fit: cover;
}
@media (max-width: 576px) {
    .breadcrumb-item + .breadcrumb-item::before {
        padding: 0 0.25rem;
    }
}
</style>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.front3', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\Users\hp\Herd\new\resources\views/catalog/level2.blade.php ENDPATH**/ ?>