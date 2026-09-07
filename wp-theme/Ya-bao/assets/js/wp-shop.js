import { initRelatedArticlesSwipers } from './components/related-articles-swiper.js';


function initCatalogControls(form) {
  if (!form || form.dataset.catalogReady === 'true') return;
  form.dataset.catalogReady = 'true';

  const toggle = form.querySelector('[data-wc-filters-toggle]');
  const panel = form.querySelector('[data-wc-filter-panel]');

  const setOpen = open => {
    if (!toggle || !panel) return;
    toggle.setAttribute('aria-expanded', String(open));
    panel.classList.toggle('is-open', open);
  };

  toggle?.addEventListener('click', () => {
    setOpen(toggle.getAttribute('aria-expanded') !== 'true');
  });

  form.querySelectorAll('[data-wc-auto-submit]').forEach(control => {
    control.addEventListener('change', () => form.requestSubmit());
  });

  const desktop = window.matchMedia('(min-width: 641px)');
  const syncViewport = event => {
    if (event.matches) setOpen(false);
  };
  if (typeof desktop.addEventListener === 'function') desktop.addEventListener('change', syncViewport);
  else if (typeof desktop.addListener === 'function') desktop.addListener(syncViewport);
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
document.querySelectorAll('[data-product-gallery]').forEach(initProductGallery);
document.querySelectorAll('[data-card-gallery]').forEach(initCardGallery);
initRelatedArticlesSwipers();
