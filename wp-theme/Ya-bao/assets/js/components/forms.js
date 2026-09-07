import { initPhoneFields } from './forms/phone.js';
import { initCustomSelects } from './forms/custom-select.js';
import { initDateTimeFields } from './forms/date-time.js';
import { initDemoForms } from './forms/demo-form.js';

let formsInitialized = false;

export function initForms() {
  if (formsInitialized) return;
  formsInitialized = true;

  initPhoneFields();
  initCustomSelects();
  initDateTimeFields();
  initDemoForms();
}
