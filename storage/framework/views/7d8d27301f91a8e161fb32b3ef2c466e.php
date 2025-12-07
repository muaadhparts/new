

<?php if(!empty($chips) && count($chips) > 0): ?>
    <?php
        $hasVinSource = collect($chips)->contains(fn($c) => ($c['source'] ?? '') === 'vin');
    ?>

    <div class="catalog-chips-bar">
        <div class="catalog-chips-header">
            <strong class="catalog-chips-title">
                <i class="fas <?php echo e($hasVinSource ? 'fa-car' : 'fa-sliders-h'); ?>"></i>
                <?php echo e($hasVinSource ? __('ui.vin_specs') : __('ui.selected_specs')); ?>

            </strong>
            <span class="catalog-chips-count"><?php echo e(count($chips)); ?></span>
        </div>

        <div class="catalog-chips-container">
            <?php $__currentLoopData = $chips; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $chip): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <span class="catalog-chip">
                    <span class="catalog-chip-label"><?php echo e($chip['label']); ?>:</span>
                    <span class="catalog-chip-value"><?php echo e($chip['value']); ?></span>
                    <?php if(!empty($chip['source'])): ?>
                        <?php if($chip['source'] === 'vin'): ?>
                            <span class="catalog-chip-source catalog-chip-source-vin">VIN</span>
                        <?php else: ?>
                            <span class="catalog-chip-source catalog-chip-source-manual">MANUAL</span>
                        <?php endif; ?>
                    <?php endif; ?>
                </span>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
    </div>
<?php endif; ?>
<?php /**PATH C:\Users\hp\Herd\new\resources\views/catalog/partials/chips-bar.blade.php ENDPATH**/ ?>