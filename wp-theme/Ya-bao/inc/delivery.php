<?php
/**
 * Stage 68: delivery and pickup rules.
 *
 * Real commercial terms supplied by the store owner:
 * - pickup: Chelyabinsk, Kirova 94, 12:00-01:00;
 * - carriers: Avito Delivery, CDEK, 5Post, Russian Post;
 * - free carrier delivery from 5,000 RUB;
 * - delivery time: usually 2-10 days;
 * - below 5,000 RUB the carrier tariff is confirmed before payment.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const YABAO_FREE_SHIPPING_THRESHOLD = 5000.0;

/**
 * Reuse the approved Stage 60 delivery styles on the WordPress delivery page,
 * and load the small checkout integration layer only where it is needed.
 */
function yabao_delivery_enqueue_assets(): void {
	if ( is_page( 'delivery' ) ) {
		wp_enqueue_style(
			'yabao-shop',
			yabao_asset_url( 'css/shop.css' ),
			array( 'yabao-pages' ),
			yabao_asset_version( 'css/shop.css' )
		);
	}

	if ( ! function_exists( 'is_checkout' ) || ! is_checkout() || is_order_received_page() ) {
		return;
	}

	wp_enqueue_style(
		'yabao-delivery',
		yabao_asset_url( 'css/wp-delivery.css' ),
		array( 'yabao-shop', 'yabao-wp' ),
		yabao_asset_version( 'css/wp-delivery.css' )
	);
	wp_enqueue_script(
		'yabao-delivery',
		yabao_asset_url( 'js/wp-delivery.js' ),
		array( 'jquery', 'wc-checkout' ),
		yabao_asset_version( 'js/wp-delivery.js' ),
		true
	);
}
add_action( 'wp_enqueue_scripts', 'yabao_delivery_enqueue_assets', 40 );

function yabao_delivery_body_classes( array $classes ): array {
	if ( is_page( 'delivery' ) ) {
		$classes[] = 'page-shop';
		$classes[] = 'page-delivery';
		$classes[] = 'page-inner';
	}
	return array_values( array_unique( $classes ) );
}
add_filter( 'body_class', 'yabao_delivery_body_classes', 30 );

function yabao_delivery_package_goods_total( array $package ): float {
	$total = 0.0;
	foreach ( (array) ( $package['contents'] ?? array() ) as $item ) {
		$total += isset( $item['line_total'] ) ? (float) $item['line_total'] : 0.0;
	}
	return max( 0.0, $total );
}

function yabao_delivery_cart_goods_total(): float {
	if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
		return 0.0;
	}
	return max( 0.0, (float) WC()->cart->get_cart_contents_total() );
}

/**
 * WooCommerce 11 returns false from WC_Cart::needs_shipping() when the store
 * has zero configured shipping methods, before product state is inspected.
 * Stage 68 has code-owned fallback fulfillment methods, so use the physical
 * cart contents as the source of truth instead of Woo's method-count gate.
 */
function yabao_delivery_cart_requires_fulfilment(): bool {
	if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
		return false;
	}

	foreach ( WC()->cart->get_cart() as $item ) {
		$product = $item['data'] ?? null;
		if ( $product instanceof WC_Product && $product->needs_shipping() ) {
			return true;
		}
	}

	return false;
}

function yabao_delivery_is_pickup_method( string $method_id ): bool {
	return str_starts_with( $method_id, 'yabao_pickup' ) || str_contains( $method_id, 'local_pickup' );
}

function yabao_delivery_is_manual_carrier_method( string $method_id ): bool {
	return str_starts_with( $method_id, 'yabao_delivery_' );
}

function yabao_delivery_method_needs_quote( string $method_id, ?float $goods_total = null ): bool {
	$total = null === $goods_total ? yabao_delivery_cart_goods_total() : $goods_total;
	return yabao_delivery_is_manual_carrier_method( $method_id ) && $total < YABAO_FREE_SHIPPING_THRESHOLD;
}

function yabao_delivery_selected_method_id(): string {
	if ( isset( $_POST['shipping_method'] ) ) {
		$posted = (array) wp_unslash( $_POST['shipping_method'] );
		$first  = reset( $posted );
		if ( is_string( $first ) && '' !== $first ) {
			return wc_clean( $first );
		}
	}

	if ( function_exists( 'WC' ) && WC()->session ) {
		$chosen = (array) WC()->session->get( 'chosen_shipping_methods', array() );
		if ( isset( $chosen[0] ) ) {
			return wc_clean( (string) $chosen[0] );
		}
	}

	return '';
}

/**
 * Built-in fallback for Stage 68.
 *
 * Once a real carrier plugin or configured WooCommerce shipping zone returns
 * rates, those rates win and this fallback deliberately adds nothing.
 */
function yabao_delivery_add_fallback_rates( array $rates, array $package ): array {
	if ( ! empty( $rates ) || ! class_exists( 'WC_Shipping_Rate' ) ) {
		return $rates;
	}

	$country = strtoupper( (string) ( $package['destination']['country'] ?? 'RU' ) );
	if ( 'RU' !== $country ) {
		return $rates;
	}

	$goods_total = yabao_delivery_package_goods_total( $package );
	$is_free     = $goods_total >= YABAO_FREE_SHIPPING_THRESHOLD;

	$pickup = new WC_Shipping_Rate(
		'yabao_pickup',
		'Самовывоз — Кирова, 94',
		0,
		array(),
		'yabao_pickup',
		0
	);
	$pickup->add_meta_data( 'yabao_kind', 'pickup' );
	$rates['yabao_pickup'] = $pickup;

	$carriers = array(
		'avito'        => 'Авито Доставка',
		'cdek'         => 'СДЭК',
		'5post'        => '5Post',
		'russian_post' => 'Почта России',
	);

	foreach ( $carriers as $slug => $name ) {
		$id    = 'yabao_delivery_' . $slug;
		$label = $is_free ? $name . ' — бесплатно' : $name . ' — тариф после оформления';
		$rate  = new WC_Shipping_Rate( $id, $label, 0, array(), 'yabao_delivery', 0 );
		$rate->add_meta_data( 'yabao_kind', 'delivery' );
		$rate->add_meta_data( 'yabao_carrier', $name );
		$rate->add_meta_data( 'yabao_quote_pending', $is_free ? 'no' : 'yes' );
		$rates[ $id ] = $rate;
	}

	return $rates;
}
add_filter( 'woocommerce_package_rates', 'yabao_delivery_add_fallback_rates', 100, 2 );

/**
 * Return only the shipping packages WooCommerce has already calculated.
 * Rendering checkout HTML must not initiate network calculations or mutate
 * checkout session state.
 */
function yabao_delivery_current_packages(): array {
	if ( ! function_exists( 'WC' ) || ! WC()->shipping() ) {
		return array();
	}

	$packages = WC()->shipping()->get_packages();
	return is_array( $packages ) ? $packages : array();
}

/**
 * Prepare the Stage 68 fallback before the order-review template is rendered.
 *
 * Normally WooCommerce calculates shipping itself. The only exceptional path
 * is a store with zero configured methods, where WC_Cart::needs_shipping()
 * short-circuits before our code-owned fallback can run. Handle that once in
 * the totals lifecycle, then let WooCommerce normalize the chosen method.
 */
function yabao_delivery_prepare_checkout_shipping( WC_Cart $cart ): void {
	$checkout_context = ( function_exists( 'is_checkout' ) && is_checkout() )
		|| ( defined( 'WOOCOMMERCE_CHECKOUT' ) && WOOCOMMERCE_CHECKOUT );

	if (
		! $checkout_context ||
		! yabao_delivery_cart_requires_fulfilment() ||
		! function_exists( 'WC' ) ||
		! WC()->shipping()
	) {
		return;
	}

	$packages = yabao_delivery_current_packages();

	if ( empty( $packages ) ) {
		$packages = $cart->get_shipping_packages();
		if ( empty( $packages ) ) {
			return;
		}

		foreach ( $packages as &$package ) {
			if ( ! isset( $package['destination'] ) || ! is_array( $package['destination'] ) ) {
				$package['destination'] = array();
			}
			$package['destination']['country'] = 'RU';
		}
		unset( $package );

		$packages = WC()->shipping()->calculate_shipping( $packages );
	}

	if (
		empty( $packages ) ||
		! WC()->session ||
		! function_exists( 'wc_get_chosen_shipping_method_for_package' )
	) {
		return;
	}

	$chosen_methods = (array) WC()->session->get( 'chosen_shipping_methods', array() );
	foreach ( $packages as $index => $package ) {
		$rates  = isset( $package['rates'] ) && is_array( $package['rates'] ) ? $package['rates'] : array();
		$chosen = isset( $chosen_methods[ $index ] ) ? (string) $chosen_methods[ $index ] : '';

		if ( empty( $rates ) || ( '' !== $chosen && isset( $rates[ $chosen ] ) ) ) {
			continue;
		}

		wc_get_chosen_shipping_method_for_package( $index, $package );
	}
}
add_action( 'woocommerce_after_calculate_totals', 'yabao_delivery_prepare_checkout_shipping', 40 );

function yabao_delivery_rate_detail( WC_Shipping_Rate $rate, float $goods_total ): string {
	$id = (string) $rate->get_id();

	if ( yabao_delivery_is_pickup_method( $id ) ) {
		return 'Челябинск, ул. Кирова, 94 · ежедневно 12:00–01:00';
	}

	if ( yabao_delivery_is_manual_carrier_method( $id ) ) {
		if ( $goods_total >= YABAO_FREE_SHIPPING_THRESHOLD ) {
			return 'Обычно 2–10 дней · доставка бесплатная при сумме товаров от 5 000 ₽.';
		}
		return 'Обычно 2–10 дней · стоимость по тарифу службы подтвердим до оплаты.';
	}

	return 'Стоимость и срок рассчитаны выбранной службой доставки.';
}

/**
 * Render shipping methods inside the custom order-review template.
 * This function is deliberately read-only: no rate calculation and no session
 * writes belong in the view layer.
 */
function yabao_delivery_render_checkout_shipping(): void {
	if ( ! function_exists( 'WC' ) || ! WC()->cart || ! yabao_delivery_cart_requires_fulfilment() ) {
		return;
	}

	$packages = yabao_delivery_current_packages();
	if ( empty( $packages ) ) {
		return;
	}

	$chosen_methods   = WC()->session ? (array) WC()->session->get( 'chosen_shipping_methods', array() ) : array();
	$posted_method    = yabao_delivery_selected_method_id();
	$rendered_methods = array();
	$goods_total      = yabao_delivery_cart_goods_total();

	echo '<section class="checkout-summary__shipping" aria-labelledby="yabao-shipping-title">';
	echo '<div class="checkout-summary__shipping-heading"><span class="eyebrow">Получение</span><strong id="yabao-shipping-title">Способ получения</strong></div>';

	foreach ( $packages as $index => $package ) {
		$rates = (array) ( $package['rates'] ?? array() );
		if ( empty( $rates ) ) {
			echo '<p class="checkout-summary__delivery-note">Для текущего адреса способы доставки пока недоступны.</p>';
			continue;
		}

		$chosen = isset( $chosen_methods[ $index ] ) ? (string) $chosen_methods[ $index ] : '';
		if ( 0 === (int) $index && '' !== $posted_method && isset( $rates[ $posted_method ] ) ) {
			$chosen = $posted_method;
		}
		if ( '' === $chosen || ! isset( $rates[ $chosen ] ) ) {
			$chosen = (string) array_key_first( $rates );
		}
		$rendered_methods[ $index ] = $chosen;

		echo '<div class="checkout-choices checkout-choices--shipping">';
		foreach ( $rates as $rate ) {
			if ( ! $rate instanceof WC_Shipping_Rate ) {
				continue;
			}

			$rate_id  = (string) $rate->get_id();
			$input_id = 'shipping_method_' . absint( $index ) . '_' . sanitize_title( $rate_id );
			$detail   = yabao_delivery_rate_detail( $rate, $goods_total );

			printf(
				'<label class="checkout-choice" for="%1$s"><input class="shipping_method" type="radio" name="shipping_method[%2$d]" data-index="%2$d" id="%1$s" value="%3$s"%4$s><span><strong>%5$s</strong><small>%6$s</small></span></label>',
				esc_attr( $input_id ),
				absint( $index ),
				esc_attr( $rate_id ),
				checked( $rate_id, $chosen, false ),
				esc_html( $rate->get_label() ),
				esc_html( $detail )
			);
		}
		echo '</div>';
	}

	$selected = isset( $rendered_methods[0] ) ? (string) $rendered_methods[0] : '';

	if ( yabao_delivery_method_needs_quote( $selected, $goods_total ) ) {
		echo '<p class="checkout-shipping-quote"><strong>Стоимость доставки рассчитывается отдельно.</strong> Заказ будет сохранён без списания денег. После расчёта тарифа магазин подтвердит полную сумму до оплаты.</p>';
	} elseif ( '' !== $selected && ! yabao_delivery_is_pickup_method( $selected ) && $goods_total >= YABAO_FREE_SHIPPING_THRESHOLD ) {
		echo '<p class="checkout-shipping-quote checkout-shipping-quote--free"><strong>Бесплатная доставка.</strong> Порог 5 000 ₽ проверяется сервером по стоимости товаров после скидок.</p>';
	}

	echo '</section>';
}

/**
 * Pickup does not require a delivery address. For carrier delivery the server
 * validation below is authoritative; JavaScript only controls presentation.
 */
function yabao_delivery_checkout_fields( array $fields ): array {
	foreach ( array( 'billing_address_1', 'billing_address_2', 'billing_city', 'billing_state', 'billing_postcode' ) as $key ) {
		if ( isset( $fields['billing'][ $key ] ) ) {
			$fields['billing'][ $key ]['required'] = false;
		}
	}
	return $fields;
}
add_filter( 'woocommerce_checkout_fields', 'yabao_delivery_checkout_fields', 30 );

function yabao_delivery_validate_checkout( array $data, WP_Error $errors ): void {
	$method = yabao_delivery_selected_method_id();
	if ( '' === $method || yabao_delivery_is_pickup_method( $method ) ) {
		return;
	}

	$required = array(
		'billing_address_1' => 'Укажите адрес доставки.',
		'billing_city'      => 'Укажите город доставки.',
		'billing_postcode'  => 'Укажите почтовый индекс.',
	);

	foreach ( $required as $key => $message ) {
		if ( empty( trim( (string) ( $data[ $key ] ?? '' ) ) ) {
			$errors->add( 'yabao_' . $key, $message );
		}
	}
}
add_action( 'woocommerce_after_checkout_validation', 'yabao_delivery_validate_checkout', 30, 2 );
