export function initHeroVideo() {
  const media = document.querySelector('[data-hero-media]');
  const video = media?.querySelector('video');
  if (!media || !video) return;

  const reduced = matchMedia('(prefers-reduced-motion: reduce)').matches;
  const saveData = navigator.connection?.saveData;
  if (reduced || saveData) {
    video.removeAttribute('autoplay');
    video.pause();
    return;
  }

  const showVideo = () => media.classList.add('is-ready');
  video.addEventListener('canplay', showVideo, { once: true });
  video.play().catch(() => {});

  const observer = new IntersectionObserver(([entry]) => {
    if (document.hidden || !entry.isIntersecting) video.pause();
    else video.play().catch(() => {});
  }, { threshold: .05 });
  observer.observe(video);

  document.addEventListener('visibilitychange', () => {
    if (document.hidden) video.pause();
    else if (video.getBoundingClientRect().bottom > 0) video.play().catch(() => {});
  });
}
