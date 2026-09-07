(function () {
  'use strict';

  const clamp = (value, min, max) => Math.max(min, Math.min(max, value));

  function enhanceQuantity(form) {
    const quantity = form.querySelector('.quantity');
    const input = quantity?.querySelector('input.qty');
    if (!quantity || !input || quantity.closest('.product-quantity-picker')) return;

    const min = Number.parseInt(input.min || '1', 10) || 1;
    const rawMax = Number.parseInt(input.max || '', 10);
    const max = Number.isFinite(rawMax) && rawMax > 0 ? rawMax : Number.MAX_SAFE_INTEGER;

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

    const setValue = next => {
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
  }

  function moveActions(form, target) {
    const summary = form.closest('.product-summary');
    const sourceActions = summary?.querySelector(':scope > .product-summary__actions');
    const submit = form.querySelector('.single_add_to_cart_button');
    const returnLink = sourceActions?.querySelector('a.button');
    if (!summary || !submit || !returnLink || !target) return;

    sourceActions.classList.add('yabao-wc-actions');
    target.append(sourceActions);
    sourceActions.prepend(submit);
    submit.textContent = 'Добавить в корзину';
  }

  function enhanceSimpleForm(form) {
    if (form.dataset.yabaoParityReady === 'true') return;
    form.dataset.yabaoParityReady = 'true';
    form.classList.add('yabao-product-form', 'yabao-simple-form');
    enhanceQuantity(form);
    moveActions(form, form);
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
    facts.before(fieldset);
    select.addEventListener('change', syncButtons);

    const quantity = variationButton.querySelector('.quantity input.qty');
    if (quantity) quantity.value = '1';
    moveActions(form, variationButton);

    const topPrice = summary.querySelector('.product-summary__price');
    if (window.jQuery && topPrice) {
      window.jQuery(form).on('found_variation.yabaoParity', function (_event, variation) {
        if (variation?.price_html) topPrice.innerHTML = variation.price_html;
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

  if (document.readyState === 'complete') init();
  else window.addEventListener('load', init, { once: true });
}());
