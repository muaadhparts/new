<?php if($gs->theme == 'theme1'): ?>
    <?php echo $__env->make('frontend.theme.home1', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
<?php elseif($gs->theme == 'theme2'): ?>
    <?php echo $__env->make('frontend.theme.home2', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
<?php elseif($gs->theme == 'theme3'): ?>
    <?php echo $__env->make('frontend.theme.home3', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
<?php elseif($gs->theme == 'theme4' || $gs->theme == 'them4'): ?>
    <?php echo $__env->make('frontend.theme.home4', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
<?php else: ?>
    <?php echo $__env->make('frontend.theme.home1', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
<?php endif; ?>
<?php /**PATH C:\Users\hp\Herd\new\resources\views/frontend/index.blade.php ENDPATH**/ ?>