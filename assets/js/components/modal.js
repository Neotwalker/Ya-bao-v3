let lastFocused = null;

export function initModal() {
  const dialog = document.querySelector('#booking-modal');
  if (!dialog) return;
  const closeButton = dialog.querySelector('[data-modal-close]');

  document.querySelectorAll('[data-modal-open]').forEach(button => {
    button.addEventListener('click', () => {
      lastFocused = button;
      const ceremony = button.dataset.ceremony || '';
      const room = button.dataset.room || '';
      const eventName = button.dataset.event || '';
      const source = button.dataset.source || location.pathname;
      const ceremonySelect = dialog.querySelector('[name="ceremony"]');
      const roomSelect = dialog.querySelector('[name="room"]');
      const comment = dialog.querySelector('[name="comment"]');
      const sourceInput = dialog.querySelector('[name="source"]');

      if (ceremonySelect && ceremony) ceremonySelect.value = ceremony;
      if (roomSelect && room) roomSelect.value = room;
      if (comment && eventName) comment.value = `Запись на событие: ${eventName}`;
      if (sourceInput) sourceInput.value = source;

      dialog.showModal();
      document.body.classList.add('is-locked');
      dialog.querySelector('input, select, textarea, button')?.focus();
    });
  });

  const close = () => {
    dialog.close();
    document.body.classList.remove('is-locked');
    lastFocused?.focus();
  };
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
