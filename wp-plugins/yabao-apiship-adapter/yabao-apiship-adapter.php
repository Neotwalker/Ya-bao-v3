<?php
/**
 * Plugin Name: Ya Bao × ApiShip Adapter
 * Description: Companion layer between the official ApiShip WooCommerce plugin and the custom Ya Bao checkout.
 * Version: 0.2.0
 * Requires at least: 6.0
 * Requires PHP: 8.0
 * Requires Plugins: woocommerce
 * Text Domain: yabao-apiship
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const YABAO_APISHIP_ADAPTER_VERSION = '0.2.0';

/**
 * Stage 68.1 keeps ApiShip as a third-party dependency. This adapter owns only
 * Ya Bao policy and compatibility glue around the official WooCommerce rates.
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

/** Checkout destination persistence. */
function yabao_apiship_checkout_draft_fields(): array {
	return array(
		'billing_country',
		'billing_state',
		'billing_postcode',
		'billing_city',
		'billing_address_1',
		'billing_address_2',
	);
}

function yabao_apiship_checkout_posted_data(): array {
	$posted = array();
	if ( isset( $_POST['post_data'] ) && is_string( $_POST['post_data'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
		parse_str( wp_unslash( $_POST['post_data'] ), $posted ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
	}
	if ( empty( $posted ) ) {
		foreach ( yabao_apiship_checkout_draft_fields() as $key ) {
			if ( array_key_exists( $key, $_POST ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
				$posted[ $key ] = wp_unslash( $_POST[ $key ] ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
			}
		}
	}
	return is_array( $posted ) ? $posted : array();
}

function yabao_apiship_capture_checkout_draft( string $post_data ): void {
	if ( ! function_exists( 'WC' ) || ! WC()->session || '' === $post_data ) {
		return;
	}
	$posted = array();
	parse_str( $post_data, $posted );
	if ( ! is_array( $posted ) ) {
		return;
	}
	$draft = array();
	foreach ( yabao_apiship_checkout_draft_fields() as $field ) {
		if ( array_key_exists( $field, $posted ) ) {
			$draft[ $field ] = wc_clean( (string) $posted[ $field ] );
		}
	}
	if ( ! empty( $draft ) ) {
		WC()->session->set( 'yabao_apiship_checkout_draft', $draft );
	}
}
add_action( 'woocommerce_checkout_update_order_review', 'yabao_apiship_capture_checkout_draft', 5 );

function yabao_apiship_checkout_value( $value, string $input ) {
	if ( null !== $value && '' !== $value ) {
		return $value;
	}
	if ( ! function_exists( 'WC' ) || ! WC()->session ) {
		return $value;
	}
	$draft = (array) WC()->session->get( 'yabao_apiship_checkout_draft', array() );
	return array_key_exists( $input, $draft ) ? $draft[ $input ] : $value;
}
add_filter( 'woocommerce_checkout_get_value', 'yabao_apiship_checkout_value', 20, 2 );

function yabao_apiship_destination_value( array $posted, array $draft, string $posted_key, string $customer_getter, string $default = '' ): string {
	if ( array_key_exists( $posted_key, $posted ) ) {
		return wc_clean( (string) $posted[ $posted_key ] );
	}
	if ( array_key_exists( $posted_key, $draft ) ) {
		return wc_clean( (string) $draft[ $posted_key ] );
	}
	if ( function_exists( 'WC' ) && WC()->customer && is_callable( array( WC()->customer, $customer_getter ) ) ) {
		return wc_clean( (string) WC()->customer->{$customer_getter}() );
	}
	return $default;
}

function yabao_apiship_sync_checkout_destination( array $packages ): array {
	if ( is_admin() && ! wp_doing_ajax() ) {
		return $packages;
	}
	if ( ! function_exists( 'WC' ) || ! WC()->customer ) {
		return $packages;
	}
	$posted  = yabao_apiship_checkout_posted_data();
	$draft   = WC()->session ? (array) WC()->session->get( 'yabao_apiship_checkout_draft', array() ) : array();
	$address = yabao_apiship_destination_value( $posted, $draft, 'billing_address_1', 'get_billing_address_1' );
	$destination = array(
		'country'   => strtoupper( yabao_apiship_destination_value( $posted, $draft, 'billing_country', 'get_billing_country', 'RU' ) ?: 'RU' ),
		'state'     => yabao_apiship_destination_value( $posted, $draft, 'billing_state', 'get_billing_state' ),
		'postcode'  => yabao_apiship_destination_value( $posted, $draft, 'billing_postcode', 'get_billing_postcode' ),
		'city'      => yabao_apiship_destination_value( $posted, $draft, 'billing_city', 'get_billing_city' ),
		'address'   => $address,
		'address_1' => $address,
		'address_2' => yabao_apiship_destination_value( $posted, $draft, 'billing_address_2', 'get_billing_address_2' ),
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

/** ApiShip calculator request policy. */
function yabao_apiship_is_calculator_url( string $url ): bool {
	$host = strtolower( rtrim( (string) wp_parse_url( $url, PHP_URL_HOST ), '.' ) );
	$path = (string) wp_parse_url( $url, PHP_URL_PATH );
	$is_apiship_host = 'apiship.ru' === $host || str_ends_with( $host, '.apiship.ru' );
	return $is_apiship_host && str_ends_with( rtrim( $path, '/' ), '/calculator' );
}

function yabao_apiship_force_non_cod( array $args, string $url ): array {
	if ( ! yabao_apiship_is_calculator_url( $url ) ) {
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
	$body['codCost'] = 0;
	$args['body'] = wp_json_encode( $body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
	return $args;
}
add_filter( 'http_request_args', 'yabao_apiship_force_non_cod', 1001, 2 );

/** ApiShip rate helpers. */
function yabao_apiship_rate_meta_array( WC_Shipping_Rate $rate ): array {
	$meta = method_exists( $rate, 'get_meta_data' ) ? $rate->get_meta_data() : array();
	return is_array( $meta ) ? $meta : array();
}

function yabao_apiship_rate_meta( WC_Shipping_Rate $rate, string $key, $default = null ) {
	$meta = yabao_apiship_rate_meta_array( $rate );
	return array_key_exists( $key, $meta ) ? $meta[ $key ] : $default;
}

function yabao_apiship_is_rate( $rate ): bool {
	return $rate instanceof WC_Shipping_Rate && 'WPApiShip' === (string) yabao_apiship_rate_meta( $rate, 'integrator', '' );
}

function yabao_apiship_provider_key( WC_Shipping_Rate $rate ): string {
	return strtolower( trim( (string) yabao_apiship_rate_meta( $rate, 'tariffProviderKey', '' ) ) );
}

/** Carrier policy and provider allowlist. */
function yabao_apiship_manual_provider_map(): array {
	$map = array(
		'yabao_delivery_cdek'  => 'cdek',
		'yabao_delivery_5post' => 'x5',
	);
	return (array) apply_filters( 'yabao_apiship_manual_provider_map', $map );
}

function yabao_apiship_allowed_providers(): array {
	$providers = array_values( yabao_apiship_manual_provider_map() );
	if ( defined( 'YABAO_APISHIP_ALLOWED_PROVIDERS' ) ) {
		$configured = YABAO_APISHIP_ALLOWED_PROVIDERS;
		if ( is_string( $configured ) ) {
			$providers = preg_split( '/\s*,\s*/', $configured, -1, PREG_SPLIT_NO_EMPTY );
		} elseif ( is_array( $configured ) ) {
			$providers = $configured;
		}
	}
	$providers = array_map( static fn( $provider ): string => strtolower( trim( (string) $provider ) ), (array) $providers );
	$providers = array_values( array_unique( array_filter( $providers ) ) );
	return array_values( (array) apply_filters( 'yabao_apiship_allowed_providers', $providers ) );
}

function yabao_apiship_filter_allowed_rates( array $rates, array $package ): array {
	$allowed = yabao_apiship_allowed_providers();
	if ( empty( $allowed ) ) {
		return $rates;
	}
	foreach ( $rates as $key => $rate ) {
		if ( yabao_apiship_is_rate( $rate ) && ! in_array( yabao_apiship_provider_key( $rate ), $allowed, true ) ) {
			unset( $rates[ $key ] );
		}
	}
	return $rates;
}
add_filter( 'woocommerce_package_rates', 'yabao_apiship_filter_allowed_rates', 95, 2 );

/** Safe provider-cache self-heal. */
function yabao_apiship_provider_cache_complete( $cached, array $required ): bool {
	if ( ! is_array( $cached ) || empty( $cached ) ) {
		return false;
	}
	foreach ( $required as $provider ) {
		if ( ! isset( $cached[ $provider ] ) ) {
			return false;
		}
	}
	return true;
}

function yabao_apiship_prime_provider_cache(): void {
	$required = yabao_apiship_allowed_providers();
	if ( empty( $required ) ) {
		return;
	}
	$cached = get_option( 'wp_apiship_providers_list', array() );
	if ( yabao_apiship_provider_cache_complete( $cached, $required ) ) {
		return;
	}
	if ( has_action( 'wp_apiship_providers_cron_hook' ) ) {
		do_action( 'wp_apiship_providers_cron_hook' );
		$cached = get_option( 'wp_apiship_providers_list', array() );
		if ( yabao_apiship_provider_cache_complete( $cached, $required ) ) {
			return;
		}
	}
	if ( get_transient( 'yabao_apiship_provider_cache_retry' ) ) {
		return;
	}
	set_transient( 'yabao_apiship_provider_cache_retry', 1, 10 * MINUTE_IN_SECONDS );
	if ( ! class_exists( '\\ApiShip\\HTTP\\ApiShip_HTTP' ) ) {
		return;
	}
	$response = \ApiShip\HTTP\ApiShip_HTTP::get( 'lists/providers?limit=999' );
	if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
		return;
	}
	$body = json_decode( wp_remote_retrieve_body( $response ) );
	if ( ! is_object( $body ) || empty( $body->rows ) || ! is_array( $body->rows ) ) {
		return;
	}
	$list = array();
	foreach ( $body->rows as $row ) {
		if ( is_object( $row ) && ! empty( $row->key ) ) {
			$list[ (string) $row->key ] = (array) $row;
		}
	}
	if ( empty( $list ) ) {
		return;
	}
	update_option( 'wp_apiship_providers_list', $list, false );
	delete_transient( 'yabao_apiship_provider_cache_retry' );
}
add_action( 'wp_loaded', 'yabao_apiship_prime_provider_cache', 20 );

/** Keep approved store pickup next to real ApiShip rates. */
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
	$pickup = new WC_Shipping_Rate( 'yabao_pickup', 'Самовывоз — Кирова, 94', 0, array(), 'yabao_pickup', 0 );
	$pickup->add_meta_data( 'yabao_kind', 'pickup' );
	return array( 'yabao_pickup' => $pickup ) + $rates;
}
add_filter( 'woocommerce_package_rates', 'yabao_apiship_keep_store_pickup', 115, 2 );

/** Free carrier delivery from threshold. */
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

/** Generic manual-carrier -> real-provider selection transition. */
function yabao_apiship_posted_shipping_methods(): array {
	$methods = array();
	if ( isset( $_POST['shipping_method'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$raw = wp_unslash( $_POST['shipping_method'] ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( is_array( $raw ) ) {
			foreach ( $raw as $index => $method ) {
				if ( is_scalar( $method ) ) {
					$methods[ $index ] = wc_clean( (string) $method );
				}
			}
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
		}
	}
	return $methods;
}

function yabao_apiship_promote_manual_selection( array $rates, array $package ): array {
	if ( empty( $rates ) || ! function_exists( 'WC' ) || ! WC()->session ) {
		return $rates;
	}
	$map = yabao_apiship_manual_provider_map();
	foreach ( yabao_apiship_posted_shipping_methods() as $index => $manual_method ) {
		$provider = strtolower( trim( (string) ( $map[ $manual_method ] ?? '' ) ) );
		if ( '' === $provider ) {
			continue;
		}
		foreach ( $rates as $rate ) {
			if ( ! yabao_apiship_is_rate( $rate ) || $provider !== yabao_apiship_provider_key( $rate ) ) {
				continue;
			}
			$chosen = (array) WC()->session->get( 'chosen_shipping_methods', array() );
			$chosen[ $index ] = (string) $rate->get_id();
			WC()->session->set( 'chosen_shipping_methods', $chosen );
			break;
		}
	}
	return $rates;
}
add_filter( 'woocommerce_package_rates', 'yabao_apiship_promote_manual_selection', 124, 2 );

/** Buyer-identical tariff deduplication. */
function yabao_apiship_tariff_payload( WC_Shipping_Rate $rate ): array {
	$raw = yabao_apiship_rate_meta( $rate, 'tariff', '' );
	if ( is_array( $raw ) ) {
		return $raw;
	}
	if ( ! is_string( $raw ) || '' === $raw ) {
		return array();
	}
	$decoded = json_decode( $raw, true );
	return is_array( $decoded ) ? $decoded : array();
}

function yabao_apiship_normalized_service( WC_Shipping_Rate $rate ): string {
	$key = yabao_apiship_provider_key( $rate );
	$name = wp_strip_all_tags( (string) yabao_apiship_rate_meta( $rate, 'tariffName', $rate->get_label() ) );
	if ( '' !== $key && '' !== $name ) {
		$name = preg_replace( '/^' . preg_quote( $key, '/' ) . '\s*[-–—]\s*/iu', '', $name );
	}
	return mb_strtolower( trim( preg_replace( '/\s+/u', ' ', (string) $name ) ) );
}

function yabao_apiship_actual_cost( WC_Shipping_Rate $rate ): float {
	$stored = yabao_apiship_rate_meta( $rate, 'yabao_apiship_actual_cost', null );
	return null !== $stored ? max( 0.0, (float) $stored ) : max( 0.0, (float) $rate->get_cost() );
}

function yabao_apiship_rate_rank( WC_Shipping_Rate $rate ): array {
	$payload = yabao_apiship_tariff_payload( $rate );
	$preferred = defined( 'YABAO_APISHIP_ORIGIN_HANDOFF' ) ? strtolower( (string) YABAO_APISHIP_ORIGIN_HANDOFF ) : 'point';
	$origin = strtolower( (string) ( $payload['from'] ?? '' ) );
	$tariff_id = absint( $payload['tariffId'] ?? yabao_apiship_rate_meta( $rate, 'tariffId', 0 ) );
	return array( ( '' !== $preferred && $preferred === $origin ) ? 0 : 1, yabao_apiship_actual_cost( $rate ), $tariff_id ?: PHP_INT_MAX );
}

function yabao_apiship_candidate_is_better( WC_Shipping_Rate $candidate, WC_Shipping_Rate $current ): bool {
	$candidate_rank = yabao_apiship_rate_rank( $candidate );
	$current_rank = yabao_apiship_rate_rank( $current );
	for ( $i = 0, $count = count( $candidate_rank ); $i < $count; $i++ ) {
		if ( $candidate_rank[ $i ] < $current_rank[ $i ] ) {
			return true;
		}
		if ( $candidate_rank[ $i ] > $current_rank[ $i ] ) {
			return false;
		}
	}
	return false;
}

function yabao_apiship_dedupe_rates( array $rates, array $package ): array {
	$groups = array();
	$replacements = array();
	foreach ( $rates as $rate_key => $rate ) {
		if ( ! yabao_apiship_is_rate( $rate ) ) {
			continue;
		}
		$payload = yabao_apiship_tariff_payload( $rate );
		$provider = yabao_apiship_provider_key( $rate );
		$type = strtolower( (string) ( $payload['deliveryType'] ?? '' ) );
		$min = absint( yabao_apiship_rate_meta( $rate, 'daysMin', $payload['daysMin'] ?? 0 ) );
		$max = absint( yabao_apiship_rate_meta( $rate, 'daysMax', $payload['daysMax'] ?? 0 ) );
		$cost = number_format( max( 0.0, (float) $rate->get_cost() ), 2, '.', '' );
		$group = implode( '|', array( $provider, yabao_apiship_normalized_service( $rate ), $type, $cost, $min, $max ) );
		if ( ! isset( $groups[ $group ] ) ) {
			$groups[ $group ] = (string) $rate_key;
			continue;
		}
		$current_key = $groups[ $group ];
		$current = $rates[ $current_key ] ?? null;
		if ( ! $current instanceof WC_Shipping_Rate ) {
			$groups[ $group ] = (string) $rate_key;
			continue;
		}
		if ( yabao_apiship_candidate_is_better( $rate, $current ) ) {
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
			$resolved = (string) $chosen_id;
			$guard = 0;
			while ( isset( $replacements[ $resolved ] ) && $guard < 10 ) {
				$resolved = $replacements[ $resolved ];
				$guard++;
			}
			if ( $resolved !== (string) $chosen_id ) {
				$chosen[ $index ] = $resolved;
				$changed = true;
			}
		}
		if ( $changed ) {
			WC()->session->set( 'chosen_shipping_methods', $chosen );
		}
	}
	return $rates;
}
add_filter( 'woocommerce_package_rates', 'yabao_apiship_dedupe_rates', 127, 2 );

/** Compact customer-facing labels. */
function yabao_apiship_provider_names(): array {
	$names = array( 'cdek' => 'СДЭК', 'x5' => '5Post' );
	return (array) apply_filters( 'yabao_apiship_provider_names', $names );
}

function yabao_apiship_clean_labels( array $rates, array $package ): array {
	$provider_names = yabao_apiship_provider_names();
	foreach ( $rates as $rate ) {
		if ( ! yabao_apiship_is_rate( $rate ) ) {
			continue;
		}
		$key = yabao_apiship_provider_key( $rate );
		$provider = $provider_names[ $key ] ?? strtoupper( $key ?: 'Доставка' );
		$service = wp_strip_all_tags( (string) yabao_apiship_rate_meta( $rate, 'tariffName', '' ) );
		if ( '' !== $key && '' !== $service ) {
			$service = preg_replace( '/^' . preg_quote( $key, '/' ) . '\s*[-–—]\s*/iu', '', $service );
		}
		$service = trim( preg_replace( '/\s+/u', ' ', (string) $service ) );
		if ( '' === $service ) {
			$service = 'Доставка';
		}
		$label = $provider . ' — ' . $service;
		$cost = max( 0.0, (float) $rate->get_cost() );
		$label .= $cost > 0 ? ' · ' . trim( wp_strip_all_tags( wc_price( $cost ) ) ) : ' · бесплатно';
		$min = absint( yabao_apiship_rate_meta( $rate, 'daysMin', 0 ) );
		$max = absint( yabao_apiship_rate_meta( $rate, 'daysMax', 0 ) );
		if ( $min && $max && $min !== $max ) {
			$label .= sprintf( ' · %d–%d дней', $min, $max );
		} elseif ( $min ) {
			$label .= sprintf( ' · %d дней', $min );
		}
		$rate->set_label( $label );
	}
	return $rates;
}
add_filter( 'woocommerce_package_rates', 'yabao_apiship_clean_labels', 130, 2 );
