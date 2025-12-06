<?php use Illuminate\Support\Str; ?>

<?php $__env->startSection('title', ($category->localized_name ?? $category->full_code) . ' - ' . __('Illustrations')); ?>

<?php $__env->startSection('content'); ?>

<style>
    /* Landmarks Styles */
    #zoom_container .landmarks {
        position: absolute;
        z-index: 10;
        top: 0;
        left: 0;
    }

    #zoom_container .landmarks .item {
        position: absolute;
        text-align: center;
        display: none;
    }

    .hovered {
        border: 2px solid rgb(219, 16, 16) !important;
        background-color: #bce8f1 !important;
    }

    div[id*='zoom_container'] .landmarks .lable div {
        z-index: 19999;
        text-align: center;
        vertical-align: middle;
        border: 2px solid blue;
        background-color: transparent;
        display: table-cell;
        cursor: pointer;
        padding-left: 4px !important;
        padding-right: 4px !important;
        position: absolute;
        border-radius: 999px;
        font: bold 15px tahoma, arial, verdana, sans-serif;
    }

    .callout-label,
    .correct-callout,
    .bbdover {
        cursor: pointer !important;
        -webkit-tap-highlight-color: rgba(0, 123, 255, 0.2);
    }

    .callout-label:hover .bbdover,
    .bbdover:hover {
        background-color: rgba(0, 123, 255, 0.3) !important;
        opacity: 1 !important;
    }

    .inner-card { height: 20px; background-color: #eee; }
    .card-1 { height: 200px; background-color: #eee; }
    .card-2 { height: 130px; }
    .h-screen { height: 100vh; }

    .animate-pulse { animation: pulse 2s cubic-bezier(.4, 0, .6, 1) infinite; }
    @keyframes pulse { 50% { opacity: .2; } }

    @media (max-width: 768px) {
        .smoothZoom_controls {
            transform: scale(0.6) !important;
            transform-origin: top right !important;
        }

        .smoothZoom_controls a {
            width: 24px !important;
            height: 24px !important;
            font-size: 16px !important;
            line-height: 24px !important;
        }

        .vehicle-search-wrapper,
        .container,
        main,
        body {
            transform: none !important;
            zoom: 1 !important;
        }

        #zoom_container {
            margin: 0 auto !important;
            padding: 0 !important;
            border: 0 !important;
            transform: none !important;
        }

        #zoom_container img#image {
            display: block;
            margin: 0;
        }

        body { overscroll-behavior-y: contain; }

        .callout-label {
            cursor: pointer !important;
            -webkit-tap-highlight-color: rgba(0, 123, 255, 0.3);
        }

        .bbdover {
            cursor: pointer !important;
            min-width: 40px !important;
            min-height: 40px !important;
        }

        .card-body .products-view,
        .card-body .view-options__body {
            padding: 0 !important;
        }

        .products-view__options {
            margin: 0 !important;
        }
    }
</style>


<?php echo $__env->make('catalog.partials.callout-modal', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

<div class="container py-2">
    <div class="row">
        <div class="col-12">
            <div class="catalog-breadcrumb-wrapper mb-3">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb catalog-breadcrumb mb-0">
                        
                        <li class="breadcrumb-item">
                            <a href="<?php echo e(route('front.index')); ?>">
                                <i class="fas fa-home"></i>
                                <span class="d-none d-sm-inline ms-1"><?php echo e(__('Home')); ?></span>
                            </a>
                        </li>

                        
                        <?php if($brand): ?>
                            <li class="breadcrumb-item">
                                <a href="<?php echo e(route('catlogs.index', ['brand' => $brand->name])); ?>">
                                    <?php echo e(Str::limit($brand->name, 15)); ?>

                                </a>
                            </li>
                        <?php endif; ?>

                        
                        <?php if($vin): ?>
                            <li class="breadcrumb-item d-none d-sm-block">
                                <a href="<?php echo e(route('tree.level1', [
                                    'brand'   => $brand->name,
                                    'catalog' => $catalog->code,
                                    'vin'  => $vin
                                ])); ?>">
                                    <i class="fas fa-car me-1"></i>
                                    <?php echo e(Str::limit($vin, 12)); ?>

                                </a>
                            </li>
                        <?php endif; ?>

                        
                        <?php if($catalog): ?>
                            <li class="breadcrumb-item d-none d-md-block">
                                <a href="<?php echo e(route('tree.level1', [
                                    'brand'   => $brand->name,
                                    'catalog' => $catalog->code
                                ])); ?>">
                                    <?php echo e(Str::limit($catalog->shortName ?? $catalog->name ?? $catalog->code, 20)); ?>

                                </a>
                            </li>
                        <?php endif; ?>

                        
                        <?php if($parentCategory1): ?>
                            <li class="breadcrumb-item d-none d-lg-block text-uppercase">
                                <a href="<?php echo e(route('tree.level2', [
                                    'brand'   => $brand->name,
                                    'catalog' => $catalog->code,
                                    'key1' => $parentCategory1->full_code
                                ])); ?>">
                                    <?php echo e(Str::limit(str_replace('-', ' ', $parentCategory1->slug ?? $parentCategory1->full_code), 25)); ?>

                                </a>
                            </li>
                        <?php endif; ?>

                        
                        <?php if($parentCategory2 && $parentCategory1): ?>
                            <li class="breadcrumb-item d-none d-xl-block text-uppercase">
                                <a href="<?php echo e(route('tree.level3', [
                                    'brand'   => $brand->name,
                                    'catalog' => $catalog->code,
                                    'key1' => $parentCategory1->full_code,
                                    'key2' => $parentCategory2->full_code
                                ])); ?>">
                                    <?php echo e(Str::limit(str_replace('-', ' ', $parentCategory2->slug ?? $parentCategory2->full_code), 25)); ?>

                                </a>
                            </li>
                        <?php endif; ?>

                        
                        <?php if($category): ?>
                            <li class="breadcrumb-item active text-uppercase" aria-current="page">
                                <span><?php echo e(Str::limit($category->Applicability ?? $category->full_code, 30)); ?></span>
                            </li>
                        <?php endif; ?>
                    </ol>
                </nav>
            </div>
        </div>
    </div>
</div>


<div class="container mb-3">
    
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
        'uniqueId' => 'illustrations',
        'showAttributes' => false
    ], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
</div>


<div class="container">
    <div class="row justify-content-center">
        <div class="col-12 col-md-10 col-lg-8">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="mb-0 text-center text-md-start">
                        <i class="fas fa-image me-2 d-none d-md-inline"></i>
                        <?php echo e($category->localized_name ?? $category->full_code); ?>

                    </h5>
                </div>
                <div class="card-body p-2 p-md-3">
                    <div class="products-view">
                        <div class="products-view__options view-options">
                            <div class="view-options__body">
                                <div id="zoom_container">
                                    <img id="image"
                                         src="<?php echo e(Storage::url($category->images)); ?>"
                                         alt="<?php echo e($category->localized_name ?? $category->full_code); ?>" />
                                    <div class="landmarks" data-show-at-zoom="0" data-allow-drag="false"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>

<script>
    window.catalogContext = {
        sectionId:   <?php echo e($section->id ?? 'null'); ?>,
        categoryId:  <?php echo e($category->id ?? 'null'); ?>,
        catalogCode: '<?php echo e($catalog->code ?? ''); ?>',
        brandName:   '<?php echo e(optional($brand)->name ?? ''); ?>'
    };
    let csrf = "<?php echo e(csrf_token()); ?>";
</script>


<script src="<?php echo e(asset('assets/front/js/jq-zoom.js')); ?>"></script>
<script src="<?php echo e(asset('assets/front/js/preview.js')); ?>"></script>
<script src="<?php echo e(asset('assets/front/js/ill/illustrated.js')); ?>?v=<?php echo e(time()); ?>"></script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.front', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\Users\hp\Herd\new\resources\views/catalog/illustrations.blade.php ENDPATH**/ ?>