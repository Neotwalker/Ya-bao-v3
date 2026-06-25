function formatPhone(value) {
  const digits = value.replace(/\D/g, '').replace(/^8/, '7').slice(0, 11);
  const normalized = digits.startsWith('7') ? digits : `7${digits}`;
  const parts = [
    normalized.slice(1, 4),
    normalized.slice(4, 7),
    normalized.slice(7, 9),
    normalized.slice(9, 11),
  ];
  let result = '+7';
  if (parts[0]) result += ` (${parts[0]}`;
  if (parts[0]?.length === 3) result += ')';
  if (parts[1]) result += ` ${parts[1]}`;
  if (parts[2]) result += `-${parts[2]}`;
  if (parts[3]) result += `-${parts[3]}`;
  return result;
}

export function initModal() {
  const modal = document.getElementById('booking-modal');
  if (!(modal instanceof HTMLDialogElement)) return;

  let opener = null;
  const openers = document.querySelectorAll('[data-modal-open="booking-modal"]');
  const closer = modal.querySelector('[data-modal-close]');
  const form = modal.querySelector('[data-booking-form]');
  const phoneInput = modal.querySelector('[data-phone]');
  const status = modal.querySelector('[data-form-status]');

  const open = (button) => {
    opener = button;
    modal.showModal();
    document.body.classList.add('is-modal-open');
    requestAnimationFrame(() => modal.querySelector('input')?.focus());
  };

  const close = () => {
    modal.close();
    document.body.classList.remove('is-modal-open');
    opener?.focus();
  };

  openers.forEach((button) => button.addEventListener('click', () => open(button)));
  closer?.addEventListener('click', close);
  modal.addEventListener('click', (event) => {
    if (event.target === modal) close();
  });
  modal.addEventListener('cancel', (event) => {
    event.preventDefault();
    close();
  });

  phoneInput?.addEventListener('input', () => {
    phoneInput.value = formatPhone(phoneInput.value);
  });

  form?.addEventListener('submit', (event) => {
    event.preventDefault();
    status.textContent = '';
    const formData = new FormData(form);
    const name = String(formData.get('name') || '').trim();
    const phone = String(formData.get('phone') || '').replace(/\D/g, '');
    const consent = formData.get('consent');

    const errors = {
      name: name.length < 2 ? 'Укажите имя.' : '',
      phone: phone.length !== 11 ? 'Введите номер полностью.' : '',
      consent: !consent ? 'Необходимо согласие на обработку данных.' : '',
    };

    Object.entries(errors).forEach(([fieldName, message]) => {
      const errorNode = form.querySelector(`[data-error-for="${fieldName}"]`);
      if (errorNode) errorNode.textContent = message;
      const field = form.querySelector(`[name="${fieldName}"]`)?.closest('.field');
      field?.classList.toggle('is-invalid', Boolean(message));
    });

    if (Object.values(errors).some(Boolean)) {
      form.querySelector('.is-invalid input')?.focus();
      return;
    }

    const submit = form.querySelector('button[type="submit"]');
    submit.disabled = true;
    submit.textContent = 'Отправляем…';
    window.setTimeout(() => {
      status.textContent = 'Заявка принята. Это демонстрационный режим без отправки на сервер.';
      submit.disabled = false;
      submit.textContent = 'Отправить заявку';
      form.reset();
    }, 700);
  });
}
