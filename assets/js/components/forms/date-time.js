import { setFieldError } from './validation.js';

function toLocalDateValue(date) {
  const year = date.getFullYear();
  const month = String(date.getMonth() + 1).padStart(2, '0');
  const day = String(date.getDate()).padStart(2, '0');
  return `${year}-${month}-${day}`;
}

function formatDateValue(value) {
  const match = /^(\d{4})-(\d{2})-(\d{2})$/.exec(value);
  if (!match) return value;

  const [, year, month, day] = match;
  const date = new Date(Number(year), Number(month) - 1, Number(day));

  try {
    return new Intl.DateTimeFormat('ru-RU', {
      day: 'numeric',
      month: 'short',
      year: 'numeric',
    }).format(date);
  } catch {
    return `${day}.${month}.${year}`;
  }
}

function formatTimeValue(value) {
  return /^\d{2}:\d{2}/.test(value) ? value.slice(0, 5) : value;
}

function enhanceDateTimeField(input) {
  if (input.closest('.date-time-control')) return input.closest('.date-time-control');

  const wrapper = document.createElement('div');
  wrapper.className = 'date-time-control';
  input.before(wrapper);
  wrapper.append(input);

  const display = document.createElement('span');
  display.className = 'date-time-control__display';
  display.setAttribute('aria-hidden', 'true');
  wrapper.append(display);

  const placeholder = input.type === 'date' ? 'Выберите дату' : 'Выберите время';

  const sync = () => {
    const hasValue = Boolean(input.value);
    wrapper.classList.toggle('has-value', hasValue);
    display.classList.toggle('is-placeholder', !hasValue);
    display.textContent = hasValue
      ? (input.type === 'date' ? formatDateValue(input.value) : formatTimeValue(input.value))
      : placeholder;
  };

  input.addEventListener('input', sync);
  input.addEventListener('change', sync);
  input.addEventListener('blur', sync);
  sync();

  return wrapper;
}

export function initDateTimeFields(root = document) {
  const today = toLocalDateValue(new Date());

  root.querySelectorAll('input[type="date"]').forEach(input => {
    if (input.dataset.dateReady === 'true') return;
    input.dataset.dateReady = 'true';

    if (!input.min) input.min = today;
    enhanceDateTimeField(input);
    input.addEventListener('change', () => setFieldError(input, ''));
  });

  root.querySelectorAll('input[type="time"]').forEach(input => {
    if (input.dataset.timeReady === 'true') return;
    input.dataset.timeReady = 'true';

    if (!input.step) input.step = String(15 * 60);
    enhanceDateTimeField(input);
    input.addEventListener('change', () => setFieldError(input, ''));
  });
}

export function syncDateTimeFields(form) {
  form.querySelectorAll('input[type="date"], input[type="time"]').forEach(input => {
    setFieldError(input, '');
    input.dispatchEvent(new Event('input', { bubbles: true }));
  });
}
