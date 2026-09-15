<?php
/**
 * SEO and routing rules for non-indexable storefront states.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const YABAO_SEO_SITEMAP_RULES_VERSION = '2026-09-15-2';

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
 * Filter states are explicitly noindex. Do not force a second canonical signal
 * on top of that policy; Rank Math may intentionally omit canonical output for
 * noindex requests. The clean /shop/ page keeps its normal self-canonical.
 */

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
 * Resolve utility page IDs for sitemap exclusions without depending solely on
 * WooCommerce page assignments, which can be incomplete on staging/local DBs.
 */
function yabao_seo_utility_page_ids(): array {
	$page_ids = array();

	if ( function_exists( 'wc_get_page_id' ) ) {
		foreach ( array( 'cart', 'checkout', 'myaccount' ) as $wc_page ) {
			$page_id = (int) wc_get_page_id( $wc_page );
			if ( $page_id > 0 ) {
				$page_ids[] = $page_id;
			}
		}
	}

	foreach (
		array(
			'category',
			'cart',
			'checkout',
			'my-account',
			'order-success',
			'order-failed',
		) as $page_path
	) {
		$page = get_page_by_path( $page_path, OBJECT, 'page' );
		if ( $page instanceof WP_Post ) {
			$page_ids[] = (int) $page->ID;
		}
	}

	return array_values(
		array_unique(
			array_filter(
				$page_ids,
				static fn( int $page_id ): bool => $page_id > 0
			)
		)
	);
}

/**
 * Keep redirecting/noindex utility pages out of Rank Math's page sitemap.
 *
 * Rank Math builds sitemap rows with a direct SQL query, so $object can be a
 * plain database object rather than WP_Post. Inspect the stable fields instead
 * of requiring a concrete WP_Post instance.
 */
function yabao_seo_rank_math_sitemap_entry( $url, string $type, $object ) {
	if (
		'post' !== $type
		|| ! is_object( $object )
		|| empty( $object->ID )
	) {
		return $url;
	}

	$post_type = isset( $object->post_type )
		? (string) $object->post_type
		: (string) get_post_type( (int) $object->ID );

	if ( 'page' !== $post_type ) {
		return $url;
	}

	return in_array(
		(int) $object->ID,
		yabao_seo_utility_page_ids(),
		true
	) ? false : $url;
}
add_filter(
	'rank_math/sitemap/entry',
	'yabao_seo_rank_math_sitemap_entry',
	20,
	3
);

/**
 * Rank Math caches generated XML. After sitemap routing rules change, clear
 * that cache once so an old page-sitemap.xml cannot survive the deployment.
 */
function yabao_seo_maybe_invalidate_rank_math_sitemap_cache(): void {
	if (
		YABAO_SEO_SITEMAP_RULES_VERSION
		=== get_option( 'yabao_seo_sitemap_rules_version', '' )
	) {
		return;
	}

	if ( ! class_exists( 'RankMath\\Sitemap\\Cache' ) ) {
		return;
	}

	try {
		\RankMath\Sitemap\Cache::invalidate_storage();
	} catch ( ArgumentCountError $error ) {
		try {
			\RankMath\Sitemap\Cache::invalidate_storage( 'page' );
			\RankMath\Sitemap\Cache::invalidate_storage( 'product_cat' );
		} catch ( Throwable $fallback_error ) {
			return;
		}
	} catch ( Throwable $error ) {
		return;
	}

	update_option(
		'yabao_seo_sitemap_rules_version',
		YABAO_SEO_SITEMAP_RULES_VERSION,
		false
	);
}
add_action(
	'init',
	'yabao_seo_maybe_invalidate_rank_math_sitemap_cache',
	99
);

/**
 * If Rank Math is disabled, WordPress core resumes serving wp-sitemap.xml.
 * Keep the same taxonomy policy there as well.
 */
function yabao_seo_core_sitemap_taxonomies( array $taxonomies ): array {
	unset( $taxonomies['product_cat'] );
	return $taxonomies;
}
add_filter(
	'wp_sitemaps_taxonomies',
	'yabao_seo_core_sitemap_taxonomies'
);

/**
 * Exclude utility pages from the WordPress core page sitemap too.
 */
function yabao_seo_core_sitemap_posts_query_args(
	array $args,
	string $post_type
): array {
	if ( 'page' !== $post_type ) {
		return $args;
	}

	$existing = isset( $args['post__not_in'] )
		? array_map( 'intval', (array) $args['post__not_in'] )
		: array();

	$args['post__not_in'] = array_values(
		array_unique(
			array_merge(
				$existing,
				yabao_seo_utility_page_ids()
			)
		)
	);

	return $args;
}
add_filter(
	'wp_sitemaps_posts_query_args',
	'yabao_seo_core_sitemap_posts_query_args',
	20,
	2
);
