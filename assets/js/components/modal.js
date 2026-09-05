import { lockBody, unlockBody } from '../utils/body-lock.js';

let lastFocused = null;

export function initModal() {
  const dialog = document.querySelector('#booking-modal');
  if (!dialog || dialog.dataset.modalReady === 'true') return;
  dialog.dataset.modalReady = 'true';

  const closeButton = dialog.querySelector('[data-modal-close]');
  const form = dialog.querySelector('form');
  const title = dialog.querySelector('[data-modal-title]');
  const intro = dialog.querySelector('[data-modal-intro]');
  const eyebrow = dialog.querySelector('[data-modal-eyebrow]');
  const generalFields = [...dialog.querySelectorAll('[data-general-field]')];
  const ceremonySelect = dialog.querySelector('[name="ceremony"]');
  const guestsField = dialog.querySelector('[name="guests"]')?.closest('.field');
  const sourceInput = dialog.querySelector('[name="source"]');
  const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  let isClosing = false;

  const eventInputs = {
    name: dialog.querySelector('[name="event_name"]'),
    date: dialog.querySelector('[name="event_date"]'),
    time: dialog.querySelector('[name="event_time"]'),
    price: dialog.querySelector('[name="event_price"]'),
  };

  const setEventMode = (button) => {
    const eventName = button.dataset.event || '';
    const isEvent = Boolean(eventName);
    dialog.classList.toggle('modal--event', isEvent);
    guestsField?.classList.toggle('field--select-pair-single', isEvent);

    generalFields.forEach(field => {
      field.hidden = isEvent;
      field.querySelectorAll('input, select, textarea').forEach(control => {
        control.disabled = isEvent;
        control.dispatchEvent(new Event('change', { bubbles: true }));
      });
    });

    if (isEvent) {
      const date = button.dataset.eventDate || 'дата уточняется';
      const time = button.dataset.eventTime || 'время уточняется';
      const price = button.dataset.eventPrice || 'цена уточняется';
      if (title) title.textContent = 'Запись на событие';
      if (intro) intro.textContent = 'Оставьте контакты и количество гостей. Данные события уже добавлены в заявку.';
      if (eyebrow) eyebrow.textContent = 'Событие';
      if (eventInputs.name) eventInputs.name.value = eventName;
      if (eventInputs.date) eventInputs.date.value = date;
      if (eventInputs.time) eventInputs.time.value = time;
      if (eventInputs.price) eventInputs.price.value = price;
    } else {
      if (title) title.textContent = 'Расскажите, какой вечер вы хотите провести';
      if (intro) intro.textContent = 'Оставьте заявку - администратор уточнит свободное время, формат встречи и ответит на вопросы.';
      if (eyebrow) eyebrow.textContent = 'Бронирование';
      Object.values(eventInputs).forEach(input => {
        if (input) input.value = '';
      });
      const ceremony = button.dataset.ceremony || '';
      if (ceremonySelect && ceremony && ceremony !== 'event') {
        ceremonySelect.value = ceremony;
        ceremonySelect.dispatchEvent(new Event('change', { bubbles: true }));
      }
    }
  };

  const focusFirstField = () => {
    dialog.querySelector(
      'input:not([type="hidden"]):not([disabled]), textarea:not([disabled]), .custom-select__trigger:not([disabled]), button:not([disabled])'
    )?.focus();
  };

  const openDialog = () => {
    if (dialog.open) return;
    dialog.showModal();
    lockBody('booking-modal');
    dialog.classList.remove('is-closing');

    if (reduceMotion) {
      dialog.classList.add('is-visible');
      focusFirstField();
      return;
    }

    requestAnimationFrame(() => {
      requestAnimationFrame(() => {
        dialog.classList.add('is-visible');
        focusFirstField();
      });
    });
  };

  const finishClose = () => {
    dialog.classList.remove('is-visible', 'is-closing');
    if (dialog.open) dialog.close();
    unlockBody('booking-modal');
    form?.reset();
    isClosing = false;
    lastFocused?.focus();
  };

  const close = () => {
    if (!dialog.open || isClosing) return;

    if (reduceMotion) {
      finishClose();
      return;
    }

    isClosing = true;
    dialog.classList.remove('is-visible');
    dialog.classList.add('is-closing');

    const handleTransitionEnd = (event) => {
      if (event.target !== dialog || event.propertyName !== 'opacity') return;
      dialog.removeEventListener('transitionend', handleTransitionEnd);
      finishClose();
    };

    dialog.addEventListener('transitionend', handleTransitionEnd);
    window.setTimeout(() => {
      if (isClosing) {
        dialog.removeEventListener('transitionend', handleTransitionEnd);
        finishClose();
      }
    }, 420);
  };

  document.querySelectorAll('[data-modal-open]').forEach(trigger => {
    trigger.addEventListener('click', event => {
      event.preventDefault();
      lastFocused = trigger;
      setEventMode(trigger);
      if (sourceInput) sourceInput.value = trigger.dataset.source || location.pathname;
      openDialog();
    });
  });

  closeButton?.addEventListener('click', close);
  dialog.addEventListener('click', event => {
    const rect = dialog.getBoundingClientRect();
    const outside = event.clientX < rect.left || event.clientX > rect.right || event.clientY < rect.top || event.clientY > rect.bottom;
    if (outside) close();
  });
  dialog.addEventListener('cancel', event => {
    event.preventDefault();
    close();
  });
}
