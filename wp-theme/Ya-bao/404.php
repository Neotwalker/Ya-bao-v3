<?php
get_header();
?>
<main id="main-content">
	<section class="section section--dark section--compact inner-hero"><div class="container inner-hero__grid"><div><p class="eyebrow">Ошибка 404</p><h1>Страница не найдена</h1></div><p class="inner-hero__text">Адрес мог измениться. Вернитесь на главную или перейдите в магазин.</p></div></section>
	<section class="section section--paper"><div class="container"><div class="shop-state"><h2>Куда перейти?</h2><div class="product-summary__actions"><a class="button button--walnut" href="<?php echo esc_url( home_url( '/' ) ); ?>">На главную</a><a class="button button--outline-walnut" href="<?php echo esc_url( yabao_wc_page_url( 'shop' ) ); ?>">В магазин</a></div></div></div></section>
</main>
<?php get_footer(); ?>
