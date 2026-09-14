<?php
/**
 * Template Name: Контакты
 * Template Post Type: page
 *
 * Production template for /contacts/.
 * Page-specific editorial content lives in ACF Local JSON.
 * Shared contact data comes only from global site settings.
 */

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

$get_url = static function ( string $name ): string {
	if ( ! function_exists( 'get_field' ) ) {
		return '';
	}

	$value = get_field( $name );
	if ( ! is_string( $value ) || '' === trim( $value ) ) {
		return '';
	}

	return esc_url_raw( trim( $value ) );
};

$hero_title       = $get_text( 'contacts_hero_title' );
$hero_intro       = $get_text( 'contacts_hero_intro' );
$contact_eyebrow  = $get_text( 'contacts_card_eyebrow' );
$map_embed_url    = $get_url( 'contacts_map_embed_url' );
$features_eyebrow = $get_text( 'contacts_features_eyebrow' );
$features_title   = $get_text( 'contacts_features_title' );
$features_intro   = $get_text( 'contacts_features_intro' );

$site_name     = function_exists( 'yabao_site_text' ) ? yabao_site_text( 'site_name' ) : '';
$address       = function_exists( 'yabao_site_group_text' ) ? yabao_site_group_text( 'address', 'display' ) : '';
$opening_hours = function_exists( 'yabao_site_text' ) ? yabao_site_text( 'opening_hours_text' ) : '';
$phone         = function_exists( 'yabao_site_text' ) ? yabao_site_text( 'phone' ) : '';
$phone_href    = function_exists( 'yabao_site_phone_href' ) ? yabao_site_phone_href() : '';
$two_gis_url   = function_exists( 'yabao_site_url' ) ? yabao_site_url( 'two_gis_url' ) : '';
$yandex_url    = function_exists( 'yabao_site_url' ) ? yabao_site_url( 'yandex_maps_url' ) : '';

$social_links = array();
if ( function_exists( 'yabao_site_social_links' ) ) {
	$seen_social_urls = array();

	foreach ( array( 'footer', 'header', 'mobile' ) as $location ) {
		foreach ( yabao_site_social_links( $location ) as $social ) {
			$url = isset( $social['url'] ) && is_string( $social['url'] ) ? $social['url'] : '';
			if ( '' === $url || isset( $seen_social_urls[ $url ] ) ) {
				continue;
			}

			$seen_social_urls[ $url ] = true;
			$social_links[]            = $social;
		}
	}
}

$features = array();
if ( function_exists( 'get_field' ) ) {
	$raw_features = get_field( 'contacts_features' );
	if ( is_array( $raw_features ) ) {
		foreach ( $raw_features as $raw_feature ) {
			if ( ! is_array( $raw_feature ) ) {
				continue;
			}

			$title = isset( $raw_feature['feature_title'] ) && is_string( $raw_feature['feature_title'] )
				? trim( wp_strip_all_tags( $raw_feature['feature_title'] ) )
				: '';
			$text = isset( $raw_feature['feature_text'] ) && is_string( $raw_feature['feature_text'] )
				? trim( wp_strip_all_tags( $raw_feature['feature_text'] ) )
				: '';

			if ( '' === $title || '' === $text ) {
				continue;
			}

			$features[] = array(
				'title' => $title,
				'text'  => $text,
			);
		}
	}
}

$show_contact_card = $contact_eyebrow || $site_name || $address || $opening_hours || ( $phone && $phone_href ) || ! empty( $social_links ) || $two_gis_url || $yandex_url;
$show_contact      = $show_contact_card || $map_embed_url;
$show_features     = $features_eyebrow || $features_title || $features_intro || ! empty( $features );
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

		<?php if ( $show_contact ) : ?>
			<section class="section section--paper section--contact">
				<div class="container<?php echo $show_contact_card && $map_embed_url ? ' contact-grid' : ''; ?>">
					<?php if ( $show_contact_card ) : ?>
						<div class="contact-card reveal">
							<?php if ( '' !== $contact_eyebrow ) : ?>
								<p class="eyebrow"><?php echo esc_html( $contact_eyebrow ); ?></p>
							<?php endif; ?>
							<?php if ( '' !== $site_name ) : ?>
								<h2><?php echo esc_html( $site_name ); ?></h2>
							<?php endif; ?>

							<?php if ( $address || $opening_hours || ( $phone && $phone_href ) || ! empty( $social_links ) ) : ?>
								<ul class="contact-list">
									<?php if ( '' !== $address ) : ?>
										<li><small>Адрес</small><strong><?php echo esc_html( $address ); ?></strong></li>
									<?php endif; ?>
									<?php if ( '' !== $opening_hours ) : ?>
										<li><small>Время работы</small><strong><?php echo esc_html( $opening_hours ); ?></strong></li>
									<?php endif; ?>
									<?php if ( '' !== $phone && '' !== $phone_href ) : ?>
										<li><small>Телефон</small><a href="<?php echo esc_url( $phone_href ); ?>"><?php echo esc_html( $phone ); ?></a></li>
									<?php endif; ?>
									<?php if ( ! empty( $social_links ) ) : ?>
										<li>
											<small>Социальные сети</small>
											<?php foreach ( $social_links as $index => $social ) : ?>
												<?php if ( $index > 0 ) : ?><span aria-hidden="true"> · </span><?php endif; ?>
												<a href="<?php echo esc_url( $social['url'] ); ?>" rel="noopener" target="_blank"><?php echo esc_html( $social['label'] ); ?></a>
											<?php endforeach; ?>
										</li>
									<?php endif; ?>
								</ul>
							<?php endif; ?>

							<?php if ( $two_gis_url || $yandex_url ) : ?>
								<div class="contact-actions">
									<?php if ( $two_gis_url ) : ?>
										<a class="button button--light" href="<?php echo esc_url( $two_gis_url ); ?>" rel="noopener" target="_blank">Перейти в 2ГИС</a>
									<?php endif; ?>
									<?php if ( $yandex_url ) : ?>
										<a class="button button--walnut" href="<?php echo esc_url( $yandex_url ); ?>" rel="noopener" target="_blank">Яндекс Карты</a>
									<?php endif; ?>
								</div>
							<?php endif; ?>
						</div>
					<?php endif; ?>

					<?php if ( $map_embed_url ) : ?>
						<div class="map-frame">
							<iframe allowfullscreen loading="eager" referrerpolicy="no-referrer-when-downgrade" src="<?php echo esc_url( $map_embed_url ); ?>" title="<?php echo esc_attr( $site_name ? $site_name . ' на Яндекс Картах' : 'Карта' ); ?>"></iframe>
							<?php if ( $yandex_url ) : ?>
								<a class="map-frame__fallback" href="<?php echo esc_url( $yandex_url ); ?>" rel="noopener" target="_blank">Открыть карту отдельно ↗</a>
							<?php endif; ?>
						</div>
					<?php endif; ?>
				</div>
			</section>
		<?php endif; ?>

		<?php if ( $show_features ) : ?>
			<section class="section section--paper">
				<div class="container">
					<?php if ( $features_eyebrow || $features_title || $features_intro ) : ?>
						<div class="section-heading reveal">
							<?php if ( $features_eyebrow || $features_title ) : ?>
								<div>
									<?php if ( '' !== $features_eyebrow ) : ?>
										<p class="eyebrow"><?php echo esc_html( $features_eyebrow ); ?></p>
									<?php endif; ?>
									<?php if ( '' !== $features_title ) : ?>
										<h2><?php echo esc_html( $features_title ); ?></h2>
									<?php endif; ?>
								</div>
							<?php endif; ?>
							<?php if ( '' !== $features_intro ) : ?>
								<p><?php echo esc_html( $features_intro ); ?></p>
							<?php endif; ?>
						</div>
					<?php endif; ?>

					<?php if ( ! empty( $features ) ) : ?>
						<div class="cards-grid cards-grid--three">
							<?php foreach ( $features as $feature ) : ?>
								<article class="card reveal">
									<div class="card__body">
										<h3><?php echo esc_html( $feature['title'] ); ?></h3>
										<p><?php echo esc_html( $feature['text'] ); ?></p>
									</div>
								</article>
							<?php endforeach; ?>
						</div>
					<?php endif; ?>
				</div>
			</section>
		<?php endif; ?>
	<?php endwhile; ?>
</main>
<?php get_footer(); ?>
