import { setFieldError } from './validation.js';

function getOptions(root) {
  return [...root.querySelectorAll('.custom-select__option:not([disabled])')];
}

function closeSelect(root, { focusTrigger = false } = {}) {
  root.classList.remove('is-open');
  const trigger = root.querySelector('.custom-select__trigger');
  trigger?.setAttribute('aria-expanded', 'false');
  if (focusTrigger) trigger?.focus();
}

function focusOption(root, mode = 'selected') {
  const options = getOptions(root);
  if (!options.length) return;

  let target = options[0];

  if (mode === 'last') {
    target = options[options.length - 1];
  } else if (mode === 'selected') {
    target = options.find(item => item.getAttribute('aria-selected') === 'true') || options[0];
  }

  target.focus();
}

function openSelect(root, mode = 'selected') {
  document.querySelectorAll('.custom-select.is-open').forEach(other => {
    if (other !== root) closeSelect(other);
  });

  root.classList.add('is-open');
  root.querySelector('.custom-select__trigger')?.setAttribute('aria-expanded', 'true');
  requestAnimationFrame(() => focusOption(root, mode));
}

function moveOptionFocus(root, direction) {
  const options = getOptions(root);
  if (!options.length) return;

  const current = options.indexOf(document.activeElement);
  const next = current < 0
    ? 0
    : (current + direction + options.length) % options.length;

  options[next].focus();
}

function initCustomSelect(select, index) {
  if (select.dataset.customSelectReady === 'true') return;
  select.dataset.customSelectReady = 'true';
  select.classList.add('custom-select__native');
  select.tabIndex = -1;
  select.setAttribute('aria-hidden', 'true');

  const root = document.createElement('div');
  root.className = 'custom-select';

  const trigger = document.createElement('button');
  trigger.className = 'custom-select__trigger';
  trigger.type = 'button';
  trigger.setAttribute('aria-haspopup', 'listbox');
  trigger.setAttribute('aria-expanded', 'false');

  const baseId = select.id || `custom-select-${index + 1}`;
  const triggerId = `${baseId}-trigger`;
  const menuId = `${baseId}-listbox`;

  trigger.id = triggerId;
  trigger.setAttribute('aria-controls', menuId);

  const label = select.id
    ? document.querySelector(`label[for="${CSS.escape(select.id)}"]`)
    : null;

  if (label) {
    if (!label.id) label.id = `${baseId}-label`;
    trigger.setAttribute('aria-labelledby', label.id);
    label.htmlFor = triggerId;
  }

  const value = document.createElement('span');
  value.className = 'custom-select__value';

  const arrow = document.createElement('span');
  arrow.className = 'custom-select__arrow';
  arrow.setAttribute('aria-hidden', 'true');
  arrow.innerHTML = '<svg viewBox="0 0 24 24"><path d="m7 9 5 5 5-5" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"/></svg>';

  trigger.append(value, arrow);

  const menu = document.createElement('div');
  menu.className = 'custom-select__menu';
  menu.id = menuId;
  menu.setAttribute('role', 'listbox');
  if (label?.id) menu.setAttribute('aria-labelledby', label.id);

  [...select.options].forEach((option, optionIndex) => {
    const item = document.createElement('button');
    item.type = 'button';
    item.className = 'custom-select__option';
    item.id = `${baseId}-option-${optionIndex + 1}`;
    item.setAttribute('role', 'option');
    item.dataset.value = option.value;
    item.textContent = option.textContent;
    item.disabled = option.disabled;

    item.addEventListener('click', () => {
      select.value = option.value;
      select.dispatchEvent(new Event('change', { bubbles: true }));
      select.dispatchEvent(new Event('input', { bubbles: true }));
      closeSelect(root, { focusTrigger: true });
    });

    item.addEventListener('keydown', event => {
      if (event.key === 'ArrowDown') {
        event.preventDefault();
        moveOptionFocus(root, 1);
      } else if (event.key === 'ArrowUp') {
        event.preventDefault();
        moveOptionFocus(root, -1);
      } else if (event.key === 'Home') {
        event.preventDefault();
        focusOption(root, 'first');
      } else if (event.key === 'End') {
        event.preventDefault();
        focusOption(root, 'last');
      } else if (event.key === 'Escape') {
        event.preventDefault();
        closeSelect(root, { focusTrigger: true });
      } else if (event.key === 'Tab') {
        closeSelect(root);
      }
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
    trigger.setAttribute('aria-invalid', select.getAttribute('aria-invalid') || 'false');

    const describedBy = select.getAttribute('aria-describedby');
    if (describedBy) trigger.setAttribute('aria-describedby', describedBy);
  };

  trigger.addEventListener('click', () => {
    root.classList.contains('is-open')
      ? closeSelect(root)
      : openSelect(root);
  });

  trigger.addEventListener('keydown', event => {
    if (event.key === 'ArrowDown') {
      event.preventDefault();
      openSelect(root, 'selected');
    } else if (event.key === 'ArrowUp') {
      event.preventDefault();
      openSelect(root, 'last');
    } else if (event.key === 'Escape') {
      event.preventDefault();
      closeSelect(root);
    }
  });

  select.addEventListener('change', () => {
    sync();
    setFieldError(select, '');
    requestAnimationFrame(sync);
  });

  const observer = new MutationObserver(sync);
  observer.observe(select, {
    attributes: true,
    attributeFilter: ['aria-invalid', 'aria-describedby', 'disabled'],
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
