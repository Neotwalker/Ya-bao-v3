const catalog = document.querySelector('[data-shop-catalog]');

if (catalog) {
  const DATA_URL = new URL('../../data/products.json', import.meta.url);
  const PUBLIC_STATUSES = new Set(['active', 'out_of_stock']);
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
  const filterBar = catalog.querySelector('[data-shop-filters]');
  const filters = [...catalog.querySelectorAll('[data-shop-filter]')];

  let products = [];
  let activeFilter = 'all';

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

  const setState = state => {
    loading.hidden = state !== 'loading';
    error.hidden = state !== 'error';
    const isReady = state === 'ready';
    grid.hidden = !isReady;
    filterBar.hidden = !isReady;
    if (!isReady) empty.hidden = true;
  };

  const applyFilter = value => {
    activeFilter = value;
    filters.forEach(button => {
      button.setAttribute('aria-pressed', String(button.dataset.shopFilter === value));
    });

    let visible = 0;
    [...grid.querySelectorAll('[data-shop-item]')].forEach((card, index) => {
      const product = products[index];
      const show = value === 'all' || product.type === value;
      card.hidden = !show;
      if (show) visible += 1;
    });

    empty.hidden = visible !== 0;
    resultCount.textContent = visible === 1 ? '1 товар' : `${visible} товаров`;
  };

  const render = data => {
    if (!data || !Array.isArray(data.products)) throw new Error('Некорректный формат каталога');

    products = data.products.filter(product => PUBLIC_STATUSES.has(product.status));
    grid.replaceChildren(...products.map(createCard));
    setState('ready');
    applyFilter(activeFilter);
  };

  const load = async () => {
    setState('loading');
    try {
      const response = await fetch(DATA_URL, { cache: 'no-store' });
      if (!response.ok) throw new Error(`HTTP ${response.status}`);
      render(await response.json());
    } catch (loadError) {
      console.error('Shop catalog load failed:', loadError);
      setState('error');
      resultCount.textContent = '';
    }
  };

  filters.forEach(button => {
    button.addEventListener('click', () => applyFilter(button.dataset.shopFilter));
  });

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

  retry.addEventListener('click', load);
  load();
}
