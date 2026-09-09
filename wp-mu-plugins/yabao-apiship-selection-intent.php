<?php
/**
 * Temporary Stage 68.1 bridge: preserve the buyer's carrier intent while a
 * manual fallback rate is replaced by a real ApiShip rate in the same checkout
 * AJAX request.
 *
 * Remove after this behaviour is folded into the permanent ApiShip adapter.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Read shipping methods exactly as the current checkout request posted them.
 * WooCommerce may already have changed its session selection by the time
 * `woocommerce_package_rates` runs, so the posted value is the source of truth
 * for the fallback -> real-rate transition.
 *
 * @return array<int|string,string>
 */
function yabao_apiship_selection_intent_posted_methods(): array {
	$methods = array();

	if ( isset( $_POST['shipping_method'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$raw = wp_unslash( $_POST['shipping_method'] ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( is_array( $raw ) ) {
			foreach ( $raw as $index => $method ) {
				if ( is_scalar( $method ) ) {
					$methods[ $index ] = wc_clean( (string) $method );
				}
			}
		} elseif ( is_scalar( $raw ) ) {
			$methods[0] = wc_clean( (string) $raw );
		}
	}

	if ( empty( $methods ) && isset( $_POST['post_data'] ) && is_string( $_POST['post_data'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$posted = array();
		parse_str( wp_unslash( $_POST['post_data'] ), $posted ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$raw = $posted['shipping_method'] ?? array();
		if ( is_array( $raw ) ) {
			foreach ( $raw as $index => $method ) {
				if ( is_scalar( $method ) ) {
					$methods[ $index ] = wc_clean( (string) $method );
				}
			}
		} elseif ( is_scalar( $raw ) ) {
			$methods[0] = wc_clean( (string) $raw );
		}
	}

	return $methods;
}

function yabao_apiship_selection_intent_is_cdek_rate( $rate ): bool {
	if ( ! $rate instanceof WC_Shipping_Rate || ! method_exists( $rate, 'get_meta_data' ) ) {
		return false;
	}

	$meta = $rate->get_meta_data();
	if ( ! is_array( $meta ) ) {
		return false;
	}

	return 'WPApiShip' === (string) ( $meta['integrator'] ?? '' )
		&& 'cdek' === strtolower( (string) ( $meta['tariffProviderKey'] ?? '' ) );
}

/**
 * If the buyer explicitly selected the old Stage 68 CDEK placeholder, preserve
 * that intent when ApiShip returns real CDEK rates after the address becomes
 * calculable. Without this bridge WooCommerce falls back to array_key_first(),
 * which is the store pickup rate.
 */
function yabao_apiship_selection_intent_promote_cdek( array $rates, array $package ): array {
	if ( empty( $rates ) || ! function_exists( 'WC' ) || ! WC()->session || ! class_exists( 'WC_Shipping_Rate' ) ) {
		return $rates;
	}

	$posted = yabao_apiship_selection_intent_posted_methods();
	$target_index = null;
	foreach ( $posted as $index => $method_id ) {
		if ( 'yabao_delivery_cdek' === $method_id ) {
			$target_index = $index;
			break;
		}
	}

	if ( null === $target_index ) {
		return $rates;
	}

	$real_cdek_id = '';
	foreach ( $rates as $rate ) {
		if ( yabao_apiship_selection_intent_is_cdek_rate( $rate ) ) {
			$real_cdek_id = (string) $rate->get_id();
			break;
		}
	}

	if ( '' === $real_cdek_id ) {
		return $rates;
	}

	$chosen = (array) WC()->session->get( 'chosen_shipping_methods', array() );
	$chosen[ $target_index ] = $real_cdek_id;
	WC()->session->set( 'chosen_shipping_methods', $chosen );

	if ( function_exists( 'yabao_apiship_debug_log' ) ) {
		yabao_apiship_debug_log(
			'CDEK placeholder intent promoted',
			array(
				'package_index' => $target_index,
				'from'          => 'yabao_delivery_cdek',
				'to'            => $real_cdek_id,
			)
		);
	}

	return $rates;
}
add_filter( 'woocommerce_package_rates', 'yabao_apiship_selection_intent_promote_cdek', 124, 2 );
