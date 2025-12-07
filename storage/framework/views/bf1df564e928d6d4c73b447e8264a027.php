<?php $__env->startSection('content'); ?>
    <section class="gs-breadcrumb-section bg-class"
        data-background="<?php echo e($gs->breadcrumb_banner ? asset('assets/images/' . $gs->breadcrumb_banner) : asset('assets/images/noimage.png')); ?>">
        <div class="container">
            <div class="row justify-content-center content-wrapper">
                <div class="col-12">
                    <h2 class="breadcrumb-title"><?php echo app('translator')->get('Checkout'); ?></h2>
                    <ul class="bread-menu">
                        <li><a href="<?php echo e(route('front.index')); ?>"><?php echo app('translator')->get('Home'); ?></a></li>
                        <li><a href="<?php echo e(route('front.cart')); ?>"><?php echo app('translator')->get('Cart'); ?></a></li>
                        <li><?php echo app('translator')->get('Checkout'); ?></li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <div class="gs-checkout-wrapper">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-6 wow-replaced" data-wow-delay=".1s">
                    <div class="checkout-step-wrapper">
                        <span class="line"></span>
                        <span class="line-2"></span>
                        <span class="line-3 d-none"></span>
                        <div class="single-step active">
                            <span class="step-btn">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                    fill="none">
                                    <path d="M20 6L9 17L4 12" stroke="white" stroke-width="2" stroke-linecap="round"
                                        stroke-linejoin="round" />
                                </svg>
                            </span>
                            <span class="step-txt"><?php echo app('translator')->get('Address'); ?></span>
                        </div>
                        <div class="single-step active">
                            <span class="step-btn">2</span>
                            <span class="step-txt"><?php echo app('translator')->get('Details'); ?></span>
                        </div>
                        <div class="single-step">
                            <span class="step-btn">3</span>
                            <span class="step-txt"><?php echo app('translator')->get('Payment'); ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- address-->
            <form class="address-wrapper" action="<?php echo e(isset($is_vendor_checkout) && $is_vendor_checkout ? route('front.checkout.vendor.step2.submit', $vendor_id) : route('front.checkout.step2.submit')); ?>" method="POST">
                <?php echo csrf_field(); ?>
                <div class="row gy-4">
                    <div class="col-lg-7 col-xl-8 wow-replaced" data-wow-delay=".2s">
                        <div class="shipping-billing-address-wrapper">
                            <!-- shipping address -->
                            <div class="single-addres">
                                <div class="title-wrapper d-flex justify-content-between">
                                    <h5><?php echo app('translator')->get('Billing Address'); ?></h5>
                                    <a class="edit-btn" href="<?php echo e(isset($is_vendor_checkout) && $is_vendor_checkout ? route('front.checkout.vendor', $vendor_id) : route('front.cart')); ?>"><?php echo app('translator')->get('Edit'); ?></a>
                                </div>

                                <ul>
                                    <li>
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                            viewBox="0 0 24 24" fill="none">
                                            <path
                                                d="M11.9999 15C8.82977 15 6.01065 16.5306 4.21585 18.906C3.82956 19.4172 3.63641 19.6728 3.64273 20.0183C3.64761 20.2852 3.81521 20.6219 4.02522 20.7867C4.29704 21 4.67372 21 5.42708 21H18.5726C19.326 21 19.7027 21 19.9745 20.7867C20.1845 20.6219 20.3521 20.2852 20.357 20.0183C20.3633 19.6728 20.1701 19.4172 19.7839 18.906C17.9891 16.5306 15.1699 15 11.9999 15Z"
                                                stroke="#1F0300" stroke-width="2" stroke-linecap="round"
                                                stroke-linejoin="round" />
                                            <path
                                                d="M11.9999 12C14.4851 12 16.4999 9.98528 16.4999 7.5C16.4999 5.01472 14.4851 3 11.9999 3C9.51457 3 7.49985 5.01472 7.49985 7.5C7.49985 9.98528 9.51457 12 11.9999 12Z"
                                                stroke="#1F0300" stroke-width="2" stroke-linecap="round"
                                                stroke-linejoin="round" />
                                        </svg>

                                        <span class="title"><?php echo e($step1->customer_name); ?></span>
                                    </li>
                                    <li>
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                            viewBox="0 0 24 24" fill="none">
                                            <path
                                                d="M12 12.5C13.6569 12.5 15 11.1569 15 9.5C15 7.84315 13.6569 6.5 12 6.5C10.3431 6.5 9 7.84315 9 9.5C9 11.1569 10.3431 12.5 12 12.5Z"
                                                stroke="#1F0300" stroke-width="2" stroke-linecap="round"
                                                stroke-linejoin="round" />
                                            <path
                                                d="M12 22C14 18 20 15.4183 20 10C20 5.58172 16.4183 2 12 2C7.58172 2 4 5.58172 4 10C4 15.4183 10 18 12 22Z"
                                                stroke="#1F0300" stroke-width="2" stroke-linecap="round"
                                                stroke-linejoin="round" />
                                        </svg>

                                        <span class="title"><?php echo e($step1->customer_address); ?></span>
                                    </li>
                                    <li>
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                            viewBox="0 0 24 24" fill="none">
                                            <path
                                                d="M8.38028 8.85335C9.07627 10.303 10.0251 11.6616 11.2266 12.8632C12.4282 14.0648 13.7869 15.0136 15.2365 15.7096C15.3612 15.7694 15.4235 15.7994 15.5024 15.8224C15.7828 15.9041 16.127 15.8454 16.3644 15.6754C16.4313 15.6275 16.4884 15.5704 16.6027 15.4561C16.9523 15.1064 17.1271 14.9316 17.3029 14.8174C17.9658 14.3864 18.8204 14.3864 19.4833 14.8174C19.6591 14.9316 19.8339 15.1064 20.1835 15.4561L20.3783 15.6509C20.9098 16.1824 21.1755 16.4481 21.3198 16.7335C21.6069 17.301 21.6069 17.9713 21.3198 18.5389C21.1755 18.8242 20.9098 19.09 20.3783 19.6214L20.2207 19.779C19.6911 20.3087 19.4263 20.5735 19.0662 20.7757C18.6667 21.0001 18.0462 21.1615 17.588 21.1601C17.1751 21.1589 16.8928 21.0788 16.3284 20.9186C13.295 20.0576 10.4326 18.4332 8.04466 16.0452C5.65668 13.6572 4.03221 10.7948 3.17124 7.76144C3.01103 7.19699 2.93092 6.91477 2.9297 6.50182C2.92833 6.0436 3.08969 5.42311 3.31411 5.0236C3.51636 4.66357 3.78117 4.39876 4.3108 3.86913L4.46843 3.7115C4.99987 3.18006 5.2656 2.91433 5.55098 2.76999C6.11854 2.48292 6.7888 2.48292 7.35636 2.76999C7.64174 2.91433 7.90747 3.18006 8.43891 3.7115L8.63378 3.90637C8.98338 4.25597 9.15819 4.43078 9.27247 4.60655C9.70347 5.26945 9.70347 6.12403 9.27247 6.78692C9.15819 6.96269 8.98338 7.1375 8.63378 7.4871C8.51947 7.60142 8.46231 7.65857 8.41447 7.72538C8.24446 7.96281 8.18576 8.30707 8.26748 8.58743C8.29048 8.66632 8.32041 8.72866 8.38028 8.85335Z"
                                                stroke="#1F0300" stroke-width="2" stroke-linecap="round"
                                                stroke-linejoin="round" />
                                        </svg>

                                        <span class="title"><?php echo e($step1->customer_phone); ?></span>
                                    </li>
                                    <li>
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                            viewBox="0 0 24 24" fill="none">
                                            <path
                                                d="M2 7L10.1649 12.7154C10.8261 13.1783 11.1567 13.4097 11.5163 13.4993C11.8339 13.5785 12.1661 13.5785 12.4837 13.4993C12.8433 13.4097 13.1739 13.1783 13.8351 12.7154L22 7M6.8 20H17.2C18.8802 20 19.7202 20 20.362 19.673C20.9265 19.3854 21.3854 18.9265 21.673 18.362C22 17.7202 22 16.8802 22 15.2V8.8C22 7.11984 22 6.27976 21.673 5.63803C21.3854 5.07354 20.9265 4.6146 20.362 4.32698C19.7202 4 18.8802 4 17.2 4H6.8C5.11984 4 4.27976 4 3.63803 4.32698C3.07354 4.6146 2.6146 5.07354 2.32698 5.63803C2 6.27976 2 7.11984 2 8.8V15.2C2 16.8802 2 17.7202 2.32698 18.362C2.6146 18.9265 3.07354 19.3854 3.63803 19.673C4.27976 20 5.11984 20 6.8 20Z"
                                                stroke="#1F0300" stroke-width="2" stroke-linecap="round"
                                                stroke-linejoin="round" />
                                        </svg>

                                        <span class="title"><?php echo e($step1->customer_email); ?></span>
                                    </li>
                                </ul>
                            </div>

                            <?php if(isset($step1->is_shipping) && $step1->is_shipping): ?>
                                <div class="single-addres">
                                    <div class="title-wrapper">
                                        <h5><?php echo app('translator')->get('Shipping Address'); ?></h5>
                                    </div>

                                    <ul>
                                        <?php if($step1->shipping_name): ?>
                                            <li>
                                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                                    viewBox="0 0 24 24" fill="none">
                                                    <path
                                                        d="M11.9999 15C8.82977 15 6.01065 16.5306 4.21585 18.906C3.82956 19.4172 3.63641 19.6728 3.64273 20.0183C3.64761 20.2852 3.81521 20.6219 4.02522 20.7867C4.29704 21 4.67372 21 5.42708 21H18.5726C19.326 21 19.7027 21 19.9745 20.7867C20.1845 20.6219 20.3521 20.2852 20.357 20.0183C20.3633 19.6728 20.1701 19.4172 19.7839 18.906C17.9891 16.5306 15.1699 15 11.9999 15Z"
                                                        stroke="#1F0300" stroke-width="2" stroke-linecap="round"
                                                        stroke-linejoin="round" />
                                                    <path
                                                        d="M11.9999 12C14.4851 12 16.4999 9.98528 16.4999 7.5C16.4999 5.01472 14.4851 3 11.9999 3C9.51457 3 7.49985 5.01472 7.49985 7.5C7.49985 9.98528 9.51457 12 11.9999 12Z"
                                                        stroke="#1F0300" stroke-width="2" stroke-linecap="round"
                                                        stroke-linejoin="round" />
                                                </svg>

                                                <span class="title"><?php echo e($step1->shipping_name); ?></span>
                                            </li>
                                        <?php endif; ?>

                                        <?php if($step1->shipping_address): ?>
                                            <li>
                                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                                    viewBox="0 0 24 24" fill="none">
                                                    <path
                                                        d="M12 12.5C13.6569 12.5 15 11.1569 15 9.5C15 7.84315 13.6569 6.5 12 6.5C10.3431 6.5 9 7.84315 9 9.5C9 11.1569 10.3431 12.5 12 12.5Z"
                                                        stroke="#1F0300" stroke-width="2" stroke-linecap="round"
                                                        stroke-linejoin="round" />
                                                    <path
                                                        d="M12 22C14 18 20 15.4183 20 10C20 5.58172 16.4183 2 12 2C7.58172 2 4 5.58172 4 10C4 15.4183 10 18 12 22Z"
                                                        stroke="#1F0300" stroke-width="2" stroke-linecap="round"
                                                        stroke-linejoin="round" />
                                                </svg>

                                                <span class="title"><?php echo e($step1->shipping_address); ?></span>
                                            </li>
                                        <?php endif; ?>
                                        <?php if($step1->shipping_phone): ?>
                                            <li>
                                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                                    viewBox="0 0 24 24" fill="none">
                                                    <path
                                                        d="M8.38028 8.85335C9.07627 10.303 10.0251 11.6616 11.2266 12.8632C12.4282 14.0648 13.7869 15.0136 15.2365 15.7096C15.3612 15.7694 15.4235 15.7994 15.5024 15.8224C15.7828 15.9041 16.127 15.8454 16.3644 15.6754C16.4313 15.6275 16.4884 15.5704 16.6027 15.4561C16.9523 15.1064 17.1271 14.9316 17.3029 14.8174C17.9658 14.3864 18.8204 14.3864 19.4833 14.8174C19.6591 14.9316 19.8339 15.1064 20.1835 15.4561L20.3783 15.6509C20.9098 16.1824 21.1755 16.4481 21.3198 16.7335C21.6069 17.301 21.6069 17.9713 21.3198 18.5389C21.1755 18.8242 20.9098 19.09 20.3783 19.6214L20.2207 19.779C19.6911 20.3087 19.4263 20.5735 19.0662 20.7757C18.6667 21.0001 18.0462 21.1615 17.588 21.1601C17.1751 21.1589 16.8928 21.0788 16.3284 20.9186C13.295 20.0576 10.4326 18.4332 8.04466 16.0452C5.65668 13.6572 4.03221 10.7948 3.17124 7.76144C3.01103 7.19699 2.93092 6.91477 2.9297 6.50182C2.92833 6.0436 3.08969 5.42311 3.31411 5.0236C3.51636 4.66357 3.78117 4.39876 4.3108 3.86913L4.46843 3.7115C4.99987 3.18006 5.2656 2.91433 5.55098 2.76999C6.11854 2.48292 6.7888 2.48292 7.35636 2.76999C7.64174 2.91433 7.90747 3.18006 8.43891 3.7115L8.63378 3.90637C8.98338 4.25597 9.15819 4.43078 9.27247 4.60655C9.70347 5.26945 9.70347 6.12403 9.27247 6.78692C9.15819 6.96269 8.98338 7.1375 8.63378 7.4871C8.51947 7.60142 8.46231 7.65857 8.41447 7.72538C8.24446 7.96281 8.18576 8.30707 8.26748 8.58743C8.29048 8.66632 8.32041 8.72866 8.38028 8.85335Z"
                                                        stroke="#1F0300" stroke-width="2" stroke-linecap="round"
                                                        stroke-linejoin="round" />
                                                </svg>

                                                <span class="title"><?php echo e($step1->shipping_phone); ?></span>
                                            </li>
                                        <?php endif; ?>

                                        <li>
                                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                                viewBox="0 0 24 24" fill="none">
                                                <path
                                                    d="M2 7L10.1649 12.7154C10.8261 13.1783 11.1567 13.4097 11.5163 13.4993C11.8339 13.5785 12.1661 13.5785 12.4837 13.4993C12.8433 13.4097 13.1739 13.1783 13.8351 12.7154L22 7M6.8 20H17.2C18.8802 20 19.7202 20 20.362 19.673C20.9265 19.3854 21.3854 18.9265 21.673 18.362C22 17.7202 22 16.8802 22 15.2V8.8C22 7.11984 22 6.27976 21.673 5.63803C21.3854 5.07354 20.9265 4.6146 20.362 4.32698C19.7202 4 18.8802 4 17.2 4H6.8C5.11984 4 4.27976 4 3.63803 4.32698C3.07354 4.6146 2.6146 5.07354 2.32698 5.63803C2 6.27976 2 7.11984 2 8.8V15.2C2 16.8802 2 17.7202 2.32698 18.362C2.6146 18.9265 3.07354 19.3854 3.63803 19.673C4.27976 20 5.11984 20 6.8 20Z"
                                                    stroke="#1F0300" stroke-width="2" stroke-linecap="round"
                                                    stroke-linejoin="round" />
                                            </svg>

                                            <span class="title"><?php echo e($step1->customer_email); ?></span>
                                        </li>
                                    </ul>
                                </div>
                            <?php endif; ?>

                        </div>

                        <?php
                            foreach ($products as $key => $item) {
                                $userId = $item['user_id'];
                                if (!isset($resultArray[$userId])) {
                                    $resultArray[$userId] = [];
                                }
                                $resultArray[$userId][$key] = $item;
                            }

                        ?>

                        <?php
                            $is_Digital = 1;
                        ?>

                        <?php $__currentLoopData = $resultArray; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $vendor_id => $array_product): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <?php

                                if ($vendor_id != 0) {
                                    $shipping = App\Models\Shipping::forVendor($vendor_id)->get();
                                    $packaging = App\Models\Package::where('user_id', $vendor_id)->get();
                                    // No fallback to user 0 - if vendor has no packages, $packaging will be empty
                                    $vendor = App\Models\User::findOrFail($vendor_id);
                                } else {
                                    $shipping = App\Models\Shipping::forVendor(0)->get();
                                    $packaging = collect(); // Empty collection - no global packaging
                                    $vendor = App\Models\Admin::findOrFail(1);
                                }

                                // Group shipping by provider
                                $groupedShipping = $shipping->groupBy('provider');

                                // Provider labels
                                $providerLabels = [
                                    'manual' => __('Manual Shipping'),
                                    'debts' => __('Debts Shipping'),
                                    'tryoto' => __('Smart Shipping (Tryoto)'),
                                ];

                            ?>

                            <div class="product-infos-wrapper wow-replaced" data-wow-delay=".2s">
                                <!-- shop-info-wrapper -->

                                <!-- product list  -->
                                <div class="product-list">
                                    <?php $__currentLoopData = $array_product; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $product): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <?php
                                            if ($product['dp'] == 0) {
                                                $is_Digital = 0;
                                            }
                                        ?>
                                        <div class="checkout-single-product wow-replaced" data-wow-delay=".1s">
                                            <div class="img-wrapper">
                                                <a href="#">
                                                    <img width="200" class="img-cls"
                                                        src="<?php echo e($product['item']['photo'] ? \Illuminate\Support\Facades\Storage::url($product['item']['photo']) : asset('assets/images/noimage.png')); ?>"
                                                        alt="product">
                                                </a>
                                            </div>
                                            <div class="content-wrapper">
                                                <?php
                                                    $checkoutProductUrl = '#';
                                                    if (isset($product['item']['slug']) && isset($product['user_id']) && isset($product['merchant_product_id'])) {
                                                        $checkoutProductUrl = route('front.product', [
                                                            'slug' => $product['item']['slug'],
                                                            'vendor_id' => $product['user_id'],
                                                            'merchant_product_id' => $product['merchant_product_id']
                                                        ]);
                                                    } elseif (isset($product['item']['slug'])) {
                                                        $checkoutProductUrl = route('front.product.legacy', $product['item']['slug']);
                                                    }
                                                ?>
                                                <h6>
                                                    <a class="product-title"
                                                        href="<?php echo e($checkoutProductUrl); ?>"
                                                        target="_blank">
                                                        <?php echo e(getLocalizedProductName($product['item'])); ?>

                                                    </a>
                                                </h6>

                                                <ul class="product-specifications-list">
                                                    <li>
                                                        <span class="specification-name"><?php echo app('translator')->get('Price :'); ?></span>
                                                        <span class="specification">
                                                            <?php echo e(App\Models\Product::convertPrice($product['item_price'])); ?></span>
                                                    </li>
                                                    <li>
                                                        <span class="specification-name"><?php echo app('translator')->get('Quantity :'); ?></span>
                                                        <span class="specification"><?php echo e($product['qty']); ?></span>
                                                    </li>
                                                    <?php if(!empty($product['size'])): ?>
                                                        <li>
                                                            <span class="specification-name"><?php echo e(__('Size')); ?> : </span>
                                                            <span
                                                                class="specification"><?php echo e(str_replace('-', ' ', $product['size'])); ?></span>
                                                        </li>
                                                    <?php endif; ?>


                                                    <?php if(!empty($product['color'])): ?>
                                                        <li>
                                                            <span class="specification-name"><?php echo app('translator')->get('Color :'); ?> </span>
                                                            <span class="specification muaadh-color-swatch"
                                                                style="--swatch-color: <?php echo e($product['color'] == '' ? 'white' : '#' . $product['color']); ?>;"></span>
                                                        </li>
                                                    <?php endif; ?>

                                                    <?php if(!empty($product['keys'])): ?>
                                                        <?php $__currentLoopData = array_combine(explode(',', $product['keys']), explode(',', $product['values'])); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $value): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                            <li>
                                                                <span
                                                                    class="specification-name"><?php echo e(ucwords(str_replace('_', ' ', $key))); ?>

                                                                    : </span>
                                                                <span class="specification"><?php echo e($value); ?></span>
                                                            </li>
                                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                                    <?php endif; ?>

                                                    <li>
                                                        <span class="specification-name"><?php echo app('translator')->get('Total Price :'); ?> </span>
                                                        <span
                                                            class="specification"><?php echo e(App\Models\Product::convertPrice($product['price'])); ?>

                                                            <?php echo e($product['discount'] == 0 ? '' : '(' . $product['discount'] . '%' . __('Off') . ')'); ?></span>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

                                </div>


                                <?php if($gs->multiple_shipping == 1): ?>
                                    <div class="shop-info-wrapper">
                                        <ul>
                                            <li>
                                                <span><b><?php echo app('translator')->get('Shop Name :'); ?></b></span>
                                                <span><?php echo e($vendor->shop_name); ?></span>
                                            </li>
                                            <li>
                                                <span><b><?php echo app('translator')->get('Shop Phone :'); ?></b></span>
                                                <span><?php echo e($vendor->phone); ?></span>
                                            </li>
                                            <li>
                                                <span><b><?php echo app('translator')->get('Shop Address:'); ?></b></span>
                                                <span><?php echo e($vendor->address); ?></span>
                                            </li>
                                            <li>

                                            </li>
                                        </ul>


                                        
                                        <?php if(isset($step1->vendor_tax_data[$vendor_id])): ?>
                                            <?php
                                                $vendorTax = $step1->vendor_tax_data[$vendor_id];
                                                $vendorTaxRate = $vendorTax['tax_rate'] ?? 0;
                                                $vendorTaxAmount = $vendorTax['tax_amount'] ?? 0;
                                            ?>
                                            <?php if($vendorTaxRate > 0): ?>
                                            <div class="d-flex flex-wrap gap-2 mb-3 bg-light-white p-4 align-items-center">
                                                <span class="label mr-2">
                                                    <b><?php echo e(__('Tax')); ?> (<?php echo e($vendorTaxRate); ?>%):</b>
                                                </span>
                                                <span class="fw-bold text-success">
                                                    <?php echo e(App\Models\Product::convertPrice($vendorTaxAmount)); ?>

                                                </span>
                                                <?php if(isset($step1->tax_location)): ?>
                                                <small class="text-muted ms-2">(<?php echo e($step1->tax_location); ?>)</small>
                                                <?php endif; ?>
                                            </div>
                                            <?php endif; ?>
                                        <?php endif; ?>

                                        <?php if($is_Digital == 0 && $packaging->isNotEmpty()): ?>
                                            <div class="d-flex flex-wrap gap-2 mb-3 bg-light-white p-4">
                                                <span class="label mr-2">
                                                    <b><?php echo e(__('Packageing :')); ?></b>
                                                </span>
                                                <p id="packing_text<?php echo e($vendor_id); ?>">
                                                    <?php echo e($packaging[0]['title'] . '+' . $curr->sign . round($packaging[0]['price'] * $curr->value, 2)); ?>

                                                </p>
                                                <button type="button" class="template-btn sm-btn" data-bs-toggle="modal"
                                                    data-bs-target="#vendor_package<?php echo e($vendor_id); ?>">
                                                    <?php echo e(__('Select Package')); ?>

                                                </button>
                                            </div>
                                        <?php endif; ?>
                                        <?php if($is_Digital == 0): ?>
                                            <div class="d-flex flex-wrap gap-2 mb-3 bg-light-white p-4">
                                                <span class="label mr-2">
                                                    <b><?php echo e(__('Shipping Methods:')); ?></b>
                                                </span>

                                                
                                                <?php $__currentLoopData = $groupedShipping; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $provider => $methods): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                    <?php
                                                        $providerLabel = $providerLabels[$provider] ?? ucfirst($provider);
                                                        $modalId = "vendor_{$provider}_shipping_{$vendor_id}";
                                                    ?>

                                                    <button type="button" class="template-btn sm-btn"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#<?php echo e($modalId); ?>">
                                                        <?php echo e($providerLabel); ?>

                                                    </button>
                                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

                                                
                                                <p id="shipping_text<?php echo e($vendor_id); ?>" class="ms-auto mb-0">
                                                    <?php echo app('translator')->get('Not Selected'); ?>
                                                </p>
                                            </div>

                                            
                                            <?php $__currentLoopData = $groupedShipping; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $provider => $methods): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                <?php
                                                    $providerLabel = $providerLabels[$provider] ?? ucfirst($provider);
                                                    $modalId = "vendor_{$provider}_shipping_{$vendor_id}";
                                                ?>

                                                <?php if($provider === 'tryoto'): ?>
                                                    
                                                    <?php echo $__env->make('includes.frontend.tryoto_shipping_modal', [
                                                        'modalId' => $modalId,
                                                        'providerLabel' => $providerLabel,
                                                        'vendor_id' => $vendor_id,
                                                        'array_product' => $array_product,
                                                    ], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
                                                <?php else: ?>
                                                    
                                                    <?php echo $__env->make('includes.frontend.provider_shipping_modal', [
                                                        'modalId' => $modalId,
                                                        'provider' => $provider,
                                                        'providerLabel' => $providerLabel,
                                                        'methods' => $methods,
                                                        'vendor_id' => $vendor_id,
                                                    ], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
                                                <?php endif; ?>
                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                            <?php if($packaging->isNotEmpty()): ?>
                                                <?php echo $__env->make('includes.frontend.vendor_packaging', [
                                                    'packaging' => $packaging,
                                                    'vendor_id' => $vendor_id,
                                                ], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                <?php else: ?>
                                    <div class="shop-info-wrapper">
                                        <ul>
                                            <li>
                                                <span><b><?php echo app('translator')->get('Shop Name :'); ?></b></span>
                                                <span><?php echo e($vendor->shop_name); ?></span>
                                            </li>
                                            <li>
                                                <span><b><?php echo app('translator')->get('Shop Phone :'); ?></b></span>
                                                <span><?php echo e($vendor->phone); ?></span>
                                            </li>
                                            <li>
                                                <span><b><?php echo app('translator')->get('Shop Address:'); ?></b></span>
                                                <span><?php echo e($vendor->address); ?></span>
                                            </li>
                                        </ul>
                                    </div>
                                <?php endif; ?>



                            </div>
                            <?php
                                $is_Digital = 1;
                            ?>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>



                    </div>
                    <div class="col-lg-5 col-xl-4 wow-replaced" data-wow-delay=".2s">
                        <div class="summary-box">
                            <h4 class="form-title"><?php echo app('translator')->get('Summery'); ?></h4>


                            <?php if($digital == 0): ?>
                                <!-- shipping methods -->
                                <?php if($gs->multiple_shipping == 0): ?>
                                    <div class="summary-inner-box">
                                        <h6 class="summary-title"><?php echo app('translator')->get('Shipping Method'); ?></h6>
                                        <div class="inputs-wrapper">

                                            <?php $__currentLoopData = $shipping_data; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $data): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                <div class="gs-radio-wrapper">
                                                    <input type="radio" class="shipping"
                                                        data-price="<?php echo e(round($data->price * $curr->value, 2)); ?>"
                                                        data-form="<?php echo e($data->title); ?>"
                                                        id="free-shepping<?php echo e($data->id); ?>" name="shipping_id"
                                                        value="<?php echo e($data->id); ?>" <?php echo e($loop->first ? 'checked' : ''); ?>>
                                                    <label class="icon-label" for="free-shepping<?php echo e($data->id); ?>">
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="20"
                                                            height="20" viewBox="0 0 20 20" fill="none">
                                                            <rect x="0.5" y="0.5" width="19" height="19"
                                                                rx="9.5" fill="#FDFDFD" />
                                                            <rect x="0.5" y="0.5" width="19" height="19"
                                                                rx="9.5" stroke="#EE1243" />
                                                            <circle cx="10" cy="10" r="4" fill="#EE1243" />
                                                        </svg>
                                                    </label>
                                                    <label for="free-shepping<?php echo e($data->id); ?>">
                                                        <?php echo e($data->title); ?>

                                                        <?php if($data->price != 0): ?>
                                                            +
                                                            <?php echo e($curr->sign); ?><?php echo e(round($data->price * $curr->value, 2)); ?>

                                                        <?php endif; ?>
                                                        <small><?php echo e($data->subtitle); ?></small>
                                                    </label>
                                                </div>
                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

                                        </div>
                                    </div>

                                    <!-- Packaging -->
                                    <?php if($package_data->isNotEmpty()): ?>
                                        <div class="summary-inner-box">
                                            <h6 class="summary-title"><?php echo app('translator')->get('Packaging'); ?></h6>
                                            <div class="inputs-wrapper">

                                                <?php $__currentLoopData = $package_data; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $data): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                    <div class="gs-radio-wrapper">
                                                        <input type="radio" class="packing"
                                                            data-price="<?php echo e(round($data->price * $curr->value, 2)); ?>"
                                                            data-form="<?php echo e($data->title); ?>"
                                                            id="free-package<?php echo e($data->id); ?>" name="packeging_id"
                                                            value="<?php echo e($data->id); ?>" <?php echo e($loop->first ? 'checked' : ''); ?>>
                                                        <label class="icon-label" for="free-package<?php echo e($data->id); ?>">
                                                            <svg xmlns="http://www.w3.org/2000/svg" width="20"
                                                                height="20" viewBox="0 0 20 20" fill="none">
                                                                <rect x="0.5" y="0.5" width="19" height="19"
                                                                    rx="9.5" fill="#FDFDFD" />
                                                                <rect x="0.5" y="0.5" width="19" height="19"
                                                                    rx="9.5" stroke="#EE1243" />
                                                                <circle cx="10" cy="10" r="4" fill="#EE1243" />
                                                            </svg>
                                                        </label>
                                                        <label for="free-package<?php echo e($data->id); ?>">
                                                            <?php echo e($data->title); ?>

                                                            <?php if($data->price != 0): ?>
                                                                +
                                                                <?php echo e($curr->sign); ?><?php echo e(round($data->price * $curr->value, 2)); ?>

                                                            <?php endif; ?>
                                                            <small><?php echo e($data->subtitle); ?></small>
                                                        </label>
                                                    </div>
                                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                <?php endif; ?>
                            <?php endif; ?>


                            
                            <?php echo $__env->make('includes.checkout-price-summary', [
                                'step' => 2,
                                'productsTotal' => $productsTotal ?? $totalPrice,
                                'totalPrice' => $totalPrice, // Backward compatibility
                                'digital' => $digital,
                                'curr' => $curr,
                                'gs' => $gs,
                                'step1' => $step1 ?? null
                            ], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

                            <!-- btn wrapper -->
                            <div class="summary-inner-box">
                                <div class="btn-wrappers">
                                    <button type="submit" class="template-btn w-100">
                                        <?php echo app('translator')->get('Continue'); ?>
                                        <svg xmlns="http://www.w3.org/2000/svg" width="25" height="24"
                                            viewBox="0 0 25 24" fill="none">
                                            <g clip-path="url(#clip0_489_34176)">
                                                <path
                                                    d="M23.62 9.9099L19.75 5.9999C19.657 5.90617 19.5464 5.83178 19.4246 5.78101C19.3027 5.73024 19.172 5.7041 19.04 5.7041C18.908 5.7041 18.7773 5.73024 18.6554 5.78101C18.5336 5.83178 18.423 5.90617 18.33 5.9999C18.1437 6.18726 18.0392 6.44071 18.0392 6.7049C18.0392 6.96909 18.1437 7.22254 18.33 7.4099L21.89 10.9999H1.5C1.23478 10.9999 0.98043 11.1053 0.792893 11.2928C0.605357 11.4803 0.5 11.7347 0.5 11.9999H0.5C0.5 12.2651 0.605357 12.5195 0.792893 12.707C0.98043 12.8945 1.23478 12.9999 1.5 12.9999H21.95L18.33 16.6099C18.2363 16.7029 18.1619 16.8135 18.1111 16.9353C18.0603 17.0572 18.0342 17.1879 18.0342 17.3199C18.0342 17.4519 18.0603 17.5826 18.1111 17.7045C18.1619 17.8263 18.2363 17.9369 18.33 18.0299C18.423 18.1236 18.5336 18.198 18.6554 18.2488C18.7773 18.2996 18.908 18.3257 19.04 18.3257C19.172 18.3257 19.3027 18.2996 19.4246 18.2488C19.5464 18.198 19.657 18.1236 19.75 18.0299L23.62 14.1499C24.1818 13.5874 24.4974 12.8249 24.4974 12.0299C24.4974 11.2349 24.1818 10.4724 23.62 9.9099Z"
                                                    fill="white" />
                                            </g>
                                            <defs>
                                                <clipPath id="clip0_489_34176">
                                                    <rect width="24" height="24" fill="white"
                                                        transform="translate(0.5)" />
                                                </clipPath>
                                            </defs>
                                        </svg>
                                    </button>
                                    <a href="<?php echo e(isset($is_vendor_checkout) && $is_vendor_checkout ? route('front.checkout.vendor', $vendor_id) : route('front.cart')); ?>" class="template-btn dark-outline w-100">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="25" height="24"
                                            viewBox="0 0 25 24" fill="none">
                                            <g clip-path="url(#clip0_489_34179)">
                                                <path
                                                    d="M1.38 9.9099L5.25 5.9999C5.34296 5.90617 5.45357 5.83178 5.57542 5.78101C5.69728 5.73024 5.82799 5.7041 5.96 5.7041C6.09201 5.7041 6.22272 5.73024 6.34458 5.78101C6.46643 5.83178 6.57704 5.90617 6.67 5.9999C6.85625 6.18726 6.96079 6.44071 6.96079 6.7049C6.96079 6.96909 6.85625 7.22254 6.67 7.4099L3.11 10.9999H23.5C23.7652 10.9999 24.0196 11.1053 24.2071 11.2928C24.3946 11.4803 24.5 11.7347 24.5 11.9999V11.9999C24.5 12.2651 24.3946 12.5195 24.2071 12.707C24.0196 12.8945 23.7652 12.9999 23.5 12.9999H3.05L6.67 16.6099C6.76373 16.7029 6.83812 16.8135 6.88889 16.9353C6.93966 17.0572 6.9658 17.1879 6.9658 17.3199C6.9658 17.4519 6.93966 17.5826 6.88889 17.7045C6.83812 17.8263 6.76373 17.9369 6.67 18.0299C6.57704 18.1236 6.46643 18.198 6.34458 18.2488C6.22272 18.2996 6.09201 18.3257 5.96 18.3257C5.82799 18.3257 5.69728 18.2996 5.57542 18.2488C5.45357 18.198 5.34296 18.1236 5.25 18.0299L1.38 14.1499C0.818197 13.5874 0.50264 12.8249 0.50264 12.0299C0.50264 11.2349 0.818197 10.4724 1.38 9.9099Z"
                                                    fill="#030712" />
                                            </g>
                                            <defs>
                                                <clipPath id="clip0_489_34179">
                                                    <rect width="24" height="24" fill="white"
                                                        transform="matrix(-1 0 0 1 24.5 0)" />
                                                </clipPath>
                                            </defs>
                                        </svg>
                                        <?php echo app('translator')->get('Back to Previous Step'); ?>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>


                <?php if($gs->multiple_shipping == 0 && $digital == 0): ?>
                    <input type="hidden" name="shipping_id" id="multi_shipping_id"
                        value="<?php echo e(@$shipping_data[0]->id); ?>">
                    <input type="hidden" name="packaging_id" id="multi_packaging_id"
                        value="<?php echo e(@$package_data[0]->id); ?>">
                <?php endif; ?>


                <input type="hidden" name="dp" value="<?php echo e($digital); ?>">
                <input type="hidden" id="input_tax" name="tax" value="">
                <input type="hidden" id="input_tax_type" name="tax_type" value="">
                <input type="hidden" name="totalQty" value="<?php echo e($totalQty); ?>">
                <input type="hidden" name="vendor_shipping_id" value="<?php echo e($vendor_shipping_id); ?>">
                <input type="hidden" name="vendor_packing_id" value="<?php echo e($vendor_packing_id); ?>">
                <input type="hidden" name="currency_sign" value="<?php echo e($curr->sign); ?>">
                <input type="hidden" name="currency_name" value="<?php echo e($curr->name); ?>">
                <input type="hidden" name="currency_value" value="<?php echo e($curr->value); ?>">
                <?php
                    // Calculate total with tax for initial display
                    // Support both regular checkout (total_tax_amount) and vendor checkout (tax_amount)
                    $taxAmount = $step1->total_tax_amount ?? $step1->tax_amount ?? 0;
                    $totalWithTax = $totalPrice + $taxAmount;
                ?>
                <?php if(Session::has('coupon_total')): ?>
                    <input type="hidden" name="total" id="grandtotal"
                        value="<?php echo e(round($totalWithTax * $curr->value, 2)); ?>">
                    <input type="hidden" id="tgrandtotal" value="<?php echo e($totalPrice); ?>">
                    <input type="hidden" id="tax_amount_value" value="<?php echo e(round($taxAmount * $curr->value, 2)); ?>">
                <?php elseif(Session::has('coupon_total1')): ?>
                    <input type="hidden" name="total" id="grandtotal"
                        value="<?php echo e(preg_replace(' /[^0-9,.]/', '', Session::get('coupon_total1')) + round($taxAmount * $curr->value, 2)); ?>">
                    <input type="hidden" id="tgrandtotal"
                        value="<?php echo e(preg_replace(' /[^0-9,.]/', '', Session::get('coupon_total1'))); ?>">
                    <input type="hidden" id="tax_amount_value" value="<?php echo e(round($taxAmount * $curr->value, 2)); ?>">
                <?php else: ?>
                    <input type="hidden" name="total" id="grandtotal"
                        value="<?php echo e(round($totalWithTax * $curr->value, 2)); ?>">
                    <input type="hidden" id="tgrandtotal" value="<?php echo e(round($totalPrice * $curr->value, 2)); ?>">
                    <input type="hidden" id="tax_amount_value" value="<?php echo e(round($taxAmount * $curr->value, 2)); ?>">
                <?php endif; ?>
                <input type="hidden" id="original_tax" value="0">
                <input type="hidden" id="wallet-price" name="wallet_price" value="0">
                <input type="hidden" id="ttotal"
                    value="<?php echo e(App\Models\Product::convertPrice($totalPrice)); ?>">
                <input type="hidden" name="coupon_code" id="coupon_code"
                    value="<?php echo e(Session::has('coupon_code') ? Session::get('coupon_code') : ''); ?>">
                <input type="hidden" name="coupon_discount" id="coupon_discount"
                    value="<?php echo e(Session::has('coupon') ? Session::get('coupon') : ''); ?>">
                <input type="hidden" name="coupon_id" id="coupon_id"
                    value="<?php echo e(Session::has('coupon') ? Session::get('coupon_id') : ''); ?>">
                <input type="hidden" name="user_id" id="user_id"
                    value="<?php echo e(Auth::guard('web')->check() ? Auth::guard('web')->user()->id : ''); ?>">






            </form>
        </div>
    </div>
    <!--  checkout wrapper end-->

    <?php
        $country = App\Models\Country::where('country_name', $step1->customer_country)->first();
        $isState = isset($step1->customer_state) ? $step1->customer_state : 0;
    ?>
    <input type="hidden" id="select_country" name="country_id" value="<?php echo e($country->id); ?>">
    <input type="hidden" id="state_id" name="state_id"
        value="<?php echo e(isset($step1->customer_state) ? $step1->customer_state : 0); ?>">
    <input type="hidden" id="is_state" name="is_state" value="<?php echo e($isState); ?>">
    <input type="hidden" id="state_url" name="state_url" value=" <?php echo e(route('country.wise.state', $country->id)); ?>">
<?php $__env->stopSection(); ?>



<?php $__env->startSection('script'); ?>
    <script src="https://js.paystack.co/v1/inline.js"></script>
    <script src="https://sdk.mercadopago.com/js/v2"></script>
    <script src="https://js.stripe.com/v3/"></script>





    <script type="text/javascript">
        var coup = 0;
        var pos = <?php echo e($gs->currency_format); ?>;
        let mship = 0;
        let mpack = 0;

        // ✅ REMOVED: Initial calculation that was showing wrong total
        // The correct total will be calculated by updateFinalTotal() inside $(document).ready()
        // This fixes the issue where packing was not included in the displayed total

        let original_tax = 0;

        $(document).ready(function() {
            console.log('📍 Step2: تحميل الصفحة');

            // ✅ Restore saved shipping/packing selections from step2 session
            <?php if(isset($step2) && $step2): ?>
                <?php if(isset($step2->saved_shipping_selections) && is_array($step2->saved_shipping_selections)): ?>
                    // Restore shipping selections
                    <?php $__currentLoopData = $step2->saved_shipping_selections; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $vendorId => $shippingValue): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        // Find and check the radio for this vendor
                        const shippingRadio<?php echo e($vendorId); ?> = $('input.shipping[name="shipping[<?php echo e($vendorId); ?>]"][value="<?php echo e($shippingValue); ?>"]');
                        if (shippingRadio<?php echo e($vendorId); ?>.length > 0) {
                            shippingRadio<?php echo e($vendorId); ?>.prop('checked', true);
                            console.log('✅ تم استرجاع shipping للـ vendor <?php echo e($vendorId); ?>');
                        }
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                <?php endif; ?>

                <?php if(isset($step2->saved_packing_selections) && is_array($step2->saved_packing_selections)): ?>
                    // Restore packing selections
                    <?php $__currentLoopData = $step2->saved_packing_selections; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $vendorId => $packingValue): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        const packingRadio<?php echo e($vendorId); ?> = $('input.packing[name="packeging[<?php echo e($vendorId); ?>]"][value="<?php echo e($packingValue); ?>"]');
                        if (packingRadio<?php echo e($vendorId); ?>.length > 0) {
                            packingRadio<?php echo e($vendorId); ?>.prop('checked', true);
                            console.log('✅ تم استرجاع packing للـ vendor <?php echo e($vendorId); ?>');
                        }
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                <?php endif; ?>

                // Display saved shipping text
                <?php if(isset($step2->shipping_company)): ?>
                    $('#shipping_text<?php echo e($vendor_id ?? 0); ?>').html('<?php echo e($step2->shipping_company); ?>');
                <?php endif; ?>

                // Display saved packing text
                <?php if(isset($step2->packing_company)): ?>
                    $('#packing_text<?php echo e($vendor_id ?? 0); ?>').html('<?php echo e($step2->packing_company); ?>');
                <?php endif; ?>
            <?php endif; ?>

            // ✅ CRITICAL: Calculate shipping/packing totals FIRST
            getShipping();
            getPacking();

            // ✅ CRITICAL: Update final total after loading saved selections
            updateFinalTotal();

            let country_id = $('#select_country').val();
            let state_id = $('#state_id').val();
            let is_state = $('#is_state').val();
            let state_url = $('#state_url').val();


            if (is_state == 1) {
                if (is_state == 1) {
                    $('.select_state').removeClass('d-none');
                    $.get(state_url, function(response) {
                        $('#show_state').html(response.data);
                        tax_submit(country_id, response.state);
                    });

                } else {
                    tax_submit(country_id, state_id);
                    hide_state();
                }
            } else {
                tax_submit(country_id, state_id);
                hide_state();
            }
        });


        function hide_state() {
            $('.select_state').addClass('d-none');
        }


        function tax_submit(country_id, state_id) {

            $('.gocover').show();
            var total = $("#ttotal").val();
            var ship = 0;
            $.ajax({
                type: "GET",
                url: mainurl + "/country/tax/check",

                data: {
                    state_id: state_id,
                    country_id: country_id,
                    total: total,
                    shipping_cost: ship
                },
                success: function(data) {

                    $('#grandtotal').val(data[0]);
                    $('#tgrandtotal').val(data[0]);
                    $('#original_tax').val(data[1]);
                    $('.tax_show').removeClass('d-none');
                    $('#input_tax').val(data[11]);
                    $('#input_tax_type').val(data[12]);
                    $('.original_tax').html(parseFloat(data[1]) + "%");
                    var ttotal = parseFloat($('#grandtotal').val());
                    var tttotal = parseFloat($('#grandtotal').val()) + (parseFloat(mship) + parseFloat(mpack));
                    ttotal = parseFloat(ttotal).toFixed(2);
                    tttotal = parseFloat(tttotal).toFixed(2);
                    $('#grandtotal').val(data[0] + parseFloat(mship) + parseFloat(mpack));
                    if (pos == 0) {
                        $('#final-cost').html('<?php echo e($curr->sign); ?>' + tttotal);
                        $('.total-cost-dum #total-cost').html('<?php echo e($curr->sign); ?>' + ttotal);
                    } else {
                        $('#total-cost').html('');
                        $('#final-cost').html(tttotal + '<?php echo e($curr->sign); ?>');
                        $('.total-cost-dum #total-cost').html(ttotal + '<?php echo e($curr->sign); ?>');
                    }
                    $('.gocover').hide();
                }
            });
        }


        $('.shipping').on('click', function() {
            getShipping();

            let ref = $(this).attr('ref');
            let view = $(this).attr('view');
            let title = $(this).attr('data-form');
            $('#shipping_text' + ref).html(title + '+' + view);

            // ✅ Use centralized updateFinalTotal() function
            updateFinalTotal();

            $('#multi_shipping_id').val($(this).val());
        })


        $('.packing').on('click', function() {
            getPacking();
            let ref = $(this).attr('ref');
            let view = $(this).attr('view');
            let title = $(this).attr('data-form');
            $('#packing_text' + ref).html(title + '+' + view);

            // ✅ Use centralized updateFinalTotal() function
            updateFinalTotal();

            $('#multi_packaging_id').val($(this).val());
            // ✅ Update vendor_packing_id for vendor checkout
            $('input[name="vendor_packing_id"]').val($(this).val());
        })


        function getShipping() {
            mship = 0;
            $('.shipping').each(function() {
                if ($(this).is(':checked')) {
                    mship += parseFloat($(this).attr('data-price'));
                }
            });
            // ✅ FIXED: Update view OUTSIDE the loop
            $('.shipping_cost_view').html('<?php echo e($curr->sign); ?>' + mship);
            console.log('🚚 Shipping total:', mship);
        }

        function getPacking() {
            mpack = 0;
            $('.packing').each(function() {
                if ($(this).is(':checked')) {
                    mpack += parseFloat($(this).attr('data-price'));
                }
            });
            // ✅ FIXED: Update view OUTSIDE the loop
            $('.packing_cost_view').html('<?php echo e($curr->sign); ?>' + mpack);
            console.log('📦 Packing total:', mpack);
        }

        /**
         * ✅ NEW FUNCTION: Update Final Total
         * Calculates: Products + Tax + Shipping + Packing
         * Called on:
         * - Page load (after restoring selections)
         * - Shipping change
         * - Packing change
         */
        function updateFinalTotal() {
            console.log('🔄 تحديث الإجمالي النهائي...');

            // Get base total (products only, from backend)
            var baseTotal = parseFloat($('#tgrandtotal').val()) || 0;

            // Get tax amount
            var taxAmount = parseFloat($('#tax_amount_value').val()) || 0;

            // Get shipping and packing (calculated by getShipping/getPacking)
            var shippingTotal = parseFloat(mship) || 0;
            var packingTotal = parseFloat(mpack) || 0;

            // ✅ Debug: Check if mship and mpack are set correctly
            console.log('📦 Current values:', {
                'mship (global)': mship,
                'mpack (global)': mpack,
                'baseTotal (#tgrandtotal)': baseTotal,
                'taxAmount (#tax_amount_value)': taxAmount
            });

            // Calculate final total
            var finalTotal = baseTotal + taxAmount + shippingTotal + packingTotal;
            finalTotal = parseFloat(finalTotal).toFixed(2);

            console.log('📊 تفاصيل الحساب:', {
                'Products': baseTotal.toFixed(2),
                'Tax (15%)': taxAmount.toFixed(2),
                'Shipping': shippingTotal.toFixed(2),
                'Packing': packingTotal.toFixed(2),
                '═══════════': '═══════════',
                'TOTAL': finalTotal
            });

            // Update UI
            if (pos == 0) {
                $('#final-cost').html('<?php echo e($curr->sign); ?>' + finalTotal);
            } else {
                $('#final-cost').html(finalTotal + '<?php echo e($curr->sign); ?>');
            }

            // Update hidden field
            $('#grandtotal').val(finalTotal);

            console.log('✅ تم تحديث الإجمالي النهائي:', finalTotal);
        }
    </script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.front', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\Users\hp\Herd\new\resources\views/frontend/checkout/step2.blade.php ENDPATH**/ ?>