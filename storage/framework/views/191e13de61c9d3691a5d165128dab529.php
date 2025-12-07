<?php $__env->startSection('content'); ?>

<!-- Hero Search Section -->
<section class="muaadh-hero">
    <div class="container">
        <div class="muaadh-hero-content">
            <h1 class="muaadh-hero-title"><?php echo app('translator')->get('Search By Part Number Or Name'); ?></h1>

            <!-- Search Box -->
            <div class="muaadh-hero-search">
                <?php echo $__env->make('includes.frontend.search-part-ajax', ['uniqueId' => 'home'], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
            </div>

            <p class="muaadh-hero-subtitle">
                <?php echo app('translator')->get("If you don't know the correct part number for your vehicle, search below using your VIN, the category tree, or the catalogues."); ?>
            </p>

            <!-- VIN Search Button -->
            <div class="muaadh-hero-actions">
                <button type="button" class="muaadh-btn-vin" data-bs-toggle="modal" data-bs-target="#vinSearchModalHome">
                    <i class="fas fa-car"></i>
                    <?php echo app('translator')->get('Search by VIN'); ?>
                </button>
                <p class="muaadh-hero-hint">
                    <?php echo app('translator')->get('Search for spare parts inside the vehicle by VIN number'); ?>
                </p>
            </div>
        </div>
    </div>
</section>

<!-- Brands Section -->
<section class="muaadh-section muaadh-section-gray">
    <div class="container">
        <div class="muaadh-section-header">
            <span class="muaadh-badge-primary"><?php echo app('translator')->get('Genuine Parts Catalogues'); ?></span>
            <h2 class="muaadh-section-title"><?php echo app('translator')->get('Explore genuine OEM parts catalogues'); ?></h2>
            <p class="muaadh-section-desc"><?php echo app('translator')->get('Select your vehicle brand to find the perfect parts'); ?></p>
        </div>

        <div class="muaadh-brands-grid">
            <?php $__currentLoopData = DB::table('brands')->get(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $brand): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <a href="<?php echo e(route('catlogs.index', $brand->name)); ?>" class="muaadh-brand-card">
                    <div class="muaadh-brand-img">
                        <img src="<?php echo e(asset('assets/images/brand/' . $brand->photo)); ?>" alt="<?php echo e($brand->name); ?>" loading="lazy">
                    </div>
                    <span class="muaadh-brand-name"><?php echo e($brand->name); ?></span>
                </a>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
    </div>
</section>

<!-- Categories Section -->
<section class="muaadh-section">
    <div class="container">
        <div class="muaadh-section-header">
            <span class="muaadh-badge-primary"><?php echo app('translator')->get('Browse Categories'); ?></span>
            <h2 class="muaadh-section-title"><?php echo app('translator')->get('Shop by Category'); ?></h2>
            <p class="muaadh-section-desc"><?php echo app('translator')->get('Find exactly what you need from our extensive catalog'); ?></p>
        </div>

        <div class="muaadh-categories-grid">
            <?php $__currentLoopData = $featured_categories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $fcategory): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <a href="<?php echo e(route('front.category', $fcategory->slug)); ?>" class="muaadh-category-card">
                    <div class="muaadh-category-img">
                        <img src="<?php echo e(asset('assets/images/categories/' . $fcategory->image)); ?>" alt="<?php echo e($fcategory->name); ?>" loading="lazy">
                        <span class="muaadh-category-count"><?php echo e($fcategory->products_count); ?></span>
                    </div>
                    <div class="muaadh-category-info">
                        <h6 class="muaadh-category-name"><?php echo e($fcategory->name); ?></h6>
                        <span class="muaadh-category-products"><?php echo e($fcategory->products_count); ?> <?php echo app('translator')->get('Products'); ?></span>
                    </div>
                </a>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
    </div>
</section>

<!-- Services Section -->
<section class="muaadh-section muaadh-section-gray">
    <div class="container">
        <div class="muaadh-services-grid">
            <?php $__currentLoopData = DB::table('services')->get(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $service): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div class="muaadh-service-card">
                    <div class="muaadh-service-icon">
                        <img src="<?php echo e(asset('assets/images/services/' . $service->photo)); ?>" alt="<?php echo e($service->title); ?>">
                    </div>
                    <div class="muaadh-service-info">
                        <h6 class="muaadh-service-title"><?php echo e($service->title); ?></h6>
                        <p class="muaadh-service-desc"><?php echo e($service->details); ?></p>
                    </div>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
    </div>
</section>

<!-- VIN Search Modal -->
<div class="modal fade" id="vinSearchModalHome" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content muaadh-modal">
            <div class="modal-header muaadh-modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-car me-2"></i>
                    <?php echo app('translator')->get('Search by VIN'); ?>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <?php echo $__env->make('includes.frontend.search-vin-ajax', ['uniqueId' => 'homeModal'], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
            </div>
        </div>
    </div>
</div>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.front', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\Users\hp\Herd\new\resources\views/frontend/theme/home4.blade.php ENDPATH**/ ?>