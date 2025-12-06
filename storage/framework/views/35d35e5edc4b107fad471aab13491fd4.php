<!DOCTYPE html>
<html lang="en" dir="<?php echo e($langg && $langg->rtl == 1 ? 'rtl' : 'ltr'); ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    <title><?php echo e($gs->title); ?></title>
    <!--Essential css files-->
    <?php if($langg && $langg->rtl == 1): ?>
        <link rel="stylesheet" href="<?php echo e(asset('assets/front')); ?>/css/bootstrap.rtl.min.css">
    <?php else: ?>
        <link rel="stylesheet" href="<?php echo e(asset('assets/front')); ?>/css/bootstrap.min.css">
    <?php endif; ?>
    <link rel="stylesheet" href="<?php echo e(asset('assets/front')); ?>/css/all.css">
    <link rel="stylesheet" href="<?php echo e(asset('assets/front')); ?>/css/slick.css">
    <link rel="stylesheet" href="<?php echo e(asset('assets/front')); ?>/css/nice-select.css">
    <link rel="stylesheet" href="<?php echo e(asset('assets/front')); ?>/css/jquery-ui.css">
    <link rel="stylesheet" href="<?php echo e(asset('assets/front')); ?>/css/animate.css">
    <link rel="stylesheet" href="<?php echo e(asset('assets/front/css/all.css')); ?>">
    <link rel="stylesheet" href="<?php echo e(asset('assets/front/css/toastr.min.css')); ?>">
    <link rel="stylesheet" href="<?php echo e(asset('assets/front')); ?>/css/datatables.min.css">
    <link rel="stylesheet" href="<?php echo e(asset('assets/front')); ?>/css/style.css?v=<?php echo e(time()); ?>">
    <?php if($langg && $langg->rtl == 1): ?>
        <link rel="stylesheet" href="<?php echo e(asset('assets/front')); ?>/css/rtl.css">
    <?php endif; ?>
    <link rel="stylesheet" href="<?php echo e(asset('assets/front')); ?>/css/custom.css">
    <link rel="stylesheet" href="<?php echo e(asset('assets/front')); ?>/css/catalog-unified.css">
    <link rel="icon" href="<?php echo e(asset('assets/images/' . $gs->favicon)); ?>">
    <?php echo $__env->make('includes.frontend.extra_head', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
    <?php echo $__env->yieldContent('css'); ?>

</head>

<body>

    <?php
        $categories = App\Models\Category::with('subs')->where('status', 1)->get();
        $pages = App\Models\Page::get();
        $currencies = App\Models\Currency::all();
        $languges = App\Models\Language::all();
    ?>
    <!-- header area -->
    <?php echo $__env->make('includes.frontend.header', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

    <!-- if route is user panel then show vendor.mobile-header else show frontend.mobile_menu -->

    <?php
        $url = url()->current();
        $explodeUrl = explode('/',$url);

    ?>

    <?php if(in_array('user',$explodeUrl)): ?>
    <!-- frontend mobile menu -->
    <?php echo $__env->make('includes.user.mobile-header', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
    <?php elseif(in_array("rider",$explodeUrl)): ?>
    <?php echo $__env->make('includes.rider.mobile-header', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
    <?php else: ?> 
    <?php echo $__env->make('includes.frontend.mobile_menu', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
        <!-- user panel mobile sidebar -->

    <?php endif; ?>
   

    <div class="overlay"></div>

    <?php echo $__env->yieldContent('content'); ?>


    <!-- footer section -->
    <?php echo $__env->make('includes.frontend.footer', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
    <!-- footer section -->

    <!--Esential Js Files-->
    <script src="<?php echo e(asset('assets/front')); ?>/js/jquery.min.js"></script>
        <script src="<?php echo e(asset('assets/front')); ?>/js/slick.js"></script>
    <script src="<?php echo e(asset('assets/front')); ?>/js/jquery-ui.js"></script>
    <script src="<?php echo e(asset('assets/front')); ?>/js/nice-select.js"></script>

    <script src="<?php echo e(asset('assets/front')); ?>/js/wow.js"></script>
    <script src="<?php echo e(asset('assets/front')); ?>/js/bootstrap.bundle.min.js"></script>
    <script src="<?php echo e(asset('assets/front/js/toastr.min.js')); ?>"></script>

    <script src="<?php echo e(asset('assets/front')); ?>/js/script.js?v=<?php echo e(time()); ?>"></script>
    <script src="<?php echo e(asset('assets/front/js/myscript.js?v=' . time())); ?>"></script>


    <script>
        "use strict";
        var mainurl = "<?php echo e(url('/')); ?>";
        var gs      = <?php echo json_encode(DB::table('generalsettings')->where('id','=',1)->first(['is_loader','decimal_separator','thousand_separator','is_cookie','is_talkto','talkto'])); ?>;
        var ps_category = <?php echo e($ps->category); ?>;

        // Setup CSRF token for all AJAX requests
        // This function is called before each AJAX request to ensure we use the latest token
        $.ajaxSetup({
            beforeSend: function(xhr) {
                const token = $('meta[name="csrf-token"]').attr('content');
                if (token) {
                    xhr.setRequestHeader('X-CSRF-TOKEN', token);
                }
            }
        });
    
        var lang = {
            'days': '<?php echo e(__('Days')); ?>',
            'hrs': '<?php echo e(__('Hrs')); ?>',
            'min': '<?php echo e(__('Min')); ?>',
            'sec': '<?php echo e(__('Sec')); ?>',
            'cart_already': '<?php echo e(__('Already Added To Card.')); ?>',
            'cart_out': '<?php echo e(__('Out Of Stock')); ?>',
            'cart_success': '<?php echo e(__('Successfully Added To Cart.')); ?>',
            'cart_empty': '<?php echo e(__('Cart is empty.')); ?>',
            'coupon_found': '<?php echo e(__('Coupon Found.')); ?>',
            'no_coupon': '<?php echo e(__('No Coupon Found.')); ?>',
            'already_coupon': '<?php echo e(__('Coupon Already Applied.')); ?>',
            'enter_coupon': '<?php echo e(__('Enter Coupon First')); ?>',
            'minimum_qty_error': '<?php echo e(__('Minimum Quantity is:')); ?>',
            'affiliate_link_copy': '<?php echo e(__('Affiliate Link Copied Successfully')); ?>'
        };
    
      </script>



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


<?php echo $__env->yieldContent('script'); ?>

</body>

</html>
<?php /**PATH C:\Users\hp\Herd\new\resources\views/layouts/front.blade.php ENDPATH**/ ?>