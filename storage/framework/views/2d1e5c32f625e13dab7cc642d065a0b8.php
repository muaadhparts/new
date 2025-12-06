


<div
    id="modal"
    class="modal fade"
    tabindex="-1"
    role="dialog"
    aria-hidden="true"
    data-bs-backdrop="static"
    data-bs-keyboard="false"
>
    
    <div class="modal-dialog modal-fullscreen-sm-down modal-xl modal-dialog-centered modal-dialog-scrollable" role="document">
        <div class="modal-content shadow-lg rounded-0 rounded-md-3 border-0">

            
            <div class="modal-header bg-primary text-white border-0 py-2 py-md-3">
                <h5 class="modal-title fs-6 fs-md-5 mb-0">
                    <span id="ill-modal-title">🔧 <?php echo app('translator')->get('catalog.modal.title'); ?></span>
                </h5>

                <div class="d-flex align-items-center gap-1 gap-md-2">
                    <button type="button"
                            id="ill-back-btn"
                            class="btn btn-sm btn-light d-none px-2 py-1 px-md-3 py-md-2"
                            title="<?php echo app('translator')->get('catalog.modal.back'); ?>">
                        <i class="fas fa-arrow-left d-md-none"></i>
                        <span class="d-none d-md-inline">← <?php echo app('translator')->get('catalog.modal.back'); ?></span>
                    </button>
                    <button type="button"
                            class="btn-close btn-close-white"
                            data-bs-dismiss="modal"
                            aria-label="<?php echo app('translator')->get('catalog.modal.close'); ?>"></button>
                </div>
            </div>

            
            <div class="modal-body p-0 bg-light">
                <div id="api-callout-body" class="p-2 p-md-4">
                    <div class="text-center p-3 p-md-5">
                        <div class="spinner-border text-primary mb-3" role="status"></div>
                        <div class="fw-bold text-muted"><?php echo app('translator')->get('catalog.modal.loading'); ?></div>
                    </div>
                </div>
            </div>

            
            <div class="modal-footer bg-light border-0 py-2 py-md-3">
                <small class="text-muted text-center text-md-start w-100">
                    <span id="ill-modal-footnote"><?php echo app('translator')->get('catalog.modal.match_info'); ?></span>
                </small>
            </div>
        </div>
    </div>
</div>


<script>
window.locale = "<?php echo e(app()->getLocale()); ?>";
window.i18n = {
    /* رؤوس الأعمدة */
    "columns.number": "<?php echo app('translator')->get('columns.number'); ?>",
    "columns.callout": "<?php echo app('translator')->get('columns.callout'); ?>",
    "columns.name": "<?php echo app('translator')->get('columns.name'); ?>",
    "columns.qty": "<?php echo app('translator')->get('columns.qty'); ?>",
    "columns.match": "<?php echo app('translator')->get('columns.match'); ?>",
    "columns.extensions": "<?php echo app('translator')->get('columns.extensions'); ?>",
    "columns.price": "<?php echo app('translator')->get('columns.price'); ?>",
    "columns.period": "<?php echo app('translator')->get('columns.period'); ?>",
    "columns.substitutions": "<?php echo app('translator')->get('columns.substitutions'); ?>",
    "columns.fits": "<?php echo app('translator')->get('columns.fits'); ?>",

    /* قيم ثابتة */
    "values.generic": "<?php echo app('translator')->get('values.generic'); ?>",

    /* تسميات (للنص داخل الأزرار) */
    "labels.period": "<?php echo app('translator')->get('labels.period'); ?>",
    "labels.name": "<?php echo app('translator')->get('labels.name'); ?>",
    "labels.qty": "<?php echo app('translator')->get('labels.qty'); ?>",
    "labels.callout": "<?php echo app('translator')->get('labels.callout'); ?>",
    "labels.match": "<?php echo app('translator')->get('labels.match'); ?>",
    "labels.extensions": "<?php echo app('translator')->get('labels.extensions'); ?>",
    "labels.number": "<?php echo app('translator')->get('labels.number'); ?>",
    "labels.price": "<?php echo app('translator')->get('labels.price'); ?>",
    "labels.quick_view": "<?php echo app('translator')->get('labels.quick_view'); ?>",
    "labels.substitutions": "<?php echo app('translator')->get('labels.substitutions'); ?>",
    "labels.fits": "<?php echo app('translator')->get('labels.fits'); ?>",

    /* عناوين شاشات المودال */
    "catalog.modal.title": "<?php echo app('translator')->get('catalog.modal.title'); ?>",
    "catalog.quickview.title": "<?php echo app('translator')->get('catalog.quickview.title'); ?>",
    "catalog.alternative_modal.title": "<?php echo app('translator')->get('catalog.alternative_modal.title'); ?>",
    "catalog.compatibility_modal.title": "<?php echo app('translator')->get('catalog.compatibility_modal.title'); ?>",
    "catalog.product_modal.title": "<?php echo app('translator')->get('catalog.product_modal.title'); ?>",
    "catalog.modal.back": "<?php echo app('translator')->get('catalog.modal.back'); ?>",
    "catalog.modal.loading": "<?php echo app('translator')->get('catalog.modal.loading'); ?>",

    /* رسائل */
    "messages.no_matches": "<?php echo app('translator')->get('messages.no_matches'); ?>",
    "messages.api_error": "<?php echo app('translator')->get('messages.api_error'); ?>",
    "messages.load_failed": "<?php echo app('translator')->get('messages.load_failed'); ?>",
    "messages.added_to_cart": "<?php echo app('translator')->get('messages.added_to_cart'); ?>",
    "messages.stock_limit": "<?php echo app('translator')->get('messages.stock_limit'); ?>",
    "messages.min_qty": "<?php echo app('translator')->get('messages.min_qty'); ?>",

    /* مفاتيح الامتدادات */
    "ext.partCode": "<?php echo app('translator')->get('ext.partCode'); ?>",
    "ext.market": "<?php echo app('translator')->get('ext.market'); ?>",
    "ext.specialNote": "<?php echo app('translator')->get('ext.specialNote'); ?>",
    "ext.specCode": "<?php echo app('translator')->get('ext.specCode'); ?>",
    "ext.specCodeDesc": "<?php echo app('translator')->get('ext.specCodeDesc'); ?>",
    "ext.smPartName": "<?php echo app('translator')->get('ext.smPartName'); ?>",
    "ext.bodyColor": "<?php echo app('translator')->get('ext.bodyColor'); ?>",
    "ext.trimColor": "<?php echo app('translator')->get('ext.trimColor'); ?>",
    "ext.bulb": "<?php echo app('translator')->get('ext.bulb'); ?>",
    "ext.size": "<?php echo app('translator')->get('ext.size'); ?>",
    "ext.voltWattage": "<?php echo app('translator')->get('ext.voltWattage'); ?>",
    "ext.chassisFrom": "<?php echo app('translator')->get('ext.chassisFrom'); ?>",
    "ext.chassisTo": "<?php echo app('translator')->get('ext.chassisTo'); ?>",
    "ext.genuinePartNumber": "<?php echo app('translator')->get('ext.genuinePartNumber'); ?>",
    "ext.vSeriesSpecification": "<?php echo app('translator')->get('ext.vSeriesSpecification'); ?>",
    "ext.jisType": "<?php echo app('translator')->get('ext.jisType'); ?>",
    "ext.pitworkNonOrderablePart": "<?php echo app('translator')->get('ext.pitworkNonOrderablePart'); ?>",
    "ext.pitworkNonTransferablePart": "<?php echo app('translator')->get('ext.pitworkNonTransferablePart'); ?>",
    "ext.pitworkModelType": "<?php echo app('translator')->get('ext.pitworkModelType'); ?>",
    "ext.applicabilityWithDescription": "<?php echo app('translator')->get('ext.applicabilityWithDescription'); ?>",
    "ext.frt": "<?php echo app('translator')->get('ext.frt'); ?>",
    "ext.frtCompany": "<?php echo app('translator')->get('ext.frtCompany'); ?>",
    "ext.frtWarranty": "<?php echo app('translator')->get('ext.frtWarranty'); ?>",
    "ext.frtOperationNo": "<?php echo app('translator')->get('ext.frtOperationNo'); ?>",
    "ext.frtOperationName": "<?php echo app('translator')->get('ext.frtOperationName'); ?>",
    "ext.frtWorkName": "<?php echo app('translator')->get('ext.frtWorkName'); ?>",
    "ext.frtUnit": "<?php echo app('translator')->get('ext.frtUnit'); ?>",
    "ext.frtAttribute": "<?php echo app('translator')->get('ext.frtAttribute'); ?>",
    "ext.frtRemarks": "<?php echo app('translator')->get('ext.frtRemarks'); ?>",
    "ext.frtNotes": "<?php echo app('translator')->get('ext.frtNotes'); ?>",
    "ext.ppsPartNumber": "<?php echo app('translator')->get('ext.ppsPartNumber'); ?>",
    "ext.jwfPartMasterNextPartNumber": "<?php echo app('translator')->get('ext.jwfPartMasterNextPartNumber'); ?>",
    "ext.warrantyPeriods": "<?php echo app('translator')->get('ext.warrantyPeriods'); ?>"
};

window.ILL_ROUTES = {
  quick:        "<?php echo e(route('modal.quickview',    ['id' => 0])); ?>".replace(/0$/, ''),
  product:      "<?php echo e(route('modal.product',      ['key' => 'SKU'])); ?>".replace(/SKU$/, ''),
  alternative:  "<?php echo e(route('modal.alternative',  ['key' => 'SKU'])); ?>".replace(/SKU$/, ''),
  compatibility:"<?php echo e(route('modal.compatibility',['key' => 'SKU'])); ?>".replace(/SKU$/, '')
};
</script>

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
<?php /**PATH C:\Users\hp\Herd\new\resources\views/catalog/partials/callout-modal.blade.php ENDPATH**/ ?>