<div>
    
    <button class="btn btn-primary position-relative" type="button"
            data-bs-toggle="offcanvas"
            data-bs-target="#specsOffcanvas"
            aria-controls="specsOffcanvas">
        <i class="fas fa-sliders-h me-1"></i>
        <?php echo e(__('Specifications')); ?>


        <!--[if BLOCK]><![endif]--><?php if($selectedCount > 0): ?>
            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-success">
                <?php echo e($selectedCount); ?>

            </span>
        <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
    </button>

    
    <div class="offcanvas offcanvas-start" tabindex="-1" id="specsOffcanvas"
         aria-labelledby="specsOffcanvasLabel" data-bs-backdrop="static" style="width: 320px;">

        
        <div class="offcanvas-header border-bottom bg-light">
            <h5 class="offcanvas-title" id="specsOffcanvasLabel">
                <i class="fas fa-cog me-2"></i>
                <?php echo e(__('Specifications')); ?>

                <!--[if BLOCK]><![endif]--><?php if($catalogName): ?>
                    <small class="d-block text-muted fs-6"><?php echo e($catalogName); ?></small>
                <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>

        
        <div class="offcanvas-body p-0">

            
            <!--[if BLOCK]><![endif]--><?php if($isVinMode): ?>
                <div class="alert alert-info m-3 py-2 mb-0 rounded-2">
                    <i class="fas fa-car me-1"></i>
                    <strong>VIN Mode</strong> - <?php echo e(__('Values are read-only')); ?>

                </div>
            <?php endif; ?><!--[if ENDBLOCK]><![endif]-->

            
            <div class="p-3">

                
                <!--[if BLOCK]><![endif]--><?php if(isset($filters['year']) || isset($filters['month'])): ?>
                    <div class="mb-3">
                        <label class="form-label fw-semibold mb-2">
                            <i class="fas fa-calendar-alt me-1 text-muted"></i>
                            <?php echo e(__('Build Date')); ?>

                            <!--[if BLOCK]><![endif]--><?php if(($filters['year']['readonly'] ?? false) || ($filters['month']['readonly'] ?? false)): ?>
                                <span class="badge bg-primary ms-1" style="font-size: 0.65rem;">VIN</span>
                            <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
                        </label>
                        <div class="row g-2">
                            
                            <!--[if BLOCK]><![endif]--><?php if(isset($filters['month'])): ?>
                                <div class="col-6">
                                    <select class="form-select form-select-sm <?php echo e(($filters['month']['readonly'] ?? false) ? 'bg-light' : ''); ?>"
                                            wire:model.live="selectedValues.month"
                                            <?php if($filters['month']['readonly'] ?? false): ?> disabled <?php endif; ?>>
                                        <option value=""><?php echo e(__('Month')); ?></option>
                                        <!--[if BLOCK]><![endif]--><?php $__currentLoopData = $filters['month']['items']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <option value="<?php echo e($item['value_id']); ?>"><?php echo e($item['label']); ?></option>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><!--[if ENDBLOCK]><![endif]-->
                                    </select>
                                </div>
                            <?php endif; ?><!--[if ENDBLOCK]><![endif]-->

                            
                            <?php if(isset($filters['year'])): ?>
                                <div class="col-6">
                                    <select class="form-select form-select-sm <?php echo e(($filters['year']['readonly'] ?? false) ? 'bg-light' : ''); ?>"
                                            wire:model.live="selectedValues.year"
                                            <?php if($filters['year']['readonly'] ?? false): ?> disabled <?php endif; ?>>
                                        <option value=""><?php echo e(__('Year')); ?></option>
                                        <!--[if BLOCK]><![endif]--><?php $__currentLoopData = $filters['year']['items']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <option value="<?php echo e($item['value_id']); ?>"><?php echo e($item['label']); ?></option>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><!--[if ENDBLOCK]><![endif]-->
                                    </select>
                                </div>
                            <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
                        </div>
                    </div>

                    <hr class="my-3">
                <?php endif; ?><!--[if ENDBLOCK]><![endif]-->

                
                <!--[if BLOCK]><![endif]--><?php $__currentLoopData = $filters; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $filter): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <!--[if BLOCK]><![endif]--><?php if(!in_array($key, ['year', 'month'])): ?>
                        <?php
                            $isReadonly = $filter['readonly'] ?? false;
                            $currentValue = $selectedValues[$key] ?? '';
                            $hasValue = !empty($currentValue);
                        ?>

                        <div class="mb-3">
                            <label class="form-label fw-semibold mb-1 d-flex align-items-center">
                                <span><?php echo e($filter['label']); ?></span>

                                <!--[if BLOCK]><![endif]--><?php if($isReadonly): ?>
                                    <span class="badge bg-primary ms-auto" style="font-size: 0.6rem;">VIN</span>
                                <?php elseif($hasValue): ?>
                                    <span class="badge bg-success ms-auto" style="font-size: 0.6rem;">SET</span>
                                <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
                            </label>

                            <select class="form-select form-select-sm <?php echo e($isReadonly ? 'bg-light' : ''); ?>"
                                    wire:model.live="selectedValues.<?php echo e($key); ?>"
                                    <?php if($isReadonly): ?> disabled <?php endif; ?>>
                                <option value="">-- <?php echo e(__('Select')); ?> --</option>
                                <!--[if BLOCK]><![endif]--><?php $__currentLoopData = $filter['items']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($item['value_id']); ?>"><?php echo e($item['label']); ?></option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><!--[if ENDBLOCK]><![endif]-->
                            </select>
                        </div>
                    <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><!--[if ENDBLOCK]><![endif]-->

                <!--[if BLOCK]><![endif]--><?php if(empty($filters)): ?>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-1"></i>
                        <?php echo e(__('No specifications available')); ?>

                    </div>
                <?php endif; ?><!--[if ENDBLOCK]><![endif]-->

            </div>
        </div>

        
        <div class="offcanvas-footer border-top bg-light p-3">
            <!--[if BLOCK]><![endif]--><?php if(!$isVinMode): ?>
                <div class="d-grid gap-2">
                    <button type="button" class="btn btn-success" wire:click="save" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="save">
                            <i class="fas fa-check me-1"></i>
                            <?php echo e(__('Apply Filters')); ?>

                        </span>
                        <span wire:loading wire:target="save">
                            <i class="fas fa-spinner fa-spin me-1"></i>
                            <?php echo e(__('Saving...')); ?>

                        </span>
                    </button>
                    <button type="button" class="btn btn-outline-secondary" wire:click="clearFilters" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="clearFilters">
                            <i class="fas fa-times me-1"></i>
                            <?php echo e(__('Clear All')); ?>

                        </span>
                        <span wire:loading wire:target="clearFilters">
                            <i class="fas fa-spinner fa-spin me-1"></i>
                            <?php echo e(__('Clearing...')); ?>

                        </span>
                    </button>
                </div>
            <?php else: ?>
                <div class="text-center text-muted small py-2">
                    <i class="fas fa-lock me-1"></i>
                    <?php echo e(__('Filters locked (VIN mode)')); ?>

                </div>
            <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
        </div>

    </div>

    
        <?php
        $__scriptKey = '2398741224-0';
        ob_start();
    ?>
    <script>
        function closeAndReload() {
            console.log('closeAndReload called');
            const offcanvas = bootstrap.Offcanvas.getInstance(document.getElementById('specsOffcanvas'));
            if (offcanvas) offcanvas.hide();
            setTimeout(() => window.location.reload(), 300);
        }

        $wire.on('specs-saved', () => {
            console.log('specs-saved event received');
            closeAndReload();
        });

        $wire.on('specs-cleared', () => {
            console.log('specs-cleared event received');
            closeAndReload();
        });
    </script>
        <?php
        $__output = ob_get_clean();

        \Livewire\store($this)->push('scripts', $__output, $__scriptKey)
    ?>

    <style>
        .offcanvas-footer {
            position: sticky;
            bottom: 0;
        }
        #specsOffcanvas .form-select-sm {
            font-size: 0.85rem;
        }
        #specsOffcanvas .form-label {
            font-size: 0.85rem;
            margin-bottom: 0.25rem;
        }
    </style>
</div>
<?php /**PATH C:\Users\hp\Herd\new\resources\views/livewire/attributes.blade.php ENDPATH**/ ?>