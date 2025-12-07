<?php if(Session::has('success')): ?>
                  <div class="alert alert-success alert-dismissible">
            <button type="button" class="btn-close" data-bs-dismiss="alert">&times;</button>
                  <?php echo e(Session::get('success')); ?>

            </div>


<?php endif; ?>

<?php if(Session::has('unsuccess')): ?>
            <div class="alert alert-danger alert-dismissible">
            <button type="button" class="btn-close" data-bs-dismiss="alert">&times;</button>
                  <?php echo e(Session::get('unsuccess')); ?>

            </div>
<?php endif; ?>

<?php if(session('message')==='f'): ?>
      <div class="alert alert-danger alert-dismissible">
      <button type="button" class="btn-close" data-bs-dismiss="alert">&times;</button>
            Credentials doesn't match
      </div>

<?php endif; ?>    <?php /**PATH C:\Users\hp\Herd\new\resources\views/alerts/form-success.blade.php ENDPATH**/ ?>