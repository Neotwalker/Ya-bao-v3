<?php
/**
 * Temporary Stage 68.1 compatibility bridge for the official ApiShip plugin.
 *
 * ApiShip's shipping method parses /calculator tariffs only for providers found
 * in the cached `wp_apiship_providers_list` option. That option is refreshed by
 * a daily WP-Cron event, so a fresh/local install can receive valid calculator
 * tariffs before the provider cache has ever been populated and silently drop
 * every tariff afterwards.
 *
 * Keep this as a QA shim for the PoC. Once confirmed, fold the guard into the
 * permanent Ya Bao × ApiShip adapter and remove this MU plugin.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function yabao_apiship_prime_provider_cache(): void {
	if (
		! class_exists( '\\ApiShip\\HTTP\\ApiShip_HTTP' ) ||
		! class_exists( '\\ApiShip\\Options\\ApiShip_Options' )
	) {
		return;
	}

	$cached = get_option( 'wp_apiship_providers_list', array() );
	$selected = \ApiShip\Options\ApiShip_Options::get_selected_providers();
	$selected = is_array( $selected ) ? $selected : array();

	$needs_refresh = ! is_array( $cached ) || empty( $cached );
	if ( ! $needs_refresh ) {
		foreach ( $selected as $provider_key ) {
			if ( ! isset( $cached[ $provider_key ] ) ) {
				$needs_refresh = true;
				break;
			}
		}
	}

	if ( ! $needs_refresh ) {
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
		if ( ! is_object( $row ) || empty( $row->key ) ) {
			continue;
		}
		$list[ (string) $row->key ] = (array) $row;
	}

	if ( empty( $list ) ) {
		return;
	}

	update_option( 'wp_apiship_providers_list', $list, false );

	if ( function_exists( 'yabao_apiship_debug_log' ) ) {
		yabao_apiship_debug_log(
			'ApiShip provider cache primed',
			array(
				'provider_count' => count( $list ),
				'selected'       => array_values( $selected ),
				'has_cdek'       => isset( $list['cdek'] ),
			)
		);
	}
}
add_action( 'wp_loaded', 'yabao_apiship_prime_provider_cache', 20 );
