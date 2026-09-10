<?php
/**
 * Delivery and pickup page.
 *
 * Editor-facing descriptive content comes from ACF. The commercial free-shipping
 * rule is rendered from the same server-side setting used by checkout so the
 * public page cannot drift from the actual order calculation.
 */

get_header();

$get_text = static function ( string $name, string $fallback ): string {
	if ( function_exists( 'get_field' ) ) {
		$value = get_field( $name );
		if ( is_string( $value ) && '' !== trim( $value ) ) {
			return trim( wp_strip_all_tags( $value ) );
		}
	}
	return $fallback;
};

$get_paragraphs = static function ( string $name, string $fallback ): string {
	$value = function_exists( 'get_field' ) ? get_field( $name ) : '';
	$text  = is_string( $value ) && '' !== trim( $value ) ? $value : $fallback;
	return wp_kses_post( wpautop( wp_strip_all_tags( $text ) ) );
};

$delivery_intro       = $get_text( 'delivery_intro', 'Доставка по Челябинску и другим городам. Выберите удобный способ получения заказа.' );
$pickup_title         = $get_text( 'pickup_title', 'Самовывоз из чайной' );
$pickup_address       = $get_text( 'pickup_address', 'Челябинск, ул. Кирова, 94' );
$pickup_hours         = $get_text( 'pickup_hours', 'ежедневно с 12:00 до 01:00' );
$pickup_description   = $get_paragraphs( 'pickup_description', 'Заказ можно забрать в чайной после подтверждения готовности.' );
$delivery_title       = $get_text( 'delivery_title', 'Доставка по России' );
$delivery_description = $get_paragraphs( 'delivery_description', 'Отправляем заказы через Авито Доставку, СДЭК, 5Post и Почту России.' );
$delivery_timing      = $get_text( 'delivery_timing_text', 'Обычно 2–10 дней в зависимости от города и службы доставки.' );

$free_shipping_threshold = function_exists( 'yabao_delivery_free_shipping_threshold' )
	? yabao_delivery_free_shipping_threshold()
	: 10000.0;
$free_shipping_label = function_exists( 'yabao_delivery_format_free_shipping_threshold' )
	? yabao_delivery_format_free_shipping_threshold( $free_shipping_threshold )
	: number_format( $free_shipping_threshold, 0, ',', ' ' ) . ' ₽';

$delivery_rules = wp_kses_post(
	wpautop(
		sprintf(
			'Стоимость и условия определяются правилами выбранной службы доставки. При сумме товаров от %s доставка для покупателя бесплатная. Порог считается после применения скидок и промокодов.',
			$free_shipping_label
		)
	)
);
?>
<main id="main-content">
	<?php while ( have_posts() ) : the_post(); ?>
	<section class="section section--dark section--compact inner-hero">
		<div class="container inner-hero__grid">
			<div>
				<nav aria-label="Хлебные крошки" class="breadcrumbs breadcrumbs--hero"><ol><li><a href="<?php echo esc_url( home_url( '/' ) ); ?>">Главная</a></li><li><span aria-current="page"><?php the_title(); ?></span></li></ol></nav>
				<h1><?php the_title(); ?></h1>
			</div>
			<p class="inner-hero__text"><?php echo esc_html( $delivery_intro ); ?></p>
		</div>
	</section>

	<section class="section section--paper delivery-page">
		<div class="container">
			<div class="delivery-intro"><p><?php echo esc_html( $delivery_intro ); ?></p></div>

			<div class="delivery-methods">
				<article class="delivery-method">
					<div class="delivery-method__icon"><svg aria-hidden="true" viewBox="0 0 24 24"><path d="M4 9.5 12 4l8 5.5V20H4V9.5Z" fill="none" stroke="currentColor" stroke-linejoin="round" stroke-width="1.7"></path><path d="M8 20v-6h8v6" fill="none" stroke="currentColor" stroke-width="1.7"></path></svg></div>
					<p class="eyebrow">Самовывоз</p>
					<h2><?php echo esc_html( $pickup_title ); ?></h2>
					<div><?php echo $pickup_description; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
					<ul class="delivery-method__facts">
						<li><span>Адрес</span><strong><?php echo esc_html( $pickup_address ); ?></strong></li>
						<li><span>Время</span><strong><?php echo esc_html( $pickup_hours ); ?></strong></li>
						<li><span>Стоимость</span><strong>Бесплатно</strong></li>
					</ul>
					<div class="delivery-method__actions">
						<a class="button button--outline-walnut" href="https://yandex.ru/maps/org/ya_bao_zavari/112754832500/" rel="noopener" target="_blank">Яндекс Карты</a>
						<a href="https://2gis.ru/chelyabinsk/firm/70000001110715460" rel="noopener" target="_blank">Открыть в 2ГИС</a>
					</div>
				</article>

				<article class="delivery-method">
					<div class="delivery-method__icon"><svg aria-hidden="true" viewBox="0 0 24 24"><path d="M3 7h11v10H3V7Z" fill="none" stroke="currentColor" stroke-linejoin="round" stroke-width="1.7"></path><path d="M14 10h3l4 4v3h-7v-7Z" fill="none" stroke="currentColor" stroke-linejoin="round" stroke-width="1.7"></path><circle cx="7" cy="18" r="2" fill="none" stroke="currentColor" stroke-width="1.7"></circle><circle cx="18" cy="18" r="2" fill="none" stroke="currentColor" stroke-width="1.7"></circle></svg></div>
					<p class="eyebrow">Доставка</p>
					<h2><?php echo esc_html( $delivery_title ); ?></h2>
					<div><?php echo $delivery_description; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
					<ul class="delivery-method__facts">
						<li><span>География</span><strong>Челябинск и другие города России</strong></li>
						<li><span>Службы</span><strong>Авито, СДЭК, 5Post, Почта России</strong></li>
						<li><span>Стоимость</span><strong>Бесплатно от <?php echo esc_html( $free_shipping_label ); ?></strong></li>
						<li><span>Срок</span><strong><?php echo esc_html( $delivery_timing ); ?></strong></li>
					</ul>
					<div class="delivery-method__actions"><a class="button button--walnut" href="<?php echo esc_url( yabao_wc_page_url( 'checkout' ) ); ?>">Перейти к оформлению</a></div>
				</article>
			</div>

			<div class="delivery-status"><strong>Условия доставки</strong><div><?php echo $delivery_rules; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div></div>
		</div>
	</section>

	<section class="section section--paper delivery-process">
		<div class="container">
			<div class="section-heading"><div><p class="eyebrow">Как это работает</p><h2>Выбор способа получения</h2></div></div>
			<div class="delivery-process__grid">
				<article class="delivery-step"><span class="delivery-step__number">01</span><h3>Выберите способ</h3><p>На оформлении доступны самовывоз, Авито Доставка, СДЭК, 5Post и Почта России.</p></article>
				<article class="delivery-step"><span class="delivery-step__number">02</span><h3>Укажите данные</h3><p>Для самовывоза адрес не требуется. Для доставки укажите город, адрес и почтовый индекс.</p></article>
				<article class="delivery-step"><span class="delivery-step__number">03</span><h3>Проверьте стоимость</h3><p>От <?php echo esc_html( $free_shipping_label ); ?> доставка бесплатная. Ниже порога тариф выбранной службы подтверждается до оплаты.</p></article>
			</div>
		</div>
	</section>

	<section class="section section--paper">
		<div class="container delivery-pending">
			<div>
				<p class="eyebrow">Доставка по России</p>
				<h2>Что важно знать</h2>
				<ul class="delivery-pending__list">
					<li>срок обычно составляет от 2 до 10 дней и зависит от города и службы;</li>
					<li>при сумме товаров от <?php echo esc_html( $free_shipping_label ); ?> доставка бесплатная;</li>
					<li>при сумме товаров меньше <?php echo esc_html( $free_shipping_label ); ?> стоимость рассчитывается по тарифу выбранной службы;</li>
					<li>порог бесплатной доставки считается после применения скидок и промокодов;</li>
					<li>ограничения по габаритам, пунктам выдачи и срокам хранения определяются правилами перевозчика;</li>
					<li>самовывоз доступен по адресу <?php echo esc_html( $pickup_address ); ?> в часы работы чайной.</li>
				</ul>
			</div>
			<aside class="delivery-pending__cta">
				<strong>Корзина уже собрана?</strong>
				<p>Перейдите к оформлению и выберите подходящий способ получения.</p>
				<a class="button button--walnut" href="<?php echo esc_url( yabao_wc_page_url( 'checkout' ) ); ?>">Оформить заказ</a>
				<a class="button button--outline-walnut" href="<?php echo esc_url( yabao_wc_page_url( 'shop' ) ); ?>">Вернуться в магазин</a>
			</aside>
		</div>
	</section>
	<?php endwhile; ?>
</main>
<?php get_footer(); ?>
