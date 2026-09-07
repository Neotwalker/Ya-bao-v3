const form = document.querySelector('.woocommerce-cart-form');

if (form) {
  let timer = 0;
  let busy = false;
  let pending = false;

  const clamp = (value, min, max) => Math.max(min, Math.min(max, value));

  function normalize(input, raw) {
    const min = Number(input.min || 0);
    const max = input.max ? Number(input.max) : Infinity;
    const parsed = Number(raw);
    const value = Number.isFinite(parsed) ? parsed : min;
    return clamp(value, min, max);
  }

  function syncButtons(control, input) {
    const value = normalize(input, input.value);
    const min = Number(input.min || 0);
    const max = input.max ? Number(input.max) : Infinity;
    const minus = control.querySelector('[data-wc-qty-minus]');
    const plus = control.querySelector('[data-wc-qty-plus]');
    if (minus) minus.disabled = value <= min;
    if (plus) plus.disabled = value >= max;
  }

  function syncGlobalFromDocument(nextDocument) {
    const nextCount = nextDocument.querySelector('[data-cart-count]')?.textContent?.trim();
    if (nextCount !== undefined && nextCount !== null && nextCount !== '') {
      document.querySelectorAll('[data-cart-count]').forEach(el => { el.textContent = nextCount; });
      document.querySelectorAll('[data-cart-indicator]').forEach(el => {
        el.setAttribute('aria-label', `Корзина: ${nextCount} товаров`);
      });
    }

    const nextMiniCart = nextDocument.querySelector('[data-wc-mini-cart-content]');
    if (nextMiniCart) {
      document.querySelectorAll('[data-wc-mini-cart-content]').forEach(el => {
        el.innerHTML = nextMiniCart.innerHTML;
      });
    }

    if (window.jQuery) {
      window.jQuery(document.body).trigger('wc_fragments_refreshed');
    }
    document.body.dispatchEvent(new CustomEvent('yabao:cartupdated'));
  }

  function syncAllControls() {
    form.querySelectorAll('[data-wc-cart-quantity]').forEach(control => {
      const input = control.querySelector('input.qty');
      if (input) syncButtons(control, input);
    });
  }

  async function updateCart() {
    if (busy) {
      pending = true;
      return;
    }

    busy = true;
    pending = false;
    form.setAttribute('aria-busy', 'true');

    const data = new FormData(form);
    data.set('update_cart', '1');

    try {
      const response = await fetch(form.action || window.location.href, {
        method: 'POST',
        credentials: 'same-origin',
        body: data,
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        redirect: 'follow',
      });
      const html = await response.text();
      if (!response.ok) throw new Error('Не удалось обновить корзину.');

      const nextDocument = new DOMParser().parseFromString(html, 'text/html');
      const nextForm = nextDocument.querySelector('.woocommerce-cart-form');
      const currentNotices = document.querySelector('.woocommerce-notices-wrapper');
      const nextNotices = nextDocument.querySelector('.woocommerce-notices-wrapper');

      // Quantity changes are already visible in the line totals and cart summary.
      // Keep genuine WooCommerce errors/info, but drop the redundant "cart updated" success message.
      if (currentNotices) {
        if (nextNotices) {
          const filteredNotices = nextNotices.cloneNode(true);
          filteredNotices.querySelectorAll('.woocommerce-message').forEach(notice => notice.remove());
          currentNotices.innerHTML = filteredNotices.innerHTML;
        } else {
          currentNotices.innerHTML = '';
        }
      }
      syncGlobalFromDocument(nextDocument);

      if (nextForm) {
        form.innerHTML = nextForm.innerHTML;
        syncAllControls();
      } else {
        const currentPage = document.querySelector('.cart-page');
        const nextPage = nextDocument.querySelector('.cart-page');
        if (currentPage && nextPage) currentPage.replaceWith(nextPage);
      }
    } catch (error) {
      console.error(error);
      const notices = document.querySelector('.woocommerce-notices-wrapper');
      if (notices) notices.innerHTML = '<ul class="woocommerce-error" role="alert"><li>Не удалось обновить корзину. Повторите попытку.</li></ul>';
    } finally {
      busy = false;
      form.removeAttribute('aria-busy');
      if (pending) {
        window.clearTimeout(timer);
        timer = window.setTimeout(updateCart, 0);
      }
    }
  }

  function schedule(input, value, delay) {
    const control = input.closest('[data-wc-cart-quantity]');
    if (!control) return;
    input.value = String(normalize(input, value));
    syncButtons(control, input);
    window.clearTimeout(timer);
    timer = window.setTimeout(updateCart, delay);
  }

  syncAllControls();

  form.addEventListener('click', event => {
    const button = event.target.closest('[data-wc-qty-minus], [data-wc-qty-plus]');
    if (!button) return;
    event.stopImmediatePropagation();
    const control = button.closest('[data-wc-cart-quantity]');
    const input = control?.querySelector('input.qty');
    if (!input) return;

    const step = Number(input.step || 1);
    const current = normalize(input, input.value);
    const next = current + (button.matches('[data-wc-qty-plus]') ? step : -step);
    schedule(input, next, 180);
  }, true);

  form.addEventListener('input', event => {
    const input = event.target.closest('[data-wc-cart-quantity] input.qty');
    if (!input) return;
    event.stopImmediatePropagation();
    schedule(input, input.value, 420);
  }, true);

  form.addEventListener('change', event => {
    const input = event.target.closest('[data-wc-cart-quantity] input.qty');
    if (!input) return;
    event.stopImmediatePropagation();
    schedule(input, input.value, 0);
  }, true);

  form.addEventListener('keydown', event => {
    const input = event.target.closest('[data-wc-cart-quantity] input.qty');
    if (!input || event.key !== 'Enter') return;
    event.stopImmediatePropagation();
    event.preventDefault();
    schedule(input, input.value, 0);
  }, true);

  form.addEventListener('submit', event => {
    const submitter = event.submitter;
    if (!submitter || submitter.name !== 'update_cart') return;
    event.stopImmediatePropagation();
    event.preventDefault();
    updateCart();
  }, true);
}
