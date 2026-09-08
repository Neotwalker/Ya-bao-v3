<?php
/**
 * Stage 67 security hardening.
 *
 * Keeps storefront behaviour unchanged while protecting privileged product
 * imports from unsafe remote image URLs and enforcing the current checkout
 * business rule: orders are accepted for Russia only.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const YABAO_IMPORT_MAX_REMOTE_IMAGE_BYTES = 8388608; // 8 MB.

/**
 * True only while the product importer is actively applying a dataset.
 */
function yabao_security_is_product_import_request(): bool {
	return function_exists( 'yabao_import_is_running' ) && yabao_import_is_running();
}

/**
 * Match download_url()-style streamed requests only, so other plugin webhooks
 * triggered by a product save are not changed by importer hardening.
 */
function yabao_security_is_import_media_http_request( array $args ): bool {
	return yabao_security_is_product_import_request() && ! empty( $args['stream'] ) && ! empty( $args['filename'] );
}

/**
 * Return true only for publicly routable IPv4/IPv6 addresses.
 */
function yabao_security_is_public_ip( string $ip ): bool {
	return false !== filter_var(
		$ip,
		FILTER_VALIDATE_IP,
		FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
	);
}

/**
 * Validate a remote image URL before WordPress performs the request.
 *
 * Remote importer images must use HTTPS and resolve to public addresses.
 * wp_safe_remote_get()/reject_unsafe_urls performs its own validation too;
 * this is an importer-specific second layer against localhost/private-network
 * targets and accidental insecure HTTP feeds.
 */
function yabao_security_validate_import_image_url( string $url ) {
	if ( ! wp_http_validate_url( $url ) ) {
		return new WP_Error( 'yabao_import_image_url', 'Удалённый URL изображения не прошёл проверку WordPress.' );
	}

	$parts = wp_parse_url( $url );
	if ( ! is_array( $parts ) ) {
		return new WP_Error( 'yabao_import_image_url', 'Некорректный URL удалённого изображения.' );
	}

	$scheme = strtolower( (string) ( $parts['scheme'] ?? '' ) );
	$host   = strtolower( rtrim( (string) ( $parts['host'] ?? '' ), '.' ) );

	if ( 'https' !== $scheme ) {
		return new WP_Error( 'yabao_import_image_scheme', 'Удалённые изображения импортируются только по HTTPS.' );
	}
	if ( '' === $host || isset( $parts['user'] ) || isset( $parts['pass'] ) ) {
		return new WP_Error( 'yabao_import_image_host', 'URL изображения содержит недопустимый host или credentials.' );
	}

	if (
		'localhost' === $host ||
		str_ends_with( $host, '.localhost' ) ||
		str_ends_with( $host, '.local' ) ||
		str_ends_with( $host, '.internal' )
	) {
		return new WP_Error( 'yabao_import_image_private_host', 'Локальные и внутренние адреса запрещены для удалённого импорта изображений.' );
	}

	if ( filter_var( $host, FILTER_VALIDATE_IP ) ) {
		if ( ! yabao_security_is_public_ip( $host ) ) {
			return new WP_Error( 'yabao_import_image_private_ip', 'Приватные и зарезервированные IP запрещены для удалённого импорта изображений.' );
		}
		return true;
	}

	$addresses = function_exists( 'gethostbynamel' ) ? gethostbynamel( $host ) : false;
	if ( ! is_array( $addresses ) || ! $addresses ) {
		return new WP_Error( 'yabao_import_image_dns', 'Не удалось безопасно разрешить домен удалённого изображения.' );
	}
	foreach ( $addresses as $address ) {
		if ( ! yabao_security_is_public_ip( (string) $address ) ) {
			return new WP_Error( 'yabao_import_image_private_dns', 'Домен удалённого изображения указывает на приватный или зарезервированный IP.' );
		}
	}

	return true;
}

/**
 * Stop unsafe remote image requests before a network connection is made.
 */
function yabao_security_pre_http_request( $preempt, array $parsed_args, string $url ) {
	if ( ! yabao_security_is_import_media_http_request( $parsed_args ) ) {
		return $preempt;
	}

	$validation = yabao_security_validate_import_image_url( $url );
	return is_wp_error( $validation ) ? $validation : $preempt;
}
add_filter( 'pre_http_request', 'yabao_security_pre_http_request', 10, 3 );

/**
 * Apply strict HTTP limits only while the importer downloads remote media.
 */
function yabao_security_import_http_args( array $args, string $url ): array {
	if ( ! yabao_security_is_import_media_http_request( $args ) ) {
		return $args;
	}

	$args['reject_unsafe_urls']  = true;
	$args['redirection']         = min( 3, max( 0, (int) ( $args['redirection'] ?? 3 ) ) );
	$args['timeout']             = min( 30, max( 1, (int) ( $args['timeout'] ?? 30 ) ) );
	$args['limit_response_size'] = YABAO_IMPORT_MAX_REMOTE_IMAGE_BYTES + 1;

	return $args;
}
add_filter( 'http_request_args', 'yabao_security_import_http_args', 10, 2 );

/**
 * Verify the downloaded bytes before media_handle_sideload() imports them.
 */
function yabao_security_verify_import_http_response( $response, array $parsed_args, string $url ) {
	if ( ! yabao_security_is_import_media_http_request( $parsed_args ) || is_wp_error( $response ) ) {
		return $response;
	}

	$filename = isset( $parsed_args['filename'] ) ? (string) $parsed_args['filename'] : '';
	if ( '' === $filename || ! is_file( $filename ) ) {
		return $response;
	}

	$size = filesize( $filename );
	if ( false === $size || $size > YABAO_IMPORT_MAX_REMOTE_IMAGE_BYTES ) {
		@unlink( $filename );
		return new WP_Error( 'yabao_import_image_size', 'Удалённое изображение превышает лимит 8 МБ.' );
	}

	$mime = function_exists( 'wp_get_image_mime' ) ? wp_get_image_mime( $filename ) : false;
	$allowed_mimes = array( 'image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/avif' );
	if ( ! is_string( $mime ) || ! in_array( $mime, $allowed_mimes, true ) ) {
		@unlink( $filename );
		return new WP_Error( 'yabao_import_image_mime', 'Удалённый файл не является разрешённым растровым изображением.' );
	}

	return $response;
}
add_filter( 'http_response', 'yabao_security_verify_import_http_response', 10, 3 );

/**
 * Current commercial rule: checkout/delivery is Russia-only.
 *
 * The country controls are hidden in the approved checkout UI. Force the
 * canonical value server-side as well, so a forged POST cannot create an
 * order with another billing/shipping country.
 */
function yabao_security_force_checkout_country( array $data ): array {
	$data['billing_country']  = 'RU';
	$data['shipping_country'] = 'RU';
	return $data;
}
add_filter( 'woocommerce_checkout_posted_data', 'yabao_security_force_checkout_country', 20 );

function yabao_security_force_order_country( WC_Order $order, array $data ): void {
	$order->set_billing_country( 'RU' );
	$order->set_shipping_country( 'RU' );
}
add_action( 'woocommerce_checkout_create_order', 'yabao_security_force_order_country', 20, 2 );
