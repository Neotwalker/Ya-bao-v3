import {
  validateForm,
  setFieldError,
  wireFormErrors,
} from './validation.js';
import { syncCustomSelects } from './custom-select.js';
import { syncDateTimeFields } from './date-time.js';

const FEEDBACK_HIDE_MS = 2600;
const FEEDBACK_TRANSITION_MS = 320;

function syncFormControls(form) {
  syncCustomSelects(form);
  syncDateTimeFields(form);
}

function getStatus(form) {
  return form.querySelector('[data-form-status]');
}

function setStatus(status, text, state = '', { overlay = false } = {}) {
  if (!status) return;
  status.textContent = text;
  status.className = `form-status${state ? ` ${state}` : ''}${overlay ? ' is-overlay' : ''}`;
  status.setAttribute('aria-live', state === 'is-error' ? 'assertive' : 'polite');
}

function clearStatus(form) {
  const status = getStatus(form);
  if (!status) return;
  setStatus(status, '');
}

function clearFeedbackTimers(form) {
  window.clearTimeout(Number(form.dataset.feedbackTimer || 0));
  window.clearTimeout(Number(form.dataset.feedbackCleanupTimer || 0));
  delete form.dataset.feedbackTimer;
  delete form.dataset.feedbackCleanupTimer;
}

function showOverlayStatus(form, text, state = '') {
  const status = getStatus(form);
  if (!status) return;

  clearFeedbackTimers(form);
  const wasVisible = status.classList.contains('is-overlay')
    && status.classList.contains('is-visible');

  status.textContent = text;
  status.classList.add('is-overlay');
  status.classList.toggle('is-success', state === 'is-success');
  status.classList.toggle('is-error', state === 'is-error');
  status.setAttribute('aria-live', state === 'is-error' ? 'assertive' : 'polite');
  form.classList.add('has-form-feedback');

  if (wasVisible) return;

  status.classList.remove('is-visible');
  requestAnimationFrame(() => {
    requestAnimationFrame(() => status.classList.add('is-visible'));
  });
}

function hideOverlayStatus(form, { delay = 0 } = {}) {
  clearFeedbackTimers(form);

  const beginHide = () => {
    const status = getStatus(form);
    if (!status?.classList.contains('is-overlay')) return;

    status.classList.remove('is-visible');

    const cleanupTimer = window.setTimeout(() => {
      status.classList.remove('is-overlay', 'is-success', 'is-error');
      status.textContent = '';
      form.classList.remove('has-form-feedback');
      delete form.dataset.feedbackCleanupTimer;
    }, FEEDBACK_TRANSITION_MS);

    form.dataset.feedbackCleanupTimer = String(cleanupTimer);
  };

  if (!delay) {
    beginHide();
    return;
  }

  const timer = window.setTimeout(beginHide, delay);
  form.dataset.feedbackTimer = String(timer);
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

function resetVisualState(form) {
  clearFeedbackTimers(form);
  clearErrors(form);
  syncFormControls(form);
  restoreSubmit(form);
  clearStatus(form);
  form.classList.remove('has-form-feedback');
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

    const validation = validateForm(form);

    if (!validation.valid) {
      showOverlayStatus(form, 'Проверьте выделенные поля.', 'is-error');
      window.setTimeout(() => {
        hideOverlayStatus(form);
        focusInvalid(validation.firstInvalid);
      }, 1800);
      return;
    }

    const submit = form.querySelector('[type="submit"]');
    submit?.classList.add('is-loading');
    submit?.setAttribute('disabled', '');
    showOverlayStatus(form, 'Отправляем заявку…');

    await new Promise(resolve => setTimeout(resolve, 700));

    showOverlayStatus(
      form,
      'Демо-режим: форма проверена, но отправка пока не подключена.',
      'is-success',
    );

    window.setTimeout(() => {
      form.reset();
      restoreSubmit(form);
      hideOverlayStatus(form);
    }, FEEDBACK_HIDE_MS);
  });

  form.addEventListener('reset', () => {
    requestAnimationFrame(() => {
      clearErrors(form);
      syncFormControls(form);
    });
  });

  form.querySelectorAll('input, select, textarea').forEach(field => {
    field.addEventListener('input', () => {
      setFieldError(field, '');
    });
  });
}

export function initDemoForms(root = document) {
  root.querySelectorAll('[data-demo-form]').forEach(initDemoForm);
}
