import { setFieldError } from './validation.js';

function toLocalDateValue(date) {
  const year = date.getFullYear();
  const month = String(date.getMonth() + 1).padStart(2, '0');
  const day = String(date.getDate()).padStart(2, '0');
  return `${year}-${month}-${day}`;
}

function enhanceDateTimeField(input) {
  if (input.closest('.date-time-control')) return input.closest('.date-time-control');

  const wrapper = document.createElement('div');
  wrapper.className = 'date-time-control';
  input.before(wrapper);
  wrapper.append(input);

  const placeholder = document.createElement('span');
  placeholder.className = 'date-time-control__placeholder';
  placeholder.setAttribute('aria-hidden', 'true');
  placeholder.textContent = input.type === 'date' ? 'Выберите дату' : 'Выберите время';
  wrapper.append(placeholder);

  const sync = () => wrapper.classList.toggle('has-value', Boolean(input.value));
  input.addEventListener('input', sync);
  input.addEventListener('change', sync);
  input.addEventListener('focus', () => wrapper.classList.add('is-focused'));
  input.addEventListener('blur', () => {
    wrapper.classList.remove('is-focused');
    sync();
  });
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
    const wrapper = input.closest('.date-time-control');
    wrapper?.classList.toggle('has-value', Boolean(input.value));
  });
}
