
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
    track.addEventListener('wheel', event => {
      if (track.scrollWidth <= track.clientWidth) return;
      const delta = Math.abs(event.deltaX) > Math.abs(event.deltaY) ? event.deltaX : event.deltaY;
      const canLeft = track.scrollLeft > 0;
      const canRight = track.scrollLeft < track.scrollWidth - track.clientWidth - 2;
      if ((delta < 0 && canLeft) || (delta > 0 && canRight)) {
        event.preventDefault();
        track.scrollLeft += delta;
      }
    }, {passive:false});
  });
}
