const LOOP_FADE_SECONDS = 0.28;
const LOOP_FADE_MS = 260;

export function initHeroMotion() {
  const hero = document.querySelector('[data-hero]');
  const video = document.querySelector('[data-hero-video]');
  if (!hero || !video) return;

  const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
  const saveData = navigator.connection?.saveData === true;

  let isVisible = true;
  let isResetting = false;
  let animationFrame = 0;
  let resetTimer = 0;

  const shouldPlay = () =>
    isVisible && !document.hidden && !reducedMotion.matches && !saveData;

  const revealVideo = () => {
    requestAnimationFrame(() => {
      hero.classList.remove('hero--video-looping');
      isResetting = false;
    });
  };

  const resetLoop = () => {
    if (isResetting || !Number.isFinite(video.duration)) return;

    isResetting = true;
    hero.classList.add('hero--video-looping');

    window.clearTimeout(resetTimer);
    resetTimer = window.setTimeout(() => {
      video.currentTime = 0;

      const playPromise = shouldPlay() ? video.play() : null;
      playPromise?.catch?.(() => {
        // The exact first-frame poster remains visible if autoplay is blocked.
      });

      if ('requestVideoFrameCallback' in video) {
        video.requestVideoFrameCallback(revealVideo);
      } else {
        window.setTimeout(revealVideo, 80);
      }
    }, LOOP_FADE_MS);
  };

  const watchLoop = () => {
    if (
      shouldPlay() &&
      !video.paused &&
      !isResetting &&
      Number.isFinite(video.duration) &&
      video.duration > LOOP_FADE_SECONDS &&
      video.currentTime >= video.duration - LOOP_FADE_SECONDS
    ) {
      resetLoop();
    }

    animationFrame = requestAnimationFrame(watchLoop);
  };

  const syncPlayback = () => {
    if (!shouldPlay()) {
      video.pause();
      return;
    }

    const playPromise = video.play();
    playPromise?.catch?.(() => {
      // The poster remains visible if a browser blocks autoplay.
    });
  };

  const observer = new IntersectionObserver(
    ([entry]) => {
      isVisible = entry.isIntersecting;
      syncPlayback();
    },
    { threshold: 0.08 }
  );

  observer.observe(hero);

  video.addEventListener(
    'canplay',
    () => hero.classList.add('hero--video-ready'),
    { once: true }
  );

  video.addEventListener('ended', resetLoop);
  document.addEventListener('visibilitychange', syncPlayback);
  reducedMotion.addEventListener?.('change', syncPlayback);

  animationFrame = requestAnimationFrame(watchLoop);
  syncPlayback();

  window.addEventListener(
    'pagehide',
    () => {
      cancelAnimationFrame(animationFrame);
      window.clearTimeout(resetTimer);
      observer.disconnect();
    },
    { once: true }
  );
}
