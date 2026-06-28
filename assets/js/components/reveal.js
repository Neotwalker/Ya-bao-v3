export function initReveal() {
  const elements = document.querySelectorAll('.reveal');
  if (!elements.length) return;
  if (matchMedia('(prefers-reduced-motion: reduce)').matches) {
    elements.forEach(el => el.classList.add('is-visible'));
    return;
  }
  const observer = new IntersectionObserver(entries => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        entry.target.classList.add('is-visible');
        observer.unobserve(entry.target);
      }
    });
  }, { threshold: .14, rootMargin: '0px 0px -5% 0px' });
  elements.forEach(el => observer.observe(el));
}
