(function ($) {
  'use strict';

  const addressFields = [
    'billing_address_1_field',
    'billing_address_2_field',
    'billing_city_field',
    'billing_state_field',
    'billing_postcode_field'
  ];

  const requiredInputs = [
    'billing_address_1',
    'billing_city',
    'billing_postcode'
  ];

  const checkoutUpdateSelector = [
    '#billing_country',
    '#billing_state',
    '#billing_postcode',
    '#billing_city',
    '#billing_address_1',
    '#billing_address_2'
  ].join(', ');

  let addressUpdateTimer = null;
  let carrierIntent = '';

  function selectedMethod() {
    const checked = document.querySelector('input.shipping_method:checked');
    return checked ? String(checked.value || '') : '';
  }

  function isPickup(method) {
    return method.indexOf('yabao_pickup') === 0 ||
      method.indexOf('local_pickup') !== -1;
  }

  function providerFromMethod(method) {
    if (method.indexOf('wpapiship_shipping:') === 0) {
      const parts = method.split(':');
      return parts[2] ? String(parts[2]).toLowerCase() : '';
    }

    if (method === 'yabao_delivery_cdek') {
      return 'cdek';
    }

    if (method === 'yabao_delivery_5post') {
      return 'x5';
    }

    return '';
  }

  function manualMethodForProvider(provider) {
    if (provider === 'cdek') {
      return 'yabao_delivery_cdek';
    }

    if (provider === 'x5') {
      return 'yabao_delivery_5post';
    }

    return '';
  }

  function rememberCarrierIntent() {
    const provider = providerFromMethod(selectedMethod());

    if (provider) {
      carrierIntent = provider;
    }
  }

  function restoreCarrierIntent() {
    const manualMethod = manualMethodForProvider(carrierIntent);

    if (!manualMethod) {
      return;
    }

    const input = [...document.querySelectorAll('input.shipping_method')]
      .find(item => String(item.value || '') === manualMethod);

    if (!input || input.checked) {
      return;
    }

    input.checked = true;
    input.dispatchEvent(new Event('change', { bubbles: true }));
  }

  function normalizeOptionalStateField() {
    const row = document.getElementById('billing_state_field');
    const input = document.getElementById('billing_state');

    if (row) {
      row.classList.remove('validate-required');
      row.querySelectorAll('.required').forEach(marker => marker.remove());
    }

    if (input) {
      input.required = false;
      input.setAttribute('aria-required', 'false');
    }
  }

  function syncAddressFields() {
    const method = selectedMethod();
    const pickup = method ? isPickup(method) : true;

    addressFields.forEach(id => {
      const row = document.getElementById(id);
      if (!row) return;

      row.classList.toggle('yabao-checkout-field--hidden', pickup);
      row.setAttribute('aria-hidden', pickup ? 'true' : 'false');
    });

    requiredInputs.forEach(id => {
      const input = document.getElementById(id);
      if (!input) return;

      input.required = !pickup;
      input.setAttribute('aria-required', pickup ? 'false' : 'true');
    });

    normalizeOptionalStateField();
  }

  function queueCheckoutUpdate() {
    rememberCarrierIntent();

    window.clearTimeout(addressUpdateTimer);

    addressUpdateTimer = window.setTimeout(() => {
      $(document.body).trigger('update_checkout');
    }, 500);
  }

  document.addEventListener('change', event => {
    if (event.target && event.target.matches('input.shipping_method')) {
      rememberCarrierIntent();
      syncAddressFields();
      return;
    }

    if (event.target && event.target.matches(checkoutUpdateSelector)) {
      queueCheckoutUpdate();
    }
  });

  document.addEventListener('input', event => {
    if (event.target && event.target.matches(checkoutUpdateSelector)) {
      queueCheckoutUpdate();
    }
  });

  $(document.body).on('updated_checkout', () => {
    restoreCarrierIntent();
    syncAddressFields();
    rememberCarrierIntent();
  });

  rememberCarrierIntent();
  syncAddressFields();
})(jQuery);
