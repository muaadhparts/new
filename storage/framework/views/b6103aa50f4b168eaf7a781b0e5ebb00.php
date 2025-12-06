


<?php if($results && count($results)): ?>
    <div class="table-responsive d-none d-md-block">
        <table class="table table-bordered table-striped text-center align-middle">
            <thead class="table-light">
                <tr>
                    <th><?php echo app('translator')->get('compatibility.part_number'); ?></th>
                    <th><?php echo app('translator')->get('compatibility.vehicle_name'); ?></th>
                    <th><?php echo app('translator')->get('compatibility.catalog_code'); ?></th>
                    <th><?php echo app('translator')->get('compatibility.from_year'); ?></th>
                    <th><?php echo app('translator')->get('compatibility.to_year'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php $__currentLoopData = $results; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php
                        $row = is_array($item) ? (object) $item : $item;
                    ?>
                    <tr>
                        <td><?php echo e($row->part_number ?? ''); ?></td>
                        <td><?php echo e($row->label ?? ''); ?></td>
                        <td><?php echo e($row->catalog_code ?? ''); ?></td>
                        <td><?php echo e($row->begin_year ?? ''); ?></td>
                        <td><?php echo e(($row->end_year ?? 0) != 0 ? $row->end_year : __('compatibility.until_now')); ?></td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </tbody>
        </table>
    </div>

    <!-- Mobile Cards -->
    <div class="d-block d-md-none">
        <?php $__currentLoopData = $results; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <?php
                $row = is_array($item) ? (object) $item : $item;
            ?>
            <div class="card shadow-sm mb-3">
                <div class="card-body">
                    <p class="mb-1"><strong><?php echo app('translator')->get('compatibility.part_number'); ?>: </strong> <?php echo e($row->part_number ?? ''); ?></p>
                    <p class="mb-1"><strong><?php echo app('translator')->get('compatibility.vehicle'); ?>: </strong> <?php echo e($row->label ?? ''); ?></p>
                    <p class="mb-1"><strong><?php echo app('translator')->get('compatibility.catalog'); ?>: </strong> <?php echo e($row->catalog_code ?? ''); ?></p>
                    <p class="mb-1"><strong><?php echo app('translator')->get('compatibility.from'); ?>: </strong> <?php echo e($row->begin_year ?? ''); ?></p>
                    <p class="mb-1"><strong><?php echo app('translator')->get('compatibility.to'); ?>: </strong> <?php echo e(($row->end_year ?? 0) != 0 ? $row->end_year : __('compatibility.until_now')); ?></p>
                </div>
            </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>
<?php else: ?>
    <div class="alert alert-info text-center">
        <?php echo app('translator')->get('compatibility.no_results'); ?>
    </div>
<?php endif; ?>
<?php /**PATH C:\Users\hp\Herd\new\resources\views/partials/api/compatibility-tabs.blade.php ENDPATH**/ ?>