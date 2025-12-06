


<div class="muaadh-mobile-menu">
    
    <div class="muaadh-mobile-menu-header">
        <a href="<?php echo e(route('front.index')); ?>" class="muaadh-mobile-logo">
            <img src="<?php echo e(asset('assets/images/' . $gs->footer_logo)); ?>" alt="<?php echo e($gs->title); ?>">
        </a>
        <button type="button" class="muaadh-mobile-close">
            <i class="fas fa-times"></i>
        </button>
    </div>

    
    <?php if(Auth::guard('web')->check()): ?>
        <div class="muaadh-mobile-user">
            <div class="muaadh-mobile-user-avatar">
                <?php if(Auth::guard('web')->user()->photo): ?>
                    <img src="<?php echo e(asset('assets/images/users/' . Auth::guard('web')->user()->photo)); ?>" alt="">
                <?php else: ?>
                    <i class="fas fa-user"></i>
                <?php endif; ?>
            </div>
            <div class="muaadh-mobile-user-info">
                <span class="muaadh-mobile-user-name"><?php echo e(Auth::guard('web')->user()->name); ?></span>
                <a href="<?php echo e(route('user-dashboard')); ?>" class="muaadh-mobile-user-link"><?php echo app('translator')->get('View Dashboard'); ?></a>
            </div>
        </div>
    <?php elseif(Auth::guard('rider')->check()): ?>
        <div class="muaadh-mobile-user">
            <div class="muaadh-mobile-user-avatar">
                <i class="fas fa-motorcycle"></i>
            </div>
            <div class="muaadh-mobile-user-info">
                <span class="muaadh-mobile-user-name"><?php echo e(Auth::guard('rider')->user()->name); ?></span>
                <a href="<?php echo e(route('rider-dashboard')); ?>" class="muaadh-mobile-user-link"><?php echo app('translator')->get('Rider Dashboard'); ?></a>
            </div>
        </div>
    <?php else: ?>
        <div class="muaadh-mobile-auth-buttons">
            <a href="<?php echo e(route('user.login')); ?>" class="muaadh-mobile-auth-btn muaadh-btn-primary">
                <i class="fas fa-sign-in-alt"></i>
                <span><?php echo app('translator')->get('Login'); ?></span>
            </a>
            <a href="<?php echo e(route('user.register')); ?>" class="muaadh-mobile-auth-btn muaadh-btn-outline">
                <i class="fas fa-user-plus"></i>
                <span><?php echo app('translator')->get('Register'); ?></span>
            </a>
        </div>
    <?php endif; ?>

    
    <div class="muaadh-mobile-tabs">
        <button class="muaadh-mobile-tab active" data-target="menu-main">
            <i class="fas fa-bars"></i>
            <span><?php echo app('translator')->get('Menu'); ?></span>
        </button>
        <button class="muaadh-mobile-tab" data-target="menu-categories">
            <i class="fas fa-th-large"></i>
            <span><?php echo app('translator')->get('Categories'); ?></span>
        </button>
        <button class="muaadh-mobile-tab" data-target="menu-account">
            <i class="fas fa-user"></i>
            <span><?php echo app('translator')->get('Account'); ?></span>
        </button>
    </div>

    
    <div class="muaadh-mobile-tab-content">
        
        <div class="muaadh-mobile-tab-pane active" id="menu-main">
            <nav class="muaadh-mobile-nav">
                <a href="<?php echo e(route('front.index')); ?>" class="muaadh-mobile-nav-item <?php echo e(request()->path() == '/' ? 'active' : ''); ?>">
                    <i class="fas fa-home"></i>
                    <span><?php echo app('translator')->get('Home'); ?></span>
                </a>
                <a href="<?php echo e(route('front.category')); ?>" class="muaadh-mobile-nav-item <?php echo e(request()->is('category*') ? 'active' : ''); ?>">
                    <i class="fas fa-box-open"></i>
                    <span><?php echo app('translator')->get('Products'); ?></span>
                </a>
                <?php if($pages->where('header', '=', 1)->count() > 0): ?>
                    <div class="muaadh-mobile-nav-accordion">
                        <button class="muaadh-mobile-nav-item muaadh-accordion-toggle">
                            <i class="fas fa-file-alt"></i>
                            <span><?php echo app('translator')->get('Pages'); ?></span>
                            <i class="fas fa-chevron-down muaadh-accordion-icon"></i>
                        </button>
                        <div class="muaadh-accordion-content">
                            <?php $__currentLoopData = $pages->where('header', '=', 1); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $page): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <a href="<?php echo e(route('front.vendor', $page->slug)); ?>" class="muaadh-mobile-nav-subitem">
                                    <?php echo e($page->title); ?>

                                </a>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </div>
                    </div>
                <?php endif; ?>
                <?php if($ps->blog == 1): ?>
                    <a href="<?php echo e(route('front.blog')); ?>" class="muaadh-mobile-nav-item <?php echo e(request()->path() == 'blog' ? 'active' : ''); ?>">
                        <i class="fas fa-newspaper"></i>
                        <span><?php echo app('translator')->get('Blog'); ?></span>
                    </a>
                <?php endif; ?>
                <a href="<?php echo e(route('front.faq')); ?>" class="muaadh-mobile-nav-item <?php echo e(request()->path() == 'faq' ? 'active' : ''); ?>">
                    <i class="fas fa-question-circle"></i>
                    <span><?php echo app('translator')->get('FAQ'); ?></span>
                </a>
                <a href="<?php echo e(route('front.contact')); ?>" class="muaadh-mobile-nav-item <?php echo e(request()->path() == 'contact' ? 'active' : ''); ?>">
                    <i class="fas fa-envelope"></i>
                    <span><?php echo app('translator')->get('Contact Us'); ?></span>
                </a>
            </nav>

            
            <div class="muaadh-mobile-quick-links">
                <h6 class="muaadh-mobile-section-title"><?php echo app('translator')->get('Quick Access'); ?></h6>
                <div class="muaadh-mobile-quick-grid">
                    <a href="<?php echo e(route('front.cart')); ?>" class="muaadh-mobile-quick-item">
                        <i class="fas fa-shopping-cart"></i>
                        <span><?php echo app('translator')->get('Cart'); ?></span>
                        <?php $cart = Session::has('cart') ? Session::get('cart')->items : []; ?>
                        <?php if(count($cart) > 0): ?>
                            <span class="muaadh-mobile-quick-badge"><?php echo e(count($cart)); ?></span>
                        <?php endif; ?>
                    </a>
                    <a href="<?php echo e(auth()->check() ? route('user-wishlists') : route('user.login')); ?>" class="muaadh-mobile-quick-item">
                        <i class="fas fa-heart"></i>
                        <span><?php echo app('translator')->get('Wishlist'); ?></span>
                    </a>
                    <a href="<?php echo e(route('product.compare')); ?>" class="muaadh-mobile-quick-item">
                        <i class="fas fa-exchange-alt"></i>
                        <span><?php echo app('translator')->get('Compare'); ?></span>
                    </a>
                    <a href="<?php echo e(route('front.tracking')); ?>" class="muaadh-mobile-quick-item">
                        <i class="fas fa-truck"></i>
                        <span><?php echo app('translator')->get('Track'); ?></span>
                    </a>
                </div>
            </div>
        </div>

        
        <div class="muaadh-mobile-tab-pane" id="menu-categories">
            <nav class="muaadh-mobile-categories">
                <?php $__currentLoopData = $categories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $category): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php if($category->subs->count() > 0): ?>
                        <div class="muaadh-mobile-nav-accordion">
                            <div class="muaadh-mobile-category-header">
                                <a href="<?php echo e(route('front.category', $category->slug)); ?>" class="muaadh-mobile-category-link <?php echo e(Request::segment(2) === $category->slug ? 'active' : ''); ?>">
                                    <?php if($category->photo): ?>
                                        <img src="<?php echo e(asset('assets/images/categories/' . $category->photo)); ?>" alt="" class="muaadh-mobile-category-img">
                                    <?php else: ?>
                                        <i class="fas fa-folder"></i>
                                    <?php endif; ?>
                                    <span><?php echo e($category->name); ?></span>
                                </a>
                                <button class="muaadh-accordion-toggle-btn">
                                    <i class="fas fa-plus"></i>
                                    <i class="fas fa-minus"></i>
                                </button>
                            </div>
                            <div class="muaadh-accordion-content">
                                <?php $__currentLoopData = $category->subs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $subcategory): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <?php if($subcategory->childs && $subcategory->childs->count() > 0): ?>
                                        <div class="muaadh-mobile-nav-accordion muaadh-nested">
                                            <div class="muaadh-mobile-category-header">
                                                <a href="<?php echo e(route('front.category', [$category->slug, $subcategory->slug])); ?>" class="muaadh-mobile-nav-subitem">
                                                    <?php echo e($subcategory->name); ?>

                                                </a>
                                                <button class="muaadh-accordion-toggle-btn">
                                                    <i class="fas fa-plus"></i>
                                                    <i class="fas fa-minus"></i>
                                                </button>
                                            </div>
                                            <div class="muaadh-accordion-content">
                                                <?php $__currentLoopData = $subcategory->childs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $child): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                    <a href="<?php echo e(route('front.category', [$category->slug, $subcategory->slug, $child->slug])); ?>" class="muaadh-mobile-nav-subitem muaadh-child-item">
                                                        <?php echo e($child->name); ?>

                                                    </a>
                                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <a href="<?php echo e(route('front.category', [$category->slug, $subcategory->slug])); ?>" class="muaadh-mobile-nav-subitem">
                                            <?php echo e($subcategory->name); ?>

                                        </a>
                                    <?php endif; ?>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <a href="<?php echo e(route('front.category', $category->slug)); ?>" class="muaadh-mobile-category-link <?php echo e(Request::segment(2) === $category->slug ? 'active' : ''); ?>">
                            <?php if($category->photo): ?>
                                <img src="<?php echo e(asset('assets/images/categories/' . $category->photo)); ?>" alt="" class="muaadh-mobile-category-img">
                            <?php else: ?>
                                <i class="fas fa-folder"></i>
                            <?php endif; ?>
                            <span><?php echo e($category->name); ?></span>
                        </a>
                    <?php endif; ?>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </nav>
        </div>

        
        <div class="muaadh-mobile-tab-pane" id="menu-account">
            <?php if(Auth::guard('web')->check()): ?>
                <nav class="muaadh-mobile-nav">
                    <a href="<?php echo e(route('user-dashboard')); ?>" class="muaadh-mobile-nav-item">
                        <i class="fas fa-tachometer-alt"></i>
                        <span><?php echo app('translator')->get('Dashboard'); ?></span>
                    </a>
                    <a href="<?php echo e(route('user-orders')); ?>" class="muaadh-mobile-nav-item">
                        <i class="fas fa-shopping-bag"></i>
                        <span><?php echo app('translator')->get('My Orders'); ?></span>
                    </a>
                    <a href="<?php echo e(route('user-wishlists')); ?>" class="muaadh-mobile-nav-item">
                        <i class="fas fa-heart"></i>
                        <span><?php echo app('translator')->get('Wishlist'); ?></span>
                    </a>
                    <a href="<?php echo e(route('user-profile')); ?>" class="muaadh-mobile-nav-item">
                        <i class="fas fa-user-edit"></i>
                        <span><?php echo app('translator')->get('Edit Profile'); ?></span>
                    </a>
                    <a href="<?php echo e(route('user-profile')); ?>" class="muaadh-mobile-nav-item">
                        <i class="fas fa-map-marker-alt"></i>
                        <span><?php echo app('translator')->get('Addresses'); ?></span>
                    </a>
                    <a href="<?php echo e(route('user-logout')); ?>" class="muaadh-mobile-nav-item text-danger">
                        <i class="fas fa-sign-out-alt"></i>
                        <span><?php echo app('translator')->get('Logout'); ?></span>
                    </a>
                </nav>
            <?php elseif(Auth::guard('rider')->check()): ?>
                <nav class="muaadh-mobile-nav">
                    <a href="<?php echo e(route('rider-dashboard')); ?>" class="muaadh-mobile-nav-item">
                        <i class="fas fa-tachometer-alt"></i>
                        <span><?php echo app('translator')->get('Rider Dashboard'); ?></span>
                    </a>
                    <a href="<?php echo e(route('rider.logout')); ?>" class="muaadh-mobile-nav-item text-danger">
                        <i class="fas fa-sign-out-alt"></i>
                        <span><?php echo app('translator')->get('Logout'); ?></span>
                    </a>
                </nav>
            <?php else: ?>
                <div class="muaadh-mobile-guest-account">
                    <div class="muaadh-mobile-guest-icon">
                        <i class="fas fa-user-circle"></i>
                    </div>
                    <h5><?php echo app('translator')->get('Welcome Guest'); ?></h5>
                    <p><?php echo app('translator')->get('Login or create an account to access your orders, wishlist and more.'); ?></p>
                    <div class="muaadh-mobile-guest-buttons">
                        <a href="<?php echo e(route('user.login')); ?>" class="muaadh-btn-primary">
                            <i class="fas fa-sign-in-alt"></i>
                            <?php echo app('translator')->get('Login'); ?>
                        </a>
                        <a href="<?php echo e(route('user.register')); ?>" class="muaadh-btn-outline">
                            <i class="fas fa-user-plus"></i>
                            <?php echo app('translator')->get('Register'); ?>
                        </a>
                    </div>
                </div>

                
                <div class="muaadh-mobile-other-logins">
                    <h6 class="muaadh-mobile-section-title"><?php echo app('translator')->get('Other Accounts'); ?></h6>
                    <a href="<?php echo e(route('vendor.login')); ?>" class="muaadh-mobile-nav-item">
                        <i class="fas fa-store"></i>
                        <span><?php echo app('translator')->get('Vendor Login'); ?></span>
                    </a>
                    <a href="<?php echo e(route('rider.login')); ?>" class="muaadh-mobile-nav-item">
                        <i class="fas fa-motorcycle"></i>
                        <span><?php echo app('translator')->get('Rider Login'); ?></span>
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    
    <div class="muaadh-mobile-menu-footer">
        
        <div class="muaadh-mobile-footer-selects">
            <div class="muaadh-mobile-select">
                <i class="fas fa-globe"></i>
                <select onchange="window.location.href=this.value">
                    <?php $__currentLoopData = $languges; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $language): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e(route('front.language', $language->id)); ?>"
                            <?php echo e(Session::has('language') && Session::get('language') == $language->id ? 'selected' : ''); ?>

                            <?php echo e(!Session::has('language') && $language->is_default == 1 ? 'selected' : ''); ?>>
                            <?php echo e($language->language); ?>

                        </option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>
            <?php if($gs->is_currency == 1): ?>
                <div class="muaadh-mobile-select">
                    <i class="fas fa-dollar-sign"></i>
                    <select onchange="window.location.href=this.value">
                        <?php $__currentLoopData = $currencies; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $currency): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e(route('front.currency', $currency->id)); ?>"
                                <?php echo e(Session::has('currency') && Session::get('currency') == $currency->id ? 'selected' : ''); ?>

                                <?php echo e(!Session::has('currency') && $currency->is_default == 1 ? 'selected' : ''); ?>>
                                <?php echo e($currency->name); ?>

                            </option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </div>
            <?php endif; ?>
        </div>

        
        <div class="muaadh-mobile-contact">
            <a href="tel:<?php echo e($ps->phone); ?>">
                <i class="fas fa-phone-alt"></i>
                <span><?php echo e($ps->phone); ?></span>
            </a>
        </div>

        
        <?php
            $socialLinks = \App\Models\Socialsetting::first();
        ?>
        <?php if($socialLinks && ($socialLinks->facebook || $socialLinks->twitter || $socialLinks->linkedin)): ?>
            <div class="muaadh-mobile-social">
                <?php if($socialLinks->facebook): ?>
                    <a href="<?php echo e($socialLinks->facebook); ?>" target="_blank"><i class="fab fa-facebook-f"></i></a>
                <?php endif; ?>
                <?php if($socialLinks->twitter): ?>
                    <a href="<?php echo e($socialLinks->twitter); ?>" target="_blank"><i class="fab fa-twitter"></i></a>
                <?php endif; ?>
                <?php if($socialLinks->linkedin): ?>
                    <a href="<?php echo e($socialLinks->linkedin); ?>" target="_blank"><i class="fab fa-linkedin-in"></i></a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>


<div class="muaadh-mobile-overlay"></div>
<?php /**PATH C:\Users\hp\Herd\new\resources\views/includes/frontend/mobile_menu.blade.php ENDPATH**/ ?>