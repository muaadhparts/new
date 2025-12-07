<?php $__env->startSection('content'); ?>
<div class="content-area">
    <div class="mr-breadcrumb">
        <div class="row">
            <div class="col-lg-12">
                <h4 class="heading"><?php echo e(__('Theme Colors')); ?></h4>
                <ul class="links">
                    <li><a href="<?php echo e(route('admin.dashboard')); ?>"><?php echo e(__('Dashboard')); ?></a></li>
                    <li><a href="javascript:;"><?php echo e(__('General Settings')); ?></a></li>
                    <li><a href="<?php echo e(route('admin-theme-colors')); ?>"><?php echo e(__('Theme Colors')); ?></a></li>
                </ul>
            </div>
        </div>
    </div>

    <div class="add-product-content1 add-product-content2">
        <div class="row">
            <div class="col-lg-12">
                <div class="product-description">
                    <div class="body-area">
                        <div class="gocover" style="background: url(<?php echo e(asset('assets/images/' . $gs->admin_loader)); ?>) no-repeat scroll center center rgba(45, 45, 45, 0.5);"></div>

                        <form action="<?php echo e(route('admin-theme-colors-update')); ?>" id="themeColorForm" method="POST">
                            <?php echo csrf_field(); ?>
                            <?php echo $__env->make('alerts.admin.form-both', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

                            
                            <div class="row justify-content-center mb-4">
                                <div class="col-lg-10">
                                    <div class="card" id="previewCard">
                                        <div class="card-header preview-header">
                                            <i class="fas fa-eye"></i> <?php echo e(__('Live Preview')); ?>

                                        </div>
                                        <div class="card-body preview-body">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <h6 class="preview-title"><?php echo e(__('Buttons')); ?></h6>
                                                    <div class="d-flex flex-wrap gap-2 mb-3">
                                                        <button type="button" class="btn preview-btn-primary"><?php echo e(__('Primary')); ?></button>
                                                        <button type="button" class="btn preview-btn-secondary"><?php echo e(__('Secondary')); ?></button>
                                                        <button type="button" class="btn preview-btn-success"><?php echo e(__('Success')); ?></button>
                                                        <button type="button" class="btn preview-btn-danger"><?php echo e(__('Danger')); ?></button>
                                                    </div>
                                                    <h6 class="preview-title"><?php echo e(__('Text & Links')); ?></h6>
                                                    <p class="preview-text-primary"><?php echo e(__('Primary Text Color')); ?></p>
                                                    <p class="preview-text-muted"><?php echo e(__('Muted Text Color')); ?></p>
                                                    <a href="javascript:;" class="preview-link"><?php echo e(__('Link Color')); ?></a>
                                                </div>
                                                <div class="col-md-6">
                                                    <h6 class="preview-title"><?php echo e(__('Badges')); ?></h6>
                                                    <div class="d-flex flex-wrap gap-2 mb-3">
                                                        <span class="badge preview-badge-primary"><?php echo e(__('Primary')); ?></span>
                                                        <span class="badge preview-badge-success"><?php echo e(__('Success')); ?></span>
                                                        <span class="badge preview-badge-warning"><?php echo e(__('Warning')); ?></span>
                                                        <span class="badge preview-badge-danger"><?php echo e(__('Danger')); ?></span>
                                                    </div>
                                                    <h6 class="preview-title"><?php echo e(__('Input')); ?></h6>
                                                    <input type="text" class="form-control preview-input" placeholder="<?php echo e(__('Focus to see border color')); ?>">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="card-footer preview-footer">
                                            <small class="preview-footer-text"><?php echo e(__('Footer Preview')); ?></small>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            
                            <div class="row justify-content-center mb-4">
                                <div class="col-lg-10">
                                    <h5 class="section-title"><i class="fas fa-magic"></i> <?php echo e(__('Quick Presets')); ?></h5>
                                    <div class="d-flex flex-wrap gap-2">
                                        <button type="button" class="btn btn-sm preset-btn" data-preset="nissan" style="background: #c3002f; color: white;">Nissan Red</button>
                                        <button type="button" class="btn btn-sm preset-btn" data-preset="blue" style="background: #2563eb; color: white;">Blue</button>
                                        <button type="button" class="btn btn-sm preset-btn" data-preset="green" style="background: #16a34a; color: white;">Green</button>
                                        <button type="button" class="btn btn-sm preset-btn" data-preset="purple" style="background: #9333ea; color: white;">Purple</button>
                                        <button type="button" class="btn btn-sm preset-btn" data-preset="orange" style="background: #ea580c; color: white;">Orange</button>
                                        <button type="button" class="btn btn-sm preset-btn" data-preset="teal" style="background: #0d9488; color: white;">Teal</button>
                                    </div>
                                </div>
                            </div>

                            <hr class="my-4">

                            
                            <div class="row justify-content-center">
                                <div class="col-lg-10">
                                    <h5 class="section-title"><i class="fas fa-palette"></i> <?php echo e(__('Primary Colors')); ?></h5>
                                    <p class="section-desc"><?php echo e(__('Main brand color used for buttons, links, and accents')); ?></p>
                                </div>
                            </div>

                            <div class="row justify-content-center">
                                <div class="col-lg-10">
                                    <div class="row">
                                        <div class="col-md-3">
                                            <div class="color-input-group">
                                                <label><?php echo e(__('Primary')); ?></label>
                                                <div class="input-group colorpicker-component cp">
                                                    <input type="text" class="form-control color-field" name="theme_primary" id="theme_primary" value="<?php echo e($gs->theme_primary ?? '#c3002f'); ?>">
                                                    <span class="input-group-addon"><i></i></span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="color-input-group">
                                                <label><?php echo e(__('Hover')); ?></label>
                                                <div class="input-group colorpicker-component cp">
                                                    <input type="text" class="form-control color-field" name="theme_primary_hover" id="theme_primary_hover" value="<?php echo e($gs->theme_primary_hover ?? '#a00025'); ?>">
                                                    <span class="input-group-addon"><i></i></span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="color-input-group">
                                                <label><?php echo e(__('Dark')); ?></label>
                                                <div class="input-group colorpicker-component cp">
                                                    <input type="text" class="form-control color-field" name="theme_primary_dark" id="theme_primary_dark" value="<?php echo e($gs->theme_primary_dark ?? '#8a0020'); ?>">
                                                    <span class="input-group-addon"><i></i></span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="color-input-group">
                                                <label><?php echo e(__('Light')); ?></label>
                                                <div class="input-group colorpicker-component cp">
                                                    <input type="text" class="form-control color-field" name="theme_primary_light" id="theme_primary_light" value="<?php echo e($gs->theme_primary_light ?? '#fef2f4'); ?>">
                                                    <span class="input-group-addon"><i></i></span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <hr class="my-4">

                            
                            <div class="row justify-content-center">
                                <div class="col-lg-10">
                                    <h5 class="section-title"><i class="fas fa-palette"></i> <?php echo e(__('Secondary Colors')); ?></h5>
                                    <p class="section-desc"><?php echo e(__('Used for dark sections, secondary buttons, and text')); ?></p>
                                </div>
                            </div>

                            <div class="row justify-content-center">
                                <div class="col-lg-10">
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="color-input-group">
                                                <label><?php echo e(__('Secondary')); ?></label>
                                                <div class="input-group colorpicker-component cp">
                                                    <input type="text" class="form-control color-field" name="theme_secondary" id="theme_secondary" value="<?php echo e($gs->theme_secondary ?? '#1f0300'); ?>">
                                                    <span class="input-group-addon"><i></i></span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="color-input-group">
                                                <label><?php echo e(__('Hover')); ?></label>
                                                <div class="input-group colorpicker-component cp">
                                                    <input type="text" class="form-control color-field" name="theme_secondary_hover" id="theme_secondary_hover" value="<?php echo e($gs->theme_secondary_hover ?? '#351c1a'); ?>">
                                                    <span class="input-group-addon"><i></i></span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="color-input-group">
                                                <label><?php echo e(__('Light')); ?></label>
                                                <div class="input-group colorpicker-component cp">
                                                    <input type="text" class="form-control color-field" name="theme_secondary_light" id="theme_secondary_light" value="<?php echo e($gs->theme_secondary_light ?? '#4c3533'); ?>">
                                                    <span class="input-group-addon"><i></i></span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <hr class="my-4">

                            
                            <div class="row justify-content-center">
                                <div class="col-lg-10">
                                    <h5 class="section-title"><i class="fas fa-font"></i> <?php echo e(__('Text Colors')); ?></h5>
                                    <p class="section-desc"><?php echo e(__('Colors for headings, paragraphs, and labels')); ?></p>
                                </div>
                            </div>

                            <div class="row justify-content-center">
                                <div class="col-lg-10">
                                    <div class="row">
                                        <div class="col-md-3">
                                            <div class="color-input-group">
                                                <label><?php echo e(__('Primary Text')); ?></label>
                                                <div class="input-group colorpicker-component cp">
                                                    <input type="text" class="form-control color-field" name="theme_text_primary" id="theme_text_primary" value="<?php echo e($gs->theme_text_primary ?? '#1f0300'); ?>">
                                                    <span class="input-group-addon"><i></i></span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="color-input-group">
                                                <label><?php echo e(__('Secondary Text')); ?></label>
                                                <div class="input-group colorpicker-component cp">
                                                    <input type="text" class="form-control color-field" name="theme_text_secondary" id="theme_text_secondary" value="<?php echo e($gs->theme_text_secondary ?? '#4c3533'); ?>">
                                                    <span class="input-group-addon"><i></i></span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="color-input-group">
                                                <label><?php echo e(__('Muted Text')); ?></label>
                                                <div class="input-group colorpicker-component cp">
                                                    <input type="text" class="form-control color-field" name="theme_text_muted" id="theme_text_muted" value="<?php echo e($gs->theme_text_muted ?? '#796866'); ?>">
                                                    <span class="input-group-addon"><i></i></span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="color-input-group">
                                                <label><?php echo e(__('Light Text')); ?></label>
                                                <div class="input-group colorpicker-component cp">
                                                    <input type="text" class="form-control color-field" name="theme_text_light" id="theme_text_light" value="<?php echo e($gs->theme_text_light ?? '#9a8e8c'); ?>">
                                                    <span class="input-group-addon"><i></i></span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <hr class="my-4">

                            
                            <div class="row justify-content-center">
                                <div class="col-lg-10">
                                    <h5 class="section-title"><i class="fas fa-fill-drip"></i> <?php echo e(__('Background Colors')); ?></h5>
                                    <p class="section-desc"><?php echo e(__('Colors for page backgrounds, cards, and sections')); ?></p>
                                </div>
                            </div>

                            <div class="row justify-content-center">
                                <div class="col-lg-10">
                                    <div class="row">
                                        <div class="col-md-3">
                                            <div class="color-input-group">
                                                <label><?php echo e(__('Body')); ?></label>
                                                <div class="input-group colorpicker-component cp">
                                                    <input type="text" class="form-control color-field" name="theme_bg_body" id="theme_bg_body" value="<?php echo e($gs->theme_bg_body ?? '#ffffff'); ?>">
                                                    <span class="input-group-addon"><i></i></span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="color-input-group">
                                                <label><?php echo e(__('Light')); ?></label>
                                                <div class="input-group colorpicker-component cp">
                                                    <input type="text" class="form-control color-field" name="theme_bg_light" id="theme_bg_light" value="<?php echo e($gs->theme_bg_light ?? '#f8f7f7'); ?>">
                                                    <span class="input-group-addon"><i></i></span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="color-input-group">
                                                <label><?php echo e(__('Gray')); ?></label>
                                                <div class="input-group colorpicker-component cp">
                                                    <input type="text" class="form-control color-field" name="theme_bg_gray" id="theme_bg_gray" value="<?php echo e($gs->theme_bg_gray ?? '#e9e6e6'); ?>">
                                                    <span class="input-group-addon"><i></i></span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="color-input-group">
                                                <label><?php echo e(__('Dark')); ?></label>
                                                <div class="input-group colorpicker-component cp">
                                                    <input type="text" class="form-control color-field" name="theme_bg_dark" id="theme_bg_dark" value="<?php echo e($gs->theme_bg_dark ?? '#030712'); ?>">
                                                    <span class="input-group-addon"><i></i></span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <hr class="my-4">

                            
                            <div class="row justify-content-center">
                                <div class="col-lg-10">
                                    <h5 class="section-title"><i class="fas fa-check-circle"></i> <?php echo e(__('Status Colors')); ?></h5>
                                    <p class="section-desc"><?php echo e(__('Colors for success, warning, danger, and info states')); ?></p>
                                </div>
                            </div>

                            <div class="row justify-content-center">
                                <div class="col-lg-10">
                                    <div class="row">
                                        <div class="col-md-3">
                                            <div class="color-input-group">
                                                <label><i class="fas fa-check text-success"></i> <?php echo e(__('Success')); ?></label>
                                                <div class="input-group colorpicker-component cp">
                                                    <input type="text" class="form-control color-field" name="theme_success" id="theme_success" value="<?php echo e($gs->theme_success ?? '#27be69'); ?>">
                                                    <span class="input-group-addon"><i></i></span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="color-input-group">
                                                <label><i class="fas fa-exclamation-triangle text-warning"></i> <?php echo e(__('Warning')); ?></label>
                                                <div class="input-group colorpicker-component cp">
                                                    <input type="text" class="form-control color-field" name="theme_warning" id="theme_warning" value="<?php echo e($gs->theme_warning ?? '#fac03c'); ?>">
                                                    <span class="input-group-addon"><i></i></span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="color-input-group">
                                                <label><i class="fas fa-times-circle text-danger"></i> <?php echo e(__('Danger')); ?></label>
                                                <div class="input-group colorpicker-component cp">
                                                    <input type="text" class="form-control color-field" name="theme_danger" id="theme_danger" value="<?php echo e($gs->theme_danger ?? '#f2415a'); ?>">
                                                    <span class="input-group-addon"><i></i></span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="color-input-group">
                                                <label><i class="fas fa-info-circle text-info"></i> <?php echo e(__('Info')); ?></label>
                                                <div class="input-group colorpicker-component cp">
                                                    <input type="text" class="form-control color-field" name="theme_info" id="theme_info" value="<?php echo e($gs->theme_info ?? '#0ea5e9'); ?>">
                                                    <span class="input-group-addon"><i></i></span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <hr class="my-4">

                            
                            <div class="row justify-content-center">
                                <div class="col-lg-10">
                                    <h5 class="section-title"><i class="fas fa-border-style"></i> <?php echo e(__('Border Colors')); ?></h5>
                                    <p class="section-desc"><?php echo e(__('Colors for borders and dividers')); ?></p>
                                </div>
                            </div>

                            <div class="row justify-content-center">
                                <div class="col-lg-10">
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="color-input-group">
                                                <label><?php echo e(__('Default Border')); ?></label>
                                                <div class="input-group colorpicker-component cp">
                                                    <input type="text" class="form-control color-field" name="theme_border" id="theme_border" value="<?php echo e($gs->theme_border ?? '#d9d4d4'); ?>">
                                                    <span class="input-group-addon"><i></i></span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="color-input-group">
                                                <label><?php echo e(__('Light Border')); ?></label>
                                                <div class="input-group colorpicker-component cp">
                                                    <input type="text" class="form-control color-field" name="theme_border_light" id="theme_border_light" value="<?php echo e($gs->theme_border_light ?? '#e9e6e6'); ?>">
                                                    <span class="input-group-addon"><i></i></span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="color-input-group">
                                                <label><?php echo e(__('Dark Border')); ?></label>
                                                <div class="input-group colorpicker-component cp">
                                                    <input type="text" class="form-control color-field" name="theme_border_dark" id="theme_border_dark" value="<?php echo e($gs->theme_border_dark ?? '#c7c0bf'); ?>">
                                                    <span class="input-group-addon"><i></i></span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <hr class="my-4">

                            
                            <div class="row justify-content-center">
                                <div class="col-lg-10">
                                    <h5 class="section-title"><i class="fas fa-columns"></i> <?php echo e(__('Header & Footer')); ?></h5>
                                    <p class="section-desc"><?php echo e(__('Colors for header and footer sections')); ?></p>
                                </div>
                            </div>

                            <div class="row justify-content-center">
                                <div class="col-lg-10">
                                    <div class="row">
                                        <div class="col-md-3">
                                            <div class="color-input-group">
                                                <label><?php echo e(__('Header BG')); ?></label>
                                                <div class="input-group colorpicker-component cp">
                                                    <input type="text" class="form-control color-field" name="theme_header_bg" id="theme_header_bg" value="<?php echo e($gs->theme_header_bg ?? '#ffffff'); ?>">
                                                    <span class="input-group-addon"><i></i></span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="color-input-group">
                                                <label><?php echo e(__('Footer BG')); ?></label>
                                                <div class="input-group colorpicker-component cp">
                                                    <input type="text" class="form-control color-field" name="theme_footer_bg" id="theme_footer_bg" value="<?php echo e($gs->theme_footer_bg ?? '#030712'); ?>">
                                                    <span class="input-group-addon"><i></i></span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="color-input-group">
                                                <label><?php echo e(__('Footer Text')); ?></label>
                                                <div class="input-group colorpicker-component cp">
                                                    <input type="text" class="form-control color-field" name="theme_footer_text" id="theme_footer_text" value="<?php echo e($gs->theme_footer_text ?? '#ffffff'); ?>">
                                                    <span class="input-group-addon"><i></i></span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="color-input-group">
                                                <label><?php echo e(__('Footer Link Hover')); ?></label>
                                                <div class="input-group colorpicker-component cp">
                                                    <input type="text" class="form-control color-field" name="theme_footer_link_hover" id="theme_footer_link_hover" value="<?php echo e($gs->theme_footer_link_hover ?? '#c3002f'); ?>">
                                                    <span class="input-group-addon"><i></i></span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <hr class="my-4">

                            
                            <div class="row justify-content-center">
                                <div class="col-lg-10 text-center">
                                    <button class="addProductSubmit-btn" type="submit">
                                        <i class="fas fa-save"></i> <?php echo e(__('Save All Colors')); ?>

                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Section Titles */
.section-title {
    color: #333;
    font-weight: 600;
    margin-bottom: 5px;
}
.section-desc {
    color: #666;
    font-size: 13px;
    margin-bottom: 15px;
}

/* Color Input Groups */
.color-input-group {
    margin-bottom: 20px;
}
.color-input-group label {
    display: block;
    font-size: 13px;
    font-weight: 500;
    margin-bottom: 5px;
    color: #555;
}
.color-input-group .input-group {
    border-radius: 6px;
    overflow: hidden;
}
.color-input-group .form-control {
    height: 40px;
    border: 1px solid #ddd;
}
/* Colorpicker addon styles */
.color-input-group .input-group {
    position: relative;
}
.color-input-group .input-group .input-group-addon {
    position: absolute;
    right: 0;
    top: 0;
    background: #f5f5f5;
    border: 1px solid #ddd;
    border-left: none;
    width: 40px;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    border-radius: 0 6px 6px 0;
}
.color-input-group .input-group .input-group-addon i {
    width: 22px;
    height: 22px;
    display: block;
    border-radius: 3px;
    border: 1px solid #ccc;
}
.color-input-group .form-control.color-field {
    padding-right: 50px;
}

/* Preview Card */
#previewCard {
    border: none;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    border-radius: 10px;
    overflow: hidden;
}
.preview-header {
    background: var(--pv-bg-dark, #030712);
    color: var(--pv-text-white, #fff);
    padding: 15px 20px;
}
.preview-body {
    background: var(--pv-bg-body, #fff);
    padding: 20px;
}
.preview-footer {
    background: var(--pv-bg-dark, #030712);
    padding: 10px 20px;
}
.preview-footer-text {
    color: var(--pv-footer-text, #fff);
}
.preview-title {
    color: var(--pv-text-primary, #1f0300);
    font-weight: 600;
    margin-bottom: 10px;
}
.preview-text-primary {
    color: var(--pv-text-primary, #1f0300);
    margin-bottom: 5px;
}
.preview-text-muted {
    color: var(--pv-text-muted, #796866);
    margin-bottom: 5px;
}

/* Preview Buttons */
.preview-btn-primary {
    background: var(--pv-primary, #c3002f);
    border: none;
    color: #fff;
    padding: 8px 16px;
    border-radius: 5px;
}
.preview-btn-primary:hover {
    background: var(--pv-primary-hover, #a00025);
    color: #fff;
}
.preview-btn-secondary {
    background: var(--pv-secondary, #1f0300);
    border: none;
    color: #fff;
    padding: 8px 16px;
    border-radius: 5px;
}
.preview-btn-success {
    background: var(--pv-success, #27be69);
    border: none;
    color: #fff;
    padding: 8px 16px;
    border-radius: 5px;
}
.preview-btn-danger {
    background: var(--pv-danger, #f2415a);
    border: none;
    color: #fff;
    padding: 8px 16px;
    border-radius: 5px;
}

/* Preview Badges */
.preview-badge-primary {
    background: var(--pv-primary, #c3002f);
    color: #fff;
    padding: 5px 10px;
    border-radius: 20px;
}
.preview-badge-success {
    background: var(--pv-success, #27be69);
    color: #fff;
    padding: 5px 10px;
    border-radius: 20px;
}
.preview-badge-warning {
    background: var(--pv-warning, #fac03c);
    color: #333;
    padding: 5px 10px;
    border-radius: 20px;
}
.preview-badge-danger {
    background: var(--pv-danger, #f2415a);
    color: #fff;
    padding: 5px 10px;
    border-radius: 20px;
}

/* Preview Link */
.preview-link {
    color: var(--pv-primary, #c3002f);
    text-decoration: none;
}
.preview-link:hover {
    color: var(--pv-primary-hover, #a00025);
}

/* Preview Input */
.preview-input {
    border: 1px solid var(--pv-border, #d9d4d4);
}
.preview-input:focus {
    border-color: var(--pv-primary, #c3002f);
    box-shadow: 0 0 0 3px var(--pv-primary-light, rgba(195,0,47,0.1));
}

/* Preset Buttons */
.preset-btn {
    border: none;
    padding: 8px 16px;
    border-radius: 5px;
    cursor: pointer;
    transition: transform 0.2s;
}
.preset-btn:hover {
    transform: scale(1.05);
}

/* Gaps */
.gap-2 { gap: 0.5rem; }
.gap-3 { gap: 1rem; }
</style>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('scripts'); ?>
<script>
$(document).ready(function() {
    // Initialize colorpickers with format hex
    $('.cp').colorpicker({
        format: 'hex'
    });

    // Update addon icon color on init and change
    function updateAddonColors() {
        $('.cp').each(function() {
            var $input = $(this).find('input');
            var $addon = $(this).find('.input-group-addon i');
            var color = $input.val();
            if (color) {
                $addon.css('background-color', color);
            }
        });
    }

    // Initial update
    updateAddonColors();

    // Update on colorpicker change
    $('.cp').on('changeColor', function(e) {
        var $input = $(this).find('input');
        var $addon = $(this).find('.input-group-addon i');
        if (e.color) {
            $addon.css('background-color', e.color.toHex());
        }
    });

    // Presets data
    var presets = {
        nissan: {
            primary: '#c3002f', primary_hover: '#a00025', primary_dark: '#8a0020', primary_light: '#fef2f4',
            secondary: '#1f0300', secondary_hover: '#351c1a', secondary_light: '#4c3533',
            text_primary: '#1f0300', text_secondary: '#4c3533', text_muted: '#796866', text_light: '#9a8e8c',
            bg_body: '#ffffff', bg_light: '#f8f7f7', bg_gray: '#e9e6e6', bg_dark: '#030712',
            success: '#27be69', warning: '#fac03c', danger: '#f2415a', info: '#0ea5e9',
            border: '#d9d4d4', border_light: '#e9e6e6', border_dark: '#c7c0bf',
            header_bg: '#ffffff', footer_bg: '#030712', footer_text: '#ffffff', footer_link_hover: '#c3002f'
        },
        blue: {
            primary: '#2563eb', primary_hover: '#1d4ed8', primary_dark: '#1e40af', primary_light: '#eff6ff',
            secondary: '#1e293b', secondary_hover: '#334155', secondary_light: '#475569',
            text_primary: '#1e293b', text_secondary: '#475569', text_muted: '#64748b', text_light: '#94a3b8',
            bg_body: '#ffffff', bg_light: '#f8fafc', bg_gray: '#e2e8f0', bg_dark: '#0f172a',
            success: '#22c55e', warning: '#f59e0b', danger: '#ef4444', info: '#06b6d4',
            border: '#e2e8f0', border_light: '#f1f5f9', border_dark: '#cbd5e1',
            header_bg: '#ffffff', footer_bg: '#0f172a', footer_text: '#ffffff', footer_link_hover: '#2563eb'
        },
        green: {
            primary: '#16a34a', primary_hover: '#15803d', primary_dark: '#166534', primary_light: '#f0fdf4',
            secondary: '#1a2e1c', secondary_hover: '#2d4a30', secondary_light: '#3f6644',
            text_primary: '#1a2e1c', text_secondary: '#3f6644', text_muted: '#5f8463', text_light: '#8fac92',
            bg_body: '#ffffff', bg_light: '#f7faf7', bg_gray: '#e6ece6', bg_dark: '#0a1f0c',
            success: '#22c55e', warning: '#f59e0b', danger: '#ef4444', info: '#06b6d4',
            border: '#d4e2d4', border_light: '#e6ece6', border_dark: '#bfcfbf',
            header_bg: '#ffffff', footer_bg: '#0a1f0c', footer_text: '#ffffff', footer_link_hover: '#16a34a'
        },
        purple: {
            primary: '#9333ea', primary_hover: '#7c3aed', primary_dark: '#6b21a8', primary_light: '#faf5ff',
            secondary: '#2e1a47', secondary_hover: '#452d68', secondary_light: '#5b4078',
            text_primary: '#2e1a47', text_secondary: '#5b4078', text_muted: '#7a5f9a', text_light: '#a890c4',
            bg_body: '#ffffff', bg_light: '#faf7fc', bg_gray: '#ece6f0', bg_dark: '#1a0f24',
            success: '#22c55e', warning: '#f59e0b', danger: '#ef4444', info: '#06b6d4',
            border: '#e2d4ec', border_light: '#ece6f0', border_dark: '#cfbfdb',
            header_bg: '#ffffff', footer_bg: '#1a0f24', footer_text: '#ffffff', footer_link_hover: '#9333ea'
        },
        orange: {
            primary: '#ea580c', primary_hover: '#c2410c', primary_dark: '#9a3412', primary_light: '#fff7ed',
            secondary: '#3d2314', secondary_hover: '#5c3a24', secondary_light: '#7a5137',
            text_primary: '#3d2314', text_secondary: '#7a5137', text_muted: '#9a7157', text_light: '#c4a088',
            bg_body: '#ffffff', bg_light: '#fffaf5', bg_gray: '#f5ebe0', bg_dark: '#1f1108',
            success: '#22c55e', warning: '#f59e0b', danger: '#ef4444', info: '#06b6d4',
            border: '#e8dcd0', border_light: '#f5ebe0', border_dark: '#d4c4b4',
            header_bg: '#ffffff', footer_bg: '#1f1108', footer_text: '#ffffff', footer_link_hover: '#ea580c'
        },
        teal: {
            primary: '#0d9488', primary_hover: '#0f766e', primary_dark: '#115e59', primary_light: '#f0fdfa',
            secondary: '#134e4a', secondary_hover: '#1e6b66', secondary_light: '#2a8882',
            text_primary: '#134e4a', text_secondary: '#2a8882', text_muted: '#4da8a1', text_light: '#80c9c3',
            bg_body: '#ffffff', bg_light: '#f5fcfb', bg_gray: '#e0f2f1', bg_dark: '#042f2e',
            success: '#22c55e', warning: '#f59e0b', danger: '#ef4444', info: '#06b6d4',
            border: '#cce8e6', border_light: '#e0f2f1', border_dark: '#b2d9d6',
            header_bg: '#ffffff', footer_bg: '#042f2e', footer_text: '#ffffff', footer_link_hover: '#0d9488'
        }
    };

    // Update preview function
    function updatePreview() {
        var root = document.documentElement;
        root.style.setProperty('--pv-primary', $('#theme_primary').val());
        root.style.setProperty('--pv-primary-hover', $('#theme_primary_hover').val());
        root.style.setProperty('--pv-primary-light', $('#theme_primary_light').val());
        root.style.setProperty('--pv-secondary', $('#theme_secondary').val());
        root.style.setProperty('--pv-text-primary', $('#theme_text_primary').val());
        root.style.setProperty('--pv-text-muted', $('#theme_text_muted').val());
        root.style.setProperty('--pv-bg-body', $('#theme_bg_body').val());
        root.style.setProperty('--pv-bg-dark', $('#theme_bg_dark').val());
        root.style.setProperty('--pv-success', $('#theme_success').val());
        root.style.setProperty('--pv-warning', $('#theme_warning').val());
        root.style.setProperty('--pv-danger', $('#theme_danger').val());
        root.style.setProperty('--pv-border', $('#theme_border').val());
        root.style.setProperty('--pv-footer-text', $('#theme_footer_text').val());
        root.style.setProperty('--pv-text-white', '#ffffff');
    }

    // Initial preview
    updatePreview();

    // Update on colorpicker change
    $('.cp').on('changeColor', function() {
        updatePreview();
    });

    // Update on input change
    $('.color-field').on('input change', function() {
        updatePreview();
    });

    // Preset buttons
    $('.preset-btn').on('click', function() {
        var presetName = $(this).data('preset');
        var preset = presets[presetName];

        if (preset) {
            $('#theme_primary').val(preset.primary);
            $('#theme_primary_hover').val(preset.primary_hover);
            $('#theme_primary_dark').val(preset.primary_dark);
            $('#theme_primary_light').val(preset.primary_light);
            $('#theme_secondary').val(preset.secondary);
            $('#theme_secondary_hover').val(preset.secondary_hover);
            $('#theme_secondary_light').val(preset.secondary_light);
            $('#theme_text_primary').val(preset.text_primary);
            $('#theme_text_secondary').val(preset.text_secondary);
            $('#theme_text_muted').val(preset.text_muted);
            $('#theme_text_light').val(preset.text_light);
            $('#theme_bg_body').val(preset.bg_body);
            $('#theme_bg_light').val(preset.bg_light);
            $('#theme_bg_gray').val(preset.bg_gray);
            $('#theme_bg_dark').val(preset.bg_dark);
            $('#theme_success').val(preset.success);
            $('#theme_warning').val(preset.warning);
            $('#theme_danger').val(preset.danger);
            $('#theme_info').val(preset.info);
            $('#theme_border').val(preset.border);
            $('#theme_border_light').val(preset.border_light);
            $('#theme_border_dark').val(preset.border_dark);
            $('#theme_header_bg').val(preset.header_bg);
            $('#theme_footer_bg').val(preset.footer_bg);
            $('#theme_footer_text').val(preset.footer_text);
            $('#theme_footer_link_hover').val(preset.footer_link_hover);

            // Reinitialize colorpickers and update addon colors
            $('.cp').each(function() {
                var val = $(this).find('input').val();
                $(this).colorpicker('setValue', val);
                $(this).find('.input-group-addon i').css('background-color', val);
            });

            updatePreview();
        }
    });
});
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\Users\hp\Herd\new\resources\views/admin/generalsetting/theme_colors.blade.php ENDPATH**/ ?>