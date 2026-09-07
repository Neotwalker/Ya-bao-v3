const subscribers = new Set();
let listening = false;
let frame = 0;

function notify() {
  frame = 0;
  const scrollY = window.scrollY;
  subscribers.forEach(callback => callback(scrollY));
}

function handleScroll() {
  if (frame) return;
  frame = window.requestAnimationFrame(notify);
}

export function onScroll(callback, { immediate = true } = {}) {
  subscribers.add(callback);

  if (!listening) {
    window.addEventListener('scroll', handleScroll, { passive: true });
    listening = true;
  }

  if (immediate) callback(window.scrollY);

  return () => {
    subscribers.delete(callback);
    if (!subscribers.size && listening) {
      window.removeEventListener('scroll', handleScroll);
      listening = false;
      if (frame) {
        window.cancelAnimationFrame(frame);
        frame = 0;
      }
    }
  };
}
