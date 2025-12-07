
<!DOCTYPE html>
<html lang="en" dir="<?php echo e($langg && $langg->rtl == 1 ? 'rtl' : 'ltr'); ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo app('translator')->get('Vendor Dashboard'); ?></title>
    <!--Essential css files-->
    <?php if($langg && $langg->rtl == 1): ?>
        <link rel="stylesheet" href="<?php echo e(asset('assets/front')); ?>/css/bootstrap.rtl.min.css">
    <?php else: ?>
        <link rel="stylesheet" href="<?php echo e(asset('assets/front')); ?>/css/bootstrap.min.css">
    <?php endif; ?>
    <link rel="stylesheet" href="<?php echo e(asset('assets/front/css/all.css')); ?>">
    <link rel="stylesheet" href="<?php echo e(asset('assets/front')); ?>/css/slick.css">
    <link rel="stylesheet" href="<?php echo e(asset('assets/front')); ?>/css/nice-select.css">
    <link rel="stylesheet" href="<?php echo e(asset('assets/front')); ?>/css/jquery-ui.css">
    <link rel="stylesheet" href="<?php echo e(asset('assets/front')); ?>/css/animate.css">
    <link rel="stylesheet" href="<?php echo e(asset('assets/front/css/toastr.min.css')); ?>">
    <link rel="stylesheet" href="<?php echo e(asset('assets/front')); ?>/css/datatables.min.css">
    <link rel="stylesheet" href="<?php echo e(asset('assets/front')); ?>/css/style.css?v=<?php echo e(time()); ?>">
    <link href="<?php echo e(asset('assets/admin/css/jquery.tagit.css')); ?>" rel="stylesheet" />
    <?php if($langg && $langg->rtl == 1): ?>
        <link rel="stylesheet" href="<?php echo e(asset('assets/front')); ?>/css/rtl.css">
    <?php endif; ?>
    <link rel="stylesheet" href="<?php echo e(asset('assets/vendor')); ?>/css/custom.css">
    
    <link rel="stylesheet" href="<?php echo e(asset('assets/front/css/theme-colors.css')); ?>?v=<?php echo e(filemtime(public_path('assets/front/css/theme-colors.css'))); ?>">
    <link rel="icon" href="<?php echo e(asset('assets/images/' . $gs->favicon)); ?>">
    <?php echo $__env->make('includes.frontend.extra_head', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
    <?php echo \Livewire\Mechanisms\FrontendAssets\FrontendAssets::styles(); ?>


    
    <style>
        .frontend-header-wrapper .header-top {
            display: none !important;
        }
    </style>

    <?php echo $__env->yieldContent('css'); ?>
    <!--favicon-->

</head>
<?php
    $user = auth()->user();
    $categories = App\Models\Category::with('subs')->where('status', 1)->get();
    $pages = App\Models\Page::get();
    $currencies = App\Models\Currency::all();
    $languges = App\Models\Language::all();
?>

<body>

    <div class="frontend-header-wrapper">
        
        <?php echo $__env->make('includes.frontend.header', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
    </div>

    
    <?php echo $__env->make('includes.vendor.vendor-mobile-header', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

    <!-- overlay -->
    <div class="overlay"></div>

    <!-- user dashboard wrapper start -->
    <div class="gs-user-panel-review">
        <div class="d-flex">
            <!-- sidebar -->
            <?php echo $__env->make('includes.vendor.sidebar', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

            <!-- main content (header and outlet) -->
            <div class="gs-vendor-header-outlet-wrapper">
                <!-- header start  -->
                <?php echo $__env->make('includes.vendor.header', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
                <!-- header end  -->

                <!-- outlet start  -->
                <?php echo $__env->yieldContent('content'); ?>
                <!-- outlet end  -->
            </div>
        </div>
    </div>
    <!-- user dashboard wrapper end -->


    <div class="modal gs-modal fade" id="confirm-detete-modal" tabindex="-1" aria-hidden="true">
        <form id="delete_url" class="modal-dialog confirm-delete-modal-dialog modal-dialog-centered" method="POST">
            <div class="modal-content confirm-delete-modal-content form-group">
                <div class="modal-header delete-modal-header w-100">
                    <div class="title-des-wrapper">
                        <h4 class="title"><?php echo app('translator')->get('Confirm Delete ?'); ?></h4>
                        <h5 class="sub-title">
                        <?php echo app('translator')->get('Are you sure you want to delete this item?'); ?>
                        </h5>
                    </div>
                </div>
                <!-- modal body start  -->

                <!-- Buttons  -->
                <div class="row row-cols-2 w-100">
                    <?php echo csrf_field(); ?>
                    <?php echo method_field('DELETE'); ?>
                    <div class="col">
                        <button type="submit" class="template-btn black-btn w-100" id=""><?php echo app('translator')->get('Delete'); ?></button>
                    </div>
                    <div class="col">
                        <button class="template-btn w-100" data-bs-dismiss="modal" type="button"><?php echo app('translator')->get('Cancel'); ?></button>
                    </div>
                </div>
                <!-- modal body end  -->
            </div>
        </form>
    </div>

    <!--Esential Js Files-->
    <script src="<?php echo e(asset('assets/front')); ?>/js/jquery.min.js"></script>
    <script src="<?php echo e(asset('assets/front')); ?>/js/jquery-ui.js"></script>
    <script src="<?php echo e(asset('assets/front')); ?>/js/nice-select.js"></script>
    <script src="<?php echo e(asset('assets/front')); ?>/js/slick.js"></script>
    <script src="<?php echo e(asset('assets/front')); ?>/js/wow.js"></script>
    <script src="<?php echo e(asset('assets/front')); ?>/js/bootstrap.bundle.min.js"></script>
    <script src="<?php echo e(asset('assets/front')); ?>/js/datatables.min.js"></script>
    <script src="<?php echo e(asset('assets/front')); ?>/js/jquery.waypoints.min.js"></script>
    <script src="<?php echo e(asset('assets/front')); ?>/js/apexcharts.js"></script>
    <script src="<?php echo e(asset('assets/admin/js/tag-it.js')); ?>"></script>
    <script src="<?php echo e(asset('assets/front/js/toastr.min.js')); ?>"></script>
    <script src="<?php echo e(asset('assets/front')); ?>/js/jquery.counterup.js"></script>
    <script src="<?php echo e(asset('assets/front')); ?>/js/script.js?v=<?php echo e(time()); ?>"></script>

    <script type="text/javascript">
        var mainurl = "<?php echo e(url('/')); ?>";
        var admin_loader = <?php echo e($gs->is_admin_loader); ?>;
        var whole_sell = <?php echo e($gs->wholesell); ?>;
        var getattrUrl = '<?php echo e(route('vendor-prod-getattributes')); ?>';
        var curr = <?php echo json_encode($curr); ?>;
        var lang = {
            'additional_price': '<?php echo e(__('0.00 (Additional Price)')); ?>'
        };
    </script>

    <?php echo $__env->yieldContent('script'); ?>

    <script src="<?php echo e(asset('assets/vendor')); ?>/js/myscript.js"></script>


    <?php
        if (Session::has('success')) {
            echo '<script>
                toastr.success("'.Session::get('success').'")
            </script>';
        }
        if (Session::has('unsuccess')) {
            echo '<script>
                toastr.error("'.Session::get('unsuccess').'")
            </script>';
        }
    ?>

<?php echo \Livewire\Mechanisms\FrontendAssets\FrontendAssets::scripts(); ?>


</body>

</html>
<?php /**PATH C:\Users\hp\Herd\new\resources\views/layouts/vendor.blade.php ENDPATH**/ ?>