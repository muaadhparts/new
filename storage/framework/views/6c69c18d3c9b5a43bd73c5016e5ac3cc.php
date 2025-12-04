<div class="container">
    <!--[if BLOCK]><![endif]--><?php if(session()->has('error')): ?>
        <div class="alert alert-danger">
            <?php echo e(session('error')); ?>

        </div>
    <?php endif; ?><!--[if ENDBLOCK]><![endif]-->

    <div class="row gy-4 gy-lg-5 mt-4">
        
        <?php echo $__env->make('includes.frontend.search-vin-ajax', ['uniqueId' => 'catlogs'], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
        <div>
            <!-- Search and Filter Row -->
            <div class="row mb-4">
                <!-- Search Input -->
                <div class="col-md-3 mb-3">
                    <input type="text" class="form-control" wire:model.live.debounce.500ms="searchName"
                           placeholder="<?php echo e(__('Search')); ?>">
                </div>

                <!-- Region Filter -->
                <!--[if BLOCK]><![endif]--><?php if($this->regionOptions && count($this->regionOptions) > 0): ?>
                    <div class="col-md-3 mb-3">
                        <select class="form-select" wire:model.live="region">
                            <!--[if BLOCK]><![endif]--><?php $__currentLoopData = $this->regionOptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($value); ?>"><?php echo e($label); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><!--[if ENDBLOCK]><![endif]-->
                        </select>
                    </div>
                <?php endif; ?><!--[if ENDBLOCK]><![endif]-->

                <!-- Year Filter Dropdown -->
                <div class="col-md-3 mb-3">
                    <select class="form-select" wire:model.live="searchYear">
                        <option value=""><?php echo e(__('Filter by Year')); ?></option>
                        <!--[if BLOCK]><![endif]--><?php $__currentLoopData = $years; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $year): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($year); ?>"><?php echo e($year); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><!--[if ENDBLOCK]><![endif]-->
                    </select>
                </div>
            </div>
        </div>

        <!--[if BLOCK]><![endif]--><?php if($catlogs && $catlogs->count() > 0): ?>
            <!--[if BLOCK]><![endif]--><?php $__currentLoopData = $catlogs->sortby('sort'); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $catalog): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div class="col-12 col-sm-6 col-md-4 col-xl-3 text-center">
                    <?php $vin = session('vin'); ?>

                    <a href="<?php echo e(route('tree.level1', [
                        'id' => $brand->name,
                        'data' => $catalog->code,
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
                                <a href="" class="text-decoration-none">
                                   <h6 class="product-title text-dark fw-bold text-center">
                                        <?php echo e(getLocalizedLabel($catalog)); ?>

                                    </h6>
                                </a>
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
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><!--[if ENDBLOCK]><![endif]-->
        <?php else: ?>
            <div class="col-12">
                <div class="alert alert-info text-center">
                    <h5><?php echo e(__('No catalogs available')); ?></h5>
                    <p><?php echo e(__('No catalogs match the selected search criteria.')); ?></p>
                </div>
            </div>
        <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
    </div>

    <!--[if BLOCK]><![endif]--><?php if($catlogs && $catlogs->hasPages()): ?>
        <div class="d-flex justify-content-center my-5">
            <?php echo $catlogs->links('includes.frontend.pagination'); ?>

        </div>
    <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
</div>
<?php /**PATH C:\Users\hp\Herd\new\resources\views/livewire/catlogs.blade.php ENDPATH**/ ?>