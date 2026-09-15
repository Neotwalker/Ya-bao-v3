<?php
/**
 * SEO and routing rules for non-indexable storefront states.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Return the current request path without query parameters.
 */
function yabao_seo_request_path(): string {
	if ( empty( $_SERVER['REQUEST_URI'] ) ) {
		return '';
	}

	$path = wp_parse_url(
		wp_unslash( $_SERVER['REQUEST_URI'] ),
		PHP_URL_PATH
	);

	return is_string( $path ) ? $path : '';
}

/**
 * True for the canonical /shop/ path and its paginated variants.
 *
 * The product_cat query var makes WordPress consider a /shop/ filter request
 * a product-category query internally. Path detection keeps the public URL
 * architecture authoritative and distinguishes it from native taxonomy URLs.
 */
function yabao_seo_is_shop_filter_path( string $request_path = '' ): bool {
	if ( ! yabao_woocommerce_active() ) {
		return false;
	}

	if ( '' === $request_path ) {
		$request_path = yabao_seo_request_path();
	}

	$shop_path = (string) wp_parse_url(
		yabao_wc_page_url( 'shop' ),
		PHP_URL_PATH
	);

	$request_path = trim( $request_path, '/' );
	$shop_path    = trim( $shop_path, '/' );

	if ( $request_path === $shop_path ) {
		return true;
	}

	if ( '' === $shop_path ) {
		return false;
	}

	return 1 === preg_match(
		'#^' . preg_quote( $shop_path, '#' ) . '/page/[1-9][0-9]*$#',
		$request_path
	);
}

/**
 * True when the request contains a storefront filter that is not an
 * independent SEO landing page.
 */
function yabao_seo_has_shop_filter_params(): bool {
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
 * True when the current request is a filtered state of /shop/.
 */
function yabao_seo_is_shop_filter_state(): bool {
	return yabao_woocommerce_active()
		&& yabao_seo_is_shop_filter_path()
		&& yabao_seo_has_shop_filter_params();
}

/**
 * Storefront utility pages must never become organic landing pages.
 */
function yabao_seo_is_storefront_utility_state(): bool {
	if ( ! yabao_woocommerce_active() ) {
		return false;
	}

	return is_cart()
		|| is_checkout()
		|| is_account_page()
		|| is_page( array( 'order-success', 'order-failed' ) );
}

/**
 * Central noindex decision used by WordPress and Rank Math.
 */
function yabao_seo_should_noindex_storefront_request(): bool {
	return yabao_seo_is_shop_filter_state()
		|| yabao_seo_is_storefront_utility_state()
		|| ( yabao_woocommerce_active() && is_product_category() );
}

/**
 * Disable older storefront hooks from functions.php after the theme finishes
 * loading. This module is now the single authority for robots/canonical and
 * category routing, preventing duplicate filters from drifting apart.
 */
function yabao_seo_disable_legacy_storefront_hooks(): void {
	remove_filter( 'wp_robots', 'yabao_wp_robots', 10 );
	remove_filter(
		'redirect_canonical',
		'yabao_preserve_shop_category_filter_url',
		10
	);
	remove_action(
		'template_redirect',
		'yabao_redirect_legacy_product_category_urls',
		5
	);
}
add_action(
	'after_setup_theme',
	'yabao_seo_disable_legacy_storefront_hooks',
	1
);

/**
 * Core WordPress robots fallback for installations without an SEO plugin.
 */
function yabao_seo_storefront_wp_robots( array $robots ): array {
	if ( ! yabao_seo_should_noindex_storefront_request() ) {
		return $robots;
	}

	unset( $robots['index'], $robots['nofollow'] );
	$robots['noindex'] = true;
	$robots['follow']  = true;

	return $robots;
}
add_filter( 'wp_robots', 'yabao_seo_storefront_wp_robots', 20 );

/**
 * Rank Math owns the rendered robots tag when the plugin is active.
 * Use Rank Math's documented directive keys rather than adding parallel keys.
 */
function yabao_seo_storefront_rank_math_robots( array $robots ): array {
	if ( ! yabao_seo_should_noindex_storefront_request() ) {
		return $robots;
	}

	unset( $robots['noindex'], $robots['nofollow'] );
	$robots['index']  = 'noindex';
	$robots['follow'] = 'follow';

	return $robots;
}
add_filter(
	'rank_math/frontend/robots',
	'yabao_seo_storefront_rank_math_robots',
	20
);

/**
 * Filtered catalog states consolidate canonical signals to the clean shop URL.
 */
function yabao_seo_shop_filter_rank_math_canonical( string $canonical ): string {
	return yabao_seo_is_shop_filter_state()
		? yabao_wc_page_url( 'shop' )
		: $canonical;
}
add_filter(
	'rank_math/frontend/canonical',
	'yabao_seo_shop_filter_rank_math_canonical',
	20
);

/**
 * Do not let WordPress canonicalize public /shop/ filter states back to native
 * WooCommerce taxonomy URLs.
 */
function yabao_seo_preserve_shop_filter_request( $redirect_url, string $requested_url ) {
	unset( $requested_url );

	return yabao_seo_is_shop_filter_state()
		? false
		: $redirect_url;
}
add_filter(
	'redirect_canonical',
	'yabao_seo_preserve_shop_filter_request',
	20,
	2
);

/**
 * Native WooCommerce category archives are technical URLs only. Keep the
 * historical /category/ page compatible, and route product taxonomy archives
 * to the corresponding state inside /shop/.
 */
function yabao_seo_redirect_legacy_product_category_urls(): void {
	if ( ! yabao_woocommerce_active() ) {
		return;
	}

	if ( is_page( 'category' ) ) {
		wp_safe_redirect(
			yabao_wc_page_url( 'shop' ),
			301,
			'Ya Bao'
		);
		exit;
	}

	if ( ! is_product_category() ) {
		return;
	}

	/*
	 * product_cat makes /shop/?product_cat=... look like a taxonomy request
	 * internally. Public shop filter URLs, including pagination, must survive.
	 */
	if (
		yabao_seo_is_shop_filter_path()
		&& ! empty( $_GET['product_cat'] )
	) {
		return;
	}

	$term = get_queried_object();
	if ( ! $term instanceof WP_Term ) {
		return;
	}

	$target = yabao_product_category_filter_url( $term );
	$paged  = max( 1, (int) get_query_var( 'paged' ) );

	if ( $paged > 1 ) {
		$target = add_query_arg(
			'paged',
			$paged,
			$target
		);
	}

	wp_safe_redirect(
		$target,
		301,
		'Ya Bao'
	);
	exit;
}
add_action(
	'template_redirect',
	'yabao_seo_redirect_legacy_product_category_urls',
	5
);

/**
 * Product categories are filter states at this stage, not XML sitemap
 * landing pages. This overrides a conflicting Rank Math taxonomy setting.
 */
function yabao_seo_rank_math_exclude_product_category_taxonomy(
	bool $exclude,
	string $type
): bool {
	return 'product_cat' === $type ? true : $exclude;
}
add_filter(
	'rank_math/sitemap/exclude_taxonomy',
	'yabao_seo_rank_math_exclude_product_category_taxonomy',
	20,
	2
);

/**
 * Keep redirecting/noindex utility pages out of Rank Math's page sitemap.
 */
function yabao_seo_rank_math_sitemap_entry( $url, string $type, $object ) {
	if (
		'post' !== $type
		|| ! $object instanceof WP_Post
		|| 'page' !== $object->post_type
	) {
		return $url;
	}

	$page_id   = (int) $object->ID;
	$page_path = trim( (string) get_page_uri( $page_id ), '/' );

	$utility_ids = array();
	if ( function_exists( 'wc_get_page_id' ) ) {
		foreach ( array( 'cart', 'checkout', 'myaccount' ) as $wc_page ) {
			$wc_page_id = (int) wc_get_page_id( $wc_page );
			if ( $wc_page_id > 0 ) {
				$utility_ids[] = $wc_page_id;
			}
		}
	}

	$excluded_paths = array(
		'category',
		'cart',
		'checkout',
		'my-account',
		'order-success',
		'order-failed',
	);

	if (
		in_array( $page_id, $utility_ids, true )
		|| in_array( $page_path, $excluded_paths, true )
	) {
		return false;
	}

	return $url;
}
add_filter(
	'rank_math/sitemap/entry',
	'yabao_seo_rank_math_sitemap_entry',
	20,
	3
);
