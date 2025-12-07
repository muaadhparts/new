 
<?php $__env->startSection('styles'); ?>
<link href="<?php echo e(asset('assets/admin/css/jquery-ui.css')); ?>" rel="stylesheet" type="text/css">
<?php $__env->stopSection(); ?>
<?php $__env->startSection('content'); ?>  
<div class="content-area">
<div class="mr-breadcrumb">
   <div class="row">
      <div class="col-lg-12">
         <h4 class="heading"><?php echo e(__('Tax Calculate')); ?></h4>
         <ul class="links">
            <li>
               <a href="<?php echo e(route('admin.dashboard')); ?>"><?php echo e(__('Dashboard')); ?> </a>
            </li>
            <li>
               <a href="javascript:;"><?php echo e(__('Total Earning')); ?> </a>
            </li>
            <li>
               <a href="<?php echo e(route('admin-tax-calculate-income')); ?>"><?php echo e(__('Tax Calculate')); ?></a>
            </li>
         </ul>
      </div>
   </div>
</div>
<form action="<?php echo e(route('admin-tax-calculate-income')); ?>" method="GET">
   <div class="product-area">
   <div class="row text-center p-3">
      <?php echo $__env->make('includes.admin.form-both', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>  
      <div class="col-sm-6 col-lg-4 offset-lg-2 col-md-6  mt-3">
         <input type="text"  autocomplete="off" class="input-field discount_date" value="<?php echo e($start_date != '' ? $start_date->format('d-m-Y') : ''); ?>"  name="start_date"  placeholder="<?php echo e(__("Enter Date")); ?>"  value="">
      </div>
      <div class="col-sm-6 col-lg-4 col-md-6  mt-3">
         <input type="text"  autocomplete="off" class="input-field discount_date" value="<?php echo e($end_date != '' ? $end_date->format('d-m-Y') : ''); ?>" name="end_date"  placeholder="<?php echo e(__("Enter Date")); ?>"  value="">
      </div>
      <div class="col-sm-12 mt-3">
         <button type="submit" class="mybtn1">Check</button>
         <button type="button" id="reset" class="mybtn1">Reset</button>
      </div>
      <div class="col-lg-12 mt-2">
         <p class="text-center"> <b> <?php echo e($start_date != '' ? $start_date->format('d-m-Y') : ''); ?> <?php echo e($start_date != '' && $end_date != '' ? 'To' : ''); ?>  <?php echo e($end_date != '' ? $end_date->format('d-m-Y') : ''); ?> <?php echo e(__('Total Earning')); ?> : <?php echo e($total); ?></b></p>
         <p class="text-center"> <b>Current Month Earning : <?php echo e($current_month); ?></b></p>
         <p class="text-center"> <b>Last 30 Days Earning : <?php echo e($last_30_days); ?></b></p>
      </div>
   </div>
</form>
<?php echo $__env->make('includes.admin.form-success', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?> 
<div class="mr-table allproduct">
   <div class="table-responsive">
      <table id="muaadhtable" class="table table-hover dt-responsive" cellspacing="0" width="100%">
         <thead>
            <tr>
               <th width="5%"><?php echo e(__('#')); ?></th>
               <th width="25%"><?php echo e(__('Order Number')); ?></th>
               <th width="20%"><?php echo e(__('Txn ID')); ?></th>
               <th width="20%"><?php echo e(__('Tax')); ?></th>
               <th width="20%"><?php echo e(__('Tax Location')); ?></th>
               <th width="10%"><?php echo e(__('Created At')); ?></th>
            </tr>
         </thead>
         <tbody>
            <?php $__currentLoopData = $orders; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $order): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <tr>
               <td><?php echo e($key+1); ?></td>
               <td>
                  <a  href="<?php echo e(route('admin-order-invoice',$order->id)); ?>">
                  <?php echo e($order->order_number); ?>

                  </a>
               </td>
               <td>
                  <?php echo e($order->txnId); ?>

               </td>
               <td>
                  <?php echo e($order->currency_sign); ?><?php echo e(round($order->tax * $order->currency_value,2)); ?>

               </td>
               <td>
                  <?php echo e($order->tax_location); ?>

               </td>
               <td>
                  <?php echo e($order->created_at->format('d-m-Y')); ?>

               </td>
            </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
         </tbody>
      </table>
   </div>
</div>
</div>
<?php $__env->stopSection(); ?>    
<?php $__env->startSection('scripts'); ?>
<script type="text/javascript">
   $('#muaadhtable').DataTable();	
   $(document).on('click','#reset',function(){
   $('.discount_date').val('');
   location.href = '<?php echo e(route('admin-tax-calculate-income')); ?>';
   })												
     
</script>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.admin', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\Users\hp\Herd\new\resources\views/admin/earning/tax_calculate.blade.php ENDPATH**/ ?>