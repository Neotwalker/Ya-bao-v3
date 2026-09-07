const STORAGE_KEY = 'yabao:cart:v1';
const STORE_VERSION = 1;

const safeParse = value => {
  try { return JSON.parse(value); } catch { return null; }
};

const asPositiveInt = value => {
  const parsed = Number.parseInt(value, 10);
  return Number.isInteger(parsed) && parsed > 0 ? parsed : 1;
};

const asPrice = value => {
  const parsed = Number(value);
  return Number.isFinite(parsed) && parsed >= 0 ? parsed : 0;
};

const makeKey = ({ productId, saleMode, variantId }) => (
  saleMode === 'weight' ? `${productId}::${variantId}` : productId
);

const normalizeItem = source => {
  if (!source || typeof source !== 'object') throw new TypeError('Cart item must be an object');
  const productId = String(source.productId || '').trim();
  const saleMode = source.saleMode === 'weight' ? 'weight' : source.saleMode === 'unit' ? 'unit' : '';
  const variantId = source.variantId ? String(source.variantId).trim() : '';
  if (!productId || !saleMode) throw new TypeError('Cart item requires productId and saleMode');
  if (saleMode === 'weight' && !variantId) throw new TypeError('Weight product requires variantId');

  const maxQuantityRaw = Number.parseInt(source.maxQuantity, 10);
  const maxQuantity = Number.isInteger(maxQuantityRaw) && maxQuantityRaw > 0 ? maxQuantityRaw : null;
  let quantity = asPositiveInt(source.quantity);
  if (maxQuantity) quantity = Math.min(quantity, maxQuantity);

  return {
    key: makeKey({ productId, saleMode, variantId }),
    productId,
    sku: String(source.sku || '').trim(),
    slug: String(source.slug || '').trim(),
    name: String(source.name || '').trim(),
    type: String(source.type || '').trim(),
    saleMode,
    variantId: saleMode === 'weight' ? variantId : null,
    variantLabel: saleMode === 'weight' ? String(source.variantLabel || '').trim() : null,
    quantity,
    unitPrice: asPrice(source.unitPrice),
    maxQuantity,
    image: String(source.image || '').trim(),
  };
};

const normalizeState = raw => {
  const items = Array.isArray(raw?.items) ? raw.items : [];
  const normalized = [];
  const seen = new Set();
  items.forEach(item => {
    try {
      const next = normalizeItem(item);
      if (seen.has(next.key)) return;
      seen.add(next.key);
      normalized.push(next);
    } catch {}
  });
  return { version: STORE_VERSION, items: normalized };
};

const readStoredState = () => {
  try { return normalizeState(safeParse(localStorage.getItem(STORAGE_KEY)) || {}); }
  catch { return { version: STORE_VERSION, items: [] }; }
};

let state = readStoredState();
const cloneState = () => ({ version: STORE_VERSION, items: state.items.map(item => ({ ...item })) });

export const getCartState = () => cloneState();
export const getCartCount = () => state.items.reduce((sum, item) => sum + item.quantity, 0);
export const getCartTotal = () => state.items.reduce((sum, item) => sum + (item.unitPrice * item.quantity), 0);

const pluralProducts = count => {
  const mod10 = count % 10;
  const mod100 = count % 100;
  if (mod10 === 1 && mod100 !== 11) return 'товар';
  if (mod10 >= 2 && mod10 <= 4 && (mod100 < 12 || mod100 > 14)) return 'товара';
  return 'товаров';
};

const syncIndicators = () => {
  const count = getCartCount();
  document.querySelectorAll('[data-cart-indicator]').forEach(indicator => {
    const counter = indicator.querySelector('[data-cart-count]');
    if (counter) counter.textContent = String(count);
    indicator.setAttribute('aria-label', `Корзина: ${count} ${pluralProducts(count)}`);
  });
};

const emitChange = reason => {
  const detail = { reason, state: cloneState(), count: getCartCount(), total: getCartTotal() };
  syncIndicators();
  document.dispatchEvent(new CustomEvent('shop:cartchange', { detail }));
};

const persist = reason => {
  try { localStorage.setItem(STORAGE_KEY, JSON.stringify(cloneState())); } catch {}
  emitChange(reason);
  return cloneState();
};

export const addCartItem = source => {
  const next = normalizeItem(source);
  const existing = state.items.find(item => item.key === next.key);
  if (existing) {
    const requested = existing.quantity + next.quantity;
    const effectiveMax = next.maxQuantity || existing.maxQuantity;
    existing.quantity = effectiveMax ? Math.min(requested, effectiveMax) : requested;
    existing.unitPrice = next.unitPrice;
    existing.name = next.name || existing.name;
    existing.sku = next.sku || existing.sku;
    existing.slug = next.slug || existing.slug;
    existing.type = next.type || existing.type;
    existing.variantLabel = next.variantLabel || existing.variantLabel;
    existing.image = next.image || existing.image;
    if (next.maxQuantity) existing.maxQuantity = next.maxQuantity;
  } else {
    state.items.push(next);
  }
  persist('add');
  return { ...(state.items.find(item => item.key === next.key) || next) };
};

export const updateCartItemQuantity = (key, quantity, options = {}) => {
  const item = state.items.find(entry => entry.key === key);
  if (!item) return null;
  const overrideMax = Number.parseInt(options?.maxQuantity, 10);
  if (Number.isInteger(overrideMax) && overrideMax > 0) item.maxQuantity = overrideMax;
  const parsed = Number.parseInt(quantity, 10);
  if (!Number.isFinite(parsed) || parsed <= 0) { removeCartItem(key); return null; }
  item.quantity = item.maxQuantity ? Math.min(parsed, item.maxQuantity) : parsed;
  persist('update');
  return { ...item };
};

export const removeCartItem = key => {
  const before = state.items.length;
  state.items = state.items.filter(item => item.key !== key);
  if (state.items.length === before) return false;
  persist('remove');
  return true;
};

export const clearCart = () => {
  state.items = [];
  return persist('clear');
};

export const initCartState = () => {
  state = readStoredState();
  syncIndicators();
  window.addEventListener('storage', event => {
    if (event.key !== STORAGE_KEY) return;
    state = readStoredState();
    emitChange('storage');
  });
};

export { STORAGE_KEY };
