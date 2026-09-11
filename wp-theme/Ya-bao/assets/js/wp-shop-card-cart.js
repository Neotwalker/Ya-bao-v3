(function () {
  'use strict';

  const formSelector = '[data-shop-card-cart]';

  function numberOr(value, fallback) {
    const parsed = Number.parseInt(String(value ?? ''), 10);
    return Number.isFinite(parsed) ? parsed : fallback;
  }

  function selectedProductId(form) {
    const type = form.dataset.productType || 'simple';

    if (type === 'variable') {
      return String(
        form.querySelector('[data-shop-card-variation-id]')?.value || ''
      );
    }

    return String(
      form.querySelector('input[name="add-to-cart"]')?.value || ''
    );
  }

  function cartQuantityForProduct(productId) {
    if (!productId) return 0;

    let total = 0;

    document
      .querySelectorAll('[data-wc-mini-cart-content] [data-product_id]')
      .forEach(node => {
        if (node.getAttribute('data-product_id') !== productId) return;

        const item = node.closest('[data-mini-cart-item]');
        const quantity = item?.querySelector('.mini-cart-qty span');

        total += numberOr(quantity?.textContent, 0);
      });

    return total;
  }

  function availableQuantity(form, input) {
    const rawMax = numberOr(input.max, 0);

    if (rawMax <= 0) {
      return Number.MAX_SAFE_INTEGER;
    }

    const productId = selectedProductId(form);
    const inCart = cartQuantityForProduct(productId);

    return Math.max(0, rawMax - inCart);
  }

  function quantityState(form, nextValue) {
    const input = form.querySelector('input.qty[name="quantity"]');
    if (!input) return null;

    const min = Math.max(1, numberOr(input.min, 1));
    const max = availableQuantity(form, input);
    const unavailable = max === 0;
    const requested = numberOr(nextValue ?? input.value, min);
    const value = unavailable
      ? min
      : Math.max(min, Math.min(max, requested));

    input.value = String(value);
    input.disabled = unavailable;

    const minus = form.querySelector('[data-shop-card-minus]');
    const plus = form.querySelector('[data-shop-card-plus]');
    const submit = form.querySelector('[data-shop-card-add]');

    if (minus) {
      minus.disabled = unavailable || value <= min;
    }

    if (plus) {
      plus.disabled = unavailable || value >= max;
    }

    if (submit && form.dataset.submitting !== 'true') {
      submit.disabled = unavailable;
    }

    if (unavailable) {
      setFeedback(
        form,
        'Максимальное доступное количество уже в корзине.',
        false
      );
    }

    return {
      value,
      max,
      unavailable,
    };
  }

  function syncQuantityAvailability() {
    document
      .querySelectorAll(formSelector)
      .forEach(form => quantityState(form));
  }

  function setFeedback(form, message, isError) {
    const node = form.querySelector('[data-shop-card-feedback]');
    if (!node) return;
    node.textContent = message || '';
    node.classList.toggle('is-error', Boolean(isError));
  }

  function addToCartPayload(form) {
    const payload = new FormData();
    const quantity = form.querySelector('input.qty[name="quantity"]');
    const type = form.dataset.productType || 'simple';

    let productId = '';

    if (type === 'variable') {
      productId =
        form.querySelector('[data-shop-card-variation-id]')?.value || '';
    } else {
      productId =
        form.querySelector('input[name="add-to-cart"]')?.value || '';
    }

    if (!productId) {
      throw new Error('Не удалось определить товар.');
    }

    payload.set('product_id', productId);
    payload.set('quantity', quantity?.value || '1');

    return payload;
  }

  function applyFragments(fragments, cartHash) {
    if (!fragments || typeof fragments !== 'object') {
      throw new Error('Корзина не вернула обновлённые данные.');
    }

    if (!window.jQuery) {
      throw new Error('Не удалось обновить корзину.');
    }

    const $ = window.jQuery;

    Object.entries(fragments).forEach(([selector, html]) => {
      $(selector).replaceWith(html);
    });

    $(document.body).trigger('added_to_cart', [
      fragments,
      cartHash || '',
      $(),
    ]);
  }
  function selectVariation(button) {
    if (!button || button.disabled) return;
    const form = button.closest(formSelector);
    if (!form) return;

    form.querySelectorAll('[data-shop-card-variation]').forEach(option => {
      option.setAttribute('aria-checked', option === button ? 'true' : 'false');
    });

    const variationId = form.querySelector('[data-shop-card-variation-id]');
    const attribute = form.querySelector('[data-shop-card-attribute-input]');
    if (variationId) variationId.value = button.dataset.variationId || '';
    if (attribute) attribute.value = button.dataset.attributeValue || '';

    const card = form.closest('.shop-card');
    const price = card?.querySelector('[data-shop-card-price]');
    const stock = card?.querySelector('[data-shop-card-stock]');
    if (price && button.dataset.priceHtml) price.innerHTML = button.dataset.priceHtml;
    if (stock && button.dataset.stockText) stock.textContent = button.dataset.stockText;

    const input = form.querySelector('input.qty[name="quantity"]');
    if (input) {
      const max = numberOr(button.dataset.max, 0);
      if (max > 0) input.max = String(max);
      else input.removeAttribute('max');
      quantityState(form, 1);
    }

    setFeedback(form, '', false);
  }

  document.addEventListener('click', event => {
    const variation = event.target.closest('[data-shop-card-variation]');
    if (variation) {
      event.preventDefault();
      selectVariation(variation);
      return;
    }

    const step = event.target.closest('[data-shop-card-minus], [data-shop-card-plus]');
    if (!step) return;

    const form = step.closest(formSelector);
    const input = form?.querySelector('input.qty[name="quantity"]');
    if (!form || !input) return;

    event.preventDefault();
    const current = numberOr(input.value, 1);
    quantityState(form, current + (step.matches('[data-shop-card-plus]') ? 1 : -1));
    setFeedback(form, '', false);
  });

  document.addEventListener('change', event => {
    const input = event.target.closest(`${formSelector} input.qty[name="quantity"]`);
    if (!input) return;
    const form = input.closest(formSelector);
    if (!form) return;
    quantityState(form, input.value);
    setFeedback(form, '', false);
  });

  document.addEventListener('focusout', event => {
    const input = event.target.closest(`${formSelector} input.qty[name="quantity"]`);
    if (!input) return;
    const form = input.closest(formSelector);
    if (form) quantityState(form, input.value);
  });

  document.addEventListener('submit', async event => {
    const form = event.target.closest(formSelector);
    if (!form) return;

    event.preventDefault();
    if (form.dataset.submitting === 'true') return;

    const submit = event.submitter || form.querySelector('[data-shop-card-add]');
    if (!submit || submit.disabled) return;

    quantityState(form);
    const originalLabel = submit.textContent;

    form.dataset.submitting = 'true';
    form.classList.add('is-loading');
    submit.disabled = true;
    submit.setAttribute('aria-busy', 'true');
    submit.textContent = 'Добавляю…';
    setFeedback(form, '', false);

    try {
      const endpoint = form.dataset.shopCardAjaxUrl;

      if (!endpoint) {
        throw new Error('Адрес корзины недоступен.');
      }

      const payload = addToCartPayload(form);

      const response = await fetch(endpoint, {
        method: 'POST',
        body: payload,
        credentials: 'same-origin',
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
        },
      });

      if (!response.ok) {
        throw new Error('Не удалось добавить товар в корзину.');
      }

      let data;

      try {
        data = await response.json();
      } catch {
        throw new Error('Корзина вернула некорректный ответ.');
      }

      if (data?.error) {
        throw new Error('Не удалось добавить товар в корзину.');
      }

      applyFragments(
        data?.fragments,
        data?.cart_hash
      );

      submit.textContent = 'Добавлено';
      setFeedback(form, 'Товар добавлен в корзину.', false);
      document.body.dispatchEvent(new CustomEvent('yabao:cartupdated', {
        detail: { source: 'shop-card' },
      }));

      window.setTimeout(() => {
        if (form.dataset.submitting === 'true') return;
        submit.textContent = originalLabel;
        setFeedback(form, '', false);
      }, 1400);
    } catch (error) {
      submit.textContent = originalLabel;
      setFeedback(form, error?.message || 'Не удалось добавить товар в корзину.', true);
    } finally {
      form.dataset.submitting = 'false';
      form.classList.remove('is-loading');
      submit.removeAttribute('aria-busy');
      submit.disabled = false;
      quantityState(form);
    }
  });

  document.body.addEventListener(
    'yabao:cartupdated',
    syncQuantityAvailability
  );

  if (window.jQuery) {
    window.jQuery(document.body).on(
      'removed_from_cart wc_fragments_refreshed',
      syncQuantityAvailability
    );
  }

  syncQuantityAvailability();
}());
