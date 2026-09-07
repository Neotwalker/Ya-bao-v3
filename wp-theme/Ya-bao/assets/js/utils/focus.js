const FOCUSABLE = [
  'a[href]',
  'button:not([disabled])',
  'input:not([disabled]):not([type="hidden"])',
  'select:not([disabled])',
  'textarea:not([disabled])',
  '[tabindex]:not([tabindex="-1"])',
].join(',');

export function getFocusable(root) {
  return [...root.querySelectorAll(FOCUSABLE)].filter(element => {
    if (element.hidden || element.getAttribute('aria-hidden') === 'true') return false;
    return element.offsetParent !== null || element.getClientRects().length > 0;
  });
}

export function trapTab(event, elements) {
  if (event.key !== 'Tab' || !elements.length) return;

  const first = elements[0];
  const last = elements[elements.length - 1];

  if (event.shiftKey && document.activeElement === first) {
    event.preventDefault();
    last.focus();
  } else if (!event.shiftKey && document.activeElement === last) {
    event.preventDefault();
    first.focus();
  }
}
