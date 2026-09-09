<?php
/**
 * Temporary Stage 68.1 stabilizer for the ApiShip PoC.
 *
 * Behaviours are intentionally scoped to the current Ya Bao checkout:
 * 1) the store does not offer cash on delivery, so ApiShip calculator requests
 *    must always use codCost = 0 even when WooCommerce posts no payment method;
 * 2) guest delivery-address state is kept in the WooCommerce session so a full
 *    checkout reload can calculate the same real rates before the first AJAX;
 * 3) technically duplicated ApiShip tariffs are collapsed to one buyer-facing
 *    choice while preferring the tea room's configured point drop-off model;
 * 4) ApiShip helper HTML in labels is replaced by compact text for Ya Bao cards.
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

/**
 * Keep the delivery destination in the WooCommerce session only. This avoids a
 * full-page reload reverting to an empty package/fallback list and then visibly
 * jumping to real ApiShip rates on the first update_order_review request.
 */
function yabao_apiship_poc_capture_checkout_draft( string $post_data ): void {
	if ( ! function_exists( 'WC' ) || ! WC()->session || '' === $post_data ) {
		return;
	}

	$posted = array();
	parse_str( $post_data, $posted );
	if ( ! is_array( $posted ) ) {
		return;
	}

	$fields = array(
		'billing_country',
		'billing_state',
		'billing_postcode',
		'billing_city',
		'billing_address_1',
		'billing_address_2',
	);
	$draft = array();

	foreach ( $fields as $field ) {
		if ( array_key_exists( $field, $posted ) ) {
			$draft[ $field ] = wc_clean( (string) $posted[ $field ] );
		}
	}

	if ( ! empty( $draft ) ) {
		WC()->session->set( 'yabao_apiship_checkout_draft', $draft );
	}
}
add_action( 'woocommerce_checkout_update_order_review', 'yabao_apiship_poc_capture_checkout_draft', 5 );

function yabao_apiship_poc_checkout_value( $value, string $input ) {
	if ( null !== $value && '' !== $value ) {
		return $value;
	}
	if ( ! function_exists( 'WC' ) || ! WC()->session ) {
		return $value;
	}

	$draft = (array) WC()->session->get( 'yabao_apiship_checkout_draft', array() );
	if ( ! array_key_exists( $input, $draft ) ) {
		return $value;
	}

	return $draft[ $input ];
}
add_filter( 'woocommerce_checkout_get_value', 'yabao_apiship_poc_checkout_value', 20, 2 );

function yabao_apiship_poc_restore_destination_from_draft( array $packages ): array {
	if ( ! function_exists( 'WC' ) || ! WC()->session ) {
		return $packages;
	}

	// Fresh AJAX form values are already handled by the permanent adapter. The
	// session draft is only a reload/bootstrap fallback.
	if ( isset( $_POST['post_data'] ) && is_string( $_POST['post_data'] ) && '' !== $_POST['post_data'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
		return $packages;
	}

	$draft = (array) WC()->session->get( 'yabao_apiship_checkout_draft', array() );
	if ( empty( $draft ) ) {
		return $packages;
	}

	$destination = array(
		'country'   => strtoupper( (string) ( $draft['billing_country'] ?? 'RU' ) ) ?: 'RU',
		'state'     => (string) ( $draft['billing_state'] ?? '' ),
		'postcode'  => (string) ( $draft['billing_postcode'] ?? '' ),
		'city'      => (string) ( $draft['billing_city'] ?? '' ),
		'address'   => (string) ( $draft['billing_address_1'] ?? '' ),
		'address_1' => (string) ( $draft['billing_address_1'] ?? '' ),
		'address_2' => (string) ( $draft['billing_address_2'] ?? '' ),
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
add_filter( 'woocommerce_cart_shipping_packages', 'yabao_apiship_poc_restore_destination_from_draft', 91 );

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

function yabao_apiship_poc_tariff_payload( WC_Shipping_Rate $rate ): array {
	$meta = yabao_apiship_poc_rate_meta( $rate );
	$raw  = $meta['tariff'] ?? '';

	if ( is_array( $raw ) ) {
		return $raw;
	}
	if ( ! is_string( $raw ) || '' === $raw ) {
		return array();
	}

	$decoded = json_decode( $raw, true );
	return is_array( $decoded ) ? $decoded : array();
}

function yabao_apiship_poc_normalized_service( WC_Shipping_Rate $rate ): string {
	$meta = yabao_apiship_poc_rate_meta( $rate );
	$key  = strtolower( (string) ( $meta['tariffProviderKey'] ?? '' ) );
	$name = wp_strip_all_tags( (string) ( $meta['tariffName'] ?? $rate->get_label() ) );

	if ( '' !== $key && '' !== $name ) {
		$name = preg_replace( '/^' . preg_quote( $key, '/' ) . '\s*[-–—]\s*/iu', '', $name );
	}

	return mb_strtolower( trim( preg_replace( '/\s+/u', ' ', (string) $name ) ) );
}

function yabao_apiship_poc_actual_cost( WC_Shipping_Rate $rate ): float {
	$meta = yabao_apiship_poc_rate_meta( $rate );
	if ( isset( $meta['yabao_apiship_actual_cost'] ) ) {
		return max( 0.0, (float) $meta['yabao_apiship_actual_cost'] );
	}
	return max( 0.0, (float) $rate->get_cost() );
}

function yabao_apiship_poc_rate_rank( WC_Shipping_Rate $rate ): array {
	$payload   = yabao_apiship_poc_tariff_payload( $rate );
	$preferred = defined( 'YABAO_APISHIP_ORIGIN_HANDOFF' )
		? strtolower( (string) YABAO_APISHIP_ORIGIN_HANDOFF )
		: 'point';
	$origin    = strtolower( (string) ( $payload['from'] ?? '' ) );
	$tariff_id = absint( $payload['tariffId'] ?? yabao_apiship_poc_rate_meta( $rate )['tariffId'] ?? 0 );

	return array(
		( '' !== $preferred && $preferred === $origin ) ? 0 : 1,
		yabao_apiship_poc_actual_cost( $rate ),
		$tariff_id ?: PHP_INT_MAX,
	);
}

function yabao_apiship_poc_candidate_is_better( WC_Shipping_Rate $candidate, WC_Shipping_Rate $current ): bool {
	$candidate_rank = yabao_apiship_poc_rate_rank( $candidate );
	$current_rank   = yabao_apiship_poc_rate_rank( $current );

	for ( $i = 0; $i < count( $candidate_rank ); $i++ ) {
		if ( $candidate_rank[ $i ] < $current_rank[ $i ] ) {
			return true;
		}
		if ( $candidate_rank[ $i ] > $current_rank[ $i ] ) {
			return false;
		}
	}

	return false;
}

/**
 * ApiShip can return several tariff ids that are operationally different for
 * the merchant but identical to the buyer (same carrier/service/price/ETA and
 * destination type). Collapse those duplicates. If both courier pickup and
 * point drop-off variants are identical to the buyer, prefer point drop-off,
 * matching the current Ya Bao fulfilment setup in ApiShip admin.
 */
function yabao_apiship_poc_dedupe_rates( array $rates, array $package ): array {
	$groups       = array();
	$replacements = array();

	foreach ( $rates as $rate_key => $rate ) {
		if ( ! yabao_apiship_poc_is_rate( $rate ) ) {
			continue;
		}

		$meta     = yabao_apiship_poc_rate_meta( $rate );
		$payload  = yabao_apiship_poc_tariff_payload( $rate );
		$provider = strtolower( (string) ( $meta['tariffProviderKey'] ?? $payload['providerKey'] ?? '' ) );
		$type     = strtolower( (string) ( $payload['deliveryType'] ?? '' ) );
		$min      = absint( $meta['daysMin'] ?? $payload['daysMin'] ?? 0 );
		$max      = absint( $meta['daysMax'] ?? $payload['daysMax'] ?? 0 );
		$cost     = number_format( max( 0.0, (float) $rate->get_cost() ), 2, '.', '' );
		$group    = implode( '|', array( $provider, yabao_apiship_poc_normalized_service( $rate ), $type, $cost, $min, $max ) );

		if ( ! isset( $groups[ $group ] ) ) {
			$groups[ $group ] = (string) $rate_key;
			continue;
		}

		$current_key = $groups[ $group ];
		$current     = $rates[ $current_key ] ?? null;
		if ( ! $current instanceof WC_Shipping_Rate ) {
			$groups[ $group ] = (string) $rate_key;
			continue;
		}

		if ( yabao_apiship_poc_candidate_is_better( $rate, $current ) ) {
			$replacements[ (string) $current->get_id() ] = (string) $rate->get_id();
			unset( $rates[ $current_key ] );
			$groups[ $group ] = (string) $rate_key;
		} else {
			$replacements[ (string) $rate->get_id() ] = (string) $current->get_id();
			unset( $rates[ $rate_key ] );
		}
	}

	if ( ! empty( $replacements ) && function_exists( 'WC' ) && WC()->session ) {
		$chosen = (array) WC()->session->get( 'chosen_shipping_methods', array() );
		$changed = false;
		foreach ( $chosen as $index => $chosen_id ) {
			$chosen_id = (string) $chosen_id;
			$guard = 0;
			while ( isset( $replacements[ $chosen_id ] ) && $guard < 10 ) {
				$chosen_id = $replacements[ $chosen_id ];
				$guard++;
			}
			if ( $chosen_id !== (string) $chosen[ $index ] ) {
				$chosen[ $index ] = $chosen_id;
				$changed = true;
			}
		}
		if ( $changed ) {
			WC()->session->set( 'chosen_shipping_methods', $chosen );
		}
	}

	return $rates;
}
add_filter( 'woocommerce_package_rates', 'yabao_apiship_poc_dedupe_rates', 127, 2 );

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
		'cdek'       => 'СДЭК',
		'x5'         => '5Post',
		'ozonlog'    => 'Ozon Доставка',
		'dostavista' => 'Dostavista',
		'cse'        => 'CSE',
		'pony'       => 'Pony Express',
		'yataxi'     => 'Яндекс Доставка',
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