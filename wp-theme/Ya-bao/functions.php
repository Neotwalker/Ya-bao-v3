<?php
/**
 * Ya Bao Zavari theme bootstrap.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const YABAO_THEME_VERSION = '0.2.3';

function yabao_asset_url( string $path = '' ): string {
	return trailingslashit( get_template_directory_uri() ) . 'assets/' . ltrim( $path, '/' );
}

function yabao_page_url( string $slug = '' ): string {
	$slug = trim( $slug, '/' );
	return $slug ? home_url( '/' . $slug . '/' ) : home_url( '/' );
}

function yabao_woocommerce_active(): bool {
	return class_exists( 'WooCommerce' );
}

function yabao_wc_page_url( string $page ): string {
	if ( ! yabao_woocommerce_active() ) {
		return yabao_page_url( $page );
	}

	if ( 'shop' === $page ) {
		$url = wc_get_page_permalink( 'shop' );
		return $url ?: yabao_page_url( 'shop' );
	}
	if ( 'cart' === $page ) {
		return wc_get_cart_url();
	}
	if ( 'checkout' === $page ) {
		return wc_get_checkout_url();
	}

	return yabao_page_url( $page );
}

function yabao_setup(): void {
	load_theme_textdomain( 'ya-bao', get_template_directory() . '/languages' );

	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'html5', array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script' ) );
	add_theme_support( 'woocommerce' );
	add_theme_support( 'wc-product-gallery-zoom' );
	add_theme_support( 'wc-product-gallery-lightbox' );
	add_theme_support( 'wc-product-gallery-slider' );

	register_nav_menus(
		array(
			'primary' => __( 'Основное меню', 'ya-bao' ),
			'footer'  => __( 'Меню в подвале', 'ya-bao' ),
		)
	);
}
add_action( 'after_setup_theme', 'yabao_setup' );

function yabao_enqueue_assets(): void {
	wp_enqueue_style( 'yabao-base', yabao_asset_url( 'css/base.css' ), array(), YABAO_THEME_VERSION );
	wp_enqueue_style( 'yabao-components', yabao_asset_url( 'css/components.css' ), array( 'yabao-base' ), YABAO_THEME_VERSION );
	wp_enqueue_style( 'yabao-responsive', yabao_asset_url( 'css/responsive.css' ), array( 'yabao-components' ), YABAO_THEME_VERSION );
	wp_enqueue_style( 'yabao-custom', yabao_asset_url( 'css/custom.css' ), array( 'yabao-responsive' ), YABAO_THEME_VERSION );

	if ( is_front_page() ) {
		wp_enqueue_style( 'yabao-home', yabao_asset_url( 'css/home-v4.css' ), array( 'yabao-custom' ), YABAO_THEME_VERSION );
		wp_enqueue_style( 'yabao-fancybox', yabao_asset_url( 'vendor/fancybox/fancybox.min.css' ), array(), YABAO_THEME_VERSION );
		wp_enqueue_style( 'yabao-swiper', yabao_asset_url( 'vendor/swiper/swiper-bundle.min.css' ), array(), YABAO_THEME_VERSION );
		wp_enqueue_script( 'yabao-fancybox', yabao_asset_url( 'vendor/fancybox/fancybox.min.js' ), array(), YABAO_THEME_VERSION, true );
		wp_enqueue_script( 'yabao-swiper', yabao_asset_url( 'vendor/swiper/swiper-bundle.min.js' ), array(), YABAO_THEME_VERSION, true );
		wp_enqueue_script( 'yabao-home', yabao_asset_url( 'js/home.js' ), array(), YABAO_THEME_VERSION, true );
	} else {
		wp_enqueue_style( 'yabao-pages', yabao_asset_url( 'css/pages.css' ), array( 'yabao-custom' ), YABAO_THEME_VERSION );
	}

	if ( yabao_woocommerce_active() && ( is_shop() || is_product_taxonomy() || is_product() || is_cart() || is_checkout() ) ) {
		wp_enqueue_style( 'yabao-shop', yabao_asset_url( 'css/shop.css' ), array( 'yabao-pages' ), YABAO_THEME_VERSION );
	}
	wp_enqueue_style( 'yabao-wp', yabao_asset_url( 'css/wp-integration.css' ), array( is_front_page() ? 'yabao-home' : 'yabao-pages' ), YABAO_THEME_VERSION );

	if ( yabao_woocommerce_active() && ( is_shop() || is_product_taxonomy() || is_product() ) ) {
		wp_enqueue_style( 'yabao-swiper', yabao_asset_url( 'vendor/swiper/swiper-bundle.min.css' ), array(), YABAO_THEME_VERSION );
		wp_enqueue_script( 'yabao-swiper', yabao_asset_url( 'vendor/swiper/swiper-bundle.min.js' ), array(), YABAO_THEME_VERSION, true );
		wp_enqueue_script( 'yabao-wp-shop', yabao_asset_url( 'js/wp-shop.js' ), array(), YABAO_THEME_VERSION, true );
	}

	if ( yabao_woocommerce_active() ) {
		wp_enqueue_script( 'wc-add-to-cart' );
		wp_enqueue_script( 'wc-cart-fragments' );
	}

	if ( yabao_woocommerce_active() && is_cart() ) {
		wp_enqueue_script( 'yabao-wp-cart', yabao_asset_url( 'js/wp-cart.js' ), array(), YABAO_THEME_VERSION, true );
	}

	wp_enqueue_script( 'yabao-wp-app', yabao_asset_url( 'js/wp-app.js' ), array(), YABAO_THEME_VERSION, true );
	wp_localize_script(
		'yabao-wp-app',
		'yabaoWoo',
		array(
			'ajaxUrl'     => admin_url( 'admin-ajax.php' ),
			'nonce'       => wp_create_nonce( 'yabao-mini-cart' ),
			'cartUrl'     => yabao_wc_page_url( 'cart' ),
			'checkoutUrl' => yabao_wc_page_url( 'checkout' ),
			'shopUrl'     => yabao_wc_page_url( 'shop' ),
		)
	);
}
add_action( 'wp_enqueue_scripts', 'yabao_enqueue_assets', 20 );

function yabao_module_script_tag( string $tag, string $handle, string $src ): string {
	$module_handles = array( 'yabao-wp-app', 'yabao-wp-shop', 'yabao-wp-cart', 'yabao-home' );
	if ( ! in_array( $handle, $module_handles, true ) ) {
		return $tag;
	}
	return sprintf( '<script type="module" src="%s" id="%s-js"></script>\n', esc_url( $src ), esc_attr( $handle ) );
}
add_filter( 'script_loader_tag', 'yabao_module_script_tag', 10, 3 );

function yabao_dequeue_woocommerce_styles( array $enqueue_styles ): array {
	unset( $enqueue_styles['woocommerce-general'], $enqueue_styles['woocommerce-layout'], $enqueue_styles['woocommerce-smallscreen'] );
	return $enqueue_styles;
}
add_filter( 'woocommerce_enqueue_styles', 'yabao_dequeue_woocommerce_styles' );

function yabao_body_classes( array $classes ): array {
	if ( is_front_page() ) {
		$classes[] = 'page-home';
		$classes[] = 'page-home-v4';
	} elseif ( yabao_woocommerce_active() && ( is_shop() || is_product_taxonomy() ) ) {
		$classes[] = 'page-shop';
		$classes[] = 'page-inner';
	} elseif ( yabao_woocommerce_active() && is_product() ) {
		$classes[] = 'page-product';
		$classes[] = 'page-inner';
	} elseif ( yabao_woocommerce_active() && is_cart() ) {
		$classes[] = 'page-cart';
		$classes[] = 'page-inner';
	} elseif ( yabao_woocommerce_active() && is_checkout() ) {
		$classes[] = 'page-checkout';
		$classes[] = 'page-inner';
	} else {
		$classes[] = 'page-inner';
	}
	return array_values( array_unique( $classes ) );
}
add_filter( 'body_class', 'yabao_body_classes' );

function yabao_wp_robots( array $robots ): array {
	if ( yabao_woocommerce_active() && ( is_cart() || is_checkout() || is_account_page() ) ) {
		$robots['noindex'] = true;
		$robots['follow']  = true;
	}
	return $robots;
}
add_filter( 'wp_robots', 'yabao_wp_robots' );

function yabao_is_shop_context(): bool {
	return yabao_woocommerce_active() && ( is_shop() || is_product_taxonomy() || is_product() || is_cart() || is_checkout() );
}

function yabao_cart_count(): int {
	return yabao_woocommerce_active() && WC()->cart ? (int) WC()->cart->get_cart_contents_count() : 0;
}

function yabao_cart_total_html(): string {
	return yabao_woocommerce_active() && WC()->cart ? WC()->cart->get_cart_subtotal() : '0 ₽';
}

function yabao_render_mini_cart_content(): string {
	ob_start();
	if ( yabao_woocommerce_active() ) {
		woocommerce_mini_cart();
	}
	return (string) ob_get_clean();
}

function yabao_cart_fragments( array $fragments ): array {
	$count = yabao_cart_count();
	$fragments['span[data-cart-count]'] = '<span aria-hidden="true" class="cart-indicator__count" data-cart-count>' . esc_html( (string) $count ) . '</span>';
	$fragments['[data-wc-mini-cart-content]'] = '<div data-wc-mini-cart-content>' . yabao_render_mini_cart_content() . '</div>';
	return $fragments;
}
add_filter( 'woocommerce_add_to_cart_fragments', 'yabao_cart_fragments' );

function yabao_update_mini_cart_quantity(): void {
	check_ajax_referer( 'yabao-mini-cart', 'nonce' );

	if ( ! yabao_woocommerce_active() || ! WC()->cart ) {
		wp_send_json_error( array( 'message' => 'Корзина недоступна.' ), 400 );
	}

	$key      = isset( $_POST['cart_item_key'] ) ? wc_clean( wp_unslash( $_POST['cart_item_key'] ) ) : '';
	$quantity = isset( $_POST['quantity'] ) ? max( 0, absint( $_POST['quantity'] ) ) : 0;
	$item     = WC()->cart->get_cart_item( $key );

	if ( ! $key || ! $item || empty( $item['data'] ) ) {
		wp_send_json_error( array( 'message' => 'Позиция корзины не найдена.' ), 404 );
	}

	$product = $item['data'];
	$max     = (int) $product->get_max_purchase_quantity();
	if ( $max > 0 ) {
		$quantity = min( $quantity, $max );
	}

	WC()->cart->set_quantity( $key, $quantity, true );
	WC()->cart->calculate_totals();

	wp_send_json_success(
		array(
			'count'    => yabao_cart_count(),
			'total'    => wp_kses_post( yabao_cart_total_html() ),
			'miniCart' => yabao_render_mini_cart_content(),
		)
	);
}
add_action( 'wp_ajax_yabao_update_mini_cart_quantity', 'yabao_update_mini_cart_quantity' );
add_action( 'wp_ajax_nopriv_yabao_update_mini_cart_quantity', 'yabao_update_mini_cart_quantity' );

function yabao_shop_query( WP_Query $query ): void {
	if ( is_admin() || ! $query->is_main_query() || ! yabao_woocommerce_active() ) {
		return;
	}
	if ( ! is_shop() && ! is_product_taxonomy() ) {
		return;
	}

	if ( isset( $_GET['q'] ) && '' !== trim( (string) $_GET['q'] ) ) {
		$query->set( 's', sanitize_text_field( wp_unslash( $_GET['q'] ) ) );
	}
}
add_action( 'pre_get_posts', 'yabao_shop_query' );

function yabao_catalog_ordering_args( array $args ): array {
	if ( isset( $_GET['orderby'] ) ) {
		return $args;
	}
	$args['orderby'] = 'menu_order title';
	$args['order']   = 'ASC';
	return $args;
}
add_filter( 'woocommerce_get_catalog_ordering_args', 'yabao_catalog_ordering_args' );


function yabao_handle_clear_cart(): void {
	if ( empty( $_POST['yabao_clear_cart'] ) || ! yabao_woocommerce_active() || ! WC()->cart ) {
		return;
	}
	if ( empty( $_POST['woocommerce-cart-nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['woocommerce-cart-nonce'] ) ), 'woocommerce-cart' ) ) {
		return;
	}
	WC()->cart->empty_cart();
	wp_safe_redirect( wc_get_cart_url() );
	exit;
}
add_action( 'template_redirect', 'yabao_handle_clear_cart' );

function yabao_product_terms_text( WC_Product $product ): string {
	$terms = get_the_terms( $product->get_id(), 'product_cat' );
	if ( ! is_array( $terms ) || ! $terms ) {
		return 'Товар';
	}
	return $terms[0]->name;
}

function yabao_product_detail_text( WC_Product $product ): string {
	$short = trim( wp_strip_all_tags( $product->get_short_description() ) );
	if ( $short ) {
		return wp_html_excerpt( $short, 120, '…' );
	}
	return $product->is_type( 'variable' ) ? 'Доступны варианты' : 'Поштучно';
}

function yabao_cart_item_variant_text( array $cart_item ): string {
	$text = wc_get_formatted_cart_item_data( $cart_item, true );
	return $text ? wp_strip_all_tags( $text ) : '';
}

function yabao_nav_fallback( $args = array() ): void {
	$items = array(
		array( 'label' => 'Магазин', 'url' => yabao_wc_page_url( 'shop' ), 'current' => yabao_is_shop_context() ),
		array( 'label' => 'О чайной', 'url' => yabao_page_url( 'about' ), 'current' => is_page( 'about' ) ),
		array( 'label' => 'Мероприятия', 'url' => yabao_page_url( 'events' ), 'current' => is_page( 'events' ) ),
		array( 'label' => 'Блог', 'url' => yabao_page_url( 'blog' ), 'current' => is_home() || is_singular( 'post' ) ),
		array( 'label' => 'Контакты', 'url' => yabao_page_url( 'contacts' ), 'current' => is_page( 'contacts' ) ),
	);

	echo '<ul>';
	foreach ( $items as $item ) {
		printf(
			'<li><a%s href="%s">%s</a></li>',
			$item['current'] ? ' aria-current="page"' : '',
			esc_url( $item['url'] ),
			esc_html( $item['label'] )
		);
	}
	echo '</ul>';
}

function yabao_catalog_orderby( array $options ): array {
	return array(
		'menu_order' => 'По умолчанию',
		'title'      => 'По алфавиту',
		'price'      => 'Сначала дешевле',
		'price-desc' => 'Сначала дороже',
	) + $options;
}
add_filter( 'woocommerce_catalog_orderby', 'yabao_catalog_orderby' );

function yabao_catalog_title_ordering( array $args ): array {
	$orderby = isset( $_GET['orderby'] ) ? wc_clean( wp_unslash( $_GET['orderby'] ) ) : '';
	if ( 'title' === $orderby ) {
		$args['orderby'] = 'title';
		$args['order']   = 'ASC';
	}
	return $args;
}
add_filter( 'woocommerce_get_catalog_ordering_args', 'yabao_catalog_title_ordering', 20 );
