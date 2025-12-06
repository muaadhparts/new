@extends('layouts.admin')

@section('content')
<div class="content-area">
    <div class="mr-breadcrumb">
        <div class="row">
            <div class="col-lg-12">
                <h4 class="heading">{{ __('Theme Colors') }}</h4>
                <ul class="links">
                    <li>
                        <a href="{{ route('admin.dashboard') }}">{{ __('Dashboard') }}</a>
                    </li>
                    <li>
                        <a href="javascript:;">{{ __('General Settings') }}</a>
                    </li>
                    <li>
                        <a href="{{ route('admin-theme-colors') }}">{{ __('Theme Colors') }}</a>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <div class="add-product-content1 add-product-content2">
        <div class="row">
            <div class="col-lg-12">
                <div class="product-description">
                    <div class="body-area">
                        <div class="gocover" style="background: url({{ asset('assets/images/' . $gs->admin_loader) }}) no-repeat scroll center center rgba(45, 45, 45, 0.5);"></div>

                        <form action="{{ route('admin-theme-colors-update') }}" id="themeColorForm" method="POST">
                            @csrf

                            @include('alerts.admin.form-both')

                            {{-- Live Preview --}}
                            <div class="row justify-content-center mb-4">
                                <div class="col-lg-9">
                                    <div class="card">
                                        <div class="card-header bg-dark text-white">
                                            <i class="fas fa-eye"></i> {{ __('Live Preview') }}
                                        </div>
                                        <div class="card-body">
                                            <div class="d-flex flex-wrap gap-3 align-items-center">
                                                <button type="button" class="btn preview-btn-primary">{{ __('Primary Button') }}</button>
                                                <button type="button" class="btn preview-btn-secondary">{{ __('Secondary Button') }}</button>
                                                <span class="badge preview-badge-primary">{{ __('Badge') }}</span>
                                                <a href="javascript:;" class="preview-link">{{ __('Link Color') }}</a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Primary Colors --}}
                            <div class="row justify-content-center">
                                <div class="col-lg-9">
                                    <h5 class="mb-3 text-primary"><i class="fas fa-palette"></i> {{ __('Primary Colors (Main Brand Color)') }}</h5>
                                </div>
                            </div>

                            <div class="row justify-content-center">
                                <div class="col-lg-3">
                                    <div class="left-area">
                                        <h4 class="heading">{{ __('Primary Color') }} *</h4>
                                        <p class="sub-heading">{{ __('Main brand color') }}</p>
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <div class="input-group colorpicker-component cp">
                                        <input type="text" class="input-field color-field" name="theme_primary" id="theme_primary"
                                               value="{{ $gs->theme_primary ?? '#c3002f' }}">
                                        <span class="input-group-addon"><i></i></span>
                                    </div>
                                </div>
                            </div>

                            <div class="row justify-content-center">
                                <div class="col-lg-3">
                                    <div class="left-area">
                                        <h4 class="heading">{{ __('Primary Hover') }} *</h4>
                                        <p class="sub-heading">{{ __('Darker shade for hover') }}</p>
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <div class="input-group colorpicker-component cp">
                                        <input type="text" class="input-field color-field" name="theme_primary_hover" id="theme_primary_hover"
                                               value="{{ $gs->theme_primary_hover ?? '#a00025' }}">
                                        <span class="input-group-addon"><i></i></span>
                                    </div>
                                </div>
                            </div>

                            <div class="row justify-content-center">
                                <div class="col-lg-3">
                                    <div class="left-area">
                                        <h4 class="heading">{{ __('Primary Dark') }} *</h4>
                                        <p class="sub-heading">{{ __('Darkest shade') }}</p>
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <div class="input-group colorpicker-component cp">
                                        <input type="text" class="input-field color-field" name="theme_primary_dark" id="theme_primary_dark"
                                               value="{{ $gs->theme_primary_dark ?? '#8a0020' }}">
                                        <span class="input-group-addon"><i></i></span>
                                    </div>
                                </div>
                            </div>

                            <div class="row justify-content-center">
                                <div class="col-lg-3">
                                    <div class="left-area">
                                        <h4 class="heading">{{ __('Primary Light') }} *</h4>
                                        <p class="sub-heading">{{ __('Light shade for backgrounds') }}</p>
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <div class="input-group colorpicker-component cp">
                                        <input type="text" class="input-field color-field" name="theme_primary_light" id="theme_primary_light"
                                               value="{{ $gs->theme_primary_light ?? '#fef2f4' }}">
                                        <span class="input-group-addon"><i></i></span>
                                    </div>
                                </div>
                            </div>

                            <hr class="my-4">

                            {{-- Secondary Colors --}}
                            <div class="row justify-content-center">
                                <div class="col-lg-9">
                                    <h5 class="mb-3 text-secondary"><i class="fas fa-palette"></i> {{ __('Secondary Colors') }}</h5>
                                </div>
                            </div>

                            <div class="row justify-content-center">
                                <div class="col-lg-3">
                                    <div class="left-area">
                                        <h4 class="heading">{{ __('Secondary Color') }} *</h4>
                                        <p class="sub-heading">{{ __('Secondary brand color') }}</p>
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <div class="input-group colorpicker-component cp">
                                        <input type="text" class="input-field color-field" name="theme_secondary" id="theme_secondary"
                                               value="{{ $gs->theme_secondary ?? '#1a1a1a' }}">
                                        <span class="input-group-addon"><i></i></span>
                                    </div>
                                </div>
                            </div>

                            <div class="row justify-content-center">
                                <div class="col-lg-3">
                                    <div class="left-area">
                                        <h4 class="heading">{{ __('Secondary Hover') }} *</h4>
                                        <p class="sub-heading">{{ __('Lighter shade for hover') }}</p>
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <div class="input-group colorpicker-component cp">
                                        <input type="text" class="input-field color-field" name="theme_secondary_hover" id="theme_secondary_hover"
                                               value="{{ $gs->theme_secondary_hover ?? '#333333' }}">
                                        <span class="input-group-addon"><i></i></span>
                                    </div>
                                </div>
                            </div>

                            <hr class="my-4">

                            {{-- Quick Presets --}}
                            <div class="row justify-content-center">
                                <div class="col-lg-9">
                                    <h5 class="mb-3"><i class="fas fa-magic"></i> {{ __('Quick Presets') }}</h5>
                                    <div class="d-flex flex-wrap gap-2 mb-4">
                                        <button type="button" class="btn btn-sm preset-btn" data-primary="#c3002f" data-hover="#a00025" data-dark="#8a0020" data-light="#fef2f4" style="background: #c3002f; color: white;">
                                            Nissan Red
                                        </button>
                                        <button type="button" class="btn btn-sm preset-btn" data-primary="#2563eb" data-hover="#1d4ed8" data-dark="#1e40af" data-light="#eff6ff" style="background: #2563eb; color: white;">
                                            Blue
                                        </button>
                                        <button type="button" class="btn btn-sm preset-btn" data-primary="#16a34a" data-hover="#15803d" data-dark="#166534" data-light="#f0fdf4" style="background: #16a34a; color: white;">
                                            Green
                                        </button>
                                        <button type="button" class="btn btn-sm preset-btn" data-primary="#9333ea" data-hover="#7c3aed" data-dark="#6b21a8" data-light="#faf5ff" style="background: #9333ea; color: white;">
                                            Purple
                                        </button>
                                        <button type="button" class="btn btn-sm preset-btn" data-primary="#ea580c" data-hover="#c2410c" data-dark="#9a3412" data-light="#fff7ed" style="background: #ea580c; color: white;">
                                            Orange
                                        </button>
                                        <button type="button" class="btn btn-sm preset-btn" data-primary="#0891b2" data-hover="#0e7490" data-dark="#155e75" data-light="#ecfeff" style="background: #0891b2; color: white;">
                                            Cyan
                                        </button>
                                    </div>
                                </div>
                            </div>

                            {{-- Submit Button --}}
                            <div class="row justify-content-center">
                                <div class="col-lg-3">
                                    <div class="left-area"></div>
                                </div>
                                <div class="col-lg-6">
                                    <button class="addProductSubmit-btn" type="submit">
                                        <i class="fas fa-save"></i> {{ __('Save Colors') }}
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
.preview-btn-primary {
    background-color: var(--preview-primary, #c3002f);
    border-color: var(--preview-primary, #c3002f);
    color: white;
    padding: 8px 20px;
    border-radius: 5px;
    transition: all 0.3s;
}
.preview-btn-primary:hover {
    background-color: var(--preview-primary-hover, #a00025);
    border-color: var(--preview-primary-hover, #a00025);
    color: white;
}
.preview-btn-secondary {
    background-color: var(--preview-secondary, #1a1a1a);
    border-color: var(--preview-secondary, #1a1a1a);
    color: white;
    padding: 8px 20px;
    border-radius: 5px;
    transition: all 0.3s;
}
.preview-btn-secondary:hover {
    background-color: var(--preview-secondary-hover, #333333);
    border-color: var(--preview-secondary-hover, #333333);
    color: white;
}
.preview-badge-primary {
    background-color: var(--preview-primary, #c3002f);
    color: white;
    padding: 5px 12px;
    border-radius: 20px;
    font-size: 14px;
}
.preview-link {
    color: var(--preview-primary, #c3002f);
    text-decoration: none;
    font-weight: 500;
}
.preview-link:hover {
    color: var(--preview-primary-hover, #a00025);
    text-decoration: underline;
}
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
.gap-2 { gap: 0.5rem; }
.gap-3 { gap: 1rem; }
</style>
@endsection

@section('scripts')
<script src="{{ asset('assets/admin/js/bootstrap-colorpicker.min.js') }}"></script>
<script>
$(document).ready(function() {
    // Initialize colorpickers
    $('.cp').colorpicker();

    // Update preview on color change
    function updatePreview() {
        document.documentElement.style.setProperty('--preview-primary', $('#theme_primary').val());
        document.documentElement.style.setProperty('--preview-primary-hover', $('#theme_primary_hover').val());
        document.documentElement.style.setProperty('--preview-primary-dark', $('#theme_primary_dark').val());
        document.documentElement.style.setProperty('--preview-primary-light', $('#theme_primary_light').val());
        document.documentElement.style.setProperty('--preview-secondary', $('#theme_secondary').val());
        document.documentElement.style.setProperty('--preview-secondary-hover', $('#theme_secondary_hover').val());
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
        var $btn = $(this);
        $('#theme_primary').val($btn.data('primary')).trigger('change');
        $('#theme_primary_hover').val($btn.data('hover')).trigger('change');
        $('#theme_primary_dark').val($btn.data('dark')).trigger('change');
        $('#theme_primary_light').val($btn.data('light')).trigger('change');

        // Reinitialize colorpickers
        $('.cp').colorpicker('setValue', function() {
            return $(this).find('input').val();
        });

        updatePreview();
    });
});
</script>
@endsection
