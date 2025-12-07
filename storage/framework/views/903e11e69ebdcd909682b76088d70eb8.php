<?php $__env->startSection('content'); ?>

<div class="content-area">
              <div class="mr-breadcrumb">
                <div class="row">
                  <div class="col-lg-12">
                      <h4 class="heading"><?php echo e(__('Website Contents')); ?></h4>
                    <ul class="links">
                      <li>
                        <a href="<?php echo e(route('admin.dashboard')); ?>"><?php echo e(__('Dashboard')); ?> </a>
                      </li>
                      <li>
                        <a href="javascript:;"><?php echo e(__('General Settings')); ?></a>
                      </li>
                      <li>
                        <a href="<?php echo e(route('admin-gs-contents')); ?>"><?php echo e(__('Website Contents')); ?></a>
                      </li>
                    </ul>
                  </div>
                </div>
              </div>
              <div class="add-product-content1 add-product-content2">
                <div class="row">
                  <div class="col-lg-12">
                    <div class="product-description py-5">
                      <div class="body-area">
                        <div class="gocover" style="background: url(<?php echo e(asset('assets/images/'.$gs->admin_loader)); ?>) no-repeat scroll center center rgba(45, 45, 45, 0.5);"></div>
                        <form action="<?php echo e(route('admin-gs-update')); ?>" id="muaadhform" method="POST" enctype="multipart/form-data">
                          <?php echo csrf_field(); ?>

                        <?php echo $__env->make('alerts.admin.form-both', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>



                        <div class="row justify-content-center">
                          <div class="col-lg-3">
                            <div class="left-area">
                                <h4 class="heading"><?php echo e(__('Website Title')); ?> *
                                  </h4>
                            </div>
                          </div>
                          <div class="col-lg-6">
                            <input type="text" class="input-field" placeholder="<?php echo e(__('Write Your Site Title Here')); ?>" name="title" value="<?php echo e($gs->title); ?>" required="">
                          </div>
                        </div>

                        <div class="row justify-content-center">
                            <div class="col-lg-3">
                              <div class="left-area">
                                  <h4 class="heading"><?php echo e(__('Copyright Text')); ?> *
                                    </h4>
                              </div>
                            </div>
                            <div class="col-lg-6">
                              <input type="text" class="input-field" placeholder="<?php echo e(__('Write Your Site Copyright Text Here')); ?>" name="copyright" value="<?php echo e($gs->copyright); ?>" required="">
                            </div>
                          </div>


                        <div class="row justify-content-center">
                          <div class="col-lg-3">
                            <div class="left-area">
                              <h4 class="heading">
                                  <?php echo e(__('Debug Mode')); ?>

                              </h4>
                            </div>
                          </div>
                          <div class="col-lg-6">
                              <div class="action-list">
                                  <select class="process select droplinks <?php echo e($gs->is_debug == 1 ? 'drop-success' : 'drop-danger'); ?>">
                                    <option data-val="1" value="<?php echo e(route('admin-gs-status',['is_debug',1])); ?>" <?php echo e($gs->is_debug == 1 ? 'selected' : ''); ?>><?php echo e(__('Activated')); ?></option>
                                    <option data-val="0" value="<?php echo e(route('admin-gs-status',['is_debug',0])); ?>" <?php echo e($gs->is_debug == 0 ? 'selected' : ''); ?>><?php echo e(__('Deactivated')); ?></option>
                                  </select>
                                </div>
                          </div>
                        </div>



                        <div class="row justify-content-center">
                          <div class="col-lg-3">
                            <div class="left-area">
                              <h4 class="heading">
                                  <?php echo e(__('Cookie')); ?>

                              </h4>
                            </div>
                          </div>
                          <div class="col-lg-6">
                              <div class="action-list">
                                  <select class="process select droplinks <?php echo e($gs->is_cookie == 1 ? 'drop-success' : 'drop-danger'); ?>">
                                    <option data-val="1" value="<?php echo e(route('admin-gs-status',['is_cookie',1])); ?>" <?php echo e($gs->is_cookie == 1 ? 'selected' : ''); ?>><?php echo e(__('Activated')); ?></option>
                                    <option data-val="0" value="<?php echo e(route('admin-gs-status',['is_cookie',0])); ?>" <?php echo e($gs->is_cookie == 0 ? 'selected' : ''); ?>><?php echo e(__('Deactivated')); ?></option>
                                  </select>
                                </div>
                          </div>
                        </div>


               
                        <div class="row justify-content-center">
                            <div class="col-lg-3">
                              <div class="left-area">
                                <h4 class="heading">
                                   <?php echo e(__('Capcha')); ?>

                                </h4>
                              </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="action-list">
                                    <select class="process select droplinks <?php echo e($gs->is_capcha== 1 ? 'drop-success' : 'drop-danger'); ?>">
                                      <option data-val="1" value="<?php echo e(route('admin-gs-status',['is_capcha',1])); ?>" <?php echo e($gs->is_capcha == 1 ? 'selected' : ''); ?>><?php echo e(__('Activated')); ?></option>
                                      <option data-val="0" value="<?php echo e(route('admin-gs-status',['is_capcha',0])); ?>" <?php echo e($gs->is_capcha == 0 ? 'selected' : ''); ?>><?php echo e(__('Deactivated')); ?></option>
                                    </select>
                                  </div>
                            </div>
                          </div>

                          <div class="row justify-content-center">
                            <div class="col-lg-3">
                              <div class="left-area">
                                <h4 class="heading">
                                    <?php echo e(__('Google ReCapcha Site Key')); ?> *
                                </h4>
                              </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="tawk-area">
                                  <input class="input-field" name="capcha_site_key" value="<?php echo e($gs->capcha_site_key); ?>">
                                </div>
                            </div>
                          </div>

                          <div class="row justify-content-center">
                            <div class="col-lg-3">
                              <div class="left-area">
                                <h4 class="heading">
                                    <?php echo e(__('Google ReCapcha Secret Key')); ?> *
                                </h4>
                              </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="tawk-area">
                                  <input class="input-field" name="capcha_secret_key" value="<?php echo e($gs->capcha_secret_key); ?>">
                                </div>
                            </div>
                          </div>


                        <div class="row justify-content-center">
                            <div class="col-lg-3">
                              <div class="left-area">
                                <h4 class="heading">
                                    <?php echo e(__('Sign Up Verification')); ?>

                                </h4>
                              </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="action-list">
                                    <select class="process select droplinks <?php echo e($gs->is_verification_email == 1 ? 'drop-success' : 'drop-danger'); ?>">
                                      <option data-val="1" value="<?php echo e(route('admin-gs-status',['is_verification_email',1])); ?>" <?php echo e($gs->is_verification_email == 1 ? 'selected' : ''); ?>><?php echo e(__('Activated')); ?></option>
                                      <option data-val="0" value="<?php echo e(route('admin-gs-status',['is_verification_email',0])); ?>" <?php echo e($gs->is_verification_email == 0 ? 'selected' : ''); ?>><?php echo e(__('Deactivated')); ?></option>
                                    </select>
                                  </div>
                            </div>
                          </div>


                        <div class="row justify-content-center">
                            <div class="col-lg-3">
                              <div class="left-area">
                                <h4 class="heading">
                                    <?php echo e(__('Tawk.to')); ?>

                                </h4>
                              </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="action-list">
                                    <select class="process select droplinks <?php echo e($gs->is_talkto == 1 ? 'drop-success' : 'drop-danger'); ?>">
                                      <option data-val="1" value="<?php echo e(route('admin-gs-status',['is_talkto',1])); ?>" <?php echo e($gs->is_talkto == 1 ? 'selected' : ''); ?>><?php echo e(__('Activated')); ?></option>
                                      <option data-val="0" value="<?php echo e(route('admin-gs-status',['is_talkto',0])); ?>" <?php echo e($gs->is_talkto == 0 ? 'selected' : ''); ?>><?php echo e(__('Deactivated')); ?></option>
                                    </select>
                                  </div>
                            </div>
                          </div>
                          <div class="row justify-content-center">
                              <div class="col-lg-3">
                                <div class="left-area">
                                  <h4 class="heading">
                                      <?php echo e(__('Tawk.to ID')); ?> *
                                  </h4>
                                </div>
                              </div>
                              <div class="col-lg-6">
                                  <div class="tawk-area">
                                    <textarea class="input-field" name="talkto"><?php echo e($gs->talkto); ?></textarea>
                                  </div>
                              </div>
                            </div>

                          <div class="row justify-content-center">
                              <div class="col-lg-3">
                                <div class="left-area">
                                  <h4 class="heading">
                                      <?php echo e(__('Cronjob URL')); ?> *
                                  </h4>
                                  <p class="sub-heading"><?php echo e(__('(Copy This URL and paste this to cron job.)')); ?> <a target="_blank" href="https://www.youtube.com/watch?v=Hw0fbM7E80Q"><?php echo e(__('Check Tutorial')); ?></a> </p>
                                </div>
                              </div>
                              <div class="col-lg-6">
                                  <div >
                                  <textarea class="input-field"  readonly=""><?php echo e(url('/vendor/subscription/check')); ?></textarea>
                                  </div>
                              </div>
                            </div>

                         


                        <div class="row justify-content-center">
                          <div class="col-lg-3">
                            <div class="left-area">

                            </div>
                          </div>
                          <div class="col-lg-6">
                            <button class="addProductSubmit-btn" type="submit"><?php echo e(__('Save')); ?></button>
                          </div>
                        </div>
                     </form>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\Users\hp\Herd\new\resources\views/admin/generalsetting/websitecontent.blade.php ENDPATH**/ ?>