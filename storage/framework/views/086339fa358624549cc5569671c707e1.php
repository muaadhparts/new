

<header class="muaadh-header">
    
    <div class="muaadh-topbar">
        <div class="container">
            <div class="muaadh-topbar-inner">
                
                <div class="muaadh-topbar-left">
                    <a href="tel:<?php echo e($ps->phone); ?>" class="muaadh-topbar-link">
                        <i class="fas fa-phone-alt"></i>
                        <span><?php echo e($ps->phone); ?></span>
                    </a>
                    <span class="muaadh-topbar-divider"></span>
                    <a href="mailto:<?php echo e($ps->email ?? 'support@example.com'); ?>" class="muaadh-topbar-link d-none d-md-flex">
                        <i class="fas fa-envelope"></i>
                        <span><?php echo e($ps->email ?? __('Support')); ?></span>
                    </a>
                </div>

                
                <div class="muaadh-topbar-right">
                    
                    <div class="muaadh-dropdown">
                        <button class="muaadh-dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            <i class="fas fa-globe"></i>
                            <span><?php echo e(Session::has('language')
                                ? $languges->where('id', '=', Session::get('language'))->first()->language
                                : $languges->where('is_default', '=', 1)->first()->language); ?></span>
                            <i class="fas fa-chevron-down"></i>
                        </button>
                        <ul class="muaadh-dropdown-menu">
                            <?php $__currentLoopData = $languges; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $language): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <li>
                                    <a class="muaadh-dropdown-item <?php echo e(Session::has('language')
                                        ? (Session::get('language') == $language->id ? 'active' : '')
                                        : ($languges->where('is_default', '=', 1)->first()->id == $language->id ? 'active' : '')); ?>"
                                        href="<?php echo e(route('front.language', $language->id)); ?>">
                                        <?php echo e($language->language); ?>

                                    </a>
                                </li>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </ul>
                    </div>

                    <?php if($gs->is_currency == 1): ?>
                        <span class="muaadh-topbar-divider"></span>
                        
                        <div class="muaadh-dropdown">
                            <button class="muaadh-dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                <i class="fas fa-dollar-sign"></i>
                                <span><?php echo e(Session::has('currency')
                                    ? $currencies->where('id', '=', Session::get('currency'))->first()->name
                                    : DB::table('currencies')->where('is_default', '=', 1)->first()->name); ?></span>
                                <i class="fas fa-chevron-down"></i>
                            </button>
                            <ul class="muaadh-dropdown-menu">
                                <?php $__currentLoopData = $currencies; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $currency): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <li>
                                        <a class="muaadh-dropdown-item <?php echo e(Session::has('currency')
                                            ? (Session::get('currency') == $currency->id ? 'active' : '')
                                            : ($currencies->where('is_default', '=', 1)->first()->id == $currency->id ? 'active' : '')); ?>"
                                            href="<?php echo e(route('front.currency', $currency->id)); ?>">
                                            <?php echo e($currency->name); ?>

                                        </a>
                                    </li>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <span class="muaadh-topbar-divider d-none d-lg-block"></span>

                    
                    <div class="muaadh-topbar-auth d-none d-lg-flex">
                        <?php if(Auth::guard('web')->check() && Auth::guard('web')->user()->is_vendor == 2): ?>
                            <a href="<?php echo e(route('vendor.dashboard')); ?>" class="muaadh-topbar-link">
                                <i class="fas fa-store"></i>
                                <span><?php echo app('translator')->get('Vendor Panel'); ?></span>
                            </a>
                        <?php else: ?>
                            <a href="<?php echo e(route('vendor.login')); ?>" class="muaadh-topbar-link">
                                <i class="fas fa-store"></i>
                                <span><?php echo app('translator')->get('Become Vendor'); ?></span>
                            </a>
                        <?php endif; ?>

                        <?php if(!Auth::guard('rider')->check()): ?>
                            <a href="<?php echo e(route('rider.login')); ?>" class="muaadh-topbar-link">
                                <i class="fas fa-motorcycle"></i>
                                <span><?php echo app('translator')->get('Rider'); ?></span>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    
    <div class="muaadh-main-header">
        <div class="container">
            <div class="muaadh-header-inner">
                
                <div class="muaadh-header-left">
                    
                    <button type="button" class="muaadh-mobile-toggle d-xl-none" aria-label="Toggle Menu">
                        <span></span>
                        <span></span>
                        <span></span>
                    </button>

                    
                    <a href="<?php echo e(route('front.index')); ?>" class="muaadh-logo">
                        <img src="<?php echo e(asset('assets/images/' . $gs->logo)); ?>" alt="<?php echo e($gs->title); ?>">
                    </a>
                </div>

                
                <div class="muaadh-header-actions">
                    
                    <div class="muaadh-action-dropdown">
                        <button type="button" class="muaadh-action-btn" data-bs-toggle="dropdown">
                            <i class="fas fa-user"></i>
                            <span class="muaadh-action-label d-none d-md-block">
                                <?php if(Auth::guard('web')->check()): ?>
                                    <?php echo e(Str::limit(Auth::guard('web')->user()->name, 10)); ?>

                                <?php elseif(Auth::guard('rider')->check()): ?>
                                    <?php echo e(Str::limit(Auth::guard('rider')->user()->name, 10)); ?>

                                <?php else: ?>
                                    <?php echo app('translator')->get('Account'); ?>
                                <?php endif; ?>
                            </span>
                        </button>
                        <div class="muaadh-action-menu">
                            <?php if(Auth::guard('web')->check()): ?>
                                <a href="<?php echo e(route('user-dashboard')); ?>" class="muaadh-action-menu-item">
                                    <i class="fas fa-tachometer-alt"></i>
                                    <span><?php echo app('translator')->get('Dashboard'); ?></span>
                                </a>
                                <a href="<?php echo e(route('user-orders')); ?>" class="muaadh-action-menu-item">
                                    <i class="fas fa-shopping-bag"></i>
                                    <span><?php echo app('translator')->get('My Orders'); ?></span>
                                </a>
                                <a href="<?php echo e(route('user-profile')); ?>" class="muaadh-action-menu-item">
                                    <i class="fas fa-cog"></i>
                                    <span><?php echo app('translator')->get('Settings'); ?></span>
                                </a>
                                <div class="muaadh-action-menu-divider"></div>
                                <a href="<?php echo e(route('user-logout')); ?>" class="muaadh-action-menu-item text-danger">
                                    <i class="fas fa-sign-out-alt"></i>
                                    <span><?php echo app('translator')->get('Logout'); ?></span>
                                </a>
                            <?php elseif(Auth::guard('rider')->check()): ?>
                                <a href="<?php echo e(route('rider-dashboard')); ?>" class="muaadh-action-menu-item">
                                    <i class="fas fa-tachometer-alt"></i>
                                    <span><?php echo app('translator')->get('Dashboard'); ?></span>
                                </a>
                                <a href="<?php echo e(route('rider.logout')); ?>" class="muaadh-action-menu-item text-danger">
                                    <i class="fas fa-sign-out-alt"></i>
                                    <span><?php echo app('translator')->get('Logout'); ?></span>
                                </a>
                            <?php else: ?>
                                <a href="<?php echo e(route('user.login')); ?>" class="muaadh-action-menu-item">
                                    <i class="fas fa-sign-in-alt"></i>
                                    <span><?php echo app('translator')->get('Login'); ?></span>
                                </a>
                                <a href="<?php echo e(route('user.register')); ?>" class="muaadh-action-menu-item">
                                    <i class="fas fa-user-plus"></i>
                                    <span><?php echo app('translator')->get('Register'); ?></span>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>

                    
                    <a href="<?php echo e(auth()->check() ? route('user-wishlists') : route('user.login')); ?>" class="muaadh-action-btn">
                        <i class="fas fa-heart"></i>
                        <span class="muaadh-badge" id="wishlist-count">
                            <?php echo e(Auth::guard('web')->check() ? Auth::guard('web')->user()->wishlistCount() : '0'); ?>

                        </span>
                    </a>

                    
                    <a href="<?php echo e(route('product.compare')); ?>" class="muaadh-action-btn d-none d-sm-flex">
                        <i class="fas fa-exchange-alt"></i>
                        <span class="muaadh-badge" id="compare-count">
                            <?php echo e(Session::has('compare') ? count(Session::get('compare')->items) : '0'); ?>

                        </span>
                    </a>

                    
                    <?php
                        $cart = Session::has('cart') ? Session::get('cart')->items : [];
                    ?>
                    <a href="<?php echo e(route('front.cart')); ?>" class="muaadh-action-btn muaadh-cart-btn">
                        <i class="fas fa-shopping-cart"></i>
                        <span class="muaadh-badge" id="cart-count"><?php echo e($cart ? count($cart) : 0); ?></span>
                        <span class="muaadh-action-label d-none d-md-block"><?php echo app('translator')->get('Cart'); ?></span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    
    <nav class="muaadh-navbar d-none d-xl-block">
        <div class="container">
            <div class="muaadh-navbar-inner">
                
                <div class="muaadh-categories-dropdown">
                    <button type="button" class="muaadh-categories-toggle">
                        <i class="fas fa-bars"></i>
                        <span><?php echo app('translator')->get('All Categories'); ?></span>
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div class="muaadh-categories-menu">
                        <?php $__currentLoopData = $categories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $category): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <div class="muaadh-category-item <?php echo e($category->subs->count() > 0 ? 'has-children' : ''); ?>">
                                <a href="<?php echo e(route('front.category', [$category->slug])); ?>">
                                    <?php if($category->photo): ?>
                                        <img src="<?php echo e(asset('assets/images/categories/' . $category->photo)); ?>" alt="<?php echo e($category->name); ?>">
                                    <?php else: ?>
                                        <i class="fas fa-folder"></i>
                                    <?php endif; ?>
                                    <span><?php echo e($category->name); ?></span>
                                    <?php if($category->subs->count() > 0): ?>
                                        <i class="fas fa-chevron-right muaadh-category-arrow"></i>
                                    <?php endif; ?>
                                </a>
                                <?php if($category->subs->count() > 0): ?>
                                    <div class="muaadh-subcategory-panel">
                                        <div class="muaadh-subcategory-grid">
                                            <?php $__currentLoopData = $category->subs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $subcategory): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                <div class="muaadh-subcategory-group">
                                                    <a href="<?php echo e(route('front.category', [$category->slug, $subcategory->slug])); ?>" class="muaadh-subcategory-title">
                                                        <?php echo e($subcategory->name); ?>

                                                    </a>
                                                    <?php if($subcategory->childs && $subcategory->childs->count() > 0): ?>
                                                        <ul class="muaadh-child-list">
                                                            <?php $__currentLoopData = $subcategory->childs->take(5); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $child): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                                <li>
                                                                    <a href="<?php echo e(route('front.category', [$category->slug, $subcategory->slug, $child->slug])); ?>">
                                                                        <?php echo e($child->name); ?>

                                                                    </a>
                                                                </li>
                                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                                            <?php if($subcategory->childs->count() > 5): ?>
                                                                <li>
                                                                    <a href="<?php echo e(route('front.category', [$category->slug, $subcategory->slug])); ?>" class="muaadh-view-all">
                                                                        <?php echo app('translator')->get('View All'); ?> →
                                                                    </a>
                                                                </li>
                                                            <?php endif; ?>
                                                        </ul>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>
                </div>

                
                <ul class="muaadh-nav-menu">
                    <li class="<?php echo e(request()->path() == '/' ? 'active' : ''); ?>">
                        <a href="<?php echo e(route('front.index')); ?>">
                            <i class="fas fa-home"></i>
                            <span><?php echo app('translator')->get('Home'); ?></span>
                        </a>
                    </li>
                    <li class="<?php echo e(request()->is('category*') ? 'active' : ''); ?>">
                        <a href="<?php echo e(route('front.category')); ?>">
                            <i class="fas fa-box-open"></i>
                            <span><?php echo app('translator')->get('Products'); ?></span>
                        </a>
                    </li>
                    <?php if($pages->where('header', '=', 1)->count() > 0): ?>
                        <li class="muaadh-nav-dropdown">
                            <a href="javascript:void(0)">
                                <i class="fas fa-file-alt"></i>
                                <span><?php echo app('translator')->get('Pages'); ?></span>
                                <i class="fas fa-chevron-down"></i>
                            </a>
                            <ul class="muaadh-nav-submenu">
                                <?php $__currentLoopData = $pages->where('header', '=', 1); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $page): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <li>
                                        <a href="<?php echo e(route('front.vendor', $page->slug)); ?>"><?php echo e($page->title); ?></a>
                                    </li>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </ul>
                        </li>
                    <?php endif; ?>
                    <?php if($ps->blog == 1): ?>
                        <li class="<?php echo e(request()->path() == 'blog' ? 'active' : ''); ?>">
                            <a href="<?php echo e(route('front.blog')); ?>">
                                <i class="fas fa-newspaper"></i>
                                <span><?php echo app('translator')->get('Blog'); ?></span>
                            </a>
                        </li>
                    <?php endif; ?>
                    <li class="<?php echo e(request()->path() == 'faq' ? 'active' : ''); ?>">
                        <a href="<?php echo e(route('front.faq')); ?>">
                            <i class="fas fa-question-circle"></i>
                            <span><?php echo app('translator')->get('FAQ'); ?></span>
                        </a>
                    </li>
                    <li class="<?php echo e(request()->path() == 'contact' ? 'active' : ''); ?>">
                        <a href="<?php echo e(route('front.contact')); ?>">
                            <i class="fas fa-envelope"></i>
                            <span><?php echo app('translator')->get('Contact'); ?></span>
                        </a>
                    </li>
                </ul>

                
                <div class="muaadh-nav-promo">
                    <i class="fas fa-truck"></i>
                    <span><?php echo app('translator')->get('Free Shipping on Orders Over $50'); ?></span>
                </div>
            </div>
        </div>
    </nav>

    </header>
<?php /**PATH C:\Users\hp\Herd\new\resources\views/includes/frontend/header.blade.php ENDPATH**/ ?>