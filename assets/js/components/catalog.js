import {
  getCachedSheets,
  setCachedSheets,
} from './catalog/cache.js';
import { loadSheets } from './catalog/sheets.js';
import {
  mergeProducts,
  applyProductsToCards,
  setCardState,
} from './catalog/products.js';
import { renderMenuItems, showCatalogNotice } from './catalog/render.js';

function dispatchCatalogUpdated(root) {
  root.dispatchEvent(new CustomEvent('catalog:updated', { bubbles: true }));
}

function renderSheets(root, sheets, { fromCache = false } = {}) {
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

  root.dataset.catalogNetworkError = 'false';
  dispatchCatalogUpdated(root);
  return stats;
}

function showStaticFallback(root, message) {
  document.querySelectorAll('[data-catalog-card]').forEach(card => {
    setCardState(card, 'unknown', 'Наличие уточняется');
    card.hidden = false;
  });

  showCatalogNotice('error', message);
  root.dataset.catalogNetworkError = 'true';
  dispatchCatalogUpdated(root);
}

export async function initCatalog() {
  const root = document.querySelector('[data-live-catalog]');
  if (!root || root.dataset.catalogReady === 'true') return;
  root.dataset.catalogReady = 'true';

  let refreshPromise = null;

  const refreshFromNetwork = async ({ reconnect = false } = {}) => {
    if (refreshPromise) return refreshPromise;

    refreshPromise = (async () => {
      root.setAttribute('aria-busy', 'true');

      if (navigator.onLine === false) {
        showStaticFallback(
          root,
          'Нет подключения к сети. Карточки показаны, наличие можно уточнить у администратора.',
        );
        return;
      }

      showCatalogNotice(
        'loading',
        reconnect
          ? 'Связь восстановлена. Обновляем меню…'
          : 'Проверяем актуальное наличие…',
      );

      try {
        const sheets = await loadSheets();
        setCachedSheets(sheets);
        renderSheets(root, sheets);
      } catch (error) {
        console.error('[catalog]', error);
        showStaticFallback(
          root,
          'Не удалось проверить остатки. Карточки показаны, наличие можно уточнить у администратора.',
        );
      } finally {
        root.setAttribute('aria-busy', 'false');
      }
    })();

    try {
      await refreshPromise;
    } finally {
      refreshPromise = null;
    }
  };

  const cachedSheets = getCachedSheets();

  if (cachedSheets) {
    try {
      renderSheets(root, cachedSheets, { fromCache: true });
      root.setAttribute('aria-busy', 'false');
    } catch (error) {
      console.warn('[catalog] cache:', error);
      await refreshFromNetwork();
    }
  } else {
    await refreshFromNetwork();
  }

  window.addEventListener('online', () => {
    if (root.dataset.catalogNetworkError === 'true') {
      refreshFromNetwork({ reconnect: true });
    }
  });
}
