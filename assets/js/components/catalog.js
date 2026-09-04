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
  subcategory: ['подкатегория'],
  price: ['цена', 'цена ₽', 'цена (₽)', 'стоимость'],
  description: ['описание'],
  stock: [
    'наличие', 'остаток', 'количество', 'кол-во', 'остаток на складе', 'склад',
    'в наличии', 'доступно', 'доступный остаток', 'фактический остаток',
  ],
});

const CATEGORY_RULES = Object.freeze([
  { slug: 'dark-oolong', pattern: /(темн.*улун|да хун пао|фен хуан|шуй сян)/i },
  { slug: 'red-tea', pattern: /(красн.*чай|дянь?\s*хун|дян\s*хун)/i },
  { slug: 'gaba', pattern: /(^|\s)габа(\s|$)/i },
  { slug: 'white-tea', pattern: /(бел.*чай|бай хао|юэ гуан|я бао)/i },
  { slug: 'light-oolong', pattern: /(светл.*улун|тегуан|те гуань|дун дин|молочн.*улун|ганпаудер|вулканическ.*улун)/i },
  { slug: 'sheng-puer', pattern: /(ш[эе]н.*пуэр|ш[эе]н\s|да сюэ шань|сэн линь|шэй цун)/i },
  { slug: 'shu-puer', pattern: /(шу.*пуэр|смола.*пуэр|долина мастера|красный дракон|хитрый дед)/i },
  { slug: 'yellow-tea', pattern: /(желт.*чай|жёлт.*чай|хо шань хуан)/i },
  { slug: 'hei-cha', pattern: /(хэй\s*ча)/i },
  { slug: 'bubble-tea', pattern: /(бабл|bubble)/i },
  { slug: 'lemonades', pattern: /(лимонад)/i },
  { slug: 'author-tea', pattern: /(авторск.*чай|холодн.*чай)/i },
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

function categorySlug(category, fallback, name = '', subcategory = '') {
  const sources = [subcategory, category, name].map(normalizeText).filter(Boolean);
  for (const source of sources) {
    const match = CATEGORY_RULES.find(rule => rule.pattern.test(source));
    if (match) return match.slug;
  }
  return fallback || '';
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
    card.dataset.category = categorySlug(product.category, card.dataset.category, product.name, product.subcategory || '');

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


const CATEGORY_LABELS = Object.freeze({
  'dark-oolong': 'Темные улуны',
  'red-tea': 'Красный чай',
  'gaba': 'Габа',
  'white-tea': 'Белый чай',
  'light-oolong': 'Светлые улуны',
  'sheng-puer': 'Шэн пуэры',
  'shu-puer': 'Шу Пуэры',
  'yellow-tea': 'Желтый чай',
  'hei-cha': 'Хэй Ча',
  'lemonades': 'Лимонады',
  'bubble-tea': 'Бабл ти',
  'author-tea': 'Авторский чай',
});

const CATEGORY_IMAGES = Object.freeze({
  'dark-oolong': 'assets/images/tea-category-dark-oolong-real.webp',
  'red-tea': 'assets/images/tea-category-red-tea-real.webp',
  'gaba': 'assets/images/tea-category-gaba.webp',
  'white-tea': 'assets/images/tea-category-white.webp',
  'light-oolong': 'assets/images/tea-category-light-oolong-real.webp',
  'sheng-puer': 'assets/images/tea-category-puer.webp',
  'shu-puer': 'assets/images/tea-category-puer.webp',
  'yellow-tea': 'assets/images/menu-yabao.webp',
  'hei-cha': 'assets/images/tea-category-puer.webp',
  'lemonades': 'assets/images/menu-lemonade.webp',
  'bubble-tea': 'assets/images/menu-bubble.webp',
  'author-tea': 'assets/images/menu-yabao.webp',
});

function escapeHtml(value = '') {
  return String(value).replace(/[&<>'"]/g, char => ({ '&':'&amp;', '<':'&lt;', '>':'&gt;', "'":'&#39;', '"':'&quot;' })[char]);
}

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
    const price = Number.isFinite(numeric) && numeric > 0 ? `${numeric.toLocaleString('ru-RU')} ₽` : String(priceRaw ?? '').trim();
    return { name, category, subcategory, description, price, slug, key: normalizeText(name) };
  }).filter(Boolean);
}

function renderMenuItems(rows, stockProducts) {
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
      if (stock.availability.available) { state = 'available'; stockLabel = 'В наличии'; }
      else { state = 'out'; stockLabel = 'Закончился'; hidden = catalogConfig.outOfStockMode === 'hide'; }
    }
    const description = item.description ? `<p>${escapeHtml(item.description)}</p>` : '';
    return `<article class="card menu-card is-visible" data-category="${item.slug}" data-stock-state="${state}" data-stock-visibility="${catalogConfig.outOfStockMode}"${hidden ? ' hidden' : ''}>
      <div class="card__image"><img alt="${escapeHtml(item.name)}" loading="lazy" src="${CATEGORY_IMAGES[item.slug]}"/></div>
      <div class="card__body"><div class="card__meta"><span>${CATEGORY_LABELS[item.slug]}</span>${item.price ? `<strong>${escapeHtml(item.price)}</strong>` : ''}</div>
      <span class="stock-badge stock-badge--${state}"><span aria-hidden="true"></span>${stockLabel}</span><h3>${escapeHtml(item.name)}</h3>${description}</div>
    </article>`;
  }).join('');
  return { total: items.length };
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
    showCatalogNotice('error', 'Не удалось проверить остатки. Карточки показаны, наличие можно уточнить у администратора.');
  } finally {
    root.setAttribute('aria-busy', 'false');
  }
}
