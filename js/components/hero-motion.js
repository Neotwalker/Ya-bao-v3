export function initHeroMotion() {
  const hero = document.querySelector('[data-hero]');
  const scene = document.querySelector('[data-scene]');
  if (!hero || !scene) return;

  const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
  const finePointer = window.matchMedia('(pointer: fine)');

  const reset = () => {
    scene.style.setProperty('--scene-x', '0px');
    scene.style.setProperty('--scene-y', '0px');
  };

  const onPointerMove = (event) => {
    if (prefersReducedMotion.matches || !finePointer.matches || window.innerWidth < 768) return;
    const rect = hero.getBoundingClientRect();
    const x = (event.clientX - rect.left) / rect.width - 0.5;
    const y = (event.clientY - rect.top) / rect.height - 0.5;
    scene.style.setProperty('--scene-x', `${x * -9}px`);
    scene.style.setProperty('--scene-y', `${y * -6}px`);
  };

  hero.addEventListener('pointermove', onPointerMove, { passive: true });
  hero.addEventListener('pointerleave', reset);
  prefersReducedMotion.addEventListener?.('change', reset);
}
