export function initGallery() {
  const dialog = document.querySelector('#lightbox');
  const image = dialog?.querySelector('img');
  const close = dialog?.querySelector('[data-lightbox-close]');
  if (!dialog || !image) return;
  document.querySelectorAll('[data-lightbox]').forEach(button => {
    button.addEventListener('click', () => {
      image.src = button.dataset.lightbox;
      image.alt = button.dataset.alt || '';
      dialog.showModal();
      close?.focus();
    });
  });
  close?.addEventListener('click', () => dialog.close());
  dialog.addEventListener('click', event => {
    if (event.target === dialog) dialog.close();
  });
}
