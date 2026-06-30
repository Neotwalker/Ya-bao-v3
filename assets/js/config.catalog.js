export const catalogConfig = Object.freeze({
  spreadsheetId: '1MHy4a56fQAQE3N7im4mjsP8NFC9ldC1RiZsC5hoL4RU',
  sheets: ['Склад', 'Меню'],
  cacheKey: 'yabao-public-catalog-v1',
  cacheTtlMs: 5 * 60 * 1000,
  outOfStockMode: 'mark', // 'mark' — показать «Закончился», 'hide' — скрыть карточку
  requestTimeoutMs: 12000,
});
