<?php $__env->startSection('title', $brand->name . ' - ' . __('Catalogs')); ?>

<?php $__env->startSection('content'); ?>
<div class="container">
    <?php if(session()->has('error')): ?>
        <div class="alert alert-danger">
            <?php echo e(session('error')); ?>

        </div>
    <?php endif; ?>

    <div class="row gy-4 gy-lg-5 mt-4">
        
        <?php echo $__env->make('includes.frontend.search-vin-ajax', ['uniqueId' => 'catlogs'], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

        <div>
            
            <form method="GET" action="<?php echo e(route('catlogs.index', $brand->name)); ?>" class="row mb-4">
                
                <div class="col-md-3 mb-3">
                    <input type="text" class="form-control" name="search"
                           placeholder="<?php echo e(__('Search')); ?>" value="<?php echo e($searchName ?? ''); ?>">
                </div>

                
                <?php if(!empty($regionOptions) && count($regionOptions) > 0): ?>
                    <div class="col-md-3 mb-3">
                        <select class="form-select" name="region" onchange="this.form.submit()">
                            <?php $__currentLoopData = $regionOptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($value); ?>" <?php echo e(($region ?? '') == $value ? 'selected' : ''); ?>>
                                    <?php echo e($label); ?>

                                </option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </div>
                <?php endif; ?>

                
                <div class="col-md-3 mb-3">
                    <select class="form-select" name="year" onchange="this.form.submit()">
                        <option value=""><?php echo e(__('Filter by Year')); ?></option>
                        <?php $__currentLoopData = $years; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $year): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($year); ?>" <?php echo e(($searchYear ?? '') == $year ? 'selected' : ''); ?>>
                                <?php echo e($year); ?>

                            </option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </div>

                
                <div class="col-md-3 mb-3">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search me-1"></i> <?php echo e(__('Search')); ?>

                    </button>
                </div>
            </form>
        </div>

        <?php if($catalogs && $catalogs->count() > 0): ?>
            <?php $__currentLoopData = $catalogs->sortby('sort'); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $catalog): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div class="col-12 col-sm-6 col-md-4 col-xl-3 text-center">
                    <?php $vin = session('vin'); ?>

                    <a href="<?php echo e(route('tree.level1', [
                        'brand' => $brand->name,
                        'catalog' => $catalog->code,
                        'vin' => $vin
                    ])); ?>">
                        <div class="single-product card border-0 shadow-sm h-100">
                            <div class="img-wrapper position-relative">
                                <img class="xproduct-img img-fluid rounded"
                                     src="<?php echo e(Storage::url($catalog->largeImagePath)); ?>"
                                     alt="product img"
                                     onerror="this.onerror=null; this.src='<?php echo e(asset('assets/images/no-image.png')); ?>';">
                            </div>
                            <div class="ccontent-wrapper p-3 text-center">
                                <h6 class="product-title text-dark fw-bold text-center">
                                    <?php echo e(getLocalizedLabel($catalog)); ?>

                                </h6>
                                <p class="text-muted small"><?php echo e($catalog->code); ?></p>
                                <div class="xprice-wrapper mt-2 text-center">
                                    <h6 class="text-muted">
                                        <?php echo e(formatYearRange($catalog->beginYear, $catalog->endYear)); ?>

                                    </h6>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        <?php else: ?>
            <div class="col-12">
                <div class="alert alert-info text-center">
                    <h5><?php echo e(__('No catalogs available')); ?></h5>
                    <p><?php echo e(__('No catalogs match the selected search criteria.')); ?></p>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <?php if($catalogs && $catalogs->hasPages()): ?>
        <div class="d-flex justify-content-center my-5">
            <?php echo $catalogs->appends(request()->query())->links('includes.frontend.pagination'); ?>

        </div>
    <?php endif; ?>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.front3', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\Users\hp\Herd\new\resources\views/catalog/index.blade.php ENDPATH**/ ?>