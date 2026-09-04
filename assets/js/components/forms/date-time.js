import { setFieldError } from './validation.js';

function toLocalDateValue(date) {
  const year = date.getFullYear();
  const month = String(date.getMonth() + 1).padStart(2, '0');
  const day = String(date.getDate()).padStart(2, '0');
  return `${year}-${month}-${day}`;
}

export function initDateTimeFields(root = document) {
  const today = toLocalDateValue(new Date());

  root.querySelectorAll('input[type="date"]').forEach(input => {
    if (input.dataset.dateReady === 'true') return;
    input.dataset.dateReady = 'true';

    if (!input.min) input.min = today;
    input.addEventListener('change', () => setFieldError(input, ''));
  });

  root.querySelectorAll('input[type="time"]').forEach(input => {
    if (input.dataset.timeReady === 'true') return;
    input.dataset.timeReady = 'true';

    if (!input.step) input.step = String(15 * 60);
    input.addEventListener('change', () => setFieldError(input, ''));
  });
}

export function syncDateTimeFields(form) {
  form.querySelectorAll('input[type="date"], input[type="time"]').forEach(input => {
    setFieldError(input, '');
  });
}
