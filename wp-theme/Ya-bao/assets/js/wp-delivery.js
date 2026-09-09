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

  function selectedMethod() {
    const checked = document.querySelector('input.shipping_method:checked');
    return checked ? String(checked.value || '') : '';
  }

  function isPickup(method) {
    return method.indexOf('yabao_pickup') === 0 || method.indexOf('local_pickup') !== -1;
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
  }

  document.addEventListener('change', event => {
    if (event.target && event.target.matches('input.shipping_method')) {
      syncAddressFields();
    }
  });

  $(document.body).on('updated_checkout', syncAddressFields);
  syncAddressFields();
})(jQuery);
