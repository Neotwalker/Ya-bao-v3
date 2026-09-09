<?php
/**
 * Temporary Stage 68.1 stabilizer for the ApiShip PoC.
 *
 * Two behaviours are intentionally scoped to the current Ya Bao checkout:
 * 1) the store does not offer cash on delivery, so ApiShip calculator requests
 *    must always use codCost = 0 even when WooCommerce posts no payment method;
 * 2) ApiShip rate labels contain helper HTML spans intended for the stock Woo
 *    template, while the custom Ya Bao renderer escapes labels as plain text.
 *
 * Remove this MU plugin after the behaviour is folded into the permanent
 * Ya Bao × ApiShip adapter.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Stage 68/69 payment model is prepaid or "calculate first, pay later".
 * There is no COD gateway. ApiShip's official calculator treats an empty or
 * unrecognised payment method as COD, which makes test CDEK tariffs disappear
 * after the first real rate is selected and creates a fallback/real-rate loop.
 */
function yabao_apiship_poc_force_non_cod( array $args, string $url ): array {
	$host = (string) wp_parse_url( $url, PHP_URL_HOST );
	$path = (string) wp_parse_url( $url, PHP_URL_PATH );

	if ( ! str_ends_with( $host, 'apiship.ru' ) || ! str_ends_with( rtrim( $path, '/' ), '/calculator' ) ) {
		return $args;
	}

	if ( defined( 'YABAO_APISHIP_ENABLE_COD' ) && YABAO_APISHIP_ENABLE_COD ) {
		return $args;
	}

	if ( ! isset( $args['body'] ) || ! is_string( $args['body'] ) ) {
		return $args;
	}

	$body = json_decode( $args['body'], true );
	if ( ! is_array( $body ) ) {
		return $args;
	}

	$old_cod = isset( $body['codCost'] ) ? (float) $body['codCost'] : 0.0;
	$body['codCost'] = 0;
	$args['body'] = wp_json_encode( $body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );

	if ( $old_cod > 0 && function_exists( 'yabao_apiship_debug_log' ) ) {
		yabao_apiship_debug_log(
			'ApiShip COD normalized',
			array(
				'from' => $old_cod,
				'to'   => 0,
			)
		);
	}

	return $args;
}
add_filter( 'http_request_args', 'yabao_apiship_poc_force_non_cod', 1001, 2 );

function yabao_apiship_poc_rate_meta( WC_Shipping_Rate $rate ): array {
	$meta = method_exists( $rate, 'get_meta_data' ) ? $rate->get_meta_data() : array();
	return is_array( $meta ) ? $meta : array();
}

function yabao_apiship_poc_is_rate( $rate ): bool {
	if ( ! $rate instanceof WC_Shipping_Rate ) {
		return false;
	}
	$meta = yabao_apiship_poc_rate_meta( $rate );
	return isset( $meta['integrator'] ) && 'WPApiShip' === (string) $meta['integrator'];
}

/**
 * Keep the buyer on CDEK when the old manual Stage 68 CDEK placeholder is
 * replaced by real ApiShip rates during the same checkout AJAX cycle.
 */
function yabao_apiship_poc_preserve_cdek_selection( array $rates, array $package ): array {
	if ( empty( $rates ) || ! function_exists( 'WC' ) || ! WC()->session ) {
		return $rates;
	}

	$chosen = (array) WC()->session->get( 'chosen_shipping_methods', array() );
	$current = isset( $chosen[0] ) ? (string) $chosen[0] : '';
	if ( 'yabao_delivery_cdek' !== $current ) {
		return $rates;
	}

	foreach ( $rates as $rate ) {
		if ( ! yabao_apiship_poc_is_rate( $rate ) ) {
			continue;
		}
		$meta = yabao_apiship_poc_rate_meta( $rate );
		if ( 'cdek' !== strtolower( (string) ( $meta['tariffProviderKey'] ?? '' ) ) ) {
			continue;
		}
		$chosen[0] = (string) $rate->get_id();
		WC()->session->set( 'chosen_shipping_methods', $chosen );
		break;
	}

	return $rates;
}
add_filter( 'woocommerce_package_rates', 'yabao_apiship_poc_preserve_cdek_selection', 125, 2 );

/**
 * The official ApiShip label contains helper <span> tags for its stock checkout
 * UI. Ya Bao deliberately renders a compact text-only shipping card, so replace
 * only ApiShip labels with a clean customer-facing string while keeping every
 * original rate id/meta/cost untouched.
 */
function yabao_apiship_poc_clean_labels( array $rates, array $package ): array {
	$provider_names = array(
		'cdek'      => 'СДЭК',
		'x5'        => '5Post',
		'ozonlog'   => 'Ozon Доставка',
		'dostavista'=> 'Dostavista',
		'cse'       => 'CSE',
		'pony'      => 'Pony Express',
		'yataxi'    => 'Яндекс Доставка',
	);

	foreach ( $rates as $rate ) {
		if ( ! yabao_apiship_poc_is_rate( $rate ) ) {
			continue;
		}

		$meta = yabao_apiship_poc_rate_meta( $rate );
		$key = strtolower( (string) ( $meta['tariffProviderKey'] ?? '' ) );
		$provider = $provider_names[ $key ] ?? strtoupper( $key ?: 'Доставка' );
		$service = wp_strip_all_tags( (string) ( $meta['tariffName'] ?? '' ) );
		if ( '' !== $key && '' !== $service ) {
			$service = preg_replace( '/^' . preg_quote( $key, '/' ) . '\s*[-–—]\s*/iu', '', $service );
		}
		$service = trim( preg_replace( '/\s+/u', ' ', (string) $service ) );
		if ( '' === $service ) {
			$service = 'Доставка';
		}

		$label = $provider . ' — ' . $service;
		$cost = max( 0.0, (float) $rate->get_cost() );
		$label .= $cost > 0
			? ' · ' . trim( wp_strip_all_tags( wc_price( $cost ) ) )
			: ' · бесплатно';

		$min = absint( $meta['daysMin'] ?? 0 );
		$max = absint( $meta['daysMax'] ?? 0 );
		if ( $min && $max && $min !== $max ) {
			$label .= sprintf( ' · %d–%d дней', $min, $max );
		} elseif ( $min ) {
			$label .= sprintf( ' · %d дней', $min );
		}

		$rate->set_label( $label );
	}

	return $rates;
}
add_filter( 'woocommerce_package_rates', 'yabao_apiship_poc_clean_labels', 130, 2 );
