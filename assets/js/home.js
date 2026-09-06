import { initHeroVideo } from './components/video.js';
import { initEventsSwipers } from './components/events-swiper.js';
import { runInitializers } from './utils/init.js';
import { onScroll } from './utils/scroll.js';

function initGuidesSwiper() {
  const slider = document.querySelector('[data-guides-swiper]');
  if (!slider || slider.dataset.swiperReady === 'true' || typeof window.Swiper !== 'function') return;

  slider.dataset.swiperReady = 'true';
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
  if (!button || button.dataset.topScrollReady === 'true') return;
  button.dataset.topScrollReady = 'true';

  const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  onScroll(scrollY => button.classList.toggle('is-visible', scrollY > 520));

  button.addEventListener('click', () => {
    window.scrollTo({ top: 0, behavior: reduceMotion ? 'auto' : 'smooth' });
  });
}

function initSpaceGalleryFancybox() {
  const gallery = document.querySelector('[data-fancybox="space-gallery"]');
  if (!gallery || document.documentElement.dataset.spaceGalleryReady === 'true') return;
  if (!window.Fancybox || typeof window.Fancybox.bind !== 'function') return;

  document.documentElement.dataset.spaceGalleryReady = 'true';
  window.Fancybox.bind('[data-fancybox="space-gallery"]', {});
}

function initHomePage() {
  runInitializers(
    initHeroVideo,
    initSpaceGalleryFancybox,
    initEventsSwipers,
    initGuidesSwiper,
    initTopScroll,
  );
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initHomePage, { once: true });
} else {
  initHomePage();
}
