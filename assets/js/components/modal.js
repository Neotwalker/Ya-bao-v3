
let lastFocused = null;

export function initModal() {
  const dialog = document.querySelector('#booking-modal');
  if (!dialog) return;
  const closeButton = dialog.querySelector('[data-modal-close]');
  const form = dialog.querySelector('form');
  const title = dialog.querySelector('[data-modal-title]');
  const intro = dialog.querySelector('[data-modal-intro]');
  const eyebrow = dialog.querySelector('[data-modal-eyebrow]');
  const generalFields = [...dialog.querySelectorAll('[data-general-field]')];
  const eventField = dialog.querySelector('[data-event-field]');
  const eventSummary = dialog.querySelector('[name="event_summary"]');
  const ceremonySelect = dialog.querySelector('[name="ceremony"]');
  const sourceInput = dialog.querySelector('[name="source"]');
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
    generalFields.forEach(field => {
      field.hidden = isEvent;
      field.querySelectorAll('input, select, textarea').forEach(control => control.disabled = isEvent);
    });
    if (eventField) eventField.hidden = !isEvent;
    if (eventSummary) eventSummary.disabled = !isEvent;

    if (isEvent) {
      const date = button.dataset.eventDate || 'дата уточняется';
      const time = button.dataset.eventTime || 'время уточняется';
      const price = button.dataset.eventPrice || 'цена уточняется';
      if (title) title.textContent = 'Запись на событие';
      if (intro) intro.textContent = 'Оставьте контакты и количество гостей. Название события, дата, время и стоимость уже добавлены в заявку.';
      if (eyebrow) eyebrow.textContent = 'Событие';
      if (eventSummary) eventSummary.value = `${eventName}\n${date} · ${time}\nСтоимость: ${price}`;
      if (eventInputs.name) eventInputs.name.value = eventName;
      if (eventInputs.date) eventInputs.date.value = date;
      if (eventInputs.time) eventInputs.time.value = time;
      if (eventInputs.price) eventInputs.price.value = price;
    } else {
      if (title) title.textContent = 'Расскажите, какой вечер вы хотите провести';
      if (intro) intro.textContent = 'Оставьте заявку — администратор уточнит свободное время, формат встречи и ответит на вопросы.';
      if (eyebrow) eyebrow.textContent = 'Бронирование';
      if (eventSummary) eventSummary.value = '';
      Object.values(eventInputs).forEach(input => { if (input) input.value = ''; });
      const ceremony = button.dataset.ceremony || '';
      if (ceremonySelect && ceremony && ceremony !== 'event') ceremonySelect.value = ceremony;
    }
  };

  document.querySelectorAll('[data-modal-open]').forEach(button => {
    button.addEventListener('click', () => {
      lastFocused = button;
      setEventMode(button);
      if (sourceInput) sourceInput.value = button.dataset.source || location.pathname;
      dialog.showModal();
      document.body.classList.add('is-locked');
      dialog.querySelector('input:not([disabled]), select:not([disabled]), textarea:not([disabled]), button')?.focus();
    });
  });

  const close = () => {
    dialog.close();
    document.body.classList.remove('is-locked');
    form?.reset();
    lastFocused?.focus();
  };
  closeButton?.addEventListener('click', close);
  dialog.addEventListener('click', event => {
    const rect = dialog.getBoundingClientRect();
    const outside = event.clientX < rect.left || event.clientX > rect.right || event.clientY < rect.top || event.clientY > rect.bottom;
    if (outside) close();
  });
  dialog.addEventListener('cancel', event => { event.preventDefault(); close(); });
}
