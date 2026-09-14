<?php
/**
 * Template Name: Юридическая страница
 * Template Post Type: page
 *
 * Shared production template for /privacy/ and /consent/.
 * Page-specific hero copy comes from ACF Local JSON, long-form legal copy
 * stays in the native WordPress editor, and operator details reuse global ACF.
 */

add_filter(
	'wp_robots',
	static function ( array $robots ): array {
		$robots['noindex'] = true;
		$robots['follow']  = true;

		unset( $robots['index'], $robots['nofollow'] );

		return $robots;
	}
);

add_filter(
	'body_class',
	static function ( array $classes ): array {
		$classes[] = 'page-legal';

		$slug = get_post_field( 'post_name', get_queried_object_id() );
		if ( is_string( $slug ) && '' !== $slug ) {
			$classes[] = 'page-' . sanitize_html_class( $slug );
		}

		return array_values( array_unique( $classes ) );
	}
);

get_header();

$get_text = static function ( string $name ): string {
	if ( ! function_exists( 'get_field' ) ) {
		return '';
	}

	$value = get_field( $name );
	if ( ! is_string( $value ) ) {
		return '';
	}

	return trim( wp_strip_all_tags( $value ) );
};

$hero_title = $get_text( 'legal_hero_title' );
$hero_intro = $get_text( 'legal_hero_intro' );

$legal_rows = array(
	array(
		'label' => 'Оператор',
		'value' => function_exists( 'yabao_site_text' ) ? yabao_site_text( 'legal_name' ) : '',
	),
	array(
		'label' => 'ИНН',
		'value' => function_exists( 'yabao_site_text' ) ? yabao_site_text( 'inn' ) : '',
	),
	array(
		'label' => 'ОГРН',
		'value' => function_exists( 'yabao_site_text' ) ? yabao_site_text( 'ogrn' ) : '',
	),
	array(
		'label' => 'Юридический адрес',
		'value' => function_exists( 'yabao_site_text' ) ? yabao_site_text( 'legal_address' ) : '',
	),
);

$legal_rows = array_values(
	array_filter(
		$legal_rows,
		static function ( array $row ): bool {
			return isset( $row['value'] ) && '' !== (string) $row['value'];
		}
	)
);
?>
<main id="main-content">
	<?php while ( have_posts() ) : the_post(); ?>

		<?php if ( '' !== $hero_title ) : ?>
			<section class="section section--dark section--compact inner-hero">
				<div class="container inner-hero__grid">
					<div>
						<nav aria-label="Хлебные крошки" class="breadcrumbs breadcrumbs--hero">
							<ol>
								<li><a href="<?php echo esc_url( home_url( '/' ) ); ?>">Главная</a></li>
								<li><span aria-current="page"><?php the_title(); ?></span></li>
							</ol>
						</nav>
						<h1><?php echo esc_html( $hero_title ); ?></h1>
					</div>

					<?php if ( '' !== $hero_intro ) : ?>
						<p class="inner-hero__text"><?php echo esc_html( $hero_intro ); ?></p>
					<?php endif; ?>
				</div>
			</section>
		<?php endif; ?>

		<?php
		$raw_content = trim( (string) get_post_field( 'post_content', get_the_ID() ) );
		$show_legal  = ! empty( $legal_rows ) || '' !== $raw_content;
		?>

		<?php if ( $show_legal ) : ?>
			<section class="section section--paper">
				<div class="container content-prose legal-content wp-entry-content">
					<?php if ( ! empty( $legal_rows ) ) : ?>
						<table class="legal-details">
							<caption class="visually-hidden">Реквизиты оператора персональных данных</caption>
							<tbody>
								<?php foreach ( $legal_rows as $row ) : ?>
									<tr>
										<th scope="row"><?php echo esc_html( $row['label'] ); ?></th>
										<td><?php echo esc_html( $row['value'] ); ?></td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					<?php endif; ?>

					<?php if ( '' !== $raw_content ) : ?>
						<?php the_content(); ?>
					<?php endif; ?>
				</div>
			</section>
		<?php endif; ?>

	<?php endwhile; ?>
</main>
<?php get_footer(); ?>
