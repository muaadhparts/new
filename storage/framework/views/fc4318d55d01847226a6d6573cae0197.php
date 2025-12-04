<?php $__env->startSection('title', $brand->name . ' - ' . __('Catalogs')); ?>

<?php $__env->startSection('content'); ?>
<div class="container py-4">
    <?php if(session()->has('error')): ?>
        <div class="alert alert-danger">
            <?php echo e(session('error')); ?>

        </div>
    <?php endif; ?>

    
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item">
                <a href="<?php echo e(route('front.index')); ?>" class="text-decoration-none">
                    <i class="fas fa-home"></i> <?php echo e(__('Home')); ?>

                </a>
            </li>
            <li class="breadcrumb-item active" aria-current="page">
                <?php echo e(strtoupper($brand->name)); ?>

            </li>
        </ol>
    </nav>

    
    <div class="row mb-4">
        <div class="col-12">
            <?php echo $__env->make('includes.frontend.search-vin-ajax', ['uniqueId' => 'catlogs'], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
        </div>
    </div>

    
    <form method="GET" action="<?php echo e(route('catalog.index', $brand->name)); ?>" class="row mb-4 g-3" id="catalogFilters">
        
        <div class="col-md-3">
            <input type="text"
                   name="search"
                   class="form-control"
                   placeholder="<?php echo e(__('Search')); ?>"
                   value="<?php echo e($searchName); ?>">
        </div>

        
        <?php if($regionOptions && count($regionOptions) > 0): ?>
            <div class="col-md-3">
                <select name="region" class="form-select" onchange="this.form.submit()">
                    <?php $__currentLoopData = $regionOptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($value); ?>" <?php echo e($region == $value ? 'selected' : ''); ?>>
                            <?php echo e($label); ?>

                        </option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>
        <?php endif; ?>

        
        <div class="col-md-3">
            <select name="year" class="form-select" onchange="this.form.submit()">
                <option value=""><?php echo e(__('Filter by Year')); ?></option>
                <?php $__currentLoopData = $years; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $year): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <option value="<?php echo e($year); ?>" <?php echo e($searchYear == $year ? 'selected' : ''); ?>>
                        <?php echo e($year); ?>

                    </option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>
        </div>

        
        <div class="col-md-3">
            <button type="submit" class="btn btn-primary w-100">
                <i class="fas fa-search me-1"></i> <?php echo e(__('Search')); ?>

            </button>
        </div>
    </form>

    
    <div class="row g-4">
        <?php $__empty_1 = true; $__currentLoopData = $catalogs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $catalog): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <div class="col-12 col-sm-6 col-md-4 col-xl-3">
                <a href="<?php echo e(route('catalog.level1', [
                    'brand' => $brand->name,
                    'catalog' => $catalog->code,
                ])); ?>" class="text-decoration-none">
                    <div class="card border-0 shadow-sm h-100 hover-lift">
                        <div class="position-relative overflow-hidden rounded-top" style="padding-top: 75%;">
                            <img class="position-absolute top-0 start-0 w-100 h-100 object-fit-cover"
                                 src="<?php echo e(Storage::url($catalog->largeImagePath)); ?>"
                                 alt="<?php echo e(getLocalizedLabel($catalog)); ?>"
                                 loading="lazy"
                                 onerror="this.onerror=null; this.src='<?php echo e(asset('assets/images/no-image.png')); ?>';">
                        </div>
                        <div class="card-body p-3 text-center">
                            <h6 class="card-title text-dark fw-bold mb-2">
                                <?php echo e(getLocalizedLabel($catalog)); ?>

                            </h6>
                            <p class="text-muted small mb-1"><?php echo e($catalog->code); ?></p>
                            <p class="text-muted small mb-0">
                                <?php echo e(formatYearRange($catalog->beginYear, $catalog->endYear)); ?>

                            </p>
                        </div>
                    </div>
                </a>
            </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <div class="col-12">
                <div class="alert alert-info text-center">
                    <h5><i class="fas fa-info-circle me-2"></i><?php echo e(__('No catalogs available')); ?></h5>
                    <p class="mb-0"><?php echo e(__('No catalogs match the selected search criteria.')); ?></p>
                </div>
            </div>
        <?php endif; ?>
    </div>

    
    <?php if($catalogs->hasPages()): ?>
        <div class="d-flex justify-content-center my-5">
            <?php echo e($catalogs->appends(request()->query())->links('includes.frontend.pagination')); ?>

        </div>
    <?php endif; ?>
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

<?php echo $__env->make('layouts.front3', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\Users\hp\Herd\new\resources\views/catalog/index.blade.php ENDPATH**/ ?>