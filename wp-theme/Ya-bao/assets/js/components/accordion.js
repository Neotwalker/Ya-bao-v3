export function initAccordion() {
  document.querySelectorAll('[data-accordion]').forEach((accordion, accordionIndex) => {
    if (accordion.dataset.accordionReady === 'true') return;
    accordion.dataset.accordionReady = 'true';

    accordion.querySelectorAll('.accordion__item').forEach((item, itemIndex) => {
      const button = item.querySelector(':scope > button');
      const panel = item.querySelector(':scope > .accordion__panel');
      if (!button || !panel) return;

      const base = `accordion-${accordionIndex + 1}-${itemIndex + 1}`;
      if (!button.id) button.id = `${base}-button`;
      if (!panel.id) panel.id = `${base}-panel`;

      button.setAttribute('aria-controls', panel.id);
      panel.setAttribute('aria-labelledby', button.id);
      panel.setAttribute('role', 'region');

      button.addEventListener('click', () => {
        const expanded = button.getAttribute('aria-expanded') === 'true';
        button.setAttribute('aria-expanded', String(!expanded));
      });
    });
  });
}
