

<?php
    $catalogCode = $catalog->code ?? '';
    $catalogName = $catalog->name ?? $catalog->shortName ?? $catalogCode;
    $catalogYears = formatYearRange($catalog->beginYear ?? null, $catalog->endYear ?? null);
?>


<button type="button"
        class="btn btn-primary position-relative"
        data-bs-toggle="offcanvas"
        data-bs-target="#specsOffcanvas"
        aria-controls="specsOffcanvas">
    <i class="fas fa-sliders-h me-1"></i>
    <?php echo e(__('Specifications')); ?>


    <?php
        $selectedCount = collect($selectedFilters)->filter(fn($v) =>
            is_array($v) ? !empty($v['value_id']) : !empty($v)
        )->count();
    ?>

    <?php if($selectedCount > 0): ?>
        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-success">
            <?php echo e($selectedCount); ?>

        </span>
    <?php endif; ?>
</button>


<div class="offcanvas offcanvas-start catalog-specs-offcanvas" tabindex="-1" id="specsOffcanvas"
     aria-labelledby="specsOffcanvasLabel" data-bs-backdrop="static">

    
    <div class="offcanvas-header">
        <h5 class="offcanvas-title" id="specsOffcanvasLabel">
            <i class="fas fa-cog me-2"></i>
            <?php echo e(__('Specifications')); ?>

            <small><?php echo e($catalogName); ?> <?php echo e($catalogYears ? "($catalogYears)" : ''); ?></small>
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>

    
    <div class="offcanvas-body p-0">

        
        <?php if($isVinMode): ?>
            <div class="alert alert-info catalog-specs-alert">
                <i class="fas fa-car me-1"></i>
                <strong>VIN Mode</strong> - <?php echo e(__('Values are read-only')); ?>

            </div>
        <?php endif; ?>

        
        <form id="specsForm" class="catalog-specs-form">
            <input type="hidden" name="catalog_code" value="<?php echo e($catalogCode); ?>">

            
            <?php if(isset($filters['year']) || isset($filters['month'])): ?>
                <div class="mb-3">
                    <label class="form-label">
                        <i class="fas fa-calendar-alt me-1 text-muted"></i>
                        <?php echo e(__('Build Date')); ?>

                        <?php if(($filters['year']['readonly'] ?? false) || ($filters['month']['readonly'] ?? false)): ?>
                            <span class="catalog-specs-badge catalog-specs-badge-vin">VIN</span>
                        <?php endif; ?>
                    </label>
                    <div class="row g-2">
                        
                        <?php if(isset($filters['month'])): ?>
                            <div class="col-6">
                                <select name="filters[month]"
                                        class="form-select form-select-sm <?php echo e(($filters['month']['readonly'] ?? false) ? 'bg-light' : ''); ?>"
                                        <?php echo e(($filters['month']['readonly'] ?? false) ? 'disabled' : ''); ?>>
                                    <option value=""><?php echo e(__('Month')); ?></option>
                                    <?php $__currentLoopData = $filters['month']['items']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <option value="<?php echo e($item['value_id']); ?>"
                                                <?php echo e(($filters['month']['selected'] ?? '') == $item['value_id'] ? 'selected' : ''); ?>>
                                            <?php echo e($item['label']); ?>

                                        </option>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </select>
                            </div>
                        <?php endif; ?>

                        
                        <?php if(isset($filters['year'])): ?>
                            <div class="col-6">
                                <select name="filters[year]"
                                        class="form-select form-select-sm <?php echo e(($filters['year']['readonly'] ?? false) ? 'bg-light' : ''); ?>"
                                        <?php echo e(($filters['year']['readonly'] ?? false) ? 'disabled' : ''); ?>>
                                    <option value=""><?php echo e(__('Year')); ?></option>
                                    <?php $__currentLoopData = $filters['year']['items']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <option value="<?php echo e($item['value_id']); ?>"
                                                <?php echo e(($filters['year']['selected'] ?? '') == $item['value_id'] ? 'selected' : ''); ?>>
                                            <?php echo e($item['label']); ?>

                                        </option>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </select>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <hr class="my-3">
            <?php endif; ?>

            
            <?php $__currentLoopData = $filters; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $filter): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <?php if(!in_array($key, ['year', 'month'])): ?>
                    <?php
                        $isReadonly = $filter['readonly'] ?? false;
                        $currentValue = $filter['selected'] ?? '';
                        $hasValue = !empty($currentValue);
                    ?>

                    <div class="mb-3">
                        <label class="form-label">
                            <span><?php echo e($filter['label']); ?></span>
                            <?php if($isReadonly): ?>
                                <span class="catalog-specs-badge catalog-specs-badge-vin">VIN</span>
                            <?php elseif($hasValue): ?>
                                <span class="catalog-specs-badge catalog-specs-badge-set">SET</span>
                            <?php endif; ?>
                        </label>

                        <select name="filters[<?php echo e($key); ?>]"
                                class="form-select form-select-sm <?php echo e($isReadonly ? 'bg-light' : ''); ?>"
                                <?php echo e($isReadonly ? 'disabled' : ''); ?>>
                            <option value="">-- <?php echo e(__('Select')); ?> --</option>
                            <?php $__currentLoopData = $filter['items']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($item['value_id']); ?>"
                                        <?php echo e($currentValue == $item['value_id'] ? 'selected' : ''); ?>>
                                    <?php echo e($item['label']); ?>

                                </option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </div>
                <?php endif; ?>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

            <?php if(empty($filters)): ?>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-1"></i>
                    <?php echo e(__('No specifications available')); ?>

                </div>
            <?php endif; ?>
        </form>
    </div>

    
    <div class="catalog-specs-footer">
        <?php if(!$isVinMode): ?>
            <div class="d-grid gap-2">
                <button type="button" class="btn btn-success" id="btnApplySpecs">
                    <i class="fas fa-check me-1"></i>
                    <?php echo e(__('Apply Filters')); ?>

                </button>
                <button type="button" class="btn btn-outline-secondary" id="btnClearSpecs">
                    <i class="fas fa-times me-1"></i>
                    <?php echo e(__('Clear All')); ?>

                </button>
            </div>
        <?php else: ?>
            <div class="text-center text-muted small py-2">
                <i class="fas fa-lock me-1"></i>
                <?php echo e(__('Filters locked (VIN mode)')); ?>

            </div>
        <?php endif; ?>
    </div>
</div>


<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('specsForm');
    const btnApply = document.getElementById('btnApplySpecs');
    const btnClear = document.getElementById('btnClearSpecs');
    const offcanvasEl = document.getElementById('specsOffcanvas');

    // Apply Filters
    if (btnApply) {
        btnApply.addEventListener('click', async function() {
            btnApply.disabled = true;
            btnApply.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> <?php echo e(__("Saving...")); ?>';

            try {
                const formData = new FormData(form);
                const filters = {};

                for (const [key, value] of formData.entries()) {
                    if (key.startsWith('filters[') && value) {
                        const filterKey = key.replace('filters[', '').replace(']', '');
                        filters[filterKey] = value;
                    }
                }

                const response = await fetch('/api/specs/save', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        catalog_code: formData.get('catalog_code'),
                        filters: filters
                    })
                });

                const result = await response.json();

                if (result.success) {
                    // Close modal and reload page
                    const offcanvas = bootstrap.Offcanvas.getInstance(offcanvasEl);
                    if (offcanvas) offcanvas.hide();
                    setTimeout(() => window.location.reload(), 200);
                } else {
                    alert(result.message || 'Error saving filters');
                    btnApply.disabled = false;
                    btnApply.innerHTML = '<i class="fas fa-check me-1"></i> <?php echo e(__("Apply Filters")); ?>';
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Error saving filters');
                btnApply.disabled = false;
                btnApply.innerHTML = '<i class="fas fa-check me-1"></i> <?php echo e(__("Apply Filters")); ?>';
            }
        });
    }

    // Clear Filters
    if (btnClear) {
        btnClear.addEventListener('click', async function() {
            btnClear.disabled = true;
            btnClear.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> <?php echo e(__("Clearing...")); ?>';

            try {
                const catalogCode = document.querySelector('input[name="catalog_code"]').value;

                const response = await fetch('/api/specs/clear', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        catalog_code: catalogCode
                    })
                });

                const result = await response.json();

                if (result.success) {
                    // Close modal and reload page
                    const offcanvas = bootstrap.Offcanvas.getInstance(offcanvasEl);
                    if (offcanvas) offcanvas.hide();
                    setTimeout(() => window.location.reload(), 200);
                } else {
                    alert(result.message || 'Error clearing filters');
                    btnClear.disabled = false;
                    btnClear.innerHTML = '<i class="fas fa-times me-1"></i> <?php echo e(__("Clear All")); ?>';
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Error clearing filters');
                btnClear.disabled = false;
                btnClear.innerHTML = '<i class="fas fa-times me-1"></i> <?php echo e(__("Clear All")); ?>';
            }
        });
    }
});
</script>


<?php /**PATH C:\Users\hp\Herd\new\resources\views/catalog/partials/specs-modal.blade.php ENDPATH**/ ?>