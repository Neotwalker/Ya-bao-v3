export function normalizeText(value = '') {
  return String(value)
    .toLocaleLowerCase('ru-RU')
    .replace(/ё/g, 'е')
    .replace(/[«»"'`]/g, '')
    .replace(/[^a-zа-я0-9]+/gi, ' ')
    .trim()
    .replace(/\s+/g, ' ');
}

export function escapeHtml(value = '') {
  return String(value).replace(
    /[&<>'"]/g,
    char => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#39;', '"': '&quot;' })[char],
  );
}

export function similarity(left, right) {
  const a = new Set(normalizeText(left).split(' ').filter(Boolean));
  const b = new Set(normalizeText(right).split(' ').filter(Boolean));
  if (!a.size || !b.size) return 0;

  let intersection = 0;
  a.forEach(token => {
    if (b.has(token)) intersection += 1;
  });

  return intersection / Math.max(a.size, b.size);
}
