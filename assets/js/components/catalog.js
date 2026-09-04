import { getCachedSheets, setCachedSheets } from './catalog/cache.js';
import { loadSheets } from './catalog/sheets.js';
import {
  mergeProducts,
  applyProductsToCards,
  setCardState,
} from './catalog/products.js';
import { renderMenuItems, showCatalogNotice } from './catalog/render.js';

export async function initCatalog() {
  const root = document.querySelector('[data-live-catalog]');
  if (!root || root.dataset.catalogReady === 'true') return;
  root.dataset.catalogReady = 'true';

  showCatalogNotice('loading', 'Проверяем актуальное наличие…');
  root.setAttribute('aria-busy', 'true');

  try {
    let sheets = getCachedSheets();
    let fromCache = true;

    if (!sheets) {
      fromCache = false;
      sheets = await loadSheets();
      setCachedSheets(sheets);
    }

    const products = mergeProducts(sheets);
    if (!products.length) {
      throw new Error('В таблице не найдены позиции каталога.');
    }

    const rendered = renderMenuItems(sheets['Меню'] || [], products);
    const stats = rendered || applyProductsToCards(products);

    showCatalogNotice(
      'success',
      `${fromCache ? 'Меню загружено из кеша' : 'Меню обновлено'}: ${stats.total} позиций.`,
    );

    root.dispatchEvent(new CustomEvent('catalog:updated', { bubbles: true }));
  } catch (error) {
    console.error('[catalog]', error);

    document.querySelectorAll('[data-catalog-card]').forEach(card => {
      setCardState(card, 'unknown', 'Наличие уточняется');
      card.hidden = false;
    });

    showCatalogNotice(
      'error',
      'Не удалось проверить остатки. Карточки показаны, наличие можно уточнить у администратора.',
    );
  } finally {
    root.setAttribute('aria-busy', 'false');
  }
}
