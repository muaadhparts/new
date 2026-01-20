{{-- resources\views\catalog\partials\callout-modal.blade.php --}}
{{-- Callout Modal - Pure HTML/JS (No Livewire) --}}
{{-- Uses catalog-unified.css for styling --}}

<div
    id="modal"
    class="modal fade"
    tabindex="-1"
    role="dialog"
    aria-hidden="true"
    data-bs-backdrop="static"
    data-bs-keyboard="false"
>
    {{-- Responsive Modal Dialog --}}
    <div class="modal-dialog modal-fullscreen-sm-down modal-xl modal-dialog-centered modal-dialog-scrollable" role="document">
        <div class="modal-content shadow-lg rounded-0 rounded-md-3 border-0">

            {{-- Header - Responsive --}}
            <div class="modal-header bg-primary text-white border-0 py-2 py-md-3">
                <h5 class="modal-name fs-6 fs-md-5 mb-0">
                    <span id="ill-modal-name">@lang('catalog.modal.name')</span>
                </h5>

                <div class="d-flex align-items-center gap-1 gap-md-2">
                    <button type="button"
                            id="ill-back-btn"
                            class="btn btn-sm btn-light d-none px-2 py-1 px-md-3 py-md-2"
                            name="@lang('catalog.modal.back')">
                        <i class="fas fa-arrow-left d-md-none"></i>
                        <span class="d-none d-md-inline">@lang('catalog.modal.back')</span>
                    </button>
                    <button type="button"
                            class="btn-close btn-close-white"
                            data-bs-dismiss="modal"
                            aria-label="@lang('catalog.modal.close')"></button>
                </div>
            </div>

            {{-- Body - Responsive Padding --}}
            <div class="modal-body p-0 bg-light">
                <div id="api-callout-body" class="callout-modal-body">
                    <div class="catalog-loading">
                        <div class="spinner-border text-primary" role="status"></div>
                        <p>@lang('catalog.modal.loading')</p>
                    </div>
                </div>
            </div>

            {{-- Footer - Responsive --}}
            <div class="modal-footer bg-light border-0 py-2 py-md-3">
                <small class="text-muted text-center text-md-start w-100">
                    <span id="ill-modal-footnote">@lang('catalog.modal.match_info')</span>
                </small>
            </div>
        </div>
    </div>
</div>

{{-- تمرير مفاتيح الترجمة ومسارات الأجزاء --}}
<script>
window.locale = "{{ app()->getLocale() }}";
window.i18n = {
    /* رؤوس الأعمدة */
    "columns.number": "@lang('columns.number')",
    "columns.callout": "@lang('columns.callout')",
    "columns.name": "@lang('columns.name')",
    "columns.qty": "@lang('columns.qty')",
    "columns.match": "@lang('columns.match')",
    "columns.extensions": "@lang('columns.extensions')",
    "columns.price": "@lang('columns.price')",
    "columns.period": "@lang('columns.period')",
    "columns.substitutions": "@lang('columns.substitutions')",
    "columns.fits": "@lang('columns.fits')",

    /* قيم ثابتة */
    "values.generic": "@lang('values.generic')",

    /* تسميات (للنص داخل الأزرار) */
    "labels.period": "@lang('labels.period')",
    "labels.name": "@lang('labels.name')",
    "labels.qty": "@lang('labels.qty')",
    "labels.callout": "@lang('labels.callout')",
    "labels.match": "@lang('labels.match')",
    "labels.extensions": "@lang('labels.extensions')",
    "labels.number": "@lang('labels.number')",
    "labels.price": "@lang('labels.price')",
    "labels.quick_view": "@lang('labels.quick_view')",
    "labels.substitutions": "@lang('labels.substitutions')",
    "labels.fits": "@lang('labels.fits')",

    /* عناوين شاشات المودال */
    "catalog.modal.name": "@lang('catalog.modal.name')",
    "catalog.quickview.name": "@lang('catalog.quickview.name')",
    "catalog.alternative_modal.name": "@lang('catalog.alternative_modal.name')",
    "catalog.compatibility_modal.name": "@lang('catalog.compatibility_modal.name')",
    "catalog.product_modal.name": "@lang('catalog.product_modal.name')",
    "catalog.offers_modal.name": "@lang('catalog.offers_modal.name')",
    "catalog.modal.back": "@lang('catalog.modal.back')",
    "catalog.modal.loading": "@lang('catalog.modal.loading')",

    /* رسائل */
    "messages.no_matches": "@lang('messages.no_matches')",
    "messages.api_error": "@lang('messages.api_error')",
    "messages.load_failed": "@lang('messages.load_failed')",
    "messages.added_to_cart": "@lang('messages.added_to_cart')",
    "messages.stock_limit": "@lang('messages.stock_limit')",
    "messages.min_qty": "@lang('messages.min_qty')",

    /* مفاتيح الامتدادات */
    "ext.partCode": "@lang('ext.partCode')",
    "ext.market": "@lang('ext.market')",
    "ext.specialNote": "@lang('ext.specialNote')",
    "ext.specCode": "@lang('ext.specCode')",
    "ext.specCodeDesc": "@lang('ext.specCodeDesc')",
    "ext.smPartName": "@lang('ext.smPartName')",
    "ext.bodyColor": "@lang('ext.bodyColor')",
    "ext.trimColor": "@lang('ext.trimColor')",
    "ext.bulb": "@lang('ext.bulb')",
    "ext.size": "@lang('ext.size')",
    "ext.voltWattage": "@lang('ext.voltWattage')",
    "ext.chassisFrom": "@lang('ext.chassisFrom')",
    "ext.chassisTo": "@lang('ext.chassisTo')",
    "ext.genuinePartNumber": "@lang('ext.genuinePartNumber')",
    "ext.vSeriesSpecification": "@lang('ext.vSeriesSpecification')",
    "ext.jisType": "@lang('ext.jisType')",
    "ext.pitworkNonOrderablePart": "@lang('ext.pitworkNonOrderablePart')",
    "ext.pitworkNonTransferablePart": "@lang('ext.pitworkNonTransferablePart')",
    "ext.pitworkModelType": "@lang('ext.pitworkModelType')",
    "ext.applicabilityWithDescription": "@lang('ext.applicabilityWithDescription')",
    "ext.frt": "@lang('ext.frt')",
    "ext.frtCompany": "@lang('ext.frtCompany')",
    "ext.frtWarranty": "@lang('ext.frtWarranty')",
    "ext.frtOperationNo": "@lang('ext.frtOperationNo')",
    "ext.frtOperationName": "@lang('ext.frtOperationName')",
    "ext.frtWorkName": "@lang('ext.frtWorkName')",
    "ext.frtUnit": "@lang('ext.frtUnit')",
    "ext.frtAttribute": "@lang('ext.frtAttribute')",
    "ext.frtRemarks": "@lang('ext.frtRemarks')",
    "ext.frtNotes": "@lang('ext.frtNotes')",
    "ext.ppsPartNumber": "@lang('ext.ppsPartNumber')",
    "ext.jwfPartMasterNextPartNumber": "@lang('ext.jwfPartMasterNextPartNumber')",
    "ext.warrantyPeriods": "@lang('ext.warrantyPeriods')"
};

window.ILL_ROUTES = {
  quick:        "{{ route('modal.quickview',    ['id' => 0]) }}".replace(/0$/, ''),
  catalogItem:  "{{ route('modal.catalog-item', ['key' => 'PART_NUMBER']) }}".replace(/PART_NUMBER$/, ''),
  alternative:  "{{ route('modal.alternative',  ['key' => 'PART_NUMBER']) }}".replace(/PART_NUMBER$/, ''),
  compatibility:"{{ route('modal.compatibility',['key' => 'PART_NUMBER']) }}".replace(/PART_NUMBER$/, '')
};
</script>

{{-- Styles moved to MUAADH.css: Callout Modal Body, Table, Badges, Action Buttons --}}
