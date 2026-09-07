<?php
/* Cart page: force the classic WooCommerce shortcode so PHP template overrides remain authoritative. */
get_header();
?>
<main id="main-content">
	<section class="section section--dark section--compact inner-hero"><div class="container inner-hero__grid"><div><nav aria-label="Хлебные крошки" class="breadcrumbs breadcrumbs--hero"><ol><li><a href="<?php echo esc_url( home_url( '/' ) ); ?>">Главная</a></li><li><span aria-current="page">Корзина</span></li></ol></nav><h1>Корзина</h1></div><p class="inner-hero__text">Проверьте состав заказа и количество. Источник цены и наличия — WooCommerce.</p></div></section>
	<section class="section section--paper cart-page"><div class="container"><?php echo do_shortcode( '[woocommerce_cart]' ); ?></div></section>
</main>
<?php get_footer(); ?>
