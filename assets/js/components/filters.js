export function initFilters() {
  document.querySelectorAll('[data-filter-group]').forEach(group => {
    const targetSelector = group.dataset.filterTarget;
    const items = document.querySelectorAll(targetSelector);
    group.querySelectorAll('[data-filter]').forEach(button => {
      button.addEventListener('click', () => {
        const value = button.dataset.filter;
        group.querySelectorAll('[data-filter]').forEach(btn => btn.setAttribute('aria-pressed', String(btn === button)));
        items.forEach(item => {
          item.hidden = value !== 'all' && item.dataset.category !== value;
        });
      });
    });
  });
}
