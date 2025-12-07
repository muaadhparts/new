<?php $__env->startSection('content'); ?>
<section class="muaadh-section py-4">
    <div class="container">
        
        <div class="mb-4">
            <?php echo $__env->make('includes.frontend.search-part-ajax', ['uniqueId' => 'searchResults'], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
            <div class="text-center mt-3">
                <button type="button" class="muaadh-btn-vin" data-bs-toggle="modal" data-bs-target="#vinSearchModalResults">
                    <i class="fas fa-car"></i>
                    <?php echo app('translator')->get('Search by VIN'); ?>
                </button>
            </div>
        </div>

        <div class="row">
            <div class="col-12">

                
                <div class="muaadh-search-results-header">
                    <h4 class="muaadh-search-results-title">
                        <i class="fas fa-box-open"></i>
                        <?php echo app('translator')->get('Total Listings Found:'); ?>
                        <span class="muaadh-search-results-count"><?php echo e($filteredMerchants->count()); ?></span>
                    </h4>
                    <p class="muaadh-search-results-query">
                        <i class="fas fa-search me-1"></i>
                        <?php echo app('translator')->get('Query'); ?> : <strong><?php echo e($sku); ?></strong>
                    </p>
                </div>

                <?php if($filteredMerchants->isEmpty()): ?>
                    
                    <div class="no-results-wrapper text-center py-5">
                        <div class="card shadow-sm">
                            <div class="card-body py-5">
                                <i class="fas fa-search fa-3x text-muted mb-3"></i>
                                <h4 class="text-muted"><?php echo app('translator')->get('No Product Found'); ?></h4>
                                <p class="text-muted mb-0"><?php echo app('translator')->get('Try searching with a different SKU or keyword'); ?></p>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- main content -->
                    <div class="tab-content" id="myTabContent">
                        <!-- product list view start  -->
                        <div class="tab-pane fade show active" id="layout-list-pane" role="tabpanel" tabindex="0">
                            <div class="row gy-4 gy-lg-5">

                                
                                <?php
                                    $mainProducts = $filteredMerchants->where('is_alternative', false);
                                    $alternativeProducts = $filteredMerchants->where('is_alternative', true);
                                ?>

                                
                                <?php $__currentLoopData = $mainProducts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <?php echo $__env->make('includes.frontend.list_view_product', [
                                        'product'  => $item['product'],
                                        'vendorId' => $item['merchant']->user_id,
                                        'mp'       => $item['merchant'],
                                    ], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

                                
                                <?php if($alternativeProducts->count() > 0): ?>
                                    <div class="col-12">
                                        <div class="alternatives-section mt-4">
                                            <div class="section-header mb-4">
                                                <h3 class="text-primary">
                                                    <i class="fas fa-exchange-alt me-2"></i>
                                                    <?php echo e(trans('Substitutions')); ?>

                                                </h3>
                                                <hr class="border-primary">
                                            </div>
                                        </div>
                                    </div>

                                    <?php $__currentLoopData = $alternativeProducts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <?php echo $__env->make('includes.frontend.list_view_product', [
                                            'product'  => $item['product'],
                                            'vendorId' => $item['merchant']->user_id,
                                            'mp'       => $item['merchant'],
                                        ], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                <?php endif; ?>

                            </div>
                        </div>
                    </div>
                <?php endif; ?>

            </div>
        </div>
    </div>
</section>


<div class="modal fade" id="vinSearchModalResults" tabindex="-1" aria-hidden="true">
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
                <?php echo $__env->make('includes.frontend.search-vin-ajax', ['uniqueId' => 'searchResultsModal'], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.front', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\Users\hp\Herd\new\resources\views/frontend/search-results.blade.php ENDPATH**/ ?>