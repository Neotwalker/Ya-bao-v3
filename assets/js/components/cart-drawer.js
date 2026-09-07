import {
  getCartWeightSummary,
  getCartState,
  getCartTotal,
  removeCartItem,
  updateCartItemQuantity,
} from '../store.js';
import { lockBody, unlockBody } from '../utils/body-lock.js';
import { getFocusable, trapTab } from '../utils/focus.js';

const SITE_ROOT_URL = new URL('../../../', import.meta.url);
const CART_URL = new URL('cart/', SITE_ROOT_URL).href;
const CHECKOUT_URL = new URL('checkout/', SITE_ROOT_URL).href;
const SHOP_URL = new URL('shop/', SITE_ROOT_URL).href;

const money = value => `${new Intl.NumberFormat('ru-RU').format(value)} ₽`;
const number = value => new Intl.NumberFormat('ru-RU').format(value);
const escapeHTML = value => String(value ?? '').replace(/[&<>\'\"]/g, char => ({
  '&': '&amp;',
  '<': '&lt;',
  '>': '&gt;',
  "'": '&#39;',
  '"': '&quot;',
})[char]);

const productHref = item => item.slug
  ? new URL(`shop/${encodeURIComponent(item.slug)}/`, SITE_ROOT_URL).href
  : SHOP_URL;

const itemVariantText = item => {
  if (item.saleMode !== 'weight') return 'Поштучно';
  const summary = getCartWeightSummary(item);
  if (!summary) return item.variantLabel || 'Весовой вариант';
  return `${number(summary.unitWeight)} г × ${summary.quantity} = ${number(summary.totalWeight)} г`;
};

const itemMarkup = item => {
  const media = item.image
    ? `<img alt="${escapeHTML(item.name || 'Товар')}" decoding="async" loading="lazy" src="${escapeHTML(item.image)}">`
    : '<span class="mini-cart-item__placeholder" aria-hidden="true">茶</span>';
  const max = Number.isInteger(item.maxQuantity) && item.maxQuantity > 0 ? item.maxQuantity : null;
  const minusDisabled = item.quantity <= 1;
  const plusDisabled = max !== null && item.quantity >= max;
  const lineTotal = item.unitPrice * item.quantity;

  return `
    <article class="mini-cart-item" data-mini-cart-item data-cart-key="${escapeHTML(item.key)}">
      <a class="mini-cart-item__media" href="${escapeHTML(productHref(item))}">${media}</a>
      <div class="mini-cart-item__body">
        <div class="mini-cart-item__heading">
          <h3><a href="${escapeHTML(productHref(item))}">${escapeHTML(item.name || 'Товар')}</a></h3>
          <button class="mini-cart-item__remove" aria-label="Удалить ${escapeHTML(item.name || 'товар')} из корзины" data-mini-cart-remove data-cart-key="${escapeHTML(item.key)}" type="button">×</button>
        </div>
        <p class="mini-cart-item__variant">${escapeHTML(itemVariantText(item))}</p>
        <div class="mini-cart-item__bottom">
          <div class="mini-cart-qty" aria-label="Количество">
            <button aria-label="Уменьшить количество" data-mini-cart-minus data-cart-key="${escapeHTML(item.key)}" type="button"${minusDisabled ? ' disabled' : ''}>−</button>
            <span aria-live="polite">${item.quantity}</span>
            <button aria-label="Увеличить количество" data-mini-cart-plus data-cart-key="${escapeHTML(item.key)}" type="button"${plusDisabled ? ' disabled' : ''}>+</button>
          </div>
          <strong class="mini-cart-item__price">${escapeHTML(money(lineTotal))}</strong>
        </div>
      </div>
    </article>`;
};

export function initCartDrawer() {
  if (typeof document === 'undefined' || document.querySelector('[data-cart-drawer]')) return;

  const onCartPage = document.body.classList.contains('page-cart');
  const shell = document.createElement('div');
  shell.className = 'cart-drawer-shell';
  shell.innerHTML = `
    <div class="cart-drawer-backdrop" data-cart-drawer-backdrop aria-hidden="true"></div>
    <aside class="cart-drawer" data-cart-drawer aria-hidden="true" aria-labelledby="cart-drawer-title" role="dialog" aria-modal="true">
      <div class="cart-drawer__header">
        <div>
          <p class="eyebrow">Быстрый просмотр</p>
          <h2 id="cart-drawer-title">Корзина</h2>
        </div>
        <button class="cart-drawer__close" data-cart-drawer-close aria-label="Закрыть корзину" type="button">×</button>
      </div>
      <div class="cart-drawer__body">
        <div class="cart-drawer__empty" data-cart-drawer-empty hidden>
          <p>Корзина пока пуста.</p>
          <a class="button button--outline-walnut" href="${escapeHTML(SHOP_URL)}">Перейти в магазин</a>
        </div>
        <div class="mini-cart-list" data-cart-drawer-list></div>
      </div>
      <div class="cart-drawer__footer" data-cart-drawer-footer>
        <div class="cart-drawer__total"><span>Итого</span><strong data-cart-drawer-total>0 ₽</strong></div>
        <a class="button button--outline-walnut" href="${escapeHTML(CART_URL)}">Корзина</a>
        <a class="button button--walnut" href="${escapeHTML(CHECKOUT_URL)}">Оформить заказ</a>
        <button class="cart-drawer__continue" data-cart-drawer-close type="button">Продолжить покупки</button>
      </div>
    </aside>`;
  document.body.append(shell);

  const drawer = shell.querySelector('[data-cart-drawer]');
  const backdrop = shell.querySelector('[data-cart-drawer-backdrop]');
  const list = shell.querySelector('[data-cart-drawer-list]');
  const empty = shell.querySelector('[data-cart-drawer-empty]');
  const footer = shell.querySelector('[data-cart-drawer-footer]');
  const total = shell.querySelector('[data-cart-drawer-total]');
  let lastTrigger = null;
  let open = false;

  const render = () => {
    const state = getCartState();
    const items = state.items;
    const isEmpty = items.length === 0;
    list.innerHTML = items.map(itemMarkup).join('');
    empty.hidden = !isEmpty;
    list.hidden = isEmpty;
    footer.hidden = isEmpty;
    total.textContent = money(getCartTotal());
  };

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
    render();
    open = true;
    lastTrigger = trigger || document.activeElement;
    drawer.classList.add('is-open');
    backdrop.classList.add('is-open');
    drawer.setAttribute('aria-hidden', 'false');
    backdrop.setAttribute('aria-hidden', 'false');
    lockBody(drawer);
    window.requestAnimationFrame(() => drawer.querySelector('[data-cart-drawer-close]')?.focus());
  };

  document.querySelectorAll('[data-cart-indicator]').forEach(indicator => {
    indicator.addEventListener('click', event => {
      if (onCartPage || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
      event.preventDefault();
      show(indicator);
    });
  });

  backdrop.addEventListener('click', close);
  shell.addEventListener('click', event => {
    if (event.target.closest('[data-cart-drawer-close]')) {
      close();
      return;
    }

    const remove = event.target.closest('[data-mini-cart-remove]');
    if (remove) {
      removeCartItem(remove.dataset.cartKey || '');
      return;
    }

    const step = event.target.closest('[data-mini-cart-minus], [data-mini-cart-plus]');
    if (!step) return;
    const key = step.dataset.cartKey || '';
    const item = getCartState().items.find(entry => entry.key === key);
    if (!item) return;
    const delta = step.matches('[data-mini-cart-plus]') ? 1 : -1;
    updateCartItemQuantity(key, item.quantity + delta);
  });

  drawer.addEventListener('keydown', event => {
    if (event.key === 'Escape') {
      event.preventDefault();
      close();
      return;
    }
    trapTab(event, getFocusable(drawer));
  });

  document.addEventListener('shop:cartchange', render);
  render();
}
