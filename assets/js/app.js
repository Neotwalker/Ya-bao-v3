import { initHeader, initMobileCta } from './components/header.js';
import { initHeroVideo } from './components/video.js';
import { initModal } from './components/modal.js';
import { initForms } from './components/forms.js';
import { initFilters } from './components/filters.js';
import { initAccordion } from './components/accordion.js';
import { initReveal } from './components/reveal.js';
import { initCatalog } from './components/catalog.js';

document.documentElement.classList.add('js');


function initGuidesSwiper() {
  const slider = document.querySelector('[data-guides-swiper]');
  if (!slider || typeof window.Swiper !== 'function') return;

  new window.Swiper(slider, {
    slidesPerView: 1,
    spaceBetween: 18,
    speed: 520,
    grabCursor: true,
    watchOverflow: true,
    navigation: {
      prevEl: '.guide-slider-v4__button--prev',
      nextEl: '.guide-slider-v4__button--next',
    },
    pagination: {
      el: '.guide-slider-v4__pagination',
      clickable: true,
    },
    breakpoints: {
      700: { slidesPerView: 2 },
      1100: { slidesPerView: 3 },
    },
  });
}

function initTopScroll() {
  const button = document.querySelector('[data-top-scroll]');
  if (!button) return;

  const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  const update = () => button.classList.toggle('is-visible', window.scrollY > 520);

  update();
  window.addEventListener('scroll', update, { passive: true });
  button.addEventListener('click', () => {
    window.scrollTo({ top: 0, behavior: reduceMotion ? 'auto' : 'smooth' });
  });
}

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
  initGuidesSwiper,
  initTopScroll,
].forEach(init => {
  try { init(); } catch (error) { console.error(`${init.name} failed`, error); }
});
