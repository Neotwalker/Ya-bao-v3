const locks = new Set();
const root = document.documentElement;

function getScrollbarWidth() {
  return Math.max(0, window.innerWidth - root.clientWidth);
}

function setScrollbarCompensation() {
  const width = getScrollbarWidth();
  root.style.setProperty('--scrollbar-compensation', `${width}px`);
}

function clearScrollbarCompensation() {
  root.style.removeProperty('--scrollbar-compensation');
}

function sync() {
  const shouldLock = locks.size > 0;
  const isLocked = document.body.classList.contains('is-locked');

  if (shouldLock && !isLocked) {
    // Measure before overflow:hidden removes the classic scrollbar.
    setScrollbarCompensation();
    document.body.classList.add('is-locked');
    return;
  }

  if (!shouldLock && isLocked) {
    document.body.classList.remove('is-locked');
    clearScrollbarCompensation();
  }
}

export function lockBody(source) {
  locks.add(source);
  sync();
}

export function unlockBody(source) {
  locks.delete(source);
  sync();
}

// Keep compensation correct if the viewport changes while an overlay is open.
window.addEventListener('resize', () => {
  if (!locks.size) return;

  // Temporarily measure against the original viewport gutter:
  // when locked, clientWidth can already include the removed scrollbar,
  // so only refresh when an explicit compensation is not yet present.
  if (!root.style.getPropertyValue('--scrollbar-compensation')) {
    setScrollbarCompensation();
  }
}, { passive: true });
