import { initHeader, initMobileCta } from './components/header.js';
import { initHeroVideo } from './components/video.js';
import { initModal } from './components/modal.js';
import { initForms } from './components/forms.js';
import { initFilters } from './components/filters.js';
import { initAccordion } from './components/accordion.js';
import { initReveal } from './components/reveal.js';
import { initCatalog } from './components/catalog.js';

document.documentElement.classList.add('js');

function initSpaceGalleryFancybox() {
  if (!window.Fancybox || typeof window.Fancybox.bind !== 'function') return;
  window.Fancybox.bind('[data-fancybox="space-gallery"]', {});
}
[
  initHeader,
  initMobileCta,
  initHeroVideo,
  initModal,
  initForms,
  initFilters,
  initAccordion,
  initReveal,
  initCatalog,
  initSpaceGalleryFancybox,
].forEach(init => {
  try { init(); } catch (error) { console.error(`${init.name} failed`, error); }
});
