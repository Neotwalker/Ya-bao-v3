import { initHeader } from './components/header.js';
import { initModal } from './components/modal.js';
import { initForms } from './components/forms.js';
import { initAccordion } from './components/accordion.js';
import { initReveal } from './components/reveal.js';
import { runInitializers } from './utils/init.js';
import { lockBody, unlockBody } from './utils/body-lock.js';
import { getFocusable, trapTab } from './utils/focus.js';

document.documentElement.classList.add('js');

function initCurrentYear() {
  const year = String(new Date().getFullYear());
  document.querySelectorAll('[data-current-year]').forEach(el => { el.textContent = year; });
}

function initTopScroll() {
  const button = document.querySelector('[data-top-scroll]');
  if (!button || button.dataset.topScrollReady === 'true') return;
  button.dataset.topScrollReady = 'true';
  const sync = () => button.classList.toggle('is-visible', window.scrollY > 520);
  window.addEventListener('scroll', sync, { passive: true });
  sync();
  button.addEventListener('click', () => window.scrollTo({ top: 0, behavior: 'smooth' }));
}

function initWooCartDrawer() {
  const shell = document.querySelector('[data-wc-cart-drawer-shell]');
  const drawer = shell?.querySelector('[data-wc-cart-drawer]');
  const backdrop = shell?.querySelector('[data-wc-cart-drawer-backdrop]');
  if (!shell || !drawer || !backdrop) return;

  let open = false;
  let lastTrigger = null;

  const close = () => {
    if (!open) return;
    open = false;
    drawer.classList.remove('is-open');
    backdrop.classList.remove('is-open');
    drawer.setAttribute('aria-hidden', 'true');
    backdrop.setAttribute('aria-hidden', 'true');
    unlockBody(drawer);
    const target = lastTrigger;
    lastTrigger = null;
    window.setTimeout(() => target?.focus(), 20);
  };

  const show = trigger => {
    if (open) return;
    open = true;
    lastTrigger = trigger || document.activeElement;
    drawer.classList.add('is-open');
    backdrop.classList.add('is-open');
    drawer.setAttribute('aria-hidden', 'false');
    backdrop.setAttribute('aria-hidden', 'false');
    lockBody(drawer);
    window.requestAnimationFrame(() => drawer.querySelector('[data-wc-cart-drawer-close]')?.focus());
  };

  document.querySelectorAll('[data-cart-indicator]').forEach(indicator => {
    indicator.addEventListener('click', event => {
      if (document.body.classList.contains('page-cart') || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
      event.preventDefault();
      show(indicator);
    });
  });

  backdrop.addEventListener('click', close);
  shell.addEventListener('click', async event => {
    if (event.target.closest('[data-wc-cart-drawer-close]')) {
      close();
      return;
    }

    const step = event.target.closest('[data-mini-cart-minus], [data-mini-cart-plus]');
    if (!step || !window.yabaoWoo?.ajaxUrl) return;
    const item = step.closest('[data-mini-cart-item]');
    const key = item?.dataset.cartKey || '';
    const current = Number(item?.querySelector('.mini-cart-qty span')?.textContent || 1);
    const quantity = Math.max(0, current + (step.matches('[data-mini-cart-plus]') ? 1 : -1));
    step.disabled = true;

    const body = new URLSearchParams({
      action: 'yabao_update_mini_cart_quantity',
      nonce: window.yabaoWoo.nonce,
      cart_item_key: key,
      quantity: String(quantity),
    });

    try {
      const response = await fetch(window.yabaoWoo.ajaxUrl, {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
        body,
      });
      const json = await response.json();
      if (!json?.success) throw new Error(json?.data?.message || 'Cart update failed');
      const content = shell.querySelector('[data-wc-mini-cart-content]');
      if (content) content.innerHTML = json.data.miniCart;
      document.querySelectorAll('[data-cart-count]').forEach(el => { el.textContent = String(json.data.count); });
      document.querySelectorAll('[data-cart-indicator]').forEach(el => el.setAttribute('aria-label', `Корзина: ${json.data.count} товаров`));
      document.body.dispatchEvent(new CustomEvent('yabao:cartupdated', { detail: json.data }));
    } catch (error) {
      console.error(error);
      step.disabled = false;
    }
  });

  drawer.addEventListener('keydown', event => {
    if (event.key === 'Escape') {
      event.preventDefault();
      close();
      return;
    }
    trapTab(event, getFocusable(drawer));
  });

  if (window.jQuery) {
    window.jQuery(document.body).on('added_to_cart removed_from_cart wc_fragments_refreshed', () => {
      if (open) drawer.querySelector('[data-wc-cart-drawer-close]')?.focus({ preventScroll: true });
    });
  }
}

runInitializers(initHeader, initModal, initForms, initAccordion, initReveal, initCurrentYear, initTopScroll, initWooCartDrawer);
