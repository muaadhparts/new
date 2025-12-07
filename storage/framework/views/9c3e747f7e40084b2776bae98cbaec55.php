<div class="modal fade gs-modal" id="vendor_package<?php echo e($vendor_id); ?>" tabindex="-1" role="dialog"
    aria-labelledby="exampleModalCenterTitle" aria-hidden="true">
    <div class="modal-dialog send-message-modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content send-message-modal-content form-group">
            <div class="modal-header w-100">
                <h4 class="title" id="exampleModalLongTitle"><?php echo app('translator')->get('Packaging'); ?></h4>
                <button type="button" data-bs-dismiss="modal" aria-label="Close">
                    <i class="fa-regular fa-circle-xmark gs-modal-close-btn"></i>
                </button>
            </div>
            <div class="packeging-area">
                <!-- start -->
                <div class="summary-inner-box">
                    <div class="inputs-wrapper">
                        <?php $__empty_1 = true; $__currentLoopData = $packaging; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $data): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <div class="gs-radio-wrapper">
                            <input type="radio" class="packing"
                                view="<?php echo e($curr->sign); ?><?php echo e(round($data->price * $curr->value, 2)); ?>"
                                data-form="<?php echo e($data->title); ?>" id="free-package<?php echo e($data->id); ?>" ref="<?php echo e($vendor_id); ?>"
                                data-price="<?php echo e(round($data->price * $curr->value, 2)); ?>"
                                name="packeging[<?php echo e($vendor_id); ?>]" value="<?php echo e($data->id); ?>" <?php echo e($loop->first ? 'checked' :
                            ''); ?>>
                            <label class="icon-label" for="free-package<?php echo e($data->id); ?>">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20"
                                    fill="none">
                                    <rect x="0.5" y="0.5" width="19" height="19" rx="9.5" fill="#FDFDFD" />
                                    <rect x="0.5" y="0.5" width="19" height="19" rx="9.5" stroke="#EE1243" />
                                    <circle cx="10" cy="10" r="4" fill="#EE1243" />
                                </svg>
                            </label>
                            <label for="free-package<?php echo e($data->id); ?>">
                                <?php echo e($data->title); ?>

                                <?php if($data->price != 0): ?>
                                + <?php echo e($curr->sign); ?><?php echo e(round($data->price * $curr->value, 2)); ?>

                                <?php endif; ?>
                                <small><?php echo e($data->subtitle); ?></small>
                            </label>
                        </div>

                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <p>
                            <?php echo app('translator')->get('No Packaging Method Available'); ?>
                        </p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- end -->

            </div>

        </div>
    </div>
</div><?php /**PATH C:\Users\hp\Herd\new\resources\views/includes/frontend/vendor_packaging.blade.php ENDPATH**/ ?>