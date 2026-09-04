export function initReveal() {
  const elements = [...document.querySelectorAll('.reveal')]
    .filter(element => element.dataset.revealReady !== 'true');
  if (!elements.length) return;

  elements.forEach(element => { element.dataset.revealReady = 'true'; });

  if (matchMedia('(prefers-reduced-motion: reduce)').matches) {
    elements.forEach(element => element.classList.add('is-visible'));
    return;
  }

  const observer = new IntersectionObserver(entries => {
    entries.forEach(entry => {
      if (!entry.isIntersecting) return;
      entry.target.classList.add('is-visible');
      observer.unobserve(entry.target);
    });
  }, { threshold: .08, rootMargin: '0px 0px -2% 0px' });

  elements.forEach(element => observer.observe(element));
}
