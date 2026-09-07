<?php
/* Checkout page: force classic WooCommerce checkout instead of the block template. */
get_header();
?>
<main id="main-content">
	<section class="section section--dark section--compact inner-hero"><div class="container inner-hero__grid"><div><nav aria-label="Хлебные крошки" class="breadcrumbs breadcrumbs--hero"><ol><li><a href="<?php echo esc_url( home_url( '/' ) ); ?>">Главная</a></li><li><a href="<?php echo esc_url( yabao_wc_page_url( 'cart' ) ); ?>">Корзина</a></li><li><span aria-current="page">Оформление</span></li></ol></nav><h1>Оформление заказа</h1></div><p class="inner-hero__text">Контакты, получение и итог заказа обрабатывает WooCommerce. Реальные доставка и платёжный провайдер подключаются на следующих этапах.</p></div></section>
	<section class="section section--paper checkout-page"><div class="container"><?php echo do_shortcode( '[woocommerce_checkout]' ); ?></div></section>
</main>
<?php get_footer(); ?>
