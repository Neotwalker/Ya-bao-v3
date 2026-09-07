import { addCartItem } from './store.js';

const initProductGallery = gallery => {
  if (gallery.dataset.galleryReady === 'true') return;
  const slides = [...gallery.querySelectorAll('[data-product-slide]')];
  const thumbs = [...gallery.querySelectorAll('[data-product-thumb]')];
  const stage = gallery.querySelector('[data-product-stage]');
  if (!slides.length || !thumbs.length) return;

  gallery.dataset.galleryReady = 'true';
  let index = 0;
  let touchStartX = 0;
  let touchStartY = 0;

  const show = next => {
    index = Math.max(0, Math.min(slides.length - 1, next));
    slides.forEach((slide, slideIndex) => {
      const active = slideIndex === index;
      slide.hidden = !active;
      slide.classList.toggle('is-active', active);
    });
    thumbs.forEach((thumb, thumbIndex) => {
      const active = thumbIndex === index;
      thumb.classList.toggle('is-active', active);
      thumb.setAttribute('aria-current', String(active));
    });
  };

  thumbs.forEach((thumb, thumbIndex) => {
    thumb.addEventListener('click', () => show(thumbIndex));
    thumb.addEventListener('keydown', event => {
      if (!['ArrowUp', 'ArrowDown', 'ArrowLeft', 'ArrowRight', 'Home', 'End'].includes(event.key)) return;
      event.preventDefault();
      let next = thumbIndex;
      if (event.key === 'Home') next = 0;
      if (event.key === 'End') next = thumbs.length - 1;
      if (event.key === 'ArrowUp' || event.key === 'ArrowLeft') next = (thumbIndex - 1 + thumbs.length) % thumbs.length;
      if (event.key === 'ArrowDown' || event.key === 'ArrowRight') next = (thumbIndex + 1) % thumbs.length;
      show(next);
      thumbs[next].focus();
    });
  });

  stage?.addEventListener('touchstart', event => {
    const touch = event.touches[0];
    touchStartX = touch.clientX;
    touchStartY = touch.clientY;
  }, { passive: true });

  stage?.addEventListener('touchend', event => {
    const touch = event.changedTouches[0];
    const dx = touch.clientX - touchStartX;
    const dy = touch.clientY - touchStartY;
    if (Math.abs(dx) > 42 && Math.abs(dx) > Math.abs(dy) * 1.2) {
      show(index + (dx < 0 ? 1 : -1));
    }
  }, { passive: true });

  show(0);
};

const initProductGalleries = (root = document) => {
  root.querySelectorAll('[data-product-gallery]').forEach(initProductGallery);
};

const initCardGallery = gallery => {
  if (gallery.dataset.galleryReady === 'true') return;
  const slides = [...gallery.querySelectorAll('[data-card-slide]')];
  const dots = [...gallery.querySelectorAll('[data-card-dot]')];
  if (slides.length < 2) return;

  gallery.dataset.galleryReady = 'true';
  let index = 0;
  let touchStartX = 0;
  let touchStartY = 0;
  let suppressClick = false;

  const show = next => {
    index = Math.max(0, Math.min(slides.length - 1, next));
    slides.forEach((slide, slideIndex) => {
      slide.hidden = slideIndex !== index;
    });
    dots.forEach((dot, dotIndex) => {
      dot.classList.toggle('is-active', dotIndex === index);
    });
  };

  const fromPointer = event => {
    if (event.pointerType === 'touch') return;
    const rect = gallery.getBoundingClientRect();
    if (!rect.width) return;
    const ratio = Math.max(0, Math.min(.999, (event.clientX - rect.left) / rect.width));
    show(Math.floor(ratio * slides.length));
  };

  gallery.addEventListener('pointerenter', fromPointer);
  gallery.addEventListener('pointermove', fromPointer);
  gallery.addEventListener('pointerleave', event => {
    if (event.pointerType !== 'touch') show(0);
  });

  gallery.addEventListener('touchstart', event => {
    const touch = event.touches[0];
    touchStartX = touch.clientX;
    touchStartY = touch.clientY;
  }, { passive: true });

  gallery.addEventListener('touchend', event => {
    const touch = event.changedTouches[0];
    const dx = touch.clientX - touchStartX;
    const dy = touch.clientY - touchStartY;
    if (Math.abs(dx) > 42 && Math.abs(dx) > Math.abs(dy) * 1.2) {
      show(index + (dx < 0 ? 1 : -1));
      suppressClick = true;
      window.setTimeout(() => { suppressClick = false; }, 360);
    }
  }, { passive: true });

  gallery.addEventListener('click', event => {
    if (!suppressClick) return;
    event.preventDefault();
    event.stopPropagation();
  });

  show(0);
};

const initCardGalleries = (root = document) => {
  root.querySelectorAll('[data-card-gallery]').forEach(initCardGallery);
};

initProductGalleries();
initCardGalleries();

const initProductVariantPicker = picker => {
  if (picker.dataset.variantReady === 'true') return;
  const options = [...picker.querySelectorAll('[data-product-variant]')].filter(option => !option.disabled);
  const hiddenInput = picker.querySelector('[data-product-variant-id]');
  const summary = picker.closest('.product-summary');
  const priceNode = summary?.querySelector('[data-product-price]');
  if (!options.length || !hiddenInput || !priceNode) return;

  picker.dataset.variantReady = 'true';

  const select = option => {
    if (!option || option.disabled) return;
    const variantId = option.dataset.variantId || '';
    const weight = Number(option.dataset.variantWeight || 0);
    const price = Number(option.dataset.variantPrice || 0);
    if (!variantId || weight < 50 || !Number.isFinite(price)) return;

    options.forEach(item => item.setAttribute('aria-checked', String(item === option)));
    hiddenInput.value = variantId;
    picker.dataset.selectedVariantId = variantId;
    priceNode.textContent = `${new Intl.NumberFormat('ru-RU').format(price)} ₽`;

    picker.dispatchEvent(new CustomEvent('shop:variantchange', {
      bubbles: true,
      detail: { variantId, weight, price },
    }));
  };

  options.forEach((option, optionIndex) => {
    option.addEventListener('click', () => select(option));
    option.addEventListener('keydown', event => {
      if (!['ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown', 'Home', 'End'].includes(event.key)) return;
      event.preventDefault();
      let next = optionIndex;
      if (event.key === 'Home') next = 0;
      if (event.key === 'End') next = options.length - 1;
      if (event.key === 'ArrowLeft' || event.key === 'ArrowUp') next = (optionIndex - 1 + options.length) % options.length;
      if (event.key === 'ArrowRight' || event.key === 'ArrowDown') next = (optionIndex + 1) % options.length;
      select(options[next]);
      options[next].focus();
    });
  });

  select(options.find(option => option.getAttribute('aria-checked') === 'true') || options[0]);
};

const initProductVariantPickers = (root = document) => {
  root.querySelectorAll('[data-product-variant-picker]').forEach(initProductVariantPicker);
};

initProductVariantPickers();

const initProductQuantityPicker = picker => {
  if (picker.dataset.quantityReady === 'true') return;
  const input = picker.querySelector('[data-product-quantity]');
  const minus = picker.querySelector('[data-quantity-minus]');
  const plus = picker.querySelector('[data-quantity-plus]');
  const maxQuantity = Number(picker.dataset.stockQuantity || input?.max || 0);
  if (!input || !minus || !plus || !Number.isInteger(maxQuantity) || maxQuantity < 1) return;

  picker.dataset.quantityReady = 'true';

  const setQuantity = (next, emit = true) => {
    const parsed = Number.parseInt(next, 10);
    const quantity = Math.max(1, Math.min(maxQuantity, Number.isFinite(parsed) ? parsed : 1));
    input.value = String(quantity);
    picker.dataset.selectedQuantity = String(quantity);
    minus.disabled = quantity <= 1;
    plus.disabled = quantity >= maxQuantity;

    if (emit) {
      picker.dispatchEvent(new CustomEvent('shop:quantitychange', {
        bubbles: true,
        detail: {
          productId: picker.dataset.productId || '',
          quantity,
          maxQuantity,
        },
      }));
    }
  };

  minus.addEventListener('click', () => setQuantity(Number(input.value) - 1));
  plus.addEventListener('click', () => setQuantity(Number(input.value) + 1));
  input.addEventListener('change', () => setQuantity(input.value));
  input.addEventListener('blur', () => setQuantity(input.value, false));

  setQuantity(input.value, false);
};

const initProductQuantityPickers = (root = document) => {
  root.querySelectorAll('[data-product-quantity-picker]').forEach(initProductQuantityPicker);
};

initProductQuantityPickers();

const initProductCartAction = summary => {
  if (summary.dataset.cartReady === 'true') return;
  const button = summary.querySelector('[data-add-to-cart]');
  if (!button || button.disabled) return;
  const productId = summary.dataset.productId || '';
  const saleMode = summary.dataset.saleMode || '';
  const stockStatus = summary.dataset.stockStatus || '';
  if (!productId || !['weight', 'unit'].includes(saleMode) || stockStatus !== 'in_stock') return;

  summary.dataset.cartReady = 'true';
  const defaultLabel = button.textContent.trim();
  let feedbackTimer = 0;

  const getPayload = () => {
    const common = {
      productId,
      sku: summary.dataset.productSku || '',
      slug: summary.dataset.productSlug || '',
      name: summary.dataset.productName || '',
      type: summary.dataset.productType || '',
      saleMode,
      image: summary.dataset.productImage ? new URL(summary.dataset.productImage, document.baseURI).href : '',
    };

    if (saleMode === 'weight') {
      const picker = summary.querySelector('[data-product-variant-picker]');
      const variantId = picker?.dataset.selectedVariantId || picker?.querySelector('[data-product-variant-id]')?.value || '';
      const selected = [...(picker?.querySelectorAll('[data-product-variant]') || [])]
        .find(option => option.dataset.variantId === variantId && !option.disabled);
      const weight = Number(selected?.dataset.variantWeight || 0);
      const price = Number(selected?.dataset.variantPrice || NaN);
      if (!variantId || weight < 50 || !Number.isFinite(price)) return null;
      return { ...common, variantId, variantLabel: `${weight} г`, quantity: 1, unitPrice: price };
    }

    const picker = summary.querySelector('[data-product-quantity-picker]');
    const input = picker?.querySelector('[data-product-quantity]');
    const quantity = Number.parseInt(picker?.dataset.selectedQuantity || input?.value || '1', 10);
    const maxQuantity = Number.parseInt(picker?.dataset.stockQuantity || summary.dataset.stockQuantity || '0', 10);
    const unitPrice = Number(summary.dataset.productPrice || NaN);
    if (!Number.isInteger(quantity) || quantity < 1 || !Number.isInteger(maxQuantity) || maxQuantity < 1 || !Number.isFinite(unitPrice)) return null;
    return { ...common, quantity, maxQuantity, unitPrice };
  };

  button.addEventListener('click', () => {
    const payload = getPayload();
    if (!payload) return;
    try {
      addCartItem(payload);
      window.clearTimeout(feedbackTimer);
      button.textContent = 'Добавлено';
      feedbackTimer = window.setTimeout(() => { button.textContent = defaultLabel; }, 1200);
    } catch {
      button.textContent = defaultLabel;
    }
  });
};

const initProductCartActions = (root = document) => {
  root.querySelectorAll('[data-cart-product]').forEach(initProductCartAction);
};

initProductCartActions();

const catalog = document.querySelector('[data-shop-catalog]');

if (catalog) {
  const DATA_URL = new URL('../../data/products.json', import.meta.url);
  const SITE_ROOT_URL = new URL('../../', import.meta.url);
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
  const filtersToggle = catalog.querySelector('[data-shop-filters-toggle]');
  const filterPanel = catalog.querySelector('[data-shop-filter-panel]');
  const filterCount = catalog.querySelector('[data-shop-filter-count]');
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

  const imageFor = (image, product, { secondary = false } = {}) => {
    const src = new URL(image.src, SITE_ROOT_URL).href;
    const supportsSmall = /\/tea-category-[^/]+\.webp$/.test(image.src);
    const smallPath = supportsSmall ? image.src.replace(/\.webp$/, '-640.webp') : '';
    const smallSrc = smallPath ? new URL(smallPath, SITE_ROOT_URL).href : '';
    const alt = secondary ? '' : (image.alt || product.name);
    const srcset = smallSrc
      ? ` srcset="${escapeHTML(smallSrc)} 640w, ${escapeHTML(src)} 1074w" sizes="(max-width: 700px) calc(100vw - 44px), (max-width: 1180px) 33vw, 24vw"`
      : '';
    return `<img class="shop-card__image" src="${escapeHTML(src)}"${srcset} alt="${escapeHTML(alt)}" width="1074" height="669" loading="lazy" decoding="async"/>`;
  };

  const mediaFor = product => {
    const images = Array.isArray(product.images)
      ? product.images.filter(image => image?.src).sort((a, b) => (a.sort_order || 0) - (b.sort_order || 0)).slice(0, 5)
      : [];
    if (!images.length) {
      return { html: `<span class="shop-card__symbol">${symbolFor(product.type)}</span>`, gallery: false };
    }
    if (images.length === 1) {
      return { html: imageFor(images[0], product), gallery: false };
    }

    const slides = images.map((image, index) => (
      `<span class="shop-card-gallery__slide" data-card-slide${index ? ' hidden' : ''}>${imageFor(image, product, { secondary: index > 0 })}</span>`
    )).join('');
    const progress = images.map((_, index) => `<span class="${index ? '' : 'is-active'}" data-card-dot></span>`).join('');
    return {
      gallery: true,
      html: `<span class="shop-card-gallery__slides">${slides}</span><span aria-hidden="true" class="shop-card-gallery__progress">${progress}</span>`,
    };
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
    const media = mediaFor(product);

    article.innerHTML = `
      <a class="shop-card__link" href="${encodeURIComponent(product.slug)}/">
        <div class="shop-card__media"${media.gallery ? ' data-card-gallery' : ''}>
          <span class="shop-card__badge${out ? ' shop-card__badge--out' : ''}">${out ? 'Нет в наличии' : 'Demo'}</span>
          ${media.html}
        </div>
        <div class="shop-card__body">
          <div class="shop-card__meta">
            <span>${escapeHTML(categoryLabel)}</span>
            <strong class="shop-card__price">${escapeHTML(priceFor(product))}</strong>
          </div>
          <h3>${escapeHTML(product.name)}</h3>
          <p class="shop-card__detail">${escapeHTML(detailFor(product))}</p>
          <div class="shop-card__stock">${escapeHTML(stockText)}</div>
        </div>
      </a>`;

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

  const advancedFilterCount = () => Number(Boolean(state.category)) + Number(state.sort !== 'default');

  const setFilterPanelOpen = open => {
    if (!filtersToggle || !filterPanel) return;
    filterPanel.classList.toggle('is-open', open);
    filtersToggle.setAttribute('aria-expanded', String(open));
    if (!open) {
      filterPanel.querySelectorAll('.custom-select.is-open').forEach(selectRoot => {
        selectRoot.classList.remove('is-open');
        selectRoot.querySelector('.custom-select__trigger')?.setAttribute('aria-expanded', 'false');
      });
    }
  };

  const syncFilterToggle = () => {
    if (!filtersToggle || !filterCount) return;
    const count = advancedFilterCount();
    filterCount.hidden = count === 0;
    filterCount.textContent = count ? String(count) : '';
  };

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
    syncFilterToggle();
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
    initCardGalleries(grid);
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

  filtersToggle?.addEventListener('click', () => {
    const open = filtersToggle.getAttribute('aria-expanded') !== 'true';
    setFilterPanelOpen(open);
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

  window.addEventListener('popstate', () => {
    state = readURLState();
    if (products.length) applyState({ updateURL: false, syncSearch: true });
  });

  retry.addEventListener('click', load);
  load();
}
