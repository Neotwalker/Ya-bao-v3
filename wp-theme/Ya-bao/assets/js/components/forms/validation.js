export const OPEN_START_MINUTES = 10 * 60;
export const OPEN_END_MINUTES = 60;

export function isOpenTime(value) {
  if (!/^\d{2}:\d{2}$/.test(value)) return false;

  const [hours, minutes] = value.split(':').map(Number);
  const total = hours * 60 + minutes;
  return total >= OPEN_START_MINUTES || total <= OPEN_END_MINUTES;
}

export function getFieldError(field) {
  return field.closest('.field, .checkbox')?.querySelector('.field__error') || null;
}

export function setFieldError(field, message) {
  field.setAttribute('aria-invalid', message ? 'true' : 'false');
  const error = getFieldError(field);
  if (error) error.textContent = message;
}

export function wireFieldError(field, index) {
  const error = getFieldError(field);
  if (!error) return;

  if (!error.id) {
    const base = field.id || field.name || `field-${index + 1}`;
    error.id = `${base}-error`;
  }

  const describedBy = new Set(
    String(field.getAttribute('aria-describedby') || '')
      .split(/\s+/)
      .filter(Boolean),
  );
  describedBy.add(error.id);
  field.setAttribute('aria-describedby', [...describedBy].join(' '));
}

export function wireFormErrors(form) {
  form.querySelectorAll('input, select, textarea').forEach((field, index) => {
    if (field.type === 'hidden') return;
    wireFieldError(field, index);
  });
}

export function validateForm(form) {
  let firstInvalid = null;

  form.querySelectorAll('[required]').forEach(field => {
    let message = '';

    if (field.type === 'checkbox' && !field.checked) {
      message = 'Подтвердите согласие.';
    } else if (!String(field.value).trim()) {
      message = 'Заполните поле.';
    } else if (field.type === 'tel' && field.value.replace(/\D/g, '').length < 11) {
      message = 'Введите полный номер телефона.';
    } else if (field.matches('input[name="time"]') && !isOpenTime(field.value)) {
      message = 'Выберите время с 10:00 до 01:00.';
    }

    setFieldError(field, message);
    if (message && !firstInvalid) firstInvalid = field;
  });

  return {
    valid: !firstInvalid,
    firstInvalid,
  };
}
