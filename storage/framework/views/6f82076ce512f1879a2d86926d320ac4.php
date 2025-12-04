<div class="container py-3">
    
    <div class="product-nav-wrapper mb-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb text-uppercase mb-0 flex-wrap">
                
                <li class="breadcrumb-item">
                    <a class="text-black text-decoration-none" href="<?php echo e(route('front.index')); ?>">
                        <i class="fas fa-home d-md-none"></i>
                        <span class="d-none d-md-inline">Home</span>
                    </a>
                </li>

                
                <li class="breadcrumb-item">
                    <a class="text-black text-decoration-none" href="<?php echo e(route('catlogs.index', $brand->name)); ?>">
                        <?php echo e(strtoupper($brand->name)); ?>

                    </a>
                </li>

                
                <!--[if BLOCK]><![endif]--><?php if(Session::get('vin')): ?>
                    <li class="breadcrumb-item">
                        <a class="text-black text-decoration-none" href="<?php echo e(route('tree.level1', [
                            'id' => $brand->name,
                            'data' => $catalog->code,
                            'vin' => Session::get('vin')
                        ])); ?>">
                            <i class="fas fa-car d-md-none"></i>
                            <span class="d-none d-md-inline"><?php echo e(Session::get('vin')); ?></span>
                            <span class="d-md-none">VIN</span>
                        </a>
                    </li>
                <?php endif; ?><!--[if ENDBLOCK]><![endif]-->

                
                <li class="breadcrumb-item">
                    <a class="text-black text-decoration-none" href="<?php echo e(route('tree.level1', [
                        'id' => $brand->name,
                        'data' => $catalog->code
                    ])); ?>">
                        <?php echo e(strtoupper($catalog->shortName ?? $catalog->name ?? $catalog->code)); ?>

                    </a>
                </li>

                
                <li class="breadcrumb-item active text-primary" aria-current="page">
                    <strong><?php echo e(strtoupper($category->slug ?? $category->full_code)); ?></strong>
                </li>
            </ol>
        </nav>
    </div>

    
    <div class="row mb-4">
        <div class="col-12">
            
            <div class="mb-3">
                <?php
$__split = function ($name, $params = []) {
    return [$name, $params];
};
[$__name, $__params] = $__split('attributes', ['catalog' => $catalog]);

$__html = app('livewire')->mount($__name, $__params, 'lw-3177036109-0', $__slots ?? [], get_defined_vars());

echo $__html;

unset($__html);
unset($__name);
unset($__params);
unset($__split);
if (isset($__slots)) unset($__slots);
?>
            </div>
            <?php echo $__env->make('includes.frontend.vehicle-search-ajax', [
                'catalog' => $catalog,
                'uniqueId' => 'level2',
                'showAttributes' => false
            ], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
        </div>
    </div>

    <?php
        $categories = collect($categories)->sortBy(function($c) {
            $code = is_array($c) ? ($c['full_code'] ?? '') : ($c->full_code ?? '');
            if (preg_match('/\d+/', $code, $m)) {
                return (int) $m[0];
            }
            return PHP_INT_MAX;
        })->values();
    ?>

    
    <div class="row g-3 g-md-4 mb-5">
        <!--[if BLOCK]><![endif]--><?php $__empty_1 = true; $__currentLoopData = $categories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $cat): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <div class="col-6 col-sm-6 col-md-4 col-lg-3">
                <a href="<?php echo e(route('tree.level3', [
                    'id' => $brand->name,
                    'data' => $catalog->code,
                    'key1' => $category->full_code,
                    'key2' => $cat->full_code,
                    'vin' => Session::get('vin')
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
                                <?php echo e($cat->full_code); ?>

                            </h6>

                            
                            <?php ($label = $cat->label_ar ?? $cat->label_en ?? null); ?>
                            <!--[if BLOCK]><![endif]--><?php if(!empty($label)): ?>
                                <p class="text-muted small mb-0 d-none d-md-block"><?php echo e($label); ?></p>
                            <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
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
        <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
    </div>

    
    <?php $__env->startPush('styles'); ?>
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
    <?php $__env->stopPush(); ?>
</div>
<?php /**PATH C:\Users\hp\Herd\new\resources\views/livewire/catlog-tree-level2.blade.php ENDPATH**/ ?>