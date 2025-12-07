


<div class="tryoto-error">
    <div class="alert alert-warning d-flex align-items-center" role="alert">
        <i class="fas fa-exclamation-triangle me-3" style="font-size: 24px;"></i>
        <div>
            <strong><?php echo app('translator')->get('shipping.smart_shipping_unavailable'); ?></strong>
            <?php if(isset($error) && $error): ?>
                <p class="mb-0 mt-1"><?php echo e($error); ?></p>
            <?php else: ?>
                <p class="mb-0 mt-1"><?php echo app('translator')->get('shipping.default_error_message'); ?></p>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php /**PATH C:\Users\hp\Herd\new\resources\views/partials/api/tryoto-error.blade.php ENDPATH**/ ?>