const STORAGE_KEY = 'yabao:payment-demo:v1';
const VERSION = 1;
const VALID_STATUSES = new Set(['pending', 'success', 'failed']);

const safeParse = value => {
  try { return JSON.parse(value); } catch { return null; }
};

const asAmount = value => {
  const parsed = Number(value);
  return Number.isFinite(parsed) && parsed >= 0 ? parsed : 0;
};

const asCount = value => {
  const parsed = Number.parseInt(value, 10);
  return Number.isInteger(parsed) && parsed >= 0 ? parsed : 0;
};

const normalize = source => {
  if (!source || typeof source !== 'object' || source.version !== VERSION) return null;
  const status = VALID_STATUSES.has(source.status) ? source.status : '';
  const reference = String(source.reference || '').trim();
  if (!status || !reference) return null;

  return {
    version: VERSION,
    reference,
    status,
    amount: asAmount(source.amount),
    currency: 'RUB',
    itemCount: asCount(source.itemCount),
    fulfillment: source.fulfillment === 'delivery' ? 'delivery' : 'pickup',
    createdAt: String(source.createdAt || ''),
    updatedAt: String(source.updatedAt || ''),
  };
};

const readStored = () => {
  try { return normalize(safeParse(sessionStorage.getItem(STORAGE_KEY))); }
  catch { return null; }
};

let state = readStored();
const clone = value => value ? { ...value } : null;

const persist = () => {
  try {
    if (state) sessionStorage.setItem(STORAGE_KEY, JSON.stringify(state));
    else sessionStorage.removeItem(STORAGE_KEY);
  } catch {}
  return clone(state);
};

const createReference = () => {
  const stamp = Date.now().toString(36).toUpperCase();
  const suffix = Math.floor(Math.random() * 36 ** 3).toString(36).toUpperCase().padStart(3, '0');
  return `DEMO-${stamp}-${suffix}`;
};

export const createDemoPayment = ({ amount, itemCount, fulfillment }) => {
  const now = new Date().toISOString();
  state = {
    version: VERSION,
    reference: createReference(),
    status: 'pending',
    amount: asAmount(amount),
    currency: 'RUB',
    itemCount: asCount(itemCount),
    fulfillment: fulfillment === 'delivery' ? 'delivery' : 'pickup',
    createdAt: now,
    updatedAt: now,
  };
  return persist();
};

export const getDemoPayment = () => clone(state);

export const setDemoPaymentStatus = status => {
  if (!state || state.status !== 'pending' || !['success', 'failed'].includes(status)) return null;
  state.status = status;
  state.updatedAt = new Date().toISOString();
  return persist();
};

export const clearDemoPayment = () => {
  state = null;
  persist();
};

export { STORAGE_KEY as DEMO_PAYMENT_STORAGE_KEY };
