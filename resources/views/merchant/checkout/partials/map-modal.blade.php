{{-- Google Maps Location Selection Modal --}}
<div class="modal fade" id="mapModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content" style="border: none; border-radius: var(--radius-lg, 12px); overflow: hidden;">
            <div class="modal-header" style="background: var(--action-primary); color: var(--text-on-primary, #fff); border: none;">
                <h5 class="modal-name">
                    <i class="fas fa-map-marker-alt me-2"></i>
                    @lang('Select your location')
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                {{-- Search Box --}}
                <div class="p-3" style="background: var(--bg-secondary); border-bottom: 1px solid var(--border-default);">
                    <div class="input-group">
                        <span class="input-group-text" style="background: var(--bg-primary); border-color: var(--border-default);">
                            <i class="fas fa-search" style="color: var(--text-muted);"></i>
                        </span>
                        <input type="text" id="map-search-input" class="form-control"
                               style="border-color: var(--border-default);"
                               placeholder="@lang('Search for a location...')" autocomplete="off">
                    </div>
                </div>

                {{-- Map Container --}}
                <div id="map" style="height: 350px; width: 100%;"></div>

                {{-- Location Display --}}
                <div class="p-3" style="background: var(--bg-secondary); border-top: 1px solid var(--border-default);">
                    <div class="d-flex justify-content-between align-items-start gap-2">
                        <div class="flex-grow-1">
                            <small class="d-block mb-1" style="color: var(--text-muted);">@lang('Selected Location'):</small>
                            <div id="coords-display" class="fw-bold" style="word-break: break-word; color: var(--text-primary);">
                                @lang('Click on map or search to select location')
                            </div>
                        </div>
                        <button type="button" class="m-btn m-btn--secondary m-btn--sm flex-shrink-0" id="my-location-btn">
                            <i class="fas fa-crosshairs"></i> @lang('My Location')
                        </button>
                    </div>
                </div>
            </div>
            <div class="modal-footer" style="background: var(--bg-primary); border-top: 1px solid var(--border-default);">
                <button type="button" class="m-btn m-btn--secondary" data-bs-dismiss="modal">@lang('Close')</button>
                <button type="button" class="m-btn m-btn--primary" id="confirm-location-btn" disabled>
                    <i class="fas fa-check me-1"></i> @lang('Confirm Location')
                </button>
            </div>
        </div>
    </div>
</div>

<style>
/* Google Places Autocomplete z-index fix */
.pac-container {
    z-index: 10000 !important;
}

/* Modal styling */
#mapModal .modal-content {
    background: var(--bg-primary, #fff) !important;
    opacity: 1 !important;
}

#mapModal .modal-header {
    background: var(--action-primary) !important;
    opacity: 1 !important;
}

#mapModal .modal-body {
    background: var(--bg-primary, #fff) !important;
    opacity: 1 !important;
}

#mapModal .modal-footer {
    background: var(--bg-primary, #fff) !important;
    opacity: 1 !important;
}

/* Dark backdrop */
.modal-backdrop.show {
    opacity: 0.7 !important;
    background: var(--overlay-backdrop, #000) !important;
}
</style>
