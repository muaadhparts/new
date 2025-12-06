<?php $__env->startSection('styles'); ?>
    <link href="<?php echo e(asset('assets/admin/css/product.css')); ?>" rel="stylesheet" />
    <link href="<?php echo e(asset('assets/admin/css/jquery.Jcrop.css')); ?>" rel="stylesheet" />
    <link href="<?php echo e(asset('assets/admin/css/Jcrop-style.css')); ?>" rel="stylesheet" />
    <link href="<?php echo e(asset('assets/admin/css/select2.css')); ?>" rel="stylesheet" />
<?php $__env->stopSection(); ?>
<?php $__env->startSection('content'); ?>
    <div class="content-area">
        <div class="mr-breadcrumb">
            <div class="row">
                <div class="col-lg-12">
                    <h4 class="heading"><?php echo e(__('Physical Product')); ?> <a class="add-btn"
                            href="<?php echo e(route('admin-prod-types')); ?>"><i class="fas fa-arrow-left"></i> <?php echo e(__('Back')); ?></a>
                    </h4>
                    <ul class="links">
                        <li>
                            <a href="<?php echo e(route('admin.dashboard')); ?>"><?php echo e(__('Dashboard')); ?> </a>
                        </li>
                        <li>
                            <a href="javascript:;"><?php echo e(__('Products')); ?> </a>
                        </li>
                        <li>
                            <a href="<?php echo e(route('admin-prod-index')); ?>"><?php echo e(__('All Products')); ?></a>
                        </li>
                        <li>
                            <a href="<?php echo e(route('admin-prod-types')); ?>"><?php echo e(__('Add Product')); ?></a>
                        </li>
                        <li>
                            <a href="<?php echo e(route('admin-prod-create', 'physical')); ?>"><?php echo e(__('Physical Product')); ?></a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        <form id="muaadhform" action="<?php echo e(route('admin-prod-store')); ?>" method="POST" enctype="multipart/form-data">
            <?php echo e(csrf_field()); ?>

            <?php echo $__env->make('alerts.admin.form-both', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
            <div class="row">
                <div class="col-lg-8">
                    <div class="add-product-content">
                        <div class="row">
                            <div class="col-lg-12">
                                <div class="product-description">
                                    <div class="body-area">
                                        <div class="gocover"
                                            style="background: url(<?php echo e(asset('assets/images/' . $gs->admin_loader)); ?>) no-repeat scroll center center rgba(45, 45, 45, 0.5);">
                                        </div>


                                        
                                        <div class="row">
                                            <div class="col-lg-12">
                                                <div class="left-area">
                                                    <h4 class="heading"><?php echo e(__('Vendor')); ?>*</h4>
                                                </div>
                                            </div>
                                            <div class="col-lg-12">
                                                <select id="vendor_id" name="user_id" required="" class="select2">
                                                    <option value=""><?php echo e(__('Select Vendor')); ?></option>
                                                    <?php $__currentLoopData = \App\Models\User::where('is_vendor', 2)->where('ban', 0)->orderBy('shop_name')->get(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $vendor): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                        <option value="<?php echo e($vendor->id); ?>">
                                                            <?php echo e($vendor->shop_name ?: $vendor->name); ?> (<?php echo e($vendor->email); ?>)
                                                        </option>
                                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                                </select>
                                            </div>
                                        </div>

                                        
                                        <div class="row">
                                            <div class="col-lg-12">
                                                <div class="left-area">
                                                    <h4 class="heading"><?php echo e(__('Brand')); ?> (<?php echo e(__('Trademark')); ?>)</h4>
                                                </div>
                                            </div>
                                            <div class="col-lg-12">
                                                <select name="brand_id" class="input-field">
                                                    <option value=""><?php echo e(__('Select Brand')); ?></option>
                                                    <?php $__currentLoopData = \App\Models\Brand::all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $brand): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                        <option value="<?php echo e($brand->id); ?>" style="color: #333;">
                                                            <?php echo e($brand->name); ?> <?php echo e($brand->name_ar ? '- ' . $brand->name_ar : ''); ?>

                                                        </option>
                                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                                </select>
                                            </div>
                                        </div>

                                        
                                        <div class="row">
                                            <div class="col-lg-12">
                                                <div class="left-area">
                                                    <h4 class="heading"><?php echo e(__('Quality Brand')); ?> (<?php echo e(__('Manufacturing Quality')); ?>)</h4>
                                                </div>
                                            </div>
                                            <div class="col-lg-12">
                                                <select name="brand_quality_id" class="input-field">
                                                    <option value=""><?php echo e(__('Select Quality Brand')); ?></option>
                                                    <?php $__currentLoopData = \App\Models\QualityBrand::all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $qb): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                        <option value="<?php echo e($qb->id); ?>" style="color: #333;">
                                                            <?php echo e($qb->name_en); ?> <?php echo e($qb->name_ar ? '- ' . $qb->name_ar : ''); ?> <?php echo e($qb->country ? '(' . $qb->country . ')' : ''); ?>

                                                        </option>
                                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-lg-12">
                                                <div class="left-area">
                                                    <h4 class="heading"><?php echo e(__('Product Name')); ?>* </h4>
                                                    <p class="sub-heading"><?php echo e(__('(In Any Language)')); ?></p>
                                                </div>
                                            </div>
                                            <div class="col-lg-12">
                                                <input type="text" class="input-field"
                                                    placeholder="<?php echo e(__('Enter Product Name')); ?>" name="name"
                                                    required="">
                                            </div>
                                        </div>

                                        
                                        <div class="row">
                                            <div class="col-lg-12">
                                                <div class="left-area">
                                                    <h4 class="heading"><?php echo e(__('Product Name (English)')); ?></h4>
                                                </div>
                                            </div>
                                            <div class="col-lg-12">
                                                <input type="text" class="input-field"
                                                    placeholder="<?php echo e(__('Enter Product Name in English')); ?>"
                                                    name="label_en">
                                            </div>
                                        </div>

                                        
                                        <div class="row">
                                            <div class="col-lg-12">
                                                <div class="left-area">
                                                    <h4 class="heading"><?php echo e(__('Product Name (Arabic)')); ?></h4>
                                                </div>
                                            </div>
                                            <div class="col-lg-12">
                                                <input type="text" class="input-field" dir="rtl"
                                                    placeholder="<?php echo e(__('Enter Product Name in Arabic')); ?>"
                                                    name="label_ar">
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-lg-12">
                                                <div class="left-area">
                                                    <h4 class="heading"><?php echo e(__('Product Sku')); ?>* </h4>
                                                </div>
                                            </div>
                                            <div class="col-lg-12">
                                                <input type="text" class="input-field"
                                                    placeholder="<?php echo e(__('Enter Product Sku')); ?>" name="sku"
                                                    required=""
                                                    value="<?php echo e(Str::random(3) . substr(time(), 6, 8) . Str::random(3)); ?>">
                                            </div>
                                        </div>


                                        <div class="row">
                                            <div class="col-lg-12">
                                                <div class="left-area">
                                                    <h4 class="heading"><?php echo e(__('Category')); ?>*</h4>
                                                </div>
                                            </div>
                                            <div class="col-lg-12">
                                                <select id="cat" name="category_id" required="">
                                                    <option value=""><?php echo e(__('Select Category')); ?></option>
                                                    <?php $__currentLoopData = $cats; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $cat): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                        <option data-href="<?php echo e(route('admin-subcat-load', $cat->id)); ?>"
                                                            value="<?php echo e($cat->id); ?>"><?php echo e($cat->name); ?></option>
                                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-lg-12">
                                                <div class="left-area">
                                                    <h4 class="heading"><?php echo e(__('Sub Category')); ?>*</h4>
                                                </div>
                                            </div>
                                            <div class="col-lg-12">
                                                <select id="subcat" name="subcategory_id" disabled="">
                                                    <option value=""><?php echo e(__('Select Sub Category')); ?></option>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-lg-12">
                                                <div class="left-area">
                                                    <h4 class="heading"><?php echo e(__('Child Category')); ?>*</h4>
                                                </div>
                                            </div>
                                            <div class="col-lg-12">
                                                <select id="childcat" name="childcategory_id" disabled="">
                                                    <option value=""><?php echo e(__('Select Child Category')); ?></option>
                                                </select>
                                            </div>
                                        </div>


                                        <div id="catAttributes"></div>
                                        <div id="subcatAttributes"></div>
                                        <div id="childcatAttributes"></div>



                                        <div class="row">
                                            <div class="col-lg-12">
                                                <div class="left-area">

                                                </div>
                                            </div>
                                            <div class="col-lg-12">
                                                <ul class="list">
                                                    <li>
                                                        <input class="checkclick1" name="product_condition_check"
                                                            type="checkbox" id="product_condition_check" value="1">
                                                        <label
                                                            for="product_condition_check"><?php echo e(__('Allow Product Condition')); ?></label>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>

                                        <div class="showbox">
                                            <div class="row">
                                                <div class="col-lg-12">
                                                    <div class="left-area">
                                                        <h4 class="heading"><?php echo e(__('Product Condition')); ?>*</h4>
                                                    </div>
                                                </div>
                                                <div class="col-lg-12">
                                                    <select name="product_condition">
                                                        <option value="2"><?php echo e(__('New')); ?></option>
                                                        <option value="1"><?php echo e(__('Used')); ?></option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>


                                        <div class="row">
                                            <div class="col-lg-12">
                                                <div class="left-area">

                                                </div>
                                            </div>
                                            <div class="col-lg-12">
                                                <ul class="list">
                                                    <li>
                                                        <input class="checkclick1" name="preordered_check"
                                                            type="checkbox" id="preorderedCheck" value="1">
                                                        <label
                                                            for="preorderedCheck"><?php echo e(__('Allow Product Preorder')); ?></label>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>


                                        <div class="showbox">
                                            <div class="row">
                                                <div class="col-lg-12">
                                                    <div class="left-area">
                                                        <h4 class="heading"><?php echo e(__('Product Preorder')); ?>*</h4>
                                                    </div>
                                                </div>
                                                <div class="col-lg-12">
                                                    <select name="preordered">
                                                        <option value="1"><?php echo e(__('Sale')); ?></option>
                                                        <option value="2"><?php echo e(__('Preordered')); ?></option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>


                                        <div class="row">
                                            <div class="col-lg-12">
                                                <div class="left-area">

                                                </div>
                                            </div>
                                            <div class="col-lg-12">
                                                <ul class="list">
                                                    <li>
                                                        <input class="checkclick1" name="minimum_qty_check"
                                                            type="checkbox" id="check111" value="1">
                                                        <label for="check111"><?php echo e(__('Allow Minimum Order Qty')); ?></label>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>


                                        <div class="showbox">
                                            <div class="row">
                                                <div class="col-lg-12">
                                                    <div class="left-area">
                                                        <h4 class="heading"><?php echo e(__('Product Minimum Order Qty')); ?>* </h4>
                                                    </div>
                                                </div>
                                                <div class="col-lg-12">
                                                    <input type="number" class="input-field" min="1"
                                                        placeholder="<?php echo e(__('Minimum Order Qty')); ?>" name="minimum_qty">
                                                </div>
                                            </div>

                                        </div>


                                        <div class="row">
                                            <div class="col-lg-12">
                                                <div class="left-area">

                                                </div>
                                            </div>
                                            <div class="col-lg-12">
                                                <ul class="list">
                                                    <li>
                                                        <input class="checkclick1" name="shipping_time_check"
                                                            type="checkbox" id="check1" value="1">
                                                        <label
                                                            for="check1"><?php echo e(__('Allow Estimated Shipping Time')); ?></label>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>



                                        <div class="showbox">
                                            <div class="row">
                                                <div class="col-lg-12">
                                                    <div class="left-area">
                                                        <h4 class="heading"><?php echo e(__('Product Estimated Shipping Time')); ?>*
                                                        </h4>
                                                    </div>
                                                </div>
                                                <div class="col-lg-12">
                                                    <input type="text" class="input-field"
                                                        placeholder="<?php echo e(__('Estimated Shipping Time')); ?>" name="ship">
                                                </div>
                                            </div>
                                        </div>







                                        <div class="row">
                                            <div class="col-lg-12">
                                                <div class="left-area">

                                                </div>
                                            </div>
                                            <div class="col-lg-12">
                                                <ul class="list">
                                                    <li>
                                                        <input class="checkclick1" name="whole_check" type="checkbox"
                                                            id="whole_check" value="1">
                                                        <label
                                                            for="whole_check"><?php echo e(__('Allow Product Whole Sell')); ?></label>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>

                                        <div class="showbox">
                                            <div class="row">
                                                <div class="col-lg-12">
                                                    <div class="left-area">

                                                    </div>
                                                </div>
                                                <div class="col-lg-12">
                                                    <div class="featured-keyword-area">
                                                        <div class="feature-tag-top-filds" id="whole-section">
                                                            <div class="feature-area">
                                                                <span class="remove whole-remove"><i
                                                                        class="fas fa-times"></i></span>
                                                                <div class="row">
                                                                    <div class="col-lg-6">
                                                                        <input type="number" name="whole_sell_qty[]"
                                                                            class="input-field"
                                                                            placeholder="<?php echo e(__('Enter Quantity')); ?>"
                                                                            min="0">
                                                                    </div>

                                                                    <div class="col-lg-6">
                                                                        <input type="number" name="whole_sell_discount[]"
                                                                            class="input-field"
                                                                            placeholder="<?php echo e(__('Enter Discount Percentage')); ?>"
                                                                            min="0" />
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <a href="javascript:;" id="whole-btn" class="add-fild-btn"><i
                                                                class="icofont-plus"></i> <?php echo e(__('Add More Field')); ?></a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>


                                        <div class="row">
                                            <div class="col-lg-12">
                                                <div class="left-area">

                                                </div>
                                            </div>
                                            <div class="col-lg-12">
                                                <ul class="list">
                                                    <li>
                                                        <input class="checkclick1" name="measure_check" type="checkbox"
                                                            id="measure_check" value="1">
                                                        <label
                                                            for="measure_check"><?php echo e(__('Allow Product Measurement')); ?></label>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>


                                        <div class="showbox">
                                            <div class="row">
                                                <div class="col-lg-6">
                                                    <div class="left-area">
                                                        <h4 class="heading"><?php echo e(__('Product Measurement')); ?>*</h4>
                                                    </div>
                                                </div>
                                                <div class="col-lg-12">
                                                    <select id="product_measure">
                                                        <option value=""><?php echo e(__('None')); ?></option>
                                                        <option value="Gram"><?php echo e(__('Gram')); ?></option>
                                                        <option value="Kilogram"><?php echo e(__('Kilogram')); ?></option>
                                                        <option value="Litre"><?php echo e(__('Litre')); ?></option>
                                                        <option value="Pound"><?php echo e(__('Pound')); ?></option>
                                                        <option value="Custom"><?php echo e(__('Custom')); ?></option>
                                                    </select>
                                                </div>
                                                
                                                <div class="col-lg-12 hidden" id="measure">
                                                    <input name="measure" type="text" id="measurement"
                                                        class="input-field" placeholder="<?php echo e(__('Enter Unit')); ?>">
                                                </div>
                                            </div>
                                        </div>


                                        <div class="row">
                                            <div class="col-lg-12">
                                                <div class="left-area">

                                                </div>
                                            </div>
                                            <div class="col-lg-12">
                                                <ul class="list">
                                                    <li>
                                                        <input class="checkclickc" name="color_check" type="checkbox"
                                                            id="check3" value="1">
                                                        <label for="check3"><?php echo e(__('Allow Product Colors')); ?></label>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>


                                        <div class="showbox">

                                            <div class="row">
                                                <div class="col-lg-12">
                                                    <div class="left-area">
                                                        <h4 class="heading">
                                                            <?php echo e(__('Product Colors')); ?>*
                                                        </h4>
                                                        <p class="sub-heading">
                                                            <?php echo e(__('(Choose Your Favorite Colors)')); ?>

                                                        </p>
                                                    </div>
                                                </div>
                                                <div class="col-lg-12">
                                                    <div class="select-input-color" id="color-section">
                                                        <div class="size-area">
                                                            <span class="remove size-remove"><i
                                                                    class="fas fa-times"></i></span>
                                                            <div class="row">
                                                                <div class="col-md-12 col-sm-12">
                                                                    <label>
                                                                        <?php echo e(__('Color')); ?> :
                                                                    </label>
                                                                    <div class="color-area">
                                                                        <div class="input-group colorpicker-component cp">
                                                                            <input type="text" name="color_all[]"
                                                                                value=""
                                                                                class="input-field cp tcolor" />
                                                                            <span class="input-group-addon"><i></i></span>
                                                                        </div>
                                                                    </div>
                                                                </div>

                                                                


                                                            </div>
                                                        </div>
                                                    </div>
                                                    <a href="javascript:;" id="color-btn" class="add-more mt-4 mb-3"><i
                                                            class="fas fa-plus"></i><?php echo e(__('Add More Color')); ?> </a>
                                                </div>
                                            </div>

                                        </div>

                                        <div class="row">
                                            <div class="col-lg-12">
                                                <div class="left-area">

                                                </div>
                                            </div>
                                            <div class="col-lg-12">
                                                <ul class="list">
                                                    <li>
                                                        <input name="stock_check" class="stock-check" type="checkbox"
                                                            id="size-check" value="1">
                                                        <label for="size-check"
                                                            class="stock-text"><?php echo e(__('Manage Stock')); ?></label>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>


                                        <div class="showbox" id="size-display">
                                            <div class="row">
                                                <div class="col-lg-12">
                                                </div>
                                                <div class="col-lg-12">
                                                    <div class="product-size-details" id="size-section">
                                                        <div class="size-area">
                                                            <span class="remove size-remove"><i
                                                                    class="fas fa-times"></i></span>
                                                            <div class="row">
                                                                <div class="col-md-4 col-sm-4">
                                                                    <label>
                                                                        <?php echo e(__('Size Name')); ?> :
                                                                        <span>
                                                                            <?php echo e(__('(eg. S,M,L,XL,3XL,4XL)')); ?>

                                                                        </span>
                                                                    </label>
                                                                    <input type="text" name="size[]"
                                                                        class="input-field tsize"
                                                                        placeholder="<?php echo e(__('Enter Product Size')); ?>"
                                                                        value="" >
                                                                </div>
                                                                <div class="col-md-4 col-sm-4">
                                                                    <label>
                                                                        <?php echo e(__('Size Qty')); ?> :
                                                                        <span>
                                                                            <?php echo e(__('(Quantity of this size)')); ?>

                                                                        </span>
                                                                    </label>
                                                                    <input type="number" name="size_qty[]"
                                                                        class="input-field"
                                                                        placeholder="<?php echo e(__('Size Qty')); ?>" value="1"
                                                                        min="1">
                                                                </div>
                                                                <div class="col-md-4 col-sm-4">
                                                                    <label>
                                                                        <?php echo e(__('Size Price')); ?> :
                                                                        <span>
                                                                            <?php echo e(__('(Added with base price)')); ?>

                                                                        </span>
                                                                    </label>
                                                                    <input type="number" name="size_price[]"
                                                                        class="input-field"
                                                                        placeholder="<?php echo e(__('Size Price')); ?>"
                                                                        value="0" min="0">
                                                                </div>


                                                            </div>
                                                        </div>
                                                    </div>

                                                    <a href="javascript:;" id="size-btn" class="add-more"><i
                                                            class="fas fa-plus"></i><?php echo e(__('Add More')); ?> </a>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row" id="default_stock">
                                            <div class="col-lg-12">
                                                <div class="left-area">
                                                    <h4 class="heading"><?php echo e(__('Product Stock')); ?>*</h4>
                                                    <p class="sub-heading">
                                                        <?php echo e(__('(Leave Empty will Show Always Available)')); ?></p>
                                                </div>
                                            </div>
                                            <div class="col-lg-12">
                                                <input name="stock" type="number" class="input-field"
                                                    placeholder="e.g 20" value="" min="0">
                                            </div>
                                        </div>




                                        <div class="row">
                                            <div class="col-lg-12">
                                                <div class="left-area">
                                                    <h4 class="heading">
                                                        <?php echo e(__('Product Description')); ?>*
                                                    </h4>
                                                </div>
                                            </div>
                                            <div class="col-lg-12">
                                                <div class="text-editor">
                                                    <textarea class="nic-edit" name="details"></textarea>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-lg-12">
                                                <div class="left-area">
                                                    <h4 class="heading">
                                                        <?php echo e(__('Product Buy/Return Policy')); ?>*
                                                    </h4>
                                                </div>
                                            </div>
                                            <div class="col-lg-12">
                                                <div class="text-editor">
                                                    <textarea class="nic-edit" name="policy"></textarea>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-lg-12">
                                                <div class="checkbox-wrapper">
                                                    <input type="checkbox" name="seo_check" value="1"
                                                        class="checkclick" id="allowProductSEO" value="1">
                                                    <label for="allowProductSEO"><?php echo e(__('Allow Product SEO')); ?></label>
                                                </div>
                                            </div>
                                        </div>



                                        <div class="showbox">
                                            <div class="row">
                                                <div class="col-lg-12">
                                                    <div class="left-area">
                                                        <h4 class="heading"><?php echo e(__('Meta Tags')); ?> *</h4>
                                                    </div>
                                                </div>
                                                <div class="col-lg-12">
                                                    <ul id="metatags" class="myTags">
                                                    </ul>
                                                </div>
                                            </div>

                                            <div class="row">
                                                <div class="col-lg-12">
                                                    <div class="left-area">
                                                        <h4 class="heading">
                                                            <?php echo e(__('Meta Description')); ?> *
                                                        </h4>
                                                    </div>
                                                </div>
                                                <div class="col-lg-12">
                                                    <div class="text-editor">
                                                        <textarea name="meta_description" class="input-field" placeholder="<?php echo e(__('Meta Description')); ?>"></textarea>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <input type="hidden" name="type" value="Physical">

                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="add-product-content">
                        <div class="row">
                            <div class="col-lg-12">
                                <div class="product-description">
                                    <div class="body-area">
                                        <div class="row">
                                            <div class="col-lg-12">
                                                <div class="left-area">
                                                    <h4 class="heading"><?php echo e(__('Feature Image')); ?> *</h4>
                                                </div>
                                            </div>
                                            <div class="col-lg-12">
                                                <div class="panel panel-body">
                                                    <div class="span4 cropme text-center" id="landscape"
                                                        style="width: 100%; height: 285px; border: 1px dashed #ddd; background: #f1f1f1;">
                                                        <a href="javascript:;" id="crop-image" class=" mybtn1"
                                                            style="">
                                                            <i class="icofont-upload-alt"></i>
                                                            <?php echo e(__('Upload Image Here')); ?>

                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <input type="hidden" id="feature_photo" name="photo" value="">
                                        <input type="file" name="gallery[]" class="hidden" id="uploadgallery"
                                            accept="image/*" multiple>
                                        <div class="row mb-4">
                                            <div class="col-lg-12 mb-2">
                                                <div class="left-area">
                                                    <h4 class="heading">
                                                        <?php echo e(__('Product Gallery Images')); ?> *
                                                    </h4>
                                                </div>
                                            </div>
                                            <div class="col-lg-12">
                                                <a href="#" class="set-gallery" data-bs-toggle="modal"
                                                    data-bs-target="#setgallery">
                                                    <i class="icofont-plus"></i> <?php echo e(__('Set Gallery')); ?>

                                                </a>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-lg-12">
                                                <div class="left-area">
                                                    <h4 class="heading">
                                                        <?php echo e(__('Product Current Price')); ?>*
                                                    </h4>
                                                    <p class="sub-heading">
                                                        (<?php echo e(__('In')); ?> <?php echo e($sign->name); ?>)
                                                    </p>
                                                </div>
                                            </div>
                                            <div class="col-lg-12">
                                                <input name="price" type="number" class="input-field"
                                                    placeholder="<?php echo e(__('e.g 20')); ?>" step="0.1" required=""
                                                    min="0">
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-lg-12">
                                                <div class="left-area">
                                                    <h4 class="heading"><?php echo e(__('Product Discount Price')); ?>*</h4>
                                                    <p class="sub-heading"><?php echo e(__('(Optional)')); ?></p>
                                                </div>
                                            </div>
                                            <div class="col-lg-12">
                                                <input name="previous_price" step="0.1" type="number"
                                                    class="input-field" placeholder="<?php echo e(__('e.g 20')); ?>"
                                                    min="0">
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-lg-12">
                                                <div class="left-area">
                                                    <h4 class="heading"><?php echo e(__('Youtube Video URL')); ?>*</h4>
                                                    <p class="sub-heading"><?php echo e(__('(Optional)')); ?></p>
                                                </div>
                                            </div>
                                            <div class="col-lg-12">
                                                <input name="youtube" type="text" class="input-field"
                                                    placeholder="<?php echo e(__('Enter Youtube Video URL')); ?>">
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-lg-12">
                                                <div class="left-area">

                                                </div>
                                            </div>
                                            <div class="col-lg-12">
                                                <div class="featured-keyword-area">
                                                    <div class="heading-area">
                                                        <h4 class="title"><?php echo e(__('Feature Tags')); ?></h4>
                                                    </div>

                                                    <div class="feature-tag-top-filds" id="feature-section">
                                                        <div class="feature-area">
                                                            <span class="remove feature-remove"><i
                                                                    class="fas fa-times"></i></span>
                                                            <div class="row">
                                                                <div class="col-lg-6">
                                                                    <input type="text" name="features[]"
                                                                        class="input-field"
                                                                        placeholder="<?php echo e(__('Enter Your Keyword')); ?>">
                                                                </div>

                                                                <div class="col-lg-6">
                                                                    <div class="input-group colorpicker-component cp">
                                                                        <input type="text" name="colors[]"
                                                                            value="#000000" class="input-field cp" />
                                                                        <span class="input-group-addon"><i></i></span>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <a href="javascript:;" id="feature-btn" class="add-fild-btn"><i
                                                            class="icofont-plus"></i> <?php echo e(__('Add More Field')); ?></a>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-lg-12">
                                                <div class="left-area">
                                                    <h4 class="heading"><?php echo e(__('Tags')); ?> *</h4>
                                                </div>
                                            </div>
                                            <div class="col-lg-12">
                                                <ul id="tags" class="myTags">
                                                </ul>
                                            </div>
                                        </div>

                                        <div class="row text-center">
                                            <div class="col-6 offset-3">
                                                <button class="addProductSubmit-btn"
                                                    type="submit"><?php echo e(__('Create Product')); ?></button>
                                            </div>
                                        </div>

                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <div class="modal fade" id="setgallery" tabindex="-1" role="dialog" aria-labelledby="setgallery"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered  modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalCenterTitle"><?php echo e(__('Image Gallery')); ?></h5>
                    <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="top-area">
                        <div class="row">
                            <div class="col-sm-6 text-right">
                                <div class="upload-img-btn">
                                    <label for="image-upload" id="prod_gallery"><i
                                            class="icofont-upload-alt"></i><?php echo e(__('Upload File')); ?></label>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <a href="javascript:;" class="upload-done" data-bs-dismiss="modal"> <i
                                        class="fas fa-check"></i> <?php echo e(__('Done')); ?></a>
                            </div>
                            <div class="col-sm-12 text-center">(
                                <small><?php echo e(__('You can upload multiple Images.')); ?></small> )
                            </div>
                        </div>
                    </div>
                    <div class="gallery-images">
                        <div class="selected-image">
                            <div class="row">


                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('scripts'); ?>
    <script src="<?php echo e(asset('assets/admin/js/jquery.Jcrop.js')); ?>"></script>
    <script src="<?php echo e(asset('assets/admin/js/jquery.SimpleCropper.js')); ?>"></script>
    <script src="<?php echo e(asset('assets/admin/js/select2.js')); ?>"></script>

    <script type="text/javascript">
        (function($) {
            "use strict";


            $(document).ready(function() {
                $('.select2').select2({
                    placeholder: "Select Products",
                    maximumSelectionLength: 4,
                });
            });

            // Gallery Section Insert

            $(document).on('click', '.remove-img', function() {
                var id = $(this).find('input[type=hidden]').val();
                $('#galval' + id).remove();
                $(this).parent().parent().remove();
            });

            $(document).on('click', '#prod_gallery', function() {
                $('#uploadgallery').click();
                $('.selected-image .row').html('');
                $('#muaadhform').find('.removegal').val(0);
            });


            $("#uploadgallery").change(function() {
                var total_file = document.getElementById("uploadgallery").files.length;
                for (var i = 0; i < total_file; i++) {
                    $('.selected-image .row').append('<div class="col-sm-6">' +
                        '<div class="img gallery-img">' +
                        '<span class="remove-img"><i class="fas fa-times"></i>' +
                        '<input type="hidden" value="' + i + '">' +
                        '</span>' +
                        '<a href="' + URL.createObjectURL(event.target.files[i]) + '" target="_blank">' +
                        '<img src="' + URL.createObjectURL(event.target.files[i]) +
                        '" alt="gallery image">' +
                        '</a>' +
                        '</div>' +
                        '</div> '
                    );
                    $('#muaadhform').append('<input type="hidden" name="galval[]" id="galval' + i +
                        '" class="removegal" value="' + i + '">')
                }

            });

            // Gallery Section Insert Ends

        })(jQuery);
    </script>

    <script type="text/javascript">
        (function($) {
            "use strict";

            $(document).ready(function() {
                $('.cp').colorpicker();
                $('.cropme').simpleCropper();
            });

        })(jQuery);


        $(document).on('click', '#size-check', function() {
            if ($(this).is(':checked')) {
                $('#default_stock').addClass('d-none')
            } else {
                $('#default_stock').removeClass('d-none');
            }
        })
    </script>


    <?php echo $__env->make('partials.admin.product.product-scripts', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\Users\hp\Herd\new\resources\views/admin/product/create/physical.blade.php ENDPATH**/ ?>