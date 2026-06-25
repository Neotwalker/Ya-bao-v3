// Toggle mobile menu
document.addEventListener('DOMContentLoaded', () => {
  const burger = document.getElementById('burger');
  const navOverlay = document.getElementById('navOverlay');
  const navLinks = navOverlay.querySelectorAll('.nav-overlay__link');

  const toggleNav = () => {
    const isOpen = navOverlay.classList.toggle('nav-overlay--open');
    burger.setAttribute('aria-expanded', isOpen);
    document.body.style.overflow = isOpen ? 'hidden' : '';
  };

  burger.addEventListener('click', toggleNav);

  navLinks.forEach(link => {
    link.addEventListener('click', () => {
      navOverlay.classList.remove('nav-overlay--open');
      burger.setAttribute('aria-expanded', 'false');
      document.body.style.overflow = '';
    });
  });

  // Simple parallax effect on mouse move for desktop
  const hero = document.getElementById('hero');
  const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  if (!prefersReducedMotion) {
    hero.addEventListener('mousemove', e => {
      const rect = hero.getBoundingClientRect();
      const x = (e.clientX - rect.left) / rect.width - 0.5;
      const y = (e.clientY - rect.top) / rect.height - 0.5;
      const steamElements = document.querySelectorAll('.steam');
      const candle = document.querySelector('.candle');

      steamElements.forEach((el, idx) => {
        const movement = (idx + 1) * 10;
        el.style.transform = `translate(${x * movement}px, ${y * movement}px)`;
      });
      candle.style.transform = `translate(${x * 15}px, ${y * 15}px)`;
    });
  }
});
