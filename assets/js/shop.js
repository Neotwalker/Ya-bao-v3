const catalog = document.querySelector('[data-shop-catalog]');

if (catalog) {
  const DATA_URL = new URL('../../data/products.json', import.meta.url);
  const PUBLIC_STATUSES = new Set(['active', 'out_of_stock']);
  const REQUEST_TIMEOUT_MS = 8000;
  const TYPES = new Set(['all', 'tea', 'ware', 'accessory']);
  const SORTS = new Set(['default', 'name-asc', 'price-asc', 'price-desc']);
  const TYPE_LABELS = {
    tea: 'Чай',
    ware: 'Посуда',
    accessory: 'Аксессуары',
  };
  const CATEGORY_LABELS = {
    'sheng-puer': 'Шэн пуэр',
    'shu-puer': 'Шу пуэр',
    'white-tea': 'Белый чай',
    'pressed-tea': 'Прессованный чай',
    'brewing-ware': 'Посуда для заваривания',
    'serving-ware': 'Посуда для подачи',
    'tea-tools': 'Чайные аксессуары',
    packaging: 'Упаковка',
  };

  const grid = catalog.querySelector('[data-shop-grid]');
  const loading = catalog.querySelector('[data-shop-loading]');
  const empty = catalog.querySelector('[data-shop-empty]');
  const error = catalog.querySelector('[data-shop-error]');
  const retry = catalog.querySelector('[data-shop-retry]');
  const resultCount = catalog.querySelector('[data-shop-result-count]');
  const controls = catalog.querySelector('[data-shop-controls]');
  const toolbar = catalog.querySelector('[data-shop-toolbar]');
  const filterBar = catalog.querySelector('[data-shop-filters]');
  const filters = [...catalog.querySelectorAll('[data-shop-filter]')];
  const searchInput = catalog.querySelector('[data-shop-search]');
  const categorySelect = catalog.querySelector('[data-shop-category]');
  const sortSelect = catalog.querySelector('[data-shop-sort]');
  const resetButtons = [...catalog.querySelectorAll('[data-shop-reset]')];

  const collator = new Intl.Collator('ru-RU', { sensitivity: 'base', numeric: true });
  let products = [];
  let sourceOrder = new Map();

  const readURLState = () => {
    const params = new URLSearchParams(window.location.search);
    const type = params.get('type') || 'all';
    const sort = params.get('sort') || 'default';
    return {
      type: TYPES.has(type) ? type : 'all',
      category: (params.get('category') || '').trim(),
      q: (params.get('q') || '').slice(0, 100),
      sort: SORTS.has(sort) ? sort : 'default',
    };
  };

  let state = readURLState();

  const money = value => new Intl.NumberFormat('ru-RU').format(value) + ' ₽';
  const escapeHTML = value => String(value).replace(/[&<>'"]/g, char => ({
    '&': '&amp;',
    '<': '&lt;',
    '>': '&gt;',
    "'": '&#39;',
    '"': '&quot;',
  })[char]);

  const symbolFor = type => {
    if (type === 'ware') {
      return '<svg viewBox="0 0 120 120" aria-hidden="true"><path d="M27 47h56v20c0 17-12 29-28 29S27 84 27 67V47Z" fill="none" stroke="currentColor" stroke-width="5" stroke-linejoin="round"/><path d="M83 54h8c10 0 15 7 15 14s-5 14-16 14h-8" fill="none" stroke="currentColor" stroke-width="5" stroke-linecap="round"/><path d="M42 28c0 7 7 8 7 15M60 24c0 8 7 9 7 17" fill="none" stroke="currentColor" stroke-width="4" stroke-linecap="round"/></svg>';
    }
    if (type === 'accessory') {
      return '<svg viewBox="0 0 120 120" aria-hidden="true"><path d="M29 88 78 39M38 97l49-49" fill="none" stroke="currentColor" stroke-width="6" stroke-linecap="round"/><path d="m77 27 16 16-13 13-16-16 13-13Z" fill="none" stroke="currentColor" stroke-width="5" stroke-linejoin="round"/><path d="M24 91c7-7 15-8 23-1-8 9-16 9-23 1Z" fill="none" stroke="currentColor" stroke-width="5" stroke-linejoin="round"/></svg>';
    }
    return '<svg viewBox="0 0 120 120" aria-hidden="true"><path d="M91 27C61 29 34 41 28 72c17 6 32 1 43-10 11-10 16-23 20-35Z" fill="none" stroke="currentColor" stroke-width="5" stroke-linejoin="round"/><path d="M30 90c8-21 24-37 47-49M48 72c3-11 3-20 0-28M59 61c10 0 19 2 26 7" fill="none" stroke="currentColor" stroke-width="4" stroke-linecap="round"/></svg>';
  };

  const detailFor = product => {
    if (product.sale_mode === 'weight') {
      const weights = Array.isArray(product.variants)
        ? product.variants.map(variant => variant.weight_g).filter(Boolean)
        : [];
      if (weights.length) return `Вес: ${weights.join(' / ')} г`;
      return `Продажа от ${product.min_weight_g || 50} г`;
    }
    return 'Продажа поштучно';
  };

  const priceFor = product => {
    if (product.sale_mode === 'weight') {
      return `от ${money(product.price)}`;
    }
    return money(product.price);
  };

  const createCard = product => {
    const article = document.createElement('article');
    const out = product.stock_status === 'out_of_stock' || product.status === 'out_of_stock';
    article.className = `shop-card${out ? ' shop-card--out' : ''}`;
    article.dataset.type = product.type;
    article.dataset.shopItem = '';
    article.dataset.productId = product.external_id;

    const typeLabel = TYPE_LABELS[product.type] || product.type;
    const categoryLabel = CATEGORY_LABELS[product.category] || typeLabel;
    const stockText = out ? 'Нет в наличии' : 'В наличии';

    article.innerHTML = `
      <div class="shop-card__media">
        <span class="shop-card__badge${out ? ' shop-card__badge--out' : ''}">${out ? 'Нет в наличии' : 'Demo'}</span>
        <span class="shop-card__symbol">${symbolFor(product.type)}</span>
      </div>
      <div class="shop-card__body">
        <div class="shop-card__meta">
          <span>${escapeHTML(categoryLabel)}</span>
          <strong class="shop-card__price">${escapeHTML(priceFor(product))}</strong>
        </div>
        <h3>${escapeHTML(product.name)}</h3>
        <p class="shop-card__detail">${escapeHTML(detailFor(product))}</p>
        <div class="shop-card__stock">${escapeHTML(stockText)}</div>
      </div>`;

    return article;
  };

  const pluralizeProducts = count => {
    const mod10 = count % 10;
    const mod100 = count % 100;
    if (mod10 === 1 && mod100 !== 11) return `${count} товар`;
    if (mod10 >= 2 && mod10 <= 4 && (mod100 < 12 || mod100 > 14)) return `${count} товара`;
    return `${count} товаров`;
  };

  const categoriesForType = type => {
    const values = new Set(
      products
        .filter(product => type === 'all' || product.type === type)
        .map(product => product.category)
        .filter(Boolean),
    );
    return [...values].sort((a, b) => collator.compare(CATEGORY_LABELS[a] || a, CATEGORY_LABELS[b] || b));
  };

  const updateCategoryOptions = () => {
    const categories = categoriesForType(state.type);
    if (state.category && !categories.includes(state.category)) state.category = '';

    const fragment = document.createDocumentFragment();
    const allOption = document.createElement('option');
    allOption.value = '';
    allOption.textContent = 'Все категории';
    fragment.append(allOption);

    categories.forEach(category => {
      const option = document.createElement('option');
      option.value = category;
      option.textContent = CATEGORY_LABELS[category] || category;
      fragment.append(option);
    });

    categorySelect.replaceChildren(fragment);
    categorySelect.value = state.category;
  };

  const hasActiveState = () => (
    state.type !== 'all'
    || Boolean(state.category)
    || Boolean(state.q.trim())
    || state.sort !== 'default'
  );

  const writeURLState = () => {
    const url = new URL(window.location.href);
    ['type', 'category', 'q', 'sort'].forEach(key => url.searchParams.delete(key));

    if (state.type !== 'all') url.searchParams.set('type', state.type);
    if (state.category) url.searchParams.set('category', state.category);
    const query = state.q.trim();
    if (query) url.searchParams.set('q', query);
    if (state.sort !== 'default') url.searchParams.set('sort', state.sort);

    window.history.replaceState(null, '', `${url.pathname}${url.search}${url.hash}`);
  };

  const syncControls = ({ syncSearch = true } = {}) => {
    filters.forEach(button => {
      button.setAttribute('aria-pressed', String(button.dataset.shopFilter === state.type));
    });
    updateCategoryOptions();
    sortSelect.value = state.sort;
    if (syncSearch && searchInput.value !== state.q) searchInput.value = state.q;
  };

  const getVisibleProducts = () => {
    const query = state.q.trim().toLocaleLowerCase('ru-RU');
    const visible = products.filter(product => {
      if (state.type !== 'all' && product.type !== state.type) return false;
      if (state.category && product.category !== state.category) return false;
      if (query && !product.name.toLocaleLowerCase('ru-RU').includes(query)) return false;
      return true;
    });

    if (state.sort === 'name-asc') {
      visible.sort((a, b) => collator.compare(a.name, b.name));
    } else if (state.sort === 'price-asc') {
      visible.sort((a, b) => (a.price - b.price) || collator.compare(a.name, b.name));
    } else if (state.sort === 'price-desc') {
      visible.sort((a, b) => (b.price - a.price) || collator.compare(a.name, b.name));
    } else {
      visible.sort((a, b) => sourceOrder.get(a.external_id) - sourceOrder.get(b.external_id));
    }

    return visible;
  };

  const setState = current => {
    loading.hidden = current !== 'loading';
    error.hidden = current !== 'error';
    const isReady = current === 'ready';
    controls.hidden = !isReady;
    toolbar.hidden = !isReady;
    if (!isReady) {
      grid.hidden = true;
      empty.hidden = true;
    }
  };

  const applyState = ({ updateURL = true, syncSearch = false } = {}) => {
    syncControls({ syncSearch });
    const visible = getVisibleProducts();
    grid.replaceChildren(...visible.map(createCard));
    const isEmpty = visible.length === 0;
    grid.hidden = isEmpty;
    empty.hidden = !isEmpty;
    resultCount.textContent = pluralizeProducts(visible.length);

    const active = hasActiveState();
    resetButtons.forEach(button => {
      if (button.matches('[data-shop-reset="toolbar"]')) button.hidden = !active;
    });

    if (updateURL) writeURLState();
  };

  const resetState = () => {
    state = { type: 'all', category: '', q: '', sort: 'default' };
    applyState({ syncSearch: true });
    searchInput.focus();
  };

  const render = data => {
    if (!data || !Array.isArray(data.products)) throw new Error('Некорректный формат каталога');

    products = data.products.filter(product => PUBLIC_STATUSES.has(product.status));
    sourceOrder = new Map(products.map((product, index) => [product.external_id, index]));
    setState('ready');
    applyState({ syncSearch: true });
  };

  const load = async () => {
    setState('loading');
    const controller = new AbortController();
    const timeoutId = window.setTimeout(() => controller.abort(), REQUEST_TIMEOUT_MS);

    try {
      const response = await fetch(DATA_URL, {
        cache: 'default',
        signal: controller.signal,
      });
      if (!response.ok) throw new Error(`HTTP ${response.status}`);
      render(await response.json());
    } catch (loadError) {
      if (loadError?.name === 'AbortError') {
        console.warn(`Shop catalog load timed out after ${REQUEST_TIMEOUT_MS} ms`);
      } else {
        console.error('Shop catalog load failed:', loadError);
      }
      setState('error');
      resultCount.textContent = '';
    } finally {
      window.clearTimeout(timeoutId);
    }
  };

  filters.forEach(button => {
    button.addEventListener('click', () => {
      state.type = button.dataset.shopFilter;
      applyState();
    });
  });

  searchInput.addEventListener('input', () => {
    state.q = searchInput.value.slice(0, 100);
    applyState();
  });

  categorySelect.addEventListener('change', () => {
    state.category = categorySelect.value;
    applyState();
  });

  sortSelect.addEventListener('change', () => {
    state.sort = SORTS.has(sortSelect.value) ? sortSelect.value : 'default';
    applyState();
  });

  resetButtons.forEach(button => button.addEventListener('click', resetState));

  filterBar.addEventListener('keydown', event => {
    if (!['ArrowLeft', 'ArrowRight', 'Home', 'End'].includes(event.key)) return;
    const current = filters.indexOf(document.activeElement);
    if (current < 0) return;
    event.preventDefault();

    let next = current;
    if (event.key === 'Home') next = 0;
    if (event.key === 'End') next = filters.length - 1;
    if (event.key === 'ArrowRight') next = (current + 1) % filters.length;
    if (event.key === 'ArrowLeft') next = (current - 1 + filters.length) % filters.length;
    filters[next].focus();
  });

  window.addEventListener('popstate', () => {
    state = readURLState();
    if (products.length) applyState({ updateURL: false, syncSearch: true });
  });

  retry.addEventListener('click', load);
  load();
}
