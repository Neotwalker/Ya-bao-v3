
export function initReviewSlider(){
  document.querySelectorAll('[data-review-slider]').forEach(slider => {
    const track = slider.querySelector('[data-review-track]');
    const prev = slider.querySelector('[data-slider-prev]');
    const next = slider.querySelector('[data-slider-next]');
    if(!track) return;
    const step = () => {
      const card = track.querySelector('.review-slide');
      if(!card) return track.clientWidth;
      const gap = parseFloat(getComputedStyle(track).gap || 0);
      return card.getBoundingClientRect().width + gap;
    };
    prev?.addEventListener('click', () => track.scrollBy({left: -step(), behavior: 'smooth'}));
    next?.addEventListener('click', () => track.scrollBy({left: step(), behavior: 'smooth'}));
  });
}
