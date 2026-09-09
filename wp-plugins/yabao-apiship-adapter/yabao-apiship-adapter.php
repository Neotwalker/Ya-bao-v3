<?php
/**
 * Plugin Name: Ya Bao × ApiShip Adapter
 * Description: Companion layer between the official ApiShip WooCommerce plugin and the custom Ya Bao checkout.
 * Version: 0.1.0
 * Requires at least: 6.0
 * Requires PHP: 8.0
 * Requires Plugins: woocommerce
 * Text Domain: yabao-apiship
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const YABAO_APISHIP_ADAPTER_VERSION = '0.1.0';

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
 * WC_Shipping_Rate meta is an associative array in current WooCommerce, but
 * keep a defensive fallback for older versions used by third-party plugins.
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

	if ( isset( $rate->meta_data ) && is_array( $rate->meta_data ) && array_key_exists( $key, $rate->meta_data ) ) {
		return $rate->meta_data[ $key ];
	}

	return $default;
}

function yabao_apiship_is_rate( $rate ): bool {
	return $rate instanceof WC_Shipping_Rate
		&& 'WPApiShip' === (string) yabao_apiship_rate_meta( $rate, 'integrator', '' );
}

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
		$rates      = (array) ( $package['rates'] ?? array() );
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
		echo '<div class="yabao-apiship-poc__controls">' . wp_kses_post( $controls ) . '</div>';
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
