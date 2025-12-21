@extends('layouts.admin')

@section('styles')
<style>
/* Theme Builder Styles */
.theme-builder-wrapper {
    background: #f8f9fa;
    min-height: 100vh;
    padding: 20px 0;
}

.theme-builder-header {
    background: linear-gradient(135deg, #1e293b 0%, #475569 100%);
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
    color: #64748b;
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
    background: #f1f5f9;
    color: #334155;
}

.theme-tab.active {
    background: #2563eb;
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
    color: #334155;
    margin-bottom: 5px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.theme-section-title i {
    color: #2563eb;
}

.theme-section-desc {
    font-size: 13px;
    color: #64748b;
    margin-bottom: 20px;
}

/* Color Input - Enhanced with native picker */
.color-input-wrapper {
    margin-bottom: 15px;
}

.color-input-wrapper label {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 13px;
    font-weight: 500;
    color: #475569;
    margin-bottom: 8px;
}

.color-input-wrapper label .color-hint {
    font-size: 11px;
    color: #94a3b8;
    font-weight: 400;
}

.color-input-group {
    display: flex;
    align-items: stretch;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    overflow: hidden;
    background: #fff;
    transition: all 0.2s;
}

.color-input-group:hover {
    border-color: #cbd5e1;
}

.color-input-group:focus-within {
    border-color: #2563eb;
    box-shadow: 0 0 0 3px rgba(37,99,235,0.1);
}

.color-input-group input.color-field {
    flex: 1;
    border: none;
    padding: 10px 12px;
    font-size: 14px;
    font-family: 'Monaco', 'Consolas', monospace;
    color: #334155;
    background: transparent;
    min-width: 0;
}

.color-input-group input.color-field:focus {
    outline: none;
}

/* Native Color Picker */
.color-input-group .native-color-picker {
    width: 50px;
    height: 100%;
    min-height: 42px;
    padding: 0;
    border: none;
    cursor: pointer;
    background: transparent;
}

.color-input-group .native-color-picker::-webkit-color-swatch-wrapper {
    padding: 6px;
}

.color-input-group .native-color-picker::-webkit-color-swatch {
    border: 1px solid #cbd5e1;
    border-radius: 4px;
}

.color-input-group .native-color-picker::-moz-color-swatch {
    border: 1px solid #cbd5e1;
    border-radius: 4px;
}

/* Color Preview Button (for colorpicker popup) */
.color-input-group .color-preview {
    width: 45px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f8fafc;
    cursor: pointer;
    border-left: 1px solid #e2e8f0;
    transition: background 0.2s;
}

.color-input-group .color-preview:hover {
    background: #f1f5f9;
}

.color-input-group .color-preview i {
    width: 26px;
    height: 26px;
    border-radius: 6px;
    border: 2px solid #fff;
    box-shadow: 0 1px 3px rgba(0,0,0,0.15);
}

/* Color Suggestions */
.color-suggestions {
    display: flex;
    gap: 4px;
    margin-top: 6px;
}

.color-suggestion {
    width: 20px;
    height: 20px;
    border-radius: 4px;
    border: 2px solid transparent;
    cursor: pointer;
    transition: all 0.15s;
}

.color-suggestion:hover {
    transform: scale(1.15);
    border-color: #fff;
    box-shadow: 0 2px 6px rgba(0,0,0,0.2);
}

/* Text/Number Input */
.text-input-wrapper {
    margin-bottom: 15px;
}

.text-input-wrapper label {
    display: block;
    font-size: 13px;
    font-weight: 500;
    color: #475569;
    margin-bottom: 8px;
}

.text-input-wrapper input,
.text-input-wrapper select {
    width: 100%;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 10px 12px;
    font-size: 14px;
    color: #334155;
}

.text-input-wrapper input:focus,
.text-input-wrapper select:focus {
    outline: none;
    border-color: #2563eb;
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
    color: #475569;
    margin-bottom: 8px;
}

.range-input-wrapper .range-value {
    color: #2563eb;
    font-weight: 600;
}

.range-input-wrapper input[type="range"] {
    width: 100%;
    height: 6px;
    border-radius: 3px;
    background: #e2e8f0;
    outline: none;
    -webkit-appearance: none;
}

.range-input-wrapper input[type="range"]::-webkit-slider-thumb {
    -webkit-appearance: none;
    width: 18px;
    height: 18px;
    border-radius: 50%;
    background: #2563eb;
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

/* Preview Navigation */
.preview-nav {
    background: var(--pv-header-bg, #fff);
    padding: 12px 15px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    border-bottom: 1px solid var(--pv-border, #e5e7eb);
}

.preview-nav-logo {
    font-weight: 700;
    color: var(--pv-primary, #c3002f);
}

.preview-nav-links {
    display: flex;
    gap: 15px;
}

.preview-nav-link {
    color: var(--pv-nav-link, #374151);
    font-size: 13px;
    text-decoration: none;
}

.preview-topbar {
    background: var(--pv-secondary, #1f2937);
    color: #fff;
    padding: 8px 15px;
    font-size: 12px;
    display: flex;
    justify-content: space-between;
}

/* Action Toolbar */
.action-toolbar {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.toolbar-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 16px;
    border: 1px solid #e2e8f0;
    background: #fff;
    color: #475569;
    font-size: 13px;
    font-weight: 500;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.2s;
}

.toolbar-btn:hover {
    background: #f8fafc;
    border-color: #cbd5e1;
}

.toolbar-btn i {
    font-size: 14px;
}

.toolbar-btn.toolbar-btn-primary {
    background: #2563eb;
    border-color: #2563eb;
    color: #fff;
}

.toolbar-btn.toolbar-btn-primary:hover {
    background: #1d4ed8;
}

/* Color Palette Display */
.color-palette-display {
    display: flex;
    gap: 3px;
    margin-bottom: 20px;
    border-radius: 8px;
    overflow: hidden;
    height: 40px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.color-palette-display .palette-color {
    flex: 1;
    cursor: pointer;
    transition: transform 0.15s;
}

.color-palette-display .palette-color:hover {
    transform: scaleY(1.1);
}

/* Tab Badge Counter */
.tab-badge {
    background: #ef4444;
    color: #fff;
    font-size: 10px;
    padding: 2px 6px;
    border-radius: 10px;
    margin-left: 5px;
}

/* Section Collapsible */
.theme-section-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    cursor: pointer;
    padding: 10px 0;
    margin: -10px 0 10px 0;
}

.theme-section-header .collapse-icon {
    transition: transform 0.2s;
}

.theme-section.collapsed .theme-section-header .collapse-icon {
    transform: rotate(-90deg);
}

.theme-section.collapsed .theme-section-body {
    display: none;
}

/* Quick Copy Button */
.copy-value-btn {
    position: absolute;
    right: 55px;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    color: #94a3b8;
    cursor: pointer;
    padding: 5px;
    opacity: 0;
    transition: opacity 0.2s;
}

.color-input-group:hover .copy-value-btn {
    opacity: 1;
}

.copy-value-btn:hover {
    color: #2563eb;
}

/* Import/Export Modal */
.theme-modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.5);
    z-index: 1050;
    align-items: center;
    justify-content: center;
}

.theme-modal.show {
    display: flex;
}

.theme-modal-content {
    background: #fff;
    border-radius: 12px;
    width: 90%;
    max-width: 600px;
    max-height: 80vh;
    overflow: hidden;
    box-shadow: 0 25px 50px rgba(0,0,0,0.25);
}

.theme-modal-header {
    padding: 20px;
    border-bottom: 1px solid #e5e7eb;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.theme-modal-header h3 {
    margin: 0;
    font-size: 18px;
}

.theme-modal-close {
    background: none;
    border: none;
    font-size: 24px;
    color: #6b7280;
    cursor: pointer;
}

.theme-modal-body {
    padding: 20px;
    max-height: calc(80vh - 130px);
    overflow-y: auto;
}

.theme-modal-body textarea {
    width: 100%;
    min-height: 200px;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    padding: 12px;
    font-family: 'Monaco', 'Consolas', monospace;
    font-size: 12px;
    resize: vertical;
}

/* CSS Variables List */
.css-var-list {
    background: #f8fafc;
    border-radius: 8px;
    padding: 15px;
    max-height: 300px;
    overflow-y: auto;
    font-family: 'Monaco', 'Consolas', monospace;
    font-size: 12px;
}

.css-var-item {
    display: flex;
    align-items: center;
    padding: 5px 0;
    border-bottom: 1px solid #e5e7eb;
}

.css-var-item:last-child {
    border-bottom: none;
}

.css-var-name {
    color: #7c3aed;
    flex: 1;
}

.css-var-value {
    color: #059669;
}

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
    background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
    color: #fff;
    border: none;
    padding: 15px 40px;
    font-size: 16px;
    font-weight: 600;
    border-radius: 50px;
    cursor: pointer;
    box-shadow: 0 4px 20px rgba(37, 99, 235, 0.4);
    transition: all 0.3s;
    display: flex;
    align-items: center;
    gap: 10px;
}

.save-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 25px rgba(37, 99, 235, 0.5);
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
@endsection

@section('content')
<div class="content-area">
    <div class="container-fluid theme-builder-wrapper">
        <!-- Header -->
        <div class="theme-builder-header">
            <div>
                <h2><i class="fas fa-palette"></i> {{ __('Theme Builder') }}</h2>
                <p class="subtitle">{{ __('Customize every aspect of your theme - Full control over colors, typography, and components') }}</p>
            </div>
            <div class="action-toolbar">
                <button type="button" class="toolbar-btn" onclick="exportTheme()">
                    <i class="fas fa-download"></i> {{ __('Export') }}
                </button>
                <button type="button" class="toolbar-btn" onclick="openImportModal()">
                    <i class="fas fa-upload"></i> {{ __('Import') }}
                </button>
                <button type="button" class="toolbar-btn" onclick="copyCSSVariables()">
                    <i class="fas fa-code"></i> {{ __('CSS') }}
                </button>
                <button type="button" class="toolbar-btn" onclick="resetToDefaults()">
                    <i class="fas fa-undo"></i> {{ __('Reset') }}
                </button>
            </div>
        </div>

        <!-- Current Theme Palette Preview -->
        <div class="color-palette-display" id="currentPalette">
            <div class="palette-color" style="background: var(--pv-primary, #c3002f)" title="Primary"></div>
            <div class="palette-color" style="background: var(--pv-primary-hover, #a00025)" title="Primary Hover"></div>
            <div class="palette-color" style="background: var(--pv-secondary, #1f2937)" title="Secondary"></div>
            <div class="palette-color" style="background: var(--pv-success, #10b981)" title="Success"></div>
            <div class="palette-color" style="background: var(--pv-warning, #f59e0b)" title="Warning"></div>
            <div class="palette-color" style="background: var(--pv-danger, #ef4444)" title="Danger"></div>
            <div class="palette-color" style="background: var(--pv-info, #3b82f6)" title="Info"></div>
            <div class="palette-color" style="background: var(--pv-bg-body, #ffffff)" title="Background"></div>
            <div class="palette-color" style="background: var(--pv-text-primary, #1f2937)" title="Text"></div>
        </div>

        <form action="{{ route('admin-theme-colors-update') }}" method="POST" id="themeBuilderForm">
            @csrf
            @include('alerts.admin.form-both')

            <div class="row">
                <!-- Main Content -->
                <div class="col-lg-8">
                    <!-- Tabs Navigation -->
                    <div class="theme-tabs">
                        <button type="button" class="theme-tab active" data-tab="colors">
                            <i class="fas fa-palette"></i> {{ __('Colors') }}
                        </button>
                        <button type="button" class="theme-tab" data-tab="typography">
                            <i class="fas fa-font"></i> {{ __('Typography') }}
                        </button>
                        <button type="button" class="theme-tab" data-tab="buttons">
                            <i class="fas fa-hand-pointer"></i> {{ __('Buttons') }}
                        </button>
                        <button type="button" class="theme-tab" data-tab="cards">
                            <i class="fas fa-square"></i> {{ __('Cards') }}
                        </button>
                        <button type="button" class="theme-tab" data-tab="forms">
                            <i class="fas fa-edit"></i> {{ __('Forms') }}
                        </button>
                        <button type="button" class="theme-tab" data-tab="header">
                            <i class="fas fa-window-maximize"></i> {{ __('Header') }}
                        </button>
                        <button type="button" class="theme-tab" data-tab="topbar">
                            <i class="fas fa-grip-horizontal"></i> {{ __('Topbar') }}
                        </button>
                        <button type="button" class="theme-tab" data-tab="footer">
                            <i class="fas fa-window-minimize"></i> {{ __('Footer') }}
                        </button>
                        <button type="button" class="theme-tab" data-tab="breadcrumb">
                            <i class="fas fa-chevron-right"></i> {{ __('Breadcrumb') }}
                        </button>
                        <button type="button" class="theme-tab" data-tab="components">
                            <i class="fas fa-puzzle-piece"></i> {{ __('Components') }}
                        </button>
                        <button type="button" class="theme-tab" data-tab="advanced">
                            <i class="fas fa-cog"></i> {{ __('Advanced') }}
                        </button>
                    </div>

                    <!-- ================================ -->
                    <!-- TAB: COLORS -->
                    <!-- ================================ -->
                    <div class="theme-tab-content active" id="tab-colors">
                        <!-- Quick Presets -->
                        <div class="theme-section">
                            <h4 class="theme-section-title"><i class="fas fa-magic"></i> {{ __('Quick Presets') }}</h4>
                            <p class="theme-section-desc">{{ __('Select a preset to quickly apply a complete color scheme') }}</p>
                            <div class="preset-grid">
                                <button type="button" class="preset-btn" data-preset="nissan" style="background: linear-gradient(135deg, #c3002f 0%, #1a1a2e 100%); color: #fff; box-shadow: 0 4px 12px rgba(195,0,47,0.3);">{{ __('Nissan Red') }}</button>
                                <button type="button" class="preset-btn" data-preset="blue" style="background: linear-gradient(135deg, #2563eb 0%, #0f172a 100%); color: #fff; box-shadow: 0 4px 12px rgba(37,99,235,0.3);">{{ __('Royal Blue') }}</button>
                                <button type="button" class="preset-btn" data-preset="green" style="background: linear-gradient(135deg, #059669 0%, #14532d 100%); color: #fff; box-shadow: 0 4px 12px rgba(5,150,105,0.3);">{{ __('Emerald') }}</button>
                                <button type="button" class="preset-btn" data-preset="purple" style="background: linear-gradient(135deg, #7c3aed 0%, #1e1b4b 100%); color: #fff; box-shadow: 0 4px 12px rgba(124,58,237,0.3);">{{ __('Purple') }}</button>
                                <button type="button" class="preset-btn" data-preset="orange" style="background: linear-gradient(135deg, #ea580c 0%, #1c1917 100%); color: #fff; box-shadow: 0 4px 12px rgba(234,88,12,0.3);">{{ __('Sunset') }}</button>
                                <button type="button" class="preset-btn" data-preset="teal" style="background: linear-gradient(135deg, #0d9488 0%, #134e4a 100%); color: #fff; box-shadow: 0 4px 12px rgba(13,148,136,0.3);">{{ __('Teal') }}</button>
                                <button type="button" class="preset-btn" data-preset="gold" style="background: linear-gradient(135deg, #b8860b 0%, #1a1a2e 100%); color: #fff; box-shadow: 0 4px 12px rgba(184,134,11,0.3);">{{ __('Gold Luxury') }}</button>
                                <button type="button" class="preset-btn" data-preset="rose" style="background: linear-gradient(135deg, #e11d48 0%, #18181b 100%); color: #fff; box-shadow: 0 4px 12px rgba(225,29,72,0.3);">{{ __('Rose') }}</button>
                                <button type="button" class="preset-btn" data-preset="ocean" style="background: linear-gradient(135deg, #0891b2 0%, #164e63 100%); color: #fff; box-shadow: 0 4px 12px rgba(8,145,178,0.3);">{{ __('Ocean Blue') }}</button>
                                <button type="button" class="preset-btn" data-preset="forest" style="background: linear-gradient(135deg, #16a34a 0%, #14532d 100%); color: #fff; box-shadow: 0 4px 12px rgba(22,163,74,0.3);">{{ __('Forest Green') }}</button>
                                <button type="button" class="preset-btn" data-preset="midnight" style="background: linear-gradient(135deg, #4f46e5 0%, #1e1b4b 100%); color: #fff; box-shadow: 0 4px 12px rgba(79,70,229,0.3);">{{ __('Midnight') }}</button>
                                <button type="button" class="preset-btn" data-preset="crimson" style="background: linear-gradient(135deg, #dc2626 0%, #7f1d1d 100%); color: #fff; box-shadow: 0 4px 12px rgba(220,38,38,0.3);">{{ __('Crimson') }}</button>
                                <button type="button" class="preset-btn" data-preset="saudi" style="background: linear-gradient(135deg, #006c35 0%, #d4af37 50%, #1a1510 100%); color: #fff; box-shadow: 0 4px 12px rgba(0,108,53,0.4);">{{ __('Saudi Heritage') }}</button>
                            </div>
                        </div>

                        <!-- Primary Colors -->
                        <div class="theme-section">
                            <h4 class="theme-section-title"><i class="fas fa-star"></i> {{ __('Primary Colors') }}</h4>
                            <p class="theme-section-desc">{{ __('Main brand color used for buttons, links, and accents') }}</p>
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="color-input-wrapper">
                                        <label>{{ __('Primary') }} <span class="color-hint">--theme-primary</span></label>
                                        <div class="color-input-group">
                                            <input type="text" name="theme_primary" id="theme_primary" class="color-field" value="{{ $gs->theme_primary ?? '#c3002f' }}">
                                            <input type="color" class="native-color-picker" value="{{ $gs->theme_primary ?? '#c3002f' }}" data-target="theme_primary">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="color-input-wrapper">
                                        <label>{{ __('Hover') }} <span class="color-hint">--theme-primary-hover</span></label>
                                        <div class="color-input-group">
                                            <input type="text" name="theme_primary_hover" id="theme_primary_hover" class="color-field" value="{{ $gs->theme_primary_hover ?? '#a00025' }}">
                                            <input type="color" class="native-color-picker" value="{{ $gs->theme_primary_hover ?? '#a00025' }}" data-target="theme_primary_hover">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="color-input-wrapper">
                                        <label>{{ __('Dark') }} <span class="color-hint">--theme-primary-dark</span></label>
                                        <div class="color-input-group">
                                            <input type="text" name="theme_primary_dark" id="theme_primary_dark" class="color-field" value="{{ $gs->theme_primary_dark ?? '#8a0020' }}">
                                            <input type="color" class="native-color-picker" value="{{ $gs->theme_primary_dark ?? '#8a0020' }}" data-target="theme_primary_dark">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="color-input-wrapper">
                                        <label>{{ __('Light') }} <span class="color-hint">--theme-primary-light</span></label>
                                        <div class="color-input-group">
                                            <input type="text" name="theme_primary_light" id="theme_primary_light" class="color-field" value="{{ $gs->theme_primary_light ?? '#fef2f4' }}">
                                            <input type="color" class="native-color-picker" value="{{ $gs->theme_primary_light ?? '#fef2f4' }}" data-target="theme_primary_light">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Secondary Colors -->
                        <div class="theme-section">
                            <h4 class="theme-section-title"><i class="fas fa-adjust"></i> {{ __('Secondary Colors') }}</h4>
                            <p class="theme-section-desc">{{ __('Used for dark sections, secondary buttons, and text') }}</p>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="color-input-wrapper">
                                        <label>{{ __('Secondary') }}</label>
                                        <div class="color-input-group">
                                            <input type="text" name="theme_secondary" id="theme_secondary" class="color-field" value="{{ $gs->theme_secondary ?? '#1f0300' }}">
                                            <span class="color-preview cp"><i></i></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="color-input-wrapper">
                                        <label>{{ __('Hover') }}</label>
                                        <div class="color-input-group">
                                            <input type="text" name="theme_secondary_hover" id="theme_secondary_hover" class="color-field" value="{{ $gs->theme_secondary_hover ?? '#351c1a' }}">
                                            <span class="color-preview cp"><i></i></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="color-input-wrapper">
                                        <label>{{ __('Light') }}</label>
                                        <div class="color-input-group">
                                            <input type="text" name="theme_secondary_light" id="theme_secondary_light" class="color-field" value="{{ $gs->theme_secondary_light ?? '#4c3533' }}">
                                            <span class="color-preview cp"><i></i></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Text Colors -->
                        <div class="theme-section">
                            <h4 class="theme-section-title"><i class="fas fa-font"></i> {{ __('Text Colors') }}</h4>
                            <p class="theme-section-desc">{{ __('Colors for headings, paragraphs, and labels') }}</p>
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="color-input-wrapper">
                                        <label>{{ __('Primary Text') }}</label>
                                        <div class="color-input-group">
                                            <input type="text" name="theme_text_primary" id="theme_text_primary" class="color-field" value="{{ $gs->theme_text_primary ?? '#1f0300' }}">
                                            <span class="color-preview cp"><i></i></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="color-input-wrapper">
                                        <label>{{ __('Secondary Text') }}</label>
                                        <div class="color-input-group">
                                            <input type="text" name="theme_text_secondary" id="theme_text_secondary" class="color-field" value="{{ $gs->theme_text_secondary ?? '#4c3533' }}">
                                            <span class="color-preview cp"><i></i></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="color-input-wrapper">
                                        <label>{{ __('Muted') }}</label>
                                        <div class="color-input-group">
                                            <input type="text" name="theme_text_muted" id="theme_text_muted" class="color-field" value="{{ $gs->theme_text_muted ?? '#796866' }}">
                                            <span class="color-preview cp"><i></i></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="color-input-wrapper">
                                        <label>{{ __('Light') }}</label>
                                        <div class="color-input-group">
                                            <input type="text" name="theme_text_light" id="theme_text_light" class="color-field" value="{{ $gs->theme_text_light ?? '#9a8e8c' }}">
                                            <span class="color-preview cp"><i></i></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Background Colors -->
                        <div class="theme-section">
                            <h4 class="theme-section-title"><i class="fas fa-fill-drip"></i> {{ __('Background Colors') }}</h4>
                            <p class="theme-section-desc">{{ __('Colors for page backgrounds, cards, and sections') }}</p>
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="color-input-wrapper">
                                        <label>{{ __('Body') }}</label>
                                        <div class="color-input-group">
                                            <input type="text" name="theme_bg_body" id="theme_bg_body" class="color-field" value="{{ $gs->theme_bg_body ?? '#ffffff' }}">
                                            <span class="color-preview cp"><i></i></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="color-input-wrapper">
                                        <label>{{ __('Light') }}</label>
                                        <div class="color-input-group">
                                            <input type="text" name="theme_bg_light" id="theme_bg_light" class="color-field" value="{{ $gs->theme_bg_light ?? '#f8f7f7' }}">
                                            <span class="color-preview cp"><i></i></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="color-input-wrapper">
                                        <label>{{ __('Gray') }}</label>
                                        <div class="color-input-group">
                                            <input type="text" name="theme_bg_gray" id="theme_bg_gray" class="color-field" value="{{ $gs->theme_bg_gray ?? '#e9e6e6' }}">
                                            <span class="color-preview cp"><i></i></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="color-input-wrapper">
                                        <label>{{ __('Dark') }}</label>
                                        <div class="color-input-group">
                                            <input type="text" name="theme_bg_dark" id="theme_bg_dark" class="color-field" value="{{ $gs->theme_bg_dark ?? '#030712' }}">
                                            <span class="color-preview cp"><i></i></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Status Colors -->
                        <div class="theme-section">
                            <h4 class="theme-section-title"><i class="fas fa-check-circle"></i> {{ __('Status Colors') }}</h4>
                            <p class="theme-section-desc">{{ __('Colors for success, warning, danger, and info states') }}</p>
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="color-input-wrapper">
                                        <label><i class="fas fa-check text-success"></i> {{ __('Success') }}</label>
                                        <div class="color-input-group">
                                            <input type="text" name="theme_success" id="theme_success" class="color-field" value="{{ $gs->theme_success ?? '#27be69' }}">
                                            <span class="color-preview cp"><i></i></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="color-input-wrapper">
                                        <label><i class="fas fa-exclamation-triangle text-warning"></i> {{ __('Warning') }}</label>
                                        <div class="color-input-group">
                                            <input type="text" name="theme_warning" id="theme_warning" class="color-field" value="{{ $gs->theme_warning ?? '#fac03c' }}">
                                            <span class="color-preview cp"><i></i></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="color-input-wrapper">
                                        <label><i class="fas fa-times-circle text-danger"></i> {{ __('Danger') }}</label>
                                        <div class="color-input-group">
                                            <input type="text" name="theme_danger" id="theme_danger" class="color-field" value="{{ $gs->theme_danger ?? '#f2415a' }}">
                                            <span class="color-preview cp"><i></i></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="color-input-wrapper">
                                        <label><i class="fas fa-info-circle text-info"></i> {{ __('Info') }}</label>
                                        <div class="color-input-group">
                                            <input type="text" name="theme_info" id="theme_info" class="color-field" value="{{ $gs->theme_info ?? '#0ea5e9' }}">
                                            <span class="color-preview cp"><i></i></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Border Colors -->
                        <div class="theme-section">
                            <h4 class="theme-section-title"><i class="fas fa-border-style"></i> {{ __('Border Colors') }}</h4>
                            <p class="theme-section-desc">{{ __('Colors for borders and dividers') }}</p>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="color-input-wrapper">
                                        <label>{{ __('Default') }}</label>
                                        <div class="color-input-group">
                                            <input type="text" name="theme_border" id="theme_border" class="color-field" value="{{ $gs->theme_border ?? '#d9d4d4' }}">
                                            <span class="color-preview cp"><i></i></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="color-input-wrapper">
                                        <label>{{ __('Light') }}</label>
                                        <div class="color-input-group">
                                            <input type="text" name="theme_border_light" id="theme_border_light" class="color-field" value="{{ $gs->theme_border_light ?? '#e9e6e6' }}">
                                            <span class="color-preview cp"><i></i></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="color-input-wrapper">
                                        <label>{{ __('Dark') }}</label>
                                        <div class="color-input-group">
                                            <input type="text" name="theme_border_dark" id="theme_border_dark" class="color-field" value="{{ $gs->theme_border_dark ?? '#c7c0bf' }}">
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
                            <h4 class="theme-section-title"><i class="fas fa-font"></i> {{ __('Font Families') }}</h4>
                            <p class="theme-section-desc">{{ __('Choose fonts for your theme') }}</p>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="text-input-wrapper">
                                        <label>{{ __('Primary Font') }}</label>
                                        <select name="theme_font_primary" id="theme_font_primary">
                                            <option value="Poppins" {{ ($gs->theme_font_primary ?? 'Poppins') == 'Poppins' ? 'selected' : '' }}>Poppins</option>
                                            <option value="Inter" {{ ($gs->theme_font_primary ?? '') == 'Inter' ? 'selected' : '' }}>Inter</option>
                                            <option value="Roboto" {{ ($gs->theme_font_primary ?? '') == 'Roboto' ? 'selected' : '' }}>Roboto</option>
                                            <option value="Open Sans" {{ ($gs->theme_font_primary ?? '') == 'Open Sans' ? 'selected' : '' }}>Open Sans</option>
                                            <option value="Lato" {{ ($gs->theme_font_primary ?? '') == 'Lato' ? 'selected' : '' }}>Lato</option>
                                            <option value="Montserrat" {{ ($gs->theme_font_primary ?? '') == 'Montserrat' ? 'selected' : '' }}>Montserrat</option>
                                            <option value="Cairo" {{ ($gs->theme_font_primary ?? '') == 'Cairo' ? 'selected' : '' }}>Cairo (Arabic)</option>
                                            <option value="Tajawal" {{ ($gs->theme_font_primary ?? '') == 'Tajawal' ? 'selected' : '' }}>Tajawal (Arabic)</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="text-input-wrapper">
                                        <label>{{ __('Heading Font') }}</label>
                                        <select name="theme_font_heading" id="theme_font_heading">
                                            <option value="Saira" {{ ($gs->theme_font_heading ?? 'Saira') == 'Saira' ? 'selected' : '' }}>Saira</option>
                                            <option value="Poppins" {{ ($gs->theme_font_heading ?? '') == 'Poppins' ? 'selected' : '' }}>Poppins</option>
                                            <option value="Montserrat" {{ ($gs->theme_font_heading ?? '') == 'Montserrat' ? 'selected' : '' }}>Montserrat</option>
                                            <option value="Playfair Display" {{ ($gs->theme_font_heading ?? '') == 'Playfair Display' ? 'selected' : '' }}>Playfair Display</option>
                                            <option value="Cairo" {{ ($gs->theme_font_heading ?? '') == 'Cairo' ? 'selected' : '' }}>Cairo (Arabic)</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="theme-section">
                            <h4 class="theme-section-title"><i class="fas fa-text-height"></i> {{ __('Font Sizes') }}</h4>
                            <p class="theme-section-desc">{{ __('Set base font sizes') }}</p>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="text-input-wrapper">
                                        <label>{{ __('Base Size') }}</label>
                                        <input type="text" name="theme_font_size_base" value="{{ $gs->theme_font_size_base ?? '16px' }}">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="text-input-wrapper">
                                        <label>{{ __('Small Size') }}</label>
                                        <input type="text" name="theme_font_size_sm" value="{{ $gs->theme_font_size_sm ?? '14px' }}">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="text-input-wrapper">
                                        <label>{{ __('Large Size') }}</label>
                                        <input type="text" name="theme_font_size_lg" value="{{ $gs->theme_font_size_lg ?? '18px' }}">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="theme-section">
                            <h4 class="theme-section-title"><i class="fas fa-ruler-vertical"></i> {{ __('Border Radius') }}</h4>
                            <p class="theme-section-desc">{{ __('Control the roundness of corners') }}</p>
                            <div class="row">
                                <div class="col-md-2">
                                    <div class="text-input-wrapper">
                                        <label>{{ __('XS') }}</label>
                                        <input type="text" name="theme_radius_xs" value="{{ $gs->theme_radius_xs ?? '3px' }}">
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="text-input-wrapper">
                                        <label>{{ __('SM') }}</label>
                                        <input type="text" name="theme_radius_sm" value="{{ $gs->theme_radius_sm ?? '4px' }}">
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="text-input-wrapper">
                                        <label>{{ __('Default') }}</label>
                                        <input type="text" name="theme_radius" value="{{ $gs->theme_radius ?? '8px' }}">
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="text-input-wrapper">
                                        <label>{{ __('LG') }}</label>
                                        <input type="text" name="theme_radius_lg" value="{{ $gs->theme_radius_lg ?? '12px' }}">
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="text-input-wrapper">
                                        <label>{{ __('XL') }}</label>
                                        <input type="text" name="theme_radius_xl" value="{{ $gs->theme_radius_xl ?? '16px' }}">
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="text-input-wrapper">
                                        <label>{{ __('Pill') }}</label>
                                        <input type="text" name="theme_radius_pill" value="{{ $gs->theme_radius_pill ?? '50px' }}">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="theme-section">
                            <h4 class="theme-section-title"><i class="fas fa-layer-group"></i> {{ __('Shadows') }}</h4>
                            <p class="theme-section-desc">{{ __('Box shadow presets for depth effects') }}</p>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="text-input-wrapper">
                                        <label>{{ __('Small Shadow') }}</label>
                                        <input type="text" name="theme_shadow_sm" value="{{ $gs->theme_shadow_sm ?? '0 1px 3px rgba(0,0,0,0.06)' }}">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="text-input-wrapper">
                                        <label>{{ __('Default Shadow') }}</label>
                                        <input type="text" name="theme_shadow" value="{{ $gs->theme_shadow ?? '0 2px 8px rgba(0,0,0,0.1)' }}">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="text-input-wrapper">
                                        <label>{{ __('Large Shadow') }}</label>
                                        <input type="text" name="theme_shadow_lg" value="{{ $gs->theme_shadow_lg ?? '0 4px 16px rgba(0,0,0,0.15)' }}">
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
                            <h4 class="theme-section-title"><i class="fas fa-hand-pointer"></i> {{ __('Button Styles') }}</h4>
                            <p class="theme-section-desc">{{ __('Customize button appearance') }}</p>
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="text-input-wrapper">
                                        <label>{{ __('Padding X') }}</label>
                                        <input type="text" name="theme_btn_padding_x" value="{{ $gs->theme_btn_padding_x ?? '24px' }}">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="text-input-wrapper">
                                        <label>{{ __('Padding Y') }}</label>
                                        <input type="text" name="theme_btn_padding_y" value="{{ $gs->theme_btn_padding_y ?? '12px' }}">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="text-input-wrapper">
                                        <label>{{ __('Font Size') }}</label>
                                        <input type="text" name="theme_btn_font_size" value="{{ $gs->theme_btn_font_size ?? '14px' }}">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="text-input-wrapper">
                                        <label>{{ __('Font Weight') }}</label>
                                        <select name="theme_btn_font_weight">
                                            <option value="400" {{ ($gs->theme_btn_font_weight ?? '600') == '400' ? 'selected' : '' }}>Normal (400)</option>
                                            <option value="500" {{ ($gs->theme_btn_font_weight ?? '600') == '500' ? 'selected' : '' }}>Medium (500)</option>
                                            <option value="600" {{ ($gs->theme_btn_font_weight ?? '600') == '600' ? 'selected' : '' }}>Semibold (600)</option>
                                            <option value="700" {{ ($gs->theme_btn_font_weight ?? '600') == '700' ? 'selected' : '' }}>Bold (700)</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="row mt-3">
                                <div class="col-md-6">
                                    <div class="text-input-wrapper">
                                        <label>{{ __('Border Radius') }}</label>
                                        <input type="text" name="theme_btn_radius" value="{{ $gs->theme_btn_radius ?? '8px' }}">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="text-input-wrapper">
                                        <label>{{ __('Box Shadow') }}</label>
                                        <input type="text" name="theme_btn_shadow" value="{{ $gs->theme_btn_shadow ?? 'none' }}">
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
                            <h4 class="theme-section-title"><i class="fas fa-square"></i> {{ __('Card Styles') }}</h4>
                            <p class="theme-section-desc">{{ __('Customize card appearance') }}</p>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="color-input-wrapper">
                                        <label>{{ __('Background') }}</label>
                                        <div class="color-input-group">
                                            <input type="text" name="theme_card_bg" class="color-field" value="{{ $gs->theme_card_bg ?? '#ffffff' }}">
                                            <span class="color-preview cp"><i></i></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="color-input-wrapper">
                                        <label>{{ __('Border Color') }}</label>
                                        <div class="color-input-group">
                                            <input type="text" name="theme_card_border" class="color-field" value="{{ $gs->theme_card_border ?? '#e9e6e6' }}">
                                            <span class="color-preview cp"><i></i></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="text-input-wrapper">
                                        <label>{{ __('Border Radius') }}</label>
                                        <input type="text" name="theme_card_radius" value="{{ $gs->theme_card_radius ?? '12px' }}">
                                    </div>
                                </div>
                            </div>
                            <div class="row mt-3">
                                <div class="col-md-4">
                                    <div class="text-input-wrapper">
                                        <label>{{ __('Shadow') }}</label>
                                        <input type="text" name="theme_card_shadow" value="{{ $gs->theme_card_shadow ?? '0 2px 8px rgba(0,0,0,0.08)' }}">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="text-input-wrapper">
                                        <label>{{ __('Hover Shadow') }}</label>
                                        <input type="text" name="theme_card_hover_shadow" value="{{ $gs->theme_card_hover_shadow ?? '0 4px 16px rgba(0,0,0,0.12)' }}">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="text-input-wrapper">
                                        <label>{{ __('Padding') }}</label>
                                        <input type="text" name="theme_card_padding" value="{{ $gs->theme_card_padding ?? '20px' }}">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Product Cards -->
                        <div class="theme-section">
                            <h4 class="theme-section-title"><i class="fas fa-box-open"></i> {{ __('Product Cards') }}</h4>
                            <p class="theme-section-desc">{{ __('Specific styles for product cards') }}</p>
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="text-input-wrapper">
                                        <label>{{ __('Title Size') }}</label>
                                        <input type="text" name="theme_product_title_size" value="{{ $gs->theme_product_title_size ?? '14px' }}">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="text-input-wrapper">
                                        <label>{{ __('Title Weight') }}</label>
                                        <select name="theme_product_title_weight">
                                            <option value="400" {{ ($gs->theme_product_title_weight ?? '500') == '400' ? 'selected' : '' }}>Normal (400)</option>
                                            <option value="500" {{ ($gs->theme_product_title_weight ?? '500') == '500' ? 'selected' : '' }}>Medium (500)</option>
                                            <option value="600" {{ ($gs->theme_product_title_weight ?? '500') == '600' ? 'selected' : '' }}>Semibold (600)</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="text-input-wrapper">
                                        <label>{{ __('Price Size') }}</label>
                                        <input type="text" name="theme_product_price_size" value="{{ $gs->theme_product_price_size ?? '16px' }}">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="text-input-wrapper">
                                        <label>{{ __('Hover Scale') }}</label>
                                        <input type="text" name="theme_product_hover_scale" value="{{ $gs->theme_product_hover_scale ?? '1.02' }}">
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
                            <h4 class="theme-section-title"><i class="fas fa-edit"></i> {{ __('Input Fields') }}</h4>
                            <p class="theme-section-desc">{{ __('Customize form input appearance') }}</p>
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="text-input-wrapper">
                                        <label>{{ __('Height') }}</label>
                                        <input type="text" name="theme_input_height" value="{{ $gs->theme_input_height ?? '48px' }}">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="color-input-wrapper">
                                        <label>{{ __('Background') }}</label>
                                        <div class="color-input-group">
                                            <input type="text" name="theme_input_bg" class="color-field" value="{{ $gs->theme_input_bg ?? '#ffffff' }}">
                                            <span class="color-preview cp"><i></i></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="color-input-wrapper">
                                        <label>{{ __('Border Color') }}</label>
                                        <div class="color-input-group">
                                            <input type="text" name="theme_input_border" class="color-field" value="{{ $gs->theme_input_border ?? '#d9d4d4' }}">
                                            <span class="color-preview cp"><i></i></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="text-input-wrapper">
                                        <label>{{ __('Border Radius') }}</label>
                                        <input type="text" name="theme_input_radius" value="{{ $gs->theme_input_radius ?? '8px' }}">
                                    </div>
                                </div>
                            </div>
                            <div class="row mt-3">
                                <div class="col-md-4">
                                    <div class="color-input-wrapper">
                                        <label>{{ __('Focus Border') }}</label>
                                        <div class="color-input-group">
                                            <input type="text" name="theme_input_focus_border" class="color-field" value="{{ $gs->theme_input_focus_border ?? '#c3002f' }}">
                                            <span class="color-preview cp"><i></i></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="text-input-wrapper">
                                        <label>{{ __('Focus Shadow') }}</label>
                                        <input type="text" name="theme_input_focus_shadow" value="{{ $gs->theme_input_focus_shadow ?? '0 0 0 3px rgba(195,0,47,0.1)' }}">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="color-input-wrapper">
                                        <label>{{ __('Placeholder Color') }}</label>
                                        <div class="color-input-group">
                                            <input type="text" name="theme_input_placeholder" class="color-field" value="{{ $gs->theme_input_placeholder ?? '#9a8e8c' }}">
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
                            <h4 class="theme-section-title"><i class="fas fa-window-maximize"></i> {{ __('Header Styles') }}</h4>
                            <p class="theme-section-desc">{{ __('Customize header appearance') }}</p>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="color-input-wrapper">
                                        <label>{{ __('Background') }}</label>
                                        <div class="color-input-group">
                                            <input type="text" name="theme_header_bg" class="color-field" value="{{ $gs->theme_header_bg ?? '#ffffff' }}">
                                            <span class="color-preview cp"><i></i></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="text-input-wrapper">
                                        <label>{{ __('Height') }}</label>
                                        <input type="text" name="theme_header_height" value="{{ $gs->theme_header_height ?? '80px' }}">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="text-input-wrapper">
                                        <label>{{ __('Shadow') }}</label>
                                        <input type="text" name="theme_header_shadow" value="{{ $gs->theme_header_shadow ?? '0 2px 10px rgba(0,0,0,0.1)' }}">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="theme-section">
                            <h4 class="theme-section-title"><i class="fas fa-bars"></i> {{ __('Navigation Links') }}</h4>
                            <p class="theme-section-desc">{{ __('Customize navigation link styles') }}</p>
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="color-input-wrapper">
                                        <label>{{ __('Link Color') }}</label>
                                        <div class="color-input-group">
                                            <input type="text" name="theme_nav_link_color" class="color-field" value="{{ $gs->theme_nav_link_color ?? '#1f0300' }}">
                                            <span class="color-preview cp"><i></i></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="color-input-wrapper">
                                        <label>{{ __('Hover Color') }}</label>
                                        <div class="color-input-group">
                                            <input type="text" name="theme_nav_link_hover" class="color-field" value="{{ $gs->theme_nav_link_hover ?? '#c3002f' }}">
                                            <span class="color-preview cp"><i></i></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="text-input-wrapper">
                                        <label>{{ __('Font Size') }}</label>
                                        <input type="text" name="theme_nav_font_size" value="{{ $gs->theme_nav_font_size ?? '15px' }}">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="text-input-wrapper">
                                        <label>{{ __('Font Weight') }}</label>
                                        <select name="theme_nav_font_weight">
                                            <option value="400" {{ ($gs->theme_nav_font_weight ?? '500') == '400' ? 'selected' : '' }}>Normal (400)</option>
                                            <option value="500" {{ ($gs->theme_nav_font_weight ?? '500') == '500' ? 'selected' : '' }}>Medium (500)</option>
                                            <option value="600" {{ ($gs->theme_nav_font_weight ?? '500') == '600' ? 'selected' : '' }}>Semibold (600)</option>
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
                            <h4 class="theme-section-title"><i class="fas fa-window-minimize"></i> {{ __('Footer Styles') }}</h4>
                            <p class="theme-section-desc">{{ __('Customize footer appearance') }}</p>
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="color-input-wrapper">
                                        <label>{{ __('Background') }}</label>
                                        <div class="color-input-group">
                                            <input type="text" name="theme_footer_bg" class="color-field" value="{{ $gs->theme_footer_bg ?? '#030712' }}">
                                            <span class="color-preview cp"><i></i></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="color-input-wrapper">
                                        <label>{{ __('Text Color') }}</label>
                                        <div class="color-input-group">
                                            <input type="text" name="theme_footer_text" class="color-field" value="{{ $gs->theme_footer_text ?? '#ffffff' }}">
                                            <span class="color-preview cp"><i></i></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="color-input-wrapper">
                                        <label>{{ __('Muted Text') }}</label>
                                        <div class="color-input-group">
                                            <input type="text" name="theme_footer_text_muted" class="color-field" value="{{ $gs->theme_footer_text_muted ?? '#d9d4d4' }}">
                                            <span class="color-preview cp"><i></i></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="color-input-wrapper">
                                        <label>{{ __('Link Hover') }}</label>
                                        <div class="color-input-group">
                                            <input type="text" name="theme_footer_link_hover" class="color-field" value="{{ $gs->theme_footer_link_hover ?? '#c3002f' }}">
                                            <span class="color-preview cp"><i></i></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row mt-3">
                                <div class="col-md-4">
                                    <div class="text-input-wrapper">
                                        <label>{{ __('Padding') }}</label>
                                        <input type="text" name="theme_footer_padding" value="{{ $gs->theme_footer_padding ?? '60px' }}">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="color-input-wrapper">
                                        <label>{{ __('Link Color') }}</label>
                                        <div class="color-input-group">
                                            <input type="text" name="theme_footer_link" class="color-field" value="{{ $gs->theme_footer_link ?? '#ffffff' }}">
                                            <span class="color-preview cp"><i></i></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="color-input-wrapper">
                                        <label>{{ __('Border Color') }}</label>
                                        <div class="color-input-group">
                                            <input type="text" name="theme_footer_border" class="color-field" value="{{ $gs->theme_footer_border ?? '#374151' }}">
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
                            <h4 class="theme-section-title"><i class="fas fa-tag"></i> {{ __('Badges') }}</h4>
                            <p class="theme-section-desc">{{ __('Customize badge appearance') }}</p>
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="text-input-wrapper">
                                        <label>{{ __('Border Radius') }}</label>
                                        <input type="text" name="theme_badge_radius" value="{{ $gs->theme_badge_radius ?? '20px' }}">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="text-input-wrapper">
                                        <label>{{ __('Padding') }}</label>
                                        <input type="text" name="theme_badge_padding" value="{{ $gs->theme_badge_padding ?? '4px 12px' }}">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="text-input-wrapper">
                                        <label>{{ __('Font Size') }}</label>
                                        <input type="text" name="theme_badge_font_size" value="{{ $gs->theme_badge_font_size ?? '12px' }}">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="text-input-wrapper">
                                        <label>{{ __('Font Weight') }}</label>
                                        <select name="theme_badge_font_weight">
                                            <option value="500" {{ ($gs->theme_badge_font_weight ?? '600') == '500' ? 'selected' : '' }}>Medium (500)</option>
                                            <option value="600" {{ ($gs->theme_badge_font_weight ?? '600') == '600' ? 'selected' : '' }}>Semibold (600)</option>
                                            <option value="700" {{ ($gs->theme_badge_font_weight ?? '600') == '700' ? 'selected' : '' }}>Bold (700)</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Scrollbar -->
                        <div class="theme-section">
                            <h4 class="theme-section-title"><i class="fas fa-arrows-alt-v"></i> {{ __('Scrollbar') }}</h4>
                            <p class="theme-section-desc">{{ __('Customize scrollbar colors') }}</p>
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="text-input-wrapper">
                                        <label>{{ __('Width') }}</label>
                                        <input type="text" name="theme_scrollbar_width" value="{{ $gs->theme_scrollbar_width ?? '10px' }}">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="color-input-wrapper">
                                        <label>{{ __('Track Color') }}</label>
                                        <div class="color-input-group">
                                            <input type="text" name="theme_scrollbar_track" class="color-field" value="{{ $gs->theme_scrollbar_track ?? '#f1f1f1' }}">
                                            <span class="color-preview cp"><i></i></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="color-input-wrapper">
                                        <label>{{ __('Thumb Color') }}</label>
                                        <div class="color-input-group">
                                            <input type="text" name="theme_scrollbar_thumb" class="color-field" value="{{ $gs->theme_scrollbar_thumb ?? '#c1c1c1' }}">
                                            <span class="color-preview cp"><i></i></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="color-input-wrapper">
                                        <label>{{ __('Thumb Hover') }}</label>
                                        <div class="color-input-group">
                                            <input type="text" name="theme_scrollbar_thumb_hover" class="color-field" value="{{ $gs->theme_scrollbar_thumb_hover ?? '#a1a1a1' }}">
                                            <span class="color-preview cp"><i></i></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Modals -->
                        <div class="theme-section">
                            <h4 class="theme-section-title"><i class="fas fa-window-restore"></i> {{ __('Modals') }}</h4>
                            <p class="theme-section-desc">{{ __('Customize modal dialogs') }}</p>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="color-input-wrapper">
                                        <label>{{ __('Background') }}</label>
                                        <div class="color-input-group">
                                            <input type="text" name="theme_modal_bg" class="color-field" value="{{ $gs->theme_modal_bg ?? '#ffffff' }}">
                                            <span class="color-preview cp"><i></i></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="text-input-wrapper">
                                        <label>{{ __('Border Radius') }}</label>
                                        <input type="text" name="theme_modal_radius" value="{{ $gs->theme_modal_radius ?? '16px' }}">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="text-input-wrapper">
                                        <label>{{ __('Backdrop') }}</label>
                                        <input type="text" name="theme_modal_backdrop" value="{{ $gs->theme_modal_backdrop ?? 'rgba(0,0,0,0.5)' }}">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Tables -->
                        <div class="theme-section">
                            <h4 class="theme-section-title"><i class="fas fa-table"></i> {{ __('Tables') }}</h4>
                            <p class="theme-section-desc">{{ __('Customize table styles') }}</p>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="color-input-wrapper">
                                        <label>{{ __('Header Background') }}</label>
                                        <div class="color-input-group">
                                            <input type="text" name="theme_table_header_bg" class="color-field" value="{{ $gs->theme_table_header_bg ?? '#f8f7f7' }}">
                                            <span class="color-preview cp"><i></i></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="color-input-wrapper">
                                        <label>{{ __('Border Color') }}</label>
                                        <div class="color-input-group">
                                            <input type="text" name="theme_table_border" class="color-field" value="{{ $gs->theme_table_border ?? '#e9e6e6' }}">
                                            <span class="color-preview cp"><i></i></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="color-input-wrapper">
                                        <label>{{ __('Hover Background') }}</label>
                                        <div class="color-input-group">
                                            <input type="text" name="theme_table_hover_bg" class="color-field" value="{{ $gs->theme_table_hover_bg ?? '#f8f7f7' }}">
                                            <span class="color-preview cp"><i></i></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ================================ -->
                    <!-- TAB: TOPBAR -->
                    <!-- ================================ -->
                    <div class="theme-tab-content" id="tab-topbar">
                        <div class="theme-section">
                            <h4 class="theme-section-title"><i class="fas fa-grip-horizontal"></i> {{ __('Topbar Settings') }}</h4>
                            <p class="theme-section-desc">{{ __('Configure the top bar with contact info and quick links') }}</p>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="color-input-wrapper">
                                        <label>{{ __('Background') }} <span class="color-hint">--theme-topbar-bg</span></label>
                                        <div class="color-input-group">
                                            <input type="text" name="theme_topbar_bg" id="theme_topbar_bg" class="color-field" value="{{ $gs->theme_topbar_bg ?? $gs->theme_secondary ?? '#1f2937' }}">
                                            <span class="color-preview cp"><i></i></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="color-input-wrapper">
                                        <label>{{ __('Text Color') }} <span class="color-hint">--theme-topbar-text</span></label>
                                        <div class="color-input-group">
                                            <input type="text" name="theme_topbar_text" id="theme_topbar_text" class="color-field" value="{{ $gs->theme_topbar_text ?? '#ffffff' }}">
                                            <span class="color-preview cp"><i></i></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="color-input-wrapper">
                                        <label>{{ __('Link Hover') }} <span class="color-hint">--theme-topbar-link-hover</span></label>
                                        <div class="color-input-group">
                                            <input type="text" name="theme_topbar_link_hover" id="theme_topbar_link_hover" class="color-field" value="{{ $gs->theme_topbar_link_hover ?? $gs->theme_primary ?? '#c3002f' }}">
                                            <span class="color-preview cp"><i></i></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row mt-3">
                                <div class="col-md-4">
                                    <div class="text-input-wrapper">
                                        <label>{{ __('Height') }}</label>
                                        <input type="text" name="theme_topbar_height" value="{{ $gs->theme_topbar_height ?? '40px' }}">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="text-input-wrapper">
                                        <label>{{ __('Font Size') }}</label>
                                        <input type="text" name="theme_topbar_font_size" value="{{ $gs->theme_topbar_font_size ?? '13px' }}">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="color-input-wrapper">
                                        <label>{{ __('Border Color') }}</label>
                                        <div class="color-input-group">
                                            <input type="text" name="theme_topbar_border" id="theme_topbar_border" class="color-field" value="{{ $gs->theme_topbar_border ?? 'rgba(255,255,255,0.1)' }}">
                                            <span class="color-preview cp"><i></i></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ================================ -->
                    <!-- TAB: BREADCRUMB -->
                    <!-- ================================ -->
                    <div class="theme-tab-content" id="tab-breadcrumb">
                        <div class="theme-section">
                            <h4 class="theme-section-title"><i class="fas fa-chevron-right"></i> {{ __('Breadcrumb Settings') }}</h4>
                            <p class="theme-section-desc">{{ __('Configure the breadcrumb navigation bar') }}</p>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="color-input-wrapper">
                                        <label>{{ __('Background') }} <span class="color-hint">--theme-breadcrumb-bg</span></label>
                                        <div class="color-input-group">
                                            <input type="text" name="theme_breadcrumb_bg" id="theme_breadcrumb_bg" class="color-field" value="{{ $gs->theme_breadcrumb_bg ?? $gs->theme_secondary ?? '#1f2937' }}">
                                            <span class="color-preview cp"><i></i></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="color-input-wrapper">
                                        <label>{{ __('Title Color') }} <span class="color-hint">--theme-breadcrumb-title</span></label>
                                        <div class="color-input-group">
                                            <input type="text" name="theme_breadcrumb_title" id="theme_breadcrumb_title" class="color-field" value="{{ $gs->theme_breadcrumb_title ?? '#ffffff' }}">
                                            <span class="color-preview cp"><i></i></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="color-input-wrapper">
                                        <label>{{ __('Link Color') }} <span class="color-hint">--theme-breadcrumb-link</span></label>
                                        <div class="color-input-group">
                                            <input type="text" name="theme_breadcrumb_link" id="theme_breadcrumb_link" class="color-field" value="{{ $gs->theme_breadcrumb_link ?? 'rgba(255,255,255,0.8)' }}">
                                            <span class="color-preview cp"><i></i></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row mt-3">
                                <div class="col-md-3">
                                    <div class="text-input-wrapper">
                                        <label>{{ __('Height') }}</label>
                                        <input type="text" name="theme_breadcrumb_height" value="{{ $gs->theme_breadcrumb_height ?? '200px' }}">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="text-input-wrapper">
                                        <label>{{ __('Title Size') }}</label>
                                        <input type="text" name="theme_breadcrumb_title_size" value="{{ $gs->theme_breadcrumb_title_size ?? '32px' }}">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="text-input-wrapper">
                                        <label>{{ __('Overlay Opacity') }}</label>
                                        <input type="text" name="theme_breadcrumb_overlay" value="{{ $gs->theme_breadcrumb_overlay ?? '0.6' }}">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="color-input-wrapper">
                                        <label>{{ __('Link Hover') }}</label>
                                        <div class="color-input-group">
                                            <input type="text" name="theme_breadcrumb_link_hover" id="theme_breadcrumb_link_hover" class="color-field" value="{{ $gs->theme_breadcrumb_link_hover ?? '#ffffff' }}">
                                            <span class="color-preview cp"><i></i></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ================================ -->
                    <!-- TAB: ADVANCED -->
                    <!-- ================================ -->
                    <div class="theme-tab-content" id="tab-advanced">
                        <!-- Status Colors Dark Variants -->
                        <div class="theme-section">
                            <h4 class="theme-section-title"><i class="fas fa-adjust"></i> {{ __('Status Colors - Dark Variants') }}</h4>
                            <p class="theme-section-desc">{{ __('Dark variants for status colors (used in hover states and borders)') }}</p>
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="color-input-wrapper">
                                        <label><i class="fas fa-check text-success"></i> {{ __('Success Dark') }}</label>
                                        <div class="color-input-group">
                                            <input type="text" name="theme_success_dark" id="theme_success_dark" class="color-field" value="{{ $gs->theme_success_dark ?? '#059669' }}">
                                            <span class="color-preview cp"><i></i></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="color-input-wrapper">
                                        <label><i class="fas fa-exclamation-triangle text-warning"></i> {{ __('Warning Dark') }}</label>
                                        <div class="color-input-group">
                                            <input type="text" name="theme_warning_dark" id="theme_warning_dark" class="color-field" value="{{ $gs->theme_warning_dark ?? '#b45309' }}">
                                            <span class="color-preview cp"><i></i></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="color-input-wrapper">
                                        <label><i class="fas fa-times-circle text-danger"></i> {{ __('Danger Dark') }}</label>
                                        <div class="color-input-group">
                                            <input type="text" name="theme_danger_dark" id="theme_danger_dark" class="color-field" value="{{ $gs->theme_danger_dark ?? '#dc2626' }}">
                                            <span class="color-preview cp"><i></i></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="color-input-wrapper">
                                        <label><i class="fas fa-info-circle text-info"></i> {{ __('Info Dark') }}</label>
                                        <div class="color-input-group">
                                            <input type="text" name="theme_info_dark" id="theme_info_dark" class="color-field" value="{{ $gs->theme_info_dark ?? '#0284c7' }}">
                                            <span class="color-preview cp"><i></i></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Link Colors -->
                        <div class="theme-section">
                            <h4 class="theme-section-title"><i class="fas fa-link"></i> {{ __('Link Colors') }}</h4>
                            <p class="theme-section-desc">{{ __('Colors for hyperlinks across the site') }}</p>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="color-input-wrapper">
                                        <label>{{ __('Link Color') }}</label>
                                        <div class="color-input-group">
                                            <input type="text" name="theme_link_color" id="theme_link_color" class="color-field" value="{{ $gs->theme_link_color ?? $gs->theme_primary ?? '#c3002f' }}">
                                            <span class="color-preview cp"><i></i></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="color-input-wrapper">
                                        <label>{{ __('Link Hover') }}</label>
                                        <div class="color-input-group">
                                            <input type="text" name="theme_link_hover" id="theme_link_hover" class="color-field" value="{{ $gs->theme_link_hover ?? $gs->theme_primary_hover ?? '#a00025' }}">
                                            <span class="color-preview cp"><i></i></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="color-input-wrapper">
                                        <label>{{ __('Link Visited') }}</label>
                                        <div class="color-input-group">
                                            <input type="text" name="theme_link_visited" id="theme_link_visited" class="color-field" value="{{ $gs->theme_link_visited ?? $gs->theme_primary_dark ?? '#8a0020' }}">
                                            <span class="color-preview cp"><i></i></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Focus & Selection -->
                        <div class="theme-section">
                            <h4 class="theme-section-title"><i class="fas fa-bullseye"></i> {{ __('Focus & Selection') }}</h4>
                            <p class="theme-section-desc">{{ __('Colors for focus rings and text selection') }}</p>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="color-input-wrapper">
                                        <label>{{ __('Focus Ring Color') }}</label>
                                        <div class="color-input-group">
                                            <input type="text" name="theme_focus_ring" id="theme_focus_ring" class="color-field" value="{{ $gs->theme_focus_ring ?? $gs->theme_primary ?? '#c3002f' }}">
                                            <span class="color-preview cp"><i></i></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="color-input-wrapper">
                                        <label>{{ __('Selection Background') }}</label>
                                        <div class="color-input-group">
                                            <input type="text" name="theme_selection_bg" id="theme_selection_bg" class="color-field" value="{{ $gs->theme_selection_bg ?? $gs->theme_primary ?? '#c3002f' }}">
                                            <span class="color-preview cp"><i></i></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="color-input-wrapper">
                                        <label>{{ __('Selection Text') }}</label>
                                        <div class="color-input-group">
                                            <input type="text" name="theme_selection_text" id="theme_selection_text" class="color-field" value="{{ $gs->theme_selection_text ?? '#ffffff' }}">
                                            <span class="color-preview cp"><i></i></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Transition & Animation -->
                        <div class="theme-section">
                            <h4 class="theme-section-title"><i class="fas fa-magic"></i> {{ __('Transitions & Animations') }}</h4>
                            <p class="theme-section-desc">{{ __('Control transition speeds and animation curves') }}</p>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="text-input-wrapper">
                                        <label>{{ __('Transition Speed (Fast)') }}</label>
                                        <input type="text" name="theme_transition_fast" value="{{ $gs->theme_transition_fast ?? '0.15s' }}">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="text-input-wrapper">
                                        <label>{{ __('Transition Speed (Normal)') }}</label>
                                        <input type="text" name="theme_transition_normal" value="{{ $gs->theme_transition_normal ?? '0.25s' }}">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="text-input-wrapper">
                                        <label>{{ __('Transition Speed (Slow)') }}</label>
                                        <input type="text" name="theme_transition_slow" value="{{ $gs->theme_transition_slow ?? '0.4s' }}">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Z-Index Layers -->
                        <div class="theme-section">
                            <h4 class="theme-section-title"><i class="fas fa-layer-group"></i> {{ __('Z-Index Layers') }}</h4>
                            <p class="theme-section-desc">{{ __('Control stacking order of UI elements') }}</p>
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="text-input-wrapper">
                                        <label>{{ __('Dropdown') }}</label>
                                        <input type="text" name="theme_zindex_dropdown" value="{{ $gs->theme_zindex_dropdown ?? '1000' }}">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="text-input-wrapper">
                                        <label>{{ __('Sticky') }}</label>
                                        <input type="text" name="theme_zindex_sticky" value="{{ $gs->theme_zindex_sticky ?? '1020' }}">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="text-input-wrapper">
                                        <label>{{ __('Modal') }}</label>
                                        <input type="text" name="theme_zindex_modal" value="{{ $gs->theme_zindex_modal ?? '1050' }}">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="text-input-wrapper">
                                        <label>{{ __('Tooltip') }}</label>
                                        <input type="text" name="theme_zindex_tooltip" value="{{ $gs->theme_zindex_tooltip ?? '1080' }}">
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
                            <i class="fas fa-eye"></i> {{ __('Live Preview') }}
                        </div>
                        <div class="preview-body">
                            <h6 class="preview-text-primary mb-3">{{ __('Buttons') }}</h6>
                            <div class="mb-3">
                                <button type="button" class="preview-btn preview-btn-primary">{{ __('Primary') }}</button>
                                <button type="button" class="preview-btn preview-btn-secondary">{{ __('Secondary') }}</button>
                            </div>

                            <h6 class="preview-text-primary mb-3">{{ __('Card') }}</h6>
                            <div class="preview-card">
                                <p class="preview-text-primary mb-1"><strong>{{ __('Card Title') }}</strong></p>
                                <p class="preview-text-muted mb-0" style="font-size: 13px;">{{ __('Card description text goes here') }}</p>
                            </div>

                            <h6 class="preview-text-primary mb-3">{{ __('Input') }}</h6>
                            <input type="text" class="preview-input mb-3" placeholder="{{ __('Type something...') }}">

                            <h6 class="preview-text-primary mb-3">{{ __('Badges') }}</h6>
                            <div>
                                <span class="preview-badge preview-badge-primary">{{ __('Primary') }}</span>
                                <span class="preview-badge preview-badge-success">{{ __('Success') }}</span>
                                <span class="preview-badge preview-badge-warning">{{ __('Warning') }}</span>
                                <span class="preview-badge preview-badge-danger">{{ __('Danger') }}</span>
                            </div>
                        </div>
                        <div class="preview-footer">
                            <i class="fas fa-check-circle"></i> {{ __('Footer Preview') }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Save Button -->
            <div class="save-btn-wrapper">
                <button type="submit" class="save-btn">
                    <i class="fas fa-save"></i> {{ __('Save Theme') }}
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

<!-- Import/Export Modal -->
<div class="theme-modal" id="importModal">
    <div class="theme-modal-content">
        <div class="theme-modal-header">
            <h3><i class="fas fa-upload"></i> {{ __('Import Theme') }}</h3>
            <button type="button" class="theme-modal-close" onclick="closeImportModal()">&times;</button>
        </div>
        <div class="theme-modal-body">
            <p>{{ __('Paste your theme JSON configuration below:') }}</p>
            <textarea id="importThemeData" placeholder='{"theme_primary": "#c3002f", ...}'></textarea>
            <div style="margin-top: 15px; text-align: right;">
                <button type="button" class="toolbar-btn" onclick="closeImportModal()">{{ __('Cancel') }}</button>
                <button type="button" class="toolbar-btn toolbar-btn-primary" onclick="applyImportedTheme()">
                    <i class="fas fa-check"></i> {{ __('Apply Theme') }}
                </button>
            </div>
        </div>
    </div>
</div>

<!-- CSS Variables Modal -->
<div class="theme-modal" id="cssModal">
    <div class="theme-modal-content">
        <div class="theme-modal-header">
            <h3><i class="fas fa-code"></i> {{ __('CSS Variables') }}</h3>
            <button type="button" class="theme-modal-close" onclick="closeCSSModal()">&times;</button>
        </div>
        <div class="theme-modal-body">
            <p>{{ __('Copy these CSS variables to use in custom CSS:') }}</p>
            <textarea id="cssVariablesOutput" readonly></textarea>
            <div style="margin-top: 15px; text-align: right;">
                <button type="button" class="toolbar-btn toolbar-btn-primary" onclick="copyCSSToClipboard()">
                    <i class="fas fa-copy"></i> {{ __('Copy to Clipboard') }}
                </button>
            </div>
        </div>
    </div>
</div>

@section('scripts')
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

    // =====================================================================
    // DYNAMIC NATIVE COLOR PICKER INJECTION
    // =====================================================================
    // Convert old color preview spans to native color pickers for better UX
    $('.color-input-group').each(function() {
        var $wrapper = $(this);
        var $input = $wrapper.find('input.color-field');
        var $oldPreview = $wrapper.find('.color-preview.cp');

        if ($input.length && $oldPreview.length) {
            // Get the current color value
            var colorValue = $input.val() || '#000000';
            // Ensure valid hex format for color input
            if (!/^#[0-9A-Fa-f]{6}$/.test(colorValue)) {
                colorValue = '#000000';
            }

            // Create native color picker
            var $nativePicker = $('<input>', {
                type: 'color',
                class: 'native-color-picker',
                value: colorValue,
                'data-target': $input.attr('id')
            });

            // Replace old preview with native picker
            $oldPreview.replaceWith($nativePicker);
        }
    });

    // =====================================================================
    // NATIVE COLOR PICKER SYNCHRONIZATION
    // =====================================================================
    // Sync native color picker with text input
    $(document).on('input change', '.native-color-picker', function() {
        var targetId = $(this).data('target');
        var $targetInput = $('#' + targetId);
        if ($targetInput.length) {
            $targetInput.val($(this).val());
            updatePreview();
            updatePaletteDisplay();
        }
    });

    // Sync text input with native color picker
    $(document).on('input change', '.color-field', function() {
        var $wrapper = $(this).closest('.color-input-group');
        var $nativePicker = $wrapper.find('.native-color-picker');
        var value = $(this).val();

        // Update native picker if valid hex color
        if ($nativePicker.length && /^#[0-9A-Fa-f]{6}$/.test(value)) {
            $nativePicker.val(value);
        }

        updatePreview();
        updatePaletteDisplay();
    });

    // =====================================================================
    // PALETTE DISPLAY UPDATE
    // =====================================================================
    function updatePaletteDisplay() {
        var root = document.documentElement;
        root.style.setProperty('--pv-primary', $('#theme_primary').val() || '#c3002f');
        root.style.setProperty('--pv-primary-hover', $('#theme_primary_hover').val() || '#a00025');
        root.style.setProperty('--pv-secondary', $('#theme_secondary').val() || '#1f2937');
        root.style.setProperty('--pv-success', $('#theme_success').val() || '#10b981');
        root.style.setProperty('--pv-warning', $('#theme_warning').val() || '#f59e0b');
        root.style.setProperty('--pv-danger', $('#theme_danger').val() || '#ef4444');
        root.style.setProperty('--pv-info', $('#theme_info').val() || '#3b82f6');
        root.style.setProperty('--pv-bg-body', $('#theme_bg_body').val() || '#ffffff');
        root.style.setProperty('--pv-text-primary', $('#theme_text_primary').val() || '#1f2937');
    }

    // Initial palette update
    updatePaletteDisplay();

    // =====================================================================
    // COMPLETE THEME PRESETS - ثيمات كاملة تشمل جميع الإعدادات
    // =====================================================================
    // كل ثيم يشمل: الألوان، الخطوط، الأزرار، البطاقات، النماذج، الرأس، التذييل، المكونات

    var presets = {
        nissan: {
            // ===== أحمر نيسان الكلاسيكي - احترافي لقطع الغيار =====
            // الألوان الأساسية
            theme_primary: '#c3002f', theme_primary_hover: '#a00025', theme_primary_dark: '#8a0020', theme_primary_light: '#fef2f4',
            theme_secondary: '#1c1917', theme_secondary_hover: '#292524', theme_secondary_light: '#44403c',
            theme_text_primary: '#1c1917', theme_text_secondary: '#44403c', theme_text_muted: '#78716c', theme_text_light: '#a8a29e',
            theme_bg_body: '#ffffff', theme_bg_light: '#fafaf9', theme_bg_gray: '#fef2f4', theme_bg_dark: '#1c1917',
            theme_success: '#10b981', theme_warning: '#f59e0b', theme_danger: '#ef4444', theme_info: '#3b82f6',
            theme_border: '#fecdd3', theme_border_light: '#fef2f4', theme_border_dark: '#fda4af',
            // الخطوط والأحجام
            theme_font_primary: 'Poppins', theme_font_heading: 'Poppins',
            theme_font_size_base: '15px', theme_font_size_sm: '13px', theme_font_size_lg: '18px',
            theme_radius_xs: '2px', theme_radius_sm: '4px', theme_radius: '8px', theme_radius_lg: '10px', theme_radius_xl: '12px', theme_radius_pill: '50px',
            theme_shadow_sm: '0 1px 2px rgba(195,0,47,0.05)', theme_shadow: '0 2px 8px rgba(195,0,47,0.08)', theme_shadow_lg: '0 4px 16px rgba(195,0,47,0.12)',
            // الأزرار
            theme_btn_padding_x: '20px', theme_btn_padding_y: '10px', theme_btn_font_size: '14px', theme_btn_font_weight: '600',
            theme_btn_radius: '6px', theme_btn_shadow: 'none',
            // البطاقات
            theme_card_bg: '#ffffff', theme_card_border: '#fecdd3', theme_card_radius: '10px',
            theme_card_shadow: '0 1px 4px rgba(195,0,47,0.06)', theme_card_hover_shadow: '0 4px 12px rgba(195,0,47,0.1)', theme_card_padding: '20px',
            theme_product_title_size: '14px', theme_product_title_weight: '500', theme_product_price_size: '16px', theme_product_hover_scale: '1.02',
            // النماذج
            theme_input_height: '44px', theme_input_bg: '#ffffff', theme_input_border: '#fecdd3',
            theme_input_radius: '6px', theme_input_focus_border: '#c3002f', theme_input_focus_shadow: '0 0 0 3px rgba(195,0,47,0.1)', theme_input_placeholder: '#a8a29e',
            // الرأس
            theme_header_bg: '#ffffff', theme_header_height: '70px', theme_header_shadow: '0 1px 3px rgba(195,0,47,0.08)',
            theme_nav_link_color: '#44403c', theme_nav_link_hover: '#c3002f', theme_nav_font_size: '15px', theme_nav_font_weight: '500',
            // التذييل
            theme_footer_bg: '#1c1917', theme_footer_text: '#fafaf9', theme_footer_text_muted: '#a8a29e',
            theme_footer_link_hover: '#fb7185', theme_footer_padding: '50px', theme_footer_link: '#d6d3d1', theme_footer_border: '#44403c',
            // المكونات
            theme_badge_radius: '4px', theme_badge_padding: '4px 10px', theme_badge_font_size: '12px', theme_badge_font_weight: '600',
            theme_scrollbar_width: '8px', theme_scrollbar_track: '#fef2f4', theme_scrollbar_thumb: '#fda4af', theme_scrollbar_thumb_hover: '#fb7185',
            theme_modal_bg: '#ffffff', theme_modal_radius: '12px', theme_modal_backdrop: 'rgba(28,25,23,0.6)',
            theme_table_header_bg: '#fef2f4', theme_table_border: '#fecdd3', theme_table_hover_bg: '#fef2f4'
        },

        blue: {
            // ===== أزرق ملكي - تصميم عصري للتقنية =====
            // الألوان الأساسية
            theme_primary: '#2563eb', theme_primary_hover: '#1d4ed8', theme_primary_dark: '#1e40af', theme_primary_light: '#eff6ff',
            theme_secondary: '#1e293b', theme_secondary_hover: '#334155', theme_secondary_light: '#475569',
            theme_text_primary: '#0f172a', theme_text_secondary: '#334155', theme_text_muted: '#64748b', theme_text_light: '#94a3b8',
            theme_bg_body: '#ffffff', theme_bg_light: '#f8fafc', theme_bg_gray: '#f1f5f9', theme_bg_dark: '#0f172a',
            theme_success: '#10b981', theme_warning: '#f59e0b', theme_danger: '#ef4444', theme_info: '#06b6d4',
            theme_border: '#e2e8f0', theme_border_light: '#f1f5f9', theme_border_dark: '#cbd5e1',
            // الخطوط والأحجام
            theme_font_primary: 'Inter', theme_font_heading: 'Inter',
            theme_font_size_base: '15px', theme_font_size_sm: '13px', theme_font_size_lg: '18px',
            theme_radius_xs: '4px', theme_radius_sm: '6px', theme_radius: '10px', theme_radius_lg: '14px', theme_radius_xl: '18px', theme_radius_pill: '9999px',
            theme_shadow_sm: '0 1px 2px rgba(0,0,0,0.04)', theme_shadow: '0 4px 6px rgba(0,0,0,0.07)', theme_shadow_lg: '0 10px 25px rgba(0,0,0,0.1)',
            // الأزرار
            theme_btn_padding_x: '22px', theme_btn_padding_y: '11px', theme_btn_font_size: '14px', theme_btn_font_weight: '500',
            theme_btn_radius: '8px', theme_btn_shadow: '0 1px 2px rgba(0,0,0,0.05)',
            // البطاقات
            theme_card_bg: '#ffffff', theme_card_border: '#e2e8f0', theme_card_radius: '14px',
            theme_card_shadow: '0 1px 3px rgba(0,0,0,0.05)', theme_card_hover_shadow: '0 8px 20px rgba(0,0,0,0.08)', theme_card_padding: '22px',
            theme_product_title_size: '14px', theme_product_title_weight: '500', theme_product_price_size: '16px', theme_product_hover_scale: '1.03',
            // النماذج
            theme_input_height: '46px', theme_input_bg: '#ffffff', theme_input_border: '#e2e8f0',
            theme_input_radius: '8px', theme_input_focus_border: '#2563eb', theme_input_focus_shadow: '0 0 0 3px rgba(37,99,235,0.12)', theme_input_placeholder: '#94a3b8',
            // الرأس
            theme_header_bg: '#ffffff', theme_header_height: '68px', theme_header_shadow: '0 1px 3px rgba(0,0,0,0.06)',
            theme_nav_link_color: '#334155', theme_nav_link_hover: '#2563eb', theme_nav_font_size: '15px', theme_nav_font_weight: '500',
            // التذييل
            theme_footer_bg: '#0f172a', theme_footer_text: '#f1f5f9', theme_footer_text_muted: '#94a3b8',
            theme_footer_link_hover: '#60a5fa', theme_footer_padding: '55px', theme_footer_link: '#cbd5e1', theme_footer_border: '#334155',
            // المكونات
            theme_badge_radius: '6px', theme_badge_padding: '4px 12px', theme_badge_font_size: '12px', theme_badge_font_weight: '500',
            theme_scrollbar_width: '8px', theme_scrollbar_track: '#f1f5f9', theme_scrollbar_thumb: '#cbd5e1', theme_scrollbar_thumb_hover: '#94a3b8',
            theme_modal_bg: '#ffffff', theme_modal_radius: '16px', theme_modal_backdrop: 'rgba(15,23,42,0.6)',
            theme_table_header_bg: '#f8fafc', theme_table_border: '#e2e8f0', theme_table_hover_bg: '#f8fafc'
        },

        green: {
            // ===== أخضر زمردي - طبيعي وصحي =====
            // الألوان الأساسية
            theme_primary: '#059669', theme_primary_hover: '#047857', theme_primary_dark: '#065f46', theme_primary_light: '#ecfdf5',
            theme_secondary: '#064e3b', theme_secondary_hover: '#065f46', theme_secondary_light: '#047857',
            theme_text_primary: '#14532d', theme_text_secondary: '#166534', theme_text_muted: '#6b7280', theme_text_light: '#9ca3af',
            theme_bg_body: '#ffffff', theme_bg_light: '#f0fdf4', theme_bg_gray: '#ecfdf5', theme_bg_dark: '#064e3b',
            theme_success: '#10b981', theme_warning: '#f59e0b', theme_danger: '#ef4444', theme_info: '#0891b2',
            theme_border: '#a7f3d0', theme_border_light: '#d1fae5', theme_border_dark: '#6ee7b7',
            // الخطوط والأحجام
            theme_font_primary: 'Nunito', theme_font_heading: 'Nunito',
            theme_font_size_base: '15px', theme_font_size_sm: '13px', theme_font_size_lg: '18px',
            theme_radius_xs: '4px', theme_radius_sm: '6px', theme_radius: '12px', theme_radius_lg: '16px', theme_radius_xl: '20px', theme_radius_pill: '9999px',
            theme_shadow_sm: '0 1px 2px rgba(5,150,105,0.06)', theme_shadow: '0 4px 12px rgba(5,150,105,0.08)', theme_shadow_lg: '0 8px 24px rgba(5,150,105,0.12)',
            // الأزرار
            theme_btn_padding_x: '24px', theme_btn_padding_y: '12px', theme_btn_font_size: '14px', theme_btn_font_weight: '600',
            theme_btn_radius: '10px', theme_btn_shadow: 'none',
            // البطاقات
            theme_card_bg: '#ffffff', theme_card_border: '#d1fae5', theme_card_radius: '16px',
            theme_card_shadow: '0 2px 8px rgba(5,150,105,0.06)', theme_card_hover_shadow: '0 8px 24px rgba(5,150,105,0.1)', theme_card_padding: '22px',
            theme_product_title_size: '14px', theme_product_title_weight: '500', theme_product_price_size: '16px', theme_product_hover_scale: '1.02',
            // النماذج
            theme_input_height: '48px', theme_input_bg: '#ffffff', theme_input_border: '#d1fae5',
            theme_input_radius: '10px', theme_input_focus_border: '#059669', theme_input_focus_shadow: '0 0 0 3px rgba(5,150,105,0.15)', theme_input_placeholder: '#9ca3af',
            // الرأس
            theme_header_bg: '#ffffff', theme_header_height: '70px', theme_header_shadow: '0 1px 3px rgba(5,150,105,0.08)',
            theme_nav_link_color: '#374151', theme_nav_link_hover: '#059669', theme_nav_font_size: '15px', theme_nav_font_weight: '500',
            // التذييل
            theme_footer_bg: '#064e3b', theme_footer_text: '#ecfdf5', theme_footer_text_muted: '#a7f3d0',
            theme_footer_link_hover: '#34d399', theme_footer_padding: '55px', theme_footer_link: '#d1fae5', theme_footer_border: '#047857',
            // المكونات
            theme_badge_radius: '8px', theme_badge_padding: '5px 12px', theme_badge_font_size: '12px', theme_badge_font_weight: '600',
            theme_scrollbar_width: '8px', theme_scrollbar_track: '#ecfdf5', theme_scrollbar_thumb: '#a7f3d0', theme_scrollbar_thumb_hover: '#6ee7b7',
            theme_modal_bg: '#ffffff', theme_modal_radius: '18px', theme_modal_backdrop: 'rgba(6,78,59,0.5)',
            theme_table_header_bg: '#ecfdf5', theme_table_border: '#d1fae5', theme_table_hover_bg: '#f0fdf4'
        },

        purple: {
            // ===== بنفسجي أنيق - إبداعي وفاخر =====
            // الألوان الأساسية
            theme_primary: '#7c3aed', theme_primary_hover: '#6d28d9', theme_primary_dark: '#5b21b6', theme_primary_light: '#f5f3ff',
            theme_secondary: '#1e1b4b', theme_secondary_hover: '#312e81', theme_secondary_light: '#3730a3',
            theme_text_primary: '#1e1b4b', theme_text_secondary: '#3730a3', theme_text_muted: '#6b7280', theme_text_light: '#a5b4fc',
            theme_bg_body: '#ffffff', theme_bg_light: '#faf8ff', theme_bg_gray: '#f5f3ff', theme_bg_dark: '#1e1b4b',
            theme_success: '#10b981', theme_warning: '#f59e0b', theme_danger: '#ef4444', theme_info: '#06b6d4',
            theme_border: '#c4b5fd', theme_border_light: '#e9d5ff', theme_border_dark: '#a78bfa',
            // الخطوط والأحجام
            theme_font_primary: 'Poppins', theme_font_heading: 'Playfair Display',
            theme_font_size_base: '15px', theme_font_size_sm: '13px', theme_font_size_lg: '19px',
            theme_radius_xs: '6px', theme_radius_sm: '8px', theme_radius: '14px', theme_radius_lg: '18px', theme_radius_xl: '24px', theme_radius_pill: '9999px',
            theme_shadow_sm: '0 1px 3px rgba(124,58,237,0.08)', theme_shadow: '0 4px 16px rgba(124,58,237,0.1)', theme_shadow_lg: '0 12px 32px rgba(124,58,237,0.15)',
            // الأزرار
            theme_btn_padding_x: '26px', theme_btn_padding_y: '13px', theme_btn_font_size: '14px', theme_btn_font_weight: '500',
            theme_btn_radius: '12px', theme_btn_shadow: '0 2px 6px rgba(124,58,237,0.2)',
            // البطاقات
            theme_card_bg: '#ffffff', theme_card_border: '#e9d5ff', theme_card_radius: '18px',
            theme_card_shadow: '0 2px 12px rgba(124,58,237,0.08)', theme_card_hover_shadow: '0 12px 32px rgba(124,58,237,0.15)', theme_card_padding: '24px',
            theme_product_title_size: '14px', theme_product_title_weight: '500', theme_product_price_size: '17px', theme_product_hover_scale: '1.03',
            // النماذج
            theme_input_height: '50px', theme_input_bg: '#ffffff', theme_input_border: '#e9d5ff',
            theme_input_radius: '12px', theme_input_focus_border: '#7c3aed', theme_input_focus_shadow: '0 0 0 4px rgba(124,58,237,0.12)', theme_input_placeholder: '#a1a1aa',
            // الرأس
            theme_header_bg: '#ffffff', theme_header_height: '72px', theme_header_shadow: '0 2px 8px rgba(124,58,237,0.08)',
            theme_nav_link_color: '#374151', theme_nav_link_hover: '#7c3aed', theme_nav_font_size: '15px', theme_nav_font_weight: '500',
            // التذييل
            theme_footer_bg: '#1e1b4b', theme_footer_text: '#f5f3ff', theme_footer_text_muted: '#c4b5fd',
            theme_footer_link_hover: '#a78bfa', theme_footer_padding: '60px', theme_footer_link: '#e9d5ff', theme_footer_border: '#5b21b6',
            // المكونات
            theme_badge_radius: '10px', theme_badge_padding: '5px 14px', theme_badge_font_size: '12px', theme_badge_font_weight: '500',
            theme_scrollbar_width: '8px', theme_scrollbar_track: '#f5f3ff', theme_scrollbar_thumb: '#d8b4fe', theme_scrollbar_thumb_hover: '#c084fc',
            theme_modal_bg: '#ffffff', theme_modal_radius: '20px', theme_modal_backdrop: 'rgba(30,27,75,0.6)',
            theme_table_header_bg: '#faf8ff', theme_table_border: '#e9d5ff', theme_table_hover_bg: '#f5f3ff'
        },

        orange: {
            // ===== برتقالي حيوي - طاقة ونشاط =====
            // الألوان الأساسية
            theme_primary: '#ea580c', theme_primary_hover: '#c2410c', theme_primary_dark: '#9a3412', theme_primary_light: '#fff7ed',
            theme_secondary: '#431407', theme_secondary_hover: '#7c2d12', theme_secondary_light: '#9a3412',
            theme_text_primary: '#431407', theme_text_secondary: '#7c2d12', theme_text_muted: '#78716c', theme_text_light: '#a8a29e',
            theme_bg_body: '#ffffff', theme_bg_light: '#fff7ed', theme_bg_gray: '#ffedd5', theme_bg_dark: '#431407',
            theme_success: '#10b981', theme_warning: '#f59e0b', theme_danger: '#ef4444', theme_info: '#0284c7',
            theme_border: '#fdba74', theme_border_light: '#fed7aa', theme_border_dark: '#fb923c',
            // الخطوط والأحجام
            theme_font_primary: 'Montserrat', theme_font_heading: 'Montserrat',
            theme_font_size_base: '15px', theme_font_size_sm: '13px', theme_font_size_lg: '18px',
            theme_radius_xs: '3px', theme_radius_sm: '5px', theme_radius: '8px', theme_radius_lg: '12px', theme_radius_xl: '16px', theme_radius_pill: '50px',
            theme_shadow_sm: '0 1px 2px rgba(234,88,12,0.08)', theme_shadow: '0 4px 12px rgba(234,88,12,0.12)', theme_shadow_lg: '0 8px 24px rgba(234,88,12,0.18)',
            // الأزرار
            theme_btn_padding_x: '22px', theme_btn_padding_y: '11px', theme_btn_font_size: '14px', theme_btn_font_weight: '700',
            theme_btn_radius: '6px', theme_btn_shadow: '0 2px 4px rgba(234,88,12,0.25)',
            // البطاقات
            theme_card_bg: '#ffffff', theme_card_border: '#fed7aa', theme_card_radius: '12px',
            theme_card_shadow: '0 2px 8px rgba(234,88,12,0.08)', theme_card_hover_shadow: '0 8px 20px rgba(234,88,12,0.15)', theme_card_padding: '20px',
            theme_product_title_size: '14px', theme_product_title_weight: '600', theme_product_price_size: '16px', theme_product_hover_scale: '1.03',
            // النماذج
            theme_input_height: '46px', theme_input_bg: '#ffffff', theme_input_border: '#fed7aa',
            theme_input_radius: '6px', theme_input_focus_border: '#ea580c', theme_input_focus_shadow: '0 0 0 3px rgba(234,88,12,0.15)', theme_input_placeholder: '#9ca3af',
            // الرأس
            theme_header_bg: '#ffffff', theme_header_height: '68px', theme_header_shadow: '0 2px 6px rgba(234,88,12,0.1)',
            theme_nav_link_color: '#374151', theme_nav_link_hover: '#ea580c', theme_nav_font_size: '15px', theme_nav_font_weight: '600',
            // التذييل
            theme_footer_bg: '#431407', theme_footer_text: '#fff7ed', theme_footer_text_muted: '#fdba74',
            theme_footer_link_hover: '#fb923c', theme_footer_padding: '50px', theme_footer_link: '#fed7aa', theme_footer_border: '#9a3412',
            // المكونات
            theme_badge_radius: '4px', theme_badge_padding: '4px 10px', theme_badge_font_size: '12px', theme_badge_font_weight: '700',
            theme_scrollbar_width: '8px', theme_scrollbar_track: '#ffedd5', theme_scrollbar_thumb: '#fdba74', theme_scrollbar_thumb_hover: '#fb923c',
            theme_modal_bg: '#ffffff', theme_modal_radius: '14px', theme_modal_backdrop: 'rgba(67,20,7,0.6)',
            theme_table_header_bg: '#fff7ed', theme_table_border: '#fed7aa', theme_table_hover_bg: '#ffedd5'
        },

        teal: {
            // ===== تركوازي - هادئ واحترافي =====
            // الألوان الأساسية
            theme_primary: '#0d9488', theme_primary_hover: '#0f766e', theme_primary_dark: '#115e59', theme_primary_light: '#f0fdfa',
            theme_secondary: '#134e4a', theme_secondary_hover: '#115e59', theme_secondary_light: '#0f766e',
            theme_text_primary: '#111827', theme_text_secondary: '#374151', theme_text_muted: '#6b7280', theme_text_light: '#9ca3af',
            theme_bg_body: '#ffffff', theme_bg_light: '#f9fafb', theme_bg_gray: '#f0fdfa', theme_bg_dark: '#134e4a',
            theme_success: '#10b981', theme_warning: '#f59e0b', theme_danger: '#ef4444', theme_info: '#38bdf8',
            theme_border: '#99f6e4', theme_border_light: '#ccfbf1', theme_border_dark: '#5eead4',
            // الخطوط والأحجام
            theme_font_primary: 'Poppins', theme_font_heading: 'Saira',
            theme_font_size_base: '15px', theme_font_size_sm: '14px', theme_font_size_lg: '18px',
            theme_radius_xs: '3px', theme_radius_sm: '4px', theme_radius: '10px', theme_radius_lg: '14px', theme_radius_xl: '18px', theme_radius_pill: '50px',
            theme_shadow_sm: '0 1px 2px rgba(13,148,136,0.05)', theme_shadow: '0 2px 8px rgba(13,148,136,0.08)', theme_shadow_lg: '0 6px 20px rgba(13,148,136,0.12)',
            // الأزرار
            theme_btn_padding_x: '24px', theme_btn_padding_y: '12px', theme_btn_font_size: '14px', theme_btn_font_weight: '500',
            theme_btn_radius: '8px', theme_btn_shadow: 'none',
            // البطاقات
            theme_card_bg: '#ffffff', theme_card_border: '#99f6e4', theme_card_radius: '14px',
            theme_card_shadow: '0 1px 4px rgba(13,148,136,0.06)', theme_card_hover_shadow: '0 6px 18px rgba(13,148,136,0.1)', theme_card_padding: '22px',
            theme_product_title_size: '14px', theme_product_title_weight: '500', theme_product_price_size: '16px', theme_product_hover_scale: '1.02',
            // النماذج
            theme_input_height: '48px', theme_input_bg: '#ffffff', theme_input_border: '#99f6e4',
            theme_input_radius: '8px', theme_input_focus_border: '#0d9488', theme_input_focus_shadow: '0 0 0 3px rgba(13,148,136,0.12)', theme_input_placeholder: '#94a3b8',
            // الرأس
            theme_header_bg: '#ffffff', theme_header_height: '68px', theme_header_shadow: '0 1px 3px rgba(13,148,136,0.06)',
            theme_nav_link_color: '#374151', theme_nav_link_hover: '#0d9488', theme_nav_font_size: '15px', theme_nav_font_weight: '500',
            // التذييل
            theme_footer_bg: '#134e4a', theme_footer_text: '#f0fdfa', theme_footer_text_muted: '#99f6e4',
            theme_footer_link_hover: '#5eead4', theme_footer_padding: '55px', theme_footer_link: '#ccfbf1', theme_footer_border: '#0f766e',
            // المكونات
            theme_badge_radius: '6px', theme_badge_padding: '4px 12px', theme_badge_font_size: '12px', theme_badge_font_weight: '500',
            theme_scrollbar_width: '8px', theme_scrollbar_track: '#f0fdfa', theme_scrollbar_thumb: '#5eead4', theme_scrollbar_thumb_hover: '#2dd4bf',
            theme_modal_bg: '#ffffff', theme_modal_radius: '16px', theme_modal_backdrop: 'rgba(19,78,74,0.5)',
            theme_table_header_bg: '#f0fdfa', theme_table_border: '#99f6e4', theme_table_hover_bg: '#ccfbf1'
        },

        gold: {
            // ===== ذهبي فاخر - رفاهية وأناقة =====
            // الألوان الأساسية
            theme_primary: '#b8860b', theme_primary_hover: '#996f09', theme_primary_dark: '#7a5807', theme_primary_light: '#fef9e7',
            theme_secondary: '#1c1917', theme_secondary_hover: '#292524', theme_secondary_light: '#44403c',
            theme_text_primary: '#422006', theme_text_secondary: '#713f12', theme_text_muted: '#78716c', theme_text_light: '#a8a29e',
            theme_bg_body: '#fffef8', theme_bg_light: '#fef9e7', theme_bg_gray: '#fef3c7', theme_bg_dark: '#1c1917',
            theme_success: '#10b981', theme_warning: '#f59e0b', theme_danger: '#ef4444', theme_info: '#3b82f6',
            theme_border: '#fcd34d', theme_border_light: '#fde68a', theme_border_dark: '#fbbf24',
            // الخطوط والأحجام
            theme_font_primary: 'Lora', theme_font_heading: 'Playfair Display',
            theme_font_size_base: '16px', theme_font_size_sm: '14px', theme_font_size_lg: '20px',
            theme_radius_xs: '4px', theme_radius_sm: '6px', theme_radius: '10px', theme_radius_lg: '14px', theme_radius_xl: '18px', theme_radius_pill: '50px',
            theme_shadow_sm: '0 1px 3px rgba(184,134,11,0.08)', theme_shadow: '0 4px 12px rgba(184,134,11,0.1)', theme_shadow_lg: '0 10px 30px rgba(184,134,11,0.15)',
            // الأزرار
            theme_btn_padding_x: '28px', theme_btn_padding_y: '14px', theme_btn_font_size: '14px', theme_btn_font_weight: '600',
            theme_btn_radius: '8px', theme_btn_shadow: '0 2px 6px rgba(184,134,11,0.2)',
            // البطاقات
            theme_card_bg: '#ffffff', theme_card_border: '#fde68a', theme_card_radius: '14px',
            theme_card_shadow: '0 2px 10px rgba(184,134,11,0.08)', theme_card_hover_shadow: '0 10px 30px rgba(184,134,11,0.15)', theme_card_padding: '26px',
            theme_product_title_size: '15px', theme_product_title_weight: '500', theme_product_price_size: '17px', theme_product_hover_scale: '1.02',
            // النماذج
            theme_input_height: '50px', theme_input_bg: '#ffffff', theme_input_border: '#fde68a',
            theme_input_radius: '8px', theme_input_focus_border: '#b8860b', theme_input_focus_shadow: '0 0 0 3px rgba(184,134,11,0.15)', theme_input_placeholder: '#9ca3af',
            // الرأس
            theme_header_bg: '#fffef8', theme_header_height: '75px', theme_header_shadow: '0 2px 8px rgba(184,134,11,0.08)',
            theme_nav_link_color: '#374151', theme_nav_link_hover: '#b8860b', theme_nav_font_size: '15px', theme_nav_font_weight: '500',
            // التذييل
            theme_footer_bg: '#1a1a2e', theme_footer_text: '#fef9e7', theme_footer_text_muted: '#fde68a',
            theme_footer_link_hover: '#d4a017', theme_footer_padding: '60px', theme_footer_link: '#fef3c7', theme_footer_border: '#7a5807',
            // المكونات
            theme_badge_radius: '6px', theme_badge_padding: '5px 14px', theme_badge_font_size: '12px', theme_badge_font_weight: '600',
            theme_scrollbar_width: '10px', theme_scrollbar_track: '#fef3c7', theme_scrollbar_thumb: '#fbbf24', theme_scrollbar_thumb_hover: '#f59e0b',
            theme_modal_bg: '#ffffff', theme_modal_radius: '16px', theme_modal_backdrop: 'rgba(26,26,46,0.6)',
            theme_table_header_bg: '#fefbf0', theme_table_border: '#fde68a', theme_table_hover_bg: '#fef3c7'
        },

        rose: {
            // ===== وردي أنيق - جمال وأناقة =====
            // الألوان الأساسية
            theme_primary: '#e11d48', theme_primary_hover: '#be123c', theme_primary_dark: '#9f1239', theme_primary_light: '#fff1f2',
            theme_secondary: '#4c0519', theme_secondary_hover: '#881337', theme_secondary_light: '#9f1239',
            theme_text_primary: '#4c0519', theme_text_secondary: '#881337', theme_text_muted: '#78716c', theme_text_light: '#fda4af',
            theme_bg_body: '#ffffff', theme_bg_light: '#fff1f2', theme_bg_gray: '#ffe4e6', theme_bg_dark: '#4c0519',
            theme_success: '#10b981', theme_warning: '#f59e0b', theme_danger: '#ef4444', theme_info: '#06b6d4',
            theme_border: '#fda4af', theme_border_light: '#fecdd3', theme_border_dark: '#fb7185',
            // الخطوط والأحجام
            theme_font_primary: 'Quicksand', theme_font_heading: 'Playfair Display',
            theme_font_size_base: '15px', theme_font_size_sm: '13px', theme_font_size_lg: '18px',
            theme_radius_xs: '6px', theme_radius_sm: '8px', theme_radius: '14px', theme_radius_lg: '18px', theme_radius_xl: '24px', theme_radius_pill: '9999px',
            theme_shadow_sm: '0 1px 2px rgba(225,29,72,0.06)', theme_shadow: '0 4px 12px rgba(225,29,72,0.08)', theme_shadow_lg: '0 10px 28px rgba(225,29,72,0.12)',
            // الأزرار
            theme_btn_padding_x: '24px', theme_btn_padding_y: '12px', theme_btn_font_size: '14px', theme_btn_font_weight: '500',
            theme_btn_radius: '12px', theme_btn_shadow: '0 2px 4px rgba(225,29,72,0.15)',
            // البطاقات
            theme_card_bg: '#ffffff', theme_card_border: '#fecdd3', theme_card_radius: '18px',
            theme_card_shadow: '0 2px 8px rgba(225,29,72,0.06)', theme_card_hover_shadow: '0 10px 28px rgba(225,29,72,0.12)', theme_card_padding: '24px',
            theme_product_title_size: '14px', theme_product_title_weight: '500', theme_product_price_size: '16px', theme_product_hover_scale: '1.02',
            // النماذج
            theme_input_height: '48px', theme_input_bg: '#ffffff', theme_input_border: '#fecdd3',
            theme_input_radius: '12px', theme_input_focus_border: '#e11d48', theme_input_focus_shadow: '0 0 0 3px rgba(225,29,72,0.12)', theme_input_placeholder: '#9ca3af',
            // الرأس
            theme_header_bg: '#ffffff', theme_header_height: '70px', theme_header_shadow: '0 1px 4px rgba(225,29,72,0.06)',
            theme_nav_link_color: '#374151', theme_nav_link_hover: '#e11d48', theme_nav_font_size: '15px', theme_nav_font_weight: '500',
            // التذييل
            theme_footer_bg: '#4c0519', theme_footer_text: '#fff1f2', theme_footer_text_muted: '#fda4af',
            theme_footer_link_hover: '#fb7185', theme_footer_padding: '55px', theme_footer_link: '#fecdd3', theme_footer_border: '#9f1239',
            // المكونات
            theme_badge_radius: '10px', theme_badge_padding: '4px 12px', theme_badge_font_size: '12px', theme_badge_font_weight: '500',
            theme_scrollbar_width: '8px', theme_scrollbar_track: '#ffe4e6', theme_scrollbar_thumb: '#fda4af', theme_scrollbar_thumb_hover: '#fb7185',
            theme_modal_bg: '#ffffff', theme_modal_radius: '20px', theme_modal_backdrop: 'rgba(76,5,25,0.5)',
            theme_table_header_bg: '#fdf2f4', theme_table_border: '#fecdd3', theme_table_hover_bg: '#ffe4e6'
        },

        ocean: {
            // ===== أزرق محيطي - سياحة وسفر =====
            // الألوان الأساسية
            theme_primary: '#0891b2', theme_primary_hover: '#0e7490', theme_primary_dark: '#155e75', theme_primary_light: '#ecfeff',
            theme_secondary: '#164e63', theme_secondary_hover: '#155e75', theme_secondary_light: '#0e7490',
            theme_text_primary: '#164e63', theme_text_secondary: '#155e75', theme_text_muted: '#6b7280', theme_text_light: '#67e8f9',
            theme_bg_body: '#ffffff', theme_bg_light: '#ecfeff', theme_bg_gray: '#cffafe', theme_bg_dark: '#164e63',
            theme_success: '#10b981', theme_warning: '#f59e0b', theme_danger: '#ef4444', theme_info: '#06b6d4',
            theme_border: '#67e8f9', theme_border_light: '#a5f3fc', theme_border_dark: '#22d3ee',
            // الخطوط والأحجام
            theme_font_primary: 'Open Sans', theme_font_heading: 'Raleway',
            theme_font_size_base: '15px', theme_font_size_sm: '13px', theme_font_size_lg: '18px',
            theme_radius_xs: '4px', theme_radius_sm: '6px', theme_radius: '12px', theme_radius_lg: '16px', theme_radius_xl: '22px', theme_radius_pill: '9999px',
            theme_shadow_sm: '0 1px 2px rgba(8,145,178,0.06)', theme_shadow: '0 4px 14px rgba(8,145,178,0.1)', theme_shadow_lg: '0 10px 30px rgba(8,145,178,0.15)',
            // الأزرار
            theme_btn_padding_x: '24px', theme_btn_padding_y: '12px', theme_btn_font_size: '14px', theme_btn_font_weight: '600',
            theme_btn_radius: '10px', theme_btn_shadow: '0 2px 4px rgba(8,145,178,0.2)',
            // البطاقات
            theme_card_bg: '#ffffff', theme_card_border: '#a5f3fc', theme_card_radius: '16px',
            theme_card_shadow: '0 2px 10px rgba(8,145,178,0.08)', theme_card_hover_shadow: '0 10px 30px rgba(8,145,178,0.15)', theme_card_padding: '22px',
            theme_product_title_size: '14px', theme_product_title_weight: '500', theme_product_price_size: '16px', theme_product_hover_scale: '1.03',
            // النماذج
            theme_input_height: '48px', theme_input_bg: '#ffffff', theme_input_border: '#a5f3fc',
            theme_input_radius: '10px', theme_input_focus_border: '#0891b2', theme_input_focus_shadow: '0 0 0 3px rgba(8,145,178,0.15)', theme_input_placeholder: '#94a3b8',
            // الرأس
            theme_header_bg: '#ffffff', theme_header_height: '70px', theme_header_shadow: '0 1px 4px rgba(8,145,178,0.08)',
            theme_nav_link_color: '#374151', theme_nav_link_hover: '#0891b2', theme_nav_font_size: '15px', theme_nav_font_weight: '500',
            // التذييل
            theme_footer_bg: '#164e63', theme_footer_text: '#ecfeff', theme_footer_text_muted: '#a5f3fc',
            theme_footer_link_hover: '#22d3ee', theme_footer_padding: '55px', theme_footer_link: '#cffafe', theme_footer_border: '#0e7490',
            // المكونات
            theme_badge_radius: '8px', theme_badge_padding: '4px 12px', theme_badge_font_size: '12px', theme_badge_font_weight: '600',
            theme_scrollbar_width: '8px', theme_scrollbar_track: '#cffafe', theme_scrollbar_thumb: '#67e8f9', theme_scrollbar_thumb_hover: '#22d3ee',
            theme_modal_bg: '#ffffff', theme_modal_radius: '18px', theme_modal_backdrop: 'rgba(22,78,99,0.5)',
            theme_table_header_bg: '#f0fdff', theme_table_border: '#a5f3fc', theme_table_hover_bg: '#cffafe'
        },

        forest: {
            // ===== أخضر غابات - طبيعة وبيئة =====
            // الألوان الأساسية
            theme_primary: '#16a34a', theme_primary_hover: '#15803d', theme_primary_dark: '#166534', theme_primary_light: '#f0fdf4',
            theme_secondary: '#14532d', theme_secondary_hover: '#166534', theme_secondary_light: '#15803d',
            theme_text_primary: '#14532d', theme_text_secondary: '#166534', theme_text_muted: '#6b7280', theme_text_light: '#86efac',
            theme_bg_body: '#ffffff', theme_bg_light: '#f0fdf4', theme_bg_gray: '#dcfce7', theme_bg_dark: '#14532d',
            theme_success: '#10b981', theme_warning: '#f59e0b', theme_danger: '#ef4444', theme_info: '#0891b2',
            theme_border: '#86efac', theme_border_light: '#bbf7d0', theme_border_dark: '#4ade80',
            // الخطوط والأحجام
            theme_font_primary: 'Source Sans Pro', theme_font_heading: 'Merriweather',
            theme_font_size_base: '16px', theme_font_size_sm: '14px', theme_font_size_lg: '19px',
            theme_radius_xs: '4px', theme_radius_sm: '6px', theme_radius: '10px', theme_radius_lg: '14px', theme_radius_xl: '18px', theme_radius_pill: '50px',
            theme_shadow_sm: '0 1px 2px rgba(22,163,74,0.06)', theme_shadow: '0 3px 10px rgba(22,163,74,0.08)', theme_shadow_lg: '0 8px 24px rgba(22,163,74,0.12)',
            // الأزرار
            theme_btn_padding_x: '22px', theme_btn_padding_y: '11px', theme_btn_font_size: '15px', theme_btn_font_weight: '600',
            theme_btn_radius: '8px', theme_btn_shadow: 'none',
            // البطاقات
            theme_card_bg: '#ffffff', theme_card_border: '#bbf7d0', theme_card_radius: '14px',
            theme_card_shadow: '0 2px 8px rgba(22,163,74,0.06)', theme_card_hover_shadow: '0 8px 24px rgba(22,163,74,0.12)', theme_card_padding: '24px',
            theme_product_title_size: '15px', theme_product_title_weight: '500', theme_product_price_size: '17px', theme_product_hover_scale: '1.02',
            // النماذج
            theme_input_height: '48px', theme_input_bg: '#ffffff', theme_input_border: '#bbf7d0',
            theme_input_radius: '8px', theme_input_focus_border: '#16a34a', theme_input_focus_shadow: '0 0 0 3px rgba(22,163,74,0.12)', theme_input_placeholder: '#9ca3af',
            // الرأس
            theme_header_bg: '#ffffff', theme_header_height: '72px', theme_header_shadow: '0 1px 3px rgba(22,163,74,0.06)',
            theme_nav_link_color: '#374151', theme_nav_link_hover: '#16a34a', theme_nav_font_size: '15px', theme_nav_font_weight: '500',
            // التذييل
            theme_footer_bg: '#14532d', theme_footer_text: '#f0fdf4', theme_footer_text_muted: '#86efac',
            theme_footer_link_hover: '#4ade80', theme_footer_padding: '55px', theme_footer_link: '#bbf7d0', theme_footer_border: '#166534',
            // المكونات
            theme_badge_radius: '6px', theme_badge_padding: '5px 12px', theme_badge_font_size: '13px', theme_badge_font_weight: '600',
            theme_scrollbar_width: '10px', theme_scrollbar_track: '#dcfce7', theme_scrollbar_thumb: '#86efac', theme_scrollbar_thumb_hover: '#4ade80',
            theme_modal_bg: '#ffffff', theme_modal_radius: '16px', theme_modal_backdrop: 'rgba(20,83,45,0.5)',
            theme_table_header_bg: '#f7fef9', theme_table_border: '#bbf7d0', theme_table_hover_bg: '#dcfce7'
        },

        midnight: {
            // ===== أزرق منتصف الليل - تقنية وابتكار =====
            // الألوان الأساسية
            theme_primary: '#4f46e5', theme_primary_hover: '#4338ca', theme_primary_dark: '#3730a3', theme_primary_light: '#eef2ff',
            theme_secondary: '#1e1b4b', theme_secondary_hover: '#312e81', theme_secondary_light: '#4338ca',
            theme_text_primary: '#111827', theme_text_secondary: '#374151', theme_text_muted: '#6b7280', theme_text_light: '#9ca3af',
            theme_bg_body: '#ffffff', theme_bg_light: '#f5f7ff', theme_bg_gray: '#eef2ff', theme_bg_dark: '#1e1b4b',
            theme_success: '#10b981', theme_warning: '#f59e0b', theme_danger: '#ef4444', theme_info: '#06b6d4',
            theme_border: '#c7d2fe', theme_border_light: '#e0e7ff', theme_border_dark: '#a5b4fc',
            // الخطوط والأحجام
            theme_font_primary: 'Inter', theme_font_heading: 'Space Grotesk',
            theme_font_size_base: '15px', theme_font_size_sm: '13px', theme_font_size_lg: '18px',
            theme_radius_xs: '2px', theme_radius_sm: '4px', theme_radius: '8px', theme_radius_lg: '10px', theme_radius_xl: '14px', theme_radius_pill: '50px',
            theme_shadow_sm: '0 1px 2px rgba(79,70,229,0.08)', theme_shadow: '0 4px 12px rgba(79,70,229,0.12)', theme_shadow_lg: '0 10px 30px rgba(79,70,229,0.18)',
            // الأزرار
            theme_btn_padding_x: '20px', theme_btn_padding_y: '10px', theme_btn_font_size: '14px', theme_btn_font_weight: '500',
            theme_btn_radius: '6px', theme_btn_shadow: '0 1px 2px rgba(79,70,229,0.2)',
            // البطاقات
            theme_card_bg: '#ffffff', theme_card_border: '#c7d2fe', theme_card_radius: '10px',
            theme_card_shadow: '0 2px 8px rgba(79,70,229,0.08)', theme_card_hover_shadow: '0 10px 30px rgba(79,70,229,0.15)', theme_card_padding: '20px',
            theme_product_title_size: '14px', theme_product_title_weight: '500', theme_product_price_size: '16px', theme_product_hover_scale: '1.02',
            // النماذج
            theme_input_height: '44px', theme_input_bg: '#ffffff', theme_input_border: '#c7d2fe',
            theme_input_radius: '6px', theme_input_focus_border: '#4f46e5', theme_input_focus_shadow: '0 0 0 3px rgba(79,70,229,0.15)', theme_input_placeholder: '#94a3b8',
            // الرأس
            theme_header_bg: '#ffffff', theme_header_height: '64px', theme_header_shadow: '0 1px 3px rgba(79,70,229,0.08)',
            theme_nav_link_color: '#374151', theme_nav_link_hover: '#4f46e5', theme_nav_font_size: '14px', theme_nav_font_weight: '500',
            // التذييل
            theme_footer_bg: '#1e1b4b', theme_footer_text: '#eef2ff', theme_footer_text_muted: '#a5b4fc',
            theme_footer_link_hover: '#818cf8', theme_footer_padding: '50px', theme_footer_link: '#c7d2fe', theme_footer_border: '#3730a3',
            // المكونات
            theme_badge_radius: '4px', theme_badge_padding: '3px 10px', theme_badge_font_size: '12px', theme_badge_font_weight: '500',
            theme_scrollbar_width: '6px', theme_scrollbar_track: '#e0e7ff', theme_scrollbar_thumb: '#a5b4fc', theme_scrollbar_thumb_hover: '#818cf8',
            theme_modal_bg: '#ffffff', theme_modal_radius: '12px', theme_modal_backdrop: 'rgba(30,27,75,0.6)',
            theme_table_header_bg: '#f5f7ff', theme_table_border: '#c7d2fe', theme_table_hover_bg: '#eef2ff'
        },

        crimson: {
            // ===== أحمر قرمزي - قوة وطاقة =====
            // الألوان الأساسية
            theme_primary: '#dc2626', theme_primary_hover: '#b91c1c', theme_primary_dark: '#991b1b', theme_primary_light: '#fef2f2',
            theme_secondary: '#450a0a', theme_secondary_hover: '#7f1d1d', theme_secondary_light: '#991b1b',
            theme_text_primary: '#450a0a', theme_text_secondary: '#7f1d1d', theme_text_muted: '#78716c', theme_text_light: '#fca5a5',
            theme_bg_body: '#ffffff', theme_bg_light: '#fef2f2', theme_bg_gray: '#fee2e2', theme_bg_dark: '#450a0a',
            theme_success: '#10b981', theme_warning: '#f59e0b', theme_danger: '#ef4444', theme_info: '#3b82f6',
            theme_border: '#fca5a5', theme_border_light: '#fecaca', theme_border_dark: '#f87171',
            // الخطوط والأحجام
            theme_font_primary: 'Roboto', theme_font_heading: 'Oswald',
            theme_font_size_base: '15px', theme_font_size_sm: '13px', theme_font_size_lg: '18px',
            theme_radius_xs: '2px', theme_radius_sm: '4px', theme_radius: '6px', theme_radius_lg: '8px', theme_radius_xl: '12px', theme_radius_pill: '50px',
            theme_shadow_sm: '0 1px 2px rgba(220,38,38,0.08)', theme_shadow: '0 4px 10px rgba(220,38,38,0.12)', theme_shadow_lg: '0 8px 24px rgba(220,38,38,0.18)',
            // الأزرار
            theme_btn_padding_x: '22px', theme_btn_padding_y: '11px', theme_btn_font_size: '14px', theme_btn_font_weight: '700',
            theme_btn_radius: '4px', theme_btn_shadow: '0 2px 4px rgba(220,38,38,0.25)',
            // البطاقات
            theme_card_bg: '#ffffff', theme_card_border: '#fecaca', theme_card_radius: '8px',
            theme_card_shadow: '0 2px 6px rgba(220,38,38,0.08)', theme_card_hover_shadow: '0 8px 24px rgba(220,38,38,0.15)', theme_card_padding: '20px',
            theme_product_title_size: '14px', theme_product_title_weight: '600', theme_product_price_size: '16px', theme_product_hover_scale: '1.02',
            // النماذج
            theme_input_height: '46px', theme_input_bg: '#ffffff', theme_input_border: '#fecaca',
            theme_input_radius: '4px', theme_input_focus_border: '#dc2626', theme_input_focus_shadow: '0 0 0 3px rgba(220,38,38,0.12)', theme_input_placeholder: '#9ca3af',
            // الرأس
            theme_header_bg: '#ffffff', theme_header_height: '68px', theme_header_shadow: '0 2px 6px rgba(220,38,38,0.1)',
            theme_nav_link_color: '#374151', theme_nav_link_hover: '#dc2626', theme_nav_font_size: '15px', theme_nav_font_weight: '600',
            // التذييل
            theme_footer_bg: '#7f1d1d', theme_footer_text: '#fef2f2', theme_footer_text_muted: '#fca5a5',
            theme_footer_link_hover: '#f87171', theme_footer_padding: '50px', theme_footer_link: '#fecaca', theme_footer_border: '#991b1b',
            // المكونات
            theme_badge_radius: '3px', theme_badge_padding: '4px 10px', theme_badge_font_size: '12px', theme_badge_font_weight: '700',
            theme_scrollbar_width: '8px', theme_scrollbar_track: '#fee2e2', theme_scrollbar_thumb: '#fca5a5', theme_scrollbar_thumb_hover: '#f87171',
            theme_modal_bg: '#ffffff', theme_modal_radius: '10px', theme_modal_backdrop: 'rgba(127,29,29,0.6)',
            theme_table_header_bg: '#fef8f8', theme_table_border: '#fecaca', theme_table_hover_bg: '#fee2e2'
        },

        saudi: {
            // ===== تراث سعودي - أخضر رسمي + ذهبي + بني صحراوي =====
            // ألوان مستوحاة من: العلم السعودي، الرمال الذهبية، العمارة التراثية
            // الألوان الأساسية
            theme_primary: '#006c35', theme_primary_hover: '#005529', theme_primary_dark: '#004420', theme_primary_light: '#e8f5ed',
            theme_secondary: '#1a1510', theme_secondary_hover: '#2d261e', theme_secondary_light: '#45382a',
            theme_text_primary: '#1a1510', theme_text_secondary: '#3d3429', theme_text_muted: '#7a6f5f', theme_text_light: '#b8a992',
            theme_bg_body: '#fdfcfa', theme_bg_light: '#faf8f5', theme_bg_gray: '#f5f2ec', theme_bg_dark: '#1a1510',
            theme_success: '#10b981', theme_warning: '#d4af37', theme_danger: '#c53030', theme_info: '#2c7a7b',
            theme_border: '#d4c4a8', theme_border_light: '#e8dcc8', theme_border_dark: '#c9a962',
            // الخطوط والأحجام - رسمي واحترافي
            theme_font_primary: 'Cairo', theme_font_heading: 'Cairo',
            theme_font_size_base: '15px', theme_font_size_sm: '13px', theme_font_size_lg: '18px',
            theme_radius_xs: '2px', theme_radius_sm: '4px', theme_radius: '6px', theme_radius_lg: '8px', theme_radius_xl: '10px', theme_radius_pill: '50px',
            theme_shadow_sm: '0 1px 2px rgba(26,21,16,0.06)', theme_shadow: '0 2px 8px rgba(26,21,16,0.08)', theme_shadow_lg: '0 6px 20px rgba(26,21,16,0.12)',
            // الأزرار - أناقة ملكية
            theme_btn_padding_x: '22px', theme_btn_padding_y: '11px', theme_btn_font_size: '14px', theme_btn_font_weight: '600',
            theme_btn_radius: '4px', theme_btn_shadow: '0 1px 3px rgba(0,108,53,0.2)',
            // البطاقات - عصرية بلمسة تراثية
            theme_card_bg: '#ffffff', theme_card_border: '#e8dcc8', theme_card_radius: '8px',
            theme_card_shadow: '0 2px 6px rgba(26,21,16,0.06)', theme_card_hover_shadow: '0 6px 18px rgba(0,108,53,0.1)', theme_card_padding: '22px',
            theme_product_title_size: '14px', theme_product_title_weight: '500', theme_product_price_size: '16px', theme_product_hover_scale: '1.01',
            // النماذج
            theme_input_height: '46px', theme_input_bg: '#ffffff', theme_input_border: '#d4c4a8',
            theme_input_radius: '4px', theme_input_focus_border: '#006c35', theme_input_focus_shadow: '0 0 0 3px rgba(0,108,53,0.12)', theme_input_placeholder: '#9c8d7a',
            // الرأس - نظيف واحترافي
            theme_header_bg: '#ffffff', theme_header_height: '70px', theme_header_shadow: '0 1px 4px rgba(26,21,16,0.08)',
            theme_nav_link_color: '#3d3429', theme_nav_link_hover: '#006c35', theme_nav_font_size: '15px', theme_nav_font_weight: '500',
            // التذييل - غني وفخم
            theme_footer_bg: '#1a1510', theme_footer_text: '#f5f2ec', theme_footer_text_muted: '#b8a992',
            theme_footer_link_hover: '#c9a962', theme_footer_padding: '55px', theme_footer_link: '#d4c4a8', theme_footer_border: '#45382a',
            // المكونات - لمسات ذهبية
            theme_badge_radius: '3px', theme_badge_padding: '4px 10px', theme_badge_font_size: '12px', theme_badge_font_weight: '600',
            theme_scrollbar_width: '8px', theme_scrollbar_track: '#f5f2ec', theme_scrollbar_thumb: '#d4c4a8', theme_scrollbar_thumb_hover: '#c9a962',
            theme_modal_bg: '#ffffff', theme_modal_radius: '10px', theme_modal_backdrop: 'rgba(26,21,16,0.65)',
            theme_table_header_bg: '#faf8f5', theme_table_border: '#e8dcc8', theme_table_hover_bg: '#f5f2ec'
        }
    };

    $('.preset-btn').on('click', function() {
        var presetName = $(this).data('preset');
        var preset = presets[presetName];

        if (preset) {
            $.each(preset, function(key, value) {
                // Try input first
                var $input = $('input[name="' + key + '"]');
                if ($input.length) {
                    $input.val(value);
                    var $wrapper = $input.closest('.color-input-group');
                    if ($wrapper.length) {
                        // Update native color picker
                        var $nativePicker = $wrapper.find('.native-color-picker');
                        if ($nativePicker.length && /^#[0-9A-Fa-f]{6}$/.test(value)) {
                            $nativePicker.val(value);
                        }
                    }
                }

                // Try select (for fonts, font weights)
                var $select = $('select[name="' + key + '"]');
                if ($select.length) {
                    $select.val(value);
                }
            });
            updatePreview();
            updatePaletteDisplay();

            // Mark active
            $('.preset-btn').removeClass('active');
            $(this).addClass('active');

            // Show success notification
            if (typeof toastr !== 'undefined') {
                toastr.success('تم تطبيق الثيم "' + presetName + '" - اضغط حفظ لتأكيد التغييرات', 'تم التطبيق');
            }
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
    if (confirm('{{ __("Are you sure you want to reset all theme settings to defaults?") }}')) {
        $('.preset-btn[data-preset="nissan"]').click();
    }
}

// =====================================================================
// EXPORT/IMPORT FUNCTIONS
// =====================================================================

// Export current theme as JSON
function exportTheme() {
    var themeData = {};

    // Collect all color fields
    $('input.color-field, input[type="text"][name^="theme_"]').each(function() {
        var name = $(this).attr('name');
        var value = $(this).val();
        if (name && value) {
            themeData[name] = value;
        }
    });

    // Collect all select fields
    $('select[name^="theme_"]').each(function() {
        var name = $(this).attr('name');
        var value = $(this).val();
        if (name && value) {
            themeData[name] = value;
        }
    });

    // Create downloadable file
    var dataStr = JSON.stringify(themeData, null, 2);
    var dataBlob = new Blob([dataStr], {type: 'application/json'});
    var url = URL.createObjectURL(dataBlob);

    var link = document.createElement('a');
    link.href = url;
    link.download = 'theme-config-' + new Date().toISOString().slice(0,10) + '.json';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    URL.revokeObjectURL(url);

    if (typeof toastr !== 'undefined') {
        toastr.success('{{ __("Theme exported successfully!") }}');
    }
}

// Open import modal
function openImportModal() {
    $('#importModal').addClass('show');
    $('#importThemeData').val('').focus();
}

// Close import modal
function closeImportModal() {
    $('#importModal').removeClass('show');
}

// Apply imported theme
function applyImportedTheme() {
    var jsonData = $('#importThemeData').val().trim();

    if (!jsonData) {
        alert('{{ __("Please paste theme JSON data") }}');
        return;
    }

    try {
        var themeData = JSON.parse(jsonData);

        $.each(themeData, function(key, value) {
            // Try input first
            var $input = $('input[name="' + key + '"]');
            if ($input.length) {
                $input.val(value);
                var $wrapper = $input.closest('.color-input-group');
                if ($wrapper.length) {
                    var $nativePicker = $wrapper.find('.native-color-picker');
                    if ($nativePicker.length && /^#[0-9A-Fa-f]{6}$/.test(value)) {
                        $nativePicker.val(value);
                    }
                }
            }

            // Try select
            var $select = $('select[name="' + key + '"]');
            if ($select.length) {
                $select.val(value);
            }
        });

        closeImportModal();

        if (typeof toastr !== 'undefined') {
            toastr.success('{{ __("Theme imported successfully! Click Save to apply.") }}');
        }

    } catch (e) {
        alert('{{ __("Invalid JSON format. Please check your data.") }}');
        console.error(e);
    }
}

// Generate CSS Variables
function copyCSSVariables() {
    var cssVars = ':root {\n';

    $('input.color-field').each(function() {
        var name = $(this).attr('name');
        var value = $(this).val();
        if (name && value) {
            var cssVarName = '--' + name.replace(/_/g, '-');
            cssVars += '    ' + cssVarName + ': ' + value + ';\n';
        }
    });

    cssVars += '}';

    $('#cssVariablesOutput').val(cssVars);
    $('#cssModal').addClass('show');
}

// Close CSS modal
function closeCSSModal() {
    $('#cssModal').removeClass('show');
}

// Copy CSS to clipboard
function copyCSSToClipboard() {
    var textarea = document.getElementById('cssVariablesOutput');
    textarea.select();
    document.execCommand('copy');

    if (typeof toastr !== 'undefined') {
        toastr.success('{{ __("CSS variables copied to clipboard!") }}');
    }

    closeCSSModal();
}

// Close modals on outside click
$(document).on('click', '.theme-modal', function(e) {
    if ($(e.target).hasClass('theme-modal')) {
        $(this).removeClass('show');
    }
});

// Close modals on ESC key
$(document).on('keydown', function(e) {
    if (e.key === 'Escape') {
        $('.theme-modal').removeClass('show');
    }
});
</script>
@endsection
