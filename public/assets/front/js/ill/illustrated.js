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

  /* ========================= Parts HTML Loader (Server-Rendered) ========================= */
  /**
   * Fetch server-rendered HTML for part details
   * Replaces the old renderProducts() JS function
   */
  function loadPartDetailsHtml(calloutKey, page = 1) {
    if (!sectionId || !categoryId || !catalogCode) {
      return Promise.reject(new Error('Context data not loaded'));
    }

    const params = new URLSearchParams({
      section_id: sectionId,
      category_id: categoryId,
      catalog_code: catalogCode,
      callout: calloutKey,
      page: page,
    });

    return fetch('/api/callouts/html?' + params.toString(), {
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
      .then(res => {
        if (!res.ok) throw new Error(`HTTP ${res.status}`);
        return res.text();
      });
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

    // ✅ Use server-rendered HTML instead of JS rendering
    loadPartDetailsHtml(key).then(html => {
      const body = modalBodyEl(); if (!body) return;
      body.innerHTML = html;
      afterInject(body);
      body.scrollTop = 0;

      pushView({ name: nameRoot, html, calloutKey: key });
      setBackVisible();
    }).catch(err => {
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
        if (data.count && data.count > 1) {
          // Multiple alternatives → show alternatives modal
          const base = window.ILL_ROUTES?.alternative || '/modal/alternative/';
          const name = t('catalog.alternative_modal.name');
          return loadIntoModal(base + encodeURIComponent(part_number), name);
        } else if (data.count === 1 && data.single_part_number) {
          // Single alternative → go directly to offers for that alternative
          return openOffersByPartNumber(data.single_part_number);
        } else {
          // No alternatives → go directly to offers for original part
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
        // Move highlight to clicked callout (replaces search highlight)
        highlightSearchedCallout(key);
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

    /* Sort offers - silent update (only for #modal context) */
    $(document).off('change.ill_sort').on('change.ill_sort', '#api-callout-body #offersSort', function (e) {
      e.preventDefault();
      e.stopPropagation();

      const $select = $(this);
      const sort = $select.val();
      const catalogItemId = $select.data('catalog-item-id');
      if (!catalogItemId) return;

      // Fade content while loading
      const $content = $select.closest('.catalog-offers-content');
      $content.css({ opacity: 0.5, pointerEvents: 'none' });

      $.get('/modal/offers/' + catalogItemId, { sort: sort, _t: Date.now() })
        .done(function(html) {
          const body = modalBodyEl();
          if (body) {
            body.innerHTML = html;
            afterInject(body);
            const cv = currentView();
            if (cv) cv.html = html;
          }
        })
        .fail(function() {
          $content.css({ opacity: 1, pointerEvents: 'auto' });
        });
    });

    /* Pagination - ✅ Updated to use server-rendered HTML */
    $(document).off('click.ill_pagination').on('click.ill_pagination', '.pagination-link', function (e) {
      e.preventDefault();
      const page = parseInt($(this).data('page'), 10);
      if (isNaN(page) || page < 1) return;

      const cv = currentView();
      if (!cv || !cv.calloutKey) return;

      const body = modalBodyEl();
      if (body) body.innerHTML = renderSpinner();

      // ✅ Use server-rendered HTML instead of JS rendering
      loadPartDetailsHtml(cv.calloutKey, page).then(html => {
        const body = modalBodyEl(); if (!body) return;
        body.innerHTML = html;
        afterInject(body);
        body.scrollTop = 0;

        cv.html = html;
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

    // Load image first to get natural dimensions
    const img = new Image();
    img.onload = function() {
      const naturalWidth = img.naturalWidth;
      const naturalHeight = img.naturalHeight;
      const containerWidth = $('#zoom_container').width() || $img.parent().width() || 800;

      // Calculate height based on image aspect ratio
      const aspectRatio = naturalHeight / naturalWidth;
      const calculatedHeight = Math.round(containerWidth * aspectRatio);

      // Limit max height on desktop, allow natural on mobile
      const isMobile = window.innerWidth < 768;
      const maxHeight = isMobile ? window.innerHeight * 0.7 : 700;
      const finalHeight = Math.min(calculatedHeight, maxHeight);

      $img.smoothZoom({
        // Container
        width: '100%',
        height: finalHeight,
        container: 'zoom_container',
        responsive: true,
        responsive_maintain_ratio: true,

        // Zoom settings
        zoom_MIN: '',
        zoom_MAX: 300,
        zoom_OUT_TO_FIT: true,

        // Pan settings
        pan_LIMIT_BOUNDARY: true,
        pan_REVERSE: false,

        // Controls - all hidden
        zoom_BUTTONS_SHOW: false,
        pan_BUTTONS_SHOW: false,

        // Interaction
        touch_DRAG: true,
        mouse_DRAG: true,
        mouse_DOUBLE_CLICK: true,
        mouse_WHEEL: true,
        mouse_WHEEL_CURSOR_POS: true,

        // Animation
        animation_SMOOTHNESS: 3,
        animation_SPEED_ZOOM: 3,
        animation_SPEED_PAN: 3,

        // Performance
        use_3D_Transform: true,

        // Appearance
        border_TRANSPARENCY: 0,
        background_COLOR: '#f8f9fa',

        // Callbacks
        on_IMAGE_LOAD: function() {
          addLandmarks().then(() => {
            initHighlightObserver();
            autoOpen();
          }).catch(err => {
            console.error('addLandmarks failed:', err);
          });
        }
      });
    };

    img.src = $img.attr('src');
  }

  function autoOpen() {
    if (window.__ill_autoOpened) return;

    // Read from sessionStorage (clean approach)
    const stored = sessionStorage.getItem('autoOpenCallout');
    if (!stored) return;

    let calloutData;
    try {
      calloutData = JSON.parse(stored);
    } catch (e) {
      sessionStorage.removeItem('autoOpenCallout');
      return;
    }

    const calloutKey = calloutData.callout;
    if (!calloutKey) {
      sessionStorage.removeItem('autoOpenCallout');
      return;
    }

    if (!metadataLoaded) {
      const maxRetries = 10;
      const currentRetry = window.__ill_autoOpenRetries || 0;

      if (currentRetry >= maxRetries) {
        window.__ill_autoOpened = true;
        sessionStorage.removeItem('autoOpenCallout');
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

    // Clear sessionStorage
    sessionStorage.removeItem('autoOpenCallout');

    const found = byKey[calloutKey];

    // Highlight the searched callout with red
    highlightSearchedCallout(calloutKey);

    // Scroll page to image area (so user sees the image after closing modal)
    scrollToImageArea();

    if (found && String(found.callout_type || '').toLowerCase() === 'section') {
      goToSection(calloutKey);
    } else {
      openCallout(calloutKey);
    }
  }

  function scrollToImageArea() {
    const zoomContainer = document.getElementById('zoom_container');
    if (zoomContainer) {
      // Scroll with offset to show some context above the image
      const rect = zoomContainer.getBoundingClientRect();
      const scrollTop = window.pageYOffset + rect.top - 100; // 100px offset from top
      window.scrollTo({ top: Math.max(0, scrollTop), behavior: 'smooth' });
    }
  }

  // Track currently highlighted callout from search
  let currentSearchedCallout = null;
  let $currentHighlightedEl = null;

  function highlightSearchedCallout(calloutKey) {
    // Guard: skip if same callout already highlighted
    if (calloutKey && calloutKey === currentSearchedCallout && $currentHighlightedEl && $currentHighlightedEl.length) {
      return;
    }

    // Remove previous highlight (direct reference, no DOM query)
    if ($currentHighlightedEl && $currentHighlightedEl.length) {
      $currentHighlightedEl.removeClass('callout-searched');
    }

    // Reset tracking
    currentSearchedCallout = null;
    $currentHighlightedEl = null;

    // Find and highlight the new one
    if (calloutKey) {
      const $callout = $(`.callout-label[data-callout-key="${calloutKey}"]`);
      if ($callout.length) {
        $callout.addClass('callout-searched');
        currentSearchedCallout = calloutKey;
        $currentHighlightedEl = $callout;
      }
    }
  }

  function clearSearchedHighlight() {
    if ($currentHighlightedEl && $currentHighlightedEl.length) {
      $currentHighlightedEl.removeClass('callout-searched');
    }
    currentSearchedCallout = null;
    $currentHighlightedEl = null;
  }

  // Re-apply highlight after zoom library redraws landmarks (if needed)
  function reapplyHighlightAfterZoom() {
    if (currentSearchedCallout) {
      const $callout = $(`.callout-label[data-callout-key="${currentSearchedCallout}"]`);
      if ($callout.length && !$callout.hasClass('callout-searched')) {
        $callout.addClass('callout-searched');
        $currentHighlightedEl = $callout;
      }
    }
  }

  // MutationObserver to detect if landmarks are recreated and reapply highlight
  function initHighlightObserver() {
    const landmarks = document.querySelector('.landmarks');
    if (!landmarks || !window.MutationObserver) return;

    const observer = new MutationObserver((mutations) => {
      // Only act if we have a highlight to preserve
      if (!currentSearchedCallout) return;

      // Check if our highlighted element was removed or class was stripped
      let needsReapply = false;
      for (const mutation of mutations) {
        if (mutation.type === 'childList' && mutation.removedNodes.length > 0) {
          needsReapply = true;
          break;
        }
      }

      if (needsReapply) {
        // Debounce to avoid multiple rapid calls
        clearTimeout(window.__highlightReapplyTimer);
        window.__highlightReapplyTimer = setTimeout(reapplyHighlightAfterZoom, 100);
      }
    });

    observer.observe(landmarks, { childList: true, subtree: true });
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
