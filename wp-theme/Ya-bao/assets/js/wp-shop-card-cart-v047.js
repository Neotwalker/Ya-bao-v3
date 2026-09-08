(function () {
  'use strict';

  const formSelector = '[data-shop-card-cart]';

  function numberOr(value, fallback) {
    const parsed = Number.parseInt(String(value ?? ''), 10);
    return Number.isFinite(parsed) ? parsed : fallback;
  }

  function quantityState(form, nextValue) {
    const input = form.querySelector('input.qty[name="quantity"]');
    if (!input) return;

    const min = Math.max(1, numberOr(input.min, 1));
    const rawMax = numberOr(input.max, 0);
    const max = rawMax > 0 ? rawMax : Number.MAX_SAFE_INTEGER;
    const requested = numberOr(nextValue ?? input.value, min);
    const value = Math.max(min, Math.min(max, requested));

    input.value = String(value);
    const minus = form.querySelector('[data-shop-card-minus]');
    const plus = form.querySelector('[data-shop-card-plus]');
    if (minus) minus.disabled = value <= min;
    if (plus) plus.disabled = value >= max;
  }

  function setFeedback(form, message, isError) {
    const node = form.querySelector('[data-shop-card-feedback]');
    if (!node) return;
    node.textContent = message || '';
    node.classList.toggle('is-error', Boolean(isError));
  }

  function responseErrorMessage(html) {
    if (!html) return '';
    const doc = new DOMParser().parseFromString(html, 'text/html');
    const error = doc.querySelector('.woocommerce-error, .woocommerce-error li, .wc-block-components-notice-banner.is-error');
    if (!error) return '';
    error.querySelectorAll('.wc-forward').forEach(link => link.remove());
    return error.textContent?.replace(/\s+/g, ' ').trim() || '';
  }

  function refreshCart() {
    if (window.jQuery) {
      window.jQuery(document.body).trigger('wc_fragment_refresh');
    }
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
    const formData = new FormData(form);
    const originalLabel = submit.textContent;

    form.dataset.submitting = 'true';
    form.classList.add('is-loading');
    submit.disabled = true;
    submit.setAttribute('aria-busy', 'true');
    submit.textContent = 'Добавляю…';
    setFeedback(form, '', false);

    try {
      const response = await fetch(form.getAttribute('action') || window.location.href, {
        method: 'POST',
        body: formData,
        credentials: 'same-origin',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        redirect: 'follow',
      });

      const html = await response.text();
      if (!response.ok) throw new Error('Не удалось добавить товар в корзину.');

      const serverError = responseErrorMessage(html);
      if (serverError) throw new Error(serverError);

      submit.textContent = 'Добавлено';
      setFeedback(form, 'Товар добавлен в корзину.', false);
      refreshCart();

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

  document.querySelectorAll(formSelector).forEach(form => quantityState(form));
}());
