import {
  clearCart,
  getCartState,
  removeCartItem,
  updateCartItemQuantity,
} from './store.js';

const PUBLIC_STATUSES = new Set(['active', 'out_of_stock']);
const REQUEST_TIMEOUT_MS = 8000;
const DATA_URL = new URL('../../data/products.json', import.meta.url);

const asNumber = value => {
  const number = Number(value);
  return Number.isFinite(number) ? number : null;
};

export const assessCartItem = (item, product, feedStatus = 'ready') => {
  const storedPrice = asNumber(item?.unitPrice) ?? 0;
  const base = {
    code: 'ok',
    available: true,
    includeInSubtotal: true,
    effectivePrice: storedPrice,
    previousPrice: storedPrice,
    currentMax: item?.maxQuantity ?? null,
    message: '',
    priceChanged: false,
    unverified: feedStatus !== 'ready',
  };

  if (feedStatus !== 'ready') return base;

  if (!product || !PUBLIC_STATUSES.has(product.status) || product.sale_mode !== item.saleMode) {
    return {
      ...base,
      code: 'product-unavailable',
      available: false,
      includeInSubtotal: false,
      message: 'Товар недоступен.',
    };
  }

  if (product.status === 'out_of_stock' || product.stock_status === 'out_of_stock') {
    return {
      ...base,
      code: 'product-ended',
      available: false,
      includeInSubtotal: false,
      message: 'Товар закончился.',
    };
  }

  if (item.saleMode === 'weight') {
    const variants = Array.isArray(product.variants) ? product.variants : [];
    const variant = variants.find(candidate => candidate?.variant_id === item.variantId);
    if (!variant || variant.stock_status === 'out_of_stock') {
      return {
        ...base,
        code: 'variant-unavailable',
        available: false,
        includeInSubtotal: false,
        message: 'Выбранный вариант сейчас недоступен.',
      };
    }

    const currentPrice = asNumber(variant.price);
    if (currentPrice !== null) {
      return {
        ...base,
        effectivePrice: currentPrice,
        priceChanged: currentPrice !== storedPrice,
        message: currentPrice !== storedPrice ? 'Цена изменилась.' : '',
      };
    }
    return base;
  }

  const currentMax = Number.parseInt(product.quantity, 10);
  if (!Number.isInteger(currentMax) || currentMax < 1) {
    return {
      ...base,
      code: 'product-ended',
      available: false,
      includeInSubtotal: false,
      currentMax: 0,
      message: 'Товар закончился.',
    };
  }

  const currentPrice = asNumber(product.price);
  const priceChanged = currentPrice !== null && currentPrice !== storedPrice;
  if (item.quantity > currentMax) {
    return {
      ...base,
      code: 'stock-limited',
      available: false,
      includeInSubtotal: false,
      currentMax,
      effectivePrice: currentPrice ?? storedPrice,
      priceChanged,
      message: `На складе осталось только ${currentMax} шт. Уменьшите количество.`,
    };
  }

  return {
    ...base,
    currentMax,
    effectivePrice: currentPrice ?? storedPrice,
    priceChanged,
    message: priceChanged ? 'Цена изменилась.' : '',
  };
};

const root = typeof document !== 'undefined' ? document.querySelector('[data-cart-page]') : null;

if (root) {
  const list = root.querySelector('[data-cart-list]');
  const empty = root.querySelector('[data-cart-empty]');
  const content = root.querySelector('[data-cart-content]');
  const subtotalNode = root.querySelector('[data-cart-subtotal]');
  const countNode = root.querySelector('[data-cart-summary-count]');
  const summaryNote = root.querySelector('[data-cart-summary-note]');
  const validationStatus = root.querySelector('[data-cart-validation-status]');
  const clearButton = root.querySelector('[data-cart-clear]');

  const money = value => `${new Intl.NumberFormat('ru-RU').format(value)} ₽`;
  const escapeHTML = value => String(value ?? '').replace(/[&<>'"]/g, char => ({
    '&': '&amp;',
    '<': '&lt;',
    '>': '&gt;',
    "'": '&#39;',
    '"': '&quot;',
  })[char]);

  const pluralProducts = count => {
    const mod10 = count % 10;
    const mod100 = count % 100;
    if (mod10 === 1 && mod100 !== 11) return 'товар';
    if (mod10 >= 2 && mod10 <= 4 && (mod100 < 12 || mod100 > 14)) return 'товара';
    return 'товаров';
  };

  let feedStatus = 'loading';
  let productsById = new Map();

  const getAssessment = item => assessCartItem(item, productsById.get(item.productId), feedStatus);

  const productHref = item => item.slug ? `../shop/${encodeURIComponent(item.slug)}/` : '../shop/';

  const statusMarkup = (item, assessment) => {
    const notices = [];
    if (assessment.code !== 'ok' && assessment.message) {
      notices.push(`<p class="cart-line__notice cart-line__notice--error">${escapeHTML(assessment.message)}</p>`);
    }
    if (assessment.priceChanged) {
      notices.push(`<p class="cart-line__notice cart-line__notice--price">Цена изменилась: <s>${escapeHTML(money(assessment.previousPrice))}</s> → <strong>${escapeHTML(money(assessment.effectivePrice))}</strong>. В итог включена актуальная цена.</p>`);
    }
    if (assessment.unverified && feedStatus === 'error') {
      notices.push('<p class="cart-line__notice">Актуальность цены и наличия сейчас не проверена.</p>');
    }
    return notices.join('');
  };

  const quantityMarkup = (item, assessment) => {
    const canAdjust = !['product-unavailable', 'product-ended', 'variant-unavailable'].includes(assessment.code);
    const max = item.saleMode === 'unit' && Number.isInteger(assessment.currentMax) && assessment.currentMax > 0
      ? assessment.currentMax
      : null;
    const minusDisabled = !canAdjust || item.quantity <= 1;
    const plusDisabled = !canAdjust || (max !== null && item.quantity >= max);
    const maxAttr = max !== null ? ` max="${max}" data-cart-max="${max}"` : '';
    const disabledAttr = canAdjust ? '' : ' disabled';

    return `
      <div class="cart-line__quantity">
        <span class="cart-line__label">Количество</span>
        <div class="product-quantity-picker__control">
          <button aria-label="Уменьшить количество" data-cart-minus data-cart-key="${escapeHTML(item.key)}" type="button"${minusDisabled ? ' disabled' : ''}>−</button>
          <input aria-label="Количество товара" data-cart-quantity-input data-cart-key="${escapeHTML(item.key)}" inputmode="numeric" min="1"${maxAttr} step="1" type="number" value="${item.quantity}"${disabledAttr}/>
          <button aria-label="Увеличить количество" data-cart-plus data-cart-key="${escapeHTML(item.key)}" type="button"${plusDisabled ? ' disabled' : ''}>+</button>
        </div>
      </div>`;
  };

  const renderLine = item => {
    const assessment = getAssessment(item);
    const lineTotal = assessment.includeInSubtotal ? assessment.effectivePrice * item.quantity : null;
    const variant = item.saleMode === 'weight' && item.variantLabel
      ? `<p class="cart-line__variant">Вариант: ${escapeHTML(item.variantLabel)}</p>`
      : '<p class="cart-line__variant">Поштучно</p>';
    const media = item.image
      ? `<img alt="${escapeHTML(item.name)}" decoding="async" loading="lazy" src="${escapeHTML(item.image)}"/>`
      : '<span class="cart-line__media-placeholder" aria-hidden="true">茶</span>';
    const warningClass = assessment.code !== 'ok' || assessment.priceChanged || (assessment.unverified && feedStatus === 'error') ? ' cart-line--warning' : '';

    return `
      <article class="cart-line${warningClass}" data-cart-line data-cart-key="${escapeHTML(item.key)}">
        <a class="cart-line__media" href="${escapeHTML(productHref(item))}">${media}</a>
        <div class="cart-line__body">
          <div class="cart-line__heading">
            <div>
              <h3><a href="${escapeHTML(productHref(item))}">${escapeHTML(item.name || 'Товар')}</a></h3>
              ${variant}
            </div>
            <button class="cart-line__remove" data-cart-remove data-cart-key="${escapeHTML(item.key)}" type="button">Удалить</button>
          </div>
          <div aria-live="polite" class="cart-line__notices">${statusMarkup(item, assessment)}</div>
          <div class="cart-line__controls">
            <div class="cart-line__price"><span class="cart-line__label">Цена</span><strong>${escapeHTML(money(assessment.effectivePrice))}</strong></div>
            ${quantityMarkup(item, assessment)}
            <div class="cart-line__total"><span class="cart-line__label">Сумма</span><strong>${lineTotal === null ? 'Не включено' : escapeHTML(money(lineTotal))}</strong></div>
          </div>
        </div>
      </article>`;
  };

  const render = () => {
    const state = getCartState();
    const items = state.items;
    const isEmpty = items.length === 0;
    empty.hidden = !isEmpty;
    content.hidden = isEmpty;
    clearButton.hidden = isEmpty;
    validationStatus.hidden = feedStatus !== 'error' || isEmpty;

    if (isEmpty) {
      list.innerHTML = '';
      subtotalNode.textContent = money(0);
      countNode.textContent = '0 товаров';
      summaryNote.hidden = true;
      return;
    }

    list.innerHTML = items.map(renderLine).join('');
    const assessed = items.map(item => ({ item, assessment: getAssessment(item) }));
    const subtotal = assessed.reduce((sum, entry) => (
      entry.assessment.includeInSubtotal
        ? sum + (entry.assessment.effectivePrice * entry.item.quantity)
        : sum
    ), 0);
    const totalCount = items.reduce((sum, item) => sum + item.quantity, 0);
    const excluded = assessed.filter(entry => !entry.assessment.includeInSubtotal).length;

    subtotalNode.textContent = money(subtotal);
    countNode.textContent = `${totalCount} ${pluralProducts(totalCount)}`;
    summaryNote.hidden = excluded === 0;
    summaryNote.textContent = excluded
      ? `Недоступные позиции: ${excluded}. Они не включены в промежуточный итог.`
      : '';
  };

  const updateQuantity = (key, requested, maxOverride = null) => {
    const parsed = Number.parseInt(requested, 10);
    let next = Number.isInteger(parsed) ? Math.max(1, parsed) : 1;
    if (Number.isInteger(maxOverride) && maxOverride > 0) next = Math.min(next, maxOverride);
    updateCartItemQuantity(key, next, Number.isInteger(maxOverride) && maxOverride > 0 ? { maxQuantity: maxOverride } : undefined);
  };

  root.addEventListener('click', event => {
    const removeButton = event.target.closest('[data-cart-remove]');
    if (removeButton) {
      removeCartItem(removeButton.dataset.cartKey || '');
      return;
    }

    const stepButton = event.target.closest('[data-cart-minus], [data-cart-plus]');
    if (!stepButton) return;
    const key = stepButton.dataset.cartKey || '';
    const item = getCartState().items.find(entry => entry.key === key);
    if (!item) return;
    const line = stepButton.closest('[data-cart-line]');
    const input = line?.querySelector('[data-cart-quantity-input]');
    const max = Number.parseInt(input?.dataset.cartMax || input?.max || '', 10);
    const delta = stepButton.matches('[data-cart-plus]') ? 1 : -1;
    updateQuantity(key, item.quantity + delta, Number.isInteger(max) ? max : null);
  });

  root.addEventListener('change', event => {
    const input = event.target.closest('[data-cart-quantity-input]');
    if (!input) return;
    const max = Number.parseInt(input.dataset.cartMax || input.max || '', 10);
    updateQuantity(input.dataset.cartKey || '', input.value, Number.isInteger(max) ? max : null);
  });

  clearButton.addEventListener('click', () => {
    if (!window.confirm('Очистить корзину?')) return;
    clearCart();
  });

  document.addEventListener('shop:cartchange', render);

  const loadProducts = async () => {
    const controller = new AbortController();
    const timer = window.setTimeout(() => controller.abort(), REQUEST_TIMEOUT_MS);
    try {
      const response = await fetch(DATA_URL, { signal: controller.signal, cache: 'no-store' });
      if (!response.ok) throw new Error(`HTTP ${response.status}`);
      const data = await response.json();
      const products = Array.isArray(data?.products) ? data.products : [];
      productsById = new Map(products.map(product => [product.external_id, product]));
      feedStatus = 'ready';
    } catch {
      feedStatus = 'error';
      validationStatus.textContent = 'Не удалось проверить актуальные цены и наличие. Показаны данные, сохранённые при добавлении в корзину.';
    } finally {
      window.clearTimeout(timer);
      render();
    }
  };

  render();
  loadProducts();
}
