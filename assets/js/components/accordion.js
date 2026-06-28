export function initAccordion() {
  document.querySelectorAll('[data-accordion-button]').forEach(button => {
    button.addEventListener('click', () => {
      const expanded = button.getAttribute('aria-expanded') === 'true';
      button.setAttribute('aria-expanded', String(!expanded));
    });
  });
}
