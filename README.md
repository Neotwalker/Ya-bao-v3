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
