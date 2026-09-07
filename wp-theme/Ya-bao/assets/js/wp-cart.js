const form = document.querySelector('.woocommerce-cart-form');
if (form) {
  let timer = 0;
  const submit = () => {
    const button = form.querySelector('[name="update_cart"]');
    if (button && typeof form.requestSubmit === 'function') form.requestSubmit(button);
    else form.submit();
  };
  form.addEventListener('click', event => {
    const button = event.target.closest('[data-wc-qty-minus], [data-wc-qty-plus]');
    if (!button) return;
    const control = button.closest('[data-wc-cart-quantity]');
    const input = control?.querySelector('input.qty');
    if (!input) return;
    const min = Number(input.min || 0);
    const max = input.max ? Number(input.max) : Infinity;
    const step = Number(input.step || 1);
    const current = Number(input.value || 0);
    const next = Math.max(min, Math.min(max, current + (button.matches('[data-wc-qty-plus]') ? step : -step)));
    input.value = String(next);
    input.dispatchEvent(new Event('change', { bubbles: true }));
    window.clearTimeout(timer);
    timer = window.setTimeout(submit, 350);
  });
}
