<div>
    <?php use Illuminate\Support\Str; ?>

    
    <?php
$__split = function ($name, $params = []) {
    return [$name, $params];
};
[$__name, $__params] = $__split('callout-modal', []);

$__html = app('livewire')->mount($__name, $__params, 'lw-3755206300-0', $__slots ?? [], get_defined_vars());

echo $__html;

unset($__html);
unset($__name);
unset($__params);
unset($__split);
if (isset($__slots)) unset($__slots);
?>

    <style>
        /* ===== Compact Breadcrumb Styles ===== */
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

        /* ⚙️ الهيكل الأساسي للـ landmarks - لا تغيير */
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

        /* ⚙️ تنسيق العناصر داخل landmarks - يجب أن يكون position:absolute فقط */
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
            /* ❌ لا transform أو scale هنا - smoothZoom يتحكم في التحجيم */
        }

        /* ⚙️ تحسين القابلية للنقر دون تعارض مع smoothZoom */
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

        /* ⚙️ Mobile adjustments - لا transform على #zoom_container أو الآباء */
        @media (max-width: 768px) {
            /* ✅ تصغير أزرار التكبير/التصغير فقط - بدون تأثير على الحاوية */
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

            /* ❌ إزالة أي transform/padding/border قد يؤثر على التموضع */
            .vehicle-search-wrapper,
            .container,
            main,
            body {
                transform: none !important;
                zoom: 1 !important;
            }

            /* ✅ حاوية محايدة بدون padding */
            #zoom_container {
                margin: 0 auto !important;
                padding: 0 !important;
                border: 0 !important;
                transform: none !important;
            }

            /* ✅ الصورة block بدون margins */
            #zoom_container img#image {
                display: block;
                margin: 0;
            }

            body { overscroll-behavior-y: contain; }

            /* تحسين القيم القابلة للضغط على الجوال */
            .callout-label {
                cursor: pointer !important;
                -webkit-tap-highlight-color: rgba(0, 123, 255, 0.3);
            }

            .bbdover {
                cursor: pointer !important;
                min-width: 40px !important;
                min-height: 40px !important;
            }

            /* ✅ إزالة padding من card-body حول الصورة على الموبايل */
            .card-body .products-view,
            .card-body .view-options__body {
                padding: 0 !important;
            }

            /* ✅ التأكد من عدم وجود margins على الحاويات الوسيطة */
            .products-view__options {
                margin: 0 !important;
            }
        }
    </style>

    <div class="container py-2">
        <div class="row">
            <div class="col-12">
                <div class="compact-breadcrumb-wrapper mb-3">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb compact-breadcrumb mb-0">
                            
                            <li class="breadcrumb-item">
                                <a href="<?php echo e(route('front.index')); ?>">
                                    <i class="fas fa-home"></i>
                                    <span class="d-none d-sm-inline ms-1">Home</span>
                                </a>
                            </li>

                            
                            <!--[if BLOCK]><![endif]--><?php if($brand): ?>
                                <li class="breadcrumb-item">
                                    <a href="<?php echo e(route('catlogs.index', ['id' => $brand->name])); ?>">
                                        <?php echo e(Str::limit($brand->name, 15)); ?>

                                    </a>
                                </li>
                            <?php endif; ?><!--[if ENDBLOCK]><![endif]-->

                            
                            <!--[if BLOCK]><![endif]--><?php if(Session::get('vin')): ?>
                                <li class="breadcrumb-item d-none d-sm-block">
                                    <a href="<?php echo e(route('tree.level1', [
                                        'id'   => $brand->name,
                                        'data' => $catalog->code,
                                        'vin'  => Session::get('vin')
                                    ])); ?>">
                                        <i class="fas fa-car me-1"></i>
                                        <?php echo e(Str::limit(Session::get('vin'), 12)); ?>

                                    </a>
                                </li>
                            <?php endif; ?><!--[if ENDBLOCK]><![endif]-->

                            
                            <!--[if BLOCK]><![endif]--><?php if($catalog): ?>
                                <li class="breadcrumb-item d-none d-md-block">
                                    <a href="<?php echo e(route('tree.level1', [
                                        'id'   => $brand->name,
                                        'data' => $catalog->code
                                    ])); ?>">
                                        <?php echo e(Str::limit($catalog->shortName ?? $catalog->name ?? $catalog->code, 20)); ?>

                                    </a>
                                </li>
                            <?php endif; ?><!--[if ENDBLOCK]><![endif]-->

                            
                            <!--[if BLOCK]><![endif]--><?php if($parentCategory1): ?>
                                <li class="breadcrumb-item d-none d-lg-block">
                                    <a href="<?php echo e(route('tree.level2', [
                                        'id'   => $brand->name,
                                        'data' => $catalog->code,
                                        'key1' => $parentCategory1->full_code
                                    ])); ?>">
                                        <?php echo e(Str::limit($parentCategory1->slug ?? $parentCategory1->full_code, 25)); ?>

                                    </a>
                                </li>
                            <?php endif; ?><!--[if ENDBLOCK]><![endif]-->

                            
                            <!--[if BLOCK]><![endif]--><?php if($parentCategory2 && $parentCategory1): ?>
                                <li class="breadcrumb-item d-none d-xl-block">
                                    <a href="<?php echo e(route('tree.level3', [
                                        'id'   => $brand->name,
                                        'data' => $catalog->code,
                                        'key1' => $parentCategory1->full_code,
                                        'key2' => $parentCategory2->full_code
                                    ])); ?>">
                                        <?php echo e(Str::limit($parentCategory2->slug ?? $parentCategory2->full_code, 25)); ?>

                                    </a>
                                </li>
                            <?php endif; ?><!--[if ENDBLOCK]><![endif]-->

                            
                            <!--[if BLOCK]><![endif]--><?php if($parentCategory3): ?>
                                <li class="breadcrumb-item active" aria-current="page">
                                    <span><?php echo e(Str::limit($parentCategory3->Applicability ?? $parentCategory3->full_code, 30)); ?></span>
                                </li>
                            <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>

    
    <div class="container mb-3">
        
        <div class="mb-3">
            <?php
$__split = function ($name, $params = []) {
    return [$name, $params];
};
[$__name, $__params] = $__split('attributes', ['catalog' => $catalog]);

$__html = app('livewire')->mount($__name, $__params, 'lw-3755206300-1', $__slots ?? [], get_defined_vars());

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
                            <?php echo e($category->localized_name); ?>

                        </h5>
                    </div>
                    <div class="card-body p-2 p-md-3">
                        <div class="products-view">
                            <div class="products-view__options view-options">
                                <div class="view-options__body">
                                    <div id="zoom_container">
                                        <img id="image"
                                             src="<?php echo e(Storage::url($category->images)); ?>"
                                             alt="<?php echo e($category->localized_name); ?>" />
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
</div>
<?php /**PATH C:\Users\hp\Herd\new\resources\views/livewire/illustrations.blade.php ENDPATH**/ ?>