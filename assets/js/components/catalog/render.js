import { catalogConfig } from '../../config.catalog.js';
import { normalizeText, escapeHtml } from './normalize.js';
import {
  HEADER_ALIASES,
  CATEGORY_LABELS,
  CATEGORY_IMAGES,
  findColumn,
  inferNameColumn,
  categorySlug,
} from './schema.js';

function menuRowsToItems(rows) {
  if (!rows.length) return [];

  const headers = [...new Set(rows.flatMap(row => Object.keys(row)))];
  const nameColumn = inferNameColumn(rows, headers);
  const categoryColumn = findColumn(headers, HEADER_ALIASES.category);
  const subcategoryColumn = findColumn(headers, HEADER_ALIASES.subcategory);
  const priceColumn = findColumn(headers, HEADER_ALIASES.price);
  const descriptionColumn = findColumn(headers, HEADER_ALIASES.description);

  if (!nameColumn) return [];

  return rows.map(row => {
    const name = String(row[nameColumn] ?? '').trim();
    const category = categoryColumn ? String(row[categoryColumn] ?? '').trim() : '';
    const subcategory = subcategoryColumn ? String(row[subcategoryColumn] ?? '').trim() : '';
    const priceRaw = priceColumn ? row[priceColumn] : '';
    const description = descriptionColumn ? String(row[descriptionColumn] ?? '').trim() : '';

    const normalized = normalizeText(`${category} ${subcategory} ${name}`);
    if (!name || /кальян|табак|никотин/i.test(normalized)) return null;

    const slug = categorySlug(category, '', name, subcategory);
    if (!slug || !CATEGORY_LABELS[slug]) return null;

    const numeric = Number(String(priceRaw ?? '').replace(/\s/g, '').replace(',', '.'));
    const price = Number.isFinite(numeric) && numeric > 0
      ? `${numeric.toLocaleString('ru-RU')} ₽`
      : String(priceRaw ?? '').trim();

    return {
      name,
      category,
      subcategory,
      description,
      price,
      slug,
      key: normalizeText(name),
    };
  }).filter(Boolean);
}

export function renderMenuItems(rows, stockProducts) {
  const grid = document.querySelector('[data-menu-grid]');
  if (!grid) return null;

  const items = menuRowsToItems(rows);
  const stockMap = new Map(stockProducts.map(product => [product.key, product]));

  grid.innerHTML = items.map(item => {
    const stock = stockMap.get(item.key);
    let state = 'unknown';
    let stockLabel = 'Наличие уточняется';
    let hidden = false;

    if (stock?.sources?.includes('Склад') && stock.availability?.known) {
      if (stock.availability.available) {
        state = 'available';
        stockLabel = 'В наличии';
      } else {
        state = 'out';
        stockLabel = 'Закончился';
        hidden = catalogConfig.outOfStockMode === 'hide';
      }
    }

    const description = item.description
      ? `<p>${escapeHtml(item.description)}</p>`
      : '';

    return `<article class="card menu-card is-visible" data-category="${item.slug}" data-stock-state="${state}" data-stock-visibility="${catalogConfig.outOfStockMode}"${hidden ? ' hidden' : ''}>
      <div class="card__image"><img alt="${escapeHtml(item.name)}" loading="lazy" src="${CATEGORY_IMAGES[item.slug]}"/></div>
      <div class="card__body">
        <div class="card__meta">
          <span>${CATEGORY_LABELS[item.slug]}</span>
          ${item.price ? `<strong>${escapeHtml(item.price)}</strong>` : ''}
        </div>
        <span class="stock-badge stock-badge--${state}"><span aria-hidden="true"></span>${stockLabel}</span>
        <h3>${escapeHtml(item.name)}</h3>
        ${description}
      </div>
    </article>`;
  }).join('');

  return { total: items.length };
}

export function showCatalogNotice(state, message) {
  const notice = document.querySelector('[data-catalog-notice]');
  if (!notice) return;

  notice.dataset.state = state;
  notice.textContent = message;
}
