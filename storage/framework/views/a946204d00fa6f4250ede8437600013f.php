<?php $__env->startSection('title', ($catalog->name ?? $catalog->shortName ?? $catalog->code) . ' - ' . __('Categories')); ?>

<?php $__env->startSection('content'); ?>
<style>
    .compact-breadcrumb-wrapper {
        background: #fff;
        border-radius: 0.5rem;
        padding: 0.5rem 0.75rem;
        box-shadow: 0 1px 3px rgba(0,0,0,0.06);
        border: 1px solid #e9ecef;
    }
    .compact-breadcrumb {
        margin: 0;
        padding: 0;
        background: transparent;
        font-size: 0.85rem;
    }
    .compact-breadcrumb .breadcrumb-item {
        padding: 0;
    }
    .compact-breadcrumb .breadcrumb-item + .breadcrumb-item::before {
        content: "›";
        color: #6c757d;
        padding: 0 0.4rem;
        font-size: 1rem;
    }
    .compact-breadcrumb a {
        color: #495057;
        text-decoration: none;
        transition: color 0.2s ease;
        display: inline-flex;
        align-items: center;
    }
    .compact-breadcrumb a:hover {
        color: #0d6efd;
    }
    .compact-breadcrumb .active span {
        color: #0d6efd;
        font-weight: 600;
    }
    .compact-breadcrumb i {
        font-size: 0.875rem;
    }
    @media (max-width: 576px) {
        .compact-breadcrumb-wrapper {
            padding: 0.4rem 0.6rem;
            border-radius: 0.4rem;
        }
        .compact-breadcrumb {
            font-size: 0.75rem;
            flex-wrap: nowrap;
            overflow-x: auto;
            scrollbar-width: none;
            -ms-overflow-style: none;
        }
        .compact-breadcrumb::-webkit-scrollbar {
            display: none;
        }
        .compact-breadcrumb .breadcrumb-item {
            white-space: nowrap;
        }
        .compact-breadcrumb .breadcrumb-item + .breadcrumb-item::before {
            padding: 0 0.3rem;
            font-size: 0.9rem;
        }
        .compact-breadcrumb i {
            font-size: 0.8rem;
        }
    }
</style>

<div class="container py-3">
    
    <div class="compact-breadcrumb-wrapper mb-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb compact-breadcrumb mb-0">
                
                <li class="breadcrumb-item">
                    <a href="<?php echo e(route('front.index')); ?>">
                        <i class="fas fa-home"></i>
                        <span class="d-none d-sm-inline ms-1"><?php echo e(__('Home')); ?></span>
                    </a>
                </li>

                
                <li class="breadcrumb-item">
                    <a href="<?php echo e(route('catlogs.index', $brand->name)); ?>">
                        <?php echo e($brand->name); ?>

                    </a>
                </li>

                
                <?php if($vin): ?>
                    <li class="breadcrumb-item d-none d-sm-block">
                        <a href="<?php echo e(route('tree.level1', [
                            'brand' => $brand->name,
                            'catalog' => $catalog->code,
                            'vin' => $vin
                        ])); ?>">
                            <i class="fas fa-car me-1"></i>
                            <?php echo e(Str::limit($vin, 12)); ?>

                        </a>
                    </li>
                <?php endif; ?>

                
                <li class="breadcrumb-item active" aria-current="page">
                    <span><?php echo e($catalog->shortName ?? $catalog->name ?? $catalog->code); ?></span>
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
                'uniqueId' => 'level1',
                'showAttributes' => false
            ], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
        </div>
    </div>

    
    <div class="row g-3 g-md-4 mb-5">
        <?php $__empty_1 = true; $__currentLoopData = $categories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $cat): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <div class="col-6 col-sm-6 col-md-4 col-lg-3">
                <a href="<?php echo e(route('tree.level2', [
                    'brand' => $brand->name,
                    'catalog' => $catalog->code,
                    'key1' => $cat->full_code,
                    'vin' => $vin
                ])); ?>" class="text-decoration-none">
                    <div class="card border-0 shadow-sm h-100 hover-lift transition">
                        
                        <div class="position-relative overflow-hidden rounded-top" style="padding-top: 75%;">
                            <img class="position-absolute top-0 start-0 w-100 h-100 object-fit-cover"
                                 src="<?php echo e($cat->thumbnail ? Storage::url($cat->thumbnail) : asset('assets/images/no-image.png')); ?>"
                                 alt="<?php echo e($cat->full_code); ?>"
                                 loading="lazy"
                                 onerror="this.onerror=null; this.src='<?php echo e(asset('assets/images/no-image.png')); ?>';">
                        </div>

                        
                        <div class="card-body p-2 p-md-3 text-center">
                            <h6 class="product-title text-dark fw-bold text-uppercase mb-1 fs-6 fs-md-5">
                                <?php echo e($cat->formatted_code ?? $cat->full_code); ?>

                            </h6>
                            <?php ($catLabel = app()->getLocale() === 'ar' ? $cat->label_ar : str_replace('-', ' ', $cat->slug ?? '')); ?>
                            <?php if(!empty($catLabel)): ?>
                                <p class="text-muted small mb-0 d-none d-md-block text-uppercase"><?php echo e($catLabel); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                </a>
            </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <div class="col-12">
                <div class="alert alert-info text-center">
                    <i class="fas fa-info-circle me-2"></i>
                    <?php echo e(__('No categories available')); ?>

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
</style>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.front3', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\Users\hp\Herd\new\resources\views/catalog/level1.blade.php ENDPATH**/ ?>