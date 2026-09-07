import { getDemoPayment } from './payment-demo.js';
import { clearCart } from './store.js';

const root = typeof document !== 'undefined' ? document.querySelector('[data-order-result]') : null;

if (root) {
  const expectedStatus = root.dataset.orderResultStatus === 'failed' ? 'failed' : 'success';
  const payment = getDemoPayment();
  const details = root.querySelector('[data-order-result-details]');
  const fallback = root.querySelector('[data-order-result-fallback]');
  const reference = root.querySelector('[data-order-result-reference]');
  const amount = root.querySelector('[data-order-result-amount]');
  const count = root.querySelector('[data-order-result-count]');
  const fulfillment = root.querySelector('[data-order-result-fulfillment]');
  const note = root.querySelector('[data-order-result-note]');
  const validContext = Boolean(payment && payment.status === expectedStatus);

  const money = value => `${new Intl.NumberFormat('ru-RU').format(Number(value) || 0)} ₽`;
  const pluralProducts = value => {
    const number = Number(value) || 0;
    const mod10 = number % 10;
    const mod100 = number % 100;
    if (mod10 === 1 && mod100 !== 11) return 'товар';
    if (mod10 >= 2 && mod10 <= 4 && (mod100 < 12 || mod100 > 14)) return 'товара';
    return 'товаров';
  };

  if (!validContext) {
    if (details) details.hidden = true;
    if (note) note.hidden = true;
    if (fallback) fallback.hidden = false;
  } else {
    if (details) details.hidden = false;
    if (note) note.hidden = false;
    if (fallback) fallback.hidden = true;
    if (reference) reference.textContent = payment.reference;
    if (amount) amount.textContent = money(payment.amount);
    if (count) count.textContent = `${payment.itemCount} ${pluralProducts(payment.itemCount)}`;
    if (fulfillment) fulfillment.textContent = payment.fulfillment === 'delivery' ? 'Доставка' : 'Самовывоз';

    if (expectedStatus === 'success') {
      clearCart();
      if (note) note.textContent = 'Корзина очищена как после подтверждённой успешной оплаты. Деньги в демо-режиме не списывались.';
    } else if (note) {
      note.textContent = 'Корзина сохранена. Можно вернуться к оформлению и повторить демо-переход к оплате.';
    }
  }
}
