<?php $__env->startSection('content'); ?>
<section class="search-results-section py-4">
    <div class="container">
        
        <div class="search-box-wrapper mb-4">
            <?php echo $__env->make('includes.frontend.search-part-ajax', ['uniqueId' => 'searchResults'], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
            <div class="text-center mt-3">
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#vinSearchModalResults">
                    <i class="fas fa-car me-2"></i>
                    <?php echo app('translator')->get('Search by VIN'); ?>
                </button>
            </div>
        </div>

        <div class="row">
            <div class="col-12">

                
                <div class="results-header card shadow-sm mb-4">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
                            <div>
                                <h4 class="mb-1">
                                    <i class="fas fa-box-open text-primary me-2"></i>
                                    <?php echo app('translator')->get('Total Listings Found:'); ?>
                                    <span class="badge bg-primary"><?php echo e($filteredMerchants->count()); ?></span>
                                </h4>
                                <p class="text-muted mb-0">
                                    <i class="fas fa-search me-1"></i>
                                    <?php echo app('translator')->get('Query'); ?> : <strong><?php echo e($sku); ?></strong>
                                </p>
                            </div>
                        </div>

                    </div>
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


<div class="modal fade" id="vinSearchModalResults" tabindex="-1" aria-labelledby="vinSearchModalResultsLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content" style="border-radius: 20px; border: none; overflow: hidden; box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);">
            <div class="modal-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #fff; padding: 1.5rem 2rem; border-bottom: none;">
                <h5 class="modal-title fw-bold" id="vinSearchModalResultsLabel">
                    <i class="fas fa-car me-2"></i>
                    <?php echo app('translator')->get('Search by VIN'); ?>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" style="filter: brightness(0) invert(1);"></button>
            </div>
            <div class="modal-body p-4" style="background: #f8fafc;">
                <?php echo $__env->make('includes.frontend.search-vin-ajax', ['uniqueId' => 'searchResultsModal'], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('css'); ?>
<style>
/* Search Results Page Styles */
.search-results-section {
    background: #f8f9fa;
    min-height: 100vh;
}

.search-box-wrapper {
    background: #fff;
    border-radius: var(--border-radius, 0.5rem);
    padding: 1.5rem;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.results-header {
    border: 1.5px solid #dee2e6;
    border-radius: var(--border-radius, 0.5rem);
    transition: all 0.3s ease;
}

.results-header:hover {
    border-color: var(--primary-color, #0d6efd);
    box-shadow: 0 4px 16px rgba(13, 110, 253, 0.1);
}

.results-header h4 {
    color: var(--dark-color, #212529);
    font-weight: 700;
}

.no-results-wrapper .card {
    border: 1.5px solid #dee2e6;
    border-radius: var(--border-radius, 0.5rem);
}

.alternatives-section .section-header h3 {
    font-weight: 700;
    position: relative;
    display: inline-block;
    padding-bottom: 0.5rem;
}

.alternatives-section .section-header hr {
    border-width: 2px;
    opacity: 0.3;
}

/* Filters Styling */
.filters-row {
    padding-top: 1rem;
    border-top: 1px solid #dee2e6;
}

.filter-item {
    min-width: 180px;
}

.filter-label {
    font-size: 0.875rem;
    color: #495057;
    display: block;
}

.filter-item .form-select {
    min-width: 180px;
    border-color: #dee2e6;
    transition: all 0.3s ease;
}

.filter-item .form-select:focus {
    border-color: var(--primary-color, #0d6efd);
    box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.1);
}

@media (max-width: 767px) {
    .search-box-wrapper {
        padding: 1rem;
    }

    .results-header h4 {
        font-size: 1.25rem;
    }

    .alternatives-section .section-header h3 {
        font-size: 1.5rem;
    }

    .filter-item {
        min-width: 100%;
        flex: 1 1 100%;
    }

    .filter-item .form-select {
        min-width: 100%;
    }

    .filters-row {
        flex-direction: column;
    }
}
</style>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.front', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\Users\hp\Herd\new\resources\views/frontend/search-results.blade.php ENDPATH**/ ?>