import { initHeader, initMobileCta } from './components/header.js';
import { initHeroVideo } from './components/video.js';
import { initModal } from './components/modal.js';
import { initForms } from './components/forms.js';
import { initFilters } from './components/filters.js';
import { initAccordion } from './components/accordion.js';
import { initGallery } from './components/gallery.js';
import { initReveal } from './components/reveal.js';
import { initReviewSlider } from './components/reviews.js';
import { initCatalog } from './components/catalog.js';

document.documentElement.classList.add('js');
[
  initHeader,
  initMobileCta,
  initHeroVideo,
  initModal,
  initForms,
  initFilters,
  initAccordion,
  initGallery,
  initReveal,
  initReviewSlider,
  initCatalog,
].forEach(init => {
  try { init(); } catch (error) { console.error(`${init.name} failed`, error); }
});
