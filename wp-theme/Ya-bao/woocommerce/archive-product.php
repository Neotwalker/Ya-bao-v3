<?php
/** WooCommerce shop and product taxonomy archive. */
defined( 'ABSPATH' ) || exit;
get_header();

$shop_url   = yabao_wc_page_url( 'shop' );
$search     = isset( $_GET['q'] ) ? sanitize_text_field( wp_unslash( $_GET['q'] ) ) : '';
$current_id = is_product_taxonomy() ? get_queried_object_id() : 0;
$terms      = get_terms(
	array(
		'taxonomy'   => 'product_cat',
		'hide_empty' => true,
		'parent'     => 0,
	)
);
?>
<main id="main-content">
	<section class="section section--dark section--compact inner-hero">
		<div class="container inner-hero__grid">
			<div>
				<nav aria-label="Хлебные крошки" class="breadcrumbs breadcrumbs--hero"><ol><li><a href="<?php echo esc_url( home_url( '/' ) ); ?>">Главная</a></li><li><span aria-current="page">Магазин</span></li></ol></nav>
				<h1><?php echo is_product_taxonomy() ? esc_html( single_term_title( '', false ) ) : 'Магазин китайского чая в Челябинске'; ?></h1>
			</div>
			<div><p class="inner-hero__text">Китайский чай, чайная посуда и аксессуары. Цена, наличие и товары теперь берутся из WooCommerce.</p></div>
		</div>
	</section>

	<section class="section section--paper shop-catalog" id="catalog">
		<div class="container">
			<div class="section-heading"><div><p class="eyebrow">Каталог</p><h2>Чай, посуда и аксессуары</h2></div><p>Каталог работает на данных WooCommerce. Структуру товарных полей и весовых вариаций закрепим на следующем этапе.</p></div>

			<div class="shop-filter-bar" aria-label="Категории магазина">
				<a class="shop-filter<?php echo $current_id ? '' : ' is-active'; ?>" href="<?php echo esc_url( $shop_url ); ?>">Все товары</a>
				<?php if ( ! is_wp_error( $terms ) ) : foreach ( $terms as $term ) : ?>
					<a class="shop-filter<?php echo (int) $term->term_id === (int) $current_id ? ' is-active' : ''; ?>" href="<?php echo esc_url( get_term_link( $term ) ); ?>"><?php echo esc_html( $term->name ); ?></a>
				<?php endforeach; endif; ?>
			</div>

			<form class="shop-catalog__controls yabao-wc-catalog-controls" action="<?php echo esc_url( $shop_url ); ?>" method="get">
				<label class="shop-control shop-control--search" for="shop-search"><span class="shop-control__label">Поиск по названию</span><span class="shop-search-field"><svg aria-hidden="true" viewBox="0 0 24 24"><circle cx="11" cy="11" r="6.5" fill="none" stroke="currentColor" stroke-width="1.7"></circle><path d="m16 16 4 4" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"></path></svg><input id="shop-search" type="search" maxlength="100" name="q" value="<?php echo esc_attr( $search ); ?>" placeholder="Например, пуэр"></span></label>
				<label class="shop-control"><span class="shop-control__label">Сортировка</span><span class="shop-select-field"><select name="orderby" onchange="this.form.submit()">
					<?php
					$current_order   = isset( $_GET['orderby'] ) ? wc_clean( wp_unslash( $_GET['orderby'] ) ) : 'menu_order';
					$orderby_options = apply_filters(
						'woocommerce_catalog_orderby',
						array(
							'menu_order' => 'По умолчанию',
							'title'      => 'По алфавиту',
							'price'      => 'Сначала дешевле',
							'price-desc' => 'Сначала дороже',
						)
					);
					foreach ( $orderby_options as $id => $name ) {
						printf( '<option value="%s"%s>%s</option>', esc_attr( $id ), selected( $current_order, $id, false ), esc_html( $name ) );
					}
					?>
				</select></span></label>
				<button class="button button--outline-walnut yabao-wc-search-submit" type="submit">Найти</button>
			</form>

			<?php woocommerce_output_all_notices(); ?>
			<?php if ( woocommerce_product_loop() ) : ?>
				<div class="shop-results-meta"><?php woocommerce_result_count(); ?></div>
				<div class="shop-grid" id="shop-grid">
					<?php while ( have_posts() ) : the_post(); wc_get_template_part( 'content', 'product' ); endwhile; ?>
				</div>
				<div class="yabao-wc-pagination"><?php woocommerce_pagination(); ?></div>
			<?php else : ?>
				<div class="shop-state"><div class="shop-state__icon">茶</div><h2>Товары не найдены</h2><p>Измените запрос или вернитесь ко всему каталогу.</p><a class="button button--walnut" href="<?php echo esc_url( $shop_url ); ?>">Показать все товары</a></div>
			<?php endif; ?>
		<div class="section-heading shop-seo-copy reveal" aria-labelledby="shop-seo-title">
			<div><p class="eyebrow">Магазин в Челябинске</p><h2 id="shop-seo-title">Китайский чай и чайная посуда</h2></div>
			<div class="shop-seo-copy__body">
				<p>Каталог «Я Бао Завари» теперь получает товары, цены и наличие из WooCommerce. Структуру весовых вариаций и редакционных полей закрепим на следующем этапе интеграции.</p>
				<p>Информация о посещении чайной находится на <a href="<?php echo esc_url( home_url( '/' ) ); ?>">главной</a>, а формат чайной церемонии - на отдельной <a href="<?php echo esc_url( yabao_page_url( 'chaynaya-ceremoniya' ) ); ?>">странице церемонии</a>.</p>
				<div class="shop-seo-links" aria-label="Популярные разделы магазина">
					<a class="button button--outline-walnut" href="<?php echo esc_url( $shop_url ); ?>">Все товары</a>
					<a class="button button--outline-walnut" href="<?php echo esc_url( add_query_arg( 'q', 'пуэр', $shop_url ) ); ?>">Пуэр</a>
					<a class="button button--outline-walnut" href="<?php echo esc_url( add_query_arg( 'q', 'посуда', $shop_url ) ); ?>">Чайная посуда</a>
					<a class="button button--outline-walnut" href="<?php echo esc_url( add_query_arg( 'q', 'аксессуар', $shop_url ) ); ?>">Аксессуары</a>
				</div>
			</div>
		</div>
		</div>
	</section>

	<section class="section section--dark shop-guides related-articles" aria-labelledby="shop-guides-title">
		<div class="container">
			<div class="section-heading reveal"><div><p class="eyebrow">Гиды по чаю</p><h2 id="shop-guides-title">Помочь с выбором и завариванием</h2></div><p>Информационные материалы отвечают на вопросы о видах чая и способах заваривания, а магазин остаётся отдельной коммерческой страницей.</p></div>
			<div class="related-articles__shell reveal" data-related-articles-shell>
				<div class="related-articles__slider swiper" data-related-articles-swiper><div class="swiper-wrapper">
					<div class="swiper-slide"><a class="related-article-card" href="<?php echo esc_url( yabao_page_url( 'blog/kak-vybrat-kitayskiy-chay' ) ); ?>"><span>Выбор чая</span><h3>Как выбрать китайский чай</h3><p>С чего начать, если названия и категории пока ничего не говорят.</p><strong>Читать <span aria-hidden="true">→</span></strong></a></div>
					<div class="swiper-slide"><a class="related-article-card" href="<?php echo esc_url( yabao_page_url( 'blog/chto-takoe-puer' ) ); ?>"><span>Пуэр</span><h3>Что такое пуэр</h3><p>Шэн, Шу, прессовка и основные различия без перегруженной терминологии.</p><strong>Читать <span aria-hidden="true">→</span></strong></a></div>
					<div class="swiper-slide"><a class="related-article-card" href="<?php echo esc_url( yabao_page_url( 'blog/kak-zavarivat-puer' ) ); ?>"><span>Заваривание</span><h3>Как заваривать пуэр</h3><p>Стартовая схема, температура, пропорции и логика коротких проливов.</p><strong>Читать <span aria-hidden="true">→</span></strong></a></div>
					<div class="swiper-slide"><a class="related-article-card" href="<?php echo esc_url( yabao_page_url( 'blog/chto-takoe-gaba' ) ); ?>"><span>Габа</span><h3>Что такое Габа чай</h3><p>Как устроена технология GABA и почему это не один конкретный сорт.</p><strong>Читать <span aria-hidden="true">→</span></strong></a></div>
					<div class="swiper-slide"><a class="related-article-card" href="<?php echo esc_url( yabao_page_url( 'blog/kak-zavarivat-gaba' ) ); ?>"><span>Заваривание</span><h3>Как заваривать Габа</h3><p>Базовая точка отсчёта для температуры, пропорций и времени.</p><strong>Читать <span aria-hidden="true">→</span></strong></a></div>
				</div></div>
				<div aria-label="Навигация по материалам" class="related-articles__controls" role="group"><button aria-label="Предыдущие материалы" class="related-articles__button related-articles__button--prev" data-related-articles-prev type="button"><svg aria-hidden="true" viewBox="0 0 24 24"><path d="m15 18-6-6 6-6" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"></path></svg></button><div aria-hidden="true" class="related-articles__pagination" data-related-articles-pagination></div><button aria-label="Следующие материалы" class="related-articles__button related-articles__button--next" data-related-articles-next type="button"><svg aria-hidden="true" viewBox="0 0 24 24"><path d="m9 18 6-6-6-6" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"></path></svg></button></div>
			</div>
		</div>
	</section>
</main>
<?php get_footer(); ?>
