<?php $__env->startSection('styles'); ?>
<style>
/* Theme Builder Styles */
.theme-builder-wrapper {
    background: #f8f9fa;
    min-height: 100vh;
    padding: 20px 0;
}

.theme-builder-header {
    background: linear-gradient(135deg, #1f0300 0%, #4c3533 100%);
    color: #fff;
    padding: 25px 30px;
    border-radius: 12px;
    margin-bottom: 25px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.theme-builder-header h2 {
    margin: 0;
    font-size: 24px;
    font-weight: 600;
}

.theme-builder-header .subtitle {
    opacity: 0.8;
    font-size: 14px;
    margin-top: 5px;
}

/* Tabs */
.theme-tabs {
    display: flex;
    flex-wrap: wrap;
    gap: 5px;
    background: #fff;
    padding: 10px;
    border-radius: 12px;
    margin-bottom: 20px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}

.theme-tab {
    padding: 12px 20px;
    border: none;
    background: transparent;
    color: #666;
    font-size: 14px;
    font-weight: 500;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    gap: 8px;
}

.theme-tab:hover {
    background: #f0f0f0;
    color: #333;
}

.theme-tab.active {
    background: #c3002f;
    color: #fff;
}

.theme-tab i {
    font-size: 16px;
}

/* Tab Content */
.theme-tab-content {
    display: none;
    background: #fff;
    border-radius: 12px;
    padding: 25px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}

.theme-tab-content.active {
    display: block;
}

/* Section */
.theme-section {
    margin-bottom: 30px;
    padding-bottom: 30px;
    border-bottom: 1px solid #eee;
}

.theme-section:last-child {
    margin-bottom: 0;
    padding-bottom: 0;
    border-bottom: none;
}

.theme-section-title {
    font-size: 16px;
    font-weight: 600;
    color: #333;
    margin-bottom: 5px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.theme-section-title i {
    color: #c3002f;
}

.theme-section-desc {
    font-size: 13px;
    color: #888;
    margin-bottom: 20px;
}

/* Color Input */
.color-input-wrapper {
    margin-bottom: 15px;
}

.color-input-wrapper label {
    display: block;
    font-size: 13px;
    font-weight: 500;
    color: #555;
    margin-bottom: 8px;
}

.color-input-group {
    display: flex;
    align-items: stretch;
    border: 1px solid #ddd;
    border-radius: 8px;
    overflow: hidden;
    background: #fff;
}

.color-input-group input {
    flex: 1;
    border: none;
    padding: 10px 12px;
    font-size: 14px;
    color: #333;
    background: transparent;
}

.color-input-group input:focus {
    outline: none;
}

.color-input-group .color-preview {
    width: 45px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f5f5f5;
    cursor: pointer;
    border-left: 1px solid #ddd;
}

.color-input-group .color-preview i {
    width: 24px;
    height: 24px;
    border-radius: 4px;
    border: 1px solid #ccc;
}

/* Text/Number Input */
.text-input-wrapper {
    margin-bottom: 15px;
}

.text-input-wrapper label {
    display: block;
    font-size: 13px;
    font-weight: 500;
    color: #555;
    margin-bottom: 8px;
}

.text-input-wrapper input,
.text-input-wrapper select {
    width: 100%;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 10px 12px;
    font-size: 14px;
    color: #333;
}

.text-input-wrapper input:focus,
.text-input-wrapper select:focus {
    outline: none;
    border-color: #c3002f;
}

/* Range Input */
.range-input-wrapper {
    margin-bottom: 15px;
}

.range-input-wrapper label {
    display: flex;
    justify-content: space-between;
    font-size: 13px;
    font-weight: 500;
    color: #555;
    margin-bottom: 8px;
}

.range-input-wrapper .range-value {
    color: #c3002f;
    font-weight: 600;
}

.range-input-wrapper input[type="range"] {
    width: 100%;
    height: 6px;
    border-radius: 3px;
    background: #e0e0e0;
    outline: none;
    -webkit-appearance: none;
}

.range-input-wrapper input[type="range"]::-webkit-slider-thumb {
    -webkit-appearance: none;
    width: 18px;
    height: 18px;
    border-radius: 50%;
    background: #c3002f;
    cursor: pointer;
}

/* Preview Panel */
.preview-panel {
    position: sticky;
    top: 20px;
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 2px 15px rgba(0,0,0,0.1);
    overflow: hidden;
}

.preview-header {
    background: var(--pv-bg-dark, #030712);
    color: #fff;
    padding: 15px 20px;
    font-weight: 600;
}

.preview-body {
    padding: 20px;
    background: var(--pv-bg-body, #fff);
}

.preview-footer {
    background: var(--pv-footer-bg, #030712);
    padding: 15px 20px;
    color: var(--pv-footer-text, #fff);
    font-size: 13px;
}

/* Preview Components */
.preview-btn {
    display: inline-block;
    padding: var(--pv-btn-py, 10px) var(--pv-btn-px, 20px);
    font-size: var(--pv-btn-font-size, 14px);
    font-weight: var(--pv-btn-font-weight, 600);
    border-radius: var(--pv-btn-radius, 8px);
    border: none;
    cursor: pointer;
    margin-right: 8px;
    margin-bottom: 8px;
    transition: all 0.2s;
}

.preview-btn-primary {
    background: var(--pv-primary, #c3002f);
    color: #fff;
}

.preview-btn-primary:hover {
    background: var(--pv-primary-hover, #a00025);
}

.preview-btn-secondary {
    background: var(--pv-secondary, #1f0300);
    color: #fff;
}

.preview-card {
    background: var(--pv-card-bg, #fff);
    border: 1px solid var(--pv-card-border, #e9e6e6);
    border-radius: var(--pv-card-radius, 12px);
    padding: var(--pv-card-padding, 15px);
    box-shadow: var(--pv-card-shadow, 0 2px 8px rgba(0,0,0,0.08));
    margin-bottom: 15px;
}

.preview-input {
    width: 100%;
    height: var(--pv-input-height, 40px);
    border: 1px solid var(--pv-input-border, #ddd);
    border-radius: var(--pv-input-radius, 8px);
    padding: 0 12px;
    font-size: 14px;
}

.preview-input:focus {
    border-color: var(--pv-primary, #c3002f);
    box-shadow: var(--pv-input-focus-shadow, 0 0 0 3px rgba(195,0,47,0.1));
    outline: none;
}

.preview-badge {
    display: inline-block;
    padding: 4px 12px;
    font-size: 12px;
    font-weight: 600;
    border-radius: var(--pv-badge-radius, 20px);
    margin-right: 5px;
}

.preview-badge-primary { background: var(--pv-primary, #c3002f); color: #fff; }
.preview-badge-success { background: var(--pv-success, #27be69); color: #fff; }
.preview-badge-warning { background: var(--pv-warning, #fac03c); color: #333; }
.preview-badge-danger { background: var(--pv-danger, #f2415a); color: #fff; }

.preview-text-primary { color: var(--pv-text-primary, #1f0300); }
.preview-text-muted { color: var(--pv-text-muted, #796866); }

/* Presets */
.preset-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
    gap: 10px;
    margin-bottom: 20px;
}

.preset-btn {
    padding: 12px 16px;
    border: 2px solid transparent;
    border-radius: 8px;
    text-align: center;
    font-size: 13px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
}

.preset-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.2);
}

.preset-btn.active {
    border-color: #333;
}

/* Save Button */
.save-btn-wrapper {
    position: fixed;
    bottom: 20px;
    right: 20px;
    z-index: 100;
}

.save-btn {
    background: linear-gradient(135deg, #c3002f 0%, #a00025 100%);
    color: #fff;
    border: none;
    padding: 15px 40px;
    font-size: 16px;
    font-weight: 600;
    border-radius: 50px;
    cursor: pointer;
    box-shadow: 0 4px 20px rgba(195, 0, 47, 0.4);
    transition: all 0.3s;
    display: flex;
    align-items: center;
    gap: 10px;
}

.save-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 25px rgba(195, 0, 47, 0.5);
}

/* Responsive */
@media (max-width: 991px) {
    .preview-panel {
        position: relative;
        top: 0;
        margin-bottom: 20px;
    }

    .theme-tabs {
        overflow-x: auto;
        flex-wrap: nowrap;
    }

    .theme-tab {
        white-space: nowrap;
    }
}
</style>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<div class="content-area">
    <div class="container-fluid theme-builder-wrapper">
        <!-- Header -->
        <div class="theme-builder-header">
            <div>
                <h2><i class="fas fa-palette"></i> <?php echo e(__('Theme Builder')); ?></h2>
                <p class="subtitle"><?php echo e(__('Customize every aspect of your theme')); ?></p>
            </div>
            <div>
                <button type="button" class="btn btn-light" onclick="resetToDefaults()">
                    <i class="fas fa-undo"></i> <?php echo e(__('Reset')); ?>

                </button>
            </div>
        </div>

        <form action="<?php echo e(route('admin-theme-colors-update')); ?>" method="POST" id="themeBuilderForm">
            <?php echo csrf_field(); ?>
            <?php echo $__env->make('alerts.admin.form-both', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

            <div class="row">
                <!-- Main Content -->
                <div class="col-lg-8">
                    <!-- Tabs Navigation -->
                    <div class="theme-tabs">
                        <button type="button" class="theme-tab active" data-tab="colors">
                            <i class="fas fa-palette"></i> <?php echo e(__('Colors')); ?>

                        </button>
                        <button type="button" class="theme-tab" data-tab="typography">
                            <i class="fas fa-font"></i> <?php echo e(__('Typography')); ?>

                        </button>
                        <button type="button" class="theme-tab" data-tab="buttons">
                            <i class="fas fa-hand-pointer"></i> <?php echo e(__('Buttons')); ?>

                        </button>
                        <button type="button" class="theme-tab" data-tab="cards">
                            <i class="fas fa-square"></i> <?php echo e(__('Cards')); ?>

                        </button>
                        <button type="button" class="theme-tab" data-tab="forms">
                            <i class="fas fa-edit"></i> <?php echo e(__('Forms')); ?>

                        </button>
                        <button type="button" class="theme-tab" data-tab="header">
                            <i class="fas fa-window-maximize"></i> <?php echo e(__('Header')); ?>

                        </button>
                        <button type="button" class="theme-tab" data-tab="footer">
                            <i class="fas fa-window-minimize"></i> <?php echo e(__('Footer')); ?>

                        </button>
                        <button type="button" class="theme-tab" data-tab="components">
                            <i class="fas fa-puzzle-piece"></i> <?php echo e(__('Components')); ?>

                        </button>
                    </div>

                    <!-- ================================ -->
                    <!-- TAB: COLORS -->
                    <!-- ================================ -->
                    <div class="theme-tab-content active" id="tab-colors">
                        <!-- Quick Presets -->
                        <div class="theme-section">
                            <h4 class="theme-section-title"><i class="fas fa-magic"></i> <?php echo e(__('Quick Presets')); ?></h4>
                            <p class="theme-section-desc"><?php echo e(__('Select a preset to quickly apply a complete color scheme')); ?></p>
                            <div class="preset-grid">
                                <button type="button" class="preset-btn" data-preset="nissan" style="background: #c3002f; color: #fff;"><?php echo e(__('Nissan Red')); ?></button>
                                <button type="button" class="preset-btn" data-preset="blue" style="background: #2563eb; color: #fff;"><?php echo e(__('Blue')); ?></button>
                                <button type="button" class="preset-btn" data-preset="green" style="background: #16a34a; color: #fff;"><?php echo e(__('Green')); ?></button>
                                <button type="button" class="preset-btn" data-preset="purple" style="background: #9333ea; color: #fff;"><?php echo e(__('Purple')); ?></button>
                                <button type="button" class="preset-btn" data-preset="orange" style="background: #ea580c; color: #fff;"><?php echo e(__('Orange')); ?></button>
                                <button type="button" class="preset-btn" data-preset="teal" style="background: #0d9488; color: #fff;"><?php echo e(__('Teal')); ?></button>
                            </div>
                        </div>

                        <!-- Primary Colors -->
                        <div class="theme-section">
                            <h4 class="theme-section-title"><i class="fas fa-star"></i> <?php echo e(__('Primary Colors')); ?></h4>
                            <p class="theme-section-desc"><?php echo e(__('Main brand color used for buttons, links, and accents')); ?></p>
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="color-input-wrapper">
                                        <label><?php echo e(__('Primary')); ?></label>
                                        <div class="color-input-group">
                                            <input type="text" name="theme_primary" id="theme_primary" class="color-field" value="<?php echo e($gs->theme_primary ?? '#c3002f'); ?>">
                                            <span class="color-preview cp"><i></i></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="color-input-wrapper">
                                        <label><?php echo e(__('Hover')); ?></label>
                                        <div class="color-input-group">
                                            <input type="text" name="theme_primary_hover" id="theme_primary_hover" class="color-field" value="<?php echo e($gs->theme_primary_hover ?? '#a00025'); ?>">
                                            <span class="color-preview cp"><i></i></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="color-input-wrapper">
                                        <label><?php echo e(__('Dark')); ?></label>
                                        <div class="color-input-group">
                                            <input type="text" name="theme_primary_dark" id="theme_primary_dark" class="color-field" value="<?php echo e($gs->theme_primary_dark ?? '#8a0020'); ?>">
                                            <span class="color-preview cp"><i></i></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="color-input-wrapper">
                                        <label><?php echo e(__('Light')); ?></label>
                                        <div class="color-input-group">
                                            <input type="text" name="theme_primary_light" id="theme_primary_light" class="color-field" value="<?php echo e($gs->theme_primary_light ?? '#fef2f4'); ?>">
                                            <span class="color-preview cp"><i></i></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Secondary Colors -->
                        <div class="theme-section">
                            <h4 class="theme-section-title"><i class="fas fa-adjust"></i> <?php echo e(__('Secondary Colors')); ?></h4>
                            <p class="theme-section-desc"><?php echo e(__('Used for dark sections, secondary buttons, and text')); ?></p>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="color-input-wrapper">
                                        <label><?php echo e(__('Secondary')); ?></label>
                                        <div class="color-input-group">
                                            <input type="text" name="theme_secondary" id="theme_secondary" class="color-field" value="<?php echo e($gs->theme_secondary ?? '#1f0300'); ?>">
                                            <span class="color-preview cp"><i></i></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="color-input-wrapper">
                                        <label><?php echo e(__('Hover')); ?></label>
                                        <div class="color-input-group">
                                            <input type="text" name="theme_secondary_hover" id="theme_secondary_hover" class="color-field" value="<?php echo e($gs->theme_secondary_hover ?? '#351c1a'); ?>">
                                            <span class="color-preview cp"><i></i></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="color-input-wrapper">
                                        <label><?php echo e(__('Light')); ?></label>
                                        <div class="color-input-group">
                                            <input type="text" name="theme_secondary_light" id="theme_secondary_light" class="color-field" value="<?php echo e($gs->theme_secondary_light ?? '#4c3533'); ?>">
                                            <span class="color-preview cp"><i></i></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Text Colors -->
                        <div class="theme-section">
                            <h4 class="theme-section-title"><i class="fas fa-font"></i> <?php echo e(__('Text Colors')); ?></h4>
                            <p class="theme-section-desc"><?php echo e(__('Colors for headings, paragraphs, and labels')); ?></p>
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="color-input-wrapper">
                                        <label><?php echo e(__('Primary Text')); ?></label>
                                        <div class="color-input-group">
                                            <input type="text" name="theme_text_primary" id="theme_text_primary" class="color-field" value="<?php echo e($gs->theme_text_primary ?? '#1f0300'); ?>">
                                            <span class="color-preview cp"><i></i></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="color-input-wrapper">
                                        <label><?php echo e(__('Secondary Text')); ?></label>
                                        <div class="color-input-group">
                                            <input type="text" name="theme_text_secondary" id="theme_text_secondary" class="color-field" value="<?php echo e($gs->theme_text_secondary ?? '#4c3533'); ?>">
                                            <span class="color-preview cp"><i></i></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="color-input-wrapper">
                                        <label><?php echo e(__('Muted')); ?></label>
                                        <div class="color-input-group">
                                            <input type="text" name="theme_text_muted" id="theme_text_muted" class="color-field" value="<?php echo e($gs->theme_text_muted ?? '#796866'); ?>">
                                            <span class="color-preview cp"><i></i></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="color-input-wrapper">
                                        <label><?php echo e(__('Light')); ?></label>
                                        <div class="color-input-group">
                                            <input type="text" name="theme_text_light" id="theme_text_light" class="color-field" value="<?php echo e($gs->theme_text_light ?? '#9a8e8c'); ?>">
                                            <span class="color-preview cp"><i></i></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Background Colors -->
                        <div class="theme-section">
                            <h4 class="theme-section-title"><i class="fas fa-fill-drip"></i> <?php echo e(__('Background Colors')); ?></h4>
                            <p class="theme-section-desc"><?php echo e(__('Colors for page backgrounds, cards, and sections')); ?></p>
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="color-input-wrapper">
                                        <label><?php echo e(__('Body')); ?></label>
                                        <div class="color-input-group">
                                            <input type="text" name="theme_bg_body" id="theme_bg_body" class="color-field" value="<?php echo e($gs->theme_bg_body ?? '#ffffff'); ?>">
                                            <span class="color-preview cp"><i></i></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="color-input-wrapper">
                                        <label><?php echo e(__('Light')); ?></label>
                                        <div class="color-input-group">
                                            <input type="text" name="theme_bg_light" id="theme_bg_light" class="color-field" value="<?php echo e($gs->theme_bg_light ?? '#f8f7f7'); ?>">
                                            <span class="color-preview cp"><i></i></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="color-input-wrapper">
                                        <label><?php echo e(__('Gray')); ?></label>
                                        <div class="color-input-group">
                                            <input type="text" name="theme_bg_gray" id="theme_bg_gray" class="color-field" value="<?php echo e($gs->theme_bg_gray ?? '#e9e6e6'); ?>">
                                            <span class="color-preview cp"><i></i></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="color-input-wrapper">
                                        <label><?php echo e(__('Dark')); ?></label>
                                        <div class="color-input-group">
                                            <input type="text" name="theme_bg_dark" id="theme_bg_dark" class="color-field" value="<?php echo e($gs->theme_bg_dark ?? '#030712'); ?>">
                                            <span class="color-preview cp"><i></i></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Status Colors -->
                        <div class="theme-section">
                            <h4 class="theme-section-title"><i class="fas fa-check-circle"></i> <?php echo e(__('Status Colors')); ?></h4>
                            <p class="theme-section-desc"><?php echo e(__('Colors for success, warning, danger, and info states')); ?></p>
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="color-input-wrapper">
                                        <label><i class="fas fa-check text-success"></i> <?php echo e(__('Success')); ?></label>
                                        <div class="color-input-group">
                                            <input type="text" name="theme_success" id="theme_success" class="color-field" value="<?php echo e($gs->theme_success ?? '#27be69'); ?>">
                                            <span class="color-preview cp"><i></i></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="color-input-wrapper">
                                        <label><i class="fas fa-exclamation-triangle text-warning"></i> <?php echo e(__('Warning')); ?></label>
                                        <div class="color-input-group">
                                            <input type="text" name="theme_warning" id="theme_warning" class="color-field" value="<?php echo e($gs->theme_warning ?? '#fac03c'); ?>">
                                            <span class="color-preview cp"><i></i></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="color-input-wrapper">
                                        <label><i class="fas fa-times-circle text-danger"></i> <?php echo e(__('Danger')); ?></label>
                                        <div class="color-input-group">
                                            <input type="text" name="theme_danger" id="theme_danger" class="color-field" value="<?php echo e($gs->theme_danger ?? '#f2415a'); ?>">
                                            <span class="color-preview cp"><i></i></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="color-input-wrapper">
                                        <label><i class="fas fa-info-circle text-info"></i> <?php echo e(__('Info')); ?></label>
                                        <div class="color-input-group">
                                            <input type="text" name="theme_info" id="theme_info" class="color-field" value="<?php echo e($gs->theme_info ?? '#0ea5e9'); ?>">
                                            <span class="color-preview cp"><i></i></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Border Colors -->
                        <div class="theme-section">
                            <h4 class="theme-section-title"><i class="fas fa-border-style"></i> <?php echo e(__('Border Colors')); ?></h4>
                            <p class="theme-section-desc"><?php echo e(__('Colors for borders and dividers')); ?></p>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="color-input-wrapper">
                                        <label><?php echo e(__('Default')); ?></label>
                                        <div class="color-input-group">
                                            <input type="text" name="theme_border" id="theme_border" class="color-field" value="<?php echo e($gs->theme_border ?? '#d9d4d4'); ?>">
                                            <span class="color-preview cp"><i></i></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="color-input-wrapper">
                                        <label><?php echo e(__('Light')); ?></label>
                                        <div class="color-input-group">
                                            <input type="text" name="theme_border_light" id="theme_border_light" class="color-field" value="<?php echo e($gs->theme_border_light ?? '#e9e6e6'); ?>">
                                            <span class="color-preview cp"><i></i></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="color-input-wrapper">
                                        <label><?php echo e(__('Dark')); ?></label>
                                        <div class="color-input-group">
                                            <input type="text" name="theme_border_dark" id="theme_border_dark" class="color-field" value="<?php echo e($gs->theme_border_dark ?? '#c7c0bf'); ?>">
                                            <span class="color-preview cp"><i></i></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ================================ -->
                    <!-- TAB: TYPOGRAPHY -->
                    <!-- ================================ -->
                    <div class="theme-tab-content" id="tab-typography">
                        <div class="theme-section">
                            <h4 class="theme-section-title"><i class="fas fa-font"></i> <?php echo e(__('Font Families')); ?></h4>
                            <p class="theme-section-desc"><?php echo e(__('Choose fonts for your theme')); ?></p>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="text-input-wrapper">
                                        <label><?php echo e(__('Primary Font')); ?></label>
                                        <select name="theme_font_primary" id="theme_font_primary">
                                            <option value="Poppins" <?php echo e(($gs->theme_font_primary ?? 'Poppins') == 'Poppins' ? 'selected' : ''); ?>>Poppins</option>
                                            <option value="Inter" <?php echo e(($gs->theme_font_primary ?? '') == 'Inter' ? 'selected' : ''); ?>>Inter</option>
                                            <option value="Roboto" <?php echo e(($gs->theme_font_primary ?? '') == 'Roboto' ? 'selected' : ''); ?>>Roboto</option>
                                            <option value="Open Sans" <?php echo e(($gs->theme_font_primary ?? '') == 'Open Sans' ? 'selected' : ''); ?>>Open Sans</option>
                                            <option value="Lato" <?php echo e(($gs->theme_font_primary ?? '') == 'Lato' ? 'selected' : ''); ?>>Lato</option>
                                            <option value="Montserrat" <?php echo e(($gs->theme_font_primary ?? '') == 'Montserrat' ? 'selected' : ''); ?>>Montserrat</option>
                                            <option value="Cairo" <?php echo e(($gs->theme_font_primary ?? '') == 'Cairo' ? 'selected' : ''); ?>>Cairo (Arabic)</option>
                                            <option value="Tajawal" <?php echo e(($gs->theme_font_primary ?? '') == 'Tajawal' ? 'selected' : ''); ?>>Tajawal (Arabic)</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="text-input-wrapper">
                                        <label><?php echo e(__('Heading Font')); ?></label>
                                        <select name="theme_font_heading" id="theme_font_heading">
                                            <option value="Saira" <?php echo e(($gs->theme_font_heading ?? 'Saira') == 'Saira' ? 'selected' : ''); ?>>Saira</option>
                                            <option value="Poppins" <?php echo e(($gs->theme_font_heading ?? '') == 'Poppins' ? 'selected' : ''); ?>>Poppins</option>
                                            <option value="Montserrat" <?php echo e(($gs->theme_font_heading ?? '') == 'Montserrat' ? 'selected' : ''); ?>>Montserrat</option>
                                            <option value="Playfair Display" <?php echo e(($gs->theme_font_heading ?? '') == 'Playfair Display' ? 'selected' : ''); ?>>Playfair Display</option>
                                            <option value="Cairo" <?php echo e(($gs->theme_font_heading ?? '') == 'Cairo' ? 'selected' : ''); ?>>Cairo (Arabic)</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="theme-section">
                            <h4 class="theme-section-title"><i class="fas fa-text-height"></i> <?php echo e(__('Font Sizes')); ?></h4>
                            <p class="theme-section-desc"><?php echo e(__('Set base font sizes')); ?></p>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="text-input-wrapper">
                                        <label><?php echo e(__('Base Size')); ?></label>
                                        <input type="text" name="theme_font_size_base" value="<?php echo e($gs->theme_font_size_base ?? '16px'); ?>">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="text-input-wrapper">
                                        <label><?php echo e(__('Small Size')); ?></label>
                                        <input type="text" name="theme_font_size_sm" value="<?php echo e($gs->theme_font_size_sm ?? '14px'); ?>">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="text-input-wrapper">
                                        <label><?php echo e(__('Large Size')); ?></label>
                                        <input type="text" name="theme_font_size_lg" value="<?php echo e($gs->theme_font_size_lg ?? '18px'); ?>">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="theme-section">
                            <h4 class="theme-section-title"><i class="fas fa-ruler-vertical"></i> <?php echo e(__('Border Radius')); ?></h4>
                            <p class="theme-section-desc"><?php echo e(__('Control the roundness of corners')); ?></p>
                            <div class="row">
                                <div class="col-md-2">
                                    <div class="text-input-wrapper">
                                        <label><?php echo e(__('XS')); ?></label>
                                        <input type="text" name="theme_radius_xs" value="<?php echo e($gs->theme_radius_xs ?? '3px'); ?>">
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="text-input-wrapper">
                                        <label><?php echo e(__('SM')); ?></label>
                                        <input type="text" name="theme_radius_sm" value="<?php echo e($gs->theme_radius_sm ?? '4px'); ?>">
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="text-input-wrapper">
                                        <label><?php echo e(__('Default')); ?></label>
                                        <input type="text" name="theme_radius" value="<?php echo e($gs->theme_radius ?? '8px'); ?>">
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="text-input-wrapper">
                                        <label><?php echo e(__('LG')); ?></label>
                                        <input type="text" name="theme_radius_lg" value="<?php echo e($gs->theme_radius_lg ?? '12px'); ?>">
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="text-input-wrapper">
                                        <label><?php echo e(__('XL')); ?></label>
                                        <input type="text" name="theme_radius_xl" value="<?php echo e($gs->theme_radius_xl ?? '16px'); ?>">
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="text-input-wrapper">
                                        <label><?php echo e(__('Pill')); ?></label>
                                        <input type="text" name="theme_radius_pill" value="<?php echo e($gs->theme_radius_pill ?? '50px'); ?>">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="theme-section">
                            <h4 class="theme-section-title"><i class="fas fa-layer-group"></i> <?php echo e(__('Shadows')); ?></h4>
                            <p class="theme-section-desc"><?php echo e(__('Box shadow presets for depth effects')); ?></p>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="text-input-wrapper">
                                        <label><?php echo e(__('Small Shadow')); ?></label>
                                        <input type="text" name="theme_shadow_sm" value="<?php echo e($gs->theme_shadow_sm ?? '0 1px 3px rgba(0,0,0,0.06)'); ?>">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="text-input-wrapper">
                                        <label><?php echo e(__('Default Shadow')); ?></label>
                                        <input type="text" name="theme_shadow" value="<?php echo e($gs->theme_shadow ?? '0 2px 8px rgba(0,0,0,0.1)'); ?>">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="text-input-wrapper">
                                        <label><?php echo e(__('Large Shadow')); ?></label>
                                        <input type="text" name="theme_shadow_lg" value="<?php echo e($gs->theme_shadow_lg ?? '0 4px 16px rgba(0,0,0,0.15)'); ?>">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ================================ -->
                    <!-- TAB: BUTTONS -->
                    <!-- ================================ -->
                    <div class="theme-tab-content" id="tab-buttons">
                        <div class="theme-section">
                            <h4 class="theme-section-title"><i class="fas fa-hand-pointer"></i> <?php echo e(__('Button Styles')); ?></h4>
                            <p class="theme-section-desc"><?php echo e(__('Customize button appearance')); ?></p>
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="text-input-wrapper">
                                        <label><?php echo e(__('Padding X')); ?></label>
                                        <input type="text" name="theme_btn_padding_x" value="<?php echo e($gs->theme_btn_padding_x ?? '24px'); ?>">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="text-input-wrapper">
                                        <label><?php echo e(__('Padding Y')); ?></label>
                                        <input type="text" name="theme_btn_padding_y" value="<?php echo e($gs->theme_btn_padding_y ?? '12px'); ?>">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="text-input-wrapper">
                                        <label><?php echo e(__('Font Size')); ?></label>
                                        <input type="text" name="theme_btn_font_size" value="<?php echo e($gs->theme_btn_font_size ?? '14px'); ?>">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="text-input-wrapper">
                                        <label><?php echo e(__('Font Weight')); ?></label>
                                        <select name="theme_btn_font_weight">
                                            <option value="400" <?php echo e(($gs->theme_btn_font_weight ?? '600') == '400' ? 'selected' : ''); ?>>Normal (400)</option>
                                            <option value="500" <?php echo e(($gs->theme_btn_font_weight ?? '600') == '500' ? 'selected' : ''); ?>>Medium (500)</option>
                                            <option value="600" <?php echo e(($gs->theme_btn_font_weight ?? '600') == '600' ? 'selected' : ''); ?>>Semibold (600)</option>
                                            <option value="700" <?php echo e(($gs->theme_btn_font_weight ?? '600') == '700' ? 'selected' : ''); ?>>Bold (700)</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="row mt-3">
                                <div class="col-md-6">
                                    <div class="text-input-wrapper">
                                        <label><?php echo e(__('Border Radius')); ?></label>
                                        <input type="text" name="theme_btn_radius" value="<?php echo e($gs->theme_btn_radius ?? '8px'); ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="text-input-wrapper">
                                        <label><?php echo e(__('Box Shadow')); ?></label>
                                        <input type="text" name="theme_btn_shadow" value="<?php echo e($gs->theme_btn_shadow ?? 'none'); ?>">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ================================ -->
                    <!-- TAB: CARDS -->
                    <!-- ================================ -->
                    <div class="theme-tab-content" id="tab-cards">
                        <div class="theme-section">
                            <h4 class="theme-section-title"><i class="fas fa-square"></i> <?php echo e(__('Card Styles')); ?></h4>
                            <p class="theme-section-desc"><?php echo e(__('Customize card appearance')); ?></p>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="color-input-wrapper">
                                        <label><?php echo e(__('Background')); ?></label>
                                        <div class="color-input-group">
                                            <input type="text" name="theme_card_bg" class="color-field" value="<?php echo e($gs->theme_card_bg ?? '#ffffff'); ?>">
                                            <span class="color-preview cp"><i></i></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="color-input-wrapper">
                                        <label><?php echo e(__('Border Color')); ?></label>
                                        <div class="color-input-group">
                                            <input type="text" name="theme_card_border" class="color-field" value="<?php echo e($gs->theme_card_border ?? '#e9e6e6'); ?>">
                                            <span class="color-preview cp"><i></i></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="text-input-wrapper">
                                        <label><?php echo e(__('Border Radius')); ?></label>
                                        <input type="text" name="theme_card_radius" value="<?php echo e($gs->theme_card_radius ?? '12px'); ?>">
                                    </div>
                                </div>
                            </div>
                            <div class="row mt-3">
                                <div class="col-md-4">
                                    <div class="text-input-wrapper">
                                        <label><?php echo e(__('Shadow')); ?></label>
                                        <input type="text" name="theme_card_shadow" value="<?php echo e($gs->theme_card_shadow ?? '0 2px 8px rgba(0,0,0,0.08)'); ?>">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="text-input-wrapper">
                                        <label><?php echo e(__('Hover Shadow')); ?></label>
                                        <input type="text" name="theme_card_hover_shadow" value="<?php echo e($gs->theme_card_hover_shadow ?? '0 4px 16px rgba(0,0,0,0.12)'); ?>">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="text-input-wrapper">
                                        <label><?php echo e(__('Padding')); ?></label>
                                        <input type="text" name="theme_card_padding" value="<?php echo e($gs->theme_card_padding ?? '20px'); ?>">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Product Cards -->
                        <div class="theme-section">
                            <h4 class="theme-section-title"><i class="fas fa-box-open"></i> <?php echo e(__('Product Cards')); ?></h4>
                            <p class="theme-section-desc"><?php echo e(__('Specific styles for product cards')); ?></p>
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="text-input-wrapper">
                                        <label><?php echo e(__('Title Size')); ?></label>
                                        <input type="text" name="theme_product_title_size" value="<?php echo e($gs->theme_product_title_size ?? '14px'); ?>">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="text-input-wrapper">
                                        <label><?php echo e(__('Title Weight')); ?></label>
                                        <select name="theme_product_title_weight">
                                            <option value="400" <?php echo e(($gs->theme_product_title_weight ?? '500') == '400' ? 'selected' : ''); ?>>Normal (400)</option>
                                            <option value="500" <?php echo e(($gs->theme_product_title_weight ?? '500') == '500' ? 'selected' : ''); ?>>Medium (500)</option>
                                            <option value="600" <?php echo e(($gs->theme_product_title_weight ?? '500') == '600' ? 'selected' : ''); ?>>Semibold (600)</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="text-input-wrapper">
                                        <label><?php echo e(__('Price Size')); ?></label>
                                        <input type="text" name="theme_product_price_size" value="<?php echo e($gs->theme_product_price_size ?? '16px'); ?>">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="text-input-wrapper">
                                        <label><?php echo e(__('Hover Scale')); ?></label>
                                        <input type="text" name="theme_product_hover_scale" value="<?php echo e($gs->theme_product_hover_scale ?? '1.02'); ?>">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ================================ -->
                    <!-- TAB: FORMS -->
                    <!-- ================================ -->
                    <div class="theme-tab-content" id="tab-forms">
                        <div class="theme-section">
                            <h4 class="theme-section-title"><i class="fas fa-edit"></i> <?php echo e(__('Input Fields')); ?></h4>
                            <p class="theme-section-desc"><?php echo e(__('Customize form input appearance')); ?></p>
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="text-input-wrapper">
                                        <label><?php echo e(__('Height')); ?></label>
                                        <input type="text" name="theme_input_height" value="<?php echo e($gs->theme_input_height ?? '48px'); ?>">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="color-input-wrapper">
                                        <label><?php echo e(__('Background')); ?></label>
                                        <div class="color-input-group">
                                            <input type="text" name="theme_input_bg" class="color-field" value="<?php echo e($gs->theme_input_bg ?? '#ffffff'); ?>">
                                            <span class="color-preview cp"><i></i></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="color-input-wrapper">
                                        <label><?php echo e(__('Border Color')); ?></label>
                                        <div class="color-input-group">
                                            <input type="text" name="theme_input_border" class="color-field" value="<?php echo e($gs->theme_input_border ?? '#d9d4d4'); ?>">
                                            <span class="color-preview cp"><i></i></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="text-input-wrapper">
                                        <label><?php echo e(__('Border Radius')); ?></label>
                                        <input type="text" name="theme_input_radius" value="<?php echo e($gs->theme_input_radius ?? '8px'); ?>">
                                    </div>
                                </div>
                            </div>
                            <div class="row mt-3">
                                <div class="col-md-4">
                                    <div class="color-input-wrapper">
                                        <label><?php echo e(__('Focus Border')); ?></label>
                                        <div class="color-input-group">
                                            <input type="text" name="theme_input_focus_border" class="color-field" value="<?php echo e($gs->theme_input_focus_border ?? '#c3002f'); ?>">
                                            <span class="color-preview cp"><i></i></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="text-input-wrapper">
                                        <label><?php echo e(__('Focus Shadow')); ?></label>
                                        <input type="text" name="theme_input_focus_shadow" value="<?php echo e($gs->theme_input_focus_shadow ?? '0 0 0 3px rgba(195,0,47,0.1)'); ?>">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="color-input-wrapper">
                                        <label><?php echo e(__('Placeholder Color')); ?></label>
                                        <div class="color-input-group">
                                            <input type="text" name="theme_input_placeholder" class="color-field" value="<?php echo e($gs->theme_input_placeholder ?? '#9a8e8c'); ?>">
                                            <span class="color-preview cp"><i></i></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ================================ -->
                    <!-- TAB: HEADER -->
                    <!-- ================================ -->
                    <div class="theme-tab-content" id="tab-header">
                        <div class="theme-section">
                            <h4 class="theme-section-title"><i class="fas fa-window-maximize"></i> <?php echo e(__('Header Styles')); ?></h4>
                            <p class="theme-section-desc"><?php echo e(__('Customize header appearance')); ?></p>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="color-input-wrapper">
                                        <label><?php echo e(__('Background')); ?></label>
                                        <div class="color-input-group">
                                            <input type="text" name="theme_header_bg" class="color-field" value="<?php echo e($gs->theme_header_bg ?? '#ffffff'); ?>">
                                            <span class="color-preview cp"><i></i></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="text-input-wrapper">
                                        <label><?php echo e(__('Height')); ?></label>
                                        <input type="text" name="theme_header_height" value="<?php echo e($gs->theme_header_height ?? '80px'); ?>">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="text-input-wrapper">
                                        <label><?php echo e(__('Shadow')); ?></label>
                                        <input type="text" name="theme_header_shadow" value="<?php echo e($gs->theme_header_shadow ?? '0 2px 10px rgba(0,0,0,0.1)'); ?>">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="theme-section">
                            <h4 class="theme-section-title"><i class="fas fa-bars"></i> <?php echo e(__('Navigation Links')); ?></h4>
                            <p class="theme-section-desc"><?php echo e(__('Customize navigation link styles')); ?></p>
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="color-input-wrapper">
                                        <label><?php echo e(__('Link Color')); ?></label>
                                        <div class="color-input-group">
                                            <input type="text" name="theme_nav_link_color" class="color-field" value="<?php echo e($gs->theme_nav_link_color ?? '#1f0300'); ?>">
                                            <span class="color-preview cp"><i></i></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="color-input-wrapper">
                                        <label><?php echo e(__('Hover Color')); ?></label>
                                        <div class="color-input-group">
                                            <input type="text" name="theme_nav_link_hover" class="color-field" value="<?php echo e($gs->theme_nav_link_hover ?? '#c3002f'); ?>">
                                            <span class="color-preview cp"><i></i></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="text-input-wrapper">
                                        <label><?php echo e(__('Font Size')); ?></label>
                                        <input type="text" name="theme_nav_font_size" value="<?php echo e($gs->theme_nav_font_size ?? '15px'); ?>">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="text-input-wrapper">
                                        <label><?php echo e(__('Font Weight')); ?></label>
                                        <select name="theme_nav_font_weight">
                                            <option value="400" <?php echo e(($gs->theme_nav_font_weight ?? '500') == '400' ? 'selected' : ''); ?>>Normal (400)</option>
                                            <option value="500" <?php echo e(($gs->theme_nav_font_weight ?? '500') == '500' ? 'selected' : ''); ?>>Medium (500)</option>
                                            <option value="600" <?php echo e(($gs->theme_nav_font_weight ?? '500') == '600' ? 'selected' : ''); ?>>Semibold (600)</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ================================ -->
                    <!-- TAB: FOOTER -->
                    <!-- ================================ -->
                    <div class="theme-tab-content" id="tab-footer">
                        <div class="theme-section">
                            <h4 class="theme-section-title"><i class="fas fa-window-minimize"></i> <?php echo e(__('Footer Styles')); ?></h4>
                            <p class="theme-section-desc"><?php echo e(__('Customize footer appearance')); ?></p>
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="color-input-wrapper">
                                        <label><?php echo e(__('Background')); ?></label>
                                        <div class="color-input-group">
                                            <input type="text" name="theme_footer_bg" class="color-field" value="<?php echo e($gs->theme_footer_bg ?? '#030712'); ?>">
                                            <span class="color-preview cp"><i></i></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="color-input-wrapper">
                                        <label><?php echo e(__('Text Color')); ?></label>
                                        <div class="color-input-group">
                                            <input type="text" name="theme_footer_text" class="color-field" value="<?php echo e($gs->theme_footer_text ?? '#ffffff'); ?>">
                                            <span class="color-preview cp"><i></i></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="color-input-wrapper">
                                        <label><?php echo e(__('Muted Text')); ?></label>
                                        <div class="color-input-group">
                                            <input type="text" name="theme_footer_text_muted" class="color-field" value="<?php echo e($gs->theme_footer_text_muted ?? '#d9d4d4'); ?>">
                                            <span class="color-preview cp"><i></i></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="color-input-wrapper">
                                        <label><?php echo e(__('Link Hover')); ?></label>
                                        <div class="color-input-group">
                                            <input type="text" name="theme_footer_link_hover" class="color-field" value="<?php echo e($gs->theme_footer_link_hover ?? '#c3002f'); ?>">
                                            <span class="color-preview cp"><i></i></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row mt-3">
                                <div class="col-md-4">
                                    <div class="text-input-wrapper">
                                        <label><?php echo e(__('Padding')); ?></label>
                                        <input type="text" name="theme_footer_padding" value="<?php echo e($gs->theme_footer_padding ?? '60px'); ?>">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="color-input-wrapper">
                                        <label><?php echo e(__('Link Color')); ?></label>
                                        <div class="color-input-group">
                                            <input type="text" name="theme_footer_link" class="color-field" value="<?php echo e($gs->theme_footer_link ?? '#ffffff'); ?>">
                                            <span class="color-preview cp"><i></i></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="color-input-wrapper">
                                        <label><?php echo e(__('Border Color')); ?></label>
                                        <div class="color-input-group">
                                            <input type="text" name="theme_footer_border" class="color-field" value="<?php echo e($gs->theme_footer_border ?? '#374151'); ?>">
                                            <span class="color-preview cp"><i></i></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ================================ -->
                    <!-- TAB: COMPONENTS -->
                    <!-- ================================ -->
                    <div class="theme-tab-content" id="tab-components">
                        <!-- Badges -->
                        <div class="theme-section">
                            <h4 class="theme-section-title"><i class="fas fa-tag"></i> <?php echo e(__('Badges')); ?></h4>
                            <p class="theme-section-desc"><?php echo e(__('Customize badge appearance')); ?></p>
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="text-input-wrapper">
                                        <label><?php echo e(__('Border Radius')); ?></label>
                                        <input type="text" name="theme_badge_radius" value="<?php echo e($gs->theme_badge_radius ?? '20px'); ?>">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="text-input-wrapper">
                                        <label><?php echo e(__('Padding')); ?></label>
                                        <input type="text" name="theme_badge_padding" value="<?php echo e($gs->theme_badge_padding ?? '4px 12px'); ?>">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="text-input-wrapper">
                                        <label><?php echo e(__('Font Size')); ?></label>
                                        <input type="text" name="theme_badge_font_size" value="<?php echo e($gs->theme_badge_font_size ?? '12px'); ?>">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="text-input-wrapper">
                                        <label><?php echo e(__('Font Weight')); ?></label>
                                        <select name="theme_badge_font_weight">
                                            <option value="500" <?php echo e(($gs->theme_badge_font_weight ?? '600') == '500' ? 'selected' : ''); ?>>Medium (500)</option>
                                            <option value="600" <?php echo e(($gs->theme_badge_font_weight ?? '600') == '600' ? 'selected' : ''); ?>>Semibold (600)</option>
                                            <option value="700" <?php echo e(($gs->theme_badge_font_weight ?? '600') == '700' ? 'selected' : ''); ?>>Bold (700)</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Scrollbar -->
                        <div class="theme-section">
                            <h4 class="theme-section-title"><i class="fas fa-arrows-alt-v"></i> <?php echo e(__('Scrollbar')); ?></h4>
                            <p class="theme-section-desc"><?php echo e(__('Customize scrollbar colors')); ?></p>
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="text-input-wrapper">
                                        <label><?php echo e(__('Width')); ?></label>
                                        <input type="text" name="theme_scrollbar_width" value="<?php echo e($gs->theme_scrollbar_width ?? '10px'); ?>">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="color-input-wrapper">
                                        <label><?php echo e(__('Track Color')); ?></label>
                                        <div class="color-input-group">
                                            <input type="text" name="theme_scrollbar_track" class="color-field" value="<?php echo e($gs->theme_scrollbar_track ?? '#f1f1f1'); ?>">
                                            <span class="color-preview cp"><i></i></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="color-input-wrapper">
                                        <label><?php echo e(__('Thumb Color')); ?></label>
                                        <div class="color-input-group">
                                            <input type="text" name="theme_scrollbar_thumb" class="color-field" value="<?php echo e($gs->theme_scrollbar_thumb ?? '#c1c1c1'); ?>">
                                            <span class="color-preview cp"><i></i></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="color-input-wrapper">
                                        <label><?php echo e(__('Thumb Hover')); ?></label>
                                        <div class="color-input-group">
                                            <input type="text" name="theme_scrollbar_thumb_hover" class="color-field" value="<?php echo e($gs->theme_scrollbar_thumb_hover ?? '#a1a1a1'); ?>">
                                            <span class="color-preview cp"><i></i></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Modals -->
                        <div class="theme-section">
                            <h4 class="theme-section-title"><i class="fas fa-window-restore"></i> <?php echo e(__('Modals')); ?></h4>
                            <p class="theme-section-desc"><?php echo e(__('Customize modal dialogs')); ?></p>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="color-input-wrapper">
                                        <label><?php echo e(__('Background')); ?></label>
                                        <div class="color-input-group">
                                            <input type="text" name="theme_modal_bg" class="color-field" value="<?php echo e($gs->theme_modal_bg ?? '#ffffff'); ?>">
                                            <span class="color-preview cp"><i></i></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="text-input-wrapper">
                                        <label><?php echo e(__('Border Radius')); ?></label>
                                        <input type="text" name="theme_modal_radius" value="<?php echo e($gs->theme_modal_radius ?? '16px'); ?>">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="text-input-wrapper">
                                        <label><?php echo e(__('Backdrop')); ?></label>
                                        <input type="text" name="theme_modal_backdrop" value="<?php echo e($gs->theme_modal_backdrop ?? 'rgba(0,0,0,0.5)'); ?>">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Tables -->
                        <div class="theme-section">
                            <h4 class="theme-section-title"><i class="fas fa-table"></i> <?php echo e(__('Tables')); ?></h4>
                            <p class="theme-section-desc"><?php echo e(__('Customize table styles')); ?></p>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="color-input-wrapper">
                                        <label><?php echo e(__('Header Background')); ?></label>
                                        <div class="color-input-group">
                                            <input type="text" name="theme_table_header_bg" class="color-field" value="<?php echo e($gs->theme_table_header_bg ?? '#f8f7f7'); ?>">
                                            <span class="color-preview cp"><i></i></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="color-input-wrapper">
                                        <label><?php echo e(__('Border Color')); ?></label>
                                        <div class="color-input-group">
                                            <input type="text" name="theme_table_border" class="color-field" value="<?php echo e($gs->theme_table_border ?? '#e9e6e6'); ?>">
                                            <span class="color-preview cp"><i></i></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="color-input-wrapper">
                                        <label><?php echo e(__('Hover Background')); ?></label>
                                        <div class="color-input-group">
                                            <input type="text" name="theme_table_hover_bg" class="color-field" value="<?php echo e($gs->theme_table_hover_bg ?? '#f8f7f7'); ?>">
                                            <span class="color-preview cp"><i></i></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Preview Panel -->
                <div class="col-lg-4">
                    <div class="preview-panel">
                        <div class="preview-header">
                            <i class="fas fa-eye"></i> <?php echo e(__('Live Preview')); ?>

                        </div>
                        <div class="preview-body">
                            <h6 class="preview-text-primary mb-3"><?php echo e(__('Buttons')); ?></h6>
                            <div class="mb-3">
                                <button type="button" class="preview-btn preview-btn-primary"><?php echo e(__('Primary')); ?></button>
                                <button type="button" class="preview-btn preview-btn-secondary"><?php echo e(__('Secondary')); ?></button>
                            </div>

                            <h6 class="preview-text-primary mb-3"><?php echo e(__('Card')); ?></h6>
                            <div class="preview-card">
                                <p class="preview-text-primary mb-1"><strong><?php echo e(__('Card Title')); ?></strong></p>
                                <p class="preview-text-muted mb-0" style="font-size: 13px;"><?php echo e(__('Card description text goes here')); ?></p>
                            </div>

                            <h6 class="preview-text-primary mb-3"><?php echo e(__('Input')); ?></h6>
                            <input type="text" class="preview-input mb-3" placeholder="<?php echo e(__('Type something...')); ?>">

                            <h6 class="preview-text-primary mb-3"><?php echo e(__('Badges')); ?></h6>
                            <div>
                                <span class="preview-badge preview-badge-primary"><?php echo e(__('Primary')); ?></span>
                                <span class="preview-badge preview-badge-success"><?php echo e(__('Success')); ?></span>
                                <span class="preview-badge preview-badge-warning"><?php echo e(__('Warning')); ?></span>
                                <span class="preview-badge preview-badge-danger"><?php echo e(__('Danger')); ?></span>
                            </div>
                        </div>
                        <div class="preview-footer">
                            <i class="fas fa-check-circle"></i> <?php echo e(__('Footer Preview')); ?>

                        </div>
                    </div>
                </div>
            </div>

            <!-- Save Button -->
            <div class="save-btn-wrapper">
                <button type="submit" class="save-btn">
                    <i class="fas fa-save"></i> <?php echo e(__('Save Theme')); ?>

                </button>
            </div>
        </form>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('scripts'); ?>
<script>
$(document).ready(function() {
    // Tab Navigation
    $('.theme-tab').on('click', function() {
        var tabId = $(this).data('tab');
        $('.theme-tab').removeClass('active');
        $(this).addClass('active');
        $('.theme-tab-content').removeClass('active');
        $('#tab-' + tabId).addClass('active');
    });

    // Initialize colorpickers
    $('.cp').each(function() {
        var $wrapper = $(this).closest('.color-input-group');
        var $input = $wrapper.find('input');
        var $preview = $(this).find('i');

        // Set initial color
        $preview.css('background-color', $input.val());

        // Initialize colorpicker on wrapper
        $wrapper.colorpicker({
            format: 'hex',
            component: '.color-preview'
        }).on('changeColor', function(e) {
            if (e.color) {
                $preview.css('background-color', e.color.toHex());
                updatePreview();
            }
        });
    });

    // Update preview on input change
    $('.color-field').on('input change', function() {
        var $wrapper = $(this).closest('.color-input-group');
        var $preview = $wrapper.find('.color-preview i');
        $preview.css('background-color', $(this).val());
        updatePreview();
    });

    // Presets
    var presets = {
        nissan: {
            theme_primary: '#c3002f', theme_primary_hover: '#a00025', theme_primary_dark: '#8a0020', theme_primary_light: '#fef2f4',
            theme_secondary: '#1f0300', theme_secondary_hover: '#351c1a', theme_secondary_light: '#4c3533',
            theme_text_primary: '#1f0300', theme_text_secondary: '#4c3533', theme_text_muted: '#796866', theme_text_light: '#9a8e8c',
            theme_bg_body: '#ffffff', theme_bg_light: '#f8f7f7', theme_bg_gray: '#e9e6e6', theme_bg_dark: '#030712',
            theme_success: '#27be69', theme_warning: '#fac03c', theme_danger: '#f2415a', theme_info: '#0ea5e9',
            theme_border: '#d9d4d4', theme_border_light: '#e9e6e6', theme_border_dark: '#c7c0bf',
            theme_header_bg: '#ffffff', theme_footer_bg: '#030712', theme_footer_text: '#ffffff', theme_footer_link_hover: '#c3002f'
        },
        blue: {
            theme_primary: '#2563eb', theme_primary_hover: '#1d4ed8', theme_primary_dark: '#1e40af', theme_primary_light: '#eff6ff',
            theme_secondary: '#1e293b', theme_secondary_hover: '#334155', theme_secondary_light: '#475569',
            theme_text_primary: '#1e293b', theme_text_secondary: '#475569', theme_text_muted: '#64748b', theme_text_light: '#94a3b8',
            theme_bg_body: '#ffffff', theme_bg_light: '#f8fafc', theme_bg_gray: '#e2e8f0', theme_bg_dark: '#0f172a',
            theme_success: '#22c55e', theme_warning: '#f59e0b', theme_danger: '#ef4444', theme_info: '#06b6d4',
            theme_border: '#e2e8f0', theme_border_light: '#f1f5f9', theme_border_dark: '#cbd5e1',
            theme_header_bg: '#ffffff', theme_footer_bg: '#0f172a', theme_footer_text: '#ffffff', theme_footer_link_hover: '#2563eb'
        },
        green: {
            theme_primary: '#16a34a', theme_primary_hover: '#15803d', theme_primary_dark: '#166534', theme_primary_light: '#f0fdf4',
            theme_secondary: '#1a2e1c', theme_secondary_hover: '#2d4a30', theme_secondary_light: '#3f6644',
            theme_text_primary: '#1a2e1c', theme_text_secondary: '#3f6644', theme_text_muted: '#5f8463', theme_text_light: '#8fac92',
            theme_bg_body: '#ffffff', theme_bg_light: '#f7faf7', theme_bg_gray: '#e6ece6', theme_bg_dark: '#0a1f0c',
            theme_success: '#22c55e', theme_warning: '#f59e0b', theme_danger: '#ef4444', theme_info: '#06b6d4',
            theme_border: '#d4e2d4', theme_border_light: '#e6ece6', theme_border_dark: '#bfcfbf',
            theme_header_bg: '#ffffff', theme_footer_bg: '#0a1f0c', theme_footer_text: '#ffffff', theme_footer_link_hover: '#16a34a'
        },
        purple: {
            theme_primary: '#9333ea', theme_primary_hover: '#7c3aed', theme_primary_dark: '#6b21a8', theme_primary_light: '#faf5ff',
            theme_secondary: '#2e1a47', theme_secondary_hover: '#452d68', theme_secondary_light: '#5b4078',
            theme_text_primary: '#2e1a47', theme_text_secondary: '#5b4078', theme_text_muted: '#7a5f9a', theme_text_light: '#a890c4',
            theme_bg_body: '#ffffff', theme_bg_light: '#faf7fc', theme_bg_gray: '#ece6f0', theme_bg_dark: '#1a0f24',
            theme_success: '#22c55e', theme_warning: '#f59e0b', theme_danger: '#ef4444', theme_info: '#06b6d4',
            theme_border: '#e2d4ec', theme_border_light: '#ece6f0', theme_border_dark: '#cfbfdb',
            theme_header_bg: '#ffffff', theme_footer_bg: '#1a0f24', theme_footer_text: '#ffffff', theme_footer_link_hover: '#9333ea'
        },
        orange: {
            theme_primary: '#ea580c', theme_primary_hover: '#c2410c', theme_primary_dark: '#9a3412', theme_primary_light: '#fff7ed',
            theme_secondary: '#3d2314', theme_secondary_hover: '#5c3a24', theme_secondary_light: '#7a5137',
            theme_text_primary: '#3d2314', theme_text_secondary: '#7a5137', theme_text_muted: '#9a7157', theme_text_light: '#c4a088',
            theme_bg_body: '#ffffff', theme_bg_light: '#fffaf5', theme_bg_gray: '#f5ebe0', theme_bg_dark: '#1f1108',
            theme_success: '#22c55e', theme_warning: '#f59e0b', theme_danger: '#ef4444', theme_info: '#06b6d4',
            theme_border: '#e8dcd0', theme_border_light: '#f5ebe0', theme_border_dark: '#d4c4b4',
            theme_header_bg: '#ffffff', theme_footer_bg: '#1f1108', theme_footer_text: '#ffffff', theme_footer_link_hover: '#ea580c'
        },
        teal: {
            theme_primary: '#0d9488', theme_primary_hover: '#0f766e', theme_primary_dark: '#115e59', theme_primary_light: '#f0fdfa',
            theme_secondary: '#134e4a', theme_secondary_hover: '#1e6b66', theme_secondary_light: '#2a8882',
            theme_text_primary: '#134e4a', theme_text_secondary: '#2a8882', theme_text_muted: '#4da8a1', theme_text_light: '#80c9c3',
            theme_bg_body: '#ffffff', theme_bg_light: '#f5fcfb', theme_bg_gray: '#e0f2f1', theme_bg_dark: '#042f2e',
            theme_success: '#22c55e', theme_warning: '#f59e0b', theme_danger: '#ef4444', theme_info: '#06b6d4',
            theme_border: '#cce8e6', theme_border_light: '#e0f2f1', theme_border_dark: '#b2d9d6',
            theme_header_bg: '#ffffff', theme_footer_bg: '#042f2e', theme_footer_text: '#ffffff', theme_footer_link_hover: '#0d9488'
        }
    };

    $('.preset-btn').on('click', function() {
        var presetName = $(this).data('preset');
        var preset = presets[presetName];

        if (preset) {
            $.each(preset, function(key, value) {
                var $input = $('input[name="' + key + '"]');
                if ($input.length) {
                    $input.val(value);
                    var $wrapper = $input.closest('.color-input-group');
                    if ($wrapper.length) {
                        $wrapper.find('.color-preview i').css('background-color', value);
                        $wrapper.colorpicker('setValue', value);
                    }
                }
            });
            updatePreview();

            // Mark active
            $('.preset-btn').removeClass('active');
            $(this).addClass('active');
        }
    });

    // Update Preview
    function updatePreview() {
        var root = document.documentElement;

        // Colors
        root.style.setProperty('--pv-primary', $('#theme_primary').val());
        root.style.setProperty('--pv-primary-hover', $('#theme_primary_hover').val());
        root.style.setProperty('--pv-secondary', $('#theme_secondary').val());
        root.style.setProperty('--pv-text-primary', $('#theme_text_primary').val());
        root.style.setProperty('--pv-text-muted', $('#theme_text_muted').val());
        root.style.setProperty('--pv-bg-body', $('#theme_bg_body').val());
        root.style.setProperty('--pv-bg-dark', $('#theme_bg_dark').val());
        root.style.setProperty('--pv-success', $('#theme_success').val());
        root.style.setProperty('--pv-warning', $('#theme_warning').val());
        root.style.setProperty('--pv-danger', $('#theme_danger').val());
        root.style.setProperty('--pv-footer-bg', $('#theme_footer_bg').val() || $('#theme_bg_dark').val());
        root.style.setProperty('--pv-footer-text', $('#theme_footer_text').val());

        // Card
        root.style.setProperty('--pv-card-bg', $('input[name="theme_card_bg"]').val() || '#ffffff');
        root.style.setProperty('--pv-card-border', $('input[name="theme_card_border"]').val() || '#e9e6e6');
        root.style.setProperty('--pv-card-radius', $('input[name="theme_card_radius"]').val() || '12px');
        root.style.setProperty('--pv-card-shadow', $('input[name="theme_card_shadow"]').val() || '0 2px 8px rgba(0,0,0,0.08)');
        root.style.setProperty('--pv-card-padding', $('input[name="theme_card_padding"]').val() || '15px');

        // Button
        root.style.setProperty('--pv-btn-px', $('input[name="theme_btn_padding_x"]').val() || '20px');
        root.style.setProperty('--pv-btn-py', $('input[name="theme_btn_padding_y"]').val() || '10px');
        root.style.setProperty('--pv-btn-radius', $('input[name="theme_btn_radius"]').val() || '8px');
        root.style.setProperty('--pv-btn-font-size', $('input[name="theme_btn_font_size"]').val() || '14px');
        root.style.setProperty('--pv-btn-font-weight', $('select[name="theme_btn_font_weight"]').val() || '600');

        // Input
        root.style.setProperty('--pv-input-height', $('input[name="theme_input_height"]').val() || '40px');
        root.style.setProperty('--pv-input-border', $('input[name="theme_input_border"]').val() || '#ddd');
        root.style.setProperty('--pv-input-radius', $('input[name="theme_input_radius"]').val() || '8px');
        root.style.setProperty('--pv-input-focus-shadow', $('input[name="theme_input_focus_shadow"]').val() || '0 0 0 3px rgba(195,0,47,0.1)');

        // Badge
        root.style.setProperty('--pv-badge-radius', $('input[name="theme_badge_radius"]').val() || '20px');
    }

    // Initial preview
    updatePreview();

    // Update on any input change
    $('input, select').on('input change', function() {
        updatePreview();
    });
});

// Reset to defaults
function resetToDefaults() {
    if (confirm('<?php echo e(__("Are you sure you want to reset all theme settings to defaults?")); ?>')) {
        $('.preset-btn[data-preset="nissan"]').click();
    }
}
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\Users\hp\Herd\new\resources\views/admin/generalsetting/theme_colors.blade.php ENDPATH**/ ?>