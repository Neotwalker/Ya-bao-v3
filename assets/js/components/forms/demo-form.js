import { validateForm, setFieldError } from './validation.js';
import { syncCustomSelects } from './custom-select.js';
import { syncDateTimeFields } from './date-time.js';

function syncFormControls(form) {
  syncCustomSelects(form);
  syncDateTimeFields(form);
}

function setStatus(status, text, state = '') {
  if (!status) return;
  status.textContent = text;
  status.className = `form-status${state ? ` ${state}` : ''}`;
}

function initDemoForm(form) {
  if (form.dataset.demoFormReady === 'true') return;
  form.dataset.demoFormReady = 'true';

  form.addEventListener('submit', async event => {
    event.preventDefault();

    const status = form.querySelector('[data-form-status]');
    if (!validateForm(form)) {
      setStatus(status, 'Проверьте выделенные поля.', 'is-error');
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

    form.reset();
    syncFormControls(form);
    submit?.classList.remove('is-loading');
    submit?.removeAttribute('disabled');
  });

  form.addEventListener('reset', () => {
    requestAnimationFrame(() => syncFormControls(form));
  });

  form.querySelectorAll('input, select, textarea').forEach(field => {
    field.addEventListener('input', () => setFieldError(field, ''));
  });
}

export function initDemoForms(root = document) {
  root.querySelectorAll('[data-demo-form]').forEach(initDemoForm);
}
