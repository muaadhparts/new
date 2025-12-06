<?php $__env->startSection('content'); ?>

<!-- breadcrumb -->
<div class="full-row bg-light py-5">
    <div class="container">
        <div class="row text-secondary">
            <div class="col-sm-6">
                <h3 class="mb-2 text-secondary">Error Page</h3>
            </div>
            <div class="col-sm-6">
                <nav aria-label="breadcrumb" class="d-flex justify-content-sm-end align-items-center h-100">
                    <ol class="breadcrumb mb-0 d-inline-flex bg-transparent p-0">
                        <li class="breadcrumb-item"><a href="index.html"><i class="fas fa-home me-1"></i>Home</a></li>
                        <li class="breadcrumb-item"><a href="#">Pages</a></li>
                        <li class="breadcrumb-item active" aria-current="page">404</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>
</div>
<!-- breadcrumb -->

        <!--==================== Error Section Start ====================-->
        <div class="full-row" style="padding: 100px 0">
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-md-6 col-lg-5">
                        <div class="text-center">
                            <img src="<?php echo e($gs->error_banner_404 ? asset('assets/images/'.$gs->error_banner_404):asset('assets/images/noimage.png')); ?>" alt="">
                            <h2 class="my-4"><?php echo e(__('404 Page not found')); ?></h2>
                            <p><?php echo e(__('The page you are looking for dosen’t exist or another error occourd go back to home or another source')); ?></p>
                            <a class="btn btn-secondary mt-5" href="<?php echo e(route('front.index')); ?>"><?php echo e(__('Return to home')); ?></a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!--==================== Error Section Form Start ====================-->




<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.front', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\Users\hp\Herd\new\resources\views/errors/404.blade.php ENDPATH**/ ?>