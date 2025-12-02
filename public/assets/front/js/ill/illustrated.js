(function ($) {
  'use strict';

  // console.log('🚀 illustrated.js loaded - Version 3.0.0 - API Optimized');

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
    // نُبقي fallback الاسم كما هو؛ المطلوب كان إخفاء الشرطات في أعمدة أخرى
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
    // ⛳️ تغيّر: لا نُظهر "—" عند عدم وجود بيانات
    if (!ext) return '';
    if (typeof ext === 'string') {
      try { const obj = JSON.parse(ext); return renderExtensions(obj); } catch {}
      return '';
    }
    if (typeof ext === 'object' && !Array.isArray(ext)) {
      const keys = Object.keys(ext); if (!keys.length) return '';
      return keys.map(k => {
        const label = t(`ext.${k}`);
        const val = (ext[k] == null) ? '' : String(ext[k]);
        if (!val) return '';
        return `<span class="badge bg-light text-dark me-1">${escapeHtml(label)}: ${escapeHtml(val)}</span>`;
      }).filter(Boolean).join(' ');
    }
    if (Array.isArray(ext)) {
      if (!ext.length) return '';
      return ext.map(it => {
        const k = (it && (it.extension_key || it.key)) ? String(it.extension_key || it.key) : '';
        const v = (it && (it.extension_value || it.value)) ? String(it.extension_value || it.value) : '';
        if (!k && !v) return '';
        const label = t(`ext.${k}`);
        return v ? `<span class="badge bg-light text-dark me-1">${escapeHtml(label)}: ${escapeHtml(v)}</span>` : '';
      }).filter(Boolean).join(' ');
    }
    return '';
  }

  /* ========================= Context from Blade (New Optimized Method) ========================= */
  const ctx = window.catalogContext || {};
  const sectionId   = ctx.sectionId   || null;
  const categoryId  = ctx.categoryId  || null;
  const catalogCode = ctx.catalogCode || '';
  const brandName   = ctx.brandName   || '';

  // console.log('✅ Using NEW optimized method - fetching from API');
  // console.log('📦 Context loaded:', { sectionId, categoryId, catalogCode, brandName });

  // Cache للبيانات
  let cachedCallouts = [];
  let byKey = {};
  let metadataLoaded = false;

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
  function renderProducts(products, pagination = null){
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

    // ⛳️ تغيّر: لا نعرض "—" لو فاضي
    const renderBadges = (arr)=> arr.length
      ? `<div class="d-flex flex-wrap gap-1">${arr.map(v=>`<span class="badge bg-light text-dark">${escapeHtml(v)}</span>`).join('')}</div>`
      : '';

    const rows=products.map(p=>{
      const name=localizedPartName(p);

      // ⛳️ تغيّر: إزالة fallback "—" للخلايا الفارغة
      const qty = (p.part_qty != null && String(p.part_qty).trim() !== '') ? escapeHtml(p.part_qty) : '';
      const mv = Array.isArray(p.match_values)
        ? p.match_values
        : (typeof p.match_values==='string' ? p.match_values.split(',').map(s=>s.trim()).filter(Boolean) : []);
      const match = mv.length
        ? `<small>${mv.map(v=>escapeHtml(v)).join(', ')}</small>`
        : `<span class="badge bg-light text-dark">${escapeHtml(t('values.generic'))}</span>`;

      const period = formatPeriodRange(p.part_begin, p.part_end);
      const periodCell = period ? `<span class="badge bg-light text-dark">${period}</span>` : '';

      const callout = (p.part_callout != null && String(p.part_callout).trim() !== '') ? escapeHtml(p.part_callout) : '';
      const exts = renderExtensions(p.extensions);

      const qvBtn=(p.store_id||p.quick_view)
        ? `<button type="button" class="btn btn-sm btn-outline-primary quick-view mt-1"
             data-id="${p.store_id||''}"
             data-sku="${escapeHtml(p.part_number||'')}"
             data-url="${escapeHtml(p.quick_view||'')}"
             data-user="${escapeHtml(p.user_id || p.vendor_id || '')}">
             ${escapeHtml(t('labels.quick_view'))}
           </button>`
        : '';
      const partLink=`<a href="javascript:;" class="text-decoration-none text-primary fw-bold part-link"
                         data-sku="${escapeHtml(p.part_number||'')}"
                         data-id="${p.store_id||''}"
                         data-url="${escapeHtml(p.quick_view||'')}"
                         data-user="${escapeHtml(p.user_id || p.vendor_id || '')}">
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
        <td class="text-center">${numberCell}</td>
        <td class="text-center">${callout}</td>
        <td class="text-center">${qty}</td>
        <td class="text-center">${escapeHtml(name)}</td>
        <td class="text-center">${match}</td>
        <td class="text-center">${exts}</td>
        <td class="text-center">${periodCell}</td>
        <td class="text-center">${fitsCell}</td>
        <td class="text-center">${subsCell}</td>
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
          const qty=(p.part_qty != null && String(p.part_qty).trim() !== '') ? escapeHtml(p.part_qty) : '';
          const mv=Array.isArray(p.match_values)?p.match_values:(typeof p.match_values==='string'?p.match_values.split(',').map(s=>s.trim()).filter(Boolean):[]);
          const match=mv.length?` ${mv.map(v=>escapeHtml(v)).join(', ')}`:`<span class="badge bg-light text-dark">${escapeHtml(t('values.generic'))}</span>`;
          const period=formatPeriodRange(p.part_begin,p.part_end);
          const periodBadge = period ? `<span class="badge bg-light text-dark">${period}</span>` : '';
          const callout=(p.part_callout != null && String(p.part_callout).trim() !== '') ? escapeHtml(p.part_callout) : '';
          const exts=renderExtensions(p.extensions);

          const qvBtn=(p.store_id||p.quick_view)?`<button type="button" class="btn btn-sm btn-outline-primary quick-view mt-2"
                 data-id="${p.store_id||''}"
                 data-sku="${escapeHtml(p.part_number||'')}"
                 data-url="${escapeHtml(p.quick_view||'')}"
                 data-user="${escapeHtml(p.user_id || p.vendor_id || '')}">
                 ${escapeHtml(t('labels.quick_view'))}</button>`:'';
          const partLink=`<a href="javascript:;" class="text-decoration-none text-primary part-link"
                          data-sku="${escapeHtml(p.part_number||'')}"
                          data-id="${p.store_id||''}"
                          data-url="${escapeHtml(p.quick_view||'')}"
                          data-user="${escapeHtml(p.user_id || p.vendor_id || '')}">
                          🔢 ${escapeHtml(p.part_number||'')}</a>`;

          const subsList = normListFromAny(p.substitutions ?? p.alternatives ?? p.alt ?? p.subs, 'subs');
          const fitsList = normListFromAny(p.fits ?? p.compatible ?? p.vehicles ?? p.fitVehicles, 'fits');
          const subsMore = p.part_number ? `<div class="mt-2"><a href="javascript:;" class="small text-decoration-underline alt-link" data-sku="${escapeHtml(p.part_number||'')}">${escapeHtml(t('labels.substitutions'))}</a></div>` : '';
          const fitsMore = p.part_number ? `<div class="mt-2"><a href="javascript:;" class="small text-decoration-underline fits-link" data-sku="${escapeHtml(p.part_number||'')}">${escapeHtml(t('labels.fits'))}</a></div>` : '';

          return `<div class="card shadow-sm mb-3"><div class="card-body text-center">
              <h6 class="card-title">${partLink}</h6>
              <p><strong>${escapeHtml(t('labels.callout'))}:</strong> ${callout}</p>
              <p><strong>${escapeHtml(t('labels.qty'))}:</strong> ${qty}</p>
              <p><strong>${escapeHtml(t('labels.name'))}:</strong> ${escapeHtml(name)}</p>
              <p><strong>${escapeHtml(t('labels.match'))}:</strong> ${match}</p>
              <p><strong>${escapeHtml(t('labels.extensions'))}:</strong> ${exts}</p>
              <p><strong>${escapeHtml(t('labels.period'))}:</strong> ${periodBadge}</p>
              <p><strong>${escapeHtml(t('columns.fits'))}:</strong> ${renderBadges(fitsList)}</p>
              ${fitsMore}
              <p><strong>${escapeHtml(t('columns.substitutions'))}:</strong> ${renderBadges(subsList)}</p>
              ${subsMore}
              <div class="mt-2">${qvBtn}</div>
          </div></div>`;
        }).join('')}
      </div>`;

    // ✅ إضافة Pagination UI إذا كانت موجودة
    let paginationHtml = '';
    if (pagination && pagination.last_page > 1) {
      const { current_page, last_page, total, from, to } = pagination;

      const showingText = t('pagination.showing') || 'Showing';
      const ofText = t('pagination.of') || 'of';
      const previousText = t('pagination.previous') || 'Previous';
      const nextText = t('pagination.next') || 'Next';

      paginationHtml = `
        <div class="d-flex justify-content-between align-items-center mt-4 px-3">
          <div class="text-muted small">
            ${escapeHtml(showingText)} ${from}-${to} ${escapeHtml(ofText)} ${total}
          </div>
          <nav aria-label="Page navigation">
            <ul class="pagination pagination-sm mb-0">
              ${current_page > 1 ? `
                <li class="page-item">
                  <a class="page-link pagination-link" href="javascript:;" data-page="${current_page - 1}">
                    ${escapeHtml(previousText)}
                  </a>
                </li>
              ` : ''}

              ${Array.from({ length: Math.min(5, last_page) }, (_, i) => {
                let pageNum;
                if (last_page <= 5) {
                  pageNum = i + 1;
                } else if (current_page <= 3) {
                  pageNum = i + 1;
                } else if (current_page >= last_page - 2) {
                  pageNum = last_page - 4 + i;
                } else {
                  pageNum = current_page - 2 + i;
                }

                return `
                  <li class="page-item ${pageNum === current_page ? 'active' : ''}">
                    <a class="page-link pagination-link" href="javascript:;" data-page="${pageNum}">
                      ${pageNum}
                    </a>
                  </li>
                `;
              }).join('')}

              ${current_page < last_page ? `
                <li class="page-item">
                  <a class="page-link pagination-link" href="javascript:;" data-page="${current_page + 1}">
                    ${escapeHtml(nextText)}
                  </a>
                </li>
              ` : ''}
            </ul>
          </nav>
        </div>
      `;
    }

    return desktop + mobile + paginationHtml;
  }

  /* ========================= API ========================= */

  /**
   * ✅ جلب metadata (coordinates) فقط من API
   */
  async function fetchCalloutMetadata() {
    const METADATA_TIMEOUT = 60000; // 60 seconds timeout for metadata (production without indexes)

    if (metadataLoaded) {
      console.log('📦 Callouts metadata already loaded from memory cache');
      return cachedCallouts;
    }

    if (!sectionId || !categoryId || !catalogCode) {
      console.error('❌ Missing context data for metadata:', { sectionId, categoryId, catalogCode });
      throw new Error('Context data not loaded');
    }

    // ✅ محاولة جلب من localStorage أولاً
    const cacheKey = `callouts_${sectionId}_${categoryId}`;
    const cacheTTL = 30 * 60 * 1000; // 30 دقيقة

    try {
      const cached = localStorage.getItem(cacheKey);
      if (cached) {
        const parsed = JSON.parse(cached);
        const now = Date.now();

        // التحقق من صلاحية الـ cache
        if (parsed.timestamp && (now - parsed.timestamp) < cacheTTL) {
          console.log('✅ Callouts loaded from localStorage cache');
          cachedCallouts = parsed.data || [];
          metadataLoaded = true;

          // بناء index للبحث السريع
          byKey = cachedCallouts.reduce((m, it) => {
            const k1 = normKey(it.callout_key);
            if (k1) m[k1] = it;
            return m;
          }, {});

          return cachedCallouts;
        } else {
          // cache منتهي الصلاحية - احذفه
          localStorage.removeItem(cacheKey);
        }
      }
    } catch (e) {
      console.warn('⚠️ localStorage read error:', e);
      // استمر في جلب من API
    }

    const params = new URLSearchParams({
      section_id   : sectionId,
      category_id  : categoryId,
      catalog_code : catalogCode,
    });

    // console.log('📡 Fetching callout metadata from API:', params.toString());

    try {
      // ✅ إضافة timeout للطلب
      const controller = new AbortController();
      const timeoutId = setTimeout(() => controller.abort(), METADATA_TIMEOUT);

      const res = await fetch(`/api/callouts/metadata?${params.toString()}`, {
        headers: { 'Accept': 'application/json' },
        signal: controller.signal
      });

      clearTimeout(timeoutId);

      // console.log('📊 Metadata API response status:', res.status);

      if (!res.ok) {
        console.error('❌ Metadata API error:', res.status);
        throw new Error(`API error ${res.status}`);
      }

      const data = await res.json();
      // console.log('✅ Metadata loaded:', data);

      if (data.ok && Array.isArray(data.callouts)) {
        cachedCallouts = data.callouts;
        metadataLoaded = true;

        // ✅ حفظ في localStorage
        try {
          localStorage.setItem(cacheKey, JSON.stringify({
            data: cachedCallouts,
            timestamp: Date.now()
          }));
          console.log('💾 Callouts saved to localStorage cache');
        } catch (e) {
          console.warn('⚠️ localStorage write error (quota?):', e);
          // لا مشكلة - استمر بدون cache
        }

        // بناء index للبحث السريع - استخدام callout_key فقط
        byKey = cachedCallouts.reduce((m, it) => {
          const k1 = normKey(it.callout_key);
          if (k1) m[k1] = it;
          return m;
        }, {});

        // console.log(`✅ Metadata cached: ${cachedCallouts.length} callouts`);
        return cachedCallouts;
      } else {
        console.error('❌ Invalid metadata response');
        throw new Error('Invalid metadata response');
      }
    } catch (err) {
      if (err.name === 'AbortError') {
        console.error('❌ Metadata request timeout after', METADATA_TIMEOUT / 1000, 'seconds');
        throw new Error('Metadata request timeout - server too slow');
      }
      console.error('❌ Fetch metadata error:', err);
      throw err;
    }
  }

  /**
   * جلب بيانات المنتجات لـ callout معين مع دعم pagination
   */
  async function fetchCalloutData(calloutKey, page = 1, perPage = 50, retryCount = 0) {
    const MAX_RETRIES = 3;
    const FETCH_TIMEOUT = 90000; // 90 seconds timeout (production without indexes)

    if (!sectionId || !categoryId || !catalogCode) {
      console.error('❌ Missing context data:', { sectionId, categoryId, catalogCode });
      throw new Error('Context data not loaded');
    }

    const params = new URLSearchParams({
      section_id   : sectionId,
      category_id  : categoryId,
      catalog_code : catalogCode,
      callout      : calloutKey,
      page         : page,
      per_page     : perPage,
    });

    // console.log('📡 Fetching callout data:', params.toString());

    try {
      // ✅ إضافة timeout للطلب
      const controller = new AbortController();
      const timeoutId = setTimeout(() => controller.abort(), FETCH_TIMEOUT);

      const res = await fetch(`/api/callouts?${params.toString()}`, {
        headers: { 'Accept': 'application/json' },
        signal: controller.signal
      });

      clearTimeout(timeoutId);

      if (!res.ok) {
        if (retryCount < MAX_RETRIES && res.status >= 500) {
          console.warn(`⚠️ API error ${res.status}, retrying (${retryCount + 1}/${MAX_RETRIES})...`);
          await new Promise(resolve => setTimeout(resolve, 1000));
          return fetchCalloutData(calloutKey, page, perPage, retryCount + 1);
        }
        throw new Error(`API error ${res.status}`);
      }

      const data = await res.json();
      // console.log('✅ Callout data loaded:', data);
      return data;
    } catch (err) {
      if (err.name === 'AbortError') {
        console.error('❌ Request timeout after', FETCH_TIMEOUT / 1000, 'seconds');
        if (retryCount < MAX_RETRIES) {
          console.warn(`⚠️ Retrying due to timeout (${retryCount + 1}/${MAX_RETRIES})...`);
          await new Promise(resolve => setTimeout(resolve, 3000));
          return fetchCalloutData(calloutKey, page, perPage, retryCount + 1);
        }
        throw new Error('Server response is too slow. Please contact administrator to add database indexes.');
      }
      console.error('❌ Fetch error:', err);
      throw err;
    }
  }

  /* ========================= Section Navigation ========================= */
  function goToSection(sectionKey) {
    // ✅ البحث عن callout بـ sectionKey والحصول على parents_key
    const callout = cachedCallouts.find(c => c.callout_type === 'section' && c.callout_key === sectionKey);

    if (!callout) {
      console.error('❌ Section callout not found:', sectionKey);
      return;
    }

    if (!callout.parents_key) {
      console.error('❌ No parents_key found in callout:', callout);
      return;
    }

    const bn = brandName || '';
    const cc = catalogCode || '';
    const pk = callout.parents_key; // parents_key من level 3 category المستهدفة
    const sk = sectionKey; // callout_key نفسه

    if (!bn || !cc || !pk || !sk) {
      console.error('❌ Missing navigation data:', { bn, cc, parentsKey: pk, sectionKey: sk });
      return;
    }

    // بناء الرابط: /catlogs/{brand}/{catalog}/{parents_key}/{callout_key}
    const url = `/catlogs/${encodeURIComponent(bn)}/${encodeURIComponent(cc)}/${encodeURIComponent(pk)}/${encodeURIComponent(sk)}`;

    console.log('🔀 Navigating to section:', url, 'from callout:', callout);
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
      const pagination = data.pagination || null;

      // اعرض الجدول مع pagination ثم سجّل "الجذر" كأول شاشة في المكدس
      const html = renderProducts(prods, pagination);
      body.innerHTML = html;
      afterInject(body);
      body.scrollTop = 0;

      pushView({ title: titleRoot, html, calloutKey: key, pagination });
      setBackVisible(); // مخفي لأن length=1
    }).catch(err=>{
      const body = modalBodyEl(); if (!body) return;
      const msg  = t('messages.load_failed');
      const isTimeout = err?.message?.includes('slow') || err?.message?.includes('timeout');

      const errorHtml = isTimeout
        ? `<div class="alert alert-danger">
             <h5><i class="fas fa-exclamation-triangle"></i> ${escapeHtml(msg)}</h5>
             <p class="mb-2">${escapeHtml(err?.message||String(err))}</p>
             <hr>
             <p class="mb-0 small">
               <strong>💡 Tip:</strong> This issue is usually caused by missing database indexes.
               Contact your administrator to run:<br>
               <code>CREATE INDEX idx_illustrations_section_code ON illustrations(section_id, code);</code>
             </p>
           </div>`
        : `<div class="alert alert-danger">${escapeHtml(msg)}: ${escapeHtml(err?.message||String(err))}</div>`;

      body.innerHTML = errorHtml;
      setBackVisible();
    });
  }

  /* ========================= Inline Sub-Views (each pushes a new state) ========================= */
  function openQuickInline(id, url, sku, user) {
    const base  = window.ILL_ROUTES?.quick || '/modal/quickview/';  // ✅ fallback محدّث
    const title = t('catalog.quickview.title');
    let finalUrl = (url && typeof url === 'string') ? url : (id ? (base + id) : null);

    // ألحق user على أي من الحالتين (url موجود/غير موجود)
    if (finalUrl && user && finalUrl.indexOf('user=') === -1) {
      finalUrl += (finalUrl.indexOf('?') === -1 ? '?' : '&') + 'user=' + encodeURIComponent(user);
    }

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
    // ✅ منع double-fire من click + touchend على الموبايل
    let lastClickTime = 0;
    const CLICK_DELAY = 300; // ms

    /* فتح الكول آوت من الصورة - دعم النقر والتاتش */
    $(document).off('click.ill_open touchend.ill_open').on('click.ill_open touchend.ill_open', '.callout-label, .bbdover', function (e) {
      e.preventDefault();
      e.stopPropagation();

      // ✅ تجنب double-fire
      const now = Date.now();
      if (now - lastClickTime < CLICK_DELAY) {
        console.log('⏭️ Skipping duplicate event');
        return;
      }
      lastClickTime = now;

      const $el = $(this).hasClass('callout-label') ? $(this) : $(this).closest('.callout-label');
      const type = ($el.data('calloutType') || 'part').toString().toLowerCase();
      const key  = ($el.data('calloutKey')  || '').toString();

      // console.log('🖱️ Callout clicked:', { key, type, eventType: e.type });

      if (type === 'section') {
        goToSection(key);
        return;
      }
      if (key) {
        openCallout(key);
      }
    });

    /* رقم القطعة */
    $(document).off('click.ill_partlink').on('click.ill_partlink', '.part-link', function (e) {
      e.preventDefault();

      const $inAlt = $(this).closest('.ill-alt').length > 0;
      const sku    = $(this).data('sku');
      const id     = $(this).data('id');
      let   url    = $(this).data('url');
      const user   = $(this).data('user');

      // ضمّن user في الرابط إذا لم يكن موجودًا
      if (user && url && url.indexOf('user=') === -1) {
        url += (url.indexOf('?') === -1 ? '?' : '&') + 'user=' + encodeURIComponent(user);
      }

      if (!$inAlt && sku) { openAlternativeInline(sku); return; }
      openQuickInline(id, url, sku, user);
    });

    /* زر "عرض سريع" */
    $(document).off('click.ill_quick').on('click.ill_quick', '.quick-view', function (e) {
      e.preventDefault();

      const $inAlt = $(this).closest('.ill-alt').length > 0;
      const sku    = $(this).data('sku');
      const id     = $(this).data('id');
      let   url    = $(this).data('url');
      const user   = $(this).data('user');

      if (!$inAlt && sku) { openAlternativeInline(sku); return; }

      // ضمّن user في رابط المودال إذا لم يكن موجودًا
      if (user && url && url.indexOf('user=') === -1) {
        url += (url.indexOf('?') === -1 ? '?' : '&') + 'user=' + encodeURIComponent(user);
      }

      openQuickInline(id, url, sku, user);
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

    /* ✅ Pagination Links */
    $(document).off('click.ill_pagination').on('click.ill_pagination', '.pagination-link', function (e) {
      e.preventDefault();
      const page = parseInt($(this).data('page'), 10);
      if (isNaN(page) || page < 1) return;

      const currentView = stack[stack.length - 1];
      if (!currentView || !currentView.calloutKey) return;

      const body = modalBodyEl();
      if (body) body.innerHTML = renderSpinner();

      fetchCalloutData(currentView.calloutKey, page).then(data => {
        const body = modalBodyEl(); if (!body) return;
        if (!data.ok) {
          const msg = t('messages.api_error');
          body.innerHTML = `<div class="alert alert-danger">${escapeHtml(msg)}: ${escapeHtml(data.error||'')}</div>`;
          return;
        }

        const prods = data.products || [];
        const pagination = data.pagination || null;
        const html = renderProducts(prods, pagination);

        body.innerHTML = html;
        afterInject(body);
        body.scrollTop = 0;

        // تحديث الـ current view في الـ stack
        currentView.html = html;
        currentView.pagination = pagination;
      }).catch(err => {
        const body = modalBodyEl(); if (!body) return;
        const msg = t('messages.load_failed');
        body.innerHTML = `<div class="alert alert-danger">${escapeHtml(msg)}: ${escapeHtml(err?.message||String(err))}</div>`;
      });
    });

    /* ============== أزرار السلة ============== */

    // إضافة إلى السلة (يبقى داخل المودال)
    $(document).off('click.ill_addnum').on('click.ill_addnum', '.ill-add-to-cart', function (e) {
      e.preventDefault();

      const btn = this;
      const id  = $(btn).data('id');
      const mpId = $(btn).data('mp-id') || $(btn).data('mpId'); // merchant_product_id
      if (!id && !mpId) { console.warn('ill-add-to-cart: missing data-id or data-mp-id'); return; }

      // كمية إن وُجدت داخل بطاقة المنتج، وإلا = 1 (جدول البدائل)
      const $root = $(btn).closest('.ill-product');
      let qty = 1;
      const $qty = $root.find('.ill-qty');
      if ($qty.length) {
        const q = parseInt($qty.val(), 10);
        if (!isNaN(q) && q > 0) qty = q;
      }

      const addUrl = $(btn).data('addnumUrl') || $(btn).data('addnum-url') || '/addnumcart';
      const user   = $(btn).data('user');

      // بناء الـ URL بناءً على نوع الـ route
      let url;
      if (mpId && (addUrl.includes('/cart/add/merchant/') || addUrl.includes('/cart/merchant/add/'))) {
        // استخدام route الجديد (merchant.cart.add) - الـ ID موجود في الـ path
        url = `${addUrl}?qty=${encodeURIComponent(qty)}` + (user ? `&user=${encodeURIComponent(user)}` : '');
      } else {
        // استخدام route القديم
        url = `${addUrl}?id=${encodeURIComponent(id)}&qty=${encodeURIComponent(qty)}`
                  + (user ? `&user=${encodeURIComponent(user)}` : '');
      }

      btn.disabled = true;
      fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(r => r.ok ? r.json() : Promise.reject(new Error(`HTTP ${r.status}`)))
        .then(data => {
          // Use global cart state updater
          if (typeof window.applyCartState === 'function') {
            window.applyCartState(data);
          } else {
            // Fallback: fetch cart summary if global updater not available
            fetch('/cart/summary', { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
              .then(r => r.ok ? r.json() : null)
              .then(s => s && window.applyCartState && window.applyCartState(s))
              .catch(() => {});
          }

          const ok = data.success ?? t('messages.added_to_cart');
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
      const mpId = $(btn).data('mp-id') || $(btn).data('mpId'); // merchant_product_id
      if (!id && !mpId) { console.warn('ill-buy-now: missing data-id or data-mp-id'); return; }

      // كمية من الحقل إن وُجد، وإلا = 1
      const $root = $(btn).closest('.ill-product');
      let qty = 1;
      const $qty = $root.find('.ill-qty');
      if ($qty.length) {
        const q = parseInt($qty.val(), 10);
        if (!isNaN(q) && q > 0) qty = q;
      }

      const addUrl = $(btn).data('addtonumUrl') || $(btn).data('addtonum-url') || '/addtonumcart';
      const user   = $(btn).data('user');
      const cartsUrl = $(btn).data('carts-url') || $(btn).data('cartsUrl') || '/carts';

      // بناء الـ URL بناءً على نوع الـ route
      let url;
      if (mpId && (addUrl.includes('/cart/add/merchant/') || addUrl.includes('/cart/merchant/add/'))) {
        // استخدام route الجديد (merchant.cart.add) - الـ ID موجود في الـ path
        // نضيف للسلة عبر AJAX ثم نذهب للـ carts
        url = `${addUrl}?qty=${encodeURIComponent(qty)}` + (user ? `&user=${encodeURIComponent(user)}` : '');
        btn.disabled = true;
        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
          .then(r => r.ok ? r.json() : Promise.reject(new Error(`HTTP ${r.status}`)))
          .then(data => {
            window.location.href = cartsUrl;
          })
          .catch(err => {
            const msg = t('messages.api_error');
            if (window.toastr) toastr.error(`${msg} ${err.message || err}`); else alert(`${msg}\n${err.message || err}`);
            btn.disabled = false;
          });
        return;
      } else {
        // استخدام route القديم
        url = `${addUrl}?id=${encodeURIComponent(id)}&qty=${encodeURIComponent(qty)}`;
        if (user) url += `&user=${encodeURIComponent(user)}`;
        window.location.href = url;
      }
    });

  }

  /* ========================= Landmarks & Hover ========================= */
  async function addLandmarks() {
    // console.log('🎯 addLandmarks called - NEW API METHOD');
    if (window.__ill_addedLandmarks) {
      // console.log('⚠️ addLandmarks already executed, skipping');
      return;
    }
    window.__ill_addedLandmarks = true;

    try {
      // جلب البيانات من API
      const callouts = await fetchCalloutMetadata();
      // console.log(`📦 Loaded ${callouts.length} callouts from API`);

      if (callouts.length === 0) {
        console.warn('⚠️ No callouts found');
        return;
      }

      const $img = $('#image');
      // console.log(`🏷️ Adding ${callouts.length} landmarks to image`);

      callouts.forEach((item, index) => {
        // ✅ استخدام الأبعاد من API
        const left   = item.rectangle_left ?? 0;
        const top    = item.rectangle_top  ?? 0;
        const width  = item.rectangle_width  ?? 150;
        const height = item.rectangle_height ?? 30;
        const key    = normKey(item.callout_key || '');
        const type   = (item.callout_type || 'part').toLowerCase();

        // ✅ تحويل الأبعاد إلى px
        const widthPx  = (typeof width  === 'number') ? `${width}px`  : (String(width).includes('px')  ? String(width)  : `${width}px`);
        const heightPx = (typeof height === 'number') ? `${height}px` : (String(height).includes('px') ? String(height) : `${height}px`);

        // console.log(`  Landmark ${index + 1}: key="${key}", type="${type}", pos=(${left},${top}), size=(${widthPx},${heightPx})`);

        // ✅ بناء HTML
        const html = `
          <div class="item lable lable-single pointer correct-callout callout-label"
               data-callout-key="${String(key)}"
               data-callout-type="${String(type)}"
               data-container="body"
               data-allow-scale="true"
               data-size="${widthPx},${heightPx}"
               data-position="${left},${top}">
            <div class="bbdover"
                 id="part_${item.index || item.id || ''}"
                 data-codeonimage="${String(key)}"
                 data-callout-key="${String(key)}"
                 data-callout-type="${String(type)}"
                 style="position:absolute;width:${widthPx};height:${heightPx};background-color:transparent;opacity:0.7;"></div>
          </div>`;
        try {
          $img.smoothZoom('addLandmark', [html]);
          // console.log(`    ✅ Landmark ${index + 1} added successfully`);
        } catch (e) {
          console.error(`    ❌ Failed to add landmark ${index + 1}:`, e);
        }
      });

      // console.log(`🎉 Finished adding landmarks. Total: ${callouts.length}`);
    } catch (err) {
      console.error('❌ Failed to add landmarks:', err);
      // عرض رسالة خطأ للمستخدم
      if (window.toastr) {
        toastr.error('Failed to load callouts. Please refresh the page.');
      }
    }
  }
  function bindHover() {
    if (window.__ill_hoverBound) return;
    window.__ill_hoverBound = true;

    // console.log('🖱️ Binding hover events...');

    $(document)
      .on('mouseenter', '.bbdover', function () {
        const code = $(this).data('codeonimage');
        // console.log('🔵 Hover enter on:', code);
        $(this).addClass('hovered');
        $(`.bbdover[data-codeonimage="${code}"]`).addClass('hovered');
      })
      .on('mouseleave', '.bbdover', function () {
        const code = $(this).data('codeonimage');
        // console.log('⚪ Hover leave on:', code);
        $(this).removeClass('hovered');
        $(`.bbdover[data-codeonimage="${code}"]`).removeClass('hovered');
      });

    // console.log('✅ Hover events bound');
  }

  /* ========================= Zoom Init & Auto Open ========================= */
  function initZoom() {
    const $img = $('#image');
    if (!$img.length) {
      // Silently skip if no #image element - this is normal on most pages
      return;
    }

    // console.log('🔍 Initializing smoothZoom with OLD settings...');

    // ✅ إعدادات smoothZoom بالضبط كما في النسخة القديمة
    $img.smoothZoom({
      width: 800,
      height: 500,
      responsive: true,
      container: 'zoom_container',
      responsive_maintain_ratio: true,
      max_WIDTH: '',
      max_HEIGHT: '',
      zoom_SINGLE_STEP: false,
      animation_SMOOTHNESS: 3,
      animation_SPEED_ZOOM: 3,
      animation_SPEED_PAN: 3,
      initial_POSITION: '200, 300',
      zoom_MAX: 200,
      button_SIZE: 20,
      button_AUTO_HIDE: 'YES',
      button_AUTO_HIDE_DELAY: 2,
      button_ALIGN: 'top right',
      mouse_DOUBLE_CLICK: false,
      mouse_WHEEL: true,
      use_3D_Transform: true,
      border_TRANSPARENCY: 0,
      on_IMAGE_LOAD: function() {
        // console.log('📸 ✅ on_IMAGE_LOAD fired - image fully loaded');
        addLandmarks().then(() => {
          autoOpen();
        }).catch(err => {
          console.error('❌ addLandmarks failed:', err);
        });
      },
      on_ZOOM_PAN_UPDATE: function() {
        // console.log('🔄 Zoom/Pan updated');
      },
      on_ZOOM_PAN_COMPLETE: function() {
        // console.log('✅ Zoom/Pan complete');
      },
      on_LANDMARK_STATE_CHANGE: function() {
        // console.log('🏷️ Landmark state changed');
      }
    });

    // console.log('✅ smoothZoom initialized with callbacks');
  }
  function autoOpen() {
    if (window.__ill_autoOpened) return;
    const calloutKey = qs('callout');
    const autoFlag = qs('auto_open');
    if (!(calloutKey && (autoFlag === '1' || autoFlag === 'true'))) return;

    // console.log('🚀 Auto-opening callout:', calloutKey);

    // ✅ تأكد من أن metadata محملة قبل الفتح - مع حد أقصى للمحاولات
    if (!metadataLoaded) {
      const maxRetries = 10; // حد أقصى 5 ثواني (10 × 500ms)
      const currentRetry = window.__ill_autoOpenRetries || 0;

      if (currentRetry >= maxRetries) {
        console.error('❌ Auto-open failed: metadata not loaded after', maxRetries, 'retries');
        window.__ill_autoOpened = true; // أوقف المحاولات
        return;
      }

      // console.warn('⚠️ Metadata not ready, retrying in 500ms... (attempt', currentRetry + 1, '/', maxRetries, ')');
      window.__ill_autoOpenRetries = currentRetry + 1;
      setTimeout(() => {
        window.__ill_autoOpened = false;
        autoOpen();
      }, 500);
      return;
    }

    window.__ill_autoOpened = true;
    window.__ill_autoOpenRetries = 0; // إعادة تعيين العداد
    const found = byKey[calloutKey];

    if (found && String(found.callout_type || '').toLowerCase() === 'section') {
      // console.log('🔀 Redirecting to section:', calloutKey);
      goToSection(calloutKey);
    } else {
      // console.log('📖 Opening callout modal:', calloutKey);
      openCallout(calloutKey);
    }
  }

  /* ========================= Boot ========================= */
  $(function () {
    // console.log('🚀 Initializing illustration viewer...');

    // ✅ ربط الأحداث أولاً قبل initZoom
    bindHover();
    bindDynamicEvents();

    // ✅ initZoom سيستدعي addLandmarks داخل on_IMAGE_LOAD فقط
    initZoom();

    // ⚠️ لا نستدعي addLandmarks هنا - فقط من داخل on_IMAGE_LOAD
    // لأن smoothZoom يحتاج الصورة محملة بالكامل للحصول على المقاس الصحيح

    // نظّم حالة زر الرجوع عند التحميل
    setBackVisible();

    // عند إغلاق المودال: صفّر المكدس وحرر focus
    $(document).off('hidden.bs.modal.ill').on('hidden.bs.modal.ill', '#modal', function () {
      // console.log('🔄 Modal closed, clearing stack');
      stack.length = 0;
      setBackVisible();

      // ✅ حرر focus من المودال لتجنب تحذير ARIA
      const modal = document.getElementById('modal');
      if (modal) {
        modal.setAttribute('aria-hidden', 'true');
        // إرجاع focus للعنصر الذي فتح المودال
        const trigger = document.activeElement;
        if (trigger && trigger !== document.body) {
          trigger.blur();
        }
      }
    });

    // عند فتح المودال: تأكد من إزالة aria-hidden
    $(document).off('shown.bs.modal.ill').on('shown.bs.modal.ill', '#modal', function () {
      // console.log('📖 Modal opened');
      const modal = document.getElementById('modal');
      if (modal) {
        modal.setAttribute('aria-hidden', 'false');
      }
    });

    // console.log('✅ Illustration viewer initialized - waiting for on_IMAGE_LOAD');
  });

  // API: لتفعيل الفتح من أماكن أخرى
  window.openCallout = openCallout;

})(jQuery);