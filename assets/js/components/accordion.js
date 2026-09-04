export function initAccordion() {
  document.querySelectorAll('[data-accordion]').forEach(accordion => {
    const buttons = accordion.querySelectorAll('.accordion__item > button');

    buttons.forEach(button => {
      button.addEventListener('click', () => {
        const expanded = button.getAttribute('aria-expanded') === 'true';
        button.setAttribute('aria-expanded', String(!expanded));
      });
    });
  });
}
