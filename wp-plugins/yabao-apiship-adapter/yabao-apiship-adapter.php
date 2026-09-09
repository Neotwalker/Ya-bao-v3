<?php
/**
 * Plugin Name: Ya Bao × ApiShip Adapter
 * Description: Companion layer between the official ApiShip WooCommerce plugin and the custom Ya Bao checkout.
 * Version: 0.1.2
 * Requires at least: 6.0
 * Requires PHP: 8.0
 * Requires Plugins: woocommerce
 * Text Domain: yabao-apiship
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const YABAO_APISHIP_ADAPTER_VERSION = '0.1.2';

/**
 * Stage 68.1 deliberately keeps ApiShip as a third-party dependency.
 * Never copy/fork ApiShip internals into this plugin: this adapter should only
 * consume public WooCommerce rates/hooks so the official plugin can be updated.
 */
function yabao_apiship_threshold(): float {
	return defined( 'YABAO_FREE_SHIPPING_THRESHOLD' )
		? (float) YABAO_FREE_SHIPPING_THRESHOLD
		: 5000.0;
}

function yabao_apiship_package_goods_total( array $package ): float {
	if ( function_exists( 'yabao_delivery_package_goods_total' ) ) {
		return (float) yabao_delivery_package_goods_total( $package );
	}

	$total = 0.0;
	foreach ( (array) ( $package['contents'] ?? array() ) as $item ) {
		$total += isset( $item['line_total'] ) ? (float) $item['line_total'] : 0.0;
	}
	return max( 0.0, $total );
}

/**
 * The Ya Bao checkout intentionally exposes one customer address only: the
 * billing fields are also the delivery destination. WooCommerce normally keeps
 * billing/shipping destinations in sync during update_order_review(), but the
 * custom Stage 68 shipping renderer can request packages again in the same AJAX
 * cycle. Make the package destination explicit from the freshest posted billing
 * data before ApiShip builds its calculator request.
 *
 * This stays in the companion adapter because it is integration glue, not a
 * change to the official ApiShip plugin.
 */
function yabao_apiship_checkout_posted_data(): array {
	$posted = array();

	if ( isset( $_POST['post_data'] ) && is_string( $_POST['post_data'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
		parse_str( wp_unslash( $_POST['post_data'] ), $posted ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
	}

	if ( empty( $posted ) ) {
		foreach ( array( 'billing_country', 'billing_state', 'billing_postcode', 'billing_city', 'billing_address_1', 'billing_address_2' ) as $key ) {
			if ( isset( $_POST[ $key ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
				$posted[ $key ] = wp_unslash( $_POST[ $key ] ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
			}
		}
	}

	return is_array( $posted ) ? $posted : array();
}

function yabao_apiship_destination_value( array $posted, string $posted_key, string $customer_getter, string $default = '' ): string {
	if ( isset( $posted[ $posted_key ] ) ) {
		return wc_clean( (string) $posted[ $posted_key ] );
	}

	if ( function_exists( 'WC' ) && WC()->customer && is_callable( array( WC()->customer, $customer_getter ) ) ) {
		return wc_clean( (string) WC()->customer->{$customer_getter}() );
	}

	return $default;
}

function yabao_apiship_sync_checkout_destination( array $packages ): array {
	if ( ! function_exists( 'WC' ) || ! WC()->customer ) {
		return $packages;
	}

	if ( is_admin() && ! wp_doing_ajax() ) {
		return $packages;
	}

	$posted = yabao_apiship_checkout_posted_data();

	$destination = array(
		'country'   => strtoupper( yabao_apiship_destination_value( $posted, 'billing_country', 'get_billing_country', 'RU' ) ?: 'RU' ),
		'state'     => yabao_apiship_destination_value( $posted, 'billing_state', 'get_billing_state' ),
		'postcode'  => yabao_apiship_destination_value( $posted, 'billing_postcode', 'get_billing_postcode' ),
		'city'      => yabao_apiship_destination_value( $posted, 'billing_city', 'get_billing_city' ),
		'address'   => yabao_apiship_destination_value( $posted, 'billing_address_1', 'get_billing_address_1' ),
		'address_2' => yabao_apiship_destination_value( $posted, 'billing_address_2', 'get_billing_address_2' ),
	);

	foreach ( $packages as &$package ) {
		if ( ! isset( $package['destination'] ) || ! is_array( $package['destination'] ) ) {
			$package['destination'] = array();
		}
		$package['destination'] = array_merge( $package['destination'], $destination );
	}
	unset( $package );

	return $packages;
}
add_filter( 'woocommerce_cart_shipping_packages', 'yabao_apiship_sync_checkout_destination', 90 );

/**
 * WC_Shipping_Rate meta is an associative array in current WooCommerce, but
 * keep a defensive fallback for versions used by third-party integrations.
 *
 * @return mixed
 */
function yabao_apiship_rate_meta( WC_Shipping_Rate $rate, string $key, $default = null ) {
	if ( method_exists( $rate, 'get_meta_data' ) ) {
		$meta = $rate->get_meta_data();
		if ( is_array( $meta ) && array_key_exists( $key, $meta ) ) {
			return $meta[ $key ];
		}
	}

	return $default;
}

function yabao_apiship_is_rate( $rate ): bool {
	return $rate instanceof WC_Shipping_Rate
		&& 'WPApiShip' === (string) yabao_apiship_rate_meta( $rate, 'integrator', '' );
}

/**
 * When ApiShip returns real rates, the Stage 68 all-or-nothing fallback no
 * longer injects the tea-room pickup option. Keep the approved store pickup
 * next to real ApiShip rates without duplicating any existing pickup method.
 */
function yabao_apiship_keep_store_pickup( array $rates, array $package ): array {
	if ( empty( $rates ) || ! class_exists( 'WC_Shipping_Rate' ) ) {
		return $rates;
	}

	$has_apiship = false;
	foreach ( $rates as $rate ) {
		if ( yabao_apiship_is_rate( $rate ) ) {
			$has_apiship = true;
		}
		if ( $rate instanceof WC_Shipping_Rate ) {
			$id = (string) $rate->get_id();
			if ( str_starts_with( $id, 'yabao_pickup' ) || str_contains( $id, 'local_pickup' ) ) {
				return $rates;
			}
		}
	}

	if ( ! $has_apiship ) {
		return $rates;
	}

	$pickup = new WC_Shipping_Rate(
		'yabao_pickup',
		'Самовывоз — Кирова, 94',
		0,
		array(),
		'yabao_pickup',
		0
	);
	$pickup->add_meta_data( 'yabao_kind', 'pickup' );

	return array( 'yabao_pickup' => $pickup ) + $rates;
}
add_filter( 'woocommerce_package_rates', 'yabao_apiship_keep_store_pickup', 115, 2 );

/**
 * Store rule: the buyer pays no delivery charge from 5,000 RUB.
 * ApiShip still calculates the real carrier cost; preserve that value in rate
 * metadata before zeroing the customer-facing WooCommerce shipping cost.
 */
function yabao_apiship_apply_free_shipping_rule( array $rates, array $package ): array {
	if ( empty( $rates ) || yabao_apiship_package_goods_total( $package ) < yabao_apiship_threshold() ) {
		return $rates;
	}

	foreach ( $rates as $rate ) {
		if ( ! yabao_apiship_is_rate( $rate ) ) {
			continue;
		}

		$actual_cost = max( 0.0, (float) $rate->get_cost() );
		$rate->add_meta_data( 'yabao_apiship_actual_cost', (string) $actual_cost );
		$rate->add_meta_data( 'yabao_apiship_customer_cost', '0' );
		$rate->set_cost( 0 );
		$rate->set_taxes( array() );
	}

	return $rates;
}
add_filter( 'woocommerce_package_rates', 'yabao_apiship_apply_free_shipping_rule', 120, 2 );

/**
 * Resolve the currently selected real ApiShip rate from calculated packages.
 *
 * @return array{rate:WC_Shipping_Rate,index:int}|null
 */
function yabao_apiship_selected_rate(): ?array {
	if ( ! function_exists( 'WC' ) || ! WC()->shipping() || ! WC()->session ) {
		return null;
	}

	$packages = WC()->shipping()->get_packages();
	$chosen   = (array) WC()->session->get( 'chosen_shipping_methods', array() );

	foreach ( (array) $packages as $index => $package ) {
		$rates       = (array) ( $package['rates'] ?? array() );
		$selected_id = isset( $chosen[ $index ] ) ? (string) $chosen[ $index ] : '';

		if ( '' === $selected_id || ! isset( $rates[ $selected_id ] ) ) {
			continue;
		}

		$rate = $rates[ $selected_id ];
		if ( yabao_apiship_is_rate( $rate ) ) {
			return array(
				'rate'  => $rate,
				'index' => (int) $index,
			);
		}
	}

	return null;
}

function yabao_apiship_days_text( WC_Shipping_Rate $rate ): string {
	$min = absint( yabao_apiship_rate_meta( $rate, 'daysMin', 0 ) );
	$max = absint( yabao_apiship_rate_meta( $rate, 'daysMax', 0 ) );

	if ( $min && $max && $max !== $min ) {
		return sprintf( '%d–%d дн.', $min, $max );
	}
	if ( $min ) {
		return sprintf( '%d дн.', $min );
	}
	if ( $max ) {
		return sprintf( 'до %d дн.', $max );
	}
	return '';
}

/**
 * PoC bridge for ApiShip pickup-point UI.
 *
 * The Ya Bao theme renders its own shipping cards and therefore does not call
 * WooCommerce's standard `woocommerce_after_shipping_rate` hook per rate.
 * ApiShip uses that hook for its PVZ controls. For the PoC we replay the hook
 * once for the selected ApiShip rate below the custom shipping list. If this
 * proves stable, Stage 68.1 will move the control inside the selected card.
 */
function yabao_apiship_render_poc_controls(): void {
	if ( ! function_exists( 'is_checkout' ) || ! is_checkout() || is_order_received_page() ) {
		return;
	}

	$selected = yabao_apiship_selected_rate();
	if ( ! $selected ) {
		return;
	}

	/** @var WC_Shipping_Rate $rate */
	$rate = $selected['rate'];
	$days = yabao_apiship_days_text( $rate );
	$cost = (float) $rate->get_cost();

	echo '<section class="yabao-apiship-poc" aria-labelledby="yabao-apiship-poc-title">';
	echo '<div class="yabao-apiship-poc__head">';
	echo '<strong id="yabao-apiship-poc-title">ApiShip подключён</strong>';
	echo '<span>PoC Stage 68.1</span>';
	echo '</div>';

	echo '<p class="yabao-apiship-poc__summary">';
	echo esc_html( $rate->get_label() );
	if ( $days ) {
		echo ' · ' . esc_html( $days );
	}
	echo ' · ';
	if ( $cost <= 0.0 ) {
		echo '<strong>бесплатно для покупателя</strong>';
	} else {
		echo '<strong>' . wp_kses_post( wc_price( $cost ) ) . '</strong>';
	}
	echo '</p>';

	ob_start();
	do_action( 'woocommerce_after_shipping_rate', $rate, $selected['index'] );
	$controls = trim( (string) ob_get_clean() );

	if ( '' !== $controls ) {
		// Trusted output from the official shipping plugin hook. Re-sanitizing it
		// with wp_kses_post() can remove hidden inputs/data attributes used by PVZ.
		echo '<div class="yabao-apiship-poc__controls">' . $controls . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	} else {
		echo '<p class="yabao-apiship-poc__note">Тариф ApiShip получен. Для этого тарифа дополнительный выбор ПВЗ не требуется или модуль не вывел контрол.</p>';
	}

	echo '</section>';
}
add_action( 'woocommerce_review_order_before_payment', 'yabao_apiship_render_poc_controls', 5 );

/**
 * Temporary PoC styling. Final Stage 68.1 styling will live with the Ya Bao
 * checkout after we see the real ApiShip/PVZ markup on the local site.
 */
function yabao_apiship_enqueue_poc_styles(): void {
	if ( ! function_exists( 'is_checkout' ) || ! is_checkout() || is_order_received_page() ) {
		return;
	}

	$css = '
	.yabao-apiship-poc{margin:14px 0;padding:14px 16px;border:1px solid rgba(31,49,40,.14);border-radius:14px;background:#fff}
	.yabao-apiship-poc__head{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:6px}
	.yabao-apiship-poc__head strong{font-size:14px}.yabao-apiship-poc__head span{font-size:11px;opacity:.55}
	.yabao-apiship-poc__summary,.yabao-apiship-poc__note{margin:0;font-size:12px;line-height:1.45;color:rgba(31,49,40,.72)}
	.yabao-apiship-poc__controls{margin-top:10px}.yabao-apiship-poc__controls button,.yabao-apiship-poc__controls a{max-width:100%}
	';

	if ( wp_style_is( 'yabao-delivery', 'enqueued' ) ) {
		wp_add_inline_style( 'yabao-delivery', $css );
	} else {
		wp_register_style( 'yabao-apiship-poc', false, array(), YABAO_APISHIP_ADAPTER_VERSION );
		wp_enqueue_style( 'yabao-apiship-poc' );
		wp_add_inline_style( 'yabao-apiship-poc', $css );
	}
}
add_action( 'wp_enqueue_scripts', 'yabao_apiship_enqueue_poc_styles', 80 );
