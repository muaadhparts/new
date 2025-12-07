



<div class="catalog-modal-content">
    <?php if($results && count($results)): ?>
        
        <div class="catalog-section-header">
            <h5>
                <i class="fas fa-car"></i>
                <?php echo app('translator')->get('labels.fits'); ?>
            </h5>
            <span class="badge bg-secondary"><?php echo e(count($results)); ?> <?php echo app('translator')->get('items'); ?></span>
        </div>

        
        <div class="d-none d-md-block">
            <div class="table-responsive">
                <table class="table table-hover align-middle catalog-table">
                    <thead>
                        <tr>
                            <th><?php echo app('translator')->get('compatibility.part_number'); ?></th>
                            <th><?php echo app('translator')->get('compatibility.vehicle_name'); ?></th>
                            <th><?php echo app('translator')->get('compatibility.catalog_code'); ?></th>
                            <th class="text-center"><?php echo app('translator')->get('compatibility.from_year'); ?></th>
                            <th class="text-center"><?php echo app('translator')->get('compatibility.to_year'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $__currentLoopData = $results; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <?php
                                $row = is_array($item) ? (object) $item : $item;
                            ?>
                            <tr>
                                <td><code class="fw-bold text-dark"><?php echo e($row->part_number ?? ''); ?></code></td>
                                <td><?php echo e($row->label ?? ''); ?></td>
                                <td><span class="catalog-badge catalog-badge-light"><?php echo e($row->catalog_code ?? ''); ?></span></td>
                                <td class="text-center"><?php echo e($row->begin_year ?? ''); ?></td>
                                <td class="text-center">
                                    <?php if(($row->end_year ?? 0) != 0): ?>
                                        <?php echo e($row->end_year); ?>

                                    <?php else: ?>
                                        <span class="catalog-badge catalog-badge-success"><?php echo app('translator')->get('compatibility.until_now'); ?></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </tbody>
                </table>
            </div>
        </div>

        
        <div class="d-block d-md-none catalog-cards">
            <?php $__currentLoopData = $results; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <?php
                    $row = is_array($item) ? (object) $item : $item;
                ?>
                <div class="catalog-card">
                    <div class="catalog-card-header">
                        <code class="fw-bold"><?php echo e($row->part_number ?? ''); ?></code>
                        <?php if(($row->end_year ?? 0) != 0): ?>
                            <span class="catalog-badge catalog-badge-secondary"><?php echo e($row->begin_year ?? ''); ?> - <?php echo e($row->end_year); ?></span>
                        <?php else: ?>
                            <span class="catalog-badge catalog-badge-success"><?php echo e($row->begin_year ?? ''); ?> - <?php echo app('translator')->get('compatibility.until_now'); ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="catalog-card-body">
                        <div class="catalog-card-title"><?php echo e($row->label ?? ''); ?></div>
                        <div class="catalog-card-details">
                            <div class="catalog-card-detail">
                                <span class="catalog-card-label"><?php echo app('translator')->get('compatibility.catalog'); ?>:</span>
                                <span class="catalog-badge catalog-badge-light"><?php echo e($row->catalog_code ?? ''); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
    <?php else: ?>
        <div class="catalog-empty">
            <i class="fas fa-car"></i>
            <p><?php echo app('translator')->get('compatibility.no_results'); ?></p>
        </div>
    <?php endif; ?>
</div>
<?php /**PATH C:\Users\hp\Herd\new\resources\views/partials/api/compatibility-tabs.blade.php ENDPATH**/ ?>