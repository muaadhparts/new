<?php $__env->startSection('content'); ?>
    <section class="gs-breadcrumb-section bg-class"
        data-background="<?php echo e($gs->breadcrumb_banner ? asset('assets/images/' . $gs->breadcrumb_banner) : asset('assets/images/noimage.png')); ?>">
        <div class="container">
            <div class="row justify-content-center content-wrapper">
                <div class="col-12">
                    <h2 class="breadcrumb-title"><?php echo app('translator')->get('Cart'); ?></h2>
                    <ul class="bread-menu">
                        <li><a href="<?php echo e(route('front.index')); ?>"><?php echo app('translator')->get('Home'); ?></a></li>
                        <li><a href="<?php echo e(route("front.cart")); ?>"><?php echo app('translator')->get('Cart'); ?></a></li>
                    </ul>
                </div>
            </div>
        </div>
    </section>
    <section class="gs-cart-section load_cart">
        <?php echo $__env->make('frontend.ajax.cart-page', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
    </section>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.front', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\Users\hp\Herd\new\resources\views/frontend/cart.blade.php ENDPATH**/ ?>