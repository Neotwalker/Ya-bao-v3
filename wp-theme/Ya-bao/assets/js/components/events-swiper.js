export function initEventsSwipers(root = document) {
  root.querySelectorAll('[data-events-swiper]').forEach(slider => {
    if (slider.dataset.swiperReady === 'true' || typeof window.Swiper !== 'function') return;

    const shell = slider.closest('[data-events-slider-shell]');
    if (!shell) return;

    const prev = shell.querySelector('[data-events-prev]');
    const next = shell.querySelector('[data-events-next]');
    const pagination = shell.querySelector('[data-events-pagination]');

    slider.dataset.swiperReady = 'true';

    new window.Swiper(slider, {
      slidesPerView: 1,
      spaceBetween: 18,
      speed: 520,
      grabCursor: true,
      watchOverflow: true,
      navigation: {
        prevEl: prev,
        nextEl: next,
      },
      pagination: {
        el: pagination,
        clickable: true,
      },
      breakpoints: {
        700: { slidesPerView: 2 },
        1100: { slidesPerView: 3 },
      },
    });
  });
}
