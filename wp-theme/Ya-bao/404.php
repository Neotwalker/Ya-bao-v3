<?php
/**
 * 404 template and centralized redirects for legacy page slugs.
 */

global $wp;

$legacy_page_redirects = array(
	'privacy-policy' => 'privacy',
);

$request_path = isset( $wp->request ) ? trim( (string) $wp->request, '/' ) : '';

if ( isset( $legacy_page_redirects[ $request_path ] ) ) {
	$target = get_page_by_path( $legacy_page_redirects[ $request_path ], OBJECT, 'page' );

	if ( $target instanceof WP_Post && 'publish' === $target->post_status ) {
		wp_safe_redirect( get_permalink( $target ), 301, 'Ya Bao legacy page redirect' );
		exit;
	}
}

get_header();
?>
<main id="main-content">
	<section class="section section--dark section--compact inner-hero"><div class="container inner-hero__grid"><div><p class="eyebrow">Ошибка 404</p><h1>Страница не найдена</h1></div><p class="inner-hero__text">Адрес мог измениться. Вернитесь на главную или перейдите в магазин.</p></div></section>
	<section class="section section--paper"><div class="container"><div class="shop-state"><h2>Куда перейти?</h2><div class="product-summary__actions"><a class="button button--walnut" href="<?php echo esc_url( home_url( '/' ) ); ?>">На главную</a><a class="button button--outline-walnut" href="<?php echo esc_url( yabao_wc_page_url( 'shop' ) ); ?>">В магазин</a></div></div></div></section>
</main>
<?php get_footer(); ?>