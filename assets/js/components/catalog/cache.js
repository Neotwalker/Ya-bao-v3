import { catalogConfig } from '../../config.catalog.js';

export function getCachedSheets() {
  try {
    const raw = localStorage.getItem(catalogConfig.cacheKey);
    if (!raw) return null;

    const cache = JSON.parse(raw);
    if (!cache?.savedAt || Date.now() - cache.savedAt > catalogConfig.cacheTtlMs) {
      return null;
    }

    return cache.sheets || null;
  } catch {
    return null;
  }
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
