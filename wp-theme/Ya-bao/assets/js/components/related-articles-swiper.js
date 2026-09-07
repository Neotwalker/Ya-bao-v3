export function initRelatedArticlesSwipers(root = document) {
  root.querySelectorAll('[data-related-articles-swiper]').forEach(slider => {
    if (slider.dataset.swiperReady === 'true' || typeof window.Swiper !== 'function') return;

    const shell = slider.closest('[data-related-articles-shell]');
    if (!shell) return;

    const prev = shell.querySelector('[data-related-articles-prev]');
    const next = shell.querySelector('[data-related-articles-next]');
    const pagination = shell.querySelector('[data-related-articles-pagination]');

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
