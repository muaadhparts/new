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

  function renderExtensions(ext) {
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

  /* ========================= Context from Blade ========================= */
  const ctx = window.catalogContext || {};
  const sectionId   = ctx.sectionId   || null;
  const categoryId  = ctx.categoryId  || null;
  const catalogCode = ctx.catalogCode || '';
  const brandName   = ctx.brandName   || '';

  // Cache
  let cachedCallouts = [];
  let byKey = {};
  let metadataLoaded = false;

  /* ========================= Modal Elements ========================= */
  const stack = [];
  function modalNameEl() { return document.getElementById('ill-modal-name'); }
  function modalBodyEl()  { return document.getElementById('api-callout-body'); }
  function backBtnEl()    { return document.getElementById('ill-back-btn'); }
  function getCurrentName() {
    const el = modalNameEl(); return el ? (el.textContent || '') : '';
  }

  function setName(txt) {
    const el = modalNameEl(); if (el) el.textContent = txt;
  }
  function setBackVisible() {
    const btn = backBtnEl(); if (!btn) return;
    const hasHistory = stack.length > 1;
    btn.classList.toggle('d-none', !hasHistory);
    btn.setAttribute('aria-disabled', hasHistory ? 'false' : 'true');
    btn.disabled = !hasHistory;
    btn.tabIndex = hasHistory ? 0 : -1;
  }

  function pushView(state) {
    const body = modalBodyEl();
    const scroll = body ? body.scrollTop : 0;
    stack.push({ name: state.name || '', html: state.html || '', __scroll: scroll, calloutKey: state.calloutKey });
    setBackVisible();
  }
  function currentView() {
    return stack[stack.length - 1] || null;
  }
  function popView() {
    if (stack.length <= 1) { setBackVisible(); return; }
    stack.pop();
    const st = currentView();
    if (st && st.html != null) {
      const body = modalBodyEl();
      setName(st.name || t('catalog.modal.name'));
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
    try {
      if (window.Livewire && typeof window.Livewire.rescan === 'function') {
        window.Livewire.rescan(container);
      }
    } catch (e) {}

    try {
      const scripts = container.querySelectorAll('script');
      scripts.forEach(s => {
        const n = document.createElement('script');
        if (s.src) { n.src = s.src; } else { n.textContent = s.textContent; }
        document.body.appendChild(n);
        setTimeout(() => n.remove(), 0);
      });
    } catch (e) {}

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

  function loadIntoModal(url, name) {
    const body = modalBodyEl(); if (!body) return Promise.resolve();
    const prevName = getCurrentName();
    const prevHtml  = body.innerHTML;

    setName(name);
    body.innerHTML = renderSpinner();

    return fetch(url, { headers: { 'X-Requested-With':'XMLHttpRequest' } })
      .then(res => { if (!res.ok) throw new Error(`HTTP ${res.status}`); return res.text(); })
      .then(html => {
        const tmp = document.createElement('div'); tmp.innerHTML = html;
        const inner = tmp.querySelector('.modal-body') || tmp.querySelector('#content') || tmp;
        const newHtml = inner.innerHTML || html;

        body.innerHTML = newHtml;
        afterInject(body);
        body.scrollTop = 0;

        pushView({ name, html: newHtml });
      })
      .catch(err => {
        setName(prevName);
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

  /* ========================= Parts Table Renderer ========================= */
  function renderProducts(catalogItems, pagination = null){
    if(!Array.isArray(catalogItems)||catalogItems.length===0){
      const noData=t('messages.no_matches');
      return `<div class="text-center p-5 text-muted"><i class="bi bi-search display-6"></i><div class="mt-3 fw-bold">${escapeHtml(noData)}</div></div>`;
    }

    const splitToList = (s)=> String(s||'').split(/[,\n;|]+/).map(v=>v.trim()).filter(Boolean);

    function normListFromAny(input, kind){
      if(input==null) return [];
      if(Array.isArray(input)){
        return input.map(it=>{
          if(it==null) return '';
          if(typeof it==='string') return it.trim();
          if(kind==='subs'){
            const cand = it.part_number ?? it.number ?? it.code ?? it.alt ?? it.key ?? '';
            return String(cand).trim();
          }else{
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
      : '';

    const rows=catalogItems.map(p=>{
      const name=localizedPartName(p);
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

      // Part number link - opens alternatives modal
      const partLink=`<a href="javascript:;" class="text-decoration-none text-primary fw-bold part-link"
                         data-part_number="${escapeHtml(p.part_number||'')}">
                         ${escapeHtml(p.part_number||'')}
                      </a>`;

      const subsList = normListFromAny(p.substitutions ?? p.alternatives ?? p.alt ?? p.subs, 'subs');
      const fitsList = normListFromAny(p.fits ?? p.compatible ?? p.vehicles ?? p.fitVehicles, 'fits');

      const subsMore = p.part_number
        ? `<div class="mt-1"><a href="javascript:;" class="small text-decoration-underline alt-link" data-part_number="${escapeHtml(p.part_number||'')}">${escapeHtml(t('labels.substitutions'))}</a></div>`
        : '';
      const fitsMore = p.part_number
        ? `<div class="mt-1"><a href="javascript:;" class="small text-decoration-underline fits-link" data-part_number="${escapeHtml(p.part_number||'')}">${escapeHtml(t('labels.fits'))}</a></div>`
        : '';

      const subsCell = `${renderBadges(subsList)}${subsMore}`;
      const fitsCell = `${renderBadges(fitsList)}${fitsMore}`;

      return `<tr class="${mv.length?'':'table-secondary'}">
        <td class="text-center">${partLink}</td>
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

    // Mobile cards
    const mobile = `
      <div class="d-block d-md-none">
        ${catalogItems.map(p=>{
          const name=localizedPartName(p);
          const qty=(p.part_qty != null && String(p.part_qty).trim() !== '') ? escapeHtml(p.part_qty) : '';
          const mv=Array.isArray(p.match_values)?p.match_values:(typeof p.match_values==='string'?p.match_values.split(',').map(s=>s.trim()).filter(Boolean):[]);
          const match=mv.length?` ${mv.map(v=>escapeHtml(v)).join(', ')}`:`<span class="badge bg-light text-dark">${escapeHtml(t('values.generic'))}</span>`;
          const period=formatPeriodRange(p.part_begin,p.part_end);
          const periodBadge = period ? `<span class="badge bg-light text-dark">${period}</span>` : '';
          const callout=(p.part_callout != null && String(p.part_callout).trim() !== '') ? escapeHtml(p.part_callout) : '';
          const exts=renderExtensions(p.extensions);

          const partLink=`<a href="javascript:;" class="text-decoration-none text-primary part-link"
                          data-part_number="${escapeHtml(p.part_number||'')}">
                          🔢 ${escapeHtml(p.part_number||'')}</a>`;

          const subsList = normListFromAny(p.substitutions ?? p.alternatives ?? p.alt ?? p.subs, 'subs');
          const fitsList = normListFromAny(p.fits ?? p.compatible ?? p.vehicles ?? p.fitVehicles, 'fits');
          const subsMore = p.part_number ? `<div class="mt-2"><a href="javascript:;" class="small text-decoration-underline alt-link" data-part_number="${escapeHtml(p.part_number||'')}">${escapeHtml(t('labels.substitutions'))}</a></div>` : '';
          const fitsMore = p.part_number ? `<div class="mt-2"><a href="javascript:;" class="small text-decoration-underline fits-link" data-part_number="${escapeHtml(p.part_number||'')}">${escapeHtml(t('labels.fits'))}</a></div>` : '';

          return `<div class="card shadow-sm mb-3"><div class="card-body text-center">
              <h6 class="card-name">${partLink}</h6>
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
          </div></div>`;
        }).join('')}
      </div>`;

    // Pagination
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
  async function fetchCalloutMetadata() {
    const METADATA_TIMEOUT = 60000;

    if (metadataLoaded) {
      return cachedCallouts;
    }

    if (!sectionId || !categoryId || !catalogCode) {
      throw new Error('Context data not loaded');
    }

    const cacheKey = `callouts_${sectionId}_${categoryId}`;
    const cacheTTL = 30 * 60 * 1000;

    try {
      const cached = localStorage.getItem(cacheKey);
      if (cached) {
        const parsed = JSON.parse(cached);
        const now = Date.now();
        if (parsed.timestamp && (now - parsed.timestamp) < cacheTTL) {
          cachedCallouts = parsed.data || [];
          metadataLoaded = true;
          byKey = cachedCallouts.reduce((m, it) => {
            const k1 = normKey(it.callout_key);
            if (k1) m[k1] = it;
            return m;
          }, {});
          return cachedCallouts;
        } else {
          localStorage.removeItem(cacheKey);
        }
      }
    } catch (e) {}

    const params = new URLSearchParams({
      section_id   : sectionId,
      category_id  : categoryId,
      catalog_code : catalogCode,
    });

    try {
      const controller = new AbortController();
      const timeoutId = setTimeout(() => controller.abort(), METADATA_TIMEOUT);

      const res = await fetch(`/api/callouts/metadata?${params.toString()}`, {
        headers: { 'Accept': 'application/json' },
        signal: controller.signal
      });

      clearTimeout(timeoutId);

      if (!res.ok) {
        throw new Error(`API error ${res.status}`);
      }

      const data = await res.json();

      if (data.ok && Array.isArray(data.callouts)) {
        cachedCallouts = data.callouts;
        metadataLoaded = true;

        try {
          localStorage.setItem(cacheKey, JSON.stringify({
            data: cachedCallouts,
            timestamp: Date.now()
          }));
        } catch (e) {}

        byKey = cachedCallouts.reduce((m, it) => {
          const k1 = normKey(it.callout_key);
          if (k1) m[k1] = it;
          return m;
        }, {});

        return cachedCallouts;
      } else {
        throw new Error('Invalid metadata response');
      }
    } catch (err) {
      if (err.name === 'AbortError') {
        throw new Error('Metadata request timeout');
      }
      throw err;
    }
  }

  async function fetchCalloutData(calloutKey, page = 1, perPage = 50, retryCount = 0) {
    const MAX_RETRIES = 3;
    const FETCH_TIMEOUT = 90000;

    if (!sectionId || !categoryId || !catalogCode) {
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

    try {
      const controller = new AbortController();
      const timeoutId = setTimeout(() => controller.abort(), FETCH_TIMEOUT);

      const res = await fetch(`/api/callouts?${params.toString()}`, {
        headers: { 'Accept': 'application/json' },
        signal: controller.signal
      });

      clearTimeout(timeoutId);

      if (!res.ok) {
        if (retryCount < MAX_RETRIES && res.status >= 500) {
          await new Promise(resolve => setTimeout(resolve, 1000));
          return fetchCalloutData(calloutKey, page, perPage, retryCount + 1);
        }
        throw new Error(`API error ${res.status}`);
      }

      return await res.json();
    } catch (err) {
      if (err.name === 'AbortError') {
        if (retryCount < MAX_RETRIES) {
          await new Promise(resolve => setTimeout(resolve, 3000));
          return fetchCalloutData(calloutKey, page, perPage, retryCount + 1);
        }
        throw new Error('Server response too slow');
      }
      throw err;
    }
  }

  /* ========================= Section Navigation ========================= */
  function goToSection(sectionKey) {
    const callout = cachedCallouts.find(c => c.callout_type === 'section' && c.callout_key === sectionKey);

    if (!callout || !callout.parents_key) {
      console.error('Section callout not found:', sectionKey);
      return;
    }

    const bn = brandName || '';
    const cc = catalogCode || '';
    const pk = callout.parents_key;
    const sk = sectionKey;

    if (!bn || !cc || !pk || !sk) {
      return;
    }

    const url = `/catlogs/${encodeURIComponent(bn)}/${encodeURIComponent(cc)}/${encodeURIComponent(pk)}/${encodeURIComponent(sk)}`;
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
    try { bootstrap.Modal.getOrCreateInstance(document.getElementById('modal')).show(); } catch {}
    const nameRoot = t('catalog.modal.name');
    setName(nameRoot);

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
      const prods = data.catalogItems || [];
      const pagination = data.pagination || null;

      const html = renderProducts(prods, pagination);
      body.innerHTML = html;
      afterInject(body);
      body.scrollTop = 0;

      pushView({ name: nameRoot, html, calloutKey: key, pagination });
      setBackVisible();
    }).catch(err=>{
      const body = modalBodyEl(); if (!body) return;
      const msg  = t('messages.load_failed');
      body.innerHTML = `<div class="alert alert-danger">${escapeHtml(msg)}: ${escapeHtml(err?.message||String(err))}</div>`;
      setBackVisible();
    });
  }

  /* ========================= Inline Sub-Views ========================= */
  function openAlternativeInline(part_number) {
    const body = modalBodyEl();
    if (body) body.innerHTML = renderSpinner();

    // Check if part has alternatives first
    return fetch('/api/catalog-item/alternatives/' + encodeURIComponent(part_number) + '/html')
      .then(res => res.json())
      .then(data => {
        if (data.count && data.count > 0) {
          // Has alternatives → show alternatives modal
          const base = window.ILL_ROUTES?.alternative || '/modal/alternative/';
          const name = t('catalog.alternative_modal.name');
          return loadIntoModal(base + encodeURIComponent(part_number), name);
        } else {
          // No alternatives → go directly to offers
          return openOffersByPartNumber(part_number);
        }
      })
      .catch(() => {
        // On error, try to show offers
        return openOffersByPartNumber(part_number);
      });
  }

  function openOffersByPartNumber(partNumber) {
    const url = '/modal/offers-by-part/' + encodeURIComponent(partNumber);
    const name = t('catalog.offers_modal.name') + ' ' + (partNumber || '');
    return loadIntoModal(url, name);
  }

  function openCompatibilityInline(part_number) {
    const base = window.ILL_ROUTES?.compatibility || '/modal/compatibility/';
    const name = t('catalog.compatibility_modal.name');
    return loadIntoModal(base + encodeURIComponent(part_number), name);
  }

  function openOffersInline(catalogItemId, partNumber) {
    const url = '/modal/offers/' + catalogItemId;
    const name = t('catalog.offers_modal.name') + ' ' + (partNumber || '');
    return loadIntoModal(url, name);
  }

  /* ========================= Dynamic Events ========================= */
  function bindDynamicEvents() {
    let lastClickTime = 0;
    const CLICK_DELAY = 300;

    /* Callout click from image */
    $(document).off('click.ill_open touchend.ill_open').on('click.ill_open touchend.ill_open', '.callout-label, .bbdover', function (e) {
      e.preventDefault();
      e.stopPropagation();

      const now = Date.now();
      if (now - lastClickTime < CLICK_DELAY) return;
      lastClickTime = now;

      const $el = $(this).hasClass('callout-label') ? $(this) : $(this).closest('.callout-label');
      const type = ($el.data('calloutType') || 'part').toString().toLowerCase();
      const key  = ($el.data('calloutKey')  || '').toString();

      if (type === 'section') {
        goToSection(key);
        return;
      }
      if (key) {
        openCallout(key);
      }
    });

    /* Part number link - opens alternatives */
    $(document).off('click.ill_partlink').on('click.ill_partlink', '.part-link', function (e) {
      e.preventDefault();
      const part_number = $(this).data('part_number');
      if (part_number) {
        openAlternativeInline(part_number);
      }
    });

    /* Alternatives link */
    $(document).off('click.ill_alt').on('click.ill_alt', '.alt-link', function (e) {
      e.preventDefault();
      openAlternativeInline($(this).data('part_number'));
    });

    /* Compatibility/Fits link */
    $(document).off('click.ill_fits').on('click.ill_fits', '.fits-link', function (e) {
      e.preventDefault();
      openCompatibilityInline($(this).data('part_number'));
    });

    /* Offers button in alternatives */
    $(document).off('click.ill_alt_offers').on('click.ill_alt_offers', '.alt-offers-btn', function (e) {
      e.preventDefault();
      e.stopPropagation();

      const catalogItemId = $(this).data('catalog-item-id') || $(this).data('catalogItemId');
      const partNumber = $(this).data('part-number') || $(this).data('partNumber');

      if (!catalogItemId) {
        console.warn('alt-offers-btn: missing data-catalog-item-id');
        return;
      }

      openOffersInline(catalogItemId, partNumber);
    });

    /* Note: Quantity controls handled globally by qty-control.js (delegated events) */

    /* Pagination */
    $(document).off('click.ill_pagination').on('click.ill_pagination', '.pagination-link', function (e) {
      e.preventDefault();
      const page = parseInt($(this).data('page'), 10);
      if (isNaN(page) || page < 1) return;

      const cv = currentView();
      if (!cv || !cv.calloutKey) return;

      const body = modalBodyEl();
      if (body) body.innerHTML = renderSpinner();

      fetchCalloutData(cv.calloutKey, page).then(data => {
        const body = modalBodyEl(); if (!body) return;
        if (!data.ok) {
          const msg = t('messages.api_error');
          body.innerHTML = `<div class="alert alert-danger">${escapeHtml(msg)}: ${escapeHtml(data.error||'')}</div>`;
          return;
        }

        const prods = data.catalogItems || [];
        const pagination = data.pagination || null;
        const html = renderProducts(prods, pagination);

        body.innerHTML = html;
        afterInject(body);
        body.scrollTop = 0;

        cv.html = html;
        cv.pagination = pagination;
      }).catch(err => {
        const body = modalBodyEl(); if (!body) return;
        const msg = t('messages.load_failed');
        body.innerHTML = `<div class="alert alert-danger">${escapeHtml(msg)}: ${escapeHtml(err?.message||String(err))}</div>`;
      });
    });
  }

  /* ========================= Landmarks & Hover ========================= */
  async function addLandmarks() {
    if (window.__ill_addedLandmarks) return;
    window.__ill_addedLandmarks = true;

    try {
      const callouts = await fetchCalloutMetadata();
      if (callouts.length === 0) return;

      const $img = $('#image');

      callouts.forEach((item) => {
        const left   = item.rectangle_left ?? 0;
        const top    = item.rectangle_top  ?? 0;
        const width  = item.rectangle_width  ?? 150;
        const height = item.rectangle_height ?? 30;
        const key    = normKey(item.callout_key || '');
        const type   = (item.callout_type || 'part').toLowerCase();

        const widthPx  = (typeof width  === 'number') ? `${width}px`  : String(width);
        const heightPx = (typeof height === 'number') ? `${height}px` : String(height);

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
        } catch (e) {}
      });
    } catch (err) {
      console.error('Failed to add landmarks:', err);
      if (window.toastr) {
        toastr.error('Failed to load callouts. Please refresh the page.');
      }
    }
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
    const $img = $('#image');
    if (!$img.length) return;

    $img.smoothZoom({
      width: '100%',
      height: 600,
      responsive: true,
      container: 'zoom_container',
      responsive_maintain_ratio: true,
      max_WIDTH: '',
      max_HEIGHT: '',
      zoom_SINGLE_STEP: false,
      animation_SMOOTHNESS: 3,
      animation_SPEED_ZOOM: 3,
      animation_SPEED_PAN: 3,
      initial_POSITION: '',
      initial_ZOOM: '',
      zoom_MIN: '',
      zoom_MAX: 300,
      zoom_OUT_TO_FIT: true,
      pan_LIMIT_BOUNDARY: false,
      pan_BUTTONS_SHOW: true,
      pan_REVERSE: false,
      touch_DRAG: true,
      mouse_DRAG: true,
      button_SIZE: 20,
      button_SIZE_TOUCH_DEVICE: 18,
      button_AUTO_HIDE: true,
      button_AUTO_HIDE_DELAY: 2,
      button_ALIGN: 'top right',
      mouse_DOUBLE_CLICK: true,
      mouse_WHEEL: true,
      mouse_WHEEL_CURSOR_POS: true,
      use_3D_Transform: true,
      border_TRANSPARENCY: 0,
      on_IMAGE_LOAD: function() {
        addLandmarks().then(() => {
          autoOpen();
        }).catch(err => {
          console.error('addLandmarks failed:', err);
        });
      }
    });
  }

  function autoOpen() {
    if (window.__ill_autoOpened) return;
    const calloutKey = qs('callout');
    const autoFlag = qs('auto_open');
    if (!(calloutKey && (autoFlag === '1' || autoFlag === 'true'))) return;

    if (!metadataLoaded) {
      const maxRetries = 10;
      const currentRetry = window.__ill_autoOpenRetries || 0;

      if (currentRetry >= maxRetries) {
        window.__ill_autoOpened = true;
        return;
      }

      window.__ill_autoOpenRetries = currentRetry + 1;
      setTimeout(() => {
        window.__ill_autoOpened = false;
        autoOpen();
      }, 500);
      return;
    }

    window.__ill_autoOpened = true;
    window.__ill_autoOpenRetries = 0;
    const found = byKey[calloutKey];

    if (found && String(found.callout_type || '').toLowerCase() === 'section') {
      goToSection(calloutKey);
    } else {
      openCallout(calloutKey);
    }
  }

  /* ========================= Boot ========================= */
  $(function () {
    bindHover();
    bindDynamicEvents();
    initZoom();
    setBackVisible();

    $(document).off('hidden.bs.modal.ill').on('hidden.bs.modal.ill', '#modal', function () {
      stack.length = 0;
      setBackVisible();
      const modal = document.getElementById('modal');
      if (modal) {
        modal.setAttribute('aria-hidden', 'true');
      }
    });

    $(document).off('shown.bs.modal.ill').on('shown.bs.modal.ill', '#modal', function () {
      const modal = document.getElementById('modal');
      if (modal) {
        modal.setAttribute('aria-hidden', 'false');
      }
    });
  });

  // API
  window.openCallout = openCallout;

})(jQuery);
