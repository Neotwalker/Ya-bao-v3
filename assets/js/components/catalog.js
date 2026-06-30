import { catalogConfig } from '../config.catalog.js';

const HEADER_ALIASES = Object.freeze({
  name: [
    'название', 'наименование', 'товар', 'позиция', 'продукт', 'название товара',
    'название позиции', 'номенклатура', 'чай', 'напиток',
  ],
  category: [
    'категория', 'раздел', 'группа', 'тип', 'вид', 'категория товара',
    'категория позиции', 'подкатегория',
  ],
  stock: [
    'наличие', 'остаток', 'количество', 'кол-во', 'остаток на складе', 'склад',
    'в наличии', 'доступно', 'доступный остаток', 'фактический остаток',
  ],
});

const CATEGORY_RULES = Object.freeze([
  { slug: 'tea', pattern: /(чай|пуэр|улун|габа|да хун|те гуань|матча|сенча|я бао|бай му|лунцзин)/i },
  { slug: 'drinks', pattern: /(лимонад|напит|бабл|bubble|смузи|коктейл|морс|холодн)/i },
  { slug: 'desserts', pattern: /(десерт|блин|слад|пирог|торт|чизкейк|печенье|вафл)/i },
]);

const AVAILABLE_VALUES = new Set([
  'да', 'есть', 'в наличии', 'доступно', 'доступен', 'активно', 'активен', 'true', 'yes', 'y', '+',
]);
const OUT_VALUES = new Set([
  'нет', 'нет в наличии', 'закончился', 'закончилась', 'закончилось', 'отсутствует', 'false', 'no', 'n', '-',
]);

function normalizeText(value = '') {
  return String(value)
    .toLocaleLowerCase('ru-RU')
    .replace(/ё/g, 'е')
    .replace(/[«»"'`]/g, '')
    .replace(/[^a-zа-я0-9]+/gi, ' ')
    .trim()
    .replace(/\s+/g, ' ');
}

function readCell(cell) {
  if (!cell) return '';
  if (cell.v !== null && cell.v !== undefined) return cell.v;
  return cell.f ?? '';
}

function parseGvizTable(response) {
  if (!response || response.status === 'error' || !response.table) {
    throw new Error(response?.errors?.[0]?.detailed_message || 'Google Sheets не вернул таблицу.');
  }

  const cols = response.table.cols || [];
  const rows = (response.table.rows || []).map(row => (row.c || []).map(readCell));
  let headers = cols.map((col, index) => String(col?.label || col?.id || `column_${index + 1}`).trim());

  const labelsAreTechnical = headers.every(header => /^([A-Z]|column_\d+)$/i.test(header));
  if (labelsAreTechnical && rows.length) {
    const candidate = rows[0].map(value => String(value ?? '').trim());
    const textCells = candidate.filter(Boolean).length;
    if (textCells >= 2) {
      headers = candidate;
      rows.shift();
    }
  }

  return rows
    .filter(row => row.some(value => String(value ?? '').trim() !== ''))
    .map(row => Object.fromEntries(headers.map((header, index) => [header || `column_${index + 1}`, row[index] ?? ''])));
}

function loadSheetWithScript(sheetName) {
  return new Promise((resolve, reject) => {
    const callbackOwner = window.google = window.google || {};
    callbackOwner.visualization = callbackOwner.visualization || {};
    callbackOwner.visualization.Query = callbackOwner.visualization.Query || {};

    const previousHandler = callbackOwner.visualization.Query.setResponse;
    const script = document.createElement('script');
    const timeout = window.setTimeout(() => finish(new Error(`Лист «${sheetName}» не ответил вовремя.`)), catalogConfig.requestTimeoutMs);
    let completed = false;

    const finish = (error, value) => {
      if (completed) return;
      completed = true;
      window.clearTimeout(timeout);
      script.remove();
      callbackOwner.visualization.Query.setResponse = previousHandler;
      error ? reject(error) : resolve(value);
    };

    callbackOwner.visualization.Query.setResponse = response => {
      try {
        finish(null, parseGvizTable(response));
      } catch (error) {
        finish(error);
      }
    };

    script.onerror = () => finish(new Error(`Не удалось загрузить лист «${sheetName}».`));
    const params = new URLSearchParams({
      tqx: 'out:json',
      sheet: sheetName,
      headers: '1',
    });
    script.src = `https://docs.google.com/spreadsheets/d/${catalogConfig.spreadsheetId}/gviz/tq?${params.toString()}`;
    script.async = true;
    document.head.append(script);
  });
}

async function loadSheets() {
  const result = {};
  for (const sheetName of catalogConfig.sheets) {
    try {
      result[sheetName] = await loadSheetWithScript(sheetName);
    } catch (error) {
      console.warn(`[catalog] ${sheetName}:`, error.message);
      result[sheetName] = [];
    }
  }
  if (!Object.values(result).some(rows => rows.length)) {
    throw new Error('Не удалось получить данные ни из одного листа.');
  }
  return result;
}

function getCachedSheets() {
  try {
    const raw = localStorage.getItem(catalogConfig.cacheKey);
    if (!raw) return null;
    const cache = JSON.parse(raw);
    if (!cache?.savedAt || Date.now() - cache.savedAt > catalogConfig.cacheTtlMs) return null;
    return cache.sheets || null;
  } catch {
    return null;
  }
}

function setCachedSheets(sheets) {
  try {
    localStorage.setItem(catalogConfig.cacheKey, JSON.stringify({ savedAt: Date.now(), sheets }));
  } catch {
    // Private mode or storage restriction: catalog still works without cache.
  }
}

function findColumn(headers, aliases) {
  const normalizedHeaders = headers.map(header => ({ original: header, normalized: normalizeText(header) }));
  for (const alias of aliases) {
    const normalizedAlias = normalizeText(alias);
    const exact = normalizedHeaders.find(header => header.normalized === normalizedAlias);
    if (exact) return exact.original;
  }
  for (const alias of aliases) {
    const normalizedAlias = normalizeText(alias);
    const partial = normalizedHeaders.find(header => header.normalized.includes(normalizedAlias));
    if (partial) return partial.original;
  }
  return null;
}

function inferNameColumn(rows, headers) {
  const explicit = findColumn(headers, HEADER_ALIASES.name);
  if (explicit) return explicit;
  return headers.find(header => rows.some(row => typeof row[header] === 'string' && normalizeText(row[header]).length >= 3)) || null;
}

function parseAvailability(rawValue) {
  if (typeof rawValue === 'boolean') return { known: true, available: rawValue, quantity: rawValue ? 1 : 0 };
  if (typeof rawValue === 'number' && Number.isFinite(rawValue)) {
    return { known: true, available: rawValue > 0, quantity: rawValue };
  }

  const normalized = normalizeText(rawValue);
  if (!normalized) return { known: false, available: false, quantity: null };
  if (AVAILABLE_VALUES.has(normalized)) return { known: true, available: true, quantity: null };
  if (OUT_VALUES.has(normalized)) return { known: true, available: false, quantity: 0 };

  const numeric = Number(String(rawValue).replace(',', '.').match(/-?\d+(?:[.,]\d+)?/)?.[0]?.replace(',', '.'));
  if (Number.isFinite(numeric)) return { known: true, available: numeric > 0, quantity: numeric };

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
    const availability = stockColumn ? parseAvailability(row[stockColumn]) : { known: false, available: false, quantity: null };
    return {
      name,
      key: normalizeText(name),
      category,
      availability,
      source: sourceName,
    };
  }).filter(product => product.key);
}

function mergeProducts(sheets) {
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

function similarity(left, right) {
  const a = new Set(normalizeText(left).split(' ').filter(Boolean));
  const b = new Set(normalizeText(right).split(' ').filter(Boolean));
  if (!a.size || !b.size) return 0;
  let intersection = 0;
  a.forEach(token => { if (b.has(token)) intersection += 1; });
  return intersection / Math.max(a.size, b.size);
}

function findStockProduct(card, products) {
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
      const contained = products.find(product => product.key.includes(candidateKey) || candidateKey.includes(product.key));
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

function categorySlug(category, fallback) {
  const source = normalizeText(category);
  const match = CATEGORY_RULES.find(rule => rule.pattern.test(source));
  return match?.slug || fallback || 'tea';
}

function setCardState(card, state, label) {
  card.dataset.stockState = state;
  const badge = card.querySelector('[data-stock-badge]');
  if (badge) {
    badge.className = `stock-badge stock-badge--${state}`;
    badge.innerHTML = `<span aria-hidden="true"></span>${label}`;
  }
}

function applyProductsToCards(products) {
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
    card.dataset.category = categorySlug(product.category, card.dataset.category);

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

function showCatalogNotice(state, message) {
  const notice = document.querySelector('[data-catalog-notice]');
  if (!notice) return;
  notice.dataset.state = state;
  notice.textContent = message;
}

export async function initCatalog() {
  const root = document.querySelector('[data-live-catalog]');
  if (!root) return;

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
    if (!products.length) throw new Error('В таблице не найдены позиции каталога.');
    const stats = applyProductsToCards(products);
    showCatalogNotice(
      'success',
      `${fromCache ? 'Наличие загружено из кеша' : 'Наличие обновлено'}: доступно ${stats.availableCount} из ${stats.total}.`,
    );
    root.dispatchEvent(new CustomEvent('catalog:updated', { bubbles: true }));
  } catch (error) {
    console.error('[catalog]', error);
    document.querySelectorAll('[data-catalog-card]').forEach(card => {
      setCardState(card, 'unknown', 'Наличие уточняется');
      card.hidden = false;
    });
    showCatalogNotice('error', 'Не удалось проверить остатки. Карточки показаны, наличие можно уточнить у администратора.');
  } finally {
    root.setAttribute('aria-busy', 'false');
  }
}
