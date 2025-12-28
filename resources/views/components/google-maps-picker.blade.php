{{--
    Google Maps Location Picker Component
    Usage: @include('components.google-maps-picker', ['showAsModal' => true])
--}}

@php
    $showAsModal = $showAsModal ?? false;
    $modalId = $modalId ?? 'google-maps-modal';
    $mapHeight = $mapHeight ?? '400px';
@endphp

{{-- POLICY: Google Maps meta tag ONLY if API key exists in api_credentials table --}}
@if(!empty($googleMapsApiKey))
@push('meta')
    <meta name="google-maps-key" content="{{ $googleMapsApiKey }}">
@endpush
@else
    @php \Log::warning('Google Maps: API key not configured in api_credentials table - Maps picker disabled'); @endphp
@endif

@if($showAsModal)
    {{-- Modal Version --}}
    <div class="modal fade" id="{{ $modalId }}" tabindex="-1" aria-labelledby="{{ $modalId }}Label" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header" style="background: linear-gradient(135deg, var(--theme-primary) 0%, var(--theme-secondary) 100%); color: white;">
                    <h5 class="modal-title" id="{{ $modalId }}Label">
                        <i class="fas fa-map-marker-alt"></i> @lang('Select Location on Map')
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" style="padding: 0;">
                    @include('components.google-maps-picker-content', ['mapHeight' => $mapHeight])
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">@lang('Cancel')</button>
                    <button type="button" class="btn btn-primary" id="confirm-location-btn" disabled>
                        @lang('Use This Location')
                    </button>
                </div>
            </div>
        </div>
    </div>
@else
    {{-- Inline Version --}}
    <div class="google-maps-picker-wrapper">
        @include('components.google-maps-picker-content', ['mapHeight' => $mapHeight])
    </div>
@endif

@push('styles')
<style>
    .google-maps-picker-wrapper {
        margin: 20px 0;
    }

    .map-picker-section {
        background: white;
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }

    .map-picker-header {
        background: linear-gradient(135deg, var(--theme-primary, #006c35) 0%, var(--theme-primary-dark, #004420) 100%);
        color: white;
        padding: 15px 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .map-picker-header h5 {
        margin: 0;
        font-size: 16px;
        font-weight: 600;
    }

    .map-picker-header .badge {
        background: var(--overlay-light, rgba(255,255,255,0.2));
        padding: 5px 10px;
        font-size: 12px;
    }

    #map-picker-container {
        position: relative;
    }

    #location-map {
        width: 100%;
        height: {{ $mapHeight }};
    }

    .map-search-wrapper {
        position: absolute;
        top: 10px;
        right: 10px;
        left: 10px;
        z-index: 10;
    }

    #map-search-input {
        width: 100%;
        padding: 12px 15px;
        border: 2px solid var(--theme-primary, #006c35);
        border-radius: 8px;
        font-size: 14px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        background: white;
    }

    #map-search-input:focus {
        outline: none;
        border-color: var(--theme-primary-dark, #004420);
    }

    #map-loading-overlay {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: var(--overlay-light-strong, rgba(255,255,255,0.9));
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 20;
    }

    #map-loading-overlay .spinner {
        border: 3px solid var(--theme-border-light, #f3f3f3);
        border-top: 3px solid var(--theme-primary, #006c35);
        border-radius: 50%;
        width: 40px;
        height: 40px;
        animation: spin 1s linear infinite;
    }

    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }

    .map-controls {
        padding: 15px 20px;
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
        border-top: 1px solid var(--theme-border-light, #eee);
    }

    .map-controls .btn {
        padding: 10px 20px;
        border-radius: 6px;
        font-size: 14px;
        font-weight: 600;
        transition: all 0.3s;
        border: none;
        cursor: pointer;
    }

    .map-controls .btn-primary {
        background: linear-gradient(135deg, var(--theme-primary, #006c35) 0%, var(--theme-primary-dark, #004420) 100%);
        color: white;
    }

    .map-controls .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 108, 53, 0.4);
    }

    .map-controls .btn-secondary {
        background: var(--theme-bg-gray, #e0e0e0);
        color: var(--theme-text-primary, #333);
    }

    .map-controls .btn-secondary:hover {
        background: var(--theme-border, #d0d0d0);
    }

    .map-controls .btn:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }

    .location-info-display {
        padding: 15px 20px;
        background: var(--theme-bg-light, #f8f9fa);
        border-top: 1px solid var(--theme-border-light, #eee);
        display: none;
    }

    .location-info-display.show {
        display: block;
    }

    .location-info-display h6 {
        font-size: 14px;
        font-weight: 600;
        margin-bottom: 10px;
        color: var(--theme-text-primary, #333);
    }

    .location-info-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 10px;
    }

    .location-info-item {
        background: white;
        padding: 10px;
        border-radius: 6px;
        border-right: 3px solid var(--theme-primary, #006c35);
    }

    .location-info-item label {
        display: block;
        font-size: 11px;
        color: var(--theme-text-muted, #666);
        margin-bottom: 3px;
        font-weight: 600;
    }

    .location-info-item .value {
        font-size: 13px;
        color: var(--theme-text-primary, #333);
    }

    #map-alert-container {
        padding: 10px 20px;
    }

    #map-alert-container .alert {
        margin: 0;
        padding: 10px 15px;
        font-size: 13px;
        border-radius: 6px;
    }
</style>
@endpush
