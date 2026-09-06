export function initHeroVideo() {
  const media = document.querySelector('[data-hero-media]');
  const video = media?.querySelector('video');
  if (!media || !video || media.dataset.heroVideoReady === 'true') return;
  media.dataset.heroVideoReady = 'true';

  const reduced = matchMedia('(prefers-reduced-motion: reduce)').matches;
  const saveData = navigator.connection?.saveData;
  if (reduced || saveData) {
    video.removeAttribute('autoplay');
    video.pause();
    return;
  }

  const source = video.querySelector('source[data-src]');
  const poster = media.querySelector('.hero-v4__poster');
  let hydrated = false;
  let inView = true;

  const playIfVisible = () => {
    if (!document.hidden && inView) video.play().catch(() => {});
  };

  const hydrate = () => {
    if (hydrated || !source) return;
    hydrated = true;
    source.src = source.dataset.src;
    source.removeAttribute('data-src');
    video.load();
    playIfVisible();
  };

  const scheduleHydration = () => {
    if ('requestIdleCallback' in window) {
      window.requestIdleCallback(hydrate, { timeout: 1200 });
    } else {
      window.setTimeout(hydrate, 250);
    }
  };

  if (!poster || poster.complete) scheduleHydration();
  else poster.addEventListener('load', scheduleHydration, { once: true });

  video.addEventListener('canplay', () => media.classList.add('is-ready'), { once: true });

  const observer = new IntersectionObserver(([entry]) => {
    inView = entry.isIntersecting;
    if (document.hidden || !inView) video.pause();
    else if (hydrated) playIfVisible();
  }, { threshold: .05 });
  observer.observe(video);

  document.addEventListener('visibilitychange', () => {
    if (document.hidden) video.pause();
    else if (hydrated) playIfVisible();
  });
}
