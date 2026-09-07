import { onScroll } from '../utils/scroll.js';
import { lockBody, unlockBody } from '../utils/body-lock.js';
import { getFocusable, trapTab } from '../utils/focus.js';

export function initHeader() {
  const header = document.querySelector('[data-header]');
  const toggle = document.querySelector('[data-menu-toggle]');
  const mobileNav = document.querySelector('[data-mobile-nav]');
  if (!header || header.dataset.headerReady === 'true') return;
  header.dataset.headerReady = 'true';

  onScroll(scrollY => header.classList.toggle('is-scrolled', scrollY > 18));

  if (!toggle || !mobileNav) return;

  const closeMenu = ({ restoreFocus = false } = {}) => {
    toggle.setAttribute('aria-expanded', 'false');
    mobileNav.classList.remove('is-open');
    mobileNav.setAttribute('aria-hidden', 'true');
    unlockBody('mobile-nav');
    if (restoreFocus) toggle.focus();
  };

  const openMenu = () => {
    toggle.setAttribute('aria-expanded', 'true');
    mobileNav.classList.add('is-open');
    mobileNav.setAttribute('aria-hidden', 'false');
    lockBody('mobile-nav');
    requestAnimationFrame(() => mobileNav.querySelector('a,button')?.focus());
  };

  toggle.addEventListener('click', () => {
    toggle.getAttribute('aria-expanded') === 'true'
      ? closeMenu()
      : openMenu();
  });

  mobileNav.querySelectorAll('a, [data-modal-open]').forEach(control => {
    control.addEventListener('click', () => closeMenu());
  });

  document.addEventListener('keydown', event => {
    if (!mobileNav.classList.contains('is-open')) return;

    if (event.key === 'Escape') {
      event.preventDefault();
      closeMenu({ restoreFocus: true });
      return;
    }

    trapTab(event, [toggle, ...getFocusable(mobileNav)]);
  });

  const desktopQuery = window.matchMedia('(min-width: 768px)');
  const handleDesktopChange = event => {
    if (event.matches && mobileNav.classList.contains('is-open')) {
      closeMenu();
    }
  };

  desktopQuery.addEventListener?.('change', handleDesktopChange);
}

