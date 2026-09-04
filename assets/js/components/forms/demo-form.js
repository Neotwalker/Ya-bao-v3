import {
  validateForm,
  setFieldError,
  wireFormErrors,
} from './validation.js';
import { syncCustomSelects } from './custom-select.js';
import { syncDateTimeFields } from './date-time.js';

function syncFormControls(form) {
  syncCustomSelects(form);
  syncDateTimeFields(form);
}

function getStatus(form) {
  return form.querySelector('[data-form-status]');
}

function setStatus(status, text, state = '') {
  if (!status) return;
  status.textContent = text;
  status.className = `form-status${state ? ` ${state}` : ''}`;
  status.setAttribute('aria-live', state === 'is-error' ? 'assertive' : 'polite');
}

function clearStatus(form) {
  const status = getStatus(form);
  if (!status) return;
  setStatus(status, '');
}

function clearErrors(form) {
  form.querySelectorAll('input, select, textarea').forEach(field => {
    if (field.type === 'hidden') return;
    setFieldError(field, '');
  });
}

function restoreSubmit(form) {
  const submit = form.querySelector('[type="submit"]');
  submit?.classList.remove('is-loading');
  submit?.removeAttribute('disabled');
}

function resetVisualState(form, { keepStatus = false } = {}) {
  clearErrors(form);
  syncFormControls(form);
  restoreSubmit(form);
  if (!keepStatus) clearStatus(form);
}

function focusInvalid(field) {
  if (!field) return;

  if (field.matches('select.custom-select__native')) {
    field.nextElementSibling?.querySelector('.custom-select__trigger')?.focus();
    return;
  }

  field.focus();
}

function initDemoForm(form) {
  if (form.dataset.demoFormReady === 'true') return;
  form.dataset.demoFormReady = 'true';
  wireFormErrors(form);

  form.addEventListener('submit', async event => {
    event.preventDefault();

    const status = getStatus(form);
    const validation = validateForm(form);

    if (!validation.valid) {
      setStatus(status, 'Проверьте выделенные поля.', 'is-error');
      focusInvalid(validation.firstInvalid);
      return;
    }

    const submit = form.querySelector('[type="submit"]');
    submit?.classList.add('is-loading');
    submit?.setAttribute('disabled', '');
    setStatus(status, 'Отправляем заявку…');

    await new Promise(resolve => setTimeout(resolve, 700));

    setStatus(
      status,
      'Демо-режим: форма проверена, но отправка пока не подключена.',
      'is-success',
    );

    form.dataset.keepStatusOnReset = 'true';
    form.reset();
    restoreSubmit(form);
  });

  form.addEventListener('reset', () => {
    const keepStatus = form.dataset.keepStatusOnReset === 'true';
    delete form.dataset.keepStatusOnReset;

    requestAnimationFrame(() => {
      resetVisualState(form, { keepStatus });
    });
  });

  form.querySelectorAll('input, select, textarea').forEach(field => {
    field.addEventListener('input', () => {
      setFieldError(field, '');

      const status = getStatus(form);
      if (status?.classList.contains('is-error')) {
        clearStatus(form);
      }
    });
  });
}

export function initDemoForms(root = document) {
  root.querySelectorAll('[data-demo-form]').forEach(initDemoForm);
}
