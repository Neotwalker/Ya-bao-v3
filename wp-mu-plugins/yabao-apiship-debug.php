<?php
/**
 * Temporary Stage 68.1 ApiShip calculator diagnostics.
 *
 * Copy to wp-content/mu-plugins/ on the local site only.
 * Remove after the PoC is diagnosed.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'YABAO_APISHIP_DEBUG' ) || ! YABAO_APISHIP_DEBUG ) {
	return;
}

function yabao_apiship_debug_log( string $label, $data ): void {
	$path = WP_CONTENT_DIR . '/yabao-apiship-debug.log';
	$line = '[' . gmdate( 'Y-m-d H:i:s' ) . ' UTC] ' . $label . PHP_EOL;

	if ( is_string( $data ) ) {
		$line .= $data;
	} else {
		$line .= wp_json_encode( $data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT );
	}

	$line .= PHP_EOL . str_repeat( '-', 80 ) . PHP_EOL;
	file_put_contents( $path, $line, FILE_APPEND | LOCK_EX ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
}

function yabao_apiship_debug_shipping_packages( array $packages ): array {
	$summary = array();

	foreach ( $packages as $index => $package ) {
		$items = array();
		foreach ( (array) ( $package['contents'] ?? array() ) as $item ) {
			$product = $item['data'] ?? null;
			$items[] = array(
				'product_id' => isset( $item['product_id'] ) ? (int) $item['product_id'] : 0,
				'quantity'   => isset( $item['quantity'] ) ? (int) $item['quantity'] : 0,
				'weight'     => $product instanceof WC_Product ? (string) $product->get_weight() : '',
				'length'     => $product instanceof WC_Product ? (string) $product->get_length() : '',
				'width'      => $product instanceof WC_Product ? (string) $product->get_width() : '',
				'height'     => $product instanceof WC_Product ? (string) $product->get_height() : '',
			);
		}

		$summary[] = array(
			'index'       => (int) $index,
			'destination' => (array) ( $package['destination'] ?? array() ),
			'contents_cost' => isset( $package['contents_cost'] ) ? (float) $package['contents_cost'] : null,
			'items'       => $items,
		);
	}

	yabao_apiship_debug_log( 'Woo shipping packages', $summary );
	return $packages;
}
add_filter( 'woocommerce_cart_shipping_packages', 'yabao_apiship_debug_shipping_packages', 999 );

function yabao_apiship_debug_http( $response, string $context, string $class, array $parsed_args, string $url ): void {
	if ( false === strpos( $url, 'apiship.ru' ) || false === strpos( $url, 'calculator' ) ) {
		return;
	}

	$request_body = $parsed_args['body'] ?? '';
	if ( is_string( $request_body ) ) {
		$decoded = json_decode( $request_body, true );
		$request_body = is_array( $decoded ) ? $decoded : $request_body;
	}

	$record = array(
		'url'     => $url,
		'context' => $context,
		'class'   => $class,
		'request' => $request_body,
	);

	if ( is_wp_error( $response ) ) {
		$record['response'] = array(
			'wp_error_code'    => $response->get_error_code(),
			'wp_error_message' => $response->get_error_message(),
		);
	} elseif ( is_array( $response ) ) {
		$body = wp_remote_retrieve_body( $response );
		$record['response'] = array(
			'http_code' => wp_remote_retrieve_response_code( $response ),
			'body'      => strlen( $body ) > 30000 ? substr( $body, 0, 30000 ) . '\n...[truncated]' : $body,
		);
	} else {
		$record['response'] = array( 'type' => gettype( $response ) );
	}

	yabao_apiship_debug_log( 'ApiShip calculator HTTP', $record );
}
add_action( 'http_api_debug', 'yabao_apiship_debug_http', 10, 5 );

function yabao_apiship_debug_rates( array $rates, array $package ): array {
	$summary = array();
	foreach ( $rates as $key => $rate ) {
		if ( ! $rate instanceof WC_Shipping_Rate ) {
			continue;
		}
		$summary[] = array(
			'key'      => (string) $key,
			'id'       => (string) $rate->get_id(),
			'label'    => wp_strip_all_tags( $rate->get_label() ),
			'cost'     => (float) $rate->get_cost(),
			'method_id'=> method_exists( $rate, 'get_method_id' ) ? (string) $rate->get_method_id() : '',
			'meta'     => method_exists( $rate, 'get_meta_data' ) ? $rate->get_meta_data() : array(),
		);
	}

	yabao_apiship_debug_log( 'Final package rates', $summary );
	return $rates;
}
add_filter( 'woocommerce_package_rates', 'yabao_apiship_debug_rates', 9999, 2 );
