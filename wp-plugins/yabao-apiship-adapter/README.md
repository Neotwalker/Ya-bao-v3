# Stage 68.1 — Ya Bao × ApiShip adapter PoC

This is a companion plugin for the custom Ya Bao WooCommerce checkout.

It does **not** bundle or fork ApiShip. Install the official ApiShip WooCommerce plugin separately and keep it updateable.

## PoC goals

1. Receive real WooCommerce shipping rates from ApiShip.
2. Preserve the store rule: carrier delivery is free for the buyer from 5,000 RUB.
3. Preserve the real ApiShip carrier cost in shipping-rate metadata before the customer-facing cost is zeroed.
4. Re-play WooCommerce's `woocommerce_after_shipping_rate` hook for the selected ApiShip rate, because the Ya Bao theme renders custom shipping cards and ApiShip uses this hook for PVZ controls.
5. Observe the real ApiShip/PVZ markup before final styling and before moving the controls directly into the Ya Bao shipping cards.

## Local installation

Copy this folder to:

`wp-content/plugins/yabao-apiship-adapter/`

Then activate **Ya Bao × ApiShip Adapter** in WordPress.

Install and activate the official ApiShip WooCommerce plugin separately.

## ApiShip prerequisites

- ApiShip account and API token.
- At least one configured delivery-service connection (test mode is acceptable for the PoC).
- Sender/store or warehouse address in WooCommerce / ApiShip settings.
- Product weight and dimensions, or explicit default package dimensions in ApiShip settings for temporary testing.
- Yandex Maps API key if the selected ApiShip configuration uses the PVZ map.
- The WooCommerce checkout must remain the classic shortcode checkout (`[woocommerce_checkout]`).
- Add **ApiShip integrator** to the WooCommerce shipping zone used for Russia.

## QA scenarios

### A. Order below 5,000 RUB

- Enter a delivery city/postcode.
- Real ApiShip rates must replace the Stage 68 fallback carrier list.
- Select a carrier/PVZ tariff.
- The PoC panel must show the selected ApiShip rate, price and delivery days when provided.
- For a PVZ tariff, ApiShip's standard PVZ control/map should be reachable from the PoC panel.
- WooCommerce total must include the real ApiShip delivery cost.

### B. Order from 5,000 RUB

- ApiShip must still calculate the real rate.
- Customer-facing shipping cost must be 0.
- The rate receives `yabao_apiship_actual_cost` metadata with the calculated carrier cost.
- WooCommerce total must not add shipping to the buyer.

### C. ApiShip disabled / unavailable

- Existing Stage 68 fallback must remain functional.
- No fatal error or broken checkout is acceptable.

## Scope boundary

This commit is intentionally a PoC. Do not merge into `main` until real ApiShip rates and PVZ controls pass local checkout QA.

After the PoC we will:

- move the selected PVZ control into the Ya Bao delivery card;
- normalize provider/tariff/price/ETA presentation in the site design;
- confirm how actual free-shipping carrier cost is persisted in the final WooCommerce order;
- decide final carrier set and remove temporary/manual carrier entries that ApiShip replaces;
- keep the Stage 68 manual quote flow only as an operational fallback.
