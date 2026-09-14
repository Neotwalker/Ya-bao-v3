<?php
/**
 * Template Name: Чайная церемония
 * Template Post Type: page
 *
 * Production template for /chaynaya-ceremoniya/.
 * Page-specific editorial content lives in ACF Local JSON.
 * Shared booking/contact data and internal URLs reuse existing sources of truth.
 */

if ( function_exists( 'yabao_asset_url' ) && function_exists( 'yabao_asset_version' ) ) {
	wp_enqueue_style(
		'yabao-home',
		yabao_asset_url( 'css/home-v4.css' ),
		array( 'yabao-pages' ),
		yabao_asset_version( 'css/home-v4.css' )
	);
}

add_filter(
	'body_class',
	static function ( array $classes ): array {
		$classes[] = 'page-ceremony';
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

$get_image_id = static function ( string $name ): int {
	if ( ! function_exists( 'get_field' ) ) {
		return 0;
	}

	$value = get_field( $name );
	if ( is_array( $value ) && isset( $value['ID'] ) ) {
		$value = $value['ID'];
	}

	return absint( $value );
};

$get_pair_rows = static function ( string $name, string $title_key, string $text_key ): array {
	if ( ! function_exists( 'get_field' ) ) {
		return array();
	}

	$rows = get_field( $name );
	if ( ! is_array( $rows ) ) {
		return array();
	}

	$result = array();

	foreach ( $rows as $row ) {
		if ( ! is_array( $row ) ) {
			continue;
		}

		$title = isset( $row[ $title_key ] ) && is_scalar( $row[ $title_key ] )
			? trim( wp_strip_all_tags( (string) $row[ $title_key ] ) )
			: '';

		$text = isset( $row[ $text_key ] ) && is_scalar( $row[ $text_key ] )
			? trim( wp_strip_all_tags( (string) $row[ $text_key ] ) )
			: '';

		if ( '' === $title || '' === $text ) {
			continue;
		}

		$result[] = array(
			'title' => $title,
			'text'  => $text,
		);
	}

	return $result;
};

$get_text_rows = static function ( string $name, string $text_key ): array {
	if ( ! function_exists( 'get_field' ) ) {
		return array();
	}

	$rows = get_field( $name );
	if ( ! is_array( $rows ) ) {
		return array();
	}

	$result = array();

	foreach ( $rows as $row ) {
		if ( ! is_array( $row ) ) {
			continue;
		}

		$text = isset( $row[ $text_key ] ) && is_scalar( $row[ $text_key ] )
			? trim( wp_strip_all_tags( (string) $row[ $text_key ] ) )
			: '';

		if ( '' !== $text ) {
			$result[] = $text;
		}
	}

	return $result;
};

$hero_title = $get_text( 'ceremony_hero_title' );
$hero_intro = $get_text( 'ceremony_hero_intro' );

$intro_image_id   = $get_image_id( 'ceremony_intro_image' );
$intro_title      = $get_text( 'ceremony_intro_title' );
$intro_lead       = $get_text( 'ceremony_intro_lead' );
$intro_steps      = $get_pair_rows( 'ceremony_intro_steps', 'step_title', 'step_text' );
$intro_shop_label = $get_text( 'ceremony_intro_shop_label' );

$experience_title = $get_text( 'ceremony_experience_title' );
$experience_intro = $get_text( 'ceremony_experience_intro' );
$experience_cards = $get_pair_rows( 'ceremony_experience_cards', 'card_title', 'card_text' );

$beginner_title      = $get_text( 'ceremony_beginner_title' );
$beginner_lead       = $get_text( 'ceremony_beginner_lead' );
$beginner_points     = $get_text_rows( 'ceremony_beginner_points', 'point_text' );
$beginner_image_id   = $get_image_id( 'ceremony_beginner_image' );
$beginner_blog_label = $get_text( 'ceremony_beginner_blog_label' );

$admin_title = $get_text( 'ceremony_admin_title' );
$admin_intro = $get_text( 'ceremony_admin_intro' );
$admin_cards = $get_pair_rows( 'ceremony_admin_cards', 'card_title', 'card_text' );

$location_title = $get_text( 'ceremony_location_title' );

$final_title          = $get_text( 'ceremony_final_title' );
$final_text           = $get_text( 'ceremony_final_text' );
$final_contacts_label = $get_text( 'ceremony_final_contacts_label' );

$booking_label = function_exists( 'yabao_site_text' ) ? yabao_site_text( 'booking_cta_label' ) : '';
$shop_url      = function_exists( 'yabao_wc_page_url' ) ? yabao_wc_page_url( 'shop' ) : '';
$blog_url      = function_exists( 'yabao_page_url' ) ? yabao_page_url( 'blog' ) : '';
$contacts_url  = function_exists( 'yabao_page_url' ) ? yabao_page_url( 'contacts' ) : '';

$site_name     = function_exists( 'yabao_site_text' ) ? yabao_site_text( 'site_name' ) : '';
$address       = function_exists( 'yabao_site_group_text' ) ? yabao_site_group_text( 'address', 'display' ) : '';
$opening_hours = function_exists( 'yabao_site_text' ) ? yabao_site_text( 'opening_hours_text' ) : '';
$two_gis_url   = function_exists( 'yabao_site_url' ) ? yabao_site_url( 'two_gis_url' ) : '';
$yandex_url    = function_exists( 'yabao_site_url' ) ? yabao_site_url( 'yandex_maps_url' ) : '';

$location_copy_parts = array();

if ( '' !== $site_name && '' !== $address ) {
	$location_copy_parts[] = sprintf( '«%s» находится по адресу: %s.', $site_name, $address );
} elseif ( '' !== $address ) {
	$location_copy_parts[] = sprintf( 'Адрес: %s.', $address );
}

if ( '' !== $opening_hours ) {
	$location_copy_parts[] = sprintf( 'Подтверждённый график работы: %s.', $opening_hours );
}

$location_copy = implode( ' ', $location_copy_parts );

$show_intro_copy =
	'' !== $intro_title ||
	'' !== $intro_lead ||
	! empty( $intro_steps ) ||
	( '' !== $intro_shop_label && '' !== $shop_url );

$show_intro      = $intro_image_id || $show_intro_copy;
$show_experience = '' !== $experience_title || '' !== $experience_intro || ! empty( $experience_cards );

$show_beginner_copy =
	'' !== $beginner_title ||
	'' !== $beginner_lead ||
	! empty( $beginner_points ) ||
	( '' !== $beginner_blog_label && '' !== $blog_url );

$show_beginner = $beginner_image_id || $show_beginner_copy;
$show_admin    = '' !== $admin_title || '' !== $admin_intro || ! empty( $admin_cards );

$show_location =
	'' !== $location_title &&
	(
		'' !== $location_copy ||
		'' !== $two_gis_url ||
		'' !== $yandex_url
	);

$show_final =
	'' !== $final_title ||
	'' !== $final_text ||
	( '' !== $final_contacts_label && '' !== $contacts_url );
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

		<?php if ( $show_intro ) : ?>
			<section class="section section--paper ceremony-v4">
				<div class="container<?php echo $intro_image_id && $show_intro_copy ? ' ceremony-v4__grid' : ''; ?>">

					<?php if ( $intro_image_id ) : ?>
						<div class="ceremony-v4__media reveal">
							<?php
							echo wp_get_attachment_image(
								$intro_image_id,
								'large',
								false,
								array(
									'loading'       => 'eager',
									'fetchpriority' => 'high',
									'decoding'      => 'async',
									'sizes'         => '(max-width: 1024px) calc(100vw - 44px), 50vw',
								)
							); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							?>
						</div>
					<?php endif; ?>

					<?php if ( $show_intro_copy ) : ?>
						<div class="ceremony-v4__copy reveal">
							<?php if ( '' !== $intro_title ) : ?>
								<h2><?php echo esc_html( $intro_title ); ?></h2>
							<?php endif; ?>

							<?php if ( '' !== $intro_lead ) : ?>
								<p class="lead"><?php echo esc_html( $intro_lead ); ?></p>
							<?php endif; ?>

							<?php if ( ! empty( $intro_steps ) ) : ?>
								<ol class="ceremony-steps-v4">
									<?php foreach ( $intro_steps as $index => $step ) : ?>
										<li>
											<span><?php echo esc_html( (string) ( $index + 1 ) ); ?></span>
											<div>
												<strong><?php echo esc_html( $step['title'] ); ?></strong>
												<p><?php echo esc_html( $step['text'] ); ?></p>
											</div>
										</li>
									<?php endforeach; ?>
								</ol>
							<?php endif; ?>

							<?php if ( '' !== $booking_label || ( '' !== $intro_shop_label && '' !== $shop_url ) ) : ?>
								<div class="ceremony-v4__actions">
									<?php if ( '' !== $booking_label ) : ?>
										<button class="button button--primary" data-ceremony="first" data-modal-open data-source="ceremony-page-intro" type="button"><?php echo esc_html( $booking_label ); ?></button>
									<?php endif; ?>
									<?php if ( '' !== $intro_shop_label && '' !== $shop_url ) : ?>
										<a class="button button--light ceremony-v4__secondary" href="<?php echo esc_url( $shop_url ); ?>"><?php echo esc_html( $intro_shop_label ); ?> <span aria-hidden="true">→</span></a>
									<?php endif; ?>
								</div>
							<?php endif; ?>
						</div>
					<?php endif; ?>
				</div>
			</section>
		<?php endif; ?>

		<?php if ( $show_experience ) : ?>
			<section class="section section--dark">
				<div class="container">
					<?php if ( '' !== $experience_title || '' !== $experience_intro ) : ?>
						<div class="section-heading reveal">
							<?php if ( '' !== $experience_title ) : ?><div><h2><?php echo esc_html( $experience_title ); ?></h2></div><?php endif; ?>
							<?php if ( '' !== $experience_intro ) : ?><p><?php echo esc_html( $experience_intro ); ?></p><?php endif; ?>
						</div>
					<?php endif; ?>
					<?php if ( ! empty( $experience_cards ) ) : ?>
						<div class="cards-grid cards-grid--three">
							<?php foreach ( $experience_cards as $card ) : ?>
								<article class="card feature-panel reveal"><div class="card__body"><h3><?php echo esc_html( $card['title'] ); ?></h3><p><?php echo esc_html( $card['text'] ); ?></p></div></article>
							<?php endforeach; ?>
						</div>
					<?php endif; ?>
				</div>
			</section>
		<?php endif; ?>

		<?php if ( $show_beginner ) : ?>
			<section class="section beginner-v4">
				<div class="container<?php echo $show_beginner_copy && $beginner_image_id ? ' beginner-v4__grid' : ''; ?>">
					<?php if ( $show_beginner_copy ) : ?>
						<div class="beginner-v4__copy reveal">
							<?php if ( '' !== $beginner_title ) : ?><h2><?php echo esc_html( $beginner_title ); ?></h2><?php endif; ?>
							<?php if ( '' !== $beginner_lead ) : ?><p class="lead"><?php echo esc_html( $beginner_lead ); ?></p><?php endif; ?>
							<?php if ( ! empty( $beginner_points ) ) : ?>
								<ul class="plain-checks"><?php foreach ( $beginner_points as $point ) : ?><li><?php echo esc_html( $point ); ?></li><?php endforeach; ?></ul>
							<?php endif; ?>
							<?php if ( '' !== $beginner_blog_label && '' !== $blog_url ) : ?><a class="button button--walnut" href="<?php echo esc_url( $blog_url ); ?>"><?php echo esc_html( $beginner_blog_label ); ?></a><?php endif; ?>
						</div>
					<?php endif; ?>
					<?php if ( $beginner_image_id ) : ?>
						<div class="beginner-v4__media reveal">
							<?php echo wp_get_attachment_image( $beginner_image_id, 'large', false, array( 'loading' => 'lazy', 'decoding' => 'async', 'sizes' => '(max-width: 1024px) calc(100vw - 44px), 50vw' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						</div>
					<?php endif; ?>
				</div>
			</section>
		<?php endif; ?>

		<?php if ( $show_admin ) : ?>
			<section class="section section--paper">
				<div class="container">
					<?php if ( '' !== $admin_title || '' !== $admin_intro ) : ?>
						<div class="section-heading reveal">
							<?php if ( '' !== $admin_title ) : ?><div><h2><?php echo esc_html( $admin_title ); ?></h2></div><?php endif; ?>
							<?php if ( '' !== $admin_intro ) : ?><p><?php echo esc_html( $admin_intro ); ?></p><?php endif; ?>
						</div>
					<?php endif; ?>
					<?php if ( ! empty( $admin_cards ) ) : ?>
						<div class="cards-grid cards-grid--three">
							<?php foreach ( $admin_cards as $card ) : ?><article class="card reveal"><div class="card__body"><h3><?php echo esc_html( $card['title'] ); ?></h3><p><?php echo esc_html( $card['text'] ); ?></p></div></article><?php endforeach; ?>
						</div>
					<?php endif; ?>
				</div>
			</section>
		<?php endif; ?>

		<?php if ( $show_location ) : ?>
			<section class="section section--dark">
				<div class="container">
					<div class="section-heading reveal">
						<div><h2><?php echo esc_html( $location_title ); ?></h2></div>
						<?php if ( '' !== $location_copy ) : ?><p><?php echo esc_html( $location_copy ); ?></p><?php endif; ?>
					</div>
					<?php if ( '' !== $two_gis_url || '' !== $yandex_url ) : ?>
						<div class="contact-actions reveal">
							<?php if ( '' !== $two_gis_url ) : ?><a class="button button--primary" href="<?php echo esc_url( $two_gis_url ); ?>" rel="noopener" target="_blank">Открыть в 2ГИС</a><?php endif; ?>
							<?php if ( '' !== $yandex_url ) : ?><a class="button button--light" href="<?php echo esc_url( $yandex_url ); ?>" rel="noopener" target="_blank">Яндекс Карты</a><?php endif; ?>
						</div>
					<?php endif; ?>
				</div>
			</section>
		<?php endif; ?>

		<?php if ( $show_final ) : ?>
			<section class="section final-cta-v4">
				<div class="container">
					<div class="final-cta-v4__panel reveal">
						<?php if ( '' !== $final_title || '' !== $final_text ) : ?>
							<div>
								<?php if ( '' !== $final_title ) : ?><h2><?php echo esc_html( $final_title ); ?></h2><?php endif; ?>
								<?php if ( '' !== $final_text ) : ?><p><?php echo esc_html( $final_text ); ?></p><?php endif; ?>
							</div>
						<?php endif; ?>
						<?php if ( '' !== $booking_label || ( '' !== $final_contacts_label && '' !== $contacts_url ) ) : ?>
							<div class="final-cta-v4__actions">
								<?php if ( '' !== $booking_label ) : ?><button class="button button--primary" data-ceremony="first" data-modal-open data-source="ceremony-page-final" type="button"><?php echo esc_html( $booking_label ); ?></button><?php endif; ?>
								<?php if ( '' !== $final_contacts_label && '' !== $contacts_url ) : ?><a class="button button--light" href="<?php echo esc_url( $contacts_url ); ?>"><?php echo esc_html( $final_contacts_label ); ?></a><?php endif; ?>
							</div>
						<?php endif; ?>
					</div>
				</div>
			</section>
		<?php endif; ?>

	<?php endwhile; ?>
</main>
<?php get_footer(); ?>
