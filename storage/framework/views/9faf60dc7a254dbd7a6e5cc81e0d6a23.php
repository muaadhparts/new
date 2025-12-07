<?php $__env->startSection('content'); ?>
    <div class="content-area">
        <div class="mr-breadcrumb">
            <div class="row">
                <div class="col-lg-12">
                    <h4 class="heading"><?php echo e(__('Home Page')); ?></h4>
                    <ul class="links">
                        <li>
                            <a href="<?php echo e(route('admin.dashboard')); ?>"><?php echo e(__('Dashboard')); ?> </a>
                        </li>
                        <li>
                            <a href="javascript:;"><?php echo e(__('Home Page Setting')); ?></a>
                        </li>
                        <li>
                            <a href="<?php echo e(route('admin-home-page-index')); ?>"><?php echo e(__('Home Page')); ?></a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="add-product-content1 add-product-content2">
            <div class="row">
                <div class="col-lg-12">
                    <div class="product-description">
                        <div class="body-area p-4">
                            <div class="gocover"
                                style="background: url(<?php echo e(asset('assets/images/' . $gs->admin_loader)); ?>) no-repeat scroll center center rgba(45, 45, 45, 0.5);">
                            </div>

                            <?php echo $__env->make('alerts.form-success', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

                            <div class="row  justify-content-center">
                                <div class="col-lg-4 mb-4 mb-lg-0">
                                    <form action="<?php echo e(route('admin-gs-update-theme')); ?>" method="POST">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="theme" value="theme1">
                                        <div class="d-flex flex-column align-items-center" >
                                            <label for="">Home Page 1</label>
                                            <div class="img-upload scroll-container"
                                                style="max-height: 240px; overflow-y: hidden;">
                                                <div id="image-preview" class="img-preview text-center"
                                                    style="background: url('<?php echo e(asset('assets/admin/theme1.png')); ?>') no-repeat center; background-size: cover; height: 400px;">
                                                </div>
                                            </div>
                                            <div class="row justify-content-center mt-3">
                                                <button class="addProductSubmit-btn" type="submit">
                                                    <?php if($gs->theme == 'theme1'): ?>
                                                        <?php echo e(__('Active')); ?>

                                                    <?php else: ?>
                                                        <?php echo e(__('Theme 1')); ?>

                                                    <?php endif; ?>
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                                <div class="col-lg-4 mb-4 mb-lg-0">
                                    <form action="<?php echo e(route('admin-gs-update-theme')); ?>" method="POST">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="theme" value="theme2">

                                        <div class="d-flex flex-column align-items-center" >
                                            <label for="">Home Page 2</label>
                                            <div class="img-upload scroll-container"
                                                style="max-height: 240px; overflow-y: hidden;">
                                                <div id="image-preview" class="img-preview text-center"
                                                    style="background: url('<?php echo e(asset('assets/admin/theme2.png')); ?>') no-repeat center; background-size: cover; height: 400px;">
                                                </div>
                                            </div>
                                            <div class="row justify-content-center mt-3">
                                                <button class="addProductSubmit-btn" type="submit">
                                                    <?php if($gs->theme == 'theme2'): ?>
                                                        <?php echo e(__('Active')); ?>

                                                    <?php else: ?>
                                                        <?php echo e(__('Theme 2')); ?>

                                                    <?php endif; ?>
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                                <div class="col-lg-4 mb-4 mb-lg-0">
                                    <form action="<?php echo e(route('admin-gs-update-theme')); ?>" method="POST">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="theme" value="theme3">
                                        <div class="d-flex flex-column align-items-center" >
                                            <label for="">Home Page 3</label>
                                            <div class="img-upload scroll-container"
                                                style="max-height: 240px; overflow-y: hidden;">
                                                <div id="image-preview" class="img-preview text-center"
                                                    style="background: url('<?php echo e(asset('assets/admin/theme3.png')); ?>') no-repeat center; background-size: cover; height: 400px;">
                                                </div>
                                            </div>
                                            <div class="row justify-content-center mt-3">
                                                <button class="addProductSubmit-btn" type="submit">
                                                    <?php if($gs->theme == 'theme3'): ?>
                                                        <?php echo e(__('Active')); ?>

                                                    <?php else: ?>
                                                        <?php echo e(__('Theme 3')); ?>

                                                    <?php endif; ?>
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>


                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('scripts'); ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const scrollContainers = document.querySelectorAll('.scroll-container');

            scrollContainers.forEach(scrollContainer => {
                let isScrolling;

                scrollContainer.addEventListener('mouseenter', function() {
                    isScrolling = setInterval(function() {
                        scrollContainer.scrollTop += 1;
                        if (scrollContainer.scrollTop + scrollContainer.clientHeight >=
                            scrollContainer.scrollHeight) {
                            scrollContainer.scrollTop = 0;
                        }
                    }, 30); // Adjust speed here (lower number = faster scroll)
                });

                scrollContainer.addEventListener('mouseleave', function() {
                    clearInterval(isScrolling);
                });
            });
        });
    </script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\Users\hp\Herd\new\resources\views/admin/generalsetting/homepage.blade.php ENDPATH**/ ?>