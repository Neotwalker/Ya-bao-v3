function formatPhone(value){const digits=value.replace(/\D/g,'').replace(/^8/,'7').slice(0,11);const body=digits.startsWith('7')?digits.slice(1):digits;let result='+7';if(body.length)result+=` (${body.slice(0,3)}`;if(body.length>=3)result+=') ';if(body.length>3)result+=body.slice(3,6);if(body.length>6)result+=`-${body.slice(6,8)}`;if(body.length>8)result+=`-${body.slice(8,10)}`;return result}

const OPEN_START_MINUTES=10*60;
const OPEN_END_MINUTES=60;

function isOpenTime(value){if(!/^\d{2}:\d{2}$/.test(value))return false;const [hours,minutes]=value.split(':').map(Number);const total=hours*60+minutes;return total>=OPEN_START_MINUTES||total<=OPEN_END_MINUTES}

function setError(field,message){field.setAttribute('aria-invalid',message?'true':'false');const error=field.closest('.field, .checkbox')?.querySelector('.field__error');if(error)error.textContent=message}

function validate(form){let valid=true;form.querySelectorAll('[required]').forEach(field=>{let message='';if(field.type==='checkbox'&&!field.checked)message='Подтвердите согласие.';else if(!String(field.value).trim())message='Заполните поле.';else if(field.type==='tel'&&field.value.replace(/\D/g,'').length<11)message='Введите полный номер телефона.';else if(field.matches('input[name="time"]')&&!isOpenTime(field.value))message='Выберите время с 10:00 до 01:00.';setError(field,message);if(message)valid=false});return valid}

function ensureStylesheet(href,id){if(document.getElementById(id))return;const link=document.createElement('link');link.id=id;link.rel='stylesheet';link.href=href;document.head.append(link)}

function loadScript(src,id){return new Promise((resolve,reject)=>{const existing=document.getElementById(id);if(existing){if(existing.dataset.loaded==='true')resolve();else existing.addEventListener('load',resolve,{once:true});return}const script=document.createElement('script');script.id=id;script.src=src;script.async=true;script.addEventListener('load',()=>{script.dataset.loaded='true';resolve()},{once:true});script.addEventListener('error',reject,{once:true});document.head.append(script)})}

async function ensureFlatpickr(){ensureStylesheet('https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.css','flatpickr-css');if(!window.flatpickr)await loadScript('https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.js','flatpickr-js');if(!window.flatpickr?.l10ns?.ru)await loadScript('https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/l10n/ru.js','flatpickr-ru');return window.flatpickr}

function initCustomSelect(select){if(select.dataset.customSelectReady==='true')return;select.dataset.customSelectReady='true';select.classList.add('custom-select__native');
  const root=document.createElement('div');root.className='custom-select';
  const trigger=document.createElement('button');trigger.className='custom-select__trigger';trigger.type='button';trigger.setAttribute('aria-haspopup','listbox');trigger.setAttribute('aria-expanded','false');
  const value=document.createElement('span');value.className='custom-select__value';
  const arrow=document.createElement('span');arrow.className='custom-select__arrow';arrow.setAttribute('aria-hidden','true');arrow.innerHTML='<svg viewBox="0 0 24 24"><path d="m7 9 5 5 5-5" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"/></svg>';
  trigger.append(value,arrow);
  const menu=document.createElement('div');menu.className='custom-select__menu';menu.setAttribute('role','listbox');
  const close=()=>{root.classList.remove('is-open');trigger.setAttribute('aria-expanded','false')};
  [...select.options].forEach(option=>{const item=document.createElement('button');item.type='button';item.className='custom-select__option';item.setAttribute('role','option');item.dataset.value=option.value;item.textContent=option.textContent;item.disabled=option.disabled;item.addEventListener('click',()=>{select.value=option.value;select.dispatchEvent(new Event('change',{bubbles:true}));select.dispatchEvent(new Event('input',{bubbles:true}));close()});menu.append(item)});
  root.append(trigger,menu);select.after(root);
  const sync=()=>{const option=select.options[select.selectedIndex]||select.options[0];value.textContent=option?.textContent||'';root.classList.toggle('has-value',Boolean(select.value));menu.querySelectorAll('.custom-select__option').forEach(item=>{const selected=item.dataset.value===select.value;item.classList.toggle('is-selected',selected);item.setAttribute('aria-selected',selected?'true':'false')});trigger.disabled=select.disabled};
  trigger.addEventListener('click',()=>{const open=!root.classList.contains('is-open');document.querySelectorAll('.custom-select.is-open').forEach(other=>{if(other!==root){other.classList.remove('is-open');other.querySelector('.custom-select__trigger')?.setAttribute('aria-expanded','false')}});root.classList.toggle('is-open',open);trigger.setAttribute('aria-expanded',open?'true':'false')});
  trigger.addEventListener('keydown',event=>{if(event.key==='Escape'){close();trigger.focus()}});
  select.addEventListener('change',()=>{sync();setError(select,'')});
  document.addEventListener('click',event=>{if(!root.contains(event.target))close()});
  sync();
}

function initCustomSelects(){document.querySelectorAll('select').forEach(initCustomSelect)}

async function initFlatpickrFields(){try{const flatpickr=await ensureFlatpickr();const locale=window.flatpickr?.l10ns?.ru||'default';document.querySelectorAll('input[type="date"], input[data-flatpickr-date]').forEach(input=>{if(input._flatpickr)return;input.type='text';input.dataset.flatpickrDate='';flatpickr(input,{locale,dateFormat:'Y-m-d',altInput:true,altFormat:'d.m.Y',minDate:'today',disableMobile:true,allowInput:false});});document.querySelectorAll('input[type="time"], input[data-flatpickr-time]').forEach(input=>{if(input._flatpickr)return;input.type='text';input.dataset.flatpickrTime='';flatpickr(input,{locale,enableTime:true,noCalendar:true,dateFormat:'H:i',time_24hr:true,minuteIncrement:15,disableMobile:true,allowInput:false,defaultHour:10,defaultMinute:0,onChange(selectedDates,dateStr,instance){if(!dateStr||isOpenTime(dateStr)){setError(input,'');return}const [hours,minutes]=dateStr.split(':').map(Number);const total=hours*60+minutes;const replacement=total>OPEN_END_MINUTES&&total<OPEN_START_MINUTES?(total<5*60?'01:00':'10:00'):'10:00';instance.setDate(replacement,false,'H:i');input.dispatchEvent(new Event('input',{bubbles:true}));setError(input,'')}});});}catch(error){console.error('flatpickr failed',error)}}

function syncFormControls(form){form.querySelectorAll('select').forEach(select=>select.dispatchEvent(new Event('change',{bubbles:true})));form.querySelectorAll('[data-flatpickr-date],[data-flatpickr-time]').forEach(input=>input._flatpickr?.clear(false))}

export function initForms(){document.querySelectorAll('input[type="tel"]').forEach(input=>input.addEventListener('input',()=>{input.value=formatPhone(input.value)}));initCustomSelects();initFlatpickrFields();document.querySelectorAll('[data-demo-form]').forEach(form=>{form.addEventListener('submit',async event=>{event.preventDefault();const status=form.querySelector('[data-form-status]');if(!validate(form)){if(status){status.textContent='Проверьте выделенные поля.';status.className='form-status is-error'}return}const submit=form.querySelector('[type="submit"]');submit?.classList.add('is-loading');submit?.setAttribute('disabled','');if(status){status.textContent='Отправляем заявку…';status.className='form-status'}await new Promise(resolve=>setTimeout(resolve,700));if(status){status.textContent='Демо-режим: форма проверена, но отправка пока не подключена.';status.className='form-status is-success'}form.reset();syncFormControls(form);submit?.classList.remove('is-loading');submit?.removeAttribute('disabled')});form.addEventListener('reset',()=>requestAnimationFrame(()=>syncFormControls(form)));form.querySelectorAll('input,select,textarea').forEach(field=>field.addEventListener('input',()=>setError(field,'')))})}
