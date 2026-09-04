import { setFieldError } from './validation.js';

function closeSelect(root) {
  root.classList.remove('is-open');
  root.querySelector('.custom-select__trigger')?.setAttribute('aria-expanded', 'false');
}

function initCustomSelect(select) {
  if (select.dataset.customSelectReady === 'true') return;
  select.dataset.customSelectReady = 'true';
  select.classList.add('custom-select__native');

  const root = document.createElement('div');
  root.className = 'custom-select';

  const trigger = document.createElement('button');
  trigger.className = 'custom-select__trigger';
  trigger.type = 'button';
  trigger.setAttribute('aria-haspopup', 'listbox');
  trigger.setAttribute('aria-expanded', 'false');

  const value = document.createElement('span');
  value.className = 'custom-select__value';

  const arrow = document.createElement('span');
  arrow.className = 'custom-select__arrow';
  arrow.setAttribute('aria-hidden', 'true');
  arrow.innerHTML = '<svg viewBox="0 0 24 24"><path d="m7 9 5 5 5-5" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"/></svg>';

  trigger.append(value, arrow);

  const menu = document.createElement('div');
  menu.className = 'custom-select__menu';
  menu.setAttribute('role', 'listbox');

  [...select.options].forEach(option => {
    const item = document.createElement('button');
    item.type = 'button';
    item.className = 'custom-select__option';
    item.setAttribute('role', 'option');
    item.dataset.value = option.value;
    item.textContent = option.textContent;
    item.disabled = option.disabled;

    item.addEventListener('click', () => {
      select.value = option.value;
      select.dispatchEvent(new Event('change', { bubbles: true }));
      select.dispatchEvent(new Event('input', { bubbles: true }));
      closeSelect(root);
    });

    menu.append(item);
  });

  root.append(trigger, menu);
  select.after(root);

  const sync = () => {
    const option = select.options[select.selectedIndex] || select.options[0];
    value.textContent = option?.textContent || '';
    root.classList.toggle('has-value', Boolean(select.value));

    menu.querySelectorAll('.custom-select__option').forEach(item => {
      const selected = item.dataset.value === select.value;
      item.classList.toggle('is-selected', selected);
      item.setAttribute('aria-selected', selected ? 'true' : 'false');
    });

    trigger.disabled = select.disabled;
  };

  trigger.addEventListener('click', () => {
    const open = !root.classList.contains('is-open');

    document.querySelectorAll('.custom-select.is-open').forEach(other => {
      if (other !== root) closeSelect(other);
    });

    root.classList.toggle('is-open', open);
    trigger.setAttribute('aria-expanded', open ? 'true' : 'false');
  });

  trigger.addEventListener('keydown', event => {
    if (event.key !== 'Escape') return;
    closeSelect(root);
    trigger.focus();
  });

  select.addEventListener('change', () => {
    sync();
    setFieldError(select, '');
  });

  sync();
}

let outsideClickReady = false;

export function initCustomSelects(root = document) {
  root.querySelectorAll('select').forEach(initCustomSelect);

  if (outsideClickReady) return;
  outsideClickReady = true;

  document.addEventListener('click', event => {
    document.querySelectorAll('.custom-select.is-open').forEach(selectRoot => {
      if (!selectRoot.contains(event.target)) closeSelect(selectRoot);
    });
  });
}

export function syncCustomSelects(form) {
  form.querySelectorAll('select').forEach(select => {
    select.dispatchEvent(new Event('change', { bubbles: true }));
  });
}
