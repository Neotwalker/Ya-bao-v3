# Я Бао Завари — статический frontend-прототип

GitHub Pages версия сайта чайной «Я Бао Завари» в Челябинске.

## Текущая структура

### Страницы
- `index.html` — главная;
- `menu.html` — меню/каталог с данными Google Sheets;
- `about.html` — о чайной;
- `contacts.html` — контакты;
- `booking.html` — интерфейс бронирования (noindex);
- `privacy.html`, `consent.html` — юридические страницы (noindex);
- `404.html` — страница ошибки (noindex).

### CSS
- `tokens.css` — дизайн-токены;
- `base.css` — reset, типографика, контейнеры и базовые utility;
- `components.css` — глобальные компоненты;
- `pages.css` — внутренние страницы;
- `responsive.css` — общий адаптив;
- `custom.css` — глобальные проектные уточнения;
- `home-v4.css` — только главная.

`sections.css` удалён в v4.36: после предыдущей чистки в нём оставался только FAQ главной, который перенесён в `home-v4.css`.

### JavaScript
- `app.js` — глобальная инициализация;
- `home.js` — только главная: hero video, Fancybox, Swiper, top-scroll;
- `menu.js` — только меню: фильтры и каталог;
- `components/forms/` — телефон, select, дата/время, validation и demo-form;
- `components/catalog/` — cache, Google Sheets transport, schema, products и render;
- `utils/` — общий initializer и единый scroll dispatcher.

## Зависимости
- Fancybox — локально в `assets/vendor/fancybox/`;
- Swiper — локально в `assets/vendor/swiper/`;
- дата и время — нативные `input[type="date"]` / `input[type="time"]`;
- Flatpickr не используется;
- каталог читает публичные данные Google Sheets через GViz endpoint;
- Google Fonts остаются внешней web-font зависимостью в `tokens.css`.

## Важное перед production
1. Подключить реальную серверную отправку форм: сейчас формы работают как UI-прототип и данные не отправляют.
2. Проверить и заполнить юридические данные в `privacy.html` и `consent.html` перед production.
3. Перед публикацией актуальных цен, графика и событий сверять данные с официальными источниками проекта.
4. При переносе с GitHub Pages на основной домен обновить canonical, sitemap, robots и JSON-LD URL.

## v4.36 — финальный QA после рефакторинга
- исправлен modifier footer social styles: `--primary`;
- удалён последний точный CSS-дубль `top-scroll`;
- удалён неиспользуемый `sections.css`;
- удалены оставшиеся Flatpickr-стили после перехода на нативные date/time;
- удалены мёртвые lightbox/tag/price-line/feature-list стили;
- удалены 10 неиспользуемых legacy-изображений;
- выполнен полный статический QA проекта; браузерный smoke-тест нужно повторить после загрузки на GitHub Pages, потому что локальный Chromium в текущей среде блокирует localhost/file navigation.

## v4.37 production hardening

- доступность: skip-link на всех страницах;
- прямое подключение Google Fonts без CSS `@import`;
- intrinsic width/height для статических и динамических изображений;
- preload hero poster + preconnect для внешних ресурсов по месту использования;
- autocomplete для имени и телефона;
- Open Graph URL/site name и Twitter Card metadata;
- hero-видео перекодировано в H.264 1920×1080 с faststart для меньшего веса;
- визуальная структура и тексты не менялись.

## v4.38 technical SEO

- индексируемые страницы: главная, меню, о чайной, контакты;
- `booking`, `privacy`, `consent` и `404` сохраняют `noindex,follow`;
- sitemap содержит только индексируемые canonical URL;
- добавлены `CafeOrCoffeeShop`, `WebSite`, `WebPage` и `BreadcrumbList` в JSON-LD;
- бизнес-схема дополнена телефоном, изображением и ссылками на карты;
- Open Graph image metadata дополнена type/width/height/alt;
- улучшены SEO title/description у страниц «О чайной» и «Контакты»;
- canonical-схема и robots-политика не меняют структуру сайта.

## v4.39 interaction accessibility

- единый body-lock для мобильного меню и booking modal;
- мобильная навигация удерживает клавиатурный фокус внутри открытого overlay и закрывается при переходе на desktop;
- accordion автоматически получает `aria-controls`, `aria-labelledby` и region semantics;
- ошибки форм связаны с полями через `aria-describedby`;
- при невалидной отправке фокус переводится к первому проблемному полю;
- custom select получил полноценную клавиатурную навигацию и больше не создаёт скрытый дублирующий Tab-stop;
- добавлены заметные `:focus-visible` состояния без изменения обычного визуала.

## v4.40 runtime robustness

- исправлен первый фокус booking modal: скрытые hidden-поля больше не перехватывают попытку фокусировки;
- reset формы теперь очищает старые ошибки, `aria-invalid`, статус и loading-state;
- после успешной демо-отправки success-сообщение сохраняется до следующего взаимодействия/закрытия;
- глобальное сообщение об ошибке формы очищается, когда пользователь начинает исправлять поля;
- каталог различает свежий cache и просроченные данные;
- просроченные цены/остатки не используются как fallback;
- при offline каталог сразу показывает безопасный статический fallback, не ждёт сетевой timeout;
- после восстановления сети каталог автоматически пытается обновиться;
- параллельные повторные запросы каталога блокируются одним refresh promise.

## v4.40.1 W3C HTML validation patch

Исправлены 4 ошибки главной страницы из Nu Html Checker:

- два generic `div` с `aria-label` получили `role="group"`;
- карточка «Вдвоём или с друзьями» больше не является `button` с вложенными `h3` и `p`;
- карточка стала валидной ссылкой на `booking.html` с сохранением открытия modal при включённом JavaScript;
- обработчик `[data-modal-open]` вызывает `preventDefault()`, поэтому ссылка работает как fallback только без JavaScript.

## v4.40.2 mobile booking UX patch

- полностью удалён sticky `.mobile-cta` со всех страниц;
- убран резерв `padding-bottom` под нижнюю sticky-кнопку;
- кнопка «Забронировать» добавлена внутрь `.mobile-nav` на всех 8 страницах;
- при нажатии кнопки мобильное меню сначала закрывается, затем открывается booking modal;
- удалены `initMobileCta`, CSS `.mobile-cta` и неиспользуемый `--z-mobile-cta`.

## v4.41 events

- добавлен блок «События и встречи» на главную;
- добавлена индексируемая страница `events.html`;
- добавлен общий Swiper-компонент для мероприятий;
- слайдер работает на главной и на странице мероприятий;
- в навигацию и футер добавлена ссылка «Мероприятия»;
- `events.html` добавлена в sitemap и получает WebPage/Breadcrumb JSON-LD;
- Event schema не добавлялась: подтверждённых дат и программ пока нет;
- карточки используют только уже заявленные на сайте форматы: музыкальные вечера, английский клуб, настольные игры, чайные клубы и камерные мастер-классы;
- неподтверждённые даты, цены, ведущие и программы не выдумывались.

## v4.41.1 events architecture patch

- `events.html` переведена со слайдера на последовательный список мероприятий;
- добавлен стилизованный пример пагинации для будущего большого архива;
- H1 страницы изменён на «Мероприятия»;
- breadcrumbs и hero `events.html` приведены к эталону `menu.html`;
- исправлен нижний отступ hero через те же `section section--dark section--compact inner-hero`;
- карточки слайдера на главной получили ссылки на `event.html`;
- создана эталонная страница одного мероприятия `event.html`;
- `event.html` пока `noindex,follow`, потому что реальных даты/времени/программы ещё нет;
- `Event` schema намеренно не используется до появления подтверждённых данных;
- Swiper полностью удалён из `events.html`, но сохранён в блоке мероприятий на главной.

## v4.41.2 events UX patch

- в `event.html` добавлена кнопка «Записаться» с event-mode booking modal;
- исправлен `aria-current`: на `events.html` и `event.html` активен только пункт «Мероприятия»;
- действие карточки списка перенесено внутрь `.event-list-card__content` и теперь подписано «Перейти →»;
- действие остаётся видимым на планшетах и мобильных;
- пример пагинации использует реальные ссылки, без подчёркивания, с hover/focus состояниями;
- удалён поясняющий текст под пагинацией;
- мобильная пагинация уплотнена;
- при `max-width:1024px` убраны лишние нижние margin у H1 и текста inner hero, потому что расстояние уже задаёт grid `gap`;
- `.eyebrow` удалены из всех `.inner-hero`.

## v4.41.3 event aside button contrast patch

- вторичная кнопка «К мероприятиям» в `event.html` переведена с `button--light` на контурную `button--outline-walnut`;
- на светлом фоне кнопка теперь имеет заметную ореховую рамку и текст;
- hover заполняет кнопку ореховым цветом с белым текстом.
