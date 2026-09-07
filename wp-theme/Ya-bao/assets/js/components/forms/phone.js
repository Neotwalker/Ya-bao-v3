export function formatPhone(value) {
  const digits = value.replace(/\D/g, '').replace(/^8/, '7').slice(0, 11);
  const body = digits.startsWith('7') ? digits.slice(1) : digits;

  let result = '+7';
  if (body.length) result += ` (${body.slice(0, 3)}`;
  if (body.length >= 3) result += ') ';
  if (body.length > 3) result += body.slice(3, 6);
  if (body.length > 6) result += `-${body.slice(6, 8)}`;
  if (body.length > 8) result += `-${body.slice(8, 10)}`;
  return result;
}

export function initPhoneFields(root = document) {
  root.querySelectorAll('input[type="tel"]').forEach(input => {
    if (input.dataset.phoneReady === 'true') return;
    input.dataset.phoneReady = 'true';

    input.addEventListener('input', () => {
      input.value = formatPhone(input.value);
    });
  });
}
