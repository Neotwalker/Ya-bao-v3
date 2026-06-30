export function initFilters() {
  document.querySelectorAll('[data-filter-group]').forEach(group => {
    const targetSelector = group.dataset.filterTarget;
    const buttons = [...group.querySelectorAll('[data-filter]')];
    let active = 'all';

    const apply = value => {
      active = value;
      const items = [...document.querySelectorAll(targetSelector)];
      buttons.forEach(btn => btn.setAttribute('aria-pressed', String(btn.dataset.filter === value)));
      items.forEach(item => {
        const filteredOut = value !== 'all' && item.dataset.category !== value;
        const hiddenByStock = item.dataset.stockState === 'out' && item.dataset.stockVisibility === 'hide';
        item.hidden = filteredOut || hiddenByStock;
      });
    };

    buttons.forEach(button => button.addEventListener('click', () => apply(button.dataset.filter)));
    const requested = new URLSearchParams(location.search).get('category');
    if (requested && buttons.some(btn => btn.dataset.filter === requested)) active = requested;
    apply(active);

    document.addEventListener('catalog:updated', () => apply(active));
  });
}
