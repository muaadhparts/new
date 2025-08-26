(function ($) {
  'use strict';

  /* ========================= Helpers ========================= */
  function qs(key) {
    try { return new URLSearchParams(window.location.search).get(key); } catch { return null; }
  }
  function normKey(v) { return String(v ?? '').trim(); }

  function getLocale() {
    const raw = (window.locale || (typeof document !== 'undefined' ? document.documentElement.lang : '') || '').toLowerCase();
    return raw.startsWith('ar') ? 'ar' : 'en';
  }

  // ✅ لا fallback داخل JS. إمّا من window.i18n أو يرجّع اسم المفتاح نفسه.
  function t(key) {
    const dict = (window.i18n && typeof window.i18n === 'object') ? window.i18n : {};
    return (typeof dict[key] === 'string' && dict[key]) ? dict[key] : key;
  }

  function escapeHtml(s) {
    return String(s)
      .replace(/&/g,'&amp;').replace(/</g,'&lt;')
      .replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#039;');
  }

  function localizedPartName(p) {
    const en = p.part_label_en || '', ar = p.part_label_ar || '';
    return getLocale() === 'ar' ? (ar || en || '—') : (en || ar || '—');
  }

  // YYYY-MM فقط (يحذف اليوم إن وُجد)
  function formatYearMonth(s) {
    if (s == null) return '';
    const raw = String(s).trim(); if (!raw) return '';
    const d = raw.replace(/[^0-9]/g,'');
    if (d.length >= 6) {
      const y = d.slice(0,4), m = d.slice(4,6);
      if (/^(19|20)\d{2}$/.test(y) && /^([0][1-9]|1[0-2])$/.test(m)) return `${y}-${m}`;
    }
    if (d.length === 4) return d;
    return raw;
  }
  function formatPeriodRange(b, e) {
    const from = formatYearMonth(b), to = formatYearMonth(e);
    return [from, to].filter(Boolean).join(' → ');
  }

  // امتدادات كبادجات
  function renderExtensions(ext) {
    if (!ext) return '—';
    if (typeof ext === 'string') {
      try { const obj = JSON.parse(ext); return renderExtensions(obj); } catch {}
      return '—';
    }
    if (typeof ext === 'object' && !Array.isArray(ext)) {
      const keys = Object.keys(ext); if (!keys.length) return '—';
      return keys.map(k => {
        const label = t(`ext.${k}`);
        const val = (ext[k] == null) ? '' : String(ext[k]);
        return `<span class="badge bg-light text-dark me-1">${escapeHtml(label)}: ${escapeHtml(val)}</span>`;
      }).join(' ');
    }
    if (Array.isArray(ext)) {
      if (!ext.length) return '—';
      return ext.map(it => {
        const k = (it && (it.extension_key || it.key)) ? String(it.extension_key || it.key) : '';
        const v = (it && (it.extension_value || it.value)) ? String(it.extension_value || it.value) : '';
        if (!k && !v) return '';
        const label = t(`ext.${k}`);
        return `<span class="badge bg-light text-dark me-1">${escapeHtml(label)}: ${escapeHtml(v)}</span>`;
      }).filter(Boolean).join(' ') || '—';
    }
    return '—';
  }

  /* ========================= Globals from Blade ========================= */
  const section   = window.sectionData  || null;
  const category  = window.categoryData || null;
  const brandName = window.brandName    || null;
  const callouts  = Array.isArray(window.calloutsFromDB) ? window.calloutsFromDB : [];
  const byKey = callouts.reduce((m, it) => {
    const k1 = normKey(it.callout_key), k2 = normKey(it.callout);
    if (k1) m[k1] = it; if (k2) m[k2] = it; return m;
  }, {});

  /* ========================= Modal Elements ========================= */
  const stack = []; // كل عنصر يمثل "شاشة حالية"؛ أعلى المكدس = الشاشة المعروضة الآن
  function modalTitleEl() { return document.getElementById('ill-modal-title'); }
  function modalBodyEl()  { return document.getElementById('api-callout-body'); }
  function backBtnEl()    { return document.getElementById('ill-back-btn'); }
  function getCurrentTitle() {
    const el = modalTitleEl(); return el ? (el.textContent || '') : '';
  }

  function setTitle(txt) {
    const el = modalTitleEl(); if (el) el.textContent = txt;
  }
  function setBackVisible() {
    const btn = backBtnEl(); if (!btn) return;
    const hasHistory = stack.length > 1;
    btn.classList.toggle('d-none', !hasHistory);
    btn.setAttribute('aria-disabled', hasHistory ? 'false' : 'true');
    btn.disabled = !hasHistory;
    btn.tabIndex = hasHistory ? 0 : -1;
  }

  // نحفظ أيضًا موضع التمرير لاستعادته عند الرجوع
  function pushView(state) {
    const body = modalBodyEl();
    const scroll = body ? body.scrollTop : 0;
    stack.push({ title: state.title || '', html: state.html || '', __scroll: scroll });
    setBackVisible();
  }
  function currentView() {
    return stack[stack.length - 1] || null;
  }
  function popView() {
    if (stack.length <= 1) { setBackVisible(); return; }
    // أزل الشاشة الحالية
    stack.pop();
    const st = currentView();
    if (st && st.html != null) {
      const body = modalBodyEl();
      setTitle(st.title || t('catalog.modal.title'));
      if (body) {
        body.innerHTML = st.html;
        afterInject(body);
        body.scrollTop = st.__scroll || 0;
      }
    }
    setBackVisible();
  }
  $(document).off('click.ill_back').on('click.ill_back', '#ill-back-btn', function (e) {
    e.preventDefault();
    if (stack.length > 1) popView();
  });

  function afterInject(container) {
    // دعم Livewire (بدون رمي أخطاء)
    try {
      if (window.Livewire && typeof window.Livewire.rescan === 'function') {
        window.Livewire.rescan(container);
      } else if (window.livewire && typeof window.livewire.rescan === 'function') {
        window.livewire.rescan();
      }
    } catch (e) {}

    // إعادة تنفيذ سكربتات HTML المُحمّلة
    try {
      const scripts = container.querySelectorAll('script');
      scripts.forEach(s => {
        const n = document.createElement('script');
        if (s.src) { n.src = s.src; } else { n.textContent = s.textContent; }
        document.body.appendChild(n);
        setTimeout(() => n.remove(), 0);
      });
    } catch (e) {}

    // اربط أحداث العناصر الديناميكية
    bindDynamicEvents();
  }

  function renderSpinner() {
    const text = t('catalog.modal.loading');
    return `
      <div class="text-center p-5" aria-busy="true">
        <div class="spinner-border text-primary mb-3" role="status" aria-live="polite"></div>
        <div class="fw-bold text-muted">${escapeHtml(text)}</div>
      </div>`;
  }

  /**
   * ✅ نموذج ملاحة صحيح:
   * - لا ندفع "الشاشة السابقة" إلى المكدس.
   * - عند نجاح التحميل: ندفع "الشاشة الجديدة" نفسها إلى المكدس (تصبح الحالية).
   * - عند الفشل: نعيد الحالة السابقة كما هي (لا تغيير في المكدس).
   */
  function loadIntoModal(url, title) {
    const body = modalBodyEl(); if (!body) return Promise.resolve();
    const prevTitle = getCurrentTitle();
    const prevHtml  = body.innerHTML;

    // أظهر سبينر موقتًا بعنوان الشاشة الجديدة
    setTitle(title);
    body.innerHTML = renderSpinner();

    return fetch(url, { headers: { 'X-Requested-With':'XMLHttpRequest' } })
      .then(res => { if (!res.ok) throw new Error(`HTTP ${res.status}`); return res.text(); })
      .then(html => {
        const tmp = document.createElement('div'); tmp.innerHTML = html;
        const inner = tmp.querySelector('.modal-body') || tmp.querySelector('#content') || tmp;
        const newHtml = inner.innerHTML || html;

        // اعرض المحتوى الجديد
        body.innerHTML = newHtml;
        afterInject(body);
        body.scrollTop = 0;

        // ✅ سجل "الشاشة الجديدة" كأعلى المكدس (الحالية)
        pushView({ title, html: newHtml });
      })
      .catch(err => {
        // ⚠️ فشل التحميل: أعد الحالة السابقة كما هي + أظهر رسالة
        setTitle(prevTitle);
        body.innerHTML = prevHtml;
        const msg = t('messages.load_failed');
        if (window.toastr) {
          window.toastr.error(`${msg}: ${err.message || err}`);
        } else {
          try { alert(`${msg}\n${err.message || err}`); } catch (_) {}
        }
      })
      .finally(() => setBackVisible());
  }

  /* ========================= Table Renderer ========================= */
  function renderProducts(products){
    if(!Array.isArray(products)||products.length===0){
      const noData=t('messages.no_matches');
      return `<div class="text-center p-5 text-muted"><i class="bi bi-search display-6"></i><div class="mt-3 fw-bold">${escapeHtml(noData)}</div></div>`;
    }

    // helpers: normalize lists and render badges
    const splitToList = (s)=> String(s||'').split(/[,\n;|]+/).map(v=>v.trim()).filter(Boolean);

    function normListFromAny(input, kind){
      if(input==null) return [];
      if(Array.isArray(input)){
        return input.map(it=>{
          if(it==null) return '';
          if(typeof it==='string') return it.trim();
          if(kind==='subs'){
            const cand = it.part_number ?? it.number ?? it.sku ?? it.code ?? it.alt ?? it.key ?? '';
            return String(cand).trim();
          }else{ // fits
            const model  = it.model ?? it.name ?? it.vehicle ?? '';
            const year   = it.year ?? it.years ?? it.model_year ?? '';
            const engine = it.engine ?? it.engine_code ?? '';
            const trim   = it.trim ?? '';
            const label = [model,year,engine,trim].map(x=>String(x||'').trim()).filter(Boolean).join(' ');
            return (label || String(it.code ?? it.id ?? '').trim());
          }
        }).filter(Boolean);
      }
      if(typeof input==='object'){
        return Object.values(input).map(v=>String(v||'').trim()).filter(Boolean);
      }
      if(typeof input==='string'){
        return splitToList(input);
      }
      return [];
    }

    const renderBadges = (arr)=> arr.length
      ? `<div class="d-flex flex-wrap gap-1">${arr.map(v=>`<span class="badge bg-light text-dark">${escapeHtml(v)}</span>`).join('')}</div>`
      : '—';

    const rows=products.map(p=>{
      const name=localizedPartName(p);
      const qty=(p.part_qty ?? '—');
      const mv=Array.isArray(p.match_values)?p.match_values:(typeof p.match_values==='string'?p.match_values.split(',').map(s=>s.trim()).filter(Boolean):[]);
      const match = mv.length?`✅ <small>${mv.map(v=>escapeHtml(v)).join(', ')}</small>`:`<span class="badge bg-light text-dark">${escapeHtml(t('values.generic'))}</span>`;
      const period=formatPeriodRange(p.part_begin, p.part_end);
      const callout=p.part_callout?escapeHtml(p.part_callout):'—';
      const exts=renderExtensions(p.extensions);

      const qvBtn=(p.store_id||p.quick_view)
        ? `<button type="button" class="btn btn-sm btn-outline-primary quick-view mt-1"
             data-id="${p.store_id||''}" data-sku="${escapeHtml(p.part_number||'')}" data-url="${escapeHtml(p.quick_view||'')}">
             ${escapeHtml(t('labels.quick_view'))}
           </button>`
        : '';
      const partLink=`<a href="javascript:;" class="text-decoration-none text-primary fw-bold part-link"
                         data-sku="${escapeHtml(p.part_number||'')}" data-id="${p.store_id||''}" data-url="${escapeHtml(p.quick_view||'')}">
                         ${escapeHtml(p.part_number||'')}
                      </a>`;
      const numberCell = qvBtn ? `${partLink}<div>${qvBtn}</div>` : partLink;

      const subsList = normListFromAny(p.substitutions ?? p.alternatives ?? p.alt ?? p.subs, 'subs');
      const fitsList = normListFromAny(p.fits ?? p.compatible ?? p.vehicles ?? p.fitVehicles, 'fits');

      const subsMore = p.part_number
        ? `<div class="mt-1"><a href="javascript:;" class="small text-decoration-underline alt-link" data-sku="${escapeHtml(p.part_number||'')}">${escapeHtml(t('labels.substitutions'))}</a></div>`
        : '';
      const fitsMore = p.part_number
        ? `<div class="mt-1"><a href="javascript:;" class="small text-decoration-underline fits-link" data-sku="${escapeHtml(p.part_number||'')}">${escapeHtml(t('labels.fits'))}</a></div>`
        : '';

      const subsCell = `${renderBadges(subsList)}${subsMore}`;
      const fitsCell = `${renderBadges(fitsList)}${fitsMore}`;

      return `<tr class="${mv.length?'':'table-secondary'}">
        <td>${numberCell}</td>
        <td>${callout}</td>
        <td>${escapeHtml(qty)}</td>
        <td>${escapeHtml(name)}</td>
        <td>${match}</td>
        <td>${exts}</td>
        <td><span class="badge bg-light text-dark">${period||'—'}</span></td>
        <td>${fitsCell}</td>
        <td>${subsCell}</td>
      </tr>`;
    }).join('');

    // Desktop table
    const desktop = `
      <div class="d-none d-md-block">
        <table class="table table-hover text-center align-middle">
          <thead class="table-light">
            <tr>
              <th>${escapeHtml(t('columns.number'))}</th>
              <th>${escapeHtml(t('columns.callout'))}</th>
              <th>${escapeHtml(t('columns.qty'))}</th>
              <th>${escapeHtml(t('columns.name'))}</th>
              <th>${escapeHtml(t('columns.match'))}</th>
              <th>${escapeHtml(t('columns.extensions'))}</th>
              <th>${escapeHtml(t('columns.period'))}</th>
              <th>${escapeHtml(t('columns.fits'))}</th>
              <th>${escapeHtml(t('columns.substitutions'))}</th>
            </tr>
          </thead>
          <tbody>${rows}</tbody>
        </table>
      </div>`;

    // Mobile cards (نفس الأعمدة)
    const mobile = `
      <div class="d-block d-md-none">
        ${products.map(p=>{
          const name=localizedPartName(p);
          const qty=(p.part_qty ?? '—');
          const mv=Array.isArray(p.match_values)?p.match_values:(typeof p.match_values==='string'?p.match_values.split(',').map(s=>s.trim()).filter(Boolean):[]);
          const match=mv.length?`✅ ${mv.map(v=>escapeHtml(v)).join(', ')}`:`<span class="badge bg-light text-dark">${escapeHtml(t('values.generic'))}</span>`;
          const period=formatPeriodRange(p.part_begin,p.part_end);
          const callout=p.part_callout?escapeHtml(p.part_callout):'—';
          const exts=renderExtensions(p.extensions);
          const qvBtn=(p.store_id||p.quick_view)?`<button type="button" class="btn btn-sm btn-outline-primary quick-view mt-2"
                 data-id="${p.store_id||''}"
                 data-sku="${escapeHtml(p.part_number||'')}"
                 data-url="${escapeHtml(p.quick_view||'')}">${escapeHtml(t('labels.quick_view'))}</button>`:'';
          const partLink=`<a href="javascript:;" class="text-decoration-none text-primary part-link"
                          data-sku="${escapeHtml(p.part_number||'')}"
                          data-id="${p.store_id||''}"
                          data-url="${escapeHtml(p.quick_view||'')}">🔢 ${escapeHtml(p.part_number||'')}</a>`;

          const subsList = normListFromAny(p.substitutions ?? p.alternatives ?? p.alt ?? p.subs, 'subs');
          const fitsList = normListFromAny(p.fits ?? p.compatible ?? p.vehicles ?? p.fitVehicles, 'fits');
          const subsMore = p.part_number ? `<div class="mt-2"><a href="javascript:;" class="small text-decoration-underline alt-link" data-sku="${escapeHtml(p.part_number||'')}">${escapeHtml(t('labels.substitutions'))}</a></div>` : '';
          const fitsMore = p.part_number ? `<div class="mt-2"><a href="javascript:;" class="small text-decoration-underline fits-link" data-sku="${escapeHtml(p.part_number||'')}">${escapeHtml(t('labels.fits'))}</a></div>` : '';

          return `<div class="card shadow-sm mb-3"><div class="card-body">
              <h6 class="card-title">${partLink}</h6>
              <p><strong>${escapeHtml(t('labels.callout'))}:</strong> ${callout}</p>
              <p><strong>${escapeHtml(t('labels.qty'))}:</strong> ${escapeHtml(qty)}</p>
              <p><strong>${escapeHtml(t('labels.name'))}:</strong> ${escapeHtml(name)}</p>
              <p><strong>${escapeHtml(t('labels.match'))}:</strong> ${match}</p>
              <p><strong>${escapeHtml(t('labels.extensions'))}:</strong> ${exts}</p>
              <p><strong>${escapeHtml(t('labels.period'))}:</strong> <span class="badge bg-light text-dark">${period||'—'}</span></p>
              <p><strong>${escapeHtml(t('columns.fits'))}:</strong> ${renderBadges(fitsList)}</p>
              ${fitsMore}
              <p><strong>${escapeHtml(t('columns.substitutions'))}:</strong> ${renderBadges(subsList)}</p>
              ${subsMore}
              <div class="mt-2">${qvBtn}</div>
          </div></div>`;
        }).join('')}
      </div>`;

    return desktop + mobile;
  }

  /* ========================= API ========================= */
  async function fetchCalloutData(calloutKey) {
    const params = new URLSearchParams({
      section_id   : section?.id,
      category_id  : category?.id,
      catalog_code : category?.catalog?.code || category?.catalog_code || '',
      callout      : calloutKey,
    });
    const res = await fetch(`/api/callouts?${params.toString()}`, { headers:{ 'Accept':'application/json' } });
    if (!res.ok) throw new Error(`API error ${res.status}`);
    return await res.json();
  }

  /* ========================= Section Navigation ========================= */
  function goToSection(sectionKey2) {
    const catalogCode = category?.catalog?.code || category?.catalog_code || '';
    const key1 = category?.parents_key || category?.parentsKey || '';
    const key2 = String(sectionKey2 || '');
    if (!brandName || !catalogCode || !key1 || !key2) return;
    const url = `/catlogs/${encodeURIComponent(brandName)}/${encodeURIComponent(catalogCode)}/${encodeURIComponent(key1)}/${encodeURIComponent(key2)}`;
    window.location.href = url;
  }

  /* ========================= Open Callout ========================= */
  function openCallout(partOrKey){
    let key='', type='part';
    if (typeof partOrKey==='object' && partOrKey){
      key  = partOrKey.callout_key || partOrKey.callout || '';
      type = partOrKey.callout_type || 'part';
    } else {
      key = String(partOrKey||'');
      const found = byKey[key]; if (found && found.callout_type) type = found.callout_type;
    }
    if (type === 'section'){ goToSection(key); return; }

    const container = modalBodyEl();
    try { $('#modal').modal('show'); } catch {}
    const titleRoot = t('catalog.modal.title');
    setTitle(titleRoot);

    // بداية جديدة
    if (container) container.innerHTML = renderSpinner();
    stack.length = 0;
    setBackVisible();

    fetchCalloutData(key).then(data=>{
      const body = modalBodyEl(); if (!body) return;
      if (!data.ok){
        const msg = t('messages.api_error');
        body.innerHTML = `<div class="alert alert-danger">${escapeHtml(msg)}: ${escapeHtml(data.error||'')}</div>`;
        setBackVisible();
        return;
      }
      const prods = data.products || [];

      // اعرض الجدول ثم سجّل "الجذر" كأول شاشة في المكدس
      const html = renderProducts(prods);
      body.innerHTML = html;
      afterInject(body);
      body.scrollTop = 0;

      pushView({ title: titleRoot, html });
      setBackVisible(); // مخفي لأن length=1
    }).catch(err=>{
      const body = modalBodyEl(); if (!body) return;
      const msg  = t('messages.load_failed');
      body.innerHTML = `<div class="alert alert-danger">${escapeHtml(msg)}: ${escapeHtml(err?.message||String(err))}</div>`;
      setBackVisible();
    });
  }

  /* ========================= Inline Sub-Views (each pushes a new state) ========================= */
  function openQuickInline(id, url, sku) {
    const base  = window.ILL_ROUTES?.quick || '/item/quick/view/';
    const title = t('catalog.quickview.title');
    const finalUrl = (url && typeof url === 'string') ? url : (id ? (base + id) : null);
    if (!finalUrl && sku) { return openProductInline(sku); }
    return loadIntoModal(finalUrl, title);
  }
  function openProductInline(key) {
    const base  = window.ILL_ROUTES?.product || '/modal/product/';
    const title = t('catalog.product_modal.title');
    return loadIntoModal(base + encodeURIComponent(key), title);
  }
  function openAlternativeInline(sku) {
    const base  = window.ILL_ROUTES?.alternative || '/modal/alternative/';
    const title = t('catalog.alternative_modal.title');
    return loadIntoModal(base + encodeURIComponent(sku), title);
  }
  function openCompatibilityInline(sku) {
    const base  = window.ILL_ROUTES?.compatibility || '/modal/compatibility/';
    const title = t('catalog.compatibility_modal.title');
    return loadIntoModal(base + encodeURIComponent(sku), title);
  }

  /* ========================= Dynamic Events ========================= */
  function bindDynamicEvents() {
    /* فتح الكول آوت من الصورة */
    $(document).off('click.ill_open').on('click.ill_open', '.callout-label', function () {
      const type = ($(this).data('calloutType') || 'part').toString().toLowerCase();
      const key  = ($(this).data('calloutKey')  || '').toString();
      if (type === 'section') { goToSection(key); return; }
      if (key) openCallout(key);
    });

    /* رقم القطعة: من النتائج الرئيسية → افتح البدائل أولاً. داخل شاشة البدائل → السلوك المعتاد */
    $(document).off('click.ill_partlink').on('click.ill_partlink', '.part-link', function (e) {
      e.preventDefault();
      const $inAlt = $(this).closest('.ill-alt').length > 0;
      const sku = $(this).data('sku');
      const id  = $(this).data('id');
      const url = $(this).data('url');

      if (!$inAlt && sku) { openAlternativeInline(sku); return; }
      if (id || url) { openQuickInline(id, url, sku); }
      else if (sku)  { openProductInline(sku); }
    });

    /* زر "عرض سريع": من النتائج الرئيسية → البدائل أولاً. من البدائل → كويك فيو فوقها */
    $(document).off('click.ill_quick').on('click.ill_quick', '.quick-view', function (e) {
      e.preventDefault();
      const $inAlt = $(this).closest('.ill-alt').length > 0;
      const sku = $(this).data('sku');
      const id  = $(this).data('id');
      const url = $(this).data('url');

      if (!$inAlt && sku) { openAlternativeInline(sku); return; }
      openQuickInline(id, url, sku);
    });

    /* رابط البدائل */
    $(document).off('click.ill_alt').on('click.ill_alt', '.alt-link', function (e) {
      e.preventDefault();
      openAlternativeInline($(this).data('sku'));
    });

    /* رابط المركبات المناسبة */
    $(document).off('click.ill_fits').on('click.ill_fits', '.fits-link', function (e) {
      e.preventDefault();
      openCompatibilityInline($(this).data('sku'));
    });

    /* ============== أزرار السلة ============== */

    // إضافة إلى السلة (يبقى داخل المودال)
    $(document).off('click.ill_addnum').on('click.ill_addnum', '.ill-add-to-cart', function (e) {
      e.preventDefault();
      const btn = this;
      const id  = $(btn).data('id');
      if (!id) { console.warn('ill-add-to-cart: missing data-id'); return; }

      // كمية إن وُجدت داخل بطاقة المنتج، وإلا = 1 (جدول البدائل)
      const $root = $(btn).closest('.ill-product');
      let qty = 1;
      const $qty = $root.find('.ill-qty');
      if ($qty.length) {
        const q = parseInt($qty.val(), 10);
        if (!isNaN(q) && q > 0) qty = q;
      }

      const addUrl = $(btn).data('addnumUrl') || $(btn).data('addnum-url') || '/addnumcart';
      const url    = `${addUrl}?id=${encodeURIComponent(id)}&qty=${encodeURIComponent(qty)}`;

      btn.disabled = true;
      fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(r => r.ok ? r.json() : Promise.reject(new Error(`HTTP ${r.status}`)))
        .then(data => {
          // تحديث عدّاد السلة إن وُجد
          try {
            const count = Array.isArray(data) ? data[0] : (data.count ?? null);
            const badge = document.querySelector('[data-cart-count], #cart-count, .header-cart-count, .cart-count');
            if (badge != null && count != null) badge.textContent = count;
          } catch (_) {}
          const ok = t('messages.added_to_cart');
          if (window.toastr) toastr.success(ok); else alert(ok);
        })
        .catch(err => {
          const msg = t('messages.api_error');
          if (window.toastr) toastr.error(`${msg} ${err.message || err}`); else alert(`${msg}\n${err.message || err}`);
        })
        .finally(() => { btn.disabled = false; });
    });

    // شراء الآن: GET إلى /addtonumcart ثم المتصفح يذهب تلقائيًا للسلة
    $(document).off('click.ill_buynow').on('click.ill_buynow', '.ill-buy-now', function (e) {
      e.preventDefault();
      const btn = this;
      const id  = $(btn).data('id');
      if (!id) { console.warn('ill-buy-now: missing data-id'); return; }

      const $root = $(btn).closest('.ill-product');
      let qty = 1;
      const $qty = $root.find('.ill-qty');
      if ($qty.length) {
        const q = parseInt($qty.val(), 10);
        if (!isNaN(q) && q > 0) qty = q;
      }

      const addUrl = $(btn).data('addtonumUrl') || $(btn).data('addtonum-url') || '/addtonumcart';
      window.location.href = `${addUrl}?id=${encodeURIComponent(id)}&qty=${encodeURIComponent(qty)}`;
    });
  }

  /* ========================= Landmarks & Hover ========================= */
  function addLandmarks() {
    if (window.__ill_addedLandmarks) return;
    window.__ill_addedLandmarks = true;
    const $img = $('#image');
    callouts.forEach(item => {
      const left = item.rectangle_left ?? item.left ?? 0;
      const top  = item.rectangle_top  ?? item.top  ?? 0;
      const key  = normKey(item.callout_key || item.callout || item.code || '');
      const type = (item.callout_type || item.type || 'part').toLowerCase();
      const html = `
        <div class="item lable lable-single pointer correct-callout callout-label"
             data-callout-key="${String(key)}"
             data-callout-type="${String(type)}"
             data-container="body"
             data-allow-scale="true"
             data-size="150px,30px"
             data-position="${left},${top}">
          <div class="bbdover" id="part_${item.index || item.id || ''}" data-codeonimage="${String(key)}"
               style="position:absolute;width:150px;height:30px;background-color:transparent;opacity:0.7;"></div>
        </div>`;
      try { $img.smoothZoom('addLandmark', [html]); } catch (e) { console.warn('addLandmark error', e); }
    });
  }
  function bindHover() {
    if (window.__ill_hoverBound) return;
    window.__ill_hoverBound = true;
    $(document)
      .on('mouseenter', '.bbdover', function () {
        const code = $(this).data('codeonimage');
        $(this).addClass('hovered');
        $(`.bbdover[data-codeonimage="${code}"]`).addClass('hovered');
      })
      .on('mouseleave', '.bbdover', function () {
        const code = $(this).data('codeonimage');
        $(this).removeClass('hovered');
        $(`.bbdover[data-codeonimage="${code}"]`).removeClass('hovered');
      });
  }

  /* ========================= Zoom Init & Auto Open ========================= */
  function initZoom() {
    const $img = $('#image'); if (!$img.length) return;
    $img.smoothZoom({
      width:800, height:500, responsive:true, container:'zoom_container',
      responsive_maintain_ratio:true, zoom_SINGLE_STEP:false,
      animation_SMOOTHNESS:3, animation_SPEED_ZOOM:3, animation_SPEED_PAN:3,
      initial_POSITION:'200, 300', zoom_MAX:200, button_SIZE:20,
      button_AUTO_HIDE:'YES', button_AUTO_HIDE_DELAY:2, button_ALIGN:'top right',
      mouse_DOUBLE_CLICK:false, mouse_WHEEL:true, use_3D_Transform:true, border_TRANSPARENCY:0,
      on_IMAGE_LOAD:function(){ addLandmarks(); autoOpen(); }
    });
  }
  function autoOpen() {
    if (window.__ill_autoOpened) return;
    const calloutKey = qs('callout'); const autoFlag = qs('auto_open');
    if (!(calloutKey && (autoFlag === '1' || autoFlag === 'true'))) return;
    window.__ill_autoOpened = true;
    const found = byKey[calloutKey];
    if (found && String(found.callout_type || '').toLowerCase() === 'section') { goToSection(calloutKey); }
    else { openCallout(calloutKey); }
  }

  /* ========================= Boot ========================= */
  $(function () {
    initZoom(); bindHover(); bindDynamicEvents();
    const imgEl = document.getElementById('image');
    if (imgEl && imgEl.complete) { addLandmarks(); autoOpen(); }

    // نظّم حالة زر الرجوع عند التحميل
    setBackVisible();

    // عند إغلاق المودال: صفّر المكدس
    $(document).off('hidden.bs.modal.ill').on('hidden.bs.modal.ill', '#modal', function () {
      stack.length = 0;
      setBackVisible();
    });
  });

  // API: لتفعيل الفتح من أماكن أخرى
  window.openCallout = openCallout;

})(jQuery);
