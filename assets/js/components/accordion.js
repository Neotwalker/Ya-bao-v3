export function initAccordion() {
  document.querySelectorAll('[data-accordion]').forEach(accordion => {
    if (accordion.dataset.accordionReady === 'true') return;
    accordion.dataset.accordionReady = 'true';

    accordion.querySelectorAll('.accordion__item > button').forEach(button => {
      button.addEventListener('click', () => {
        const expanded = button.getAttribute('aria-expanded') === 'true';
        button.setAttribute('aria-expanded', String(!expanded));
      });
    });
  });
}
