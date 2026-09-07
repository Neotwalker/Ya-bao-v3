import { initRelatedArticlesSwipers } from './components/related-articles-swiper.js';

let catalogRequest = null;
let catalogSearchTimer = null;

function catalogUrlFromForm(form) {
  const url = new URL(form.action, window.location.href);
  const data = new FormData(form);

  url.search = '';
  for (const [key, rawValue] of data.entries()) {
    const value = String(rawValue).trim();
    if (!value) continue;
    if (key === 'orderby' && value === 'menu_order') continue;
    url.searchParams.set(key, value);
  }

  return url;
}

function initDynamicCatalogContent(root = document) {
  root.querySelectorAll('[data-card-gallery]').forEach(initCardGallery);
}

async function updateCatalog(url, { push = true, focusGrid = false } = {}) {
  const currentShell = document.querySelector('[data-wc-catalog-shell]');
  if (!currentShell) {
    window.location.assign(url);
    return;
  }

  const activeElement = document.activeElement;
  const restoreSearchFocus = activeElement?.matches?.('input[name=\"q\"]') && currentShell.contains(activeElement);
  const searchSelection = restoreSearchFocus ? activeElement.selectionStart : null;

  catalogRequest?.abort();
  catalogRequest = new AbortController();
  currentShell.classList.add('is-loading');
  currentShell.setAttribute('aria-busy', 'true');

  try {
    const response = await fetch(url, {
      method: 'GET',
      credentials: 'same-origin',
      headers: { 'X-Requested-With': 'XMLHttpRequest' },
      signal: catalogRequest.signal,
    });

    if (!response.ok) throw new Error(`Catalog request failed: ${response.status}`);

    const html = await response.text();
    const doc = new DOMParser().parseFromString(html, 'text/html');
    const nextShell = doc.querySelector('[data-wc-catalog-shell]');
    if (!nextShell) throw new Error('Catalog shell is missing in the response');

    currentShell.replaceWith(nextShell);
    initCatalogControls(nextShell.querySelector('[data-wc-catalog-controls]'));
    initDynamicCatalogContent(nextShell);

    if (restoreSearchFocus) {
      const nextSearch = nextShell.querySelector('input[name=\"q\"]');
      nextSearch?.focus({ preventScroll: true });
      if (nextSearch && Number.isInteger(searchSelection)) {
        const caret = Math.min(searchSelection, nextSearch.value.length);
        nextSearch.setSelectionRange(caret, caret);
      }
    }

    if (push) history.pushState({ yabaoCatalog: true }, '', url);

    if (focusGrid) {
      const grid = nextShell.querySelector('#shop-grid, .shop-state');
      grid?.setAttribute('tabindex', '-1');
      grid?.focus({ preventScroll: true });
    }
  } catch (error) {
    if (error?.name === 'AbortError') return;
    window.location.assign(url);
  }
}

function initCatalogControls(form) {
  if (!form || form.dataset.catalogReady === 'true') return;
  form.dataset.catalogReady = 'true';

  const toggle = form.querySelector('[data-wc-filters-toggle]');
  const panel = form.querySelector('[data-wc-filter-panel]');
  const search = form.querySelector('input[name="q"]');

  const setOpen = open => {
    if (!toggle || !panel) return;
    toggle.setAttribute('aria-expanded', String(open));
    panel.classList.toggle('is-open', open);
  };

  const run = ({ focusGrid = false } = {}) => {
    clearTimeout(catalogSearchTimer);
    updateCatalog(catalogUrlFromForm(form), { push: true, focusGrid });
  };

  toggle?.addEventListener('click', () => {
    setOpen(toggle.getAttribute('aria-expanded') !== 'true');
  });

  form.addEventListener('submit', event => {
    event.preventDefault();
    run({ focusGrid: true });
  });

  form.querySelectorAll('[data-wc-auto-submit]').forEach(control => {
    control.addEventListener('change', () => run());
  });

  search?.addEventListener('input', () => {
    clearTimeout(catalogSearchTimer);
    catalogSearchTimer = window.setTimeout(() => run(), 320);
  });

  const desktop = window.matchMedia('(min-width: 641px)');
  const syncViewport = event => {
    if (event.matches) setOpen(false);
  };
  if (typeof desktop.addEventListener === 'function') desktop.addEventListener('change', syncViewport);
  else if (typeof desktop.addListener === 'function') desktop.addListener(syncViewport);
}

function initCatalogNavigation() {
  document.addEventListener('click', event => {
    const link = event.target.closest(
      '[data-wc-catalog-shell] .shop-filter, [data-wc-catalog-shell] .shop-reset, [data-wc-catalog-shell] .yabao-wc-pagination a, [data-wc-catalog-shell] .shop-state a'
    );
    if (!link || event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
    if (link.target && link.target !== '_self') return;

    let url = new URL(link.href, window.location.href);
    if (url.origin !== window.location.origin) return;

    event.preventDefault();

    if (link.matches('.shop-filter')) {
      const form = document.querySelector('[data-wc-catalog-controls]');
      const category = form?.querySelector('select[name=\"product_cat\"]');
      if (form && category) {
        category.value = link.dataset.wcCategory || '';
        url = catalogUrlFromForm(form);
      }
    }

    updateCatalog(url, { push: true, focusGrid: true });
  });

  window.addEventListener('popstate', () => {
    updateCatalog(new URL(window.location.href), { push: false });
  });
}

function initProductGallery(gallery) {
  if (gallery.dataset.galleryReady === 'true') return;
  const slides = [...gallery.querySelectorAll('[data-product-slide]')];
  const thumbs = [...gallery.querySelectorAll('[data-product-thumb]')];
  const stage = gallery.querySelector('[data-product-stage]');
  if (!slides.length || !thumbs.length) return;
  gallery.dataset.galleryReady = 'true';
  let index = 0;
  let startX = 0;
  let startY = 0;

  const show = next => {
    index = Math.max(0, Math.min(slides.length - 1, next));
    slides.forEach((slide, i) => {
      slide.hidden = i !== index;
      slide.classList.toggle('is-active', i === index);
    });
    thumbs.forEach((thumb, i) => {
      thumb.classList.toggle('is-active', i === index);
      thumb.setAttribute('aria-current', String(i === index));
    });
  };

  thumbs.forEach((thumb, i) => thumb.addEventListener('click', () => show(i)));
  stage?.addEventListener('touchstart', e => {
    startX = e.touches[0].clientX;
    startY = e.touches[0].clientY;
  }, { passive: true });
  stage?.addEventListener('touchend', e => {
    const dx = e.changedTouches[0].clientX - startX;
    const dy = e.changedTouches[0].clientY - startY;
    if (Math.abs(dx) > 42 && Math.abs(dx) > Math.abs(dy) * 1.2) show(index + (dx < 0 ? 1 : -1));
  }, { passive: true });
  show(0);
}

function initCardGallery(gallery) {
  if (gallery.dataset.galleryReady === 'true') return;
  const slides = [...gallery.querySelectorAll('[data-card-slide]')];
  const dots = [...gallery.querySelectorAll('[data-card-dot]')];
  if (slides.length < 2) return;
  gallery.dataset.galleryReady = 'true';
  let index = 0;
  let startX = 0;
  let startY = 0;
  const show = next => {
    index = Math.max(0, Math.min(slides.length - 1, next));
    slides.forEach((slide, i) => { slide.hidden = i !== index; });
    dots.forEach((dot, i) => dot.classList.toggle('is-active', i === index));
  };
  const pointer = e => {
    if (e.pointerType === 'touch') return;
    const rect = gallery.getBoundingClientRect();
    if (!rect.width) return;
    show(Math.floor(Math.max(0, Math.min(.999, (e.clientX - rect.left) / rect.width)) * slides.length));
  };
  gallery.addEventListener('pointerenter', pointer);
  gallery.addEventListener('pointermove', pointer);
  gallery.addEventListener('pointerleave', e => { if (e.pointerType !== 'touch') show(0); });
  gallery.addEventListener('touchstart', e => {
    startX = e.touches[0].clientX;
    startY = e.touches[0].clientY;
  }, { passive: true });
  gallery.addEventListener('touchend', e => {
    const dx = e.changedTouches[0].clientX - startX;
    const dy = e.changedTouches[0].clientY - startY;
    if (Math.abs(dx) > 42 && Math.abs(dx) > Math.abs(dy) * 1.2) show(index + (dx < 0 ? 1 : -1));
  }, { passive: true });
  show(0);
}

document.querySelectorAll('[data-wc-catalog-controls]').forEach(initCatalogControls);
initCatalogNavigation();
document.querySelectorAll('[data-product-gallery]').forEach(initProductGallery);
initDynamicCatalogContent();
initRelatedArticlesSwipers();
