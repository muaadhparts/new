<!-- mobile menu -->
<div class="mobile-menu">
    <div class="mobile-menu-top">
        <img src="<?php echo e(asset('assets/images/' . $gs->footer_logo)); ?>" alt="">
        <svg class="close" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
            fill="none">
            <path d="M18 6L6 18M6 6L18 18" stroke="white" stroke-width="2" stroke-linecap="round"
                stroke-linejoin="round" />
        </svg>
    </div>
    <nav>
        <div class="nav justify-content-between pt-24" id="nav-tab" role="tablist">
            <button class="flex-grow-1 state-left-btn active active-tab-btn" id="main-menu-tab" data-bs-toggle="tab"
                data-bs-target="#main-menu" type="button" role="tab" aria-controls="main-menu"
                aria-selected="true"><?php echo app('translator')->get('MAIN MENU'); ?></button>

            <button class="flex-grow-1 state-right-btn active-tab-btn" id="categories-tab" data-bs-toggle="tab"
                data-bs-target="#categories" type="button" role="tab" aria-controls="categories"
                aria-selected="false"><?php echo app('translator')->get('CATEGORIES'); ?></button>
        </div>
    </nav>

    <div class="tab-content " id="nav-tabContent1">
        <div class="tab-pane fade show active table-responsive tb-tb" id="main-menu" role="tabpanel"
            aria-labelledby="main-menu-tab" style="color: white;">

            <div class="mobile-menu-widget">
                <div class="single-product-widget">
                    <!-- <h5 class="widget-title">Product categories</h5> -->
                    <div class="product-cat-widget">
                        <ul class="accordion">
                            <!-- main list -->
                            <li><a href="<?php echo e(route('front.index')); ?>"><?php echo app('translator')->get('Home'); ?></a></li>
                            <li><a href="<?php echo e(route('front.category')); ?>"><?php echo app('translator')->get('Product'); ?></a></li>
                            <li>
                                <a href="#" data-bs-toggle="collapse" data-bs-target="#child_level_1"
                                    aria-controls="child_level_1" aria-expanded="false" class="collapsed">
                                    <?php echo app('translator')->get('Pages'); ?>
                                </a>

                                <ul id="child_level_1" class="accordion-collapse collapse ms-3">
                                    <?php $__currentLoopData = $pages->where('header', '=', 1); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $data): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <li>
                                            <a href="<?php echo e(route('front.vendor', $data->slug)); ?>"><?php echo e($data->title); ?></a>
                                        </li>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

                                </ul>
                            </li>
                            <li><a href="<?php echo e(route('front.blog')); ?>"><?php echo app('translator')->get('BLOG'); ?></a></li>
                            <li><a href="<?php echo e(route('front.faq')); ?>"><?php echo app('translator')->get('FAQ'); ?></a></li>
                            <li><a href="<?php echo e(route('front.contact')); ?>"><?php echo app('translator')->get('CONTACT'); ?></a></li>

                        </ul>

                        <div class="auth-actions-btn gap-4 d-flex flex-column">

                            
                            <?php if(Auth::guard('web')->check() && Auth::guard('web')->user()->is_vendor == 2): ?>
                                <a class="template-btn" href="<?php echo e(route('vendor.dashboard')); ?>"><?php echo app('translator')->get('Vendor Panel'); ?></a>
                            <?php elseif(!Auth::guard('web')->check() && !Auth::guard('rider')->check()): ?>
                                <a class="template-btn" href="<?php echo e(route('vendor.login')); ?>"><?php echo app('translator')->get('Vendor Login'); ?></a>
                            <?php endif; ?>

                            
                            <?php if(Auth::guard('rider')->check()): ?>
                                <a class="template-btn" href="<?php echo e(route('rider-dashboard')); ?>"><?php echo app('translator')->get('Rider Dashboard'); ?></a>
                            <?php elseif(!Auth::guard('web')->check() && !Auth::guard('rider')->check()): ?>
                                <a class="template-btn" href="<?php echo e(route('rider.login')); ?>"><?php echo app('translator')->get('Rider Login'); ?></a>
                            <?php endif; ?>

                            
                            <?php if(Auth::guard('web')->check() && Auth::guard('web')->user()->is_vendor != 2): ?>
                                <a class="template-btn" href="<?php echo e(route('user-dashboard')); ?>"><?php echo app('translator')->get('User Dashboard'); ?></a>
                            <?php elseif(!Auth::guard('web')->check() && !Auth::guard('rider')->check()): ?>
                                <a class="template-btn" href="<?php echo e(route('user.login')); ?>"><?php echo app('translator')->get('User Login'); ?></a>
                            <?php endif; ?>

                        </div>



                    </div>
                </div>
            </div>

        </div>
    </div>

    <div class="tab-content " id="nav-tabContent3">
        <div class="tab-pane fade table-responsive tb-tb" id="categories" role="tabpanel"
            aria-labelledby="categories-tab" style="color: white;">

            <div class="mobile-menu-widget">
                <div class="single-product-widget">
                    <!-- <h5 class="widget-title">Product categories</h5> -->
                    <div class="product-cat-widget">
                        <ul class="accordion">
                            <?php $__currentLoopData = $categories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $category): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <?php if($category->subs->count() > 0): ?>
                                    <li>
                                        <?php
                                            $isCategoryActive = Request::segment(2) === $category->slug;
                                        ?>
                                        <div class="d-flex justify-content-between align-items-lg-baseline">
                                            <a href="<?php echo e(route('front.category', $category->slug)); ?>"
                                                class="<?php echo e($isCategoryActive ? 'sidebar-active-color' : ''); ?>">
                                                <?php echo e($category->name); ?>

                                            </a>

                                            <button data-bs-toggle="collapse"
                                                data-bs-target="#<?php echo e($category->slug); ?>_level_2"
                                                aria-controls="<?php echo e($category->slug); ?>_level_2"
                                                aria-expanded="<?php echo e($isCategoryActive ? 'true' : 'false'); ?>"
                                                class="position-relative bottom-12 <?php echo e($isCategoryActive ? '' : 'collapsed'); ?>">
                                                <i class="fa-solid fa-plus"></i>
                                                <i class="fa-solid fa-minus"></i>
                                            </button>
                                        </div>

                                        <?php $__currentLoopData = $category->subs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $subcategory): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <?php
                                                $isSubcategoryActive =
                                                    $isCategoryActive && Request::segment(3) === $subcategory->slug;
                                            ?>
                                            <ul id="<?php echo e($category->slug); ?>_level_2"
                                                class="accordion-collapse collapse ms-3 <?php echo e($isCategoryActive ? 'show' : ''); ?>">
                                                <li class="">
                                                    <div class="d-flex justify-content-between align-items-lg-baseline">
                                                        <a href="<?php echo e(route('front.category', [$category->slug, $subcategory->slug])); ?>"
                                                            class="<?php echo e($isSubcategoryActive ? 'sidebar-active-color' : ''); ?> "
                                                            <?php if($subcategory->childs->count() > 0): ?> data-bs-toggle="collapse"
                                        data-bs-target="#inner<?php echo e($subcategory->slug); ?>_level_2_1"
                                        aria-controls="inner<?php echo e($subcategory->slug); ?>_level_2_1"
                                        aria-expanded="<?php echo e($isSubcategoryActive ? 'true' : 'false'); ?>"
                                        class="<?php echo e($isSubcategoryActive ? '' : 'collapsed'); ?>" <?php endif; ?>>
                                                            <?php echo e($subcategory->name); ?>

                                                        </a>

                                                        <?php if($subcategory->childs->count() > 0): ?>
                                                            <button data-bs-toggle="collapse"
                                                                data-bs-target="#inner<?php echo e($subcategory->slug); ?>_level_2_1"
                                                                aria-controls="inner<?php echo e($subcategory->slug); ?>_level_2_1"
                                                                aria-expanded="<?php echo e($isSubcategoryActive ? 'true' : 'false'); ?>"
                                                                class="position-relative bottom-12 <?php echo e($isSubcategoryActive ? '' : 'collapsed'); ?>">
                                                                <i class="fa-solid fa-plus"></i>
                                                                <i class="fa-solid fa-minus"></i>
                                                            </button>
                                                        <?php endif; ?>
                                                    </div>

                                                    <?php if($subcategory->childs->count() > 0): ?>
                                                        <ul id="inner<?php echo e($subcategory->slug); ?>_level_2_1"
                                                            class="accordion-collapse collapse ms-3 <?php echo e($isSubcategoryActive ? 'show' : ''); ?>">
                                                            <?php $__currentLoopData = $subcategory->childs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $child): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                                <?php
                                                                    $isChildActive =
                                                                        $isSubcategoryActive &&
                                                                        Request::segment(4) === $child->slug;
                                                                ?>
                                                                <li>
                                                                    <a href="<?php echo e(route('front.category', [$category->slug, $subcategory->slug, $child->slug])); ?>"
                                                                        class="<?php echo e($isChildActive ? 'sidebar-active-color' : ''); ?>">
                                                                        <?php echo e($child->name); ?>

                                                                    </a>
                                                                </li>
                                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                                        </ul>
                                                    <?php endif; ?>
                                                </li>
                                            </ul>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

                                    </li>
                                <?php else: ?>
                                    <li>
                                        <a href="<?php echo e(route('front.category', $category->slug)); ?>"
                                            class="<?php echo e(Request::segment(2) === $category->slug ? 'active' : ''); ?>">
                                            <?php echo e($category->name); ?>

                                        </a>
                                    </li>
                                <?php endif; ?>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- search bar -->
<div class="search-bar" id="searchBar">
    <div class="container">
        <div class="row">
            <div class="col">
                <form class="search-form"
                    action="<?php echo e(route('front.category', [Request::route('category'), Request::route('subcategory'), Request::route('childcategory')])); ?>">

                    <?php if(!empty(request()->input('sort'))): ?>
                        <input type="hidden" name="sort" value="<?php echo e(request()->input('sort')); ?>">
                    <?php endif; ?>
                    <?php if(!empty(request()->input('minprice'))): ?>
                        <input type="hidden" name="minprice" value="<?php echo e(request()->input('minprice')); ?>">
                    <?php endif; ?>
                    <?php if(!empty(request()->input('maxprice'))): ?>
                        <input type="hidden" name="maxprice" value="<?php echo e(request()->input('maxprice')); ?>">
                    <?php endif; ?>

                    <div class="input-group input__group">
                        <input type="text" class="form-control form__control" name="search"
                            placeholder="<?php echo app('translator')->get('Search Any Product Here'); ?>">
                        <div class="input-group-append">
                            <span class="search-separator"></span>
                            <button class="dropdown-toggle btn btn-secondary search-category-dropdown" type="button"
                                data-bs-toggle="dropdown" aria-expanded="false">
                                <?php echo app('translator')->get('All Categories'); ?>
                            </button>
                            <ul class="dropdown-menu">
                                <?php $__currentLoopData = $categories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $category): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <li>
                                        <a class="dropdown-item dropdown__item"
                                            href="javascript:;"><?php echo e($category->name); ?></a>
                                    </li>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </ul>
                        </div>


                        <div class="input-group-append">
                            <button class="btn btn-primary search-icn" type="submit">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                    viewBox="0 0 24 24" fill="none">
                                    <path
                                        d="M21 21L17.5001 17.5M20 11.5C20 16.1944 16.1944 20 11.5 20C6.80558 20 3 16.1944 3 11.5C3 6.80558 6.80558 3 11.5 3C16.1944 3 20 6.80558 20 11.5Z"
                                        stroke="white" stroke-width="2" stroke-linecap="round"
                                        stroke-linejoin="round" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php /**PATH C:\Users\hp\Herd\new\resources\views/includes/frontend/mobile_menu.blade.php ENDPATH**/ ?>