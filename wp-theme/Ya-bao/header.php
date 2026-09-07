<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width,initial-scale=1">
	<meta name="theme-color" content="#17130f">
	<link rel="icon" href="<?php echo esc_url( yabao_asset_url( 'icons/favicon.svg' ) ); ?>" type="image/svg+xml">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="skip-link" href="#main-content">К основному содержанию</a>
<noscript><div class="no-js-note">Для работы меню и интерактивных элементов включите JavaScript.</div></noscript>
<header class="site-header<?php echo is_front_page() ? ' home-header' : ''; ?>" data-header>
	<div class="container header-inner">
		<a aria-label="Я Бао Завари - главная" class="brand" href="<?php echo esc_url( home_url( '/' ) ); ?>">
			<img alt="" decoding="async" height="56" src="<?php echo esc_url( yabao_asset_url( 'icons/logo-mark.svg' ) ); ?>" width="56">
			<span><strong>Я Бао Завари</strong><small>чайная на Кировке</small></span>
		</a>
		<nav aria-label="Основная навигация" class="site-nav">
			<?php
			wp_nav_menu(
				array(
					'theme_location' => 'primary',
					'container'      => false,
					'fallback_cb'    => 'yabao_nav_fallback',
					'depth'          => 1,
				)
			);
			?>
		</nav>
		<div class="header-actions">
			<div class="header-meta">
				<a class="header-phone" href="tel:+79995847290">
					<svg aria-hidden="true" viewBox="0 0 24 24"><path d="M6.6 10.8a15.4 15.4 0 0 0 6.6 6.6l2.2-2.2c.3-.3.7-.4 1.1-.3 1.2.4 2.4.6 3.7.6.6 0 1 .4 1 1V21c0 .6-.4 1-1 1C10.6 22 2 13.4 2 3c0-.6.4-1 1-1h4.5c.6 0 1 .4 1 1 0 1.3.2 2.5.6 3.7.1.4 0 .8-.3 1.1l-2.2 2.2Z" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"></path></svg>
					<span>+7 999 584-72-90</span>
				</a>
				<div class="header-socials">
					<a aria-label="ВКонтакте" class="social-link" href="https://vk.ru/yabaozavary" rel="noopener" target="_blank"><img alt="" aria-hidden="true" decoding="async" height="48" src="<?php echo esc_url( yabao_asset_url( 'icons/vk.svg' ) ); ?>" width="48"></a>
					<a aria-label="Телеграм" class="social-link" href="https://t.me/yabaozavari" rel="noopener" target="_blank"><svg aria-hidden="true" viewBox="0 0 24 24"><path d="M20.7 4.3 3.9 10.8c-.9.4-.8 1.6.1 1.8l4.3 1.3 1.6 4.8c.3.9 1.5 1 1.9.2l2.3-4.1 4.1 3.3c.7.6 1.8.2 2-.7l2.4-11.7c.2-1-.8-1.9-1.9-1.4ZM9.5 13.4l8-6.1-6.3 7.4-.5 2.5-1.2-3.8Z" fill="currentColor"></path></svg></a>
				</div>
			</div>
			<a aria-label="Корзина: <?php echo esc_attr( (string) yabao_cart_count() ); ?> товаров" class="cart-indicator" data-cart-indicator href="<?php echo esc_url( yabao_wc_page_url( 'cart' ) ); ?>">
				<svg aria-hidden="true" viewBox="0 0 24 24"><path d="M3.5 5h2l1.7 9.1a2 2 0 0 0 2 1.6h7.9a2 2 0 0 0 1.9-1.4L21 8H7" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7"></path><circle cx="9.5" cy="19" fill="currentColor" r="1"></circle><circle cx="17.5" cy="19" fill="currentColor" r="1"></circle></svg>
				<span aria-hidden="true" class="cart-indicator__count" data-cart-count><?php echo esc_html( (string) yabao_cart_count() ); ?></span>
			</a>
			<button class="button button--primary" data-modal-open data-source="header" type="button">Забронировать</button>
			<button aria-controls="mobile-navigation" aria-expanded="false" class="menu-toggle" data-menu-toggle type="button"><span></span><span></span><span></span><span class="visually-hidden">Открыть меню</span></button>
		</div>
	</div>
</header>
<div aria-hidden="true" class="mobile-nav" data-mobile-nav id="mobile-navigation">
	<nav aria-label="Мобильная навигация">
		<?php
		wp_nav_menu(
			array(
				'theme_location' => 'primary',
				'container'      => false,
				'fallback_cb'    => 'yabao_nav_fallback',
				'depth'          => 1,
			)
		);
		?>
	</nav>
	<button class="button button--primary mobile-nav__booking" data-modal-open data-source="mobile-nav" type="button">Забронировать</button>
	<div class="mobile-nav__contacts">
		<a class="mobile-nav__phone" href="tel:+79995847290"><svg aria-hidden="true" viewBox="0 0 24 24"><path d="M6.6 10.8a15.4 15.4 0 0 0 6.6 6.6l2.2-2.2c.3-.3.7-.4 1.1-.3 1.2.4 2.4.6 3.7.6.6 0 1 .4 1 1V21c0 .6-.4 1-1 1C10.6 22 2 13.4 2 3c0-.6.4-1 1-1h4.5c.6 0 1 .4 1 1 0 1.3.2 2.5.6 3.7.1.4 0 .8-.3 1.1l-2.2 2.2Z" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"></path></svg><span>+7 999 584-72-90</span></a>
		<div class="mobile-nav__socials">
			<a aria-label="ВКонтакте" class="social-link" href="https://vk.ru/yabaozavary" rel="noopener" target="_blank"><img alt="" aria-hidden="true" decoding="async" height="48" src="<?php echo esc_url( yabao_asset_url( 'icons/vk.svg' ) ); ?>" width="48"></a>
			<a aria-label="Телеграм" class="social-link" href="https://t.me/yabaozavari" rel="noopener" target="_blank"><svg aria-hidden="true" viewBox="0 0 24 24"><path d="M20.7 4.3 3.9 10.8c-.9.4-.8 1.6.1 1.8l4.3 1.3 1.6 4.8c.3.9 1.5 1 1.9.2l2.3-4.1 4.1 3.3c.7.6 1.8.2 2-.7l2.4-11.7c.2-1-.8-1.9-1.9-1.4ZM9.5 13.4l8-6.1-6.3 7.4-.5 2.5-1.2-3.8Z" fill="currentColor"></path></svg></a>
		</div>
	</div>
	<div class="mobile-nav__meta">
		<strong>Челябинск, Кирова, 94</strong>
		<a href="https://2gis.ru/chelyabinsk/firm/70000001110715460" rel="noopener" target="_blank">Открыть в 2ГИС</a>
		<a href="https://yandex.ru/maps/org/ya_bao_zavari/112754832500/" rel="noopener" target="_blank">Открыть в Яндекс Картах</a>
	</div>
</div>
