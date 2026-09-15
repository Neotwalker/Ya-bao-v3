# Stage 68.1 — Ya Bao × ApiShip adapter

This is a companion plugin for the custom Ya Bao WooCommerce checkout.

It does **not** bundle or fork ApiShip. Install the official ApiShip WooCommerce plugin separately and keep it updateable.

## Current approved business rules

- Free carrier delivery for the buyer starts from **10,000 RUB** of merchandise after discounts/coupons. Shipping charges and payment fees are not part of the threshold calculation.
- **CDEK, 5Post and Russian Post must be calculated automatically through ApiShip.**
- Use the real ApiShip provider list/runtime metadata for provider keys; do not guess or freeze undocumented provider IDs in project documentation.
- **Avito Delivery remains a manual fallback** until an official automatic flow for WooCommerce orders created outside Avito is separately confirmed.
- The store has **no own courier delivery in Chelyabinsk**. A customer may independently order **Yandex Go** in their own app; Yandex Go is not a WooCommerce shipping method and is not calculated by this adapter.
- The Stage 68 manual-quote flow remains a safety fallback when ApiShip or a required carrier is unavailable.

## Adapter goals

1. Receive real WooCommerce shipping rates from ApiShip.
2. Allow only the approved automatic carriers: CDEK, 5Post and Russian Post.
3. Preserve the store rule: carrier delivery is free for the buyer from 10,000 RUB.
4. Preserve the real ApiShip carrier cost in shipping-rate/order metadata before the customer-facing cost is zeroed.
5. Preserve WooCommerce/ApiShip extension hooks required for PVZ controls in the custom Ya Bao shipping-card UI.
6. Persist the selected provider, tariff and PVZ data in the WooCommerce order where available.

## Local installation

Copy this folder to:

`wp-content/plugins/yabao-apiship-adapter/`

Then activate **Ya Bao × ApiShip Adapter** in WordPress.

Install and activate the official ApiShip WooCommerce plugin separately.

## ApiShip prerequisites

- ApiShip account and API token.
- Configured connections for CDEK, 5Post and Russian Post (test mode is acceptable for local QA until production credentials are available).
- Sender/store or warehouse address in WooCommerce / ApiShip settings.
- Product weight and dimensions, or explicit default package dimensions in ApiShip settings for temporary testing.
- Yandex Maps API key if the selected ApiShip configuration uses the PVZ map.
- The WooCommerce checkout must remain the classic shortcode checkout (`[woocommerce_checkout]`).
- Add **ApiShip integrator** to the WooCommerce shipping zone used for Russia.

## QA scenarios

### A. Order below 10,000 RUB

- Enter a delivery city/postcode.
- Real ApiShip rates for the approved automatic carriers must replace their Stage 68 manual carrier entries when available.
- CDEK, 5Post and Russian Post must be able to return automatic rates when the corresponding ApiShip connections support the destination/package.
- Select a carrier/PVZ tariff.
- The checkout must show the selected ApiShip rate, price and delivery days when provided.
- For a PVZ tariff, the ApiShip PVZ control/map must remain usable through WooCommerce AJAX checkout updates.
- WooCommerce total must include the real ApiShip delivery cost.

### B. Order from 10,000 RUB

- ApiShip must still calculate the real carrier rate.
- Customer-facing shipping cost must be 0.
- The actual calculated carrier cost must remain available in Ya Bao shipping/order metadata for expense control and analytics.
- WooCommerce total must not add shipping to the buyer.

### C. ApiShip disabled / unavailable

- Existing Stage 68 fallback must remain functional.
- Manual quote must remain available for a carrier that cannot be calculated automatically.
- No fatal error or broken checkout is acceptable.

### D. Chelyabinsk / Yandex Go

- Do not expose Yandex Go as an automatic store shipping rate.
- If the site mentions this option, wording must make clear that the customer independently orders and pays for Yandex Go in their own application.

## Scope boundary

Do not merge a delivery automation change into `main` until real ApiShip rates and PVZ controls pass local checkout QA for the approved carriers.

Before Stage 69 (YooKassa) we must:

- finish the selected PVZ integration in the Ya Bao delivery cards;
- normalize provider/tariff/price/ETA presentation in the site design;
- verify that CDEK, 5Post and Russian Post are calculated automatically with the real ApiShip provider keys/configuration;
- confirm persistence of provider/tariff/PVZ and actual free-shipping carrier cost in the final WooCommerce order;
- keep Avito/manual quote as fallback unless its automatic flow is separately approved;
- keep the Stage 68 manual quote flow as an operational safety fallback.
