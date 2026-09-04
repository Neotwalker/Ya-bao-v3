import { catalogConfig } from '../../config.catalog.js';
import { normalizeText, similarity } from './normalize.js';
import {
  HEADER_ALIASES,
  findColumn,
  inferNameColumn,
  categorySlug,
} from './schema.js';

const AVAILABLE_VALUES = new Set([
  'да', 'есть', 'в наличии', 'доступно', 'доступен', 'активно', 'активен',
  'true', 'yes', 'y', '+',
]);

const OUT_VALUES = new Set([
  'нет', 'нет в наличии', 'закончился', 'закончилась', 'закончилось',
  'отсутствует', 'false', 'no', 'n', '-',
]);

export function parseAvailability(rawValue) {
  if (typeof rawValue === 'boolean') {
    return { known: true, available: rawValue, quantity: rawValue ? 1 : 0 };
  }

  if (typeof rawValue === 'number' && Number.isFinite(rawValue)) {
    return { known: true, available: rawValue > 0, quantity: rawValue };
  }

  const normalized = normalizeText(rawValue);
  if (!normalized) return { known: false, available: false, quantity: null };
  if (AVAILABLE_VALUES.has(normalized)) return { known: true, available: true, quantity: null };
  if (OUT_VALUES.has(normalized)) return { known: true, available: false, quantity: 0 };

  const numericMatch = String(rawValue).replace(',', '.').match(/-?\d+(?:[.,]\d+)?/);
  const numeric = Number(numericMatch?.[0]?.replace(',', '.'));

  if (Number.isFinite(numeric)) {
    return { known: true, available: numeric > 0, quantity: numeric };
  }

  if (/\b(есть|доступ|налич)\b/i.test(normalized) && !/\b(нет|не |0)\b/i.test(normalized)) {
    return { known: true, available: true, quantity: null };
  }

  if (/\b(нет|законч|отсутств)\b/i.test(normalized)) {
    return { known: true, available: false, quantity: 0 };
  }

  return { known: false, available: false, quantity: null };
}

function rowsToProducts(rows, sourceName) {
  if (!rows.length) return [];

  const headers = [...new Set(rows.flatMap(row => Object.keys(row)))];
  const nameColumn = inferNameColumn(rows, headers);
  const categoryColumn = findColumn(headers, HEADER_ALIASES.category);
  const stockColumn = findColumn(headers, HEADER_ALIASES.stock);

  if (!nameColumn) return [];

  return rows.map(row => {
    const name = String(row[nameColumn] ?? '').trim();
    const category = categoryColumn ? String(row[categoryColumn] ?? '').trim() : '';
    const availability = stockColumn
      ? parseAvailability(row[stockColumn])
      : { known: false, available: false, quantity: null };

    return {
      name,
      key: normalizeText(name),
      category,
      availability,
      source: sourceName,
    };
  }).filter(product => product.key);
}

export function mergeProducts(sheets) {
  const map = new Map();

  for (const sheetName of catalogConfig.sheets) {
    const products = rowsToProducts(sheets[sheetName] || [], sheetName);

    for (const product of products) {
      const current = map.get(product.key) || {
        name: product.name,
        key: product.key,
        category: '',
        availability: { known: false, available: false, quantity: null },
        sources: [],
      };

      if (product.name) current.name = product.name;
      if (product.category) current.category = product.category;
      if (product.availability.known) current.availability = product.availability;
      current.sources.push(product.source);
      map.set(product.key, current);
    }
  }

  return [...map.values()];
}

export function findStockProduct(card, products) {
  const title = card.querySelector('h3')?.textContent || '';
  const aliases = (card.dataset.stockAliases || '')
    .split('|')
    .map(value => value.trim())
    .filter(Boolean);

  const candidates = [card.dataset.stockName, title, ...aliases].filter(Boolean);

  for (const candidate of candidates) {
    const candidateKey = normalizeText(candidate);
    const exact = products.find(product => product.key === candidateKey);
    if (exact) return exact;

    if (candidateKey.length >= 5) {
      const contained = products.find(product =>
        product.key.includes(candidateKey) || candidateKey.includes(product.key)
      );
      if (contained) return contained;
    }
  }

  let best = null;
  let bestScore = 0;

  for (const candidate of candidates) {
    for (const product of products) {
      const score = similarity(candidate, product.name);
      if (score > bestScore) {
        best = product;
        bestScore = score;
      }
    }
  }

  return bestScore >= 0.72 ? best : null;
}

export function applyProductsToCards(products) {
  const cards = [...document.querySelectorAll('[data-catalog-card]')];
  let availableCount = 0;
  let outCount = 0;

  cards.forEach(card => {
    const product = findStockProduct(card, products);
    const categoryLabel = card.querySelector('[data-catalog-category]');
    const title = card.querySelector('h3');

    if (!product || !product.sources.includes('Склад')) {
      outCount += 1;
      setCardState(card, 'out', 'Закончился');
      card.dataset.stockVisibility = catalogConfig.outOfStockMode;
      card.hidden = catalogConfig.outOfStockMode === 'hide';
      return;
    }

    if (product.name && title) title.textContent = product.name;
    if (product.category && categoryLabel) categoryLabel.textContent = product.category;

    card.dataset.category = categorySlug(
      product.category,
      card.dataset.category,
      product.name,
      product.subcategory || '',
    );

    if (!product.availability.known) {
      setCardState(card, 'unknown', 'Наличие уточняется');
      card.hidden = false;
      return;
    }

    if (product.availability.available) {
      availableCount += 1;
      setCardState(card, 'available', 'В наличии');
      card.hidden = false;
    } else {
      outCount += 1;
      setCardState(card, 'out', 'Закончился');
      card.dataset.stockVisibility = catalogConfig.outOfStockMode;
      card.hidden = catalogConfig.outOfStockMode === 'hide';
    }
  });

  return { total: cards.length, availableCount, outCount };
}

export function setCardState(card, state, label) {
  card.dataset.stockState = state;
  const badge = card.querySelector('[data-stock-badge]');

  if (badge) {
    badge.className = `stock-badge stock-badge--${state}`;
    badge.innerHTML = `<span aria-hidden="true"></span>${label}`;
  }
}
