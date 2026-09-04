const locks = new Set();

function sync() {
  document.body.classList.toggle('is-locked', locks.size > 0);
}

export function lockBody(source) {
  locks.add(source);
  sync();
}

export function unlockBody(source) {
  locks.delete(source);
  sync();
}
