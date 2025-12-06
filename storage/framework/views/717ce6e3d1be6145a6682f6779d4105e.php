

<footer class="muaadh-footer">
    
    <div class="muaadh-footer-main">
        <div class="container">
            <div class="muaadh-footer-grid">
                
                <div class="muaadh-footer-col">
                    <a href="<?php echo e(route('front.index')); ?>" class="muaadh-footer-logo">
                        <img src="<?php echo e(asset('assets/images/' . $gs->footer_logo)); ?>" alt="<?php echo e($gs->title); ?>">
                    </a>
                    <p class="muaadh-footer-desc">
                        <?php echo app('translator')->get('Your trusted source for genuine auto parts and accessories.'); ?>
                    </p>
                    <div class="muaadh-footer-contact">
                        <a href="tel:<?php echo e($ps->phone); ?>" class="muaadh-footer-contact-item">
                            <i class="fas fa-phone-alt"></i>
                            <span><?php echo e($ps->phone); ?></span>
                        </a>
                        <a href="mailto:<?php echo e($ps->email); ?>" class="muaadh-footer-contact-item">
                            <i class="fas fa-envelope"></i>
                            <span><?php echo e($ps->email); ?></span>
                        </a>
                        <?php if($ps->street): ?>
                        <div class="muaadh-footer-contact-item">
                            <i class="fas fa-map-marker-alt"></i>
                            <span><?php echo e($ps->street); ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                
                <div class="muaadh-footer-col">
                    <h5 class="muaadh-footer-title"><?php echo app('translator')->get('Categories'); ?></h5>
                    <ul class="muaadh-footer-links">
                        <?php $__currentLoopData = $categories->take(6); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $cate): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <li>
                                <a href="<?php echo e(route('front.category', $cate->slug)); ?>"><?php echo e($cate->name); ?></a>
                            </li>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </ul>
                </div>

                
                <div class="muaadh-footer-col">
                    <h5 class="muaadh-footer-title"><?php echo app('translator')->get('Quick Links'); ?></h5>
                    <ul class="muaadh-footer-links">
                        <?php if($ps->home == 1): ?>
                            <li><a href="<?php echo e(route('front.index')); ?>"><?php echo app('translator')->get('Home'); ?></a></li>
                        <?php endif; ?>
                        <li><a href="<?php echo e(route('front.category')); ?>"><?php echo app('translator')->get('Products'); ?></a></li>
                        <?php if($ps->contact == 1): ?>
                            <li><a href="<?php echo e(route('front.contact')); ?>"><?php echo app('translator')->get('Contact Us'); ?></a></li>
                        <?php endif; ?>
                        <?php $__currentLoopData = DB::table('pages')->where('footer', '=', 1)->get(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $page): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <li><a href="<?php echo e(route('front.vendor', $page->slug)); ?>"><?php echo e($page->title); ?></a></li>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </ul>
                </div>

                
                <div class="muaadh-footer-col">
                    <h5 class="muaadh-footer-title"><?php echo app('translator')->get('Newsletter'); ?></h5>
                    <p class="muaadh-footer-newsletter-text">
                        <?php echo app('translator')->get('Subscribe to get updates on new products and offers.'); ?>
                    </p>
                    <form action="<?php echo e(route('front.subscribe')); ?>" method="POST" class="muaadh-footer-newsletter">
                        <?php echo csrf_field(); ?>
                        <div class="muaadh-newsletter-input-group">
                            <input type="email" name="email" placeholder="<?php echo app('translator')->get('Enter your email'); ?>" required>
                            <button type="submit">
                                <i class="fas fa-paper-plane"></i>
                            </button>
                        </div>
                    </form>

                    
                    <div class="muaadh-footer-social">
                        <?php
                            $socialLinks = DB::table('social_links')->where('user_id', 0)->where('status', 1)->get();
                        ?>
                        <?php $__currentLoopData = $socialLinks; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $link): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <a href="<?php echo e($link->link); ?>" target="_blank" class="muaadh-social-link">
                                <i class="<?php echo e($link->icon); ?>"></i>
                            </a>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    
    <div class="muaadh-footer-bottom">
        <div class="container">
            <div class="muaadh-footer-bottom-inner">
                <p class="muaadh-copyright"><?php echo e($gs->copyright); ?></p>
            </div>
        </div>
    </div>
</footer>
<?php /**PATH C:\Users\hp\Herd\new\resources\views/includes/frontend/footer.blade.php ENDPATH**/ ?>