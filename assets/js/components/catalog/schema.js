import { normalizeText } from './normalize.js';

export const HEADER_ALIASES = Object.freeze({
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

export const CATEGORY_RULES = Object.freeze([
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

export const CATEGORY_LABELS = Object.freeze({
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

export const CATEGORY_IMAGES = Object.freeze({
  'dark-oolong': 'assets/images/tea-category-dark-oolong-real.webp',
  'red-tea': 'assets/images/tea-category-red-tea-real.webp',
  'gaba': 'assets/images/tea-category-gaba-real.webp',
  'white-tea': 'assets/images/tea-category-white-real.webp',
  'light-oolong': 'assets/images/tea-category-light-oolong-real.webp',
  'sheng-puer': 'assets/images/tea-category-sheng-puer-real.webp',
  'shu-puer': 'assets/images/tea-category-shu-puer-real.webp',
  'yellow-tea': 'assets/images/tea-category-yellow-tea-real.webp',
  'hei-cha': 'assets/images/tea-category-hei-cha-real.webp',
  'lemonades': 'assets/images/tea-category-lemonades-real.webp',
  'bubble-tea': 'assets/images/tea-category-bubble-tea-real.webp',
  'author-tea': 'assets/images/tea-category-author-tea-real.webp',
});

export function findColumn(headers, aliases) {
  const normalizedHeaders = headers.map(header => ({
    original: header,
    normalized: normalizeText(header),
  }));

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

export function inferNameColumn(rows, headers) {
  const explicit = findColumn(headers, HEADER_ALIASES.name);
  if (explicit) return explicit;

  return headers.find(header =>
    rows.some(row => typeof row[header] === 'string' && normalizeText(row[header]).length >= 3)
  ) || null;
}

export function categorySlug(category, fallback, name = '', subcategory = '') {
  const sources = [subcategory, category, name].map(normalizeText).filter(Boolean);

  for (const source of sources) {
    const match = CATEGORY_RULES.find(rule => rule.pattern.test(source));
    if (match) return match.slug;
  }

  return fallback || '';
}
