# Этап 46 — Performance

Статическая сборка остаётся референсом для WordPress. Изменения этого этапа касаются только загрузки hero media, raster images и блокирующих vendor scripts.

## Hero
- `hero-poster.webp` остаётся исходником 1920×1080 для OG/retina.
- Добавлены 960×540 и 1440×810 WebP-варианты и `srcset`/`sizes`.
- Preload главной использует `imagesrcset`, поэтому мобильному экрану не нужно обязательно брать 1920px-файл.
- Hero video сохранён под тем же URL, но перекодирован до 1440×810, H.264, faststart, без аудиодорожки.
- Видео больше не конкурирует с LCP-постером: source подключается JS после загрузки постера и idle-slot браузера.
- При `prefers-reduced-motion: reduce` и Save-Data видео вообще не загружается.
- `muted` и `playsinline` сохранены; автозапуска со звуком нет.

## Изображения
- Для категорий чая добавлены 640px WebP-варианты.
- Для крупных интерьерных/чайных фото добавлены 720px варианты.
- Исходники сохранены как верхний кандидат `srcset` для больших/Retina экранов и для OG/schema, где они уже используются.
- `width`/`height` не удалялись, lazy-load ниже первого экрана сохранён.

## JS
- Swiper и Fancybox больше не parser-blocking: vendor scripts получают `defer`.
- Инициализация home/article компонентов запускается после `DOMContentLoaded`, когда defer-зависимости уже готовы.

## WordPress
При интеграции использовать `wp_get_attachment_image()` / responsive image markup, не переносить статические srcset вручную как постоянную систему. Видео оставить с poster-first стратегией и не включать preload video на старте.
