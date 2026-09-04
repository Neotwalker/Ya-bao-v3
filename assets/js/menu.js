import { initFilters } from './components/filters.js';
import { initCatalog } from './components/catalog.js';
import { runInitializers } from './utils/init.js';

runInitializers(
  initFilters,
  initCatalog,
);
