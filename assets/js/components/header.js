export function initHeader() {
  const header = document.querySelector('[data-header]');
  const toggle = document.querySelector('[data-menu-toggle]');
  const mobileNav = document.querySelector('[data-mobile-nav]');
  if (!header) return;

  const updateHeader = () => header.classList.toggle('is-scrolled', window.scrollY > 18);
  updateHeader();
  window.addEventListener('scroll', updateHeader, { passive: true });

  if (!toggle || !mobileNav) return;
  const closeMenu = () => {
    toggle.setAttribute('aria-expanded', 'false');
    mobileNav.classList.remove('is-open');
    mobileNav.setAttribute('aria-hidden', 'true');
    document.body.classList.remove('is-locked');
  };
  const openMenu = () => {
    toggle.setAttribute('aria-expanded', 'true');
    mobileNav.classList.add('is-open');
    mobileNav.setAttribute('aria-hidden', 'false');
    document.body.classList.add('is-locked');
    mobileNav.querySelector('a')?.focus();
  };

  toggle.addEventListener('click', () => {
    toggle.getAttribute('aria-expanded') === 'true' ? closeMenu() : openMenu();
  });
  mobileNav.querySelectorAll('a').forEach(link => link.addEventListener('click', closeMenu));
  document.addEventListener('keydown', event => {
    if (event.key === 'Escape' && mobileNav.classList.contains('is-open')) {
      closeMenu();
      toggle.focus();
    }
  });
}

export function initMobileCta() {
  const cta = document.querySelector('[data-mobile-cta]');
  const hero = document.querySelector('.hero, .page-hero');
  if (!cta || !hero) return;
  const observer = new IntersectionObserver(([entry]) => {
    cta.classList.toggle('is-visible', !entry.isIntersecting);
  }, { threshold: .1 });
  observer.observe(hero);
}
