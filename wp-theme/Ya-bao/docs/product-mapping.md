# Stage 65 — Product data → WooCommerce mapping

Источник контракта: `data/products.schema.json` / `data/products.json` версии `1.0.0`.

Цель этапа: закрепить **однозначное соответствие** между полями внешнего товарного контракта и WooCommerce до разработки импортера. Этот этап **не импортирует товары и не меняет БД**.

## 1. Источники истины

### WooCommerce — коммерческие данные

WooCommerce хранит и валидирует:

- товар и его статус публикации;
- SKU;
- цену;
- остаток / наличие;
- вариации;
- категории товаров;
- основное изображение и галерею;
- корзину и заказы.

### ACF Pro — редакционный контент

ACF Pro будет использоваться для ручного заполнения редакционных полей. Поля из `editorial` не должны становиться вторым источником цены, SKU, остатка или вариаций.

Запланированные имена ACF-полей:

- `seo_title`
- `meta_description`
- `long_description`
- `taste`
- `aroma`
- `brewing`
- `related_content`

ACF на Stage 65 **не требуется**. Поля только резервируются в контракте.

## 2. Идентификаторы

| Contract | WooCommerce / WP | Правило |
| --- | --- | --- |
| `external_id` | post meta `_yabao_external_id` | Постоянный внешний ID. Уникальный ключ синхронизации. После создания не меняется. |
| `sku` | WooCommerce SKU | Уникальный артикул родительского товара. |
| `variant_id` | variation meta `_yabao_variant_id` | Постоянный внешний ID вариации. Не подменяется синтетическим SKU. |
| `slug` | `post_name` | Используется при первом создании. После публикации не переписывается автоматически, чтобы не ломать SEO URL. |

`external_id` важнее внутреннего WordPress ID и не зависит от окружения/БД.

## 3. Тип товара

| Contract | WooCommerce |
| --- | --- |
| `sale_mode = weight` | variable product |
| `sale_mode = unit` и `variants` пуст | simple product |
| `sale_mode = unit` и есть `variants` | variable product |

Дополнительные исходные признаки сохраняются в meta:

- `type` → `_yabao_type` (`tea`, `ware`, `accessory`)
- `sale_mode` → `_yabao_sale_mode`
- `tea_form` → `_yabao_tea_form`
- `min_weight_g` → `_yabao_min_weight_g`

`category` → taxonomy `product_cat`, термин определяется по slug из контракта.

## 4. Статусы

| Contract `status` | WP status | В каталоге |
| --- | --- | --- |
| `active` | `publish` | да |
| `out_of_stock` | `publish` | да, как «Нет в наличии» |
| `inactive` | `draft` | нет |
| `missing_from_feed` | `draft` | нет; товар и его данные сохраняются |
| `archived` | `draft` | нет; товар не удаляется |

Исходный статус всегда дополнительно сохраняется в `_yabao_source_status`.

**Никакого физического удаления из-за отсутствия в фиде.** Это обязательное правило для Stage 66.

## 5. Наличие и количество

### Поштучные товары

`ware` / `accessory` без вариантов:

- `price` → Woo regular price;
- `quantity` → stock quantity;
- `manage_stock = true`;
- `stock_status = in_stock` → `instock`;
- `stock_status = out_of_stock` → `outofstock`;
- `quantity = 0` обязательно соответствует `outofstock`.

### Чай на вес

Чай не получает выдуманный числовой остаток, потому что контракт содержит только `stock_status` вариации.

- родитель → variable product;
- глобальный атрибут WooCommerce: **Вес**, slug `weight`, taxonomy `pa_weight`;
- значения: `50 г`, `100 г`, `250 г` и т. д.;
- term slug: `50-g`, `100-g`, `250-g`;
- каждая запись `variants[]` → Woo variation;
- `variant_id` → `_yabao_variant_id` вариации;
- `weight_g` → атрибут `pa_weight`;
- `price` → variation regular price;
- `stock_status` → variation stock status;
- `manage_stock = false`, пока источник не начнёт отдавать числовой остаток по весовой вариации.

Верхнеуровневый `price` весового товара не записывается как regular price родителя. Он сохраняется как `_yabao_source_price` для проверки контракта; фактическую цену WooCommerce выводит из вариаций.

## 6. Поштучные варианты

Если у `sale_mode = unit` появится `variants[]`:

- товар становится variable;
- `variant_id` → `_yabao_variant_id`;
- `price` → variation price;
- `quantity` → variation stock quantity;
- `stock_status` → variation stock status;
- `option_values` формируют variation attributes.

Произвольные `option_values` на первом этапе создаются как **локальные атрибуты товара**, а не как новые глобальные taxonomy. Глобальные атрибуты регистрируем отдельно только после подтверждения реального ассортимента.

## 7. Изображения

`images[]` сортируются по `sort_order`:

- первый элемент → featured image;
- остальные → WooCommerce product gallery;
- `alt` → attachment alt;
- повторная синхронизация должна переиспользовать уже импортированный attachment, а не плодить дубли.

Правила дедупликации реализуются на Stage 66.

## 8. Редакционные поля и ownership

По умолчанию:

**Источник / sync владеет:**

- `external_id`
- `sku`
- `name`
- `category`
- `type`
- `sale_mode`
- `tea_form`
- `min_weight_g`
- `price`
- `stock_status`
- `quantity`
- `variants`
- `images`
- `status`
- `updated_at`

**WordPress / ACF владеет:**

- `seo_title`
- `meta_description`
- `long_description`
- `taste`
- `aroma`
- `brewing`
- `related_content`

На первом создании Stage 66 сможет заполнить пустое ACF-поле из `editorial`, если оно пришло в источнике. После ручного редактирования непустые ACF-поля импорт не перезаписывает без отдельного явно включённого режима.

## 9. Demo safety

`is_demo = true` сохраняется в `_yabao_is_demo`, `demo_note` → `_yabao_demo_note`.

Stage 66 должен различать окружения:

- local/staging: demo-товары можно импортировать для QA;
- production: demo-товары по умолчанию не должны становиться публичными.

## 10. Поля source state

| Contract | Meta |
| --- | --- |
| `status` | `_yabao_source_status` |
| `price` | `_yabao_source_price` для аудита / variable validation |
| `updated_at` | `_yabao_source_updated_at` |
| `is_demo` | `_yabao_is_demo` |
| `demo_note` | `_yabao_demo_note` |

## 11. Проверки перед импортом

Stage 66 не должен писать данные, если нарушено хотя бы одно из условий:

1. `external_id` и `sku` пусты или конфликтуют с другим товаром.
2. `sale_mode = weight`, но товар в наличии не имеет хотя бы одной вариации от 50 г.
3. `weight_g` меньше 50 или не кратен 50.
4. `variant_id` повторяется.
5. Поштучный товар `out_of_stock` имеет `quantity > 0`.
6. Поштучный товар `in_stock` имеет `quantity < 1`.
7. Неизвестный `status`, `type`, `sale_mode` или `stock_status`.
8. Валюта набора не `RUB`.

## 12. Результат Stage 65

После этого документа нет неоднозначности по:

- product identity;
- simple / variable типу;
- весам 50 г+;
- ценам;
- stock;
- категориям;
- изображениям;
- статусам;
- ACF editorial ownership;
- безопасному поведению для missing/archived/demo.

Следующий этап — **Stage 66: безопасный importer / sync с dry-run, логом и no-delete-on-missing**.
