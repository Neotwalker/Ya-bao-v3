(function () {
  'use strict';

  const clamp = (value, min, max) => Math.max(min, Math.min(max, value));

  function enhanceQuantity(form) {
    const quantity = form.querySelector('.quantity');
    const input = quantity?.querySelector('input.qty');
    if (!quantity || !input) return null;

    const existing = quantity.closest('.product-quantity-picker');
    if (existing) return existing;

    const fieldset = document.createElement('fieldset');
    fieldset.className = 'product-quantity-picker';

    const legend = document.createElement('legend');
    legend.textContent = 'Количество, шт.';

    const control = document.createElement('div');
    control.className = 'product-quantity-picker__control';

    const minus = document.createElement('button');
    minus.type = 'button';
    minus.setAttribute('aria-label', 'Уменьшить количество');
    minus.textContent = '−';

    const plus = document.createElement('button');
    plus.type = 'button';
    plus.setAttribute('aria-label', 'Увеличить количество');
    plus.textContent = '+';

    const bounds = () => {
      const min = Number.parseInt(input.min || '1', 10) || 1;
      const rawMax = Number.parseInt(input.max || '', 10);
      const max = Number.isFinite(rawMax) && rawMax > 0 ? rawMax : Number.MAX_SAFE_INTEGER;
      return { min, max };
    };

    const setValue = next => {
      const { min, max } = bounds();
      const parsed = Number.parseInt(String(next), 10);
      const value = clamp(Number.isFinite(parsed) ? parsed : min, min, max);
      input.value = String(value);
      minus.disabled = value <= min;
      plus.disabled = value >= max;
    };

    minus.addEventListener('click', () => setValue(Number(input.value) - 1));
    plus.addEventListener('click', () => setValue(Number(input.value) + 1));
    input.addEventListener('change', () => setValue(input.value));
    input.addEventListener('blur', () => setValue(input.value));

    quantity.before(fieldset);
    control.append(minus, quantity, plus);
    fieldset.append(legend, control);
    setValue(input.value);
    return fieldset;
  }

  function moveActions(form, target) {
    const summary = form.closest('.product-summary');
    const sourceActions = summary?.querySelector(':scope > .product-summary__actions');
    const submit = form.querySelector('.single_add_to_cart_button');
    const returnLink = sourceActions?.querySelector('a.button');
    if (!summary || !submit || !returnLink || !target) return;

    submit.classList.add('button', 'button--walnut');
    sourceActions.classList.add('yabao-wc-actions');
    target.append(sourceActions);
    sourceActions.prepend(submit);
    submit.textContent = 'Добавить в корзину';
  }

  function feedbackNode(form) {
    let node = form.querySelector('.yabao-cart-feedback');
    if (node) return node;

    node = document.createElement('p');
    node.className = 'yabao-cart-feedback';
    node.setAttribute('role', 'status');
    node.setAttribute('aria-live', 'polite');
    form.append(node);
    return node;
  }

  function setFeedback(form, message, isError) {
    const node = feedbackNode(form);
    node.textContent = message || '';
    node.classList.toggle('is-error', Boolean(isError));
  }

  function refreshCartFragments() {
    if (window.jQuery) {
      window.jQuery(document.body).trigger('wc_fragment_refresh');
    }
  }

  function responseErrorMessage(html) {
    if (!html) return '';
    const documentFromResponse = new DOMParser().parseFromString(html, 'text/html');
    const error = documentFromResponse.querySelector('.woocommerce-error, .woocommerce-error li, .wc-block-components-notice-banner.is-error');
    if (!error) return '';
    error.querySelectorAll('.wc-forward').forEach(link => link.remove());
    return error.textContent?.replace(/\s+/g, ' ').trim() || '';
  }

  function enableAjaxAddToCart(form) {
    if (form.dataset.yabaoAjaxCartReady === 'true') return;
    form.dataset.yabaoAjaxCartReady = 'true';

    form.addEventListener('submit', async event => {
      event.preventDefault();

      const submit = event.submitter || form.querySelector('.single_add_to_cart_button');
      if (!submit || submit.disabled || submit.classList.contains('disabled')) return;
      if (form.dataset.yabaoSubmitting === 'true') return;

      const formData = new FormData(form);
      if (submit.name && !formData.has(submit.name)) {
        formData.append(submit.name, submit.value || '');
      }

      form.dataset.yabaoSubmitting = 'true';
      submit.disabled = true;
      submit.setAttribute('aria-busy', 'true');
      setFeedback(form, 'Добавляю в корзину…', false);

      try {
        const response = await fetch(form.getAttribute('action') || window.location.href, {
          method: (form.getAttribute('method') || 'post').toUpperCase(),
          body: formData,
          credentials: 'same-origin',
          headers: {
            'X-Requested-With': 'XMLHttpRequest'
          },
          redirect: 'follow'
        });

        const html = await response.text();
        if (!response.ok) {
          throw new Error('Не удалось добавить товар в корзину.');
        }

        const serverError = responseErrorMessage(html);
        if (serverError) {
          throw new Error(serverError);
        }

        setFeedback(form, 'Добавлено в корзину.', false);
        refreshCartFragments();
      } catch (error) {
        setFeedback(form, error?.message || 'Не удалось добавить товар в корзину.', true);
      } finally {
        form.dataset.yabaoSubmitting = 'false';
        submit.removeAttribute('aria-busy');
        submit.disabled = submit.classList.contains('disabled');
      }
    });
  }

  function enhanceSimpleForm(form) {
    if (form.dataset.yabaoParityReady === 'true') return;
    form.dataset.yabaoParityReady = 'true';
    form.classList.add('yabao-product-form', 'yabao-simple-form');
    enhanceQuantity(form);
    moveActions(form, form);
    enableAjaxAddToCart(form);
  }

  function optionWeight(option) {
    const fromValue = Number.parseInt(String(option.value).replace(/[^0-9]/g, ''), 10);
    const fromText = Number.parseInt(String(option.textContent).replace(/[^0-9]/g, ''), 10);
    return Number.isFinite(fromText) ? fromText : (Number.isFinite(fromValue) ? fromValue : 0);
  }

  function enhanceWeightForm(form) {
    if (form.dataset.yabaoParityReady === 'true') return;
    const select = form.querySelector('select[name="attribute_pa_weight"]');
    const summary = form.closest('.product-summary');
    const facts = summary?.querySelector('.product-facts');
    const variationButton = form.querySelector('.variations_button');
    if (!select || !summary || !facts || !variationButton) return;

    form.dataset.yabaoParityReady = 'true';
    form.classList.add('yabao-product-form', 'yabao-weight-form');

    const options = [...select.options]
      .filter(option => option.value && !option.disabled)
      .sort((a, b) => optionWeight(a) - optionWeight(b));
    if (!options.length) return;

    const fieldset = document.createElement('fieldset');
    fieldset.className = 'product-variant-picker yabao-wc-weight-picker';
    const legend = document.createElement('legend');
    legend.textContent = 'Вес';
    const bar = document.createElement('div');
    bar.className = 'shop-filter-bar';
    bar.setAttribute('role', 'radiogroup');
    bar.setAttribute('aria-label', 'Выберите вес');

    const buttons = options.map(option => {
      const button = document.createElement('button');
      button.type = 'button';
      button.className = 'shop-filter';
      button.setAttribute('role', 'radio');
      button.dataset.value = option.value;
      button.textContent = option.textContent.trim();
      button.addEventListener('click', () => {
        select.value = option.value;
        select.dispatchEvent(new Event('change', { bubbles: true }));
        syncButtons();
      });
      bar.append(button);
      return button;
    });

    const syncButtons = () => {
      buttons.forEach(button => {
        const selected = button.dataset.value === select.value;
        button.setAttribute('aria-checked', selected ? 'true' : 'false');
      });
    };

    fieldset.append(legend, bar);
    select.addEventListener('change', syncButtons);

    const quantityPicker = enhanceQuantity(form);
    const optionsRow = document.createElement('div');
    optionsRow.className = 'yabao-product-options';
    optionsRow.append(fieldset);
    if (quantityPicker) optionsRow.append(quantityPicker);
    form.prepend(optionsRow);

    const quantity = form.querySelector('.product-quantity-picker input.qty');
    if (quantity) {
      quantity.value = '1';
      quantity.dispatchEvent(new Event('change', { bubbles: true }));
    }
    moveActions(form, variationButton);
    enableAjaxAddToCart(form);

    const topPrice = summary.querySelector('.product-summary__price');
    if (window.jQuery) {
      window.jQuery(form).on('found_variation.yabaoParity', function (_event, variation) {
        if (topPrice && variation?.price_html) topPrice.innerHTML = variation.price_html;
        if (!quantity || !variation) return;

        window.setTimeout(() => {
          const minQty = Number.parseInt(String(variation.min_qty ?? '1'), 10);
          const maxQty = Number.parseInt(String(variation.max_qty ?? ''), 10);
          quantity.min = String(Number.isFinite(minQty) && minQty > 0 ? minQty : 1);
          if (Number.isFinite(maxQty) && maxQty > 0) quantity.max = String(maxQty);
          else quantity.removeAttribute('max');
          if (variation.step) quantity.step = String(variation.step);
          quantity.value = quantity.min || '1';
          quantity.dispatchEvent(new Event('change', { bubbles: true }));
        }, 0);
      });
    }

    const first = options[0];
    select.value = first.value;
    syncButtons();
    window.setTimeout(() => {
      select.dispatchEvent(new Event('change', { bubbles: true }));
      syncButtons();
    }, 0);
  }

  function init() {
    document.querySelectorAll('.yabao-wc-add-to-cart form.cart').forEach(form => {
      if (form.matches('.variations_form')) enhanceWeightForm(form);
      else enhanceSimpleForm(form);
    });
  }

  function resyncWeightForms() {
    document.querySelectorAll('.yabao-weight-form select[name="attribute_pa_weight"]').forEach(select => {
      if (!select.value) return;
      select.dispatchEvent(new Event('change', { bubbles: true }));
    });
  }

  /* Footer script: enhance immediately, not after every product image has loaded. */
  init();

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
      init();
      window.setTimeout(resyncWeightForms, 0);
    }, { once: true });
  } else {
    window.setTimeout(resyncWeightForms, 0);
  }
}());
