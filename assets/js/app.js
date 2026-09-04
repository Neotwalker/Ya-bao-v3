import { initHeader, initMobileCta } from './components/header.js';
import { initModal } from './components/modal.js';
import { initForms } from './components/forms.js';
import { initAccordion } from './components/accordion.js';
import { initReveal } from './components/reveal.js';
import { runInitializers } from './utils/init.js';

document.documentElement.classList.add('js');

function initCurrentYear() {
  const year = String(new Date().getFullYear());
  document.querySelectorAll('[data-current-year]').forEach(element => {
    element.textContent = year;
  });
}

runInitializers(
  initHeader,
  initMobileCta,
  initModal,
  initForms,
  initAccordion,
  initReveal,
  initCurrentYear,
);
