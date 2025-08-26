<div
    id="modal"
    wire:ignore.self
    class="modal fade"
    tabindex="-1"
    role="dialog"
    aria-hidden="true"
    data-bs-backdrop="static"
    data-bs-keyboard="false"
>
    <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
        <div class="modal-content shadow-lg rounded-3 border-0">
            
            {{-- Header --}}
            <div class="modal-header bg-primary text-white border-0">
                <h5 class="modal-title">
                    <span id="ill-modal-title">🔧 @lang('catalog.modal.title')</span>
                </h5>

                <div class="d-flex align-items-center gap-2">
                    <button type="button"
                            id="ill-back-btn"
                            class="btn btn-sm btn-light d-none"
                            title="@lang('catalog.modal.back')">
                        ← @lang('catalog.modal.back')
                    </button>
                    <button type="button"
                            class="btn-close"
                            data-bs-dismiss="modal"
                            aria-label="@lang('catalog.modal.close')"></button>
                </div>
            </div>

            {{-- Body --}}
            <div class="modal-body p-0 bg-light">
                <div id="api-callout-body" class="p-4">
                    <div class="text-center p-5">
                        <div class="spinner-border text-primary mb-3" role="status"></div>
                        <div class="fw-bold text-muted">@lang('catalog.modal.loading')</div>
                    </div>
                </div>
            </div>

            {{-- Footer --}}
            <div class="modal-footer bg-light border-0">
                <small class="text-muted">
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
    "columns.substitutions": "@lang('columns.substitutions')",   // جديد
    "columns.fits": "@lang('columns.fits')",                     // جديد

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
    "catalog.modal.title": "@lang('catalog.modal.title')",
    "catalog.quickview.title": "@lang('catalog.quickview.title')",
    "catalog.alternative_modal.title": "@lang('catalog.alternative_modal.title')",
    "catalog.compatibility_modal.title": "@lang('catalog.compatibility_modal.title')",
    "catalog.product_modal.title": "@lang('catalog.product_modal.title')",
    "catalog.modal.back": "@lang('catalog.modal.back')",
    "catalog.modal.loading": "@lang('catalog.modal.loading')",

    /* رسائل */
    "messages.no_matches": "@lang('messages.no_matches')",
    "messages.api_error": "@lang('messages.api_error')",
    "messages.load_failed": "@lang('messages.load_failed')",

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
  product:      "{{ route('modal.product',      ['key' => 'SKU']) }}".replace(/SKU$/, ''),
  alternative:  "{{ route('modal.alternative',  ['key' => 'SKU']) }}".replace(/SKU$/, ''),
  compatibility:"{{ route('modal.compatibility',['key' => 'SKU']) }}".replace(/SKU$/, '')
};
</script>

@once
<style id="ill-modal-styles">
  /* جسم المودال */
  #api-callout-body {
      max-height: 70vh;
      overflow: auto;
      padding: 1rem;
      transition: opacity .2s ease-in-out;
  }

  /* الجدول */
  #api-callout-body .table {
      border-radius: .5rem;
      overflow: hidden;
  }
  #api-callout-body .table thead th {
      position: sticky;
      top: 0;
      z-index: 2;
      background: #f1f3f5;
      font-weight: 600;
      color: #333;
  }
  #api-callout-body .table-hover tbody tr:hover {
      background-color: #f8f9fa;
  }
  #api-callout-body .table th,
  #api-callout-body .table td {
      vertical-align: middle;
      text-align: center;
  }

  /* روابط رقم القطعة */
  #api-callout-body .table td:first-child a {
      font-weight: 700;
      text-decoration: none;
  }

  /* شارات */
  #api-callout-body .table .badge {
      font-size: .75rem;
  }

  /* أزرار الأكشن */
  #api-callout-body .ill-actions {
      display: flex;
      gap: .25rem;
      justify-content: center;
  }

  #ill-back-btn.d-none {
      display: none !important;
  }
</style>
@endonce

