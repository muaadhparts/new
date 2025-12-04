<div>
    <?php
        $filtersLabeled = Session::get('selected_filters_labeled');
        $isFromVin = Session::has('vin');
    ?>

    <!-- زر فتح النافذة -->
    <button class="btn btn-primary" type="button"
            data-bs-toggle="offcanvas" data-bs-target="#offcanvasForm"
            aria-controls="offcanvasForm">
        Specifications
    </button>
    <!-- عرض المواصفات -->
    

    <!-- نموذج المواصفات -->
    <form wire:submit.prevent="save" class="row g-1 p-1">
        <div class="offcanvas offcanvas-start" tabindex="-1" id="offcanvasForm"
             aria-labelledby="offcanvasFormLabel" data-bs-backdrop="static">

            <div class="offcanvas-header">
                <h5 id="offcanvasFormLabel" class="mb-0">
                    Specifications <?php echo e($catalogName ?? $shortName ?? $catalogCode ?? 'Unknown'); ?>

                    <!--[if BLOCK]><![endif]--><?php if(isset($source)): ?> <small class="text-muted"> (<?php echo e($source); ?>)</small> <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
                </h5>

                <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas"
                        aria-label="Close"></button>
            </div>

            <div class="offcanvas-body">

                <!-- التاريخ -->
                <!--[if BLOCK]><![endif]--><?php if(isset($filters['year']) || isset($filters['month'])): ?>
                    <div class="mb-3">
                        <label>Build Date:</label>
                        <div class="input-group">
                            <!--[if BLOCK]><![endif]--><?php if(isset($filters['month'])): ?>
                                <select class="form-select me-2"
                                        wire:model="data.month.value_id"
                                        name="data[month][value_id]"
                                        <?php if($isFromVin): ?> disabled <?php endif; ?>>
                                    <option value="">Month</option>
                                    <!--[if BLOCK]><![endif]--><?php $__currentLoopData = $filters['month']['items'] ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <option value="<?php echo e(is_object($item) ? $item->value_id : $item['value_id']); ?>">
                                            <?php echo e(is_object($item) ? $item->label : $item['label']); ?>

                                        </option>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><!--[if ENDBLOCK]><![endif]-->
                                </select>
                            <?php endif; ?><!--[if ENDBLOCK]><![endif]-->

                            <?php if(isset($filters['year'])): ?>
                                <select class="form-select"
                                        wire:model="data.year.value_id"
                                        name="data[year][value_id]"
                                        <?php if($isFromVin): ?> disabled <?php endif; ?>>
                                    <option value="">Year</option>
                                    <!--[if BLOCK]><![endif]--><?php $__currentLoopData = $filters['year']['items'] ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <option value="<?php echo e(is_object($item) ? $item->value_id : $item['value_id']); ?>">
                                            <?php echo e(is_object($item) ? $item->label : $item['label']); ?>

                                        </option>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><!--[if ENDBLOCK]><![endif]-->
                                </select>
                            <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
                        </div>
                    </div>
                <?php endif; ?><!--[if ENDBLOCK]><![endif]-->

                <!-- باقي الخصائص -->
                <!--[if BLOCK]><![endif]--><?php $__empty_1 = true; $__currentLoopData = $filters; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $attribute): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <!--[if BLOCK]><![endif]--><?php if(!in_array($index, ['year', 'month'])): ?>
                        <div class="mb-3">
                            <label for="select-<?php echo e($index); ?>" class="form-label">
                                <?php echo e($attribute['label'] ?? $index); ?>

                            </label>
                            <select class="form-select"
                                    wire:model="data.<?php echo e($index); ?>.value_id"
                                    name="data[<?php echo e($index); ?>][value_id]"
                                    <?php if($isFromVin): ?> disabled <?php endif; ?>>
                                <option value="">-- Choose --</option>
                                <!--[if BLOCK]><![endif]--><?php $__currentLoopData = $attribute['items'] ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e(is_object($item) ? $item->value_id : $item['value_id']); ?>">
                                        <?php echo e(is_object($item) ? $item->label : $item['label']); ?>

                                    </option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><!--[if ENDBLOCK]><![endif]-->
                            </select>
                        </div>
                    <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <div class="alert alert-warning mt-2">
                        No specifications available to display.
                    </div>
                <?php endif; ?><!--[if ENDBLOCK]><![endif]-->

                <!-- زر الحفظ -->
                <!--[if BLOCK]><![endif]--><?php if (! ($isFromVin)): ?>
                    <div class="d-grid mt-4">
                        <button type="submit" class="btn btn-success">
                            Save Specifications
                        </button>
                    </div>
                <?php endif; ?><!--[if ENDBLOCK]><![endif]-->

     <!-- زر الإزالة -->
<!--[if BLOCK]><![endif]--><?php if (! ($isFromVin)): ?>
    <div class="d-grid mt-2">
        <button type="button" class="btn btn-outline-secondary" wire:click="resetFilters">
            Clear Entries
        </button>
    </div>
<?php endif; ?><!--[if ENDBLOCK]><![endif]-->

</div> <!-- /offcanvas-body -->
</div> <!-- /offcanvas -->
</form>

    <script>
    (function() {
        document.addEventListener('livewire:init', function() {
            Livewire.on('filtersSelected', function() {
                console.log('Filters saved - reloading');
                setTimeout(function() {
                    window.location.reload();
                }, 300);
            });

            Livewire.on('filtersCleared', function() {
                console.log('Filters cleared - reloading');
                setTimeout(function() {
                    window.location.reload();
                }, 300);
            });
        });
    })();
    </script>
</div> <!-- /wrapper div -->
<?php /**PATH C:\Users\hp\Herd\new\resources\views/livewire/attributes.blade.php ENDPATH**/ ?>