<?php
/**
 * SEO rules for non-indexable storefront filter states.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * True when the current request is a filtered state of the canonical /shop/
 * catalog rather than an independent SEO landing page.
 */
function yabao_seo_is_shop_filter_state(): bool {
	if ( ! yabao_woocommerce_active() ) {
		return false;
	}

	$request_path = isset( $_SERVER['REQUEST_URI'] )
		? (string) wp_parse_url( wp_unslash( $_SERVER['REQUEST_URI'] ), PHP_URL_PATH )
		: '';
	$shop_path = (string) wp_parse_url( yabao_wc_page_url( 'shop' ), PHP_URL_PATH );

	if ( untrailingslashit( $request_path ) !== untrailingslashit( $shop_path ) ) {
		return false;
	}

	$category = isset( $_GET['product_cat'] )
		? sanitize_title( wp_unslash( $_GET['product_cat'] ) )
		: '';
	$search = isset( $_GET['q'] )
		? trim( sanitize_text_field( wp_unslash( $_GET['q'] ) ) )
		: '';
	$orderby = isset( $_GET['orderby'] )
		? wc_clean( wp_unslash( $_GET['orderby'] ) )
		: '';

	return '' !== $category
		|| '' !== $search
		|| ( '' !== $orderby && 'menu_order' !== $orderby );
}

/**
 * Core WordPress robots fallback for installations without an SEO plugin.
 */
function yabao_seo_shop_filter_wp_robots( array $robots ): array {
	if ( ! yabao_seo_is_shop_filter_state() ) {
		return $robots;
	}

	$robots['noindex'] = true;
	$robots['follow']  = true;
	unset( $robots['index'], $robots['nofollow'] );

	return $robots;
}
add_filter( 'wp_robots', 'yabao_seo_shop_filter_wp_robots', 20 );

/**
 * Rank Math owns the rendered robots tag when the plugin is active.
 */
function yabao_seo_shop_filter_rank_math_robots( array $robots ): array {
	if ( ! yabao_seo_is_shop_filter_state() ) {
		return $robots;
	}

	unset( $robots['index'], $robots['nofollow'] );
	$robots['noindex'] = 'noindex';
	$robots['follow']  = 'follow';

	return $robots;
}
add_filter( 'rank_math/frontend/robots', 'yabao_seo_shop_filter_rank_math_robots', 20 );

/**
 * Filtered catalog states consolidate to the canonical shop landing page.
 */
function yabao_seo_shop_filter_rank_math_canonical( string $canonical ): string {
	return yabao_seo_is_shop_filter_state()
		? yabao_wc_page_url( 'shop' )
		: $canonical;
}
add_filter( 'rank_math/frontend/canonical', 'yabao_seo_shop_filter_rank_math_canonical', 20 );

/**
 * Do not let WordPress canonicalize query-string filter states back to native
 * taxonomy URLs. The native product-category archives are handled separately
 * by the permanent redirects in functions.php.
 */
function yabao_seo_preserve_shop_filter_request( $redirect_url, string $requested_url ) {
	unset( $requested_url );

	return yabao_seo_is_shop_filter_state()
		? false
		: $redirect_url;
}
add_filter( 'redirect_canonical', 'yabao_seo_preserve_shop_filter_request', 20, 2 );
