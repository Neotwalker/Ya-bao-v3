# Stage 45 — Technical SEO handoff

This folder records the SEO rules to carry from the static GitHub Pages reference into WordPress.

## Indexation
- Index: homepage, menu, about, contacts, tea ceremony, blog hub, published SEO articles.
- Noindex,follow: booking, privacy, consent, generic article template, generic event template, 404.
- `/events/` remains `noindex,follow` and is excluded from the sitemap until real, confirmed events are available.
- Product and Event structured data are intentionally not added without verified current product/event data.

## Canonical URLs
GitHub Pages keeps its current `.html` canonicals because it is the preview/reference build.
At WordPress integration, replace them with the clean paths from `metadata-audit.csv` and the redirect map in `../seo-migration/`.

## LocalBusiness
Confirmed facts used:
- Name: Я Бао Завари
- Address: Челябинск, ул. Кирова, 94
- Phone: +7 999 584-72-90
- Hours: daily 10:00–01:00
- VK: https://vk.ru/yabaozavary
- Telegram: https://t.me/yabaozavari
- 2GIS and Yandex Maps listing URLs already present in the project

No prices, ratings, reviews, legal identifiers, geo coordinates, or product availability are invented.

## robots.txt on GitHub Pages
This repository is a GitHub Pages project site under `/Ya-bao-v3/`. A robots file inside that project path is useful as a production template, but the authoritative robots.txt for the `neotwalker.github.io` host would have to live at the host root.
For WordPress, deploy the production robots file at `/robots.txt` on the final domain.

## Sitemap
The preview sitemap contains only canonical pages intended for indexing in the current content architecture.
`events.html` is excluded until real events are confirmed.
