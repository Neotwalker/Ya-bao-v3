export function initHeroMotion() {
  const hero = document.querySelector('[data-hero]');
  const video = document.querySelector('[data-hero-video]');
  if (!hero || !video) return;

  const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
  const saveData = navigator.connection?.saveData === true;

  const syncPlayback = () => {
    if (reducedMotion.matches || saveData || document.hidden) {
      video.pause();
      return;
    }

    const playPromise = video.play();
    if (playPromise && typeof playPromise.catch === 'function') {
      playPromise.catch(() => {
        // The poster remains visible if a browser blocks autoplay.
      });
    }
  };

  const observer = new IntersectionObserver(
    ([entry]) => {
      if (entry.isIntersecting) syncPlayback();
      else video.pause();
    },
    { threshold: 0.08 }
  );

  observer.observe(hero);
  document.addEventListener('visibilitychange', syncPlayback);
  reducedMotion.addEventListener?.('change', syncPlayback);
  video.addEventListener('canplay', () => hero.classList.add('hero--video-ready'), { once: true });

  syncPlayback();
}
