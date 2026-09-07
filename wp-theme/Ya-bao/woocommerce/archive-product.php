<?php
/** WooCommerce shop and product taxonomy archive. */
defined( 'ABSPATH' ) || exit;
get_header();

global $wp_query;

$shop_url = yabao_wc_page_url( 'shop' );
$search   = isset( $_GET['q'] ) ? sanitize_text_field( wp_unslash( $_GET['q'] ) ) : '';

$current_order = isset( $_GET['orderby'] ) ? wc_clean( wp_unslash( $_GET['orderby'] ) ) : 'menu_order';
$current_cat   = isset( $_GET['product_cat'] ) ? sanitize_title( wp_unslash( $_GET['product_cat'] ) ) : '';
if ( is_product_category() ) {
	$queried = get_queried_object();
	if ( $queried instanceof WP_Term ) {
		$current_cat = $queried->slug;
	}
}

$terms = get_terms(
	array(
		'taxonomy'   => 'product_cat',
		'hide_empty' => true,
		'parent'     => 0,
	)
);

$orderby_options = array(
	'menu_order' => 'По умолчанию',
	'title'      => 'По названию А–Я',
	'price'      => 'Сначала дешевле',
	'price-desc' => 'Сначала дороже',
);
$active_filter_count = ( $current_cat ? 1 : 0 ) + ( 'menu_order' !== $current_order ? 1 : 0 );
$preserved_args      = array();
if ( $search ) {
	$preserved_args['q'] = $search;
}
if ( 'menu_order' !== $current_order ) {
	$preserved_args['orderby'] = $current_order;
}
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

			<div class="yabao-wc-catalog-shell" data-wc-catalog-shell aria-live="polite">
			<form class="shop-catalog__controls yabao-wc-catalog-controls" action="<?php echo esc_url( $shop_url ); ?>" method="get" data-wc-catalog-controls>
				<label class="shop-control shop-control--search" for="shop-search">
					<span class="shop-control__label">Поиск по названию</span>
					<span class="shop-search-field">
						<svg aria-hidden="true" viewBox="0 0 24 24"><circle cx="11" cy="11" r="6.5" fill="none" stroke="currentColor" stroke-width="1.7"></circle><path d="m16 16 4 4" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"></path></svg>
						<input id="shop-search" type="search" maxlength="100" autocomplete="off" name="q" value="<?php echo esc_attr( $search ); ?>" placeholder="Например, пуэр" aria-controls="shop-grid" />
					</span>
				</label>

				<button class="shop-filters-toggle" type="button" data-wc-filters-toggle aria-expanded="false" aria-controls="shop-filter-panel">
					<svg aria-hidden="true" viewBox="0 0 24 24"><path d="M4 7h10M18 7h2M4 17h2M10 17h10M14 4v6M7 14v6" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"></path></svg>
					<span>Фильтры</span>
					<span class="shop-filters-toggle__count"<?php echo $active_filter_count ? '' : ' hidden'; ?>><?php echo esc_html( (string) $active_filter_count ); ?></span>
					<svg class="shop-filters-toggle__chevron" aria-hidden="true" viewBox="0 0 24 24"><path d="m7 9 5 5 5-5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"></path></svg>
				</button>

				<div class="shop-filter-panel" id="shop-filter-panel" data-wc-filter-panel>
					<label class="shop-control" for="shop-category">
						<span class="shop-control__label">Категория</span>
						<span class="shop-select-field"><select id="shop-category" name="product_cat" data-shop-category aria-controls="shop-grid" data-wc-auto-submit>
							<option value="">Все категории</option>
							<?php if ( ! is_wp_error( $terms ) ) : foreach ( $terms as $term ) : ?>
								<option value="<?php echo esc_attr( $term->slug ); ?>"<?php selected( $current_cat, $term->slug ); ?>><?php echo esc_html( $term->name ); ?></option>
							<?php endforeach; endif; ?>
						</select></span>
					</label>
					<label class="shop-control" for="shop-sort">
						<span class="shop-control__label">Сортировка</span>
						<span class="shop-select-field"><select id="shop-sort" name="orderby" data-shop-sort aria-controls="shop-grid" data-wc-auto-submit>
							<?php foreach ( $orderby_options as $id => $name ) : ?>
								<option value="<?php echo esc_attr( $id ); ?>"<?php selected( $current_order, $id ); ?>><?php echo esc_html( $name ); ?></option>
							<?php endforeach; ?>
						</select></span>
					</label>
				</div>
			</form>

			<div class="shop-catalog__toolbar">
				<div class="shop-filter-bar" role="group" aria-label="Категории магазина">
					<a class="shop-filter<?php echo $current_cat ? '' : ' is-active'; ?>" data-wc-category=""<?php echo $current_cat ? '' : ' aria-current="page"'; ?> href="<?php echo esc_url( add_query_arg( $preserved_args, $shop_url ) ); ?>">Все</a>
					<?php if ( ! is_wp_error( $terms ) ) : foreach ( $terms as $term ) :
						$term_link = get_term_link( $term );
						if ( is_wp_error( $term_link ) ) {
							continue;
						}
						$term_link = add_query_arg( $preserved_args, $term_link );
						$is_active = $current_cat === $term->slug;
					?>
						<a class="shop-filter<?php echo $is_active ? ' is-active' : ''; ?>" data-wc-category="<?php echo esc_attr( $term->slug ); ?>"<?php echo $is_active ? ' aria-current="page"' : ''; ?> href="<?php echo esc_url( $term_link ); ?>"><?php echo esc_html( $term->name ); ?></a>
					<?php endforeach; endif; ?>
				</div>
				<div class="shop-catalog__summary">
					<p class="shop-result-count" aria-live="polite">Найдено: <?php echo esc_html( (string) (int) $wp_query->found_posts ); ?></p>
					<?php if ( $search || $current_cat || 'menu_order' !== $current_order ) : ?><a class="shop-reset" href="<?php echo esc_url( $shop_url ); ?>">Сбросить</a><?php endif; ?>
				</div>
			</div>

			<?php woocommerce_output_all_notices(); ?>
			<?php if ( woocommerce_product_loop() ) : ?>
				<div class="shop-grid" id="shop-grid">
					<?php while ( have_posts() ) : the_post(); wc_get_template_part( 'content', 'product' ); endwhile; ?>
				</div>
				<div class="yabao-wc-pagination"><?php woocommerce_pagination(); ?></div>
			<?php else : ?>
				<div class="shop-state"><div class="shop-state__icon">茶</div><h2>Товары не найдены</h2><p>Измените запрос или вернитесь ко всему каталогу.</p><a class="button button--walnut" href="<?php echo esc_url( $shop_url ); ?>">Показать все товары</a></div>
			<?php endif; ?>

			</div>

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
