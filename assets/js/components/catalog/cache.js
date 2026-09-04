import { catalogConfig } from '../../config.catalog.js';

export function readCatalogCache() {
  try {
    const raw = localStorage.getItem(catalogConfig.cacheKey);
    if (!raw) return null;

    const cache = JSON.parse(raw);
    if (!cache?.savedAt || !cache?.sheets) return null;

    const ageMs = Math.max(0, Date.now() - cache.savedAt);

    return {
      sheets: cache.sheets,
      savedAt: cache.savedAt,
      ageMs,
      fresh: ageMs <= catalogConfig.cacheTtlMs,
    };
  } catch {
    return null;
  }
}

export function getCachedSheets() {
  const cache = readCatalogCache();
  return cache?.fresh ? cache.sheets : null;
}

export function setCachedSheets(sheets) {
  try {
    localStorage.setItem(
      catalogConfig.cacheKey,
      JSON.stringify({ savedAt: Date.now(), sheets }),
    );
  } catch {
    // Catalog remains functional when localStorage is unavailable.
  }
}
