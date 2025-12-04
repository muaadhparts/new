

<?php if(!empty($chips) && count($chips) > 0): ?>
    <?php
        $hasVinSource = collect($chips)->contains(fn($c) => ($c['source'] ?? '') === 'vin');
    ?>

    <div class="specs-bar mb-3 p-3 rounded" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
        <div class="d-flex align-items-center mb-2" style="gap: 0.5rem;">
            <strong class="text-white">
                <i class="fas <?php echo e($hasVinSource ? 'fa-car' : 'fa-sliders-h'); ?> me-1"></i>
                <?php echo e($hasVinSource ? __('ui.vin_specs') : __('ui.selected_specs')); ?>

            </strong>
            <span class="badge bg-light text-dark"><?php echo e(count($chips)); ?></span>
        </div>

        <div class="d-flex flex-wrap" style="gap: 0.5rem;">
            <?php $__currentLoopData = $chips; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $chip): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <span class="spec-chip d-inline-flex align-items-center px-2 py-1 rounded-pill"
                      style="background: #fff; font-size: 0.85rem;">
                    <span style="color: #6c757d; font-weight: 500;"><?php echo e($chip['label']); ?>:</span>
                    <span style="font-weight: 600; color: #212529; margin-left: 0.25rem;"><?php echo e($chip['value']); ?></span>
                    <?php if(!empty($chip['source'])): ?>
                        <?php if($chip['source'] === 'vin'): ?>
                            <span class="ms-1" style="font-size: 0.65rem; background: #0d6efd; color: #fff; border-radius: 0.75rem; padding: 0.1rem 0.4rem;">VIN</span>
                        <?php else: ?>
                            <span class="ms-1" style="font-size: 0.65rem; background: #198754; color: #fff; border-radius: 0.75rem; padding: 0.1rem 0.4rem;">MANUAL</span>
                        <?php endif; ?>
                    <?php endif; ?>
                </span>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
    </div>
<?php endif; ?>
<?php /**PATH C:\Users\hp\Herd\new\resources\views/catalog/partials/chips-bar.blade.php ENDPATH**/ ?>