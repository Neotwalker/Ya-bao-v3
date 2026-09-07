import { assessCartItem } from './cart.js';
import { getCartState, getCartWeightSummary } from './store.js';
import { setFieldError, wireFormErrors } from './components/forms/validation.js';

const PUBLIC_STATUSES = new Set(['active', 'out_of_stock']);
const REQUEST_TIMEOUT_MS = 8000;
const DATA_URL = new URL('../../data/products.json', import.meta.url);

const root = typeof document !== 'undefined' ? document.querySelector('[data-checkout-page]') : null;

const money = value => `${new Intl.NumberFormat('ru-RU').format(value)} ₽`;
const number = value => new Intl.NumberFormat('ru-RU').format(value);
const escapeHTML = value => String(value ?? '').replace(/[&<>\'\"]/g, char => ({
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

export const isCheckoutEmail = value => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(String(value || '').trim());

export const getCheckoutVariantText = item => {
  if (item?.saleMode !== 'weight') return `Количество: ${Number.parseInt(item?.quantity, 10) || 1} шт.`;
  const summary = getCartWeightSummary(item);
  if (!summary) return item.variantLabel || 'Весовой вариант';
  return `${number(summary.unitWeight)} г × ${summary.quantity} = ${number(summary.totalWeight)} г`;
};

if (root) {
  const form = root.querySelector('[data-checkout-form]');
  const loading = root.querySelector('[data-checkout-loading]');
  const empty = root.querySelector('[data-checkout-empty]');
  const content = root.querySelector('[data-checkout-content]');
  const itemsNode = root.querySelector('[data-checkout-items]');
  const countNode = root.querySelector('[data-checkout-count]');
  const subtotalNode = root.querySelector('[data-checkout-subtotal]');
  const summaryNote = root.querySelector('[data-checkout-summary-note]');
  const validationStatus = root.querySelector('[data-checkout-validation-status]');
  const submit = form?.querySelector('[type="submit"]');
  const formStatus = form?.querySelector('[data-form-status]');
  const fulfillmentInputs = [...(form?.querySelectorAll('input[name="fulfillment"]') || [])];
  const addressInput = form?.querySelector('[name="address"]');
  const fulfillmentInfo = [...(form?.querySelectorAll('[data-fulfillment-info]') || [])];
  const fulfillmentSummary = root.querySelector('[data-checkout-fulfillment-summary]');
  const deliveryNote = root.querySelector('[data-checkout-delivery-note]');

  let feedStatus = 'loading';
  let productsById = new Map();
  let checkoutState = { subtotal: 0, invalidCount: 0, priceChangedCount: 0 };

  const getAssessment = item => assessCartItem(item, productsById.get(item.productId), feedStatus);

  const syncFulfillment = () => {
    const method = form?.querySelector('input[name="fulfillment"]:checked')?.value === 'delivery' ? 'delivery' : 'pickup';
    const isDelivery = method === 'delivery';

    fulfillmentInfo.forEach(panel => {
      panel.hidden = panel.dataset.fulfillmentInfo !== method;
    });

    if (addressInput) {
      addressInput.disabled = !isDelivery;
      addressInput.required = isDelivery;
      if (!isDelivery) setFieldError(addressInput, '');
    }

    if (fulfillmentSummary) {
      fulfillmentSummary.textContent = isDelivery ? 'Доставка' : 'Самовывоз';
    }
    if (deliveryNote) {
      deliveryNote.textContent = isDelivery
        ? 'Зона, стоимость и сроки доставки пока уточняются. В итог включены только товары.'
        : 'Самовывоз: Челябинск, Кирова, 94. В итог включены только товары.';
    }
  };

  const summaryItemMarkup = item => {
    const assessment = getAssessment(item);
    const media = item.image
      ? `<img alt="${escapeHTML(item.name || 'Товар')}" decoding="async" loading="lazy" src="${escapeHTML(item.image)}">`
      : '<span class="checkout-item__placeholder" aria-hidden="true">茶</span>';
    const lineTotal = assessment.includeInSubtotal ? assessment.effectivePrice * item.quantity : null;
    const notices = [];
    if (!assessment.includeInSubtotal && assessment.message) notices.push(assessment.message);
    if (assessment.priceChanged) notices.push('Цена обновлена.');

    return `
      <article class="checkout-item${assessment.includeInSubtotal ? '' : ' checkout-item--warning'}">
        <div class="checkout-item__media">${media}</div>
        <div class="checkout-item__body">
          <h3>${escapeHTML(item.name || 'Товар')}</h3>
          <p>${escapeHTML(getCheckoutVariantText(item))}</p>
          ${notices.length ? `<small>${escapeHTML(notices.join(' '))}</small>` : ''}
        </div>
        <strong class="checkout-item__price">${lineTotal === null ? '—' : escapeHTML(money(lineTotal))}</strong>
      </article>`;
  };

  const render = () => {
    const state = getCartState();
    const items = state.items;
    const isEmpty = items.length === 0;
    loading.hidden = true;
    empty.hidden = !isEmpty;
    content.hidden = isEmpty;

    if (isEmpty) {
      itemsNode.innerHTML = '';
      countNode.textContent = '0 товаров';
      subtotalNode.textContent = money(0);
      summaryNote.hidden = true;
      validationStatus.hidden = true;
      if (submit) submit.disabled = true;
      checkoutState = { subtotal: 0, invalidCount: 0, priceChangedCount: 0 };
      return;
    }

    const assessed = items.map(item => ({ item, assessment: getAssessment(item) }));
    const subtotal = assessed.reduce((sum, entry) => (
      entry.assessment.includeInSubtotal
        ? sum + (entry.assessment.effectivePrice * entry.item.quantity)
        : sum
    ), 0);
    const invalidCount = assessed.filter(entry => !entry.assessment.includeInSubtotal).length;
    const priceChangedCount = assessed.filter(entry => entry.assessment.priceChanged).length;
    const totalCount = items.reduce((sum, item) => sum + item.quantity, 0);

    itemsNode.innerHTML = items.map(summaryItemMarkup).join('');
    countNode.textContent = `${totalCount} ${pluralProducts(totalCount)}`;
    subtotalNode.textContent = money(subtotal);
    checkoutState = { subtotal, invalidCount, priceChangedCount };

    if (feedStatus === 'loading') {
      summaryNote.hidden = false;
      summaryNote.textContent = 'Проверяем актуальные цены и наличие…';
    } else if (feedStatus === 'error') {
      summaryNote.hidden = false;
      summaryNote.textContent = 'Не удалось проверить каталог. Показаны данные, сохранённые в корзине.';
    } else if (invalidCount) {
      summaryNote.hidden = false;
      summaryNote.textContent = `Недоступных позиций: ${invalidCount}. Исправьте корзину перед оформлением.`;
    } else if (priceChangedCount) {
      summaryNote.hidden = false;
      summaryNote.textContent = `Цена обновлена у ${priceChangedCount} ${priceChangedCount === 1 ? 'позиции' : 'позиций'}. В итог включены актуальные цены.`;
    } else {
      summaryNote.hidden = true;
      summaryNote.textContent = '';
    }

    validationStatus.hidden = feedStatus !== 'error';
    if (feedStatus === 'error') {
      validationStatus.textContent = 'Сейчас не удалось проверить актуальность цены и наличия. Перед реальным созданием заказа эти данные должны быть проверены сервером.';
    }

    if (submit) submit.disabled = feedStatus === 'loading' || invalidCount > 0;
  };

  const clearFormStatus = () => {
    if (!formStatus) return;
    formStatus.textContent = '';
    formStatus.className = 'form-status';
  };

  const focusInvalid = field => field?.focus();

  const validateCheckout = () => {
    if (!form) return { valid: false, firstInvalid: null };
    let firstInvalid = null;
    const mark = (field, message) => {
      setFieldError(field, message);
      if (message && !firstInvalid) firstInvalid = field;
    };

    const name = form.querySelector('[name="name"]');
    const phone = form.querySelector('[name="phone"]');
    const email = form.querySelector('[name="email"]');
    const consent = form.querySelector('[name="consent"]');

    mark(name, String(name?.value || '').trim() ? '' : 'Заполните поле.');
    mark(phone, String(phone?.value || '').replace(/\D/g, '').length >= 11 ? '' : 'Введите полный номер телефона.');
    mark(email, isCheckoutEmail(email?.value) ? '' : 'Введите корректный email.');

    const isDelivery = form.querySelector('input[name="fulfillment"]:checked')?.value === 'delivery';
    if (isDelivery && addressInput) mark(addressInput, String(addressInput.value || '').trim() ? '' : 'Укажите адрес доставки.');
    if (consent) mark(consent, consent.checked ? '' : 'Подтвердите согласие.');

    return { valid: !firstInvalid, firstInvalid };
  };

  if (form) {
    wireFormErrors(form);
    syncFulfillment();

    fulfillmentInputs.forEach(input => {
      input.addEventListener('change', () => {
        syncFulfillment();
        clearFormStatus();
      });
    });

    form.querySelectorAll('input, textarea').forEach(field => {
      field.addEventListener('input', () => {
        setFieldError(field, '');
        clearFormStatus();
      });
    });

    form.addEventListener('submit', event => {
      event.preventDefault();
      if (checkoutState.invalidCount > 0 || feedStatus === 'loading') {
        if (formStatus) {
          formStatus.textContent = 'Сначала исправьте состав корзины.';
          formStatus.className = 'form-status is-error';
        }
        return;
      }

      const validation = validateCheckout();
      if (!validation.valid) {
        if (formStatus) {
          formStatus.textContent = 'Проверьте обязательные поля.';
          formStatus.className = 'form-status is-error';
        }
        focusInvalid(validation.firstInvalid);
        return;
      }

      if (formStatus) {
        formStatus.textContent = 'Демо-режим: данные проверены. Создание заказа и отправка менеджеру пока не подключены.';
        formStatus.className = 'form-status is-success';
      }
    });
  }

  document.addEventListener('shop:cartchange', render);

  const loadProducts = async () => {
    const controller = new AbortController();
    const timer = window.setTimeout(() => controller.abort(), REQUEST_TIMEOUT_MS);
    try {
      const response = await fetch(DATA_URL, { signal: controller.signal, cache: 'no-store' });
      if (!response.ok) throw new Error(`HTTP ${response.status}`);
      const data = await response.json();
      const products = Array.isArray(data?.products) ? data.products : [];
      productsById = new Map(products
        .filter(product => PUBLIC_STATUSES.has(product?.status))
        .map(product => [product.external_id, product]));
      feedStatus = 'ready';
    } catch {
      feedStatus = 'error';
    } finally {
      window.clearTimeout(timer);
      render();
    }
  };

  render();
  loadProducts();
}
