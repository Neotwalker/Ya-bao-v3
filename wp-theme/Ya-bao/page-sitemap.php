<?php
/**
 * Template Name: Карта сайта
 * Template Post Type: page
 *
 * Human-readable production sitemap built from current WordPress content.
 * No static URL list is duplicated from the reference HTML.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_filter(
	'body_class',
	static function ( array $classes ): array {
		$classes[] = 'page-sitemap';
		return array_values( array_unique( $classes ) );
	}
);

wp_enqueue_style(
	'yabao-sitemap',
	yabao_asset_url( 'css/sitemap.css' ),
	array( 'yabao-pages' ),
	yabao_asset_version( 'css/sitemap.css' )
);

get_header();

$current_page_id = get_queried_object_id();

$utility_page_ids = array_filter(
	array(
		$current_page_id,
		function_exists( 'wc_get_page_id' ) ? wc_get_page_id( 'cart' ) : 0,
		function_exists( 'wc_get_page_id' ) ? wc_get_page_id( 'checkout' ) : 0,
		function_exists( 'wc_get_page_id' ) ? wc_get_page_id( 'myaccount' ) : 0,
	)
);

/*
 * Keep utility pages out of the human-readable sitemap even when WooCommerce
 * page assignments are missing or incomplete in a local/staging database.
 */
$utility_page_paths = array(
	'cart',
	'checkout',
	'my-account',
);

$blog_page = (int) get_option( 'page_for_posts' );
if ( $blog_page <= 0 ) {
	$blog_page_object = get_page_by_path( 'blog', OBJECT, 'page' );
	$blog_page        = $blog_page_object ? (int) $blog_page_object->ID : 0;
}

$published_pages = get_posts(
	array(
		'post_type'      => 'page',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'orderby'        => 'menu_order title',
		'order'          => 'ASC',
	)
);

$main_pages  = array();
$legal_pages = array();

foreach ( $published_pages as $published_page ) {
	$page_id       = (int) $published_page->ID;
	$page_template = get_page_template_slug( $page_id );
	$page_path     = trim( (string) get_page_uri( $page_id ), '/' );

	if (
		in_array( $page_id, $utility_page_ids, true )
		|| in_array( $page_path, $utility_page_paths, true )
	) {
		continue;
	}

	if ( $blog_page > 0 && $page_id === $blog_page ) {
		continue;
	}

	if ( 'page-legal.php' === $page_template ) {
		$legal_pages[] = $published_page;
		continue;
	}

	/*
	 * The legacy category landing page is intentionally excluded. Product
	 * categories are represented as filter states inside /shop/.
	 */
	if ( 'category' === $published_page->post_name ) {
		continue;
	}

	$main_pages[] = $published_page;
}

$blog_posts = get_posts(
	array(
		'post_type'      => 'post',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'orderby'        => 'date',
		'order'          => 'DESC',
	)
);

$event_archive_url = post_type_exists( 'event' )
	? get_post_type_archive_link( 'event' )
	: '';

$event_posts = post_type_exists( 'event' )
	? get_posts(
		array(
			'post_type'      => 'event',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => 'date',
			'order'          => 'DESC',
		)
	)
	: array();
?>
<main id="main-content">
	<?php while ( have_posts() ) : the_post(); ?>
		<section class="section section--dark section--compact inner-hero">
			<div class="container inner-hero__grid">
				<div>
					<nav aria-label="Хлебные крошки" class="breadcrumbs breadcrumbs--hero">
						<ol>
							<li><a href="<?php echo esc_url( home_url( '/' ) ); ?>">Главная</a></li>
							<li><span aria-current="page"><?php the_title(); ?></span></li>
						</ol>
					</nav>
					<h1><?php the_title(); ?></h1>
				</div>
				<p class="inner-hero__text">Все основные разделы, материалы блога, афиша и юридическая информация «Я Бао Завари» в одном месте.</p>
			</div>
		</section>

		<section class="section section--paper">
			<div class="container">
				<div class="section-heading reveal">
					<div>
						<p class="eyebrow">Навигация</p>
						<h2>Все страницы сайта</h2>
					</div>
					<p>Можно сразу перейти к нужному разделу, статье, мероприятию или юридической информации.</p>
				</div>

				<div class="sitemap-grid sitemap-grid--full">
					<?php if ( $main_pages || $event_archive_url || $legal_pages ) : ?>
						<section class="sitemap-card sitemap-card--full reveal">
							<h3>Основные разделы</h3>

							<div class="sitemap-card__groups">
								<div class="sitemap-card__group">
									<ul class="sitemap-card__columns">
										<?php foreach ( $main_pages as $main_page ) : ?>
											<li>
												<a href="<?php echo esc_url( get_permalink( $main_page ) ); ?>">
													<?php echo esc_html( get_the_title( $main_page ) ); ?>
												</a>
											</li>
										<?php endforeach; ?>

										<?php if ( $event_archive_url ) : ?>
											<li><a href="<?php echo esc_url( $event_archive_url ); ?>">Мероприятия</a></li>
										<?php endif; ?>
									</ul>
								</div>

								<?php if ( $legal_pages ) : ?>
									<div class="sitemap-card__group sitemap-card__group--legal">
										<h4>Юридическая информация</h4>
										<ul class="sitemap-card__columns">
											<?php foreach ( $legal_pages as $legal_page ) : ?>
												<li>
													<a href="<?php echo esc_url( get_permalink( $legal_page ) ); ?>">
														<?php echo esc_html( get_the_title( $legal_page ) ); ?>
													</a>
												</li>
											<?php endforeach; ?>
										</ul>
									</div>
								<?php endif; ?>
							</div>
						</section>
					<?php endif; ?>

					<?php if ( $blog_page > 0 || $blog_posts ) : ?>
						<section class="sitemap-card sitemap-card--full reveal">
							<h3>Блог</h3>
							<ul class="sitemap-card__columns">
								<?php if ( $blog_page > 0 ) : ?>
									<li><a href="<?php echo esc_url( get_permalink( $blog_page ) ); ?>">Все статьи</a></li>
								<?php endif; ?>

								<?php foreach ( $blog_posts as $blog_post ) : ?>
									<li>
										<a href="<?php echo esc_url( get_permalink( $blog_post ) ); ?>">
											<?php echo esc_html( get_the_title( $blog_post ) ); ?>
										</a>
									</li>
								<?php endforeach; ?>
							</ul>
						</section>
					<?php endif; ?>

					<?php if ( $event_posts ) : ?>
						<section class="sitemap-card sitemap-card--full reveal">
							<h3>Афиша</h3>
							<ul class="sitemap-card__columns">
								<?php foreach ( $event_posts as $event_post ) : ?>
									<li>
										<a href="<?php echo esc_url( get_permalink( $event_post ) ); ?>">
											<?php echo esc_html( get_the_title( $event_post ) ); ?>
										</a>
									</li>
								<?php endforeach; ?>
							</ul>
						</section>
					<?php endif; ?>
				</div>
			</div>
		</section>
	<?php endwhile; ?>
</main>
<?php get_footer(); ?>
