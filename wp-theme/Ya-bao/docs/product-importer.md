# Stage 66 — безопасный importer / sync

Версия темы: `0.4.0`.

## Цель

Stage 66 реализует безопасный слой синхронизации **канонического product contract 1.0.0 → WooCommerce**. Источник данных пока не фиксируется: Google Sheets, CSV, 1С, МойСклад или API позже должны быть адаптированы к тому же JSON-контракту. WooCommerce остаётся источником истины для каталога, цены, остатков, вариаций и корзины.

## Запуск

После установки patch появляется:

`WooCommerce → Импорт товаров`

Доступны два источника:

1. `data/products.json` внутри темы — demo QA;
2. загруженный JSON-файл до 5 МБ.

Сначала всегда запускать **Проверить (dry-run)**. Для реальной записи требуется отдельный checkbox подтверждения.

## Гарантии безопасности

- весь набор валидируется **до записи товаров**;
- `external_id`, SKU и `variant_id` проверяются на конфликты;
- existing product type автоматически не меняется;
- slug задаётся только при первом создании и потом sync его не переписывает;
- `missing_from_feed`, `inactive`, `archived` становятся draft, но не удаляются;
- товар, который вообще отсутствует в новом полном наборе, **не удаляется и не меняется** — событие только попадает в warning log;
- исчезнувшая управляемая variation не удаляется: переводится в `private + outofstock` и помечается `_yabao_variant_missing_from_source=1`;
- повторный импорт того же source-owned payload определяется по `_yabao_import_hash` и становится `no-op`; ручное сохранение управляемого Woo-товара/variation сбрасывает hash, чтобы следующий sync заново применил source-owned поля;
- media дедуплицируется по `_yabao_source_image`, поэтому повторный sync не плодит attachment-копии;
- редакционные ACF-поля не перезаписываются sync-ом. На первом создании они могут быть заполнены только если ACF Pro уже активен и поле зарегистрировано;
- demo product в `production` environment принудительно остаётся draft.

## Dry-run

Dry-run не создаёт и не изменяет:

- products;
- variations;
- categories;
- attributes;
- attachments.

Он читает текущую WooCommerce DB, строит план `create / update / noop` и показывает конфликты. Сам отчёт сохраняется в журнале импортера — это единственная служебная запись dry-run.

## Категории

Importer не придумывает названия неизвестных product category slug. Для категорий, уже присутствующих в утверждённом static catalog, используется подтверждённая карта:

- `sheng-puer` → Шэн пуэр
- `shu-puer` → Шу пуэр
- `white-tea` → Белый чай
- `pressed-tea` → Прессованный чай
- `brewing-ware` → Посуда для заваривания
- `serving-ware` → Посуда для подачи
- `tea-tools` → Чайные аксессуары
- `packaging` → Упаковка

Неизвестная категория должна либо уже существовать в WooCommerce, либо получить подтверждённое имя через будущий adapter/filter.

## Весовые товары

Для `sale_mode=weight` importer:

- создаёт/использует global attribute `Вес`, taxonomy `pa_weight`;
- создаёт terms `50 г`, `100 г`, `250 г` с slug `50-g`, `100-g`, `250-g`;
- parent product остаётся variable;
- parent не получает выдуманный numeric stock;
- каждая source variation получает `_yabao_variant_id`, цену, stock status и `pa_weight`;
- `manage_stock=false` для весовых variations, пока источник не отдаёт реальное количество.

## Поштучные товары

Simple unit product:

- regular price из contract;
- `manage_stock=true`;
- quantity и stock status из contract.

Unit product с variants поддерживается как variable product. Для безопасного однозначного mapping каждая unit variation обязана иметь `option_values`, и набор ключей должен быть одинаковым у всех variations одного товара. Эти attributes остаются локальными product attributes, как закреплено Stage 65.

## Изображения

Поддерживаются:

- relative path внутри темы, например `assets/images/...`;
- `http/https` URL.

Relative path не может выйти за пределы theme directory. Первый `images[]` после сортировки `sort_order` становится featured image, остальные — gallery.

## Логи

- последние 20 run reports: option `yabao_product_import_logs`;
- WooCommerce logger source: `yabao-importer`;
- admin UI показывает последний run с summary и сообщениями.

## Локальное окружение

WordPress по умолчанию может считать локальный OpenServer-сайт `production`. Для корректного различения demo/local желательно в локальном `wp-config.php` указать:

```php
define( 'WP_ENVIRONMENT_TYPE', 'local' );
```

Без этого demo import всё равно безопасен: importer создаст такие товары как draft и не опубликует их.

## Что Stage 66 специально не делает

- не подключает ACF Pro;
- не выбирает реальный production source;
- не реализует автоматический cron/webhook sync;
- не удаляет товары;
- не занимается checkout/delivery/payment;
- не заменяет Stage 67 server-side cart validation.
