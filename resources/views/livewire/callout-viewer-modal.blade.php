
{{-- resources\views\livewire\callout-viewer-modal.blade.php --}}

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
    {{-- Responsive Modal Dialog --}}
    <div class="modal-dialog modal-fullscreen-sm-down modal-xl modal-dialog-centered modal-dialog-scrollable" role="document">
        <div class="modal-content shadow-lg rounded-0 rounded-md-3 border-0">

            {{-- Header - Responsive --}}
            <div class="modal-header bg-primary text-white border-0 py-2 py-md-3">
                <h5 class="modal-title fs-6 fs-md-5 mb-0">
                    <span id="ill-modal-title">🔧 @lang('catalog.modal.title')</span>
                </h5>

                <div class="d-flex align-items-center gap-1 gap-md-2">
                    <button type="button"
                            id="ill-back-btn"
                            class="btn btn-sm btn-light d-none px-2 py-1 px-md-3 py-md-2"
                            title="@lang('catalog.modal.back')">
                        <i class="fas fa-arrow-left d-md-none"></i>
                        <span class="d-none d-md-inline">← @lang('catalog.modal.back')</span>
                    </button>
                    <button type="button"
                            class="btn-close btn-close-white"
                            data-bs-dismiss="modal"
                            aria-label="@lang('catalog.modal.close')"></button>
                </div>
            </div>

            {{-- Body - Responsive Padding --}}
            <div class="modal-body p-0 bg-light">
                <div id="api-callout-body" class="p-2 p-md-4">
                    <div class="text-center p-3 p-md-5">
                        <div class="spinner-border text-primary mb-3" role="status"></div>
                        <div class="fw-bold text-muted">@lang('catalog.modal.loading')</div>
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
    "messages.added_to_cart": "@lang('messages.added_to_cart')",

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
  /* جسم المودال - Responsive */
  #api-callout-body {
      max-height: 70vh;
      overflow: auto;
      padding: 1rem;
      transition: opacity .2s ease-in-out;
  }

  @media (max-width: 768px) {
      #api-callout-body {
          max-height: 80vh;
          padding: 0.5rem;
      }
  }

  /* الجدول - Responsive */
  #api-callout-body .table {
      border-radius: .5rem;
      overflow: hidden;
      font-size: 0.875rem;
  }

  @media (max-width: 768px) {
      #api-callout-body .table {
          font-size: 0.75rem;
          border-radius: 0;
      }

      /* تصغير Padding للخلايا على الموبايل */
      #api-callout-body .table th,
      #api-callout-body .table td {
          padding: 0.25rem 0.15rem !important;
          font-size: 0.7rem;
      }

      /* إخفاء بعض الأعمدة على الشاشات الصغيرة جداً */
      @media (max-width: 576px) {
          #api-callout-body .table .d-sm-none {
              display: none !important;
          }
      }
  }

  #api-callout-body .table thead th {
      position: sticky;
      top: 0;
      z-index: 2;
      background: #f1f3f5;
      font-weight: 600;
      color: #333;
      white-space: nowrap;
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
      font-size: 0.85rem;
  }

  @media (max-width: 768px) {
      #api-callout-body .table td:first-child a {
          font-size: 0.7rem;
      }
  }

  /* شارات - Responsive */
  #api-callout-body .table .badge {
      font-size: .75rem;
  }

  @media (max-width: 768px) {
      #api-callout-body .table .badge {
          font-size: 0.6rem;
          padding: 0.15em 0.4em;
      }
  }

  /* أزرار الأكشن - Responsive */
  #api-callout-body .ill-actions {
      display: flex;
      gap: .25rem;
      justify-content: center;
      flex-wrap: wrap;
  }

  @media (max-width: 768px) {
      #api-callout-body .ill-actions {
          gap: 0.15rem;
      }

      #api-callout-body .ill-actions .btn {
          font-size: 0.65rem;
          padding: 0.2rem 0.4rem;
      }

      #api-callout-body .ill-actions .btn i {
          font-size: 0.7rem;
      }
  }

  #ill-back-btn.d-none {
      display: none !important;
  }

  /* Modal Footer - Responsive */
  @media (max-width: 768px) {
      .modal-footer small {
          font-size: 0.7rem;
      }
  }
</style>
@endonce

